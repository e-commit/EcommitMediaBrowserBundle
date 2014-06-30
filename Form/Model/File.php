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
use Symfony\Component\Validator\Context\ExecutionContextInterface;
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

    protected $requestPath;

    public function __construct($requestPath)
    {
        $this->requestPath = $requestPath;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function setFile(SfFile $file = null)
    {
        $this->file = $file;
    }

    public function isFileValid(ExecutionContextInterface $context)
    {
        if ($this->file instanceof UploadedFile) {
            if (!preg_match('/^[A-Za-z0-9\._-]+$/', $this->file->getClientOriginalName())) {
                $context->buildViolation('Incorrect filename')
                    ->addViolation();
            }
            $path = \realpath($this->requestPath . '/' . $this->file->getClientOriginalName());
            if ($path && \file_exists($path)) {
                $context->buildViolation('The file already exists')
                    ->addViolation();
            }
        }
    }
}