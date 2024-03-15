<?php

namespace NamespaceFunction;

/**
 *"/test/" Endpoint - prints method with query uri
 */
class TestController extends \NamespaceBase\BaseController
{
    public function handle()
    {
        $strErrorDesc = "";
        $strErrorHeader = "";
        $requestMethod = $this->getRequestMethod();
        $arrQueryStringParams = $this->getQueryStringParams();
        $arrQueryUri = $this->getUriSegments();

        array_unshift($arrQueryUri, $requestMethod);
        $responseData = json_encode($arrQueryUri);

        // send output
        if (!$strErrorDesc) {
            $this->sendOkayOutput($responseData);
        } else {
            $this->sendErrorOutput($strErrorDesc, $strErrorHeader);
        }
    }
}
