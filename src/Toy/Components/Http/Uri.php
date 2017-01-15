<?php

namespace Toy\Components\Http;

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{

    protected $scheme;
    protected $authority;
    protected $user_info;
    protected $host;
    protected $port;
    protected $path;
    protected $query;
    protected $fragment;
    protected $allowedSchemes = [
        'http'  => 80,
        'https' => 443,
    ];
    const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';
    const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';

    public function __construct($uri = null)
    {
        if(!empty($uri)){
            $this->parseUri($uri);
        }
    }

    private function parseUri($uri)
    {
        $parts = parse_url($uri);
        if (false === $parts) {
            throw new InvalidArgumentException('Неверный формат URL');
        }
        $this->scheme    = isset($parts['scheme'])   ? $this->filterScheme($parts['scheme']) : '';
        $this->user_info = isset($parts['user'])     ? $parts['user']     : '';
        $this->host      = isset($parts['host'])     ? $parts['host']     : '';
        $this->port      = isset($parts['port'])     ? $parts['port']     : null;
        $this->path      = isset($parts['path'])     ? $this->filterPath($parts['path']) : '';
        $this->query     = isset($parts['query'])    ? $this->filterQuery($parts['query']) : '';
        $this->fragment  = isset($parts['fragment']) ? $this->filterFragment($parts['fragment']) : '';
    }

    private function urlEncodeChar(array $matches)
    {
        return rawurlencode($matches[0]);
    }

    private function filterPath($path)
    {
        $path = preg_replace_callback(
            '/(?:[^' . self::CHAR_UNRESERVED . ':@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'urlEncodeChar'],
            $path
        );

        if (empty($path)) {
            // No path
            return $path;
        }

        if ($path[0] !== '/') {
            // Relative path
            return $path;
        }

        // Ensure only one leading slash, to prevent XSS attempts.
        return '/' . ltrim($path, '/');
    }

    private function filterQuery($query)
    {
        if (! empty($query) && strpos($query, '?') === 0) {
            $query = substr($query, 1);
        }

        $parts = explode('&', $query);
        foreach ($parts as $index => $part) {
            list($key, $value) = $this->splitQueryValue($part);
            if ($value === null) {
                $parts[$index] = $this->filterQueryOrFragment($key);
                continue;
            }
            $parts[$index] = sprintf(
                '%s=%s',
                $this->filterQueryOrFragment($key),
                $this->filterQueryOrFragment($value)
            );
        }

        return implode('&', $parts);
    }

    private function splitQueryValue($value)
    {
        $data = explode('=', $value, 2);
        if (1 === count($data)) {
            $data[] = null;
        }
        return $data;
    }

    private function filterFragment($fragment)
    {
        if (! empty($fragment) && strpos($fragment, '#') === 0) {
            $fragment = '%23' . substr($fragment, 1);
        }

        return $this->filterQueryOrFragment($fragment);
    }

    private function filterQueryOrFragment($value)
    {
        return preg_replace_callback(
            '/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'urlEncodeChar'],
            $value
        );
    }

    private function filterScheme($scheme)
    {
        $scheme = strtolower($scheme);
        $scheme = preg_replace('#:(//)?$#', '', $scheme);

        if (empty($scheme)) {
            return '';
        }

        if (! array_key_exists($scheme, $this->allowedSchemes)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported scheme "%s"; must be any empty string or in the set (%s)',
                $scheme,
                implode(', ', array_keys($this->allowedSchemes))
            ));
        }

        return $scheme;
    }

    /**
     * @inheritdoc
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @inheritdoc
     */
    public function getAuthority()
    {
        if (empty($this->host)) {
            return '';
        }
        $authority = $this->host;
        if (!empty($this->user_info)) {
            $authority = $this->user_info . '@' . $this->host;
        }
        if (!empty($this->port)) {
            $authority .= ':' . $this->port;
        }
        return $authority;
    }

    /**
     * @inheritdoc
     */
    public function getUserInfo()
    {
        return $this->user_info;
    }

    /**
     * @inheritdoc
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @inheritdoc
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @inheritdoc
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @inheritdoc
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @inheritdoc
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * @inheritdoc
     */
    public function withScheme($scheme)
    {
        if ($this->scheme === $scheme) {
            return $this;
        }
        $new_instance = clone $this;
        $new_instance->scheme = $this->filterScheme($scheme);
        return $new_instance;
    }

    /**
     * @inheritdoc
     */
    public function withUserInfo($user, $password = null)
    {
        $user_info = $user . $password ? $user . ':' . $password : null;
        if($this->user_info === $user_info){
            return $this;
        }
        $new_instance = clone $this;
        $new_instance->user_info = $user_info;
        return $new_instance;
    }

    /**
     * @inheritdoc
     */
    public function withHost($host)
    {
        if($this->host === $host){
            return $this;
        }
        $new_instance = clone $this;
        $new_instance->host = $host;
        return $new_instance;
    }

    /**
     * @inheritdoc
     */
    public function withPort($port)
    {
        if($this->port === $port){
            return $this;
        }
        if (null !== $port) {
            $port = (int) $port;
            if (1 > $port || 0xffff < $port) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid port: %d. Must be between 1 and 65535', $port)
                );
            }
        }
        $new_instance = clone $this;
        $new_instance->port = $port;
        return $new_instance;
    }

    /**
     * @inheritdoc
     */
    public function withPath($path)
    {
        if($this->path === $path){
            return $this;
        }
        $new_instance = clone $this;
        $new_instance->path = $this->filterPath($path);
        return $new_instance;
    }

    /**
     * @inheritdoc
     */
    public function withQuery($query)
    {
        if($this->query === $query){
            return $this;
        }
        $new_instance = clone $this;
        $new_instance->query = $this->filterQuery($query);
        return $new_instance;
    }

    /**
     * @inheritdoc
     */
    public function withFragment($fragment)
    {
        if (substr($fragment, 0, 1) === '#') {
            $fragment = substr($fragment, 1);
        }
        if ($this->fragment === $fragment) {
            return $this;
        }
        $new_instance = clone $this;
        $new_instance->fragment = $this->filterFragment($fragment);
        return $new_instance;
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        $uri = '';
        if (!empty($this->scheme)) {
            $uri .= $this->scheme . ':';
        }
        $part = '';
        $authority = $this->getAuthority();
        if (!empty($authority)) {
            if (!empty($this->scheme)) {
                $part .= '//';
            }
            $part .= $this->getAuthority();
        }
        if ($this->path != null) {
            // Add a leading slash if necessary.
            if ($part && substr($this->path, 0, 1) !== '/') {
                $part .= '/';
            }
            $part .= $this->path;
        }
        $uri .= $part;
        if ($this->query != null) {
            $uri .= '?' . $this->query;
        }
        if ($this->fragment != null) {
            $uri .= '#' . $this->fragment;
        }
        return $uri;
    }
}