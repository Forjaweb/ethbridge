<?php

namespace Forjaweb\EthbridgeBundle\Request;

interface RequestInterface
{
    public function request($function, $args);
}
