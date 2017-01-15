<?php

namespace Toy\Components\Http;

use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface
{

    protected $stream;
    protected $file;
    protected $size;
    protected $error;
    protected $client_filename;
    protected $client_media_type;
    protected $moved = false;

    public function __construct(array $file)
    {
        if(is_uploaded_file($file['tmp_name'])){
            $this->stream = $this->createStream($file['tmp_name']);
            $this->file = $file['tmp_name'];
            $this->size = $file['size'];
            $this->error = $file['error'];
            $this->client_filename = $file['name'];
            $this->client_media_type = $file['type'];
        }else{
            $this->error = UPLOAD_ERR_NO_FILE;
        }
    }

    protected function createStream($file)
    {
        if($this->isOk()){
            return new Stream(fopen($file, 'r'));
        }
        return null;
    }

    protected function isOk()
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    /**
     * @inheritdoc
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * @inheritdoc
     */
    public function moveTo($targetPath)
    {
        if($this->isOk() and !$this->moved){
            move_uploaded_file($this->file, $targetPath);
            $this->moved = true;
        }
    }

    /**
     * @inheritdoc
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @inheritdoc
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @inheritdoc
     */
    public function getClientFilename()
    {
        return $this->client_filename;
    }

    /**
     * @inheritdoc
     */
    public function getClientMediaType()
    {
        return $this->client_media_type;
    }
}