<?php

namespace Spinen\BrowserFilter\Support;

trait DecipherRules
{
    /**
     * Determines if the client needs to be redirected.
     *
     * @return \Illuminate\Http\RedirectResponse|bool
     */
    private function determineRedirect()
    {
        if ($this->isBlocked()) {
            return $this->redirector->route($this->getRedirectRoute());
        }

        return false;
    }

    /**
     * Checks to see if the browser/client is blocked.
     *
     * @return bool
     */
    private function isBlocked()
    {
        return $this->isBlockedDevice() || $this->isBlockedBrowser() || $this->isBlockedBrowserVersion();
    }

    /**
     * Checks to see if all versions of the browser is blocked.
     *
     * @return bool
     */
    private function isBlockedBrowser()
    {
        return $this->getBlockedBrowserVersions() === '*';
    }

    /**
     * Checks to see if the version of the browser is blocked.
     *
     * Uses the php version_compare function to decide if there is a match.
     *
     * @link http://php.net/manual/en/function.version-compare.php
     *
     * @return bool
     */
    private function isBlockedBrowserVersion()
    {
        $denied = false;

        // cache it, so that we don't have to keep asking for it
        $client_version = $this->client->ua->toVersion();

        foreach ((array)$this->getBlockedBrowserVersions() as $operator => $version) {
            $denied |= (bool)version_compare($client_version, $version, $operator);
        }

        return $denied;
    }

    /**
     * Checks to see if all browsers of the device family is blocked.
     *
     * @return bool
     */
    private function isBlockedDevice()
    {
        return $this->getBlockedBrowsers() === '*';
    }
}
