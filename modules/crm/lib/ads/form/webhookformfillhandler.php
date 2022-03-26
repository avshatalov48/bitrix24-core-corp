<?php

namespace Bitrix\Crm\Ads\Form;

use Bitrix\Crm\Ads\Internals\AdsFormLinkTable;
use Bitrix\Crm\Tracking\Channel;
use Bitrix\Crm\Tracking\Channel\FbLeadAds;
use Bitrix\Crm\Tracking\Channel\VkLeadAds;
use Bitrix\Crm\Tracking\Trace;
use Bitrix\Crm\WebForm\Form;
use Bitrix\Main\Application;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Seo\LeadAds\Service;
use Bitrix\Seo\WebHook\Payload;
use Throwable;

Loc::loadMessages(__FILE__);

/**
 * Class WebHookFormFillHandler.
 *
 * @package Bitrix\Crm\Ads\Form
 */
class WebHookFormFillHandler
{
	/** @var ErrorCollection $errorCollection Error collection. */
	protected $errorCollection;

	/** @var Payload\Batch $payload Payload. */
	protected $payload;

	/**@var Service|null $service */
	protected $service;

	/**
	 * WebHookFormFillHandler constructor
	 *
	 * @param Payload\Batch $payload
	 */
	public function __construct(Payload\Batch $payload, Service $service)
	{
		$this->errorCollection = new ErrorCollection();
		$this->payload = $payload;
		$this->service = $service;
	}

	/**
	 * Handle form fill.
	 *
	 * @param Event $event Web hook event.
	 * @return EventResult
	 */
	public static function handleEvent(Event $event): EventResult
	{
		/**@var Service|null $service */
		$service = null;
		$serviceLocator = ServiceLocator::getInstance();
		if (Loader::includeModule('seo') && $serviceLocator->has("seo.leadads.service"))
		{
			$service = $serviceLocator->get("seo.leadads.service");
		}

		/** @var  Payload\Batch $payload Payload. */
		$payload = $event->getParameter('PAYLOAD');
		$instance = new WebHookFormFillHandler($payload, $service);
		$instance->process();

		return new EventResult(
			$instance->getErrorCollection()->count() > 0 ? EventResult::ERROR : EventResult::SUCCESS,
			['ERROR_COLLECTION' => $instance->getErrorCollection(),]
		);
	}

	/**
	 * Method for fill Crm-forms from external service
	 */
	public function process(): void
	{
		if (!$application = Application::getInstance())
		{
			$this->addError("Can't load application instance.");

			return;
		}
		if (!$service = $this->service)
		{
			$this->addError("Can't load Seo service.");

			return;
		}
		if (0 === count($this->payload->getItems()))
		{
			$this->addError('Empty payload items.');

			return;
		}
		if (!($code = $this->payload->getCode()) || !$externalServiceType = $service::getTypeByEngine($code))
		{
			$this->addError('Empty payload code.');

			return;
		}

		foreach ($this->payload->getItems() as $externalFormFillItem)
		{
			if (!$externalFormFillItem->getFormId())
			{
				$this->addError("Empty payload item parameters `formId`.");
				continue;
			}
			if (!$formFillId = $externalFormFillItem->getLeadId())
			{
				$this->addError("Empty payload item parameters `leadId`.");
				continue;
			}

			if ($application::getConnection()->lock($originId = "{$externalServiceType}/{$formFillId}"))
			{
				try
				{
					$this->processItem($externalServiceType, $externalFormFillItem, $originId);
				}
				catch (Throwable $throwable)
				{
					$this->addError($throwable->getMessage());
				}
				finally
				{
					$application::getConnection()->unlock($originId);
				}
			}
		}
	}

	/**
	 * Add Error to collection
	 *
	 * @param string $errorText
	 */
	private function addError(string $errorText): void
	{
		$this->errorCollection->setError(new Error($errorText));
	}

	private function processItem(string $serviceType, Payload\LeadItem $externalFormFillItem, string $originId): void
	{
		$linkDb =
			AdsFormLinkTable::query()
				->setSelect(['WEBFORM_ID'])
				->where('ADS_FORM_ID', $adsFormId = $externalFormFillItem->getFormId())
				->where('ADS_TYPE', $serviceType)
				->addOrder('DATE_INSERT', 'DESC')
				->exec();

		if ($linkDb->getSelectedRowsCount() <= 0)
		{
			$this->addError("Linked crm-forms by ads-form-id `{$adsFormId}` not found.");

			return;
		}

		/**@var \Bitrix\Seo\LeadAds\Form */
		$form = $this->service->getForm($serviceType);
		$adsResult = $form->getResult($externalFormFillItem);

		if (!$adsResult || !$adsResult->isSuccess())
		{
			$this->errorCollection->add($adsResult->getErrors());

			return;
		}

		for ($incomeFields = []; $item = $adsResult->fetch();)
		{
			$incomeFields[$item['NAME']] = $item['VALUES'];
		}

		for (; $link = $linkDb->fetch();)
		{
			// check existing form
			if (($form = new Form()) && !$form->load($link['WEBFORM_ID']))
			{
				$this->addError("Can not load crm-form by id `{$link['WEBFORM_ID']}`.");
				continue;
			}
			// check existing result
			if ($form->hasResult($originId))
			{
				continue;
			}
			// chek if map exists
			if (!$mapper = $form->getIntegration()->getIntegrationFieldsMapper($serviceType, $adsFormId))
			{
				$this->addError("Mapper not exists for this form");
				continue;
			}

			// add result
			$result = $form->addResult(
				$mapper->prepareFormFillResult($incomeFields),
				[
					'ORIGIN_ID' => $originId,
					'COMMON_DATA' => [
						'TRACE_ID' => Trace::create()
							->addChannel($serviceType === Service::TYPE_FACEBOOK ? new FbLeadAds() : new VkLeadAds())
							->addChannel(new Channel\Form($link['WEBFORM_ID']))
							->save(),
					],
				]
			);

			foreach ($result->getErrors() as $errorMessage)
			{
				$this->addError($errorMessage);
			}
		}
	}

	/**
	 * Get error collection.
	 *
	 * @return ErrorCollection
	 */
	public function getErrorCollection(): ErrorCollection
	{
		return $this->errorCollection;
	}
}
