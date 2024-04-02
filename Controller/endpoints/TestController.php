<?php

namespace NamespaceFunction;

/**
 * TEST resource endpoint
 * 
 * GET
 * - /test
 **/
class TestController extends \NamespaceBase\BaseController
{
    public function handle()
    {
        $this->logger->info("Handling Test", ['method' => $this->getRequestMethod(), 'uri' => $this->getUriSegments()]);

        try {
            $requestMethod = $this->getRequestMethod();
            $arrQueryUri = $this->getUriSegments();
            array_unshift($arrQueryUri, $requestMethod);
            $responseData = json_encode($arrQueryUri);

            $this->logger->info("Handling Test Endpoint", [
                'method' => $requestMethod,
                'uriSegments' => $arrQueryUri
            ]);

            return $this->sendOkayOutput($responseData);
        } catch (\Exception $e) {
            $this->logger->error("Exception occurred in TestController handle method", [
                'exception' => $e->getMessage()
            ]);
            return $this->sendError500Output("An error occurred: " . $e->getMessage());
        }
    }
}
