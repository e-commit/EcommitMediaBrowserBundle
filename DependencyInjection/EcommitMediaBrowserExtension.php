<?php

namespace Ecommit\MediaBrowserBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class EcommitMediaBrowserExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $container->setParameter('ecommit_media_browser.root_dir', $config['root_dir']);
        $container->setParameter('ecommit_media_browser.tiny_mce_popup', $config['tiny_mce_popup']);
        $container->setParameter('ecommit_media_browser.jquery', $config['jquery']);

        $container->setParameter(
            'assetic.bundles',
            array_merge(
                $container->getParameter('assetic.bundles'),
                array('EcommitMediaBrowserBundle')
            )
        );
    }
}
