<?php

/*
 * This file is part of the OpwocoBootstrapBundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace opwoco\Bundle\BootstrapBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class opwocoBootstrapExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('bootstrap.xml');
        $loader->load('twig.xml');

        if (isset($config['bootstrap'])) {
            if (!isset($config['bootstrap']['install_path'])) {
                throw new \RuntimeException('Please specify the "bootstrap.install_path" or disable "opwoco_bootstrap" in your application config.');
            }
            $container->setParameter('opwoco_bootstrap.bootstrap.install_path', $config['bootstrap']['install_path']);
        }

        /**
         * Form
         */
        if (isset($config['form'])) {
            $loader->load('form.xml');
            foreach ($config['form'] as $key => $value) {
                if (is_array($value)) {
                    $this->remapParameters($container, 'opwoco_bootstrap.form.'.$key, $config['form'][$key]);
                } else {
                    $container->setParameter(
                        'opwoco_bootstrap.form.'.$key,
                        $value
                    );
                }
            }
        }

        /**
         * Menu
         */
        if ($this->isConfigEnabled($container, $config['menu']) || $this->isConfigEnabled($container, $config['navbar'])) {
            // TODO: remove this BC layer
            if ($this->isConfigEnabled($container, $config['navbar'])) {
                trigger_error(sprintf('opwoco_bootstrap.navbar is deprecated. Use opwoco_bootstrap.menu.'), E_USER_DEPRECATED);
            }
            $loader->load('menu.xml');
            $this->remapParameters($container, 'opwoco_bootstrap.menu', $config['menu']);
        }

        /**
         * Icons
         */
        if (isset($config['icons'])) {
            $this->remapParameters($container, 'opwoco_bootstrap.icons', $config['icons']);
        }

        /**
         * Initializr
         */
        if (isset($config['initializr'])) {
            $loader->load('initializr.xml');
            $this->remapParameters($container, 'opwoco_bootstrap.initializr', $config['initializr']);
        }

        /**
         * Flash
         */
        if (isset($config['flash'])) {
            $mapping = array();

            foreach ($config['flash']['mapping'] as $alertType => $flashTypes) {
                foreach ($flashTypes as $type) {
                    $mapping[$type] = $alertType;
                }
            }

            $container->getDefinition('opwoco_bootstrap.twig.extension.bootstrap_flash')
                ->replaceArgument(0, $mapping);
        }
    }

    /**
     * Remap parameters.
     *
     * @param ContainerBuilder $container
     * @param string           $prefix
     * @param array            $config
     */
    private function remapParameters(ContainerBuilder $container, $prefix, array $config)
    {
        foreach ($config as $key => $value) {
            $container->setParameter(sprintf('%s.%s', $prefix, $key), $value);
        }
    }
}