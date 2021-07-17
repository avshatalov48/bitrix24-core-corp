<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Crm;
use \Bitrix\Main;
use Bitrix\Main\Localization\Loc;

class CMobileCrmCallTrackerDetailsComponent extends CBitrixComponent implements Main\Engine\Contract\Controllerable, Main\Errorable
{
	protected $entityId;
	protected $entityTypeId;
	protected $entityTypeName;
	protected $errors;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errors = new Main\ErrorCollection();
	}

	public function onPrepareComponentParams($arParams)
	{
		if (Main\Loader::includeModule('crm'))
		{
			$arParams['ENTITY_TYPE_ID'] = (int)$arParams['ENTITY_TYPE_ID'];
			$arParams['ENTITY_ID'] = (int)$arParams['ENTITY_ID'];

			$this->entityTypeId = $arParams['ENTITY_TYPE_ID'];
			$this->entityTypeName = CCrmOwnerType::ResolveName($this->entityTypeId);
			$this->entityId = $arParams['ENTITY_ID'];

			if ($this->entityTypeName !== CCrmOwnerType::DealName)
			{
				$this->errors->setError(new Main\Error('Wrong entity type.'));
			}
		}
		else
		{
			$this->errors->setError(new Main\Error('Module CRM is not installed.'));
		}
		return $arParams;
	}

	protected function getEntity(int $entityId)
	{
		if (!Crm\Authorization\Authorization::checkReadPermission($this->entityTypeId, $entityId))
		{
			$this->errors->setError(new Main\Error(Loc::getMessage('CRM_PERMISSION_DENIED')));
			return null;
		}

		$entity = $this->loadEntity($entityId);
		if (!$entity)
		{
			$this->errors->setError(new Main\Error(Loc::getMessage('CRM_CALL_TRACKER_ENTITY_NOT_FOUND_' . $this->entityTypeName)));
			return null;
		}

		return $entity;
	}

	protected function getLastTrackerActivity(int $entityId)
	{
		$activity = CCrmActivity::GetList(
			['CREATED' => 'desc'],
			[
				'=COMPLETED' => 'N',
				'CHECK_PERMISSIONS' => 'N',
				'TYPE_ID' => \CCrmActivityType::Provider,
				'PROVIDER_ID' => \Bitrix\Crm\Activity\Provider\CallTracker::PROVIDER_ID,
				'BINDINGS' => [
					[
						'OWNER_ID' => $entityId,
						'OWNER_TYPE_ID' => $this->entityTypeId
					]
				]
			],
			false,
			['nTopCount' => 1],
			[
				'SETTINGS',
				'DIRECTION'
			]
		)->Fetch();
		if (!$activity)
		{
			return null;
		}
		return [
			'PHONE_NUMBER' => $activity['SETTINGS']['PHONE_NUMBER'] ?? '',
			'DIRECTION' => $activity['DIRECTION']
		];
	}

	protected function loadEntity(int $entityId)
	{
		if ($entity = Crm\DealTable::query()
			->where('ID', $entityId)
			->setSelect(['ID', 'TITLE', 'CONTACT_ID', 'COMPANY_ID', 'OPPORTUNITY', 'CURRENCY_ID', 'IS_MANUAL_OPPORTUNITY'])
			->fetch())
		{
			$entity['CURRENCY_ID'] = $entity['CURRENCY_ID'] ?: CCrmCurrency::GetBaseCurrencyID();

			$entity['CONTACTS'] = [];
			if ($entity['CONTACT_ID'] > 0
				&& ($ids = Crm\Binding\DealContactTable::getDealContactIDs($entityId))
				&& !empty($ids))
			{
				$dbRes = Crm\ContactTable::query()
					->whereIn('ID', $ids)
					->setSelect(['ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'POST', 'PHOTO'])
					->exec();
				while($contact = $dbRes->fetch())
				{
					if (CCrmContact::CheckReadPermission($contact['ID']))
					{
						if (!($contact['PHOTO'] > 0
							&& ($contact['PHOTO'] = CFile::ResizeImageGet($contact['PHOTO'], ['width' => 43, 'height' => 43])))
						)
						{
							unset($contact['PHOTO']);
						}
						$entity['CONTACTS'][$contact['ID']] = $contact + [
								'FULL_NAME' => CCrmContact::PrepareFormattedName($contact),
								'CONTACTS' => CCrmMobileHelper::PrepareMultiFieldsData($contact['ID'], CCrmOwnerType::ContactName)
							];
					}
				}
			}

			if ($entity['COMPANY_ID']
				&& CCrmCompany::CheckReadPermission($entity['COMPANY_ID'])
				&& ($company = Crm\CompanyTable::query()
					->where('ID', $entity['COMPANY_ID'])
					->setSelect(['ID', 'TITLE', 'COMPANY_TYPE', 'LOGO'])
					->fetch()
				)
			)
			{
				if (!($company['LOGO'] > 0
					&& ($company['LOGO'] = CFile::ResizeImageGet($company['LOGO'], ['width' => 43, 'height' => 43])))
				)
				{
					unset($company['LOGO']);
				}
				$entity['COMPANY'] = $company;
			}
		}
		return $entity;
	}

	public function executeComponent()
	{
		if (!$this->errors->count())
		{
			$this->arResult['ENTITY'] = $this->getEntity($this->entityId);
		}
		if (!$this->errors->count())
		{
			$this->arResult['CURRENCIES'] = CCrmCurrencyHelper::PrepareListItems();
			$this->arResult['CURRENCY'] = CCrmCurrency::GetCurrencyFormatParams($this->arResult['ENTITY']['CURRENCY_ID']);
			$this->arResult['ACTIVITY'] = $this->getLastTrackerActivity($this->entityId);
		}
		if ($this->errors->count())
		{
			ShowError($this->errors->offsetGet(0)->getMessage());
			return;
		}
		if ($this->request->isPost()
			&& check_bitrix_sessid()
			&& ($deal = $this->request->getPostList()->toArray())
			&& array_key_exists('ID', $deal)
			&& $deal['ID'] === $this->entityId
		)
		{
			$this->saveAction($this->entityId, $deal);
		}

		$this->includeComponentTemplate();
	}

	public function configureActions()
	{
		return [];
	}
	protected function listKeysSignedParameters()
	{
		return [
			"ENTITY_TYPE_ID",
			"ENTITY_ID",
		];
	}

	public function saveAction(int $entityId, array $data)
	{
		$entity = $this->getEntity($entityId);
		if (!CCrmDeal::CheckUpdatePermission($entityId))
		{
			$this->errors->setError(new Main\Error('CRM_PERMISSION_DENIED'));
		}
		if (!$this->errors->isEmpty())
		{
			return false;
		}

		$fields = [];

		if (array_key_exists('OPPORTUNITY', $data) && $data['OPPORTUNITY'] >= 0)
		{
			$fields['OPPORTUNITY'] = $data['OPPORTUNITY'];
			$fields['IS_MANUAL_OPPORTUNITY'] = $data['OPPORTUNITY'] > 0 ? 'Y' : 'N';
		}

		if (array_key_exists('CONTACTS', $data))
		{
			$newContacts = array_diff_key($data['CONTACTS'], $entity['CONTACTS']);
			if (!empty($newContacts))
			{
				$fields['CONTACT_IDS'] = [];
				foreach ($newContacts as $contactId)
				{
					if (CCrmContact::CheckReadPermission($contactId))
					{
						$fields['CONTACT_IDS'][] = $contactId;
					}
				}
				if (empty($fields['CONTACT_IDS']))
				{
					unset($fields['CONTACT_IDS']);
				}
				else if ($entity['CONTACT_ID'] <= 0)
				{
					$entity['CONTACT_ID'] = reset($fields['CONTACT_IDS']);
				}
			}
		}
		if (array_key_exists('COMPANY', $data) && $data['COMPANY']['ID'] !== $entity['COMPANY']['ID'])
		{
			if (CCrmCompany::CheckReadPermission($data['COMPANY']['ID']))
			{
				$fields['COMPANY_ID'] = $data['COMPANY']['ID'];
			}
		}

		if (!empty($fields))
		{
			$deal = new CCrmDeal();
			if (!$deal->Update($entityId, $fields, true, true, ['REGISTER_SONET_EVENT' => true, 'DISABLE_USER_FIELD_CHECK' => true]))
			{
				$this->errors->setError(new Main\Error($deal->LAST_ERROR));
			}
		}

		$updateEntityInfos = [];
		//region Contact names
		if ($this->errors->isEmpty())
		{
			if (!empty($data['CONTACTS']))
			{
				$sourceContacts = $entity['CONTACTS'];
				if (array_key_exists('CONTACT_IDS', $fields))
				{
					$dbRes = Crm\ContactTable::query()
						->whereIn('ID', $fields['CONTACT_IDS'])
						->setSelect(['ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'POST'])
						->exec();

					while($contact = $dbRes->fetch())
					{
						$sourceContacts[$contact['ID']] = $contact + [
								'FULL_NAME' => CCrmContact::PrepareFormattedName($contact)
							];
					}
				}

				foreach ($data['CONTACTS'] as $contactId => $contactPostData)
				{
					if (
						array_key_exists($contactId, $sourceContacts)
						&& $contactPostData['FULL_NAME'] !== $sourceContacts[$contactId]['FULL_NAME']
						&& CCrmContact::CheckUpdatePermission($contactId)
					)
					{
						$updateEntityInfos[] = [
							'type' => CCrmOwnerType::Contact,
							'id' => $contactId,
							'info' => ['title' => $contactPostData['FULL_NAME']]
						];
					}
				}
			}
			if (array_key_exists('COMPANY', $data)
				&& CCrmCompany::CheckUpdatePermission($data['COMPANY']['ID'])
			)
			{
				$data['COMPANY']['TITLE'] = trim($data['COMPANY']['TITLE']);
				if ('' !== $data['COMPANY']['TITLE'])
				{
					$updateEntityInfos[] = [
						'type' => CCrmOwnerType::Company,
						'id' => $data['COMPANY']['ID'],
						'info' => ['title' => $data['COMPANY']['TITLE']]
					];
				}
			}

			$companyId = $entity['COMPANY_ID'];
			$bindContactIDs = (array_key_exists('CONTACT_IDS', $fields) ? $fields['CONTACT_IDS'] : []);
			if (array_key_exists('COMPANY_ID', $fields))
			{
				$companyId = $fields['COMPANY_ID'];
				if (array_key_exists('CONTACTS', $entity))
				{
					$bindContactIDs += array_keys($entity['CONTACTS']);
				}
			}
			if ($companyId > 0 && !empty($bindContactIDs))
			{
				$editorSettings = new \Bitrix\Crm\Settings\EntityEditSettings('deal_details');
				if ($editorSettings->isClientCompanyEnabled()
					&& $editorSettings->isClientContactEnabled()
				)
				{
					Crm\Binding\ContactCompanyTable::bindContactIDs($companyId, $bindContactIDs);
				}
			}
		}

		while ($someEntity = array_shift($updateEntityInfos))
		{
			Crm\Component\EntityDetails\BaseComponent::updateEntity(
				$someEntity['type'],
				$someEntity['id'],
				$someEntity['info'],
				['startWorkFlows' => true]
			);
		}

		//Region automation
		if (class_exists('\Bitrix\Crm\Automation\Factory') && class_exists('\Bitrix\Crm\Automation\Starter'))
		{
			$starter = new Crm\Automation\Starter(CCrmOwnerType::Deal, $entity['ID']);
			$starter->runOnUpdate($fields, $entity);
		}
		//end automation
		return $fields;
	}
	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors(): array
	{
		return $this->errors->toArray();
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return ?Main\Error
	 */
	public function getErrorByCode($code): ?Main\Error
	{
		return $this->errors->getErrorByCode($code);
	}
}