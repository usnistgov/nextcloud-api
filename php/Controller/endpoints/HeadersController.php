<?php

namespace NamespaceFunction;

/**
 * HEADERS resource endpoint
 * 
 * GET
 * - /headers
 **/
class HeadersController extends \NamespaceBase\BaseController
{

    public function handle()
    {
        $this->logger->info("Handling Headers", ['method' => $this->getRequestMethod(), 'uri' => $this->getUriSegments()]);
        try {
            $headers = apache_request_headers();

            $this->logger->info("Headers retrieved successfully", ['headers' => $headers]);
            return $this->sendOkayOutput(json_encode($headers));
        } catch (\Exception $e) {
            $this->logger->error("Exception occurred in handling headers", ['exception' => $e->getMessage()]);
            return $this->sendError500Output('Failed to retrieve headers. ' . $e->getMessage());
        }
    }
}
