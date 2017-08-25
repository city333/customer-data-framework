<?php

declare(strict_types=1);

/**
 * Pimcore Customer Management Framework Bundle
 * Full copyright and license information is available in
 * License.md which is distributed with this source code.
 *
 * @copyright  Copyright (C) Elements.at New Media Solutions GmbH
 * @license    GPLv3
 */

namespace CustomerManagementFrameworkBundle\DependencyInjection;

use CustomerManagementFrameworkBundle\CustomerProvider\CustomerProviderInterface;
use CustomerManagementFrameworkBundle\CustomerSaveManager\CustomerSaveManagerInterface;
use CustomerManagementFrameworkBundle\CustomerSaveValidator\CustomerSaveValidatorInterface;
use CustomerManagementFrameworkBundle\SegmentManager\SegmentManagerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class PimcoreCustomerManagementFrameworkExtension extends ConfigurableExtension
{
    protected function loadInternal(array $config, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('services.yml');
        $loader->load('services_templating.yml');
        $loader->load('services_events.yml');

        $loader->load('services_security.yml');

        if ($config['oauth_client']['enabled']) {
            $loader->load('services_security_oauth_client.yml');
        }

        $this->registerGeneralConfiguration($container, $config['general']);
        $this->registerEncryptionConfiguration($container, $config['encryption']);
        $this->registerCustomerSaveManagerConfiguration($container, $config['customer_save_manager']);
        $this->registerCustomerSaveValidatorConfiguration($container, $config['customer_save_validator']);
        $this->registerSegmentManagerConfiguration($container, $config['segment_manager']);
        $this->registerCustomerProviderConfiguration($container, $config['customer_provider']);
        $this->registerCustomerListConfiguration($container, $config['customer_list']);
    }

    private function registerGeneralConfiguration(ContainerBuilder $container, array $config)
    {
        $container->setParameter('pimcore_customer_management_framework.general.customerPimcoreClass', $config['customerPimcoreClass']);
        $container->setParameter('pimcore_customer_management_framework.general.mailBlackListFile', $config['mailBlackListFile']);
    }

    private function registerEncryptionConfiguration(ContainerBuilder $container, array $config)
    {
        $container->setParameter('pimcore_customer_management_framework.encryption.secret', $config['secret']);
    }

    private function registerCustomerSaveManagerConfiguration(ContainerBuilder $container, array $config)
    {
        $container->setAlias('cmf.customer_save_manager', CustomerSaveManagerInterface::class);

        $container->setParameter('pimcore_customer_management_framework.customer_save_manager.enableAutomaticObjectNamingScheme', $config['enableAutomaticObjectNamingScheme']);
    }

    private function registerCustomerSaveValidatorConfiguration(ContainerBuilder $container, array $config)
    {
        $container->setAlias('cmf.customer_save_validator', CustomerSaveValidatorInterface::class);

        $container->setParameter('pimcore_customer_management_framework.customer_save_validator.requiredFields', is_array($config['requiredFields']) ? $config['requiredFields'] : []);
        $container->setParameter('pimcore_customer_management_framework.customer_save_validator.checkForDuplicates', $config['checkForDuplicates']);
    }

    private function registerSegmentManagerConfiguration(ContainerBuilder $container, array $config)
    {
        $container->setAlias('cmf.segment_manager', SegmentManagerInterface::class);

        $container->setParameter('pimcore_customer_management_framework.segment_manager.segmentFolder.calculated', $config['segmentFolder']['calculated']);
        $container->setParameter('pimcore_customer_management_framework.segment_manager.segmentFolder.manual', $config['segmentFolder']['manual']);
    }

    private function registerCustomerProviderConfiguration(ContainerBuilder $container, array $config)
    {
        $container->setAlias('cmf.customer_provider', CustomerProviderInterface::class);

        $container->setParameter('pimcore_customer_management_framework.customer_provider.namingScheme', $config['namingScheme']);
        $container->setParameter('pimcore_customer_management_framework.customer_provider.parentPath', $config['parentPath']);
        $container->setParameter('pimcore_customer_management_framework.customer_provider.archiveDir', $config['archiveDir']);
    }


    private function registerCustomerListConfiguration(ContainerBuilder $container, array $config)
    {
        $container->setParameter('pimcore_customer_management_framework.customer_list.exporters', $config['exporters'] ?: []);
    }
}
