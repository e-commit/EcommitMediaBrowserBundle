<?php

/*
 * This file is part of the EcommitMediaBrowserBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\MediaBrowserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class FolderType extends AbstractType
{
    /**
     * {@inheritDoc} 
     */
    function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('name');
    }
    
    /**
     * {@inheritDoc} 
     */
    public function getDefaultOptions(array $options)
    {
        return array(
            'csrf_protection' => false,
        );
    }
    
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'ecommit_media_browser_folder';
    }
}
