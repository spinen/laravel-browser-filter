<?php

namespace Spinen\BrowserFilter\Contracts;

/**
 * Interface Filterable
 *
 * @package Spinen\BrowserFilter\Contracts
 */
interface Filterable
{
    /**
     * Get the browsers being filtered.
     *
     * @return string|array
     */
    public function getBlockedBrowsers();

    /**
     * Get the versions of the browsers being filtered.
     *
     * @return string|array
     */
    public function getBlockedBrowserVersions();

    /**
     * Get the route to the redirect path.
     *
     * @return string|null
     */
    public function getRedirectRoute();
}
