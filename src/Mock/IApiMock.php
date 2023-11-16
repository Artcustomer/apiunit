<?php

namespace Artcustomer\ApiUnit\Mock;

/**
 * @author David
 */
interface IApiMock
{

    /**
     * @param string $endpoint
     * @return bool
     */
    public function match(string $endpoint): bool;
}
