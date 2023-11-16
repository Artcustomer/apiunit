<?php

namespace Artcustomer\ApiUnit\Event;

/**
 * @author David
 */
interface IApiEventHandler
{

    /**
     * @param IApiEvent $event
     */
    public function handleEvent(IApiEvent $event): void;
}
