<?php

namespace Bitrix\Crm\Ads\Form;

use Bitrix\Crm\WebForm\Form;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Ads\Internals\AdsFormLinkTable;
use Bitrix\Crm\Tracking;
use Bitrix\Seo\LeadAds\Service as LeadAdsService;
use Bitrix\Seo\WebHook\Payload;

Loc::loadMessages(__FILE__);

/**
 * Class WebHookFormFillHandler.
 * @package Bitrix\Crm\Ads\Form
 */
class WebHookFormFillHandler
{
	/** @var ErrorCollection $errorCollection Error collection.  */
	protected $errorCollection = [];

	/** @var  Payload\Batch $payload Payload. */
	protected $payload;

	/**
	 * Handle form fill.
	 *
	 * @param Event $event Web hook event.
	 * @return EventResult
	 */
	public static function handleEvent(Event $event)
	{
		/** @var  Payload\Batch $payload Payload. */
		$payload = $event->getParameter('PAYLOAD');
		$instance = new self($payload);
		$instance->process();

		$eventResult = new EventResult(
			$instance->hasErrors() ? EventResult::ERROR : EventResult::SUCCESS,
			['ERROR_COLLECTION' => $instance->getErrorCollection(),]
		);

		return $eventResult;
	}

	public function __construct(Payload\Batch $payload)
	{
		$this->errorCollection = new ErrorCollection();
		$this->payload = $payload;
	}

	protected function check()
	{
		if (count($this->payload->getItems()) === 0)
		{
			$this->addError('Empty payload items.');
		}

		if (!$this->payload->getCode())
		{
			$this->addError('Empty payload code.');
		}

		return !$this->hasErrors();
	}

	public function process()
	{
		if (!$this->check())
		{
			return;
		}

		$type = $this->payload->getCode();
		$type = explode('.', $type);
		$type = $type[1];

		foreach ($this->payload->getItems() as $item)
		{
			$this->processItem($type, $item);
		}
	}

	protected function checkItem(Payload\LeadItem $item)
	{
		$adsResultId = null;
		$adsFormId = null;
		$adsLeadId = null;

		if (!$item->getFormId())
		{
			$this->addError("Empty payload item parameters `formId`.");
		}

		if (!$item->getLeadId())
		{
			$this->addError("Empty payload item parameters `leadId`.");
		}

		return !$this->hasErrors();
	}

	protected function processItem($type, Payload\LeadItem $item)
	{
		if (!$this->checkItem($item))
		{
			return;
		}

		$adsResultId = $item->getLeadId();
		$adsFormId = $item->getFormId();

		// retrieve linked crm-forms
		$crmForms = self::getLinkedCrmForms($type, $adsFormId);
		if (count($crmForms) <= 0)
		{
			$this->addError("Linked crm-forms by ads-form-id `$adsFormId` not found.");
		}

		$adsForm = LeadAdsService::getForm($type);
		$adsResult = $adsForm->getResult($item);
		if (!$adsResult->isSuccess())
		{
			$this->errorCollection->add($adsResult->getErrors());
		}

		$incomeFields = array();
		while ($item = $adsResult->fetch())
		{
			$incomeFields[$item['NAME']] = $item['VALUES'];
		}

		$addResultParameters = array(
			'ORIGIN_ID' => $type . '/' . $adsResultId,
			'COMMON_DATA' => []
		);
		foreach ($crmForms as $crmFormId)
		{
			// add result
			$this->addResult($crmFormId, $incomeFields, $addResultParameters, $type);
		}
	}

	protected static function getLinkedCrmForms($type, $adsFormId)
	{
		$linkDb = AdsFormLinkTable::getList(array(
			'select' => array('WEBFORM_ID'),
			'filter' => array(
				'=ADS_FORM_ID' => $adsFormId,
				'=ADS_TYPE' => $type
			),
			'limit' => 5,
			'order' => array('DATE_INSERT' => 'DESC'),
		));

		$crmForms = array();
		while ($link = $linkDb->fetch())
		{
			$crmForms[] = $link['WEBFORM_ID'];
		}

		return $crmForms;
	}

	protected function addResult($formId, array $incomeFields, array $addResultParameters, $type)
	{
		// check existing form
		$form = new Form();
		if (!$form->load($formId))
		{
			$this->addError("Can not load crm-form by id `$formId`.");
			return false;
		}

		// check existing result
		if ($form->hasResult($addResultParameters['ORIGIN_ID']))
		{
			return true;
		}

		$addResultParameters['COMMON_DATA']['TRACE_ID'] = Tracking\Trace::create()
			->addChannel(
			$type === LeadAdsService::TYPE_FACEBOOK
				? new Tracking\Channel\FbLeadAds()
				: new Tracking\Channel\VkLeadAds()
			)
			->addChannel(new Tracking\Channel\Form($formId))
			->save();

		// prepare fields
		$fields = $form->getFieldsMap();
		foreach ($fields as $fieldKey => $field)
		{
			$values = array();
			if (isset($incomeFields[$field['name']]))
			{
				$values = $incomeFields[$field['name']];
				if(!is_array($values))
				{
					$values = array($values);
				}
			}

			$field['values'] = $values;
			$fields[$fieldKey] = $field;
		}

		// add result
		$result = $form->addResult($fields, $addResultParameters);
		foreach ($result->getErrors() as $errorMessage)
		{
			$this->errorCollection->setError(new Error($errorMessage));
		}

		return ($result->getId() && $result->getId() > 0);
	}

	protected function addError($errorText)
	{
		$this->errorCollection->setError(new Error($errorText));
	}

	/**
	 * Return true if it has errors.
	 *
	 * @return bool
	 */
	public function hasErrors()
	{
		return $this->errorCollection->count() > 0;
	}

	/**
	 * Get error collection.
	 *
	 * @return ErrorCollection
	 */
	public function getErrorCollection()
	{
		return $this->errorCollection;
	}
}