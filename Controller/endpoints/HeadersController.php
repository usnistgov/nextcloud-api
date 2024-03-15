<?php

namespace NamespaceFunction;

/**
     * "/headers/" Endpoint - prints headers from API call
     */
    class HeadersController extends \NamespaceBase\BaseController {
        public function handle()
        {
        $strErrorDesc = "";
        $strErrorHeader = "";
        $requestMethod = $this->getRequestMethod();
        $arrQueryStringParams = $this->getQueryStringParams();
        $arrQueryUri = $this->getUriSegments();

        $headers = apache_request_headers();

        $this->sendOkayOutput(json_encode($headers));

        return $headers;
    }
}