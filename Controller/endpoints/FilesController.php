<?php

namespace NamespaceFunction;

class FilesController extends \NamespaceBase\BaseController {
    public function handle() {
        $requestMethod = $this->getRequestMethod();
        $arrQueryUri = $this->getUriSegments();

        // You can now use $requestMethod and $arrQueryUri just like before
        // Continue with the implementation...
    }
}