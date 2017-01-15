<?php

namespace Toy\Components\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class Request extends Message implements RequestInterface
{

    protected $request_target;

    protected $method;

    /**
     * @var UriInterface
     */
    protected $uri;

    /**
     * @inheritdoc
     */
    public function getRequestTarget()
    {
        if ($this->request_target !== null) {
            return $this->request_target;
        }

        if ($this->uri === null) {
            return '/';
        }

        $target = $this->uri->getPath();

        $query = $this->uri->getQuery();

        if ($query !== '') {
            $target .= '?'.$query;
        }

        $this->request_target = $target;

        return $this->request_target;
    }

    /**
     * @inheritdoc
     */
    public function withRequestTarget($requestTarget)
    {
        if ($this->request_target = $requestTarget) {
            return $this;
        }
        $new_instance = clone $this;
        $new_instance->request_target = $requestTarget;
        return $new_instance;
    }

    /**
     * @inheritdoc
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @inheritdoc
     */
    public function withMethod($method)
    {
        if ($this->method = $method) {
            return $this;
        }
        $new_instance = clone $this;
        $new_instance->method = $method;
        return $new_instance;
    }

    /**
     * @inheritdoc
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @inheritdoc
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        if ($uri === $this->uri) {
            return $this;
        }
        $new_instance = clone $this;
        $new_instance->uri = $uri;
        if ($preserveHost === true) {
            if($host = $uri->getHost() and !$this->hasHeader('host')){
                $new_instance->headers = ['host' => [$host]] + $new_instance->headers;
            }
        }
        return $new_instance;
    }
}