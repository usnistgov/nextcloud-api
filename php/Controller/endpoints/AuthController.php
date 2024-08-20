<?php

namespace NamespaceFunction;

use GuzzleHttp\Client;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use OCA\User_SAML\SAMLSettings;
use OCA\User_SAML\GroupManager;
use OCA\User_SAML\GroupBackend;
use OCA\User_SAML\UserBackend;
use OCA\User_SAML\UserData;
use OCA\User_SAML\UserResolver;
use Psr\Log\LoggerInterface;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use OCP\IConfig;
use OCP\ISession;
use OCP\Security\ISecureRandom;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\BackgroundJob\IJobList;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;

class AuthController extends \NamespaceBase\BaseController
{
    public function handle()
    {
        $this->logger->info("Handling Auth", ['method' => $this->getRequestMethod(), 'uri' => $this->getUriSegments()]);

        try {
            $requestMethod = $this->getRequestMethod();
            $arrQueryUri = $this->getUriSegments();
            $queryUri = $this->getUri();

            switch ($requestMethod) {
                case 'POST':
                    if (count($arrQueryUri) == 5) {
                        // "/genapi.php/auth/{user}" Endpoint - Generate Temporary pwd for user
                        $this->createTemporaryPassword($arrQueryUri[4]);
                    } else {
                        return $this->sendUnsupportedEndpointResponse($requestMethod, $queryUri);
                    }
                    break;
                default:
                    return $this->sendUnsupportedEndpointResponse($requestMethod, $queryUri);
                    break;
            }
        } catch (\Exception $e) {
            $this->logger->error("Exception occurred in auth handle method", ['exception' => $e->getMessage()]);
            return $this->sendError400Output($e->getMessage());
        }
    }

    /**
     * "-X POST /auth/{user}" Endpoint - creates temporary password for a user
     */
    private function createTemporaryPassword($user)
    {
        require_once '/var/www/html/lib/base.php';
        $config = require '/var/www/api/config/custom_config.php';

        try {
            $this->logger->info("Starting session initialization");
            \OC::initSession();
            $this->logger->info("Session initialized");

            $this->logger->info("Handling Auth Endpoint", ['User' => $user]);

            // Verify user existence using the occ command
            $userExists = $this->verifyUserExists($user);
            if (!$userExists) {
                $this->logger->info("User does not exist", ['User' => $user]);
                return $this->sendError404Output("User does not exist");
            }
            $this->logger->info("User exists", ['User' => $user]);

            // Retrieve normal nextcloud user
            $userManager = \OC::$server->getUserManager();
            $this->logger->info("UserManager retrieved", ['User' => $user]);
            $userObject = $userManager->get($user);

            if ($userObject === null) {
                // User is a SAML user
                $this->logger->info("Manually loading SAML backend");

                // Fetch dependencies for SAML backend
                $config = \OC::$server->getConfig();
                $urlGenerator = \OC::$server->getURLGenerator();
                $session = \OC::$server->getSession();
                $db = \OC::$server->getDatabaseConnection();
                $groupManager = \OC::$server->get(IGroupManager::class);
                $settings = \OC::$server->get(SAMLSettings::class);
                $logger = \OC::$server->get(LoggerInterface::class);
                $userResolver = new UserResolver($userManager);
                $userData = new UserData($userResolver, $settings);
                $eventDispatcher = \OC::$server->get(IEventDispatcher::class);
                $groupBackend = new GroupBackend($db, $logger, $config, $eventDispatcher, $settings);
                $tokenProvider = \OC::$server->query(IProvider::class);

                // Instantiate the SAML backend with required dependencies
                $samlBackend = new UserBackend(
                    $config,
                    $urlGenerator,
                    $session,
                    $db,
                    $userManager,
                    new GroupManager($db, $groupManager, $groupBackend, $config, $eventDispatcher, \OC::$server->get(IJobList::class), $settings),
                    $settings,
                    $logger,
                    $userData,
                    $eventDispatcher
                );
                $userManager->registerBackend($samlBackend);

                // Try to get the user object again after loading the SAML backend
                $userObject = $userManager->get($user);
                $this->logger->info("User Object after SAML backend load", ['User' => $userObject]);
            }

            if ($userObject === null) {
                $this->logger->error("User does not exist in any backend", ['User' => $user]);
                return $this->sendError404Output("User does not exist");
            }

            $this->logger->info("Setting user session", ['User' => $user]);
            \OC::$server->getUserSession()->setUser($userObject);
            $this->logger->info("User session set", ['User' => \OC::$server->getUserSession()->getUser()]);

            // Create the app password
            $this->logger->info("Generating temporary password", ['User' => $user]);
            $random = \OC::$server->get(ISecureRandom::class);
            $appPassword = $random->generate(72, ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS);

            // Generate a token using the token provider
            $tokenProvider = \OC::$server->query(IProvider::class);
            if ($tokenProvider === null) {
                $this->logger->error("Token provider is not available", ['User' => $user]);
                return $this->sendError500Output("Token provider is not available");
            }

            $userAgent = "Generated via API";
            $token = $tokenProvider->generateToken(
                $appPassword,
                $userObject->getUID(),
                $userObject->getUID(),
                null, // user pwd is not required
                $userAgent,
                IToken::TEMPORARY_TOKEN,
                IToken::DO_NOT_REMEMBER

            );
            $this->logger->info("Temporary password created successfully", ['User' => $user]);


            // Set expiration
            $passwordDuration = $config['app_password_duration'] ?? 57600;
            $expirationTime = time() + $passwordDuration;
            $token->setExpires($expirationTime);
            $tokenProvider->updateToken($token);

            $this->logger->info("Password will expire in " . $passwordDuration . "seconds.");
            return $this->sendOkayOutput(json_encode(['temporary_password' => $appPassword]));
        } catch (\Exception $e) {
            $this->logger->error("Exception occurred in createTemporaryPassword method", ['exception' => $e->getMessage()]);
            return $this->sendError500Output("An error occurred: " . $e->getMessage());
        }
    }

    private function verifyUserExists($user)
    {
        $command = parent::$occ . " user:info " . $user . " --output json";
        exec($command, $output, $returnVar);
        if ($returnVar === 0 && !empty($output)) {
            return true;
        } else {
            return false;
        }
    }
}
