<?php

namespace Bitrix\Crm\WebForm\Options;

use Bitrix\Crm\Ads\AdsForm;
use Bitrix\Crm\Ads\Form\WebHookFormFillHandler;
use Bitrix\Crm\Ads\Internals\AdsFormLinkTable;
use Bitrix\Crm\Ads\Internals\EO_AdsFormLink;
use Bitrix\Crm\Ads\Internals\EO_AdsFormLink_Collection;
use Bitrix\Crm\WebForm;
use Bitrix\Crm\WebForm\Internals\FormFieldMappingTable;
use Bitrix\Main;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Error;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Seo\LeadAds\Service;

Loc::loadMessages(__FILE__);

final class Integration
{
	/** @var WebForm\Form  */
	private $form;

	private const FAKE_ID_VALUE = 0;

	private static function convertToFormOptions(?array $data): array
	{
		$result = array_map(
			static function(array $integrationOption) : array {
				return [
					'ADS_TYPE' => $integrationOption['providerCode'],
					'LINK_DIRECTION' => $integrationOption['linkDirection'],
					'ADS_ACCOUNT_ID' => $integrationOption['account']['id'],
					'ADS_ACCOUNT_NAME' => $integrationOption['account']['name'],
					'ADS_FORM_ID' => $integrationOption['form']['id'],
					'ADS_FORM_NAME' => $integrationOption['form']['name'] ?: 'Default name',
					'FIELDS_MAPPING' => array_map(
						static function(array $raw) : array {
							return [
								'ADS_FIELD_KEY' => $raw['adsFieldKey'],
								'CRM_FIELD_KEY' => $raw['crmFieldKey'],
								'ITEMS' => $raw['items'],
							];
						},
						$integrationOption['fieldsMapping'] ?? []
					),
				];
			},
			$data['cases'] ?? []
		);

		$result = array_filter(
			$result,
			function (array $item)
			{
				return !empty($item['ADS_TYPE'])
					&& !empty($item['ADS_ACCOUNT_ID'])
					&& !empty($item['ADS_FORM_ID'])
				;
			}
		);

		return $result;
	}

	private function prepareResult(Main\Result $result, Main\Result... $values) : Result
	{
		foreach ($values as $value)
		{
			if ($value->isSuccess())
			{
				continue;
			}
			$result->addErrors(
				$value->getErrors()
			);
		}

		return $result;
	}

	/**
	 * Integration constructor.
	 *
	 * @param WebForm\Form $form
	 */
	public function __construct(WebForm\Form $form)
	{
		$this->form = $form;
	}

	/**
	 * Load integration fields
	 *
	 * @return array
	 */
	public function load() : array
	{
		$linkDb = AdsFormLinkTable::query()
			->setSelect(
				array(
					'ID',
					'LINK_DIRECTION',
					'ADS_FORM_NAME',
					'ADS_FORM_ID',
					'ADS_ACCOUNT_NAME',
					'ADS_ACCOUNT_ID',
					'DATE_INSERT',
					'ADS_TYPE'
				)
			)
			->where('WEBFORM_ID', $this->getForm()->getId())
			->setCacheTtl(300)
			->addOrder('DATE_INSERT','DESC')
			->exec();

		$linkDb->addFetchDataModifier(
			static function (array $raw): array {
				$raw['ADS_FORM_NAME'] = $raw['ADS_FORM_NAME'] ?? $raw['ADS_FORM_ID'];
				$raw['ADS_ACCOUNT_NAME'] = $raw['ADS_ACCOUNT_NAME'] ?? $raw['ADS_ACCOUNT_ID'];
				$raw['LINK_DIRECTION'] = (int) $raw['LINK_DIRECTION'];

				/*INTEGRATION MAPPING*/
				if (AdsFormLinkTable::LINK_DIRECTION_IMPORT === $raw["LINK_DIRECTION"])
				{
					$raw['FIELDS_MAPPING'] = FormFieldMappingTable::query()
						->setSelect(['CRM_FIELD_KEY', 'ADS_FIELD_KEY', 'ITEMS'])
						->where("FORM_LINK_ID", $raw["ID"])
						->exec()
						->fetchAll();
				}

				return $raw;
			}
		);

		return $linkDb->fetchAll();
	}

	/**
	 * Delete integration fields
	 * @return void
	 * @throws \Exception
	 */
	public function delete() : void
	{
		$links = AdsFormLinkTable::query()
			->setSelect([
				"ID",
				"ADS_TYPE",
				"ADS_ACCOUNT_ID",
				"ADS_FORM_ID",
			])
			->where("WEBFORM_ID", $this->form->getId())
			->exec()
			->fetchCollection()
		;
		$this->unlinkForms($links);
	}
	/**
	 * Set data to form
	 * @param array|null $data
	 *
	 * @return $this
	 */
	public function setData(?array $data) : self
	{
		$this->form->merge(
			array('INTEGRATION' => is_array($data) ? self::convertToFormOptions($data) : null)
		);

		return $this;
	}

	/**
	 * Save integration fields
	 *
	 * @return Result
	 * @throws \Exception
	 */
	public function save(): Result
	{
		$integrationSaveOperationResult = new Result;

		/** if integration is empty skip save operation*/
		if (!is_array($integrationOptions = $this->form->get()['INTEGRATION'] ?? null))
		{
			return $integrationSaveOperationResult;
		}

		$checkBeforeSave = $this->checkData();
		if (!$checkBeforeSave->isSuccess())
		{
			return $checkBeforeSave;
		}

		$linksDb = AdsFormLinkTable::query()
			->setSelect(["ID","ADS_FORM_ID","ADS_ACCOUNT_ID","ADS_TYPE","LINK_DIRECTION"])
			->where("WEBFORM_ID", $this->form->getId())
			->where("LINK_DIRECTION",AdsFormLinkTable::LINK_DIRECTION_IMPORT)
			->exec();

		/** form hash-array with key 'formId/accountId/formId' and dblink and delete flag*/
		for ($links = [];$link = $linksDb->fetchObject();)
		{
			$key = "{$link->get("ADS_TYPE")}/{$link->get("ADS_ACCOUNT_ID")}/{$link->get("ADS_FORM_ID")}";
			$links[$key] = $link;
		}

		$formsLinkResults = [];

		foreach ($integrationOptions as $key => $integration)
		{
			/**chek if form link has export type*/
			if (AdsFormLinkTable::LINK_DIRECTION_IMPORT !== (int)$integration["LINK_DIRECTION"])
			{
				continue;
			}

			$formsLinkResults[$key] = new Result();

			/**form key to find links */
			$origin = "{$integration['ADS_TYPE']}/{$integration['ADS_ACCOUNT_ID']}/{$integration["ADS_FORM_ID"]}";

			/**if form unknown link it*/
			if (!array_key_exists($origin,$links))
			{
				$linkFormResult = $this->linkForm($integration);

				if (!$linkFormResult->isSuccess())
				{
					$formsLinkResults[$key]->addErrors(
						$linkFormResult->getErrors()
					);
					break;
				}

				["LINK_ID" => $linkId] = $linkFormResult->getData();
			}
			/**if form was linked DELETE current mappings*/
			else
			{
				/**@var EO_AdsFormLink $link*/
				$link = $links[$origin];
				$formMappingDeleteResult = FormFieldMappingTable::delete($linkId = $link->getId());
				unset($links[$origin]);

				if (!$formMappingDeleteResult->isSuccess())
				{
					$formsLinkResults[$key]->addErrors(
						$formMappingDeleteResult->getErrors()
					);
					break;
				}
			}

			foreach ($integration['FIELDS_MAPPING'] as $mapping)
			{
				$mappingSaveResult = WebForm\Internals\FormFieldMappingTable::add(
					array(
						'FORM_LINK_ID' => $linkId,
						'CRM_FIELD_KEY' => $mapping['CRM_FIELD_KEY'],
						'ADS_FIELD_KEY' => $mapping['ADS_FIELD_KEY'],
						'ITEMS' => $mapping['ITEMS'] ?? [],
					)
				);

				$this->prepareResult($formsLinkResults[$key],$mappingSaveResult);
			}
		}

		$this->prepareResult($integrationSaveOperationResult, ...$formsLinkResults);

		if (!$integrationSaveOperationResult->isSuccess())
		{
			return $integrationSaveOperationResult;
		}

		/**delete unused form links */
		$linksDeleteResult = $this->unlinkForms($links);

		return $this->prepareResult(
			$integrationSaveOperationResult,
			$linksDeleteResult
		);
	}

	/**
	 * Data check before save
	 * @return Main\Entity\Result
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	public function checkData(): Main\Entity\Result
	{
		$integrationResult = new Main\Entity\Result;

		if (!$integrationOptions = $this->form->get()['INTEGRATION'])
		{
			return $integrationResult;
		}

		/** type control array */
		$typesCheck = array_combine(
			$types = AdsForm::getServiceTypes(),
			array_fill(0, count($types), true)
		);

		self::clearNotExistsFormIntegrationLinks();

		foreach ($integrationOptions as $integration)
		{
			if (AdsFormLinkTable::LINK_DIRECTION_IMPORT !== (int)$integration["LINK_DIRECTION"])
			{
				continue;
			}

			if (!in_array($type = $integration["ADS_TYPE"], $types, true))
			{
				$integrationResult->addError(
					new Error(Loc::getMessage("CRM_WEBFORM_OPTIONS_LINK_WRONG_TYPE"))
				);

				continue;
			}

			if (!$typesCheck[$type])
			{
				$integrationResult->addError(
					new Error(Loc::getMessage("CRM_WEBFORM_OPTIONS_LINK_TYPE_DUPLICATE"))
				);

				continue;
			}

			if (!isset($integration['FIELDS_MAPPING']))
			{
				$integrationResult->addError(
					new Error(Loc::getMessage("CRM_WEBFORM_OPTIONS_LINK_EMPTY_FIELD_MAPPING"))
				);

				continue;
			}

			$duplicates =
				AdsFormLinkTable::query()
					->setSelect(['ID'])
					->where('ADS_TYPE',$integration['ADS_TYPE'])
					->where('ADS_FORM_ID', $integration['ADS_FORM_ID'])
					->where('ADS_ACCOUNT_ID', $integration['ADS_ACCOUNT_ID'])
					->whereNot('WEBFORM_ID', $this->form->getId())
					->exec()
			;
			if ($duplicates->getSelectedRowsCount() > 0)
			{
				$integrationResult->addError(
					new Error(Loc::getMessage('CRM_WEBFORM_OPTIONS_LINK_FORM_DUPLICATE_ERROR'))
				);

				continue;
			}

			AdsFormLinkTable::checkFields(
				$integrationResult,
				null,
				array(
					'LINK_DIRECTION' => $integration['LINK_DIRECTION'],
					'WEBFORM_ID' => $this->form->getId(),
					'ADS_TYPE' => $integration['ADS_TYPE'],
					'ADS_ACCOUNT_ID' => $integration['ADS_ACCOUNT_ID'],
					'ADS_FORM_ID' => $integration['ADS_FORM_ID'],
					'ADS_ACCOUNT_NAME' => $integration['ADS_ACCOUNT_NAME'] ?? '',
					'ADS_FORM_NAME' => $integration['ADS_FORM_NAME'],
				)
			);

			foreach ($integration['FIELDS_MAPPING'] as $mapping)
			{
				Webform\Internals\FormFieldMappingTable::checkFields(
					$integrationResult,
					null,
					array(
						'FORM_LINK_ID' => self::FAKE_ID_VALUE,
						'CRM_FIELD_KEY' => $mapping['CRM_FIELD_KEY'],
						'ADS_FIELD_KEY' => $mapping['ADS_FIELD_KEY'],
						'ITEMS' => $mapping['ITEMS'] ?? [],
					)
				);
			}

			$typesCheck[$integration['ADS_TYPE']] = false;

		}

		return $integrationResult;
	}

	public static function clearNotExistsFormIntegrationLinks()
	{
		$notExistedLinks =
			AdsFormLinkTable::query()
				->setSelect(['ID'])
				->whereNull('FORM.ID')
				->exec();

		foreach ($notExistedLinks as $notExistedLink)
		{
			AdsFormLinkTable::delete($notExistedLink['ID']);
		}
	}
	/**
	 * Convert to array
	 * @return array
	 */
	public function toArray() : array
	{
		$cases = [];
		foreach ($this->form->get()['INTEGRATION'] ?? [] as $link)
		{
			$case = [
				'providerCode' => $link['ADS_TYPE'],
				'date' => $link['DATE_INSERT'],
				'form' => [
					'id' => $link['ADS_FORM_ID'],
					'name' => $link['ADS_FORM_NAME'],
				],
				'account' => [
					'id' => $link['ADS_ACCOUNT_ID'],
					'name' => $link['ADS_ACCOUNT_NAME'],
				],
				'linkDirection' => $link['LINK_DIRECTION'],
				'fieldsMapping' => [],
			];

			if (AdsFormLinkTable::LINK_DIRECTION_IMPORT === (int)$link['LINK_DIRECTION'])
			{
				$case['fieldsMapping'] = array_map(
					static function(array $raw) : array {
						return [
							'crmFieldKey' => $raw['CRM_FIELD_KEY'],
							'adsFieldKey' => $raw['ADS_FIELD_KEY'],
							'items' => $raw['ITEMS'],
						];
					},
					$link['FIELDS_MAPPING']
				);
			}

			$cases[] = $case;
		}

		return [
			'cases' => $cases
		];
	}

	/**
	 * get field mapper
	 * @param string $type
	 * @param string|int|mixed $adsFormId
	 *
	 * @return Integration\IFieldMapper|null
	 * @throws Main\NotImplementedException
	 */
	public function getIntegrationFieldsMapper(string $type,$adsFormId): ?Integration\IFieldMapper
	{
		if (!$adsFormId || !$integration = $this->form->get()['INTEGRATION'] ?? null)
		{
			return null;
		}

		foreach ($integration as $option)
		{
			if ($option['ADS_TYPE'] !== $type || $option['ADS_FORM_ID'] !== (string)$adsFormId)
			{
				continue;
			}

			switch ($option['LINK_DIRECTION'])
			{
				case AdsFormLinkTable::LINK_DIRECTION_IMPORT:
					return Integration\Factory::getFieldsMapper(
						$type,
						$option['FIELDS_MAPPING'],
						$this
					);
				case AdsFormLinkTable::LINK_DIRECTION_EXPORT:
				default:
					return Integration\Factory::getCompatibleFieldsMapper(
						$type,
						$this
					);
			}
		}

		return null;
	}

	/**
	 * get Integration form
	 * @return WebForm\Form
	 */
	public function getForm() : WebForm\Form
	{
		return $this->form;
	}

	/**
	 * @return Service|null
	 * @throws Main\LoaderException
	 * @throws Main\ObjectNotFoundException
	 */
	private function getService(): ?Service
	{
		$serviceLocator = ServiceLocator::getInstance();
		if (!Loader::includeModule("seo") || !$serviceLocator->has("seo.leadads.service"))
		{
			return null;
		}

		/**@var Service $service*/
		return $serviceLocator->get("seo.leadads.service");
	}


	/**
	 * @param string $type
	 * @param string $accountId
	 * @param string $formId
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	private function subscribeOnLeadAdsEvents(string $type, string $accountId, string $formId): Result
	{
		$result = new Result();

		if (!$service = $this->getService())
		{
			$result->addError(
				new Error(Loc::getMessage('CRM_WEBFORM_OPTIONS_LINK_MODULE_SEO_NOT_INSTALLED'))
			);

			return $result;
		}

		$form = $service->getForm($type);
		$form->setAccountId($accountId);

		$registerResult = $form->register($formId);
		if (!$registerResult->isSuccess())
		{
			$result->addErrors($registerResult->getErrors());
			return $result;
		}

		EventManager::getInstance()->registerEventHandler(
			'seo',
			'OnWebHook',
			'crm',
			WebHookFormFillHandler::class,
			'handleEvent'
		);

		return $result;
	}

	/**
	 * @param string $type
	 * @param string $accountId
	 * @param string $formId
	 * @return Result
	 */
	private function unsubscribeOnLeadAdsEvents(string $type, string $accountId, string $formId): Result
	{
		$unsubscribeResult = new Result();

		if (!$service = $this->getService())
		{
			$unsubscribeResult->addError(
				new Error(Loc::getMessage('CRM_WEBFORM_OPTIONS_LINK_MODULE_SEO_NOT_INSTALLED'))
			);

			return $unsubscribeResult;
		}

		$form = $service->getForm($type);
		$form->setAccountId($accountId);

		switch ($form::TYPE_CODE)
		{
			case Service::TYPE_VKONTAKTE:
				$links = AdsFormLinkTable::query()
					->where("ADS_TYPE",$form::TYPE_CODE)
					->where("ADS_ACCOUNT_ID",$form->getAccountId())
					->exec();
				$unlinkResult = $links->getSelectedRowsCount() > 1 || $form->unlink($formId);
				break;
			case Service::TYPE_FACEBOOK:
			default:
				$unlinkResult = $form->unlink($formId);
				break;

		}

		if (!$unlinkResult)
		{
			$unsubscribeResult->addError(
				new Error(Loc::getMessage('CRM_WEBFORM_OPTIONS_LINK_UNREGISTER_FAILED'))
			);
		}

		return $unsubscribeResult;
	}

	/**
	 * @param EO_AdsFormLink[]|EO_AdsFormLink_Collection $adsFormLinkObject
	 * @return Result
	 */
	private function unlinkForms($adsFormLinkObjects) : Result
	{
		$unlinkResult = new Result();

		/**@var EO_AdsFormLink $adsFormLinkObject*/
		foreach ($adsFormLinkObjects as $adsFormLinkObject)
		{
			$unsubscribeFormLinkResult = $this->unsubscribeOnLeadAdsEvents(
				$adsFormLinkObject->getAdsType(),
				$adsFormLinkObject->getAdsAccountId(),
				$adsFormLinkObject->getAdsFormId()
			);

			if (!$unsubscribeFormLinkResult->isSuccess())
			{
				$this->prepareResult($unlinkResult,$unsubscribeFormLinkResult);

				continue;
			}

			$deleteFormLinkResult = $adsFormLinkObject->delete();

			$this->prepareResult($unlinkResult, $unsubscribeFormLinkResult, $deleteFormLinkResult);
		}


		return $unlinkResult;
	}

	private function linkForm(array $integration) : Result
	{
		$subscribeResult = $this->subscribeOnLeadAdsEvents(
			$integration['ADS_TYPE'],
			$integration['ADS_ACCOUNT_ID'],
			$integration['ADS_FORM_ID']
		);

		/**if subscribe on webhook failed skip link add to db*/
		if (!$subscribeResult->isSuccess())
		{
			return $subscribeResult;
		}

		$linkSaveResult = AdsFormLinkTable::add(
			array(
				'LINK_DIRECTION' => AdsFormLinkTable::LINK_DIRECTION_IMPORT,
				'WEBFORM_ID' => $this->form->getId(),
				'ADS_TYPE' => $integration['ADS_TYPE'],
				'ADS_ACCOUNT_ID' => $integration['ADS_ACCOUNT_ID'],
				'ADS_FORM_ID' => $integration['ADS_FORM_ID'],
				'ADS_FORM_NAME' => $integration['ADS_FORM_NAME'],
				'ADS_ACCOUNT_NAME' => $integration['ADS_ACCOUNT_NAME'] ?? '',
			)
		);

		/**if add link to db failed skip add mapping to db*/
		if (!$linkSaveResult->isSuccess())
		{
			return $linkSaveResult;
		}

		$linkFormIntegrationResult = new Result();
		$linkFormIntegrationResult->setData([
			"LINK_ID" => $linkSaveResult->getId()
		]);

		return $linkFormIntegrationResult;
	}

	protected function handleLeadsByForm(array $integration)
	{
		$leadsResponse = $this->getLeadsByForm(
			$integration['ADS_TYPE'],
			$integration['ADS_ACCOUNT_ID'],
			$integration['ADS_FORM_ID']
		);

		/**if subscribe on webhook failed skip link add to db*/
		if (!$leadsResponse->isSuccess())
		{
			return $leadsResponse;
		}

//save leads

		/**if add link to db failed skip add mapping to db*/
		if (!$linkSaveResult->isSuccess())
		{
			return $linkSaveResult;
		}

		$linkFormIntegrationResult = new Result();
		$linkFormIntegrationResult->setData([
			"LINK_ID" => $linkSaveResult->getId()
		]);

		return $linkFormIntegrationResult;
	}

	protected function getLeadsByForm(string $type, string $accountId, string $formId): Result
	{
		$result = new Result();

		if (!$service = $this->getService())
		{
			$result->addError(
				new Error(Loc::getMessage('CRM_WEBFORM_OPTIONS_LINK_MODULE_SEO_NOT_INSTALLED'))
			);

			return $result;
		}

		$form = $service->getForm($type);
		$form->setAccountId($accountId);

		$loadLeadsResult = $form->loadLeads($formId);
		if (!$loadLeadsResult->isSuccess())
		{
			$result->addErrors($loadLeadsResult->getErrors());
			return $result;
		}

		return $loadLeadsResult;
	}
}
