<?php

namespace Toy\Components\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class ServerRequest extends Request implements ServerRequestInterface
{

    protected $server_params = [];
    protected $cookie_params = [];
    protected $query_params = [];
    protected $uploaded_files = [];
    protected $parsed_body = [];
    protected $attributes = [];

    public function __construct($body = 'php://input',
                                array $parsed_body = [],
                                array $query_params = [],
                                array $cookie_params = [],
                                array $uploaded_files = [],
                                array $server_params = [],
                                array $headers = [],
                                $protocol_version = null,
                                $method = null,
                                $uri = null)
    {
        $this->query_params = $query_params;
        $this->body = $this->createBody($body);
        $this->parsed_body = $parsed_body;
        $this->cookie_params = $cookie_params;
        $this->uploaded_files = $uploaded_files;
        $this->server_params = $server_params;
        $this->headers = $headers;
        $this->protocol_version = $protocol_version;
        $this->method = $method;
        if($uri instanceof UriInterface){
            $this->uri = $uri;
        }else{
            $this->uri = $this->createUri($uri);
        }
    }

    protected function getRequestHeaders()
    {
        $headers = '';
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

    protected function createBody($body)
    {
        return new Stream(fopen($body, 'r'));
    }

    protected function createUri($uri)
    {
        return new Uri($uri);
    }

    /**
     * @inheritdoc
     */
    public function getServerParams()
    {
        return $this->server_params;
    }

    /**
     * @inheritdoc
     */
    public function getCookieParams()
    {
        return $this->cookie_params;
    }

    /**
     * @inheritdoc
     */
    public function withCookieParams(array $cookies)
    {
        if ($this->cookie_params === $cookies) {
            return $this;
        }
        $new_instance = clone $this;
        $new_instance->cookie_params = $cookies;
        return $new_instance;
    }

    /**
     * @inheritdoc
     */
    public function getQueryParams()
    {
        return $this->query_params;
    }

    /**
     * @inheritdoc
     */
    public function withQueryParams(array $query)
    {
        if ($this->query_params === $query) {
            return $this;
        }
        $new_instance = clone $this;
        $new_instance->query_params = $query;
        return $new_instance;
    }

    /**
     * @inheritdoc
     */
    public function getUploadedFiles()
    {
        return $this->uploaded_files;
    }

    /**
     * @inheritdoc
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        if ($this->uploaded_files === $uploadedFiles) {
            return $this;
        }
        $new_instance = clone $this;
        $new_instance->uploaded_files = $uploadedFiles;
        return $new_instance;
    }

    /**
     * @inheritdoc
     */
    public function getParsedBody()
    {
        return $this->parsed_body;
    }

    /**
     * {@inheritdoc}
     */
    public function withParsedBody($data)
    {
        if ($this->parsed_body === $data) {
            return $this;
        }
        $new_instance = clone $this;
        $new_instance->parsed_body = $data;
        return $new_instance;
    }

    /**
     * @inheritdoc
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @inheritdoc
     */
    public function getAttribute($name, $default = null)
    {
        if (false === array_key_exists($name, $this->attributes)) {
            return $default;
        }
        return $this->attributes[$name];
    }

    /**
     * @inheritdoc
     */
    public function withAttribute($name, $value)
    {
        $new_instance = clone $this;
        $new_instance->attributes[$name] = $value;
        return $new_instance;
    }

    /**
     * @inheritdoc
     */
    public function withoutAttribute($name)
    {
        if (false === isset($this->attributes[$name])) {
            return clone $this;
        }
        $new = clone $this;
        unset($new->attributes[$name]);
        return $new;
    }
}
