<?php

namespace Artcustomer\ApiUnit\Logger;

/**
 * @author David
 */
interface IApiLogger
{

    /**
     * @param \Exception $exception
     */
    public function logException(\Exception $exception): void;
}
