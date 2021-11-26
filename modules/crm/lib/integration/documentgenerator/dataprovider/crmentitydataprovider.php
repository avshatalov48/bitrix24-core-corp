<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

\Bitrix\Main\Loader::includeModule('documentgenerator');

use Bitrix\Crm\Automation\Trigger\DocumentCreateTrigger;
use Bitrix\Crm\Automation\Trigger\DocumentViewTrigger;
use Bitrix\Crm\Conversion\Entity\EntityConversionMapTable;
use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Format\PersonNameFormatter;
use Bitrix\Crm\Integration\DocumentGenerator\Value\Money;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Timeline\DocumentController;
use Bitrix\Crm\Timeline\DocumentEntry;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\DocumentGenerator\CreationMethod;
use Bitrix\DocumentGenerator\DataProvider;
use Bitrix\DocumentGenerator\DataProvider\EntityDataProvider;
use Bitrix\DocumentGenerator\DataProvider\User;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Document;
use Bitrix\DocumentGenerator\Integration\Numerator\DocumentNumerable;
use Bitrix\DocumentGenerator\Nameable;
use Bitrix\DocumentGenerator\Template;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Numerator\Hashable;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Main\Type\DateTime;

abstract class CrmEntityDataProvider extends EntityDataProvider implements Hashable, DocumentNumerable, Nameable
{
	protected $multiFields;
	protected $linkData;
	protected $requisiteIds;
	protected $bankDetailIds;
	protected $myCompanyRequisiteIds;
	protected $myCompanyBankDetailIds;
	protected $crmUserTypeManager;
	protected $userFieldDescriptions = [];

	abstract public function getCrmOwnerType();

	public function getTimelineItemIdentifier(): ?ItemIdentifier
	{
		$entityTypeId = (int)$this->getCrmOwnerType();
		$entityId = (int)$this->source;
		if ($entityTypeId > 0 && $entityId > 0)
		{
			return new ItemIdentifier($entityTypeId, $entityId);
		}

		return null;
	}

	/**
	 * @return mixed
	 */
	abstract protected function getUserFieldEntityID();

	public function onDocumentCreate(Document $document)
	{
		$userId = $this->getDocumentUserId($document);
		Loc::loadLanguageFile(__FILE__);
		$text = Loc::getMessage('CRM_DOCGEN_CRMENTITYDATAPROVIDER_COMMENT', ['#TITLE#' => htmlspecialcharsbx($document->getTitle())]);
		$timelineIdentifier = $this->getTimelineItemIdentifier();
		$entityTypeId = $timelineIdentifier ? $timelineIdentifier->getEntityTypeId() : $this->getCrmOwnerType();
		$entityId = $timelineIdentifier ? $timelineIdentifier->getEntityId() : $this->source;
		$entryID = DocumentEntry::create([
			'TEXT' => $text,
			'AUTHOR_ID' => $userId,
			'BINDINGS' => [
				[
					'ENTITY_TYPE_ID' => $entityTypeId,
					'ENTITY_ID' => $entityId,
				]
			],
			'TYPE_CATEGORY_ID' => TimelineType::CREATION,
		], $document->ID);
		if($entryID > 0)
		{
			$saveData = array(
				'COMMENT' => $text,
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId,
				'USER_ID' => $userId,
				'DOCUMENT_ID' => $document->ID,
			);
			DocumentController::getInstance()->onCreate($entryID, $saveData);
		}

		//call automation trigger
		if (CreationMethod::isDocumentCreatedByPublic($document) || CreationMethod::isDocumentCreatedByRest($document))
		{
			$template = $document->getTemplate();
			DocumentCreateTrigger::execute(
				[
					['OWNER_TYPE_ID' => $this->getCrmOwnerType(), 'OWNER_ID' => $this->source]
				],
				['TEMPLATE_ID' => $template->ID]
			);
		}
	}

	/**
	 * @param Document $document
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function onDocumentDelete(Document $document)
	{
		$entries = DocumentEntry::getListByDocumentId($document->ID);
		foreach($entries as $entry)
		{
			$timelineIdentifier = $this->getTimelineItemIdentifier();
			$entityTypeId = $timelineIdentifier ? $timelineIdentifier->getEntityTypeId() : $this->getCrmOwnerType();
			$entityId = $timelineIdentifier ? $timelineIdentifier->getEntityId() : $this->source;
			DocumentController::getInstance()->onDelete($entry['ID'], [
				'TYPE_CATEGORY_ID' => (int)$entry['TYPE_CATEGORY_ID'],
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId,
			]);
		}
	}

	/**
	 * @param Document $document
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function onDocumentUpdate(Document $document)
	{
		Loc::loadLanguageFile(__FILE__);
		$timelineIdentifier = $this->getTimelineItemIdentifier();
		$entityTypeId = $timelineIdentifier ? $timelineIdentifier->getEntityTypeId() : $this->getCrmOwnerType();
		$entityId = $timelineIdentifier ? $timelineIdentifier->getEntityId() : $this->source;
		$entries = DocumentEntry::getListByDocumentId($document->ID);
		foreach($entries as $entry)
		{
			if($entry['TYPE_CATEGORY_ID'] === TimelineType::MODIFICATION)
			{
				$text = Loc::getMessage('CRM_DOCGEN_CRMENTITYDATAPROVIDER_PULIC_LINK_VIEWED', ['#TITLE#' => htmlspecialcharsbx($document->getTitle())]);
			}
			else
			{
				$text = Loc::getMessage('CRM_DOCGEN_CRMENTITYDATAPROVIDER_COMMENT', ['#TITLE#' => htmlspecialcharsbx($document->getTitle())]);
			}
			if($entry['COMMENT'] != $text)
			{
				$entry['COMMENT'] = $text;
				DocumentEntry::update($entry['ID'], $entry);
			}
			$saveData = array(
				'TITLE' => $document->getTitle(),
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId,
				'USER_ID' => $this->getDocumentUserId($document),
				'DOCUMENT_ID' => $document->ID,
			);
			DocumentController::getInstance()->onUpdate($entry['ID'], $saveData);
		}
	}

	/**
	 * @param Document $document
	 * @param bool $isFirstTime
	 */
	public function onPublicView(Document $document, bool $isFirstTime = false)
	{
		//call automation trigger
		$template = $document->getTemplate();

		DocumentViewTrigger::execute(
			[
				['OWNER_TYPE_ID' => $this->getCrmOwnerType(), 'OWNER_ID' => $this->source]
			],
			['TEMPLATE_ID' => $template->ID]
		);

		if($isFirstTime)
		{
			$timelineIdentifier = $this->getTimelineItemIdentifier();
			$entityTypeId = $timelineIdentifier ? $timelineIdentifier->getEntityTypeId() : $this->getCrmOwnerType();
			$entityId = $timelineIdentifier ? $timelineIdentifier->getEntityId() : $this->source;
			$text = Loc::getMessage('CRM_DOCGEN_CRMENTITYDATAPROVIDER_PULIC_LINK_VIEWED', ['#TITLE#' => htmlspecialcharsbx($document->getTitle())]);
			$entryId = DocumentEntry::create([
				'TEXT' => $text,
				'BINDINGS' => [
					[
						'ENTITY_TYPE_ID' => $entityTypeId,
						'ENTITY_ID' => $entityId,
					],
				],
				'TYPE_CATEGORY_ID' => TimelineType::MODIFICATION,
			], $document->ID);

			if($entryId > 0)
			{
				$saveData = array(
					'COMMENT' => $text,
					'ENTITY_TYPE_ID' => $entityTypeId,
					'ENTITY_ID' => $entityId,
					'DOCUMENT_ID' => $document->ID,
				);
				DocumentController::getInstance()->addToStack($entryId, 'timeline_document_add', $saveData);
			}
		}
	}

	/**
	 * @param Document $document
	 * @return int
	 */
	protected function getDocumentUserId(Document $document)
	{
		if(method_exists($document, 'getUserId'))
		{
			$userId = $document->getUserId();
		}
		else
		{
			$userId = \CCrmSecurityHelper::GetCurrentUserID();
		}

		return $userId;
	}

	/**
	 * @return array
	 */
	public function getFields()
	{
		if($this->fields === null)
		{
			$fields = array_merge(parent::getFields(), $this->getCommonFields());

			$this->fields = $fields;
			$fields = $this->getUserFields();
			$this->fields = array_merge($this->fields, $fields);
			foreach($this->fields as $placeholder => $field)
			{
				if(mb_substr($placeholder, 0, 3) === 'UF_')
				{
					if(mb_substr($placeholder, -7) === '_SINGLE')
					{
						unset($this->fields[$placeholder]);
					}
					else
					{
						$this->userFieldDescriptions[$placeholder] = $this->fields[$placeholder]['DESCRIPTION'];
						unset($this->fields[$placeholder]['DESCRIPTION']);
					}
				}
			}
		}

		return $this->fields;
	}

	protected function isEnableMyCompany(): bool
	{
		if (!$this->isLightMode())
		{
			return true;
		}

		return isset($this->options['enableMyCompany']) && $this->options['enableMyCompany'] === true;
	}

	public function getCommonFields(): array
	{
		$fields = [];

		if ($this->isEnableMyCompany())
		{
			$fields['MY_COMPANY'] = [
				'PROVIDER' => Company::class,
				'VALUE' => [$this, 'getMyCompanyId'],
				'TITLE' => GetMessage('CRM_DOCGEN_CRMENTITYDATAPROVIDER_MY_COMPANY_TITLE'),
				'OPTIONS' => [
					'MY_COMPANY' => 'Y',
					'VALUES' => [
						'REQUISITE' => $this->getMyCompanyRequisiteId(),
						'BANK_DETAIL' => $this->getMyCompanyBankDetailId(),
					],
					'isLightMode' => true,
				],
			];
		}

		$fields['REQUISITE'] = [
			'PROVIDER' => Requisite::class,
			'VALUE' => [$this, 'getRequisiteId'],
			'TITLE' => GetMessage('CRM_DOCGEN_CRMENTITYDATAPROVIDER_CUSTOMER_REQUISITE_TITLE'),
		];
		$fields['BANK_DETAIL'] = [
			'PROVIDER' => BankDetail::class,
			'VALUE' => [$this, 'getBankDetailId'],
			'TITLE' => GetMessage('CRM_DOCGEN_CRMENTITYDATAPROVIDER_BANK_DETAIL_TITLE'),
		];

		$fields['COMPANY'] = [
			'PROVIDER' => Company::class,
			'VALUE' => [$this, 'getCompanyId'],
			'TITLE' => GetMessage('CRM_DOCGEN_CRMENTITYDATAPROVIDER_COMPANY_TITLE'),
			'OPTIONS' => [
				'DISABLE_MY_COMPANY' => true,
				'VALUES' => [
					'REQUISITE' => $this->getRequisiteId(),
					'BANK_DETAIL' => $this->getBankDetailId(),
				],
				'isLightMode' => true,
			]
		];
		$fields['CONTACT'] = [
			'PROVIDER' => Contact::class,
			'VALUE' => [$this, 'getContactId'],
			'TITLE' => GetMessage('CRM_DOCGEN_CRMENTITYDATAPROVIDER_CONTACT_TITLE'),
			'OPTIONS' => [
				'DISABLE_MY_COMPANY' => true,
				'isLightMode' => true,
			],
		];

		$fields['ASSIGNED'] = [
			'TITLE' => GetMessage('CRM_DOCGEN_CRMENTITYDATAPROVIDER_ASSIGNED_TITLE'),
			'VALUE' => [$this, 'getAssignedId'],
			'PROVIDER' => User::class,
			'OPTIONS' => [
				'FORMATTED_NAME_FORMAT' => [
					'format' => static::getNameFormat(),
				]
			]
		];

		$fields['CLIENT_PHONE'] = [
			'TITLE' => GetMessage('CRM_DOCGEN_CRMENTITYDATAPROVIDER_CLIENT_PHONE_TITLE'),
			'VALUE' => [$this, 'getClientPhone'],
			'TYPE' => 'PHONE',
			'FORMAT' => [
				'mfirst' => true,
			],
		];
		$fields['CLIENT_EMAIL'] = [
			'TITLE' => GetMessage('CRM_DOCGEN_CRMENTITYDATAPROVIDER_CLIENT_EMAIL_TITLE'),
			'VALUE' => [$this, 'getClientEmail'],
			'FORMAT' => [
				'mfirst' => true,
			],
		];
		$fields['CLIENT_WEB'] = [
			'TITLE' => GetMessage('CRM_DOCGEN_CRMENTITYDATAPROVIDER_CLIENT_WEB_TITLE'),
			'VALUE' => [$this, 'getClientWeb'],
			'FORMAT' => [
				'mfirst' => true,
			],
		];

		if($this->hasLeadField())
		{
			$fields['LEAD'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_CRMENTITYDATAPROVIDER_LEAD_TITLE'),
				'PROVIDER' => Lead::class,
				'VALUE' => 'LEAD_ID',
				'OPTIONS' => [
					'isLightMode' => true,
				],
			];
		}

		return $fields;
	}

	/**
	 * @return array
	 */
	public function getUserFields()
	{
		$result = [];

		$manager = $this->getCrmUserTypeManager();
		if(!$manager)
		{
			return $result;
		}

		$crmOwnerTypeProvidersMap = DocumentGeneratorManager::getInstance()->getCrmOwnerTypeProvidersMap(false);
		$enumerationFields = [];
		$fields = $manager->GetEntityFields($this->getSource());
		foreach($fields as $code => $field)
		{
			if(!isset($this->getAvailableUserFieldTypes()[$field['USER_TYPE_ID']]))
			{
				if(isset($this->fields[$code]))
				{
					unset($this->fields[$code]);
				}
				continue;
			}
			$result[$code] = [
				'TITLE' => $field['EDIT_FORM_LABEL'],
				'VALUE' => [$this, 'getUserFieldValue'],
				'DESCRIPTION' => $field,
			];
			if($field['USER_TYPE_ID'] === 'file')
			{
				$result[$code]['TYPE'] = DataProvider::FIELD_TYPE_IMAGE;
			}
			elseif($field['USER_TYPE_ID'] === 'enumeration')
			{
				$enumerationFields[] = $field;
			}
			elseif($field['USER_TYPE_ID'] === 'employee')
			{
				if($field['MULTIPLE'] === 'Y')
				{
					$result[$code]['PROVIDER'] = DataProvider\ArrayDataProvider::class;
					$result[$code]['OPTIONS'] = [
						'ITEM_PROVIDER' => User::class,
						'ITEM_NAME' => 'ITEM',
						'ITEM_OPTIONS' => [
							'isLightMode' => true,
						],
					];
					$result[$code]['DESCRIPTION'] = $field;
				}
				else
				{
					$result[$code]['PROVIDER'] = User::class;
				}
			}
			elseif($field['USER_TYPE_ID'] === 'date')
			{
				$result[$code]['TYPE'] = static::FIELD_TYPE_DATE;
			}
			elseif($field['USER_TYPE_ID'] === 'datetime')
			{
				$result[$code]['TYPE'] = static::FIELD_TYPE_DATE;
				$result[$code]['FORMAT'] = ['format' => DateTime::getFormat(DataProviderManager::getInstance()->getCulture())];
			}
			elseif($field['USER_TYPE_ID'] === 'crm' && !$this->isLightMode())
			{
				$provider = null;
				$entityTypes = [];
				$field['SETTINGS'] = (array)$field['SETTINGS'];
				foreach ($field['SETTINGS'] as $entityName => $isEnabled)
				{
					if ($isEnabled !== 'Y')
					{
						continue;
					}
					$entityTypeId = \CCrmOwnerType::ResolveID($entityName);
					if ($entityTypeId > 0)
					{
						$entityTypes[] = $entityTypeId;
					}
				}
				$isCrmPrefix = (count($entityTypes) > 1);
				if (
					(
						$isCrmPrefix
						|| (!is_numeric($field['VALUE']))
					)
					&& $field['VALUE'] !== false
					&& !is_array($field['VALUE'])
				)
				{
					$parts = explode('_', $field['VALUE']);
					$field['VALUE'] = $parts[1];
					$ownerTypeId = \CCrmOwnerType::ResolveID($parts[0]);
				}
				else
				{
					$ownerTypeId = $entityTypes[0];
				}
				if($ownerTypeId > 0)
				{
					if(isset($crmOwnerTypeProvidersMap[$ownerTypeId]))
					{
						$provider = $crmOwnerTypeProvidersMap[$ownerTypeId];
					}
				}
				if($provider)
				{
					if($field['MULTIPLE'] === 'Y')
					{
						$result[$code]['PROVIDER'] = DataProvider\ArrayDataProvider::class;
						$result[$code]['OPTIONS'] = [
							'ITEM_PROVIDER' => $provider,
							'ITEM_NAME' => 'ITEM',
							'ITEM_OPTIONS' => [
								'DISABLE_MY_COMPANY' => true,
								'isLightMode' => true,
							],
						];
						$result[$code]['DESCRIPTION'] = $field;
					}
					else
					{
						$result[$code]['PROVIDER'] = $provider;
						$result[$code]['OPTIONS']['isLightMode'] = true;
						$result[$code]['DESCRIPTION'] = $field;
					}
				}
			}
			elseif($field['USER_TYPE_ID'] === 'money')
			{
				$result[$code]['TYPE'] = Money::class;
			}
		}

		$enumInfos = \CCrmUserType::PrepareEnumerationInfos($enumerationFields);
		foreach($enumInfos as $placeholder => $data)
		{
			foreach($data as $enum)
			{
				$result[$placeholder]['DESCRIPTION']['DATA'][$enum['ID']] = $enum['VALUE'];
			}
		}

		$alternativeUserFieldNames = $this->getAlternativeUserFieldNames(array_keys($result));
		foreach($alternativeUserFieldNames as $placeholder => $alternatives)
		{
			foreach($alternatives as $alternative)
			{
				if(!isset($result[$alternative]))
				{
					$result[$alternative] = [
						'TITLE' => $result[$placeholder]['TITLE'],
						'VALUE' => $placeholder,
						'OPTIONS' => [
							'COPY' => $placeholder,
						]
					];
				}
			}
		}

		return $result;
	}

	/**
	 * @param array $placeholders
	 * @return array
	 */
	protected function getAlternativeUserFieldNames(array $placeholders)
	{
		$result = [];

		if(empty($placeholders))
		{
			return $result;
		}

		$map = $this->getFullMap();
		foreach($map as $item)
		{
			foreach($placeholders as $placeholder)
			{
				if(isset($item[$placeholder]))
				{
					foreach($item as $name => $t)
					{
						if($name != $placeholder)
						{
							$result[$placeholder][] = $name;
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	protected function getFullMap()
	{
		static $result = null;
		if($result === null)
		{
			$result = [];

			$maps = EntityConversionMapTable::getList(['select' => ['DATA']]);
			while($map = $maps->fetch())
			{
				$data = unserialize($map['DATA'], [
					'allowed_classes' => false,
				]);
				if(!is_array($data) || !isset($data['items']) || !is_array($data['items']) || empty($data['items']))
				{
					continue;
				}

				foreach($data['items'] as $item)
				{
					if(isset($item['srcField']) && isset($item['dstField']) && !empty($item['srcField']) && !empty($item['dstField']))
					{
						$isFound = false;
						foreach($result as &$items)
						{
							if(isset($items[$item['srcField']]) || isset($items[$item['dstField']]))
							{
								$isFound = true;
								$items[$item['dstField']] = true;
								$items[$item['srcField']] = true;
							}
						}
						if(!$isFound)
						{
							$result[] = [
								$item['dstField'] => true,
								$item['srcField'] => true,
							];
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param string $placeholder
	 * @return null
	 */
	public function getUserFieldValue($placeholder = null)
	{
		$value = null;
		if(!$placeholder || !isset($this->fields[$placeholder]))
		{
			return $value;
		}
		$field = $this->userFieldDescriptions[$placeholder];

		$value = $field['VALUE'];
		if(!$value && $field['USER_TYPE_ID'] != 'boolean')
		{
			return $value;
		}
		if($field['USER_TYPE_ID'] == 'file')
		{
			if(is_array($value))
			{
				$value = \CFile::GetPath(reset($value));
			}
			else
			{
				$value = \CFile::GetPath($value);
			}
		}
		elseif($field['USER_TYPE_ID'] == 'enumeration')
		{
			if(!isset($field['DATA']))
			{
				$value = null;
			}
			elseif(is_array($value))
			{
				$result = [];
				foreach($value as $item)
				{
					$result[] = $field['DATA'][$item];
				}
				$value = $result;
			}
			else
			{
				$value = $field['DATA'][$value];
			}
		}
		elseif($field['USER_TYPE_ID'] == 'money')
		{
			$result = null;
			if(!is_array($value))
			{
				$parts = explode('|', $value);
				$result = new Money($parts[0], ['CURRENCY_ID' => $parts[1]]);
			}
			else
			{
				$result = [];
				foreach($value as $val)
				{
					$parts = explode('|', $val);
					$result[] = new Money($parts[0], ['CURRENCY_ID' => $parts[1]]);
				}
			}
			$value = $result;
		}
		elseif($field['USER_TYPE_ID'] == 'boolean')
		{
			if($value)
			{
				$value = DataProviderManager::getInstance()->getLangPhraseValue($this, 'UF_TYPE_BOOLEAN_YES');
			}
			else
			{
				$value = DataProviderManager::getInstance()->getLangPhraseValue($this, 'UF_TYPE_BOOLEAN_NO');
			}
		}
		elseif($field['USER_TYPE_ID'] == 'address')
		{
			$result = [];
			if(is_array($value))
			{
				foreach($value as $val)
				{
					if(mb_strpos($val, '|') !== false)
					{
						$array = explode('|', $val);
						$val = $array[0];
					}
					$result[] = $val;
				}
			}
			else
			{
				if(mb_strpos($value, '|') !== false)
				{
					$array = explode('|', $value);
					$value = $array[0];
				}
				$result = $value;
			}
			$value = $result;
		}
		elseif($field['USER_TYPE_ID'] == 'iblock_element')
		{
			$value = null;
			if(Loader::includeModule('iblock') && !empty($field['VALUE']))
			{
				$value = [];
				$elements = ElementTable::getList([
					'select' => ['NAME'],
					'filter' => ['ID' => $field['VALUE']]
				]);
				while($element = $elements->fetch())
				{
					$value[] = $element['NAME'];
				}
			}
		}
		elseif($field['USER_TYPE_ID'] == 'crm' && is_array($value))
		{
			if($field['MULTIPLE'] === 'Y' && $this->fields[$placeholder]['PROVIDER'] && $this->fields[$placeholder]['PROVIDER'] === DataProvider\ArrayDataProvider::class)
			{
				$result = [];
				foreach($value as $val)
				{
					if(!is_numeric($val))
					{
						[, $val] = explode('_', $val);
					}
					$val = intval($val);
					if($val > 0)
					{
						$provider = DataProviderManager::getInstance()->getDataProvider(
							$this->fields[$placeholder]['OPTIONS']['ITEM_PROVIDER'],
							$val,
							$this->fields[$placeholder]['OPTIONS']['ITEM_OPTIONS'],
							$this);
						if($provider)
						{
							$result[] = $provider;
						}
					}
				}
				$value = $result;
			}
			else
			{
				$value = reset($value);
			}
		}
		elseif($field['USER_TYPE_ID'] === 'employee' && is_array($value))
		{
			if($field['MULTIPLE'] === 'Y' && $this->fields[$placeholder]['PROVIDER'] && $this->fields[$placeholder]['PROVIDER'] === DataProvider\ArrayDataProvider::class)
			{
				$result = [];
				foreach($value as $val)
				{
					$val = intval($val);
					if($val > 0)
					{
						$provider = DataProviderManager::getInstance()->getDataProvider(
							$this->fields[$placeholder]['OPTIONS']['ITEM_PROVIDER'],
							$val,
							$this->fields[$placeholder]['OPTIONS']['ITEM_OPTIONS'],
							$this);
						if($provider)
						{
							$result[] = $provider;
						}
					}
				}
				$value = $result;
			}
			else
			{
				$value = reset($value);
			}
		}

		return $value;
	}

	/**
	 * @return \CCrmUserType
	 */
	protected function getCrmUserTypeManager()
	{
		$userFieldEntityId = $this->getUserFieldEntityID();
		if($this->crmUserTypeManager === null && !empty($userFieldEntityId))
		{
			global $USER_FIELD_MANAGER;
			$this->crmUserTypeManager = new \CCrmUserType($USER_FIELD_MANAGER, $userFieldEntityId);
		}

		return $this->crmUserTypeManager;
	}

	/**
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Exception
	 */
	protected function getLinkData()
	{
		if($this->linkData === null)
		{
			$this->linkData = EntityLink::getByEntity($this->getCrmOwnerType(), $this->getSource());
		}

		return $this->linkData;
	}

	/**
	 * @return array
	 */
	protected function getAvailableUserFieldTypes()
	{
		return [
			'string' => 'string',
			'integer' => 'integer',
			'enumeration' => 'enumeration',
			'file' => 'file',
			'url' => 'url',
			'date' => 'date',
			'datetime' => 'datetime',
			'money' => 'money',
			'boolean' => 'boolean',
			'double' => 'double',
			'crm' => 'crm',
			'employee' => 'employee',
			'address' => 'address',
			'iblock_element' => 'iblock_element',
		];
	}

	/**
	 * @return int|string
	 */
	public function getSelfCompanyId()
	{
		$options = $this->getOptions();
		$myCompanyId = null;
		if (isset($options['VALUES']['MY_COMPANY']))
		{
			$myCompanyId = (int) $options['VALUES']['MY_COMPANY'];
		}
		if (!$myCompanyId)
		{
			$dataProviderManager = DataProviderManager::getInstance();
			$myCompany = $dataProviderManager->getValueFromList($dataProviderManager->getDataProviderValue($this, 'MY_COMPANY'));
			if ($myCompany instanceof DataProvider)
			{
				$myCompanyId = (int) $myCompany->getSource();
			}
			else
			{
				$myCompanyId = (int) $myCompany;
			}
		}
		if($myCompanyId > 0)
		{
			return $myCompanyId;
		}

		return '';
	}

	/**
	 * @return int
	 */
	public function getSelfId()
	{
		return $this->getSource();
	}

	/**
	 * @return string
	 */
	public function getHash()
	{
		return 'COMPANY_ID_' . $this->getSelfCompanyId();
	}

	/**
	 * @return int|string
	 */
	public function getClientId()
	{
		$id = '';

		$company = $this->getValue('COMPANY');
		if($company instanceof DataProvider)
		{
			$id = $company->getSource();
		}
		if(!$id)
		{
			$contact = $this->getValue('CONTACT');
			if($contact instanceof DataProvider)
			{
				$id = $contact->getSource();
			}
		}

		return $id;
	}

	/**
	 * @return array
	 */
	public function getEmailCommunication()
	{
		$result = [];
		$company = $this->getValue('COMPANY');
		if($company instanceof Company)
		{
			$email = $company->getValue('EMAIL_WORK');
			if(!$email)
			{
				$email = $company->getValue('EMAIL_HOME');
			}
			$result[] = [
				'entityType' => 'COMPANY',
				'entityId' => $company->getSource(),
				'entityTitle' => $company->getValue('TITLE'),
				'type' => 'EMAIL',
				'value' => $email,
			];
		}
		$contact = $this->getValue('CONTACT');
		if($contact instanceof Contact)
		{
			if(!$email)
			{
				$email = $contact->getValue('EMAIL_WORK');
			}
			if(!$email)
			{
				$email = $contact->getValue('EMAIL_HOME');
			}
			$result[] = [
				'entityType' => 'CONTACT',
				'entityId' => $contact->getSource(),
				'entityTitle' => $contact->getValue('FORMATTED_NAME'),
				'type' => 'EMAIL',
				'value' => $email,
			];
		}

		return $result;
	}

	/**
	 * @return int
	 */
	protected function getMyCompanyRequisiteId()
	{
		if($this->myCompanyRequisiteIds === null)
		{
			if($this->isLoaded())
			{
				$this->myCompanyRequisiteIds = '';
				if(!empty($this->getOptions()['VALUES']['MY_COMPANY.REQUISITE']))
				{
					$this->myCompanyRequisiteIds = $this->getOptions()['VALUES']['MY_COMPANY.REQUISITE'];
				}
				else
				{
					$linkData = $this->getLinkData();
					if($linkData['MC_REQUISITE_ID'] > 0)
					{
						$this->myCompanyRequisiteIds = $linkData['MC_REQUISITE_ID'];
					}
					else
					{
						$requisiteLink = EntityLink::getDefaultMyCompanyRequisiteLink();
						if(isset($requisiteLink['MC_REQUISITE_ID']) && $requisiteLink['MC_REQUISITE_ID'] > 0)
						{
							$this->myCompanyRequisiteIds = $requisiteLink['MC_REQUISITE_ID'];
						}
					}
				}
			}
		}

		return $this->myCompanyRequisiteIds;
	}

	/**
	 * @return int
	 */
	protected function getMyCompanyBankDetailId()
	{
		if($this->myCompanyBankDetailIds === null)
		{
			$this->myCompanyBankDetailIds = '';
			if($this->isLoaded())
			{
				if(!empty($this->getOptions()['VALUES']['MY_COMPANY.BANK_DETAIL']))
				{
					$this->myCompanyBankDetailIds = $this->getOptions()['VALUES']['MY_COMPANY.BANK_DETAIL'];
				}
				else
				{
					$linkData = $this->getLinkData();
					$this->myCompanyBankDetailIds = $linkData['MC_BANK_DETAIL_ID'];
				}
			}
		}

		return $this->myCompanyBankDetailIds;
	}

	/**
	 * @return int|array
	 */
	public function getRequisiteId()
	{
		if($this->requisiteIds === null)
		{
			$this->requisiteIds = '';
			if($this->isLoaded())
			{
				$requisiteId = false;
				if(isset($this->data['REQUISITE']) && $this->data['REQUISITE'] instanceof DataProvider)
				{
					$requisite = $this->data['REQUISITE'];
					/** @var DataProvider $requisite */
					$requisiteId = $requisite->getSource();
				}
				elseif(!empty($this->getOptions()['VALUES']['REQUISITE']))
				{
					$requisiteId = $this->getOptions()['VALUES']['REQUISITE'];
				}
				else
				{
					$linkData = $this->getLinkData();
					if($linkData['REQUISITE_ID'] > 0)
					{
						$requisiteId = $linkData['REQUISITE_ID'];
					}
				}

				$entityTypeId = \CCrmOwnerType::Company;
				$entityId = $this->getCompanyId();
				if(!$entityId)
				{
					$entityId = $this->getContactId();
					$entityTypeId = \CCrmOwnerType::Contact;
				}

				if($entityId > 0)
				{
					/** @var EntityRequisite $entityRequisite */
					$entityRequisite = EntityRequisite::getSingleInstance();
					if (!$requisiteId)
					{
						$settings = $entityRequisite->loadSettings($entityTypeId, $entityId);
						if (isset($settings['REQUISITE_ID_SELECTED']) && $settings['REQUISITE_ID_SELECTED'] > 0)
						{
							$defRequisiteId = (int)$settings['REQUISITE_ID_SELECTED'];
							if ($entityRequisite->exists($defRequisiteId))
							{
								$requisiteId = $defRequisiteId;
							}
						}
					}
					$requisites = $entityRequisite->getList([
						'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
						'filter' => [
							'=ENTITY_TYPE_ID' => $entityTypeId,
							'=ENTITY_ID' => $entityId,
						],
						'select' => ['ID', 'NAME'],
					])->fetchAll();
					if($requisites)
					{
						if(count($requisites) == 1)
						{
							$this->requisiteIds = (int)$requisites[0]['ID'];
						}
						else
						{
							$this->requisiteIds = [];
							foreach($requisites as $requisite)
							{
								$this->requisiteIds[$requisite['ID']] = [
									'VALUE' => $requisite['ID'],
									'TITLE' => $requisite['NAME'],
									'SELECTED' => false,
								];
								if($requisiteId && $requisiteId == $requisite['ID'])
								{
									$this->requisiteIds[$requisite['ID']]['SELECTED'] = true;
								}
							}
						}
					}
				}
			}
		}

		return $this->requisiteIds;
	}

	/**
	 * @return int
	 */
	public function getBankDetailId()
	{
		if($this->bankDetailIds === null)
		{
			if($this->isLoaded())
			{
				if(isset($this->data['BANK_DETAIL']) && $this->data['BANK_DETAIL'] instanceof DataProvider)
				{
					$bankDetail = $this->data['BANK_DETAIL'];
					/** @var DataProvider $bankDetail */
					$bankDetailId = $bankDetail->getSource();
				}
				elseif(!empty($this->getOptions()['VALUES']['BANK_DETAIL']))
				{
					$bankDetailId = $this->getOptions()['VALUES']['BANK_DETAIL'];
				}
				else
				{
					$linkData = $this->getLinkData();
					$bankDetailId = $linkData['BANK_DETAIL_ID'];
				}

				$requisiteId = DataProviderManager::getInstance()->getValueFromList($this->getRequisiteId(), true);
				if(!is_array($requisiteId) && $requisiteId > 0)
				{
					$bankDetails = EntityBankDetail::getSingleInstance()->getList([
						'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
						'filter' => [
							'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
							'=ENTITY_ID' => $requisiteId
						],
						'select' => ['ID', 'NAME'],
					])->fetchAll();
					if($bankDetails)
					{
						if(count($bankDetails) == 1)
						{
							$this->bankDetailIds = (int)$bankDetails[0]['ID'];
						}
						else
						{
							$this->bankDetailIds = [];
							foreach($bankDetails as $bankDetail)
							{
								$this->bankDetailIds[$bankDetail['ID']] = [
									'VALUE' => $bankDetail['ID'],
									'TITLE' => $bankDetail['NAME'],
									'SELECTED' => false,
								];
								if($bankDetailId && $bankDetailId == $bankDetail['ID'])
								{
									$this->bankDetailIds[$bankDetail['ID']]['SELECTED'] = true;
								}
							}
						}
					}
				}
			}
		}

		return $this->bankDetailIds;
	}

	/**
	 * @param null $defaultMyCompanyId
	 * @return int|array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function getMyCompanyId($defaultMyCompanyId = null)
	{
		$defaultMyCompanyId = intval($defaultMyCompanyId);
		if(!$defaultMyCompanyId)
		{
			$defaultMyCompanyId = $this->getLinkData()['MYCOMPANY_ID'];
		}
		if(!$defaultMyCompanyId)
		{
			$defaultMyCompanyId = EntityLink::getDefaultMyCompanyId();
		}

		$companies = [];
		$res = \CCrmCompany::GetListEx(
			['ID' => 'ASC'],
			['IS_MY_COMPANY' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['ID', 'TITLE']
		);
		while($company = $res->Fetch())
		{
			$selected = false;
			if($defaultMyCompanyId > 0 && $defaultMyCompanyId == $company['ID'])
			{
				$selected = true;
			}
			$companies[] = [
				'VALUE' => $company['ID'],
				'TITLE' => $company['TITLE'],
				'SELECTED' => $selected,
			];
		}
		if(count($companies) === 0)
		{
			return null;
		}
		elseif(count($companies) === 1)
		{
			return $companies[0]['VALUE'];
		}

		return $companies;
	}

	/**
	 * @return int|null
	 */
	public function getCompanyId()
	{
		if(isset($this->data['COMPANY_ID']) && $this->data['COMPANY_ID'] > 0)
		{
			return $this->data['COMPANY_ID'];
		}

		return null;
	}

	/**
	 * @return int|null
	 */
	public function getContactId()
	{
		if(isset($this->data['CONTACT_ID']) && $this->data['CONTACT_ID'] > 0)
		{
			return $this->data['CONTACT_ID'];
		}

		return null;
	}

	/**
	 * @return array
	 */
	protected function loadMultiFields()
	{
		$result = [];
		if($this->isLoaded())
		{
			if($this->multiFields === null)
			{
				$this->multiFields = [];

				$entityId = \CCrmOwnerType::CompanyName;
				$elementId = $this->getCompanyId();
				if(!$elementId)
				{
					$elementId = $this->getContactId();
					$entityId = \CCrmOwnerType::ContactName;
				}

				if($elementId > 0)
				{
					$multiFieldDbResult = \CCrmFieldMulti::GetList(
						['ID' => 'asc'],
						[
							'ENTITY_ID' => $entityId,
							'ELEMENT_ID' => $elementId,
						]
					);
					while($multiField = $multiFieldDbResult->Fetch())
					{
						$this->multiFields[$multiField['TYPE_ID']][] = $multiField;
					}
				}
			}
			$result = $this->multiFields;
		}

		return $result;
	}

	/**
	 * @param string $type - EMAIL, PHONE, WEB, IM.
	 * @param string $valueType - HOME, WORK, OTHER.
	 * @return array
	 */
	protected function getMultiFields($type = null, $valueType = null)
	{
		$multiFields = $this->loadMultiFields();

		$result = [];
		foreach($multiFields as $typeId => $fields)
		{
			if(!empty($type) && $typeId == $type || (empty($type)))
			{
				if(is_array($fields))
				{
					foreach($fields as $value)
					{
						if(
							(!empty($valueType) && $value['VALUE_TYPE'] == $valueType) ||
							(empty($valueType))
						)
						{
							$result[] = $value['VALUE'];
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function getClientPhone()
	{
		return $this->getMultiFields('PHONE');
	}

	/**
	 * @return array
	 */
	public function getClientEmail()
	{
		return $this->getMultiFields('EMAIL');
	}

	/**
	 * @return array
	 */
	public function getClientWeb()
	{
		return $this->getMultiFields('WEB');
	}

	/**
	 * @return array
	 */
	public function getClientIm()
	{
		$multiFields = $this->loadMultiFields();
		$descriptions = \CCrmFieldMulti::GetEntityTypes()['IM'];

		$result = [];
		foreach($multiFields as $typeId => $fields)
		{
			if($typeId == 'IM')
			{
				if(is_array($fields))
				{
					foreach($fields as $value)
					{
						$result[] = $descriptions[$value['VALUE_TYPE']]['SHORT'].': '.$value['VALUE'];
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @return int|string
	 */
	public function getAssignedId()
	{
		return $this->data['ASSIGNED_BY_ID'];
	}

	/**
	 * @param Document $document
	 * @return array
	 */
	public function getAdditionalDocumentInfo(Document $document)
	{
		$data = parent::getAdditionalDocumentInfo($document);

		$stampPlaceholders = [];
		$data['changeStampsEnabled'] = false;
		$template = $document->getTemplate();
		if($template)
		{
			$stampPlaceholders = $this->getTemplateStampsFields($template);
		}
		if(!empty($stampPlaceholders))
		{
			$documentFields = $document->getFields($stampPlaceholders);
			foreach($stampPlaceholders as $placeholder)
			{
				if(isset($documentFields[$placeholder]['VALUE']) && !empty($documentFields[$placeholder]['VALUE']) && $documentFields[$placeholder]['VALUE'] != false)
				{
					$data['changeStampsEnabled'] = true;
					break;
				}
			}
			if(!$data['changeStampsEnabled'])
			{
				$data['changeStampsDisabledReason'] = GetMessage('CRM_DOCGEN_CRMENTITYDATAPROVIDER_STAMPS_DISABLED_EMPTY_FIELDS');
				$data['myCompanyEditUrl'] = $this->getMyCompanyEditUrl();
				if($data['myCompanyEditUrl'])
				{
					$data['changeStampsDisabledReason'] .= '<br />'.GetMessage('CRM_DOCGEN_CRMENTITYDATAPROVIDER_EDIT_MY_COMPANY', ['#URL#' => $data['myCompanyEditUrl']]);
				}
			}
		}
		else
		{
			$data['changeStampsDisabledReason'] = GetMessage('CRM_DOCGEN_CRMENTITYDATAPROVIDER_STAMPS_DISABLED_NO_TEMPLATE');
		}

		return $data;
	}

	/**
	 * @param Template $template
	 * @return array
	 */
	protected function getTemplateStampsFields(Template $template)
	{
		$placeholders = [];

		$fields = $template->getFields();
		foreach($fields as $placeholder => $field)
		{
			if(isset($field['TYPE']) && $field['TYPE'] === DataProvider::FIELD_TYPE_STAMP)
			{
				$placeholders[] = $placeholder;
			}
		}

		return $placeholders;
	}

	/**
	 * @param bool $singleOnly
	 * @return bool|string
	 */
	public function getMyCompanyEditUrl($singleOnly = true)
	{
		$siteDir = rtrim(SITE_DIR, '/');
		if($singleOnly)
		{
			$myCompanyId = DataProviderManager::getInstance()->getValueFromList($this->getMyCompanyId());
			if($myCompanyId > 0)
			{
				return $siteDir.'/crm/configs/mycompany/edit/'.$myCompanyId.'/';
			}
			else
			{
				return false;
			}
		}
		else
		{
			$myCompanyId = $this->getMyCompanyId();
			if(is_array($myCompanyId))
			{
				return $siteDir.'/crm/configs/mycompany/';
			}
			elseif($myCompanyId > 0)
			{
				return $siteDir.'/crm/configs/mycompany/edit/'.$myCompanyId.'/';
			}
			else
			{
				return $siteDir.'/crm/company/details/0/?mycompany=y';
			}
		}
	}

	/**
	 * Get Primary Address (it used to be like this).
	 * If Primary Address is empty - get Delivery Address instead (as this is new by default address type).
	 *
	 * @return string
	 */
	public function getAddress()
	{
		$address = $this->getAddressFromRequisite($this->fields['REQUISITE'], 'PRIMARY_ADDRESS');
		if(empty($address))
		{
			$address = $this->getAddressFromRequisite($this->fields['REQUISITE'], 'DELIVERY_ADDRESS');
		}

		return $address;
	}

	/**
	 * @return string
	 */
	public function getPrimaryAddress()
	{
		return $this->getAddressFromRequisite($this->fields['REQUISITE'], 'PRIMARY_ADDRESS');
	}

	/**
	 * @return string
	 */
	public function getRegisteredAddress()
	{
		return $this->getAddressFromRequisite($this->fields['REQUISITE'], 'REGISTERED_ADDRESS');
	}

	/**
	 * @internal
	 * @param array $requisiteFieldDescription
	 * @param string $placeholder
	 * @return array
	 */
	protected function getAddressFromRequisite(array $requisiteFieldDescription, $placeholder)
	{
		$address = '';
		$requisites = $this->getValue('REQUISITE');
		if(!$requisites instanceof Requisite)
		{
			$requisites = DataProviderManager::getInstance()->getValueFromList($requisites);
			$requisites = DataProviderManager::getInstance()->createDataProvider($requisiteFieldDescription, $requisites, $this, 'REQUISITE');
		}
		if($requisites instanceof Requisite)
		{
			$data = DataProviderManager::getInstance()->getArray($requisites);
			if(
				isset($data[$placeholder])
				&& is_array($data[$placeholder])
			)
			{
				$address = $data[$placeholder];
			}
		}

		return $address;
	}

	/**
	 * @return array
	 */
	protected function getHiddenFields()
	{
		return array_merge(parent::getHiddenFields(), [
			'UTS_OBJECT',
			'CONTACT_BINDINGS',
		]);
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ObjectPropertyException
	 */
	public static function getNameFormat()
	{
		$formatId = PersonNameFormatter::getFormatID();
		if($formatId == PersonNameFormatter::Dflt)
		{
			return DataProviderManager::getInstance()->getCulture()->getNameFormat();
		}
		else
		{
			return PersonNameFormatter::getFormatByID($formatId);
		}
	}

	/**
	 * @return array
	 */
	public function getAnotherPhone()
	{
		return $this->getMultiFields('PHONE', 'OTHER');
	}

	/**
	 * @return array
	 */
	public function getAnotherEmail()
	{
		return $this->getMultiFields('EMAIL', 'OTHER');
	}

	/**
	 * @return array
	 */
	public function getHomeEmail()
	{
		return $this->getMultiFields('EMAIL', 'HOME');
	}

	/**
	 * @return array
	 */
	public function getWorkEmail()
	{
		return $this->getMultiFields('EMAIL', 'WORK');
	}

	/**
	 * @return array
	 */
	public function getMobilePhone()
	{
		return $this->getMultiFields('PHONE', 'MOBILE');
	}

	/**
	 * @return array
	 */
	public function getWorkPhone()
	{
		return $this->getMultiFields('PHONE', 'WORK');
	}

	/**
	 * @return array
	 */
	public function getHomePhone()
	{
		return $this->getMultiFields('PHONE', 'HOME');
	}

	/**
	 * @return string
	 */
	public function getLangPhrasesPath()
	{
		Loc::loadLanguageFile(__FILE__);
		return Path::getDirectory(Path::normalize(__FILE__)).'/../phrases';
	}

	/**
	 * @return bool
	 */
	protected function hasLeadField()
	{
		return false;
	}
}