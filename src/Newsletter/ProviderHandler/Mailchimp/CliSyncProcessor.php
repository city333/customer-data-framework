<?php

/**
 * Pimcore Customer Management Framework Bundle
 * Full copyright and license information is available in
 * License.md which is distributed with this source code.
 *
 * @copyright  Copyright (C) Elements.at New Media Solutions GmbH
 * @license    GPLv3
 */

namespace CustomerManagementFrameworkBundle\Newsletter\ProviderHandler\Mailchimp;

use Carbon\Carbon;
use CustomerManagementFrameworkBundle\CustomerProvider\CustomerProviderInterface;
use CustomerManagementFrameworkBundle\Model\MailchimpAwareCustomerInterface;
use CustomerManagementFrameworkBundle\Newsletter\Manager\NewsletterManagerInterface;
use CustomerManagementFrameworkBundle\Newsletter\ProviderHandler\Mailchimp;
use CustomerManagementFrameworkBundle\Traits\ApplicationLoggerAware;
use Pimcore\Model\User;

class CliSyncProcessor
{
    use ApplicationLoggerAware;

    /**
     * @var string|null
     */
    protected $pimcoreUserName;

    /**
     * @var MailChimpExportService
     */
    protected $exportService;

    /**
     * @var CustomerProviderInterface
     */
    protected $customerProvider;

    /**
     * @var UpdateFromMailchimpProcessor
     */
    protected $updateFromMailchimpProcessor;

    /**
     * @var NewsletterManagerInterface
     */
    protected $newsletterManager;

    public function __construct($pimcoreUserName = null, MailChimpExportService $exportService, CustomerProviderInterface $customerProvider, UpdateFromMailchimpProcessor $updateFromMailchimpProcessor, NewsletterManagerInterface $newsletterManager)
    {
        if(!is_null($pimcoreUserName)) {
            if($user = User::getByName($pimcoreUserName)) {
                $updateFromMailchimpProcessor->setUser($user);
            } else {
                $this->getLogger()->error(sprintf("pimcore user %s not found (mailchimp config parameter cliUpdatesPimcoreUserName)", $pimcoreUserName));
            }
        }

        $this->exportService = $exportService;
        $this->customerProvider = $customerProvider;
        $this->updateFromMailchimpProcessor = $updateFromMailchimpProcessor;
        $this->newsletterManager = $newsletterManager;
    }

    public function process()
    {
        $client = $this->exportService->getApiClient();

        foreach($this->newsletterManager->getNewsletterProviderHandlers() as $newsletterProviderHandler) {
            if($newsletterProviderHandler instanceof Mailchimp) {

                // get updates from the last 3 days
                $date = Carbon::createFromTimestamp(time() - (60*60*24*3));
                $date = $date->toIso8601String();

                $result = $client->get(
                    $this->exportService->getListResourceUrl($newsletterProviderHandler->getListId(), 'members/?since_last_changed=' . urlencode($date))
                );

                if($client->success() && sizeof($result['members'])) {
                    foreach ($result['members'] as $row) {

                        // var_dump($row);
                        /**
                         * @var MailchimpAwareCustomerInterface $customer
                         */
                        try {
                            if(!$customer = $this->customerProvider->getActiveCustomerByEmail($row['email_address'])) {
                                $this->getLogger()->error(sprintf("no active customer with email %s found", $row['email_address']));
                            }
                        } catch(\RuntimeException $e) {
                            if(!$customer = $this->customerProvider->getActiveCustomerByEmail($row['email_address'])) {
                                $this->getLogger()->error(sprintf("multiple active customers with email %s found", $row['email_address']));
                            }
                        }

                        $status = $row['status'];

                        $statusChanged = $this->updateFromMailchimpProcessor->updateNewsletterStatus($newsletterProviderHandler, $customer, $status);
                        $mergeFieldsChanged = $this->updateFromMailchimpProcessor->processMergeFields($newsletterProviderHandler, $customer, $row['merge_fields']);

                        $changed = $statusChanged || $mergeFieldsChanged;

                        if($changed) {
                            $this->getLogger()->info(sprintf('customer id %s changed - updating...', $customer->getId()));
                        } else {
                            $this->getLogger()->info(sprintf('customer id %s did not change - no update needed.', $customer->getId()));
                        }

                        $this->updateFromMailchimpProcessor->saveCustomerIfChanged($customer, $changed);
                    }
                }

            }
        }
    }



}