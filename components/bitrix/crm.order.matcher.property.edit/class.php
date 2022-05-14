<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Sale\Internals\Input;
use Bitrix\Crm;

Loc::loadMessages(__FILE__);

class ConfigOrderPropertyEdit extends \CBitrixComponent
{
	use Crm\Component\EntityDetails\SaleProps\ComponentTrait;

	protected $action = null;
	protected $errors = [];

	protected $dbProperty = null;
	protected $property = null;
	protected $propertySettings = null;

	protected function hasErrors()
	{
		return !empty($this->errors);
	}

	protected function showErrors()
	{
		foreach ($this->errors as $error)
		{
			ShowError($error);
		}

		return true;
	}

	public function onPrepareComponentParams($params)
	{
		$params['PERSON_TYPE_ID'] = (int)$params['PERSON_TYPE_ID'];

		if (empty($params['PERSON_TYPE_ID']))
		{
			$this->errors[] = Loc::getMessage('CRM_INVALID_PERSON_TYPE_ID');
		}

		$params['PROPERTY_ID'] = (int)$params['PROPERTY_ID'];
		$params['RELOAD_HANDLER'] = 'orderPropertyConfig.reloadAction()';
		$params['IFRAME'] = isset($params['IFRAME']) && $params['IFRAME'];
		$params['LOAD_FROM_REQUEST'] = isset($params['LOAD_FROM_REQUEST']) && $params['LOAD_FROM_REQUEST'] === 'Y' ? 'Y' : 'N';

		return $params;
	}

	protected function checkModules()
	{
		if (!Loader::includeModule('crm'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED');
			return false;
		}

		if (!CAllCrmInvoice::installExternalEntities())
		{
			return false;
		}

		if (!CCrmQuote::LocalComponentCausedUpdater())
		{
			return false;
		}

		if (!Loader::includeModule('sale'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_SALE');
			return false;
		}

		return true;
	}

	protected function checkPermissions()
	{
		global $USER;

		$crmPerms = new CCrmPerms($USER->GetID());

		if (!$crmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
		{
			$this->errors[] = Loc::getMessage('CRM_PERMISSION_DENIED');
			return false;
		}

		$this->arResult['PERM_CAN_EDIT'] = $crmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

		return true;
	}

	protected function modifyDataDependedByType($property)
	{
		if (!empty($property))
		{
			switch ($property['TYPE'])
			{
				case 'ENUM':
					$variants = [];

					$result = \CSaleOrderPropsVariant::GetList(
						['SORT' => 'ASC'],
						['ORDER_PROPS_ID' => $this->arParams['PROPERTY_ID']]
					);
					while ($row = $result->Fetch())
					{
						$variants[] = $row;
					}

					$property['VARIANTS'] = $variants;
					break;
				case 'FILE':
					$property['DEFAULT_VALUE'] = Input\File::loadInfo($property['DEFAULT_VALUE']);
					break;
			}
		}

		return $property;
	}

	protected function parseFileIds(array $propertyFields)
	{
		if (!empty($propertyFields['FILE_IDS']))
		{
			if ($propertyFields['MULTIPLE'] === 'Y')
			{
				foreach ($propertyFields['FILE_IDS'] as $name => $fileIds)
				{
					foreach ($fileIds as $index => $fileId)
					{
						if (!isset($propertyFields[$name][$index]))
						{
							$propertyFields[$name] = [$index => Input\File::loadInfo($propertyFields[$name]['ID'])];
						}
					}
				}
			}
			else
			{
				foreach ($propertyFields['FILE_IDS'] as $name => $fileIds)
				{
					if (isset($propertyFields[$name]['name']))
					{
						$propertyFields[$name]['ID'] = '';
					}
					elseif (!empty($fileIds) && is_array($fileIds))
					{
						$propertyFields[$name] = Input\File::loadInfo(reset($fileIds)['ID']);
					}
				}
			}
		}

		unset($propertyFields['FILE_IDS']);

		return $propertyFields;
	}

	protected function checkMultipleField($property)
	{
		$allowedMultipleTypes = ['ENUM', 'FILE'];

		return in_array($property['TYPE'], $allowedMultipleTypes);
	}

	protected function checkPreviousTypeCompatibility($property)
	{
		if (
			$this->request->get('isAjax') === 'Y'
			&& $this->request->get('TYPE') !== $this->request->get('PREVIOUS-TYPE')
		)
		{
			unset($property['DEFAULT_VALUE'], $property['SETTINGS']);
			$property = array_diff_key($property, $this->getInputSettings($property));

			if (!empty($property['ID']))
			{
				$this->initializeDbProperty($property['ID']);

				if (!empty($this->dbProperty) && $property['TYPE'] === $this->dbProperty['TYPE'])
				{
					$property['MULTIPLE'] = $this->dbProperty['MULTIPLE'];
					$property['DEFAULT_VALUE'] = $this->dbProperty['DEFAULT_VALUE'];
					$property += $this->dbProperty['SETTINGS'];
				}
			}
		}

		return $property;
	}

	protected function preparePropertyFromRequest(array $propertyFields)
	{
		if (!isset($propertyFields['MATCHED']) || $propertyFields['MATCHED'] !== 'Y')
		{
			$propertyFields['CODE'] = \Bitrix\Crm\Order\Matcher\FieldSynchronizer::getOrderPropertyName(
				$propertyFields['CODE'], $propertyFields['ID']
			);
		}

		$propertyFields = $this->parseFileIds($propertyFields);

		// ITEMS - from order form
		// VARIANTS - save
		$variantsFromPost = !empty($propertyFields['ITEMS']) ? $propertyFields['ITEMS'] : $propertyFields['VARIANTS'];

		if (!empty($variantsFromPost))
		{
			$variants = [];
			$sortByColumn = null;

			foreach ($variantsFromPost as $key => $variant)
			{
				if ($sortByColumn === null)
				{
					$sortByColumn = isset($variant['SORT']);
				}

				if (!empty($variant['ID']) || !empty($variant['NAME']))
				{
					if (empty($variant['VALUE']))
					{
						$variant['VALUE'] = uniqid().'_'.$key;
					}

					$variants[] = $variant;
				}
			}

			if ($sortByColumn)
			{
				\Bitrix\Main\Type\Collection::sortByColumn($variants, ['SORT' => SORT_ASC]);
			}

			$propertyFields['VARIANTS'] = $variants;
		}

		$propertyFields = $this->checkPreviousTypeCompatibility($propertyFields);
		$propertyFields['MULTIPLE'] = $this->checkMultipleField($propertyFields) ? $propertyFields['MULTIPLE'] : 'N';

		return $propertyFields;
	}

	protected function initializePropertyFromRequest(array $propertyFields)
	{
		$this->property = $this->preparePropertyFromRequest($propertyFields);
	}

	protected function initializeProperty()
	{
		if ($this->property === null)
		{
			$this->dbProperty = $this->property = $this->loadProperty($this->arParams['PROPERTY_ID']);

			if (empty($this->property))
			{
				if (!empty($this->arParams['PROPERTY_ID']))
				{
					$this->errors[] = Loc::getMessage('CRM_INVALID_PROPERTY_ID');
				}

				$this->property = $this->prepareCreationInfo();
			}
		}
	}

	protected function initializeDbProperty($propertyId)
	{
		if ($this->dbProperty === null)
		{
			$this->dbProperty = $this->loadProperty($propertyId);
		}
	}

	protected function loadProperty($propertyId)
	{
		if (empty($propertyId))
		{
			return [];
		}

		$dbRes = \Bitrix\Crm\Order\Property::getList([
			'filter' => ['=ID' => $propertyId]
		]);

		$property = $dbRes->fetch();
		if (!empty($property))
		{
			$property += $property['SETTINGS'];
			$property = $this->modifyDataDependedByType($property);
		}

		return $property;
	}

	protected function prepareCreationInfo()
	{
		$propertyTypes = \Bitrix\Sale\Internals\Input\Manager::getTypes();
		$propertyType = $this->request->get('TYPE');
		$propertyType = isset($propertyTypes[$propertyType]) ? $propertyType : 'STRING';

		return [
			'PERSON_TYPE_ID' => $this->arParams['PERSON_TYPE_ID'],
			'TYPE' => $propertyType,
		];
	}

	protected function getPropertyGroupOptions()
	{
		$groupOptions = [];

		$result = \CSaleOrderPropsGroup::GetList(['NAME' => 'ASC'], ['PERSON_TYPE_ID' => $this->arParams['PERSON_TYPE_ID']]);
		while ($row = $result->Fetch())
		{
			$groupOptions[$row['ID']] = $row['NAME'];
		}

		return $groupOptions;
	}

	protected function getPersonType($personTypeId)
	{
		$personTypeList = \Bitrix\Sale\PersonType::load($this->getSiteId(), $personTypeId);

		return isset($personTypeList[$personTypeId]) ? $personTypeList[$personTypeId] : null;
	}

	protected function getCommonSettings()
	{
		$personType = $this->getPersonType($this->arParams['PERSON_TYPE_ID']);
		$groupOptions = $this->getPropertyGroupOptions();

		$commonSettings = [
			'PERSON_TYPE_ID' => [
				'TYPE' => 'NUMBER',
				'LABEL' => Loc::getMessage('SALE_PERS_TYPE'),
				'MIN' => 0,
				'STEP' => 1,
				'HIDDEN' => 'Y',
				'REQUIRED' => 'Y',
				'RLABEL' => $personType['NAME']
			],
			'PROPS_GROUP_ID' => [
				'TYPE' => 'ENUM',
				'LABEL' => Loc::getMessage('F_PROPS_GROUP_ID'),
				'OPTIONS' => $groupOptions
			],
			'NAME' => [
				'TYPE' => 'STRING',
				'LABEL' => Loc::getMessage('F_NAME'),
				'MAXLENGTH' => 255,
				'REQUIRED' => 'Y'
			],
			'CODE' => [
				'TYPE' => 'STRING',
				'LABEL' => Loc::getMessage('F_CODE'),
				'MAXLENGTH' => 50
			],
			'ACTIVE' => [
				'TYPE' => 'Y/N' ,
				'LABEL' => Loc::getMessage('F_ACTIVE'),
				'VALUE' => 'Y'
			],
			'UTIL' => [
				'TYPE' => 'Y/N',
				'LABEL' => Loc::getMessage('F_UTIL')
			],
			'USER_PROPS' => [
				'TYPE' => 'Y/N',
				'LABEL' => Loc::getMessage('F_USER_PROPS')
			],
			'IS_FILTERED' => [
				'TYPE' => 'Y/N',
				'LABEL' => Loc::getMessage('F_IS_FILTERED'),
				'DESCRIPTION' => Loc::getMessage('MULTIPLE_DESCRIPTION')
			],
			'SORT' => [
				'TYPE' => 'NUMBER',
				'LABEL' => Loc::getMessage('F_SORT'),
				'MIN' => 0,
				'STEP' => 1,
				'VALUE' => 100
			],
			'DESCRIPTION' => [
				'TYPE' => 'STRING',
				'LABEL' => Loc::getMessage('F_DESCRIPTION'),
				'MULTILINE' => 'Y',
				'ROWS' => 3,
				'COLS' => 40
			],
			'XML_ID' => [
				'TYPE' => 'STRING',
				'LABEL' => Loc::getMessage('F_XML_ID'),
				'VALUE' => \Bitrix\Sale\Internals\OrderPropsTable::generateXmlId()
			],
		];

		if (!empty($this->property['ID']))
		{
			$commonSettings = array_merge(
				[
					'ID' => [
						'TYPE' => 'NUMBER',
						'LABEL' => 'ID',
						'MIN' => 0,
						'STEP' => 1,
						'HIDDEN' => 'Y',
						'RLABEL' => $this->property['ID']
					]
				],
				$commonSettings
			);
		}

		$commonSettings += Input\Manager::getCommonSettings($this->property, $this->arParams['RELOAD_HANDLER']);

		if (!empty($commonSettings['TYPE']['OPTIONS']))
		{
			$types = [];
			$commonTypes = Input\Manager::getTypes();

			foreach ($commonSettings['TYPE']['OPTIONS'] as $key => $option)
			{
				if (
					isset($commonTypes[$key])
					&& mb_strpos($commonTypes[$key]['CLASS'], 'Bitrix\Sale\Internals\Input') !== false
				)
				{
					$types[$key] = $commonTypes[$key]['NAME'];
				}
			}

			$commonSettings['TYPE']['OPTIONS'] = $types;
		}

		if (isset($commonSettings['TYPE']['OPTIONS']['ADDRESS'])
			&& (
				!is_set($this->property['ID'])
				|| $this->property['TYPE'] !== 'ADDRESS'
			)
		)
		{
			unset($commonSettings['TYPE']['OPTIONS']['ADDRESS']);
		}

		if (!$this->checkMultipleField($this->property))
		{
			$commonSettings['MULTIPLE']['DISABLED'] = 'Y';
			$commonSettings['MULTIPLE']['NO_DISPLAY'] = 'Y';
			unset($commonSettings['IS_FILTERED']['DESCRIPTION']);
		}

		$commonSettings['MULTIPLE']['DESCRIPTION'] = Loc::getMessage('MULTIPLE_DESCRIPTION');
		unset($commonSettings['VALUE']);

		$commonSettings['DEFAULT_VALUE'] = array(
				'REQUIRED' => 'N',
				'DESCRIPTION' => null,
				'VALUE' => $this->property['DEFAULT_VALUE'],
				'LABEL' => Loc::getMessage('F_DEFAULT_VALUE'),
			) + $this->property;

		if ($this->property['TYPE'] === 'ENUM')
		{
			$defaultOptions = $this->property['MULTIPLE'] === 'Y'
				? []
				: ['' => Loc::getMessage('NO_DEFAULT_VALUE')];

			if (!empty($this->property['VARIANTS']))
			{
				foreach ($this->property['VARIANTS'] as $row)
				{
					$defaultOptions[$row['VALUE']] = $row['NAME'];
				}
			}

			$commonSettings['DEFAULT_VALUE']['OPTIONS'] = $defaultOptions;
		}
		elseif ($this->property['TYPE'] === 'LOCATION')
		{
			if ($this->property['IS_LOCATION'] === 'Y' || $this->property['IS_LOCATION4TAX'] === 'Y')
			{
				unset($commonSettings['MULTIPLE']);
			}
		}

		return $commonSettings;
	}

	protected function getInputSettings($property)
	{
		return Input\Manager::getSettings($property, $this->arParams['RELOAD_HANDLER']);
	}

	protected function getStringSettings()
	{
		return array(
			'IS_PROFILE_NAME' => [
				'TYPE' => 'Y/N',
				'LABEL' => Loc::getMessage('F_IS_PROFILE_NAME'),
				'DESCRIPTION' => Loc::getMessage('F_IS_PROFILE_NAME_DESCR')
			],
			'IS_PAYER' => [
				'TYPE' => 'Y/N',
				'LABEL' => Loc::getMessage('F_IS_PAYER'),
				'DESCRIPTION' => Loc::getMessage('F_IS_PAYER_DESCR')
			],
			'IS_EMAIL' => [
				'TYPE' => 'Y/N',
				'LABEL' => Loc::getMessage('F_IS_EMAIL'),
				'DESCRIPTION' => Loc::getMessage('F_IS_EMAIL_DESCR')
			],
			'IS_PHONE' => [
				'TYPE' => 'Y/N',
				'LABEL' => Loc::getMessage('F_IS_PHONE'),
				'DESCRIPTION' => Loc::getMessage('F_IS_PHONE_DESCR')
			],
			'IS_ZIP' => [
				'TYPE' => 'Y/N',
				'LABEL' => Loc::getMessage('F_IS_ZIP'),
				'DESCRIPTION' => Loc::getMessage('F_IS_ZIP_DESCR')
			],
			'IS_ADDRESS' => [
				'TYPE' => 'Y/N',
				'LABEL' => Loc::getMessage('F_IS_ADDRESS'),
				'DESCRIPTION' => Loc::getMessage('F_IS_ADDRESS_DESCR')
			],
		);
	}

	protected function getLocationSettings()
	{
		$locationOptions = ['' => Loc::getMessage('NULL_ANOTHER_LOCATION')];

		$result = \CSaleOrderProps::GetList([],
			[
				'PERSON_TYPE_ID' => $this->arParams['PERSON_TYPE_ID'],
				'TYPE' => 'STRING',
				'ACTIVE' => 'Y'
			],
			false, false, ['ID', 'NAME']
		);
		while ($row = $result->Fetch())
		{
			$locationOptions[$row['ID']] = $row['NAME'];
		}

		return [
			'IS_LOCATION' => [
				'TYPE' => 'Y/N' ,
				'LABEL' => Loc::getMessage('F_IS_LOCATION'),
				'DESCRIPTION' => Loc::getMessage('F_IS_LOCATION_DESCR'),
				'ONCLICK' => $this->arParams['RELOAD_HANDLER']
			],
			'INPUT_FIELD_LOCATION' => [
				'TYPE' => 'ENUM',
				'LABEL' => Loc::getMessage('F_ANOTHER_LOCATION'),
				'DESCRIPTION' => Loc::getMessage('F_INPUT_FIELD_DESCR'),
				'OPTIONS' => $locationOptions,
				'VALUE' => 0
			],
			'IS_LOCATION4TAX' => [
				'TYPE' => 'Y/N',
				'LABEL' => Loc::getMessage('F_IS_LOCATION4TAX'),
				'DESCRIPTION' => Loc::getMessage('F_IS_LOCATION4TAX_DESCR'),
				'ONCLICK' => $this->arParams['RELOAD_HANDLER']
			],
		];
	}

	protected function modifyInputSettingsByType(&$propertySettings)
	{
		if ($this->property['MULTIPLE'] === 'Y' || $this->property['TYPE'] === 'DATE')
		{
			$propertySettings['IS_FILTERED']['DISABLED'] = 'Y';
		}

		if ($this->property['TYPE'] === 'STRING')
		{
			$propertySettings += $this->getStringSettings();
		}
		elseif ($this->property['TYPE'] === 'LOCATION')
		{
			$propertySettings += $this->getLocationSettings();

			if ($this->property['IS_LOCATION'] !== 'Y' || $this->property['MULTIPLE'] === 'Y')
			{
				unset($propertySettings['INPUT_FIELD_LOCATION']);
			}

			if ($this->property['IS_LOCATION'] === 'Y')
			{
				$this->property['REQUIRED'] = 'Y';
				$propertySettings['REQUIRED']['DISABLED'] = 'Y';
			}
		}
	}

	protected function getDisabledFields($isNew = true)
	{
		$fields = ['ID', 'ACTIVE', 'UTIL', 'SORT'];

		if (!$isNew)
		{
			$fields = array_merge($fields, ['PERSON_TYPE_ID', 'CODE', 'TYPE']);
		}

		return $fields;
	}

	protected function getNotVisibleFields($isNew = true)
	{
		return ['ID', 'CODE', 'ACTIVE', 'UTIL', 'SORT'];
	}

	protected function isCreationMode()
	{
		return empty($this->property['ID']) && $this->arParams['LOAD_FROM_REQUEST'] !== 'Y';
	}

	protected function modifyInputSettingsByEditType(&$propertySettings)
	{
		$creationMode = $this->isCreationMode();
		$disabledFields = array_fill_keys($this->getDisabledFields($creationMode), true);
		$notVisibleFields = array_fill_keys($this->getNotVisibleFields($creationMode), true);

		if (!$creationMode)
		{
			$propertySettings['TYPE'] = array_merge($propertySettings['TYPE'], [
				'TYPE' => 'STRING',
				'HIDDEN' => 'Y',
				'REQUIRED' => 'N',
				'RLABEL' => $propertySettings['TYPE']['OPTIONS'][$this->property['TYPE']],
			]);
		}

		foreach ($propertySettings as $propertyName => &$propertySetting)
		{
			if (isset($disabledFields[$propertyName]))
			{
				$propertySetting['DISABLED'] = 'Y';
				$propertySetting['ADDITIONAL_HIDDEN'] = 'Y';
			}

			if (isset($notVisibleFields[$propertyName]))
			{
				$propertySetting['NO_DISPLAY'] = 'Y';
			}
		}
	}

	protected function initializePropertySettings()
	{
		if ($this->propertySettings === null)
		{
			$this->propertySettings = $this->getCommonSettings();
			$this->propertySettings += $this->getInputSettings($this->property);

			$this->modifyInputSettingsByType($this->propertySettings);
			$this->modifyInputSettingsByEditType($this->propertySettings);
		}
	}

	protected function placeTitle()
	{
		global $APPLICATION;

		if (!empty($this->property['ID']))
		{
			$propertyName = (string)($this->property['NAME'] ?? '');
			$propertyName = \Bitrix\Main\Text\HtmlFilter::encode($propertyName);
			$title = Loc::getMessage('CRM_CONFIG_ORDER_TITLE_EDIT').' '."\"{$propertyName}\"";
		}
		else
		{
			$title = Loc::getMessage('CRM_CONFIG_ORDER_TITLE_CREATE');
		}

		$APPLICATION->SetTitle($title);
	}

	protected function getResult()
	{
		$result = [];

		$result['PROPERTY'] = $this->property;
		$result['PROPERTY_SETTINGS'] = $this->propertySettings;
		$result['MATCH_SETTINGS'] = $this->getMatchSettings();
		$result['MATCH_CODE'] = $this->getMatchCode();

		$result['CAN_EDIT_VARIANTS'] = $this->arParams['LOAD_FROM_REQUEST'] !== 'Y' && empty($result['MATCH_SETTINGS']);
		$result['VARIANT_SETTINGS'] = $this->getVariantSettings($result['CAN_EDIT_VARIANTS']);

		$result['ERRORS'] = $this->errors;

		return $result;
	}

	protected function initialLoadFromRequestAction()
	{
		$this->arParams['LOAD_FROM_REQUEST'] = 'Y';
		$this->initializePropertyFromRequest($this->request->toArray());
		$this->initialLoadAction();
	}

	protected function initialLoadAction()
	{
		$this->initializeProperty();
		$this->initializePropertySettings();

		$this->arResult = $this->getResult();

		$this->placeTitle();
		$this->includeComponentTemplate();
	}

	protected function reloadFormAjaxAction()
	{
		$GLOBALS['APPLICATION']->RestartBuffer();

		$this->initializePropertyFromRequest($this->request->toArray());

		$result = [];

		if ($this->checkPostRequest())
		{
			$result['html'] = $this->getInitialLoadRedraw();
			$result['property'] = $this->property;
		}

		$this->sendJsonAnswer($result);
	}

	protected function validateProperty()
	{
		$this->initializePropertySettings();
		$this->validateFields();

		if ($this->property['TYPE'] === 'ENUM')
		{
			$this->validateVariants();
		}

		return !$this->hasErrors();
	}

	protected function validateFields()
	{
		foreach ($this->propertySettings as $name => $input)
		{
			if ($error = Input\Manager::getError($input, $this->property[$name]))
			{
				if ($input['MULTIPLE'] && $input['MULTIPLE'] === 'Y')
				{
					$errorString = '';

					foreach ($error as $k => $v)
					{
						$errorString .= ' '.(++$k).': '.implode(', ', $v).';';
					}

					$this->errors[] = $input['LABEL'].$errorString;
				}
				else
				{
					$this->errors[] = $input['LABEL'].': '.implode(', ', $error);
				}
			}
		}
	}

	protected function getVariantSettings($editable = true)
	{
		return [
			'VALUE' => [
				'TYPE' => 'STRING', 'LABEL' => Loc::getMessage('SALE_VARIANTS_CODE'), 'SIZE' => '5', 'MAXLENGTH' => 255,
				'REQUIRED' => 'Y', 'HIDDEN' => 'Y', 'DISABLED' => $editable ? 'N' : 'Y', 'ADDITIONAL_HIDDEN' => $editable ? 'N' : 'Y'
			],
			'NAME' => [
				'TYPE' => 'STRING', 'LABEL' => Loc::getMessage('SALE_VARIANTS_NAME'), 'SIZE' => '20',
				'MAXLENGTH' => 255, 'REQUIRED' => 'Y', 'DISABLED' => $editable ? 'N' : 'Y', 'ADDITIONAL_HIDDEN' => $editable ? 'N' : 'Y'
			],
			'SORT' => [
				'TYPE' => 'NUMBER', 'LABEL' => Loc::getMessage('SALE_VARIANTS_SORT'), 'MIN' => 0, 'STEP' => 1,
				'VALUE' => 100, 'HIDDEN' => $editable ? 'N' : 'Y'
			],
			'DESCRIPTION' => [
				'TYPE' => 'STRING', 'LABEL' => Loc::getMessage('SALE_VARIANTS_DESCR'), 'SIZE' => '30',
				'MAXLENGTH' => 255, 'DISABLED' => $editable ? 'N' : 'Y', 'ADDITIONAL_HIDDEN' => $editable ? 'N' : 'Y'
			],
			'ID' => [
				'TYPE' => 'NUMBER', 'MIN' => 0, 'STEP' => 1, 'HIDDEN' => 'Y', 'DISABLED' => $editable ? 'N' : 'Y',
				'ADDITIONAL_HIDDEN' => $editable ? 'N' : 'Y'
			],
		];
	}

	protected function getMatchSettings()
	{
		if (!empty($this->property['ID']))
		{
			$entityName = '';
			$entityFieldName = [];

			$match = \Bitrix\Crm\Order\Matcher\Internals\OrderPropsMatchTable::getByPropertyId($this->property['ID']);

			if (!empty($match))
			{
				$entity = '';
				$field = $match['CRM_FIELD_CODE'];

				if ((int)$match['CRM_ENTITY_TYPE'] === CCrmOwnerType::Contact)
				{
					$entity = \CCrmOwnerType::ContactName;
					$entityName = Loc::getMessage('MATCH_ENTITY_CONTACT');
				}
				elseif ((int)$match['CRM_ENTITY_TYPE'] === CCrmOwnerType::Company)
				{
					$entity = \CCrmOwnerType::CompanyName;
					$entityName = Loc::getMessage('MATCH_ENTITY_COMPANY');
				}

				if ((int)$match['CRM_FIELD_TYPE'] === \Bitrix\Crm\Order\Matcher\BaseEntityMatcher::MULTI_FIELD_TYPE)
				{
					$fieldInfo = \CCrmFieldMulti::ParseComplexName($field, true);
					$types = \CCrmFieldMulti::GetEntityTypes();

					if ($types[$fieldInfo['TYPE']][$fieldInfo['VALUE_TYPE']])
					{
						$entityFieldName[] = $types[$fieldInfo['TYPE']][$fieldInfo['VALUE_TYPE']]['FULL'];
					}
				}

				if ((int)$match['CRM_FIELD_TYPE'] === \Bitrix\Crm\Order\Matcher\BaseEntityMatcher::REQUISITE_FIELD_TYPE)
				{
					$entity = \CCrmOwnerType::RequisiteName;
					$entityFieldName[] = Loc::getMessage('MATCH_ENTITY_REQUISITE');
				}

				if ((int)$match['CRM_FIELD_TYPE'] === \Bitrix\Crm\Order\Matcher\BaseEntityMatcher::BANK_DETAIL_FIELD_TYPE)
				{
					$entity = 'BANK_DETAIL';
					$entityFieldName[] = Loc::getMessage('MATCH_ENTITY_BANK_DETAIL');
				}

				if ($field === 'RQ_ADDR')
				{
					$entity = 'ADDRESS';
					$field = $match['SETTINGS']['RQ_ADDR_CODE'];

					$addressTypeDescriptions = \Bitrix\Crm\EntityAddressType::getAllDescriptions();

					if (isset($addressTypeDescriptions[$match['SETTINGS']['RQ_ADDR_TYPE']]))
					{
						$entityFieldName[] = $addressTypeDescriptions[$match['SETTINGS']['RQ_ADDR_TYPE']];
					}
				}

				$caption = \Bitrix\Crm\Order\Matcher\FieldSynchronizer::getFieldCaption($entity, $field);

				if (!empty($caption))
				{
					$entityFieldName[] = $caption;
				}
			}
		}

		$matchSettings = [];

		if (!empty($entityName))
		{
			$matchSettings['ENTITY'] = [
				'TYPE' => 'STRING', 'LABEL' => Loc::getMessage('MATCH_ENTITY'), 'HIDDEN' => 'Y', 'RLABEL' => $entityName
			];
		}

		if (!empty($entityFieldName))
		{
			$matchSettings['ENTITY_FIELD'] = [
				'TYPE' => 'STRING', 'LABEL' => Loc::getMessage('MATCH_ENTITY_FIELD'), 'HIDDEN' => 'Y', 'RLABEL' => implode(' / ', $entityFieldName)
			];
		}

		return $matchSettings;
	}

	protected function getMatchCode()
	{
		$matchCode = '';

		if (!empty($this->property['ID']))
		{
			$match = \Bitrix\Crm\Order\Matcher\Internals\OrderPropsMatchTable::getByPropertyId($this->property['ID']);

			if (!empty($match))
			{
				$matchCode = \Bitrix\Crm\Order\Matcher\FieldSynchronizer::getFieldFullCode($match);
			}
		}

		return $matchCode;
	}

	protected function validateVariants()
	{
		if (!empty($this->property))
		{
			$index = 0;
			$variantSettings = $this->getVariantSettings();

			foreach ($this->property['VARIANTS'] as $row)
			{
				++$index;

				if (isset($row['DELETE']) && $row['DELETE'] === 'Y')
				{
					unset($this->propertySettings['DEFAULT_VALUE']['OPTIONS'][$row['VALUE']]);
				}
				else
				{
					$hasError = false;

					foreach ($variantSettings as $name => $input)
					{
						if ($error = Input\Manager::getError($input, $row[$name]))
						{
							$this->errors[] = Loc::getMessage('INPUT_ENUM')." $index: ".$input['LABEL'].': '.implode(', ', $error);
							$hasError = true;
						}
					}

					if ($hasError)
					{
						unset($this->propertySettings['DEFAULT_VALUE']['OPTIONS'][$row['VALUE']]);
					}
				}
			}
		}
	}

	protected function saveFiles(&$property)
	{
		$savedFiles = array();

		$files = Input\File::asMultiple($property['DEFAULT_VALUE']);
		foreach ($files as $i => $file)
		{
			if (Input\File::isDeletedSingle($file))
			{
				unset($files[$i]);
			}
			else
			{
				if (
					Input\File::isUploadedSingle($file)
					&& ($fileId = \CFile::SaveFile(array('MODULE_ID' => 'sale') + $file, 'sale/order/properties/default'))
					&& is_numeric($fileId)
				)
				{
					$file = $fileId;
					$savedFiles[] = $fileId;
				}

				$files[$i] = Input\File::loadInfoSingle($file);
			}
		}

		$property['DEFAULT_VALUE'] = $files;

		return $savedFiles;
	}

	protected function updateProperty($propertiesToSave)
	{
		$update = \Bitrix\Sale\Internals\OrderPropsTable::update(
			$this->property['ID'],
			array_diff_key($propertiesToSave, array('ID' => 1))
		);
		if ($update->isSuccess())
		{
			$propertyCode = $propertiesToSave['CODE'] ?: false;

			$result = \CSaleOrderPropsValue::GetList([], [
				'ORDER_PROPS_ID' => $this->property['ID'],
				'!CODE' => $propertyCode,
			]);
			while ($row = $result->Fetch())
			{
				\CSaleOrderPropsValue::Update($row['ID'], ['CODE' => $propertyCode]);
			}
		}
		else
		{
			foreach ($update->getErrorMessages() as $errorMessage)
			{
				$this->errors[] = $errorMessage;
			}
		}
	}

	protected function addProperty($propertiesToSave)
	{
		$propertyId = null;

		$addResult = \Bitrix\Sale\Internals\OrderPropsTable::add($propertiesToSave);
		if ($addResult->isSuccess())
		{
			$propertyId = $addResult->getId();
		}
		else
		{
			foreach ($addResult->getErrorMessages() as $errorMessage)
			{
				$this->errors[] = $errorMessage;
			}
		}

		return $propertyId;
	}

	protected function cleanUpFiles($savedFiles)
	{
		$filesToDelete = [];

		if ($this->hasErrors())
		{
			if (!empty($savedFiles))
			{
				$filesToDelete = $savedFiles;
			}
		}
		else
		{
			if (!empty($this->dbProperty) && $this->dbProperty['TYPE'] === 'FILE')
			{
				$filesToDelete = Input\File::asMultiple(Input\File::getValue(
					$this->dbProperty, $this->dbProperty['DEFAULT_VALUE']
				));

				if (!empty($this->property['DEFAULT_VALUE']))
				{
					$filesToDelete = array_diff(
						$filesToDelete,
						Input\File::asMultiple(Input\File::getValue($this->property, $this->property['DEFAULT_VALUE']))
					);
				}
			}
		}

		foreach ($filesToDelete as $fileId)
		{
			if (is_numeric($fileId))
			{
				\CFile::Delete($fileId);
			}
		}
	}

	protected function saveVariants()
	{
		if ($this->property['TYPE'] === 'ENUM')
		{
			$index = 0;
			$variantSettings = $this->getVariantSettings();

			foreach ($this->property['VARIANTS'] as $key => $row)
			{
				if (isset($row['DELETE']) && $row['DELETE'] === 'Y')
				{
					if ($row['ID'])
					{
						\CSaleOrderPropsVariant::Delete($row['ID']);
					}

					unset($this->property['VARIANTS'][$key]);
				}
				else
				{
					++$index;
					$variantId = $row['ID'];
					$row = array_intersect_key($row, $variantSettings);

					if ($variantId)
					{
						unset($row['ID']);
						if (!\CSaleOrderPropsVariant::Update($variantId, $row))
						{
							$this->errors[] = Loc::getMessage('ERROR_EDIT_VARIANT')." $index";
						}
					}
					else
					{
						$row['ORDER_PROPS_ID'] = $this->property['ID'];

						if ($variantId = \CSaleOrderPropsVariant::Add($row))
						{
							$variants[$key]['ID'] = $variantId;
						}
						else
						{
							$this->errors[] = Loc::getMessage('ERROR_ADD_VARIANT')." $index";
						}
					}
				}
			}
		}
		// cleanup variants
		elseif (!empty($this->dbProperty) && $this->dbProperty['TYPE'] === 'ENUM')
		{
			\CSaleOrderPropsVariant::DeleteAll($this->dbProperty['ID']);
		}
	}

	protected function saveProperty()
	{
		if ($this->property['TYPE'] === 'FILE')
		{
			$savedFiles = $this->saveFiles($this->property);
		}
		else
		{
			$savedFiles = [];
		}

		$propertiesToSave = [];

		foreach ($this->propertySettings as $name => $input)
		{
			$inputValue = Input\Manager::getValue($input, $this->property[$name]);

			if ($name === 'DEFAULT_VALUE' || $inputValue !== null)
			{
				$propertiesToSave[$name] = $inputValue;
			}
		}

		$inputSettings = $this->getInputSettings($this->property);
		$propertiesToSave['SETTINGS'] = array_intersect_key($propertiesToSave, $inputSettings);
		$propertiesToSave = array_diff_key($propertiesToSave, $propertiesToSave['SETTINGS']);

		if (!empty($this->property['ID']))
		{
			$this->initializeDbProperty($this->property['ID']);
		}

		if (!empty($this->property['ID']))
		{
			$this->updateProperty($propertiesToSave);
		}
		else
		{
			$propertiesToSave["ENTITY_REGISTRY_TYPE"] = \Bitrix\Sale\Registry::REGISTRY_TYPE_ORDER;
			$this->property['ID'] = $this->addProperty($propertiesToSave);
		}

		$this->cleanUpFiles($savedFiles);

		if (!$this->hasErrors())
		{
			$this->saveVariants();
		}
	}

	protected function getInitialLoadRedraw()
	{
		ob_start();

		$this->initialLoadAction();

		$content = ob_get_contents();
		ob_end_clean();

		list(, $html) = explode('<!-- form-reload-container -->', $content);

		return $html;
	}

	protected function saveFormAjaxAction()
	{
		$this->initializePropertyFromRequest($this->request->toArray());
		$this->initializePropertySettings();

		$result = [];
		$isCreationMode = $this->isCreationMode();

		if ($this->validateProperty())
		{
			$this->saveProperty();
		}

		$result['property'] = $this->property;

		if ($propertyRows = $this->getPropertyRowsCount($result['property']))
		{
			$result['property']['ROWS'] = (string)$propertyRows;
		}
		
		if ($this->hasErrors() || !$isCreationMode)
		{
			$result['html'] = $this->getInitialLoadRedraw();
		}
		elseif (!$this->arParams['IFRAME'])
		{
			$result['redirect'] = str_replace(
				['#person_type_id#', '#property_id#'],
				[$this->property['PERSON_TYPE_ID'], $this->property['ID']],
				$this->arParams['PATH_TO_ORDER_PROPERTY_EDIT']
			);
		}

		$this->sendJsonAnswer($result);
	}

	protected function checkPostRequest()
	{
		if (!check_bitrix_sessid() || !$this->request->isPost())
		{
			$this->errors[] = 'Security error';

			return false;
		}

		return true;
	}

	protected function sendJsonAnswer($result)
	{
		global $APPLICATION;

		$APPLICATION->RestartBuffer();
		header('Content-Type: application/json');

		echo \Bitrix\Main\Web\Json::encode(
			$result + [
				'error' => $this->hasErrors(),
				'errorText' => implode('<br>', $this->errors)
			]
		);

		\CMain::FinalActions();
		die();
	}

	protected function prepareAction()
	{
		$action = (string)$this->request->get('action');

		if (empty($action))
		{
			$action = 'initialLoad';
		}

		return $action;
	}

	protected function doAction($action)
	{
		$funcName = $action.'Action';

		if (is_callable([$this, $funcName]))
		{
			$this->{$funcName}();
		}
	}

	public function executeComponent()
	{
		if (!$this->checkModules() || !$this->checkPermissions())
		{
			$this->showErrors();
			return;
		}

		$this->action = $this->prepareAction();
		$this->doAction($this->action);
	}
}