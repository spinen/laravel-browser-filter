<?php

namespace Spinen\BrowserFilter;

use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Mobile_Detect;
use Spinen\BrowserFilter\Exceptions\FilterTypeNotSetException;
use Spinen\BrowserFilter\Exceptions\InvalidRuleDefinitionsException;
use Spinen\BrowserFilter\Support\ParserCreator;
use UAParser\Result\Client;

/**
 * Class Filter
 */
abstract class Filter
{
    /**
     * Is this a block or allow filter?
     */
    protected ?bool $block_filter = null;

    /**
     * The client instance.
     */
    protected Client $client;

    /**
     * Location of the config file.
     */
    protected string $config_path = 'browserfilter.';

    /**
     * The path to redirect the user if client is blocked.
     */
    protected ?string $redirect_route = null;

    /**
     * The array of rules
     */
    protected array $rules = [];

    /**
     * Create a new browser filter middleware instance.
     */
    public function __construct(
        protected Cache $cache,
        protected Config $config,
        protected Mobile_Detect $detector,
        protected ParserCreator $parser,
        protected Redirector $redirector
    ) {
        $this->client = $parser->parseAgent($detector->getUserAgent());
    }

    /**
     * Determines if the client needs to be redirected.
     */
    public function determineRedirect(): string|bool
    {
        return $this->needsRedirecting() ? $this->getRedirectRoute() : false;
    }

    /**
     * Generate the key to use to cache the determination.
     */
    public function generateCacheKey(Request $request): string
    {
        // NOTE: $request is an unused variable here, but needed in a class that extends this one
        return $this->client->device->family.':'.$this->client->ua->family.':'.$this->client->ua->toVersion();
    }

    /**
     * Get the browsers being filtered.
     */
    public function getBrowsers(): string|array|null
    {
        return $this->haveRulesForDevice() ? $this->getRules()[$this->client->device->family] : null;
    }

    /**
     * Get the versions of the browsers being filtered.
     */
    public function getBrowserVersions(): string|array|null
    {
        return $this->haveVersionsForBrowser() ? $this->getRules()[$this->client->device->family][$this->client->ua->family] : null;
    }

    /**
     * Get the timeout of the cached value.
     */
    public function getCacheTimeout(): int
    {
        return $this->config->get($this->config_path.'timeout');
    }

    /**
     * Return the filter type.
     *
     * @throws FilterTypeNotSetException
     */
    public function getFilterType(): string
    {
        if (is_bool($this->block_filter)) {
            return $this->block_filter ? 'block' : 'allow';
        }

        throw new FilterTypeNotSetException();
    }

    /**
     * Get the route to the redirect path.
     */
    public function getRedirectRoute(): ?string
    {
        return $this->redirect_route ?: $this->config->get($this->config_path.'route');
    }

    /**
     * Return the array of rules.
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string|array|null $filter_string = null, ?string $redirect_route = null)
    {
        $this->redirect_route = $redirect_route;

        if ($this->onRedirectPath($request)) {
            return $next($request);
        }

        $cache_key = $this->generateCacheKey($request);

        $redirect = $this->cache->get($cache_key);

        if (is_null($redirect)) {
            $this->parseFilterString($filter_string);

            $this->validateRules();

            $redirect = $this->determineRedirect();

            $this->cache->put($cache_key, $redirect, $this->getCacheTimeout());
        }

        if ($redirect) {
            $request->session()
                    ->flash('redirected', true);

            return $this->redirector->route($redirect);
        }

        return $next($request);
    }

    /**
     * Check to see if there are defined rules for the device.
     */
    public function haveRulesForDevice(): bool
    {
        return array_key_exists($this->client->device->family, $this->getRules());
    }

    /**
     * Check to see if there are defined versions for the browser for the device.
     */
    public function haveVersionsForBrowser(): bool
    {
        return array_key_exists($this->client->device->family, $this->getRules()) &&
            array_key_exists($this->client->ua->family, $this->getRules()[$this->client->device->family]);
    }

    /**
     * Checks to see if the browser/client is blocked.
     */
    public function isMatched(): bool
    {
        return $this->isMatchedDevice() || $this->isMatchedBrowser() || $this->isMatchedBrowserVersion();
    }

    /**
     * Checks to see if all versions of the browser is blocked.
     */
    public function isMatchedBrowser(): bool
    {
        return '*' === $this->getBrowserVersions();
    }

    /**
     * Checks to see if the version of the browser is blocked.
     *
     * Uses the php version_compare function to decide if there is a match.
     *
     * @link http://php.net/manual/en/function.version-compare.php
     */
    public function isMatchedBrowserVersion(): bool
    {
        $denied = false;

        // cache it, so that we don't have to keep asking for it
        $client_version = $this->client->ua->toVersion();

        foreach ((array) $this->getBrowserVersions() as $operator => $version) {
            $denied |= (bool) version_compare($client_version, $version, $operator);
        }

        return (bool) $denied;
    }

    /**
     * Checks to see if all browsers of the device family is blocked.
     */
    public function isMatchedDevice(): bool
    {
        return '*' === $this->getBrowsers();
    }

    /**
     * Decide if the client needs to be redirected.
     *
     * Here is the logic:
     *
     *   blockedFilter  true       true    false   false
     *   isMatched()    true       false   true    false
     *                  redirect   no      no      redirect
     *
     * so you can see this is a negative xor
     */
    public function needsRedirecting(): bool
    {
        return ! $this->block_filter xor $this->isMatched();
    }

    /**
     * Check to see if we are on the redirect page.
     *
     * If we did not test for this, then we would get into a redirect loop.
     */
    public function onRedirectPath(Request $request): bool
    {
        return $request->session()
                       ->get('redirected', false);
    }

    /**
     * Delegate setting the rules from the passed in filter string.
     *
     * The the filter string will always be null on the stack filter.
     */
    abstract public function parseFilterString(string|array|null $filter_string): void;

    /**
     * Validate a device browser stanza in the rules.
     *
     * @throws InvalidRuleDefinitionsException
     */
    protected function validateBrowserRules(string $device, string $browser, string|array $versions): void
    {
        if ('*' === $versions) {
            return;
        }

        if (! is_array($versions)) {
            throw new InvalidRuleDefinitionsException(
                sprintf(
                    'The value for [%s] must be either an array of browsers or an asterisk (*) for all browsers.',
                    $browser
                )
            );
        }

        foreach ($versions as $operator => $version) {
            $this->validateBrowserVersionRules($device, $browser, $operator, $version);
        }
    }

    /**
     * Validate a browser version stanza in the rules.
     *
     * @throws InvalidRuleDefinitionsException
     */
    protected function validateBrowserVersionRules(string $device, string $browser, string $operator, string $version): void
    {
        if (! in_array(
            $operator,
            ['<', 'lt', '<=', 'le', '>', 'gt', '>=', 'ge', '==', '=', 'eq', '!=', '<>', 'ne'],
            true
        )) {
            throw new InvalidRuleDefinitionsException(
                sprintf(
                    'The comparison operator [%s] for [%s > %s] is invalid.',
                    $operator,
                    $device,
                    $browser
                )
            );
        }
    }

    /**
     * Validate a device stanza in the rules.
     *
     * @throws InvalidRuleDefinitionsException
     */
    protected function validDeviceRule(string $device, string|array $browsers): void
    {
        if ('*' === $browsers) {
            return;
        }

        if (! is_array($browsers)) {
            throw new InvalidRuleDefinitionsException(
                sprintf(
                    'The value for [%s] must be either an array of browsers or an asterisk (*) for all browsers.',
                    $device
                )
            );
        }

        foreach ($browsers as $browser => $versions) {
            $this->validateBrowserRules($device, $browser, $versions);
        }
    }

    /**
     * Validate the rules.
     *
     * @throws InvalidRuleDefinitionsException
     */
    public function validateRules(): void
    {
        if (empty($this->getRules())) {
            return;
        }

        foreach ($this->getRules() as $device => $browsers) {
            $this->validDeviceRule($device, $browsers);
        }
    }
}
