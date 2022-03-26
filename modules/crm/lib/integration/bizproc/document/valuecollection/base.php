<?php

namespace Bitrix\Crm\Integration\BizProc\Document\ValueCollection;

use Bitrix\Bizproc\Document\ValueCollection;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Crm;
use Bitrix\Main\Localization\Loc;

if (!Loader::includeModule('bizproc'))
{
	return;
}

abstract class Base extends ValueCollection
{
	protected $typeId;
	protected $id;

	public function __construct(int $typeId, int $id)
	{
		$this->typeId = $typeId;
		$this->id = $id;
	}

	abstract protected function loadValue(string $fieldId): void;

	abstract protected function loadEntityValues(): void;

	public function offsetGet($offset)
	{
		if (!array_key_exists($offset, $this->document))
		{
			$this->loadCommonValue($offset);
		}

		return parent::offsetGet($offset);
	}

	public function offsetExists($offset)
	{
		if (!array_key_exists($offset, $this->document))
		{
			$this->loadCommonValue($offset);
		}

		return parent::offsetExists($offset);
	}

	protected function loadCommonValue($fieldId): void
	{
		if ($fieldId === 'OBSERVER_IDS')
		{
			$this->loadObserverValues();
		}
		elseif ($fieldId === 'CRM_ID')
		{
			$this->document['CRM_ID'] = \CCrmOwnerTypeAbbr::ResolveByTypeID($this->typeId) . '_' . $this->id;
		}
		elseif ($fieldId === 'CREATED_BY_PRINTABLE')
		{
			$this->loadCreatedByPrintable();
		}
		elseif (strpos($fieldId, 'ASSIGNED_BY') === 0)
		{
			$this->loadAssignedByValues();
		}
		elseif (strpos($fieldId, 'PRODUCT_IDS') === 0)
		{
			$this->loadProductValues();
		}
		elseif (strpos($fieldId, 'FORMS.') === 0)
		{
			$this->loadFormValues();
		}
		elseif (strpos($fieldId, 'COMMUNICATIONS.') === 0)
		{
			$this->loadCommunicationValues();
		}
		elseif ($fieldId === 'TRACKING_SOURCE_ID')
		{
			$this->loadTrackingValues();
		}
		else
		{
			$this->loadValue($fieldId);
		}
	}

	protected function getUserValues($id): ?array
	{
		$id = \CBPHelper::StripUserPrefix($id);

		$userList = \CUser::getList(
			'id',
			'asc',
			['ID' => $id],
			[
				'SELECT' => [
					'UF_SKYPE',
					'UF_TWITTER',
					'UF_FACEBOOK',
					'UF_LINKEDIN',
					'UF_XING',
					'UF_WEB_SITES',
					'UF_PHONE_INNER',
				],
				'FIELDS' => [
					'EMAIL',
					'WORK_PHONE',
					'PERSONAL_MOBILE',
					'LOGIN',
					'ACTIVE',
					'NAME',
					'LAST_NAME',
					'SECOND_NAME',
					'WORK_POSITION',
					'PERSONAL_WWW',
					'PERSONAL_CITY',
				],
			]
		);

		$user = is_object($userList) ? $userList->fetch() : null;

		return $user ?: null;
	}

	protected function loadAssignedByValues(
		string $fieldId = 'ASSIGNED_BY_ID',
		string $prefix = 'ASSIGNED_BY',
		bool $compatible = true
	): void
	{
		$this->loadEntityValues();

		$user = $this->getUserValues($this->document[$fieldId]);
		if (!$user)
		{
			return;
		}

		$compatibleDelimiter = $compatible ? '_' : '.';

		//compatible fields
		$this->document[$prefix . $compatibleDelimiter . 'EMAIL'] = $user['EMAIL'];
		$this->document[$prefix . $compatibleDelimiter . 'WORK_PHONE'] = $user['WORK_PHONE'];
		$this->document[$prefix . $compatibleDelimiter . 'PERSONAL_MOBILE'] = $user['PERSONAL_MOBILE'];

		//new style fields
		$this->document[$prefix . '.LOGIN'] = $user['LOGIN'];
		$this->document[$prefix . '.ACTIVE'] = $user['ACTIVE'];
		$this->document[$prefix . '.NAME'] = $user['NAME'];
		$this->document[$prefix . '.LAST_NAME'] = $user['LAST_NAME'];
		$this->document[$prefix . '.SECOND_NAME'] = $user['SECOND_NAME'];
		$this->document[$prefix . '.WORK_POSITION'] = $user['WORK_POSITION'];
		$this->document[$prefix . '.PERSONAL_WWW'] = $user['PERSONAL_WWW'];
		$this->document[$prefix . '.PERSONAL_CITY'] = $user['PERSONAL_CITY'];
		$this->document[$prefix . '.UF_SKYPE'] = $user['UF_SKYPE'];
		$this->document[$prefix . '.UF_TWITTER'] = $user['UF_TWITTER'];
		$this->document[$prefix . '.UF_FACEBOOK'] = $user['UF_FACEBOOK'];
		$this->document[$prefix . '.UF_LINKEDIN'] = $user['UF_LINKEDIN'];
		$this->document[$prefix . '.UF_XING'] = $user['UF_XING'];
		$this->document[$prefix . '.UF_WEB_SITES'] = $user['UF_WEB_SITES'];
		$this->document[$prefix . '.UF_PHONE_INNER'] = $user['UF_PHONE_INNER'];

		$this->document[$prefix . '_PRINTABLE'] = \CUser::FormatName(
			\CSite::GetNameFormat(false),
			[
				'LOGIN' => $user['LOGIN'],
				'NAME' => $user['NAME'],
				'LAST_NAME' => $user['LAST_NAME'],
				'SECOND_NAME' => $user['SECOND_NAME'],
			],
			true,
			false
		);
	}

	protected function loadProductValues(): void
	{
		$productRows = Crm\ProductRowTable::getList([
			'select' => ['ID', 'PRODUCT_ID', 'CP_PRODUCT_NAME', 'SUM_ACCOUNT'],
			'filter' => [
				'=OWNER_TYPE' => \CCrmOwnerTypeAbbr::ResolveByTypeID($this->typeId),
				'=OWNER_ID' => $this->id,
			],
			'order' => ['SORT' => 'ASC'],
		])->fetchAll();

		$this->document['PRODUCT_IDS'] = array_column($productRows, 'ID');
		$this->document['PRODUCT_IDS_PRINTABLE'] = '';

		if (!empty($productRows))
		{
			$this->document['PRODUCT_IDS_PRINTABLE'] = $this->getProductRowsPrintable($productRows);
		}
	}

	protected function getProductRowsPrintable(array $rows): string
	{
		$text = sprintf(
			'[table][tr][th]%s[/th][th]%s[/th][/tr]',
			Loc::getMessage('CRM_DOCUMENT_FIELD_PRODUCT_NAME'),
			Loc::getMessage('CRM_DOCUMENT_FIELD_PRODUCT_SUM')
		);

		$currencyId = \CCrmCurrency::GetAccountCurrencyID();

		foreach ($rows as $row)
		{
			$text .= sprintf(
				'[tr][td]%s[/td][td]%s[/td][/tr]',
				$row['CP_PRODUCT_NAME'],
				\CCrmCurrency::MoneyToString($row['SUM_ACCOUNT'], $currencyId)
			);
		}

		return $text . '[/table]';
	}

	protected function loadCreatedByPrintable(): void
	{
		$this->loadEntityValues();

		if (isset($this->document['CREATED_BY_ID']))
		{
			$this->document['CREATED_BY_PRINTABLE'] = \CUser::FormatName(
				\CSite::GetNameFormat(false),
				[
					'LOGIN' => $this->document['CREATED_BY_LOGIN'] ?? '',
					'NAME' => $this->document['CREATED_BY_NAME'] ?? '',
					'LAST_NAME' => $this->document['CREATED_BY_LAST_NAME'] ?? '',
					'SECOND_NAME' => $this->document['CREATED_BY_SECOND_NAME'] ?? '',
				],
				true,
				false
			);
		}
	}

	protected function loadUserFieldValues(): void
	{
		$entity = \CCrmOwnerType::ResolveUserFieldEntityID($this->typeId);
		$userFieldsList = Application::getUserTypeManager()->getUserFields($entity);

		if (is_array($userFieldsList))
		{
			foreach ($userFieldsList as $userFieldName => $userFieldParams)
			{
				$fieldTypeID = isset($userFieldParams['USER_TYPE']) ? $userFieldParams['USER_TYPE']['USER_TYPE_ID'] : '';
				$isFieldMultiple = isset($userFieldParams['MULTIPLE']) && $userFieldParams['MULTIPLE'] === 'Y';
				$fieldSettings = $userFieldParams['SETTINGS'] ?? [];

				if (isset($this->document[$userFieldName]))
				{
					$fieldValue = $this->document[$userFieldName];
				}
				elseif (isset($fieldSettings['DEFAULT_VALUE']))
				{
					$fieldValue = $fieldSettings['DEFAULT_VALUE'];
				}
				else
				{
					$this->document[$userFieldName] = $this->document[$userFieldName . '_PRINTABLE'] = '';
					continue;
				}

				if ($fieldTypeID == 'employee')
				{
					if (!$isFieldMultiple)
					{
						$this->document[$userFieldName] = 'user_' . $fieldValue;
					}
					elseif (is_array($fieldValue))
					{
						$this->document[$userFieldName] = [];
						foreach ($fieldValue as $value)
						{
							$this->document[$userFieldName][] = 'user_' . $value;
						}
					}
				}
				elseif ($fieldTypeID == 'crm')
				{
					$defaultTypeName = '';
					foreach ($fieldSettings as $typeName => $flag)
					{
						if ($flag === 'Y')
						{
							$defaultTypeName = $typeName;
							break;
						}
					}

					if (!$isFieldMultiple)
					{
						$this->document[$userFieldName . '_PRINTABLE'] = \CCrmDocument::prepareCrmUserTypeValueView($fieldValue, $defaultTypeName);
					}
					elseif (is_array($fieldValue))
					{
						$views = [];
						foreach ($fieldValue as $value)
						{
							$views[] = \CCrmDocument::prepareCrmUserTypeValueView($value, $defaultTypeName);
						}

						$this->document[$userFieldName . '_PRINTABLE'] = $views;

					}
				}
				elseif ($fieldTypeID == 'enumeration')
				{
					\CCrmDocument::externalizeEnumerationField($this->document, $userFieldName);
				}
				elseif ($fieldTypeID === 'boolean')
				{
					$this->document[$userFieldName] = \CBPHelper::getBool($fieldValue) ? 'Y' : 'N';
					$this->document[$userFieldName . '_PRINTABLE'] = GetMessage($this->document[$userFieldName] === 'Y' ? 'MAIN_YES' : 'MAIN_NO');
				}
				elseif ($fieldTypeID === 'resourcebooking')
				{
					self::prepareResourceBookingField($this->document, $userFieldName);
				}
			}
		}
	}

	private static function prepareResourceBookingField(array &$document, $fieldId): void
	{
		if (empty($document[$fieldId]) || !\Bitrix\Main\Loader::includeModule('calendar'))
		{
			return;
		}

		$resourceList = \Bitrix\Calendar\UserField\ResourceBooking::getResourceEntriesList((array)$document[$fieldId]);

		if ($resourceList)
		{
			$document[$fieldId . '.SERVICE_NAME'] = $resourceList['SERVICE_NAME'];
			$document[$fieldId . '.DATE_FROM'] = $resourceList['DATE_FROM'];
			$document[$fieldId . '.DATE_TO'] = $resourceList['DATE_TO'];
			$users = [];

			foreach ($resourceList['ENTRIES'] as $entry)
			{
				if ($entry['TYPE'] === 'user')
				{
					$users[] = 'user_' . $entry['RESOURCE_ID'];
				}
			}
			$document[$fieldId . '.USERS'] = $users;
		}
	}

	protected function loadFmValues(): void
	{
		$multiFields = $this->getDocumentFieldMulti();
		foreach ($multiFields as $ar)
		{
			if (!isset($this->document[$ar['TYPE_ID']]))
			{
				$this->document[$ar['TYPE_ID']] = [];
			}
			$this->document[$ar['TYPE_ID']]['n0' . $ar['ID']] = [
				'VALUE' => $ar['VALUE'],
				'VALUE_TYPE' => $ar['VALUE_TYPE']
			];

			if (!isset($this->document[$ar['TYPE_ID'] . "_" . $ar['VALUE_TYPE']]))
			{
				$this->document[$ar['TYPE_ID'] . "_" . $ar['VALUE_TYPE']] = [];
			}
			$this->document[$ar['TYPE_ID'] . "_" . $ar['VALUE_TYPE']][] = $ar['VALUE'];

			if (!isset($this->document[$ar['TYPE_ID'] . "_" . $ar['VALUE_TYPE'] . "_PRINTABLE"]))
			{
				$this->document[$ar['TYPE_ID'] . "_" . $ar['VALUE_TYPE'] . "_PRINTABLE"] = "";
			}
			$this->document[$ar['TYPE_ID'] . "_" . $ar['VALUE_TYPE'] . "_PRINTABLE"] .=
				($this->document[$ar['TYPE_ID'] . "_" . $ar['VALUE_TYPE'] . "_PRINTABLE"] ? ", " : "") . $ar['VALUE']
			;

			if (!isset($this->document[$ar['TYPE_ID'] . "_PRINTABLE"]))
			{
				$this->document[$ar['TYPE_ID'] . "_PRINTABLE"] = "";
			}
			$this->document[$ar['TYPE_ID'] . "_PRINTABLE"] .=
				($this->document[$ar['TYPE_ID'] . "_PRINTABLE"] ? ", " : "") . $ar['VALUE']
			;
		}

		$multiFieldTypes = \CCrmFieldMulti::GetEntityTypeList();
		foreach ($multiFieldTypes as $typeId => $arFields)
		{
			if (!isset($this->document[$typeId]))
			{
				$this->document[$typeId] = [];
			}

			$printableFieldName = $typeId . '_PRINTABLE';
			if (!isset($this->document[$printableFieldName]))
			{
				$this->document[$printableFieldName] = '';
			}

			foreach ($arFields as $valueType => $valueName)
			{
				$fieldName = $typeId . '_' . $valueType;
				if (!isset($this->document[$fieldName]))
				{
					$this->document[$fieldName] = [''];
				}

				$printableFieldName = $fieldName . '_PRINTABLE';
				if (!isset($this->document[$printableFieldName]))
				{
					$this->document[$printableFieldName] = '';
				}
			}
		}
	}

	protected function loadObserverValues(): void
	{
		$ids = [];
		$observerIds = Crm\Observer\ObserverManager::getEntityObserverIDs(
			$this->typeId,
			$this->id
		);
		if ($observerIds)
		{
			foreach ($observerIds as $id)
			{
				$ids[] = 'user_' . $id;
			}
		}
		$this->document['OBSERVER_IDS'] = $ids;
	}

	private function getDocumentFieldMulti(): array
	{
		$entityType = \CCrmOwnerType::ResolveName($this->typeId);
		$entityId = $this->id;

		$fields = [];
		$entities = [[$entityType, $entityId]];

		if (
			$entityType === \CCrmOwnerType::LeadName
			&& \CCrmLead::ResolveCustomerType($this->document) === \Bitrix\Crm\CustomerType::RETURNING
		)
		{
			$entities = [];
			if ($this->document['CONTACT_ID'] > 0)
			{
				$entities[] = [\CCrmOwnerType::ContactName, $this->document['CONTACT_ID']];
			}
			if ($this->document['COMPANY_ID'] > 0)
			{
				$entities[] = [\CCrmOwnerType::CompanyName, $this->document['COMPANY_ID']];
			}
			if (!$entities)
			{
				$entities[] = [$entityType, $entityId];
			}
		}

		foreach ($entities as [$type, $id])
		{
			$res = \CCrmFieldMulti::GetList(
				['ID' => 'asc'],
				['ENTITY_ID' => $type, 'ELEMENT_ID' => $id]
			);
			while ($ar = $res->Fetch())
			{
				$fields[] = $ar;
			}
		}

		return $fields;
	}

	protected function loadFormValues(): void
	{
		Crm\WebForm\Internals\BPDocument::fill(
			$this->typeId,
			$this->id,
			$this->document
		);
	}

	protected function loadCommunicationValues(): void
	{
		$callId = Crm\Activity\Provider\Call::getId();
		$emailId = Crm\Activity\Provider\Email::getId();
		$olId = Crm\Activity\Provider\OpenLine::getId();
		$webFormId = Crm\Activity\Provider\WebForm::getId();

		$callDate = $emailDate = $olDate = $webFormDate = null;

		$ormRes = Crm\ActivityTable::getList([
			'select' => ['END_TIME', 'PROVIDER_ID'],
			'filter' => [
				'=COMPLETED' => 'Y',
				'@PROVIDER_ID' => [$callId, $emailId, $olId, $webFormId],
				'=BINDINGS.OWNER_TYPE_ID' => $this->typeId,
				'=BINDINGS.OWNER_ID' => $this->id,
			],
			'order' => ['END_TIME' => 'DESC'],
		]);

		while ($row = $ormRes->fetch())
		{
			if ($callDate === null)
			{
				if ($row['PROVIDER_ID'] === $callId)
				{
					$callDate = $row['END_TIME'];
				}
			}
			if ($emailDate === null)
			{
				if ($row['PROVIDER_ID'] === $emailId)
				{
					$emailDate = $row['END_TIME'];
				}
			}
			if ($olDate === null)
			{
				if ($row['PROVIDER_ID'] === $olId)
				{
					$olDate = $row['END_TIME'];
				}
			}
			if ($webFormDate === null)
			{
				if ($row['PROVIDER_ID'] === $webFormId)
				{
					$webFormDate = $row['END_TIME'];
				}
			}

			if ($callDate !== null && $emailDate !== null && $olDate !== null && $webFormDate !== null)
			{
				break;
			}
		}

		$this->document['COMMUNICATIONS.LAST_CALL_DATE'] = (string)$callDate;
		$this->document['COMMUNICATIONS.LAST_EMAIL_DATE'] = (string)$emailDate;
		$this->document['COMMUNICATIONS.LAST_OL_DATE'] = (string)$olDate;
		$this->document['COMMUNICATIONS.LAST_FORM_DATE'] = (string)$webFormDate;
	}

	protected function loadAddressValues(): void
	{
		$settings = Crm\EntityRequisite::getSingleInstance()->loadSettings($this->typeId, $this->id);

		$filter = [
			'=ENTITY_TYPE_ID' => $this->typeId,
			'=ENTITY_ID' => $this->id,
		];

		if (array_key_exists('REQUISITE_ID_SELECTED', $settings))
		{
			$filter['=ID'] = $settings['REQUISITE_ID_SELECTED'];
		}

		$requisiteId = Crm\EntityRequisite::getSingleInstance()->getList([
			'select' => ['ID'],
			'filter' => $filter,
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC',
			],
		])->fetch();

		if ($requisiteId === false)
		{
			return;
		}

		$addressFields = Crm\EntityRequisite::getAddresses($requisiteId['ID']);

		$primaryAddressFields = current($addressFields);
		foreach ($addressFields as $addressTypeId => $addressTypeFields)
		{
			if ($this->typeId === \CCrmOwnerType::Company && $addressTypeId === Crm\EntityAddressType::Registered)
			{
				continue;
			}
			if (!\CBPHelper::isEmptyValue($addressTypeFields))
			{
				$primaryAddressFields = $addressTypeFields;
				break;
			}
		}

		$registeredAddressFields = $addressFields[Crm\EntityAddressType::Registered];

		$primaryAddressFields = [
			'ADDRESS' => $primaryAddressFields['ADDRESS_1'],
			'ADDRESS_2' => $primaryAddressFields['ADDRESS_2'],
			'ADDRESS_CITY' => $primaryAddressFields['CITY'],
			'ADDRESS_POSTAL_CODE' => $primaryAddressFields['POSTAL_CODE'],
			'ADDRESS_REGION' => $primaryAddressFields['REGION'],
			'ADDRESS_PROVINCE' => $primaryAddressFields['PROVINCE'],
			'ADDRESS_COUNTRY' => $primaryAddressFields['COUNTRY'],
			'ADDRESS_COUNTRY_CODE' => $primaryAddressFields['COUNTRY_CODE'],
			'ADDRESS_LOC_ADDR_ID' => $primaryAddressFields['LOC_ADDR_ID'],
			'ADDRESS_LOC_ADDR' => $primaryAddressFields['LOC_ADDR'],
		];

		$registeredAddressFields = [
			'REG_ADDRESS' => $registeredAddressFields['ADDRESS_1'],
			'ADDRESS_LEGAL' => $registeredAddressFields['ADDRESS_1'],
			'REG_ADDRESS_2' => $registeredAddressFields['ADDRESS_2'],
			'REG_ADDRESS_CITY' => $registeredAddressFields['CITY'],
			'REG_ADDRESS_POSTAL_CODE' => $registeredAddressFields['POSTAL_CODE'],
			'REG_ADDRESS_REGION' => $registeredAddressFields['REGION'],
			'REG_ADDRESS_PROVINCE' => $registeredAddressFields['PROVINCE'],
			'REG_ADDRESS_COUNTRY' => $registeredAddressFields['COUNTRY'],
			'REG_ADDRESS_COUNTRY_CODE' => $registeredAddressFields['COUNTRY_CODE'],
			'REG_ADDRESS_LOC_ADDR_ID' => $registeredAddressFields['LOC_ADDR_ID'],
			'REG_ADDRESS_LOC_ADDR' => $registeredAddressFields['LOC_ADDR'],
		];

		$entityAddressFields = [$primaryAddressFields];
		if ($this->typeId === \CCrmOwnerType::Company)
		{
			$entityAddressFields[] = $registeredAddressFields;
		}
		foreach ($entityAddressFields as $addressTypeFields)
		{
			if (\CBPHelper::isEmptyValue(array_intersect_key($this->document, $addressTypeFields)))
			{
				$this->document = array_merge($this->document, $addressTypeFields);
			}
		}
	}

	protected function loadTrackingValues(): void
	{
		$source = Crm\Tracking\Internals\TraceTable::getTraceByEntity($this->typeId, $this->id);

		$this->document['TRACKING_SOURCE_ID'] = ($source === null) ? 0 : $source['SOURCE_ID'];
	}

	protected function appendDefaultUserPrefixes(): void
	{
		$fieldList = ['CREATED_BY', 'CREATED_BY_ID', 'MODIFY_BY', 'MODIFY_BY_ID', 'ASSIGNED_BY', 'ASSIGNED_BY_ID'];
		foreach ($fieldList as $field)
		{
			if (isset($this->document[$field]))
			{
				$this->document[$field] = 'user_' . $this->document[$field];
			}
		}
	}
}
