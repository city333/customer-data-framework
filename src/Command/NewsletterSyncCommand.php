<?php

/**
 * Pimcore Customer Management Framework Bundle
 * Full copyright and license information is available in
 * License.md which is distributed with this source code.
 *
 * @copyright  Copyright (C) Elements.at New Media Solutions GmbH
 * @license    GPLv3
 */

namespace CustomerManagementFrameworkBundle\Command;

use CustomerManagementFrameworkBundle\Newsletter\Manager\NewsletterManagerInterface;
use CustomerManagementFrameworkBundle\Newsletter\ProviderHandler\Mailchimp;
use CustomerManagementFrameworkBundle\Newsletter\Queue\NewsletterQueueInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NewsletterSyncCommand extends AbstractCommand
{
    /**
     * @var NewsletterManagerInterface
     */
    private $newsletterManager;



    protected function configure()
    {
        $this->setName('cmf:newsletter-sync')
            ->setDescription('Handles the synchronization of customers and segments with the newsletter provider')
            ->addOption('customer-data-sync', 'c', null, 'process customer data sync')
            ->addOption('enqueue-all-customers', null, null, 'add all customers to newsletter queue')
            ->addOption('all-customers', 'a', null, 'full sync of all customers (otherwise only the newsletter queue will be processed)')
            ->addOption('force-segments', 's', null, 'force update of segments (otherwise only changed segments will be exported)')
            ->addOption('force-customers', 'f', null, 'force update of customers (otherwise only changed customers will be exported)')
            ->addOption('mailchimp-status-sync', 'm', null, 'mailchimp status sync (direction mailchimp => pimcore) for all mailchimp newsletter provider handlers');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->newsletterManager = \Pimcore::getContainer()->get(NewsletterManagerInterface::class);

        if($input->getOption('enqueue-all-customers')) {
            /**
             * @var NewsletterQueueInterface $newsletterQueue
             */
            $newsletterQueue = \Pimcore::getContainer()->get(NewsletterQueueInterface::class);
            $newsletterQueue->enqueueAllCustomers();
        }

        if($input->getOption('customer-data-sync')) {
            $this->newsletterManager->syncSegments((bool)$input->getOption('force-segments'));
            $this->newsletterManager->syncCustomers(
                (bool)$input->getOption('all-customers'),
                (bool)$input->getOption('force-customers')
            );
        }

        if($input->getOption('mailchimp-status-sync')) {
            $this->mailchimpStatusSync();
        }
    }

    protected function mailchimpStatusSync()
    {
        /**
         * @var Mailchimp\CliSyncProcessor $cliSyncProcessor
         */
        $cliSyncProcessor = \Pimcore::getContainer()->get(Mailchimp\CliSyncProcessor::class );

        $cliSyncProcessor->process();
    }
}
