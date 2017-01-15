<?php

namespace Toy\Components\Http;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

class Message implements MessageInterface
{

    protected $protocol_version;
    protected static $available_protocol_version = [
        '1.0' => true,
        '1.1' => true,
        '2.0' => true,
    ];
    protected $headers = [];
    protected $body;

    /**
     * @inheritdoc
     */
    public function getProtocolVersion()
    {
        return $this->protocol_version;
    }

    /**
     * @inheritdoc
     */
    public function withProtocolVersion($version)
    {
        if(!isset(self::$available_protocol_version[$version])){
            throw new InvalidArgumentException('Неверное значение версии протокола');
        }
        if($this->protocol_version === $version){
            return $this;
        }
        $new_instance = clone $this;
        $new_instance->protocol_version = $version;
        return $new_instance;
    }

    /**
     * @inheritdoc
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @inheritdoc
     */
    public function hasHeader($name)
    {
        return isset($this->headers[strtolower($name)]);
    }

    /**
     * @inheritdoc
     */
    public function getHeader($name)
    {
        $header = strtolower($name);
        return isset($this->headers[$header]) ? $this->headers[$header] : [];
    }

    /**
     * @inheritdoc
     */
    public function getHeaderLine($name)
    {
        return implode(', ', $this->getHeader($name));
    }

    /**
     * @inheritdoc
     */
    public function withHeader($name, $value)
    {
        $new_instance = clone $this;
        $name = strtolower($name);

        if (!is_array($value)) {
            $new_instance->headers[$name] = [trim($value)];
        } else {
            foreach ($value as $k => $v) {
                $new_instance->headers[$name][$k] = trim($v);
            }
        }
        return $new_instance;
    }

    /**
     * @inheritdoc
     */
    public function withAddedHeader($name, $value)
    {
        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }
        $new_instance = clone $this;
        $new_instance->headers[strtolower($name)][] = $value;
        return $new_instance;
    }

    /**
     * @inheritdoc
     */
    public function withoutHeader($name)
    {
        if (!$this->hasHeader($name)) {
            return $this;
        }
        $new_instance = clone $this;
        $header = strtolower($name);
        unset($new_instance->headers[$header]);
        return $new_instance;
    }

    /**
     * @inheritdoc
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @inheritdoc
     */
    public function withBody(StreamInterface $body)
    {
        if($this->body === $body){
           return $this;
        }
        $new_instance = clone $this;
        $new_instance->body = $body;
        return $new_instance;
    }
}