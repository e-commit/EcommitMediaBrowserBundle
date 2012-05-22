<?php

/*
 * This file is part of the EcommitMediaBrowserBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\MediaBrowserBundle\Form\Model;

use Symfony\Component\HttpFoundation\File\File as SfFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContext;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @Assert\Callback(methods={"isFileValid"}) 
 */
class File
{
    /**
     * @Assert\NotNull()
     * @Assert\File()
     */
    protected $file;
    
    protected $request_path;
    
    public function __construct($request_path)
    {
        $this->request_path = $request_path;
    }
    
    public function getFile()
    {
        return $this->file;
    }
    
    public function setFile(SfFile $file = null)
    {
        $this->file = $file;
    }
    
    public function isFileValid(ExecutionContext $context)
    {
        if($this->file instanceof UploadedFile)
        {
            if(!preg_match('/^[A-Za-z0-9\._-]+$/', $this->file->getClientOriginalName()))
            {
                $context->addViolation('Incorrect filename', array(), null);
            }
            $path = \realpath($this->request_path.'/'.$this->file->getClientOriginalName());
            if($path && \file_exists($path))
            {
                $context->addViolation('The file already exists', array(), null);
            }
        }
    }
}