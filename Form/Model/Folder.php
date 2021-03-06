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

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @Assert\Callback(methods={"isFolderValid"})
 */
class Folder
{
    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=20)
     * @Assert\Regex("/^[A-Za-z0-9\._-]+$/")
     */
    protected $name;

    protected $requestPath;

    public function __construct($requestPath)
    {
        $this->requestPath = $requestPath;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function isFolderValid(ExecutionContextInterface $context)
    {
        if (!empty($this->name)) {
            $path = \realpath($this->requestPath . '/' . $this->name);
            if ($path && \file_exists($path)) {
                $context->buildViolation('The folder already exists')
                    ->addViolation();
            }
        }
    }
}
