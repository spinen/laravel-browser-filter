<?php

namespace Spinen\BrowserFilter\Route;

trait StringFilterParser
{
    /**
     * Loop through all of the parameters in the string and process them.
     *
     * @param string $filter The filter separated by '/'
     *
     * @return void
     */
    private function extractRule($filter)
    {
        list($device, $browser, $operator_versions) = array_pad(array_filter(explode('/', $filter, 3)), 3, '*');

        // Block all browsers of the device
        if ('*' === $browser) {
            $this->rules[$device] = '*';

            return;
        }

        // Block all versions of the browser
        if ('*' === $operator_versions) {
            $this->rules[$device][$browser] = '*';

            return;
        }

        $this->rules[$device][$browser] = $this->extractVersions($device, $browser, $operator_versions);

        return;
    }

    /**
     * Loop through all of the versions in the string and process them.
     *
     * @param string $device            The device
     * @param string $browser           The browser
     * @param string $operator_versions The versions separated by '|'
     *
     * @return array
     */
    private function extractVersions($device, $browser, $operator_versions)
    {
        // Were there existing rules for the browser?
        $versions = empty($this->getRules()[$device][$browser]) ? [] : $this->getRules()[$device][$browser];

        foreach (array_filter(explode('|', $operator_versions)) as $operator_version) {
            // Remove everything to the leading numbers
            $version = preg_replace("/^[^\\d]*/u", "", $operator_version);
            // Default no operator to equals
            $operator = str_replace($version, '', $operator_version) ?: '=';

            $versions[$operator] = $version;
        }

        return $versions;
    }

    /**
     * Loop through all of the filters in the string and process them.
     *
     * @param string $filter_string The filters separated by ';'
     */
    public function parseFilterString($filter_string)
    {
        array_map([$this, 'extractRule'], array_filter(explode(';', $filter_string)));
    }
}
