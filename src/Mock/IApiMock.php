<?php

namespace Artcustomer\ApiUnit\Mock;

interface IApiMock {

    /**
     * @param string $endpoint
     * @return bool
     */
    public function match(string $endpoint): bool;
}
