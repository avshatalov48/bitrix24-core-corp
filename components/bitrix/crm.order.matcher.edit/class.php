<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity;
use Bitrix\Crm\Order\Matcher\FieldSynchronizer;
use Bitrix\Crm\Order\Matcher\ResponsibleQueue;

Loc::loadMessages(__FILE__);

class CCrmConfigOrderProps extends \CBitrixComponent
{
	const ENUM_ENTITY_SCHEME_CONTACT = 2;

	protected $action = null;

	protected $personTypes = null;

	protected $errors = [];

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
	}

	public function onPrepareComponentParams($params)
	{
		$personTypes = $this->getPersonTypes();

		if (!isset($personTypes[$params['PERSON_TYPE_ID']]))
		{
			if (!empty($personTypes))
			{
				$params['PERSON_TYPE_ID'] = reset($personTypes)['ID'];
			}
			else
			{
				$params['PERSON_TYPE_ID'] = 0;
			}
		}

		$params['PERSON_TYPE_ID'] = (int)$params['PERSON_TYPE_ID'];

		$params['IFRAME'] = isset($params['IFRAME']) && $params['IFRAME'];
		$params['NAME_TEMPLATE'] = empty($params['NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(array('#NOBR#','#/NOBR#'), array('',''), $params['NAME_TEMPLATE']);

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

	protected function checkPersonType()
	{
		if (empty($this->arParams['PERSON_TYPE_ID']))
		{
			$this->errors[] = Loc::getMessage('CRM_EMPTY_PERSON_TYPE_LIST');
			return false;
		}

		return true;
	}

	protected function getPersonTypes()
	{
		if ($this->personTypes === null)
		{
			$personTypes = \Bitrix\Sale\PersonType::getList()->fetchAll();
			if ($personTypes)
			{
				foreach ($personTypes as $personType)
				{
					$personType['NAME'] .= " ({$personType['ID']}) [{$personType['LID']}]";
					$this->personTypes[$personType['ID']] = $personType;
				}
			}
		}

		return $this->personTypes;
	}

	protected function getExistingFormFields($personTypeId, bool $withUtil = false)
	{
		$formFields = [];
		$cnt = 0;

		if (!empty($personTypeId))
		{
			$filter = [
				'=PERSON_TYPE_ID' => $personTypeId,
				'=ACTIVE' => 'Y',
			];
			if (!$withUtil)
			{
				$filter['=UTIL'] = 'N';
			}

			$filter[] = [
				'LOGIC' => 'OR',
				'!PROP_RELATION.ENTITY_TYPE' => 'L',
				'=PROP_RELATION.ENTITY_TYPE' => null,
			];

			$personTypeProperties = \Bitrix\Crm\Order\Property::getList([
				'filter' => $filter,
				'runtime' => [
					new Entity\ReferenceField(
						'PROP_RELATION',
						'\Bitrix\Sale\Internals\OrderPropsRelationTable',
						[
							'=this.ID' => 'ref.PROPERTY_ID',
						]
					)
				],
				'order' => ['SORT' => 'ASC'],
			])->fetchAll();
			$personTypeProperties = array_column($personTypeProperties, null, 'ID');

			$matchedProperties = \Bitrix\Crm\Order\Matcher\Internals\OrderPropsMatchTable::getList([
				'filter' => ['SALE_PROP_ID' => array_keys($personTypeProperties)]
			])->fetchAll();
			$matchedProperties = array_column($matchedProperties, null, 'SALE_PROP_ID');

			foreach ($personTypeProperties as $property)
			{
				if (isset($matchedProperties[$property['ID']]))
				{
					$matchedProperty = $matchedProperties[$property['ID']];
					$field = null;

					if ((int)$matchedProperty['CRM_FIELD_TYPE'] === \Bitrix\Crm\Order\Matcher\BaseEntityMatcher::MULTI_FIELD_TYPE)
					{
						$fieldInfo = \CCrmFieldMulti::ParseComplexName($matchedProperty['CRM_FIELD_CODE'], true);

						if (isset($fieldInfo['TYPE']))
						{
							$field = $fieldInfo['TYPE'];
						}
					}
					else
					{
						$field = $matchedProperty['CRM_FIELD_CODE'];
					}

					if (!empty($field))
					{
						$matchedProperty['CODE'] = $property['CODE'];
						$matchedProperty['DATA'] = $property;

						$formFields[$property['ID']] = $matchedProperty;
					}
				}
				else
				{
					if (empty($property['CODE']))
					{
						$propertyName = FieldSynchronizer::getOrderPropertyName($property['CODE'], $property['ID']);
					}
					else
					{
						$propertyName = $property['CODE'];
					}

					$formFields[$property['ID']] = [
						'ID' => 'n'.$cnt++,
						'SALE_PROP_ID' => $property['ID'],
						'CRM_ENTITY_TYPE' => \CCrmOwnerType::Order,
						'CRM_FIELD_TYPE' => \Bitrix\Crm\Order\Matcher\BaseEntityMatcher::GENERAL_FIELD_TYPE,
						'SETTINGS' => [],
						'CODE' => $propertyName,
						'DATA' => $property
					];
				}
			}
		}

		return $formFields;
	}

	protected function getFieldsDescription($availableFields, $existingFields)
	{
		$descriptionFields = [];

		foreach ($existingFields as $field)
		{
			$fieldCode = FieldSynchronizer::getFieldFullCode($field);

			if (!isset($availableFields[$fieldCode]))
			{
				continue;
			}

			$propertyName = FieldSynchronizer::getOrderPropertyName($fieldCode, $field['ID']);
			$fieldAvailable = $availableFields[$fieldCode];

			$descriptionField = $field['DATA'] + $fieldAvailable;
			$descriptionField['caption'] = $descriptionField['NAME'];
			$descriptionField['NAME'] = $descriptionField['name'] = $field['CODE'];
			$descriptionField['NAME_ID'] = $descriptionField['CODE'] = $fieldCode;
			$descriptionField['TYPE_ORIGINAL'] = $fieldAvailable['type'];
			$descriptionField['MULTIPLE_ORIGINAL'] = $fieldAvailable['multiple'];
			$descriptionField['VALUE_TYPE_ORIGINAL'] = $fieldAvailable['value_type'] ?: [];
			$descriptionField['TYPE'] = $fieldAvailable['type'];
			$descriptionField['ENTITY_NAME'] = $fieldAvailable['entity_name'];
			$descriptionField['ENTITY_FIELD_NAME'] = $fieldAvailable['entity_field_name'];
			$descriptionField['ENTITY_CAPTION'] = $fieldAvailable['entity_caption'];
			$descriptionField['ENTITY_FIELD_CAPTION'] = $fieldAvailable['caption'];

			$descriptionField['VALUE'] = $descriptionField['DEFAULT_VALUE'];
			$descriptionField['PLACEHOLDER'] = $descriptionField['DESCRIPTION'];

			$descriptionField['PRESET_ID'] = isset($field['SETTINGS']['RQ_PRESET_ID'])
				? (string)$field['SETTINGS']['RQ_PRESET_ID']
				: '';
			$descriptionField['BANK_DETAIL'] = isset($field['SETTINGS']['BD_NAME']) ? 'Y' : 'N';
			$descriptionField['ADDRESS'] = isset($field['SETTINGS']['RQ_ADDR_CODE']) ? 'Y' : 'N';
			$descriptionField['ADDRESS_TYPE'] = isset($field['SETTINGS']['RQ_ADDR_TYPE'])
				? $field['SETTINGS']['RQ_ADDR_TYPE']
				: '';

			if (\CCrmFieldMulti::IsSupportedType($fieldAvailable['entity_field_name']))
			{
				$fieldInfo = \CCrmFieldMulti::ParseComplexName($field['CRM_FIELD_CODE'], true);

				if (isset($fieldInfo['VALUE_TYPE']))
				{
					$descriptionField['VALUE_TYPE'] = $fieldInfo['VALUE_TYPE'];
				}
			}

			if (isset($availableFields[$propertyName]['items']))
			{
				$descriptionField['ITEMS'] = $availableFields[$propertyName]['items'];

				foreach ($descriptionField['ITEMS'] as &$item)
				{
					$item['IS_ORDER_TYPE'] = true;
				}
			}
			elseif (isset($fieldAvailable['items']) && is_array($fieldAvailable['items']))
			{
				if (!isset($descriptionField['ITEMS']) || !is_array($descriptionField['ITEMS']))
				{
					$descriptionField['ITEMS'] = [];
				}

				$orderFields = isset($availableFields[$propertyName]['items']) && is_array($availableFields[$propertyName]['items'])
					? array_column($availableFields[$propertyName]['items'], null, 'VALUE')
					: [];

				foreach ($fieldAvailable['items'] as $availableItem)
				{
					if (isset($orderFields[$availableItem['ID']]))
					{
						$availableItem = $orderFields[$availableItem['ID']];
						$availableItem['IS_ORDER_TYPE'] = true;
					}
					else
					{
						if (isset($availableItem['NAME']))
						{
							$availableItem['IS_ORDER_TYPE'] = true;
						}
						else
						{
							//$availableItem['NAME'] = $availableItem['VALUE'];
							//$availableItem['VALUE'] = $availableItem['ID'];
							//unset($availableItem['ID']);

							$checked = is_array($field['DATA']['DEFAULT_VALUE'])
								? in_array($availableItem['ID'], $field['DATA']['DEFAULT_VALUE'])
								: $availableItem['ID'] === $field['DATA']['DEFAULT_VALUE'];

							$availableItem['SELECTED'] = $checked ? 'selected' : '';
							$availableItem['CHECKED'] = $checked ? 'checked' : '';
						}
					}

					$descriptionField['ITEMS'][] = $availableItem;
				}
			}

			$additionalFields = ['checked', 'selected'];

			foreach ($additionalFields as $additionalField)
			{
				if (isset($availableFields[$propertyName][$additionalField]))
				{
					$descriptionField[$additionalField] = $availableFields[$propertyName][$additionalField];
				}
			}

			$descriptionFields[] = $descriptionField;
		}

		return $descriptionFields;
	}

	protected function getFormFields($personTypeId, bool $withUtil = false)
	{
		$availableFields = FieldSynchronizer::getFieldsByCode($personTypeId);
		$existingFields = $this->getExistingFormFields($personTypeId, $withUtil);

		return $this->getFieldsDescription($availableFields, $existingFields);
	}

	protected function getFormRelations($field)
	{
		$relation = [];
		$entityValues = [];

		$result = \CSaleOrderProps::GetOrderPropsRelations(['PROPERTY_ID' => $field['ID']]);
		while ($row = $result->Fetch())
		{
			$entityValues[$row['ENTITY_TYPE']][] = $row['ENTITY_ID'];
		}

		if (!empty($entityValues))
		{
			foreach ($entityValues as $entityType => $values)
			{
				$relation[] = [
					'ID' => $field['NAME'].'_'.$entityType,
					'IF_FIELD_CODE' => $entityType,
					'IF_VALUE' => $values,
					'DO_FIELD_CODE' => $field['NAME'],
					'DO_ACTION' => 'SHOW',
				];
			}
		}

		return $relation;
	}

	protected function getForm($personTypeId)
	{
		$form = [];
		$form['FIELDS'] = $this->getFormFields($personTypeId);
		$form['RELATIONS'] = [];
		$allFields = $this->getFormFields($personTypeId, true);
		$form['ALL_RELATIONS'] = [];

		foreach ($form['FIELDS'] as $field)
		{
			$form['RELATIONS'] = array_merge($form['RELATIONS'], $this->getFormRelations($field));
		}

		foreach ($allFields as $field)
		{
			$form['ALL_RELATIONS'] = array_merge($form['ALL_RELATIONS'], $this->getFormRelations($field));
		}

		return $form;
	}

	public static function getSchemes($schemeId = null)
	{
		// ATTENTION!!! SCHEME ORDER IS IMPORTANT FOR getSchemesByInvoice
		// ATTENTION!!! ENTITY ORDER IS IMPORTANT FOR SYNCHRONIZATION
		$schemes = [
			self::ENUM_ENTITY_SCHEME_CONTACT => [
				'NAME' => Loc::getMessage('CRM_ORDERFORM_ENTITY_SCHEME_CLIENT'),
				'ENTITIES' => [
					\CCrmOwnerType::CompanyName,
					\CCrmOwnerType::ContactName,
					\CCrmOwnerType::RequisiteName,
				],
				'DESCRIPTION' => Loc::getMessage('CRM_ORDERFORM_ENTITY_SCHEME_CLIENT_DESC2')
			]
		];

		if ($schemeId)
		{
			return isset($schemes[$schemeId]) ? $schemes[$schemeId] : false;
		}
		else
		{
			return $schemes;
		}
	}

	public static function getSchemesByInvoice($selectedSchemeId = null, $allowedEntitySchemes = null)
	{
		$result = [
			'HAS_DEAL' => false,
			'HAS_INVOICE' => false,
			'SELECTED_DESCRIPTION' => '',
			'BY_INVOICE' => [],
			'BY_NON_INVOICE' => [],
		];

		$schemes = static::getSchemes();
		$previousSchemeId = null;
		foreach ($schemes as $schemeId => $scheme)
		{
			if (!$selectedSchemeId)
			{
				$selectedSchemeId = $schemeId;
			}

			$scheme['ID'] = $schemeId;
			$scheme['SELECTED'] = false;
			$scheme['DISABLED'] = (!empty($allowedEntitySchemes) && !in_array($schemeId, $allowedEntitySchemes));

			$hasDeal = in_array(\CCrmOwnerType::DealName, $scheme['ENTITIES']);
			$hasInvoice = in_array(\CCrmOwnerType::InvoiceName, $scheme['ENTITIES']);
			$searchSchemeId = $hasInvoice ? $previousSchemeId : $schemeId;

			$section = $hasInvoice ? 'BY_INVOICE' : 'BY_NON_INVOICE';
			$result[$section][$searchSchemeId] = $scheme;
			$previousSchemeId = $schemeId;

			if ($schemeId == $selectedSchemeId)
			{
				$result['SELECTED_ID'] = $selectedSchemeId;
				$result['HAS_DEAL'] = $hasDeal;
				$result['HAS_INVOICE'] = $hasInvoice;
				$result['BY_NON_INVOICE'][$searchSchemeId]['SELECTED'] = true;
				$result['SELECTED_DESCRIPTION'] = $scheme['DESCRIPTION'];
			}
		}

		return $result;
	}

	protected static function getAvailableEntities()
	{
		$result = [];

		$map = FieldSynchronizer::getEntityMap();

		foreach ($map as $entityName => $entity)
		{
			$result[$entityName] = \CCrmOwnerType::GetDescription(\CCrmOwnerType::ResolveID($entityName));
		}

		return $result;
	}

	protected static function getRelationEntities()
	{
		return [
			[
				'CODE' => 'D',
				'NAME' => Loc::getMessage('CRM_ORDERFORM_RELATION_DELIVERY'),
				'ITEMS' => array_values(FieldSynchronizer::getDeliveryRelations())
			],
			[
				'CODE' => 'P',
				'NAME' => Loc::getMessage('CRM_ORDERFORM_RELATION_PAY_SYSTEM'),
				'ITEMS' => array_values(FieldSynchronizer::getPaySystemRelations())
			]
		];
	}

	protected static function getDuplicateModeList()
	{
		$duplicateModeList = [];

		foreach (\Bitrix\Crm\Order\Matcher\BaseEntityMatcher::DUPLICATE_CONTROL_MODES as $mode)
		{
			$duplicateModeList[$mode] = Loc::getMessage('CRM_ORDERFORM_RESULT_ENTITY_DC_'.$mode);
		}

		return $duplicateModeList;
	}

	protected function getDuplicateMode()
	{
		return \Bitrix\Crm\Order\Matcher\Internals\FormTable::getDuplicateModeByPersonType($this->arParams['PERSON_TYPE_ID']);
	}

	protected function initDuplicateModes()
	{
		$selectedDuplicateMode = $this->getDuplicateMode();

		foreach (static::getDuplicateModeList() as $duplicateModeId => $duplicateModeCaption)
		{
			$this->arResult['DUPLICATE_MODES'][] = [
				'ID' => $duplicateModeId,
				'CAPTION' => $duplicateModeCaption,
				'SELECTED' => $duplicateModeId === $selectedDuplicateMode,
			];
		}
	}

	protected static function getFieldsTreeToRender($personTypeId)
	{
		$fieldsTree = FieldSynchronizer::getFieldsTree($personTypeId);

		if (!empty($fieldsTree[\CCrmOwnerType::OrderName]['FIELDS']))
		{
			$fieldsToRender = [];
			$allOrderProperties = $fieldsTree[\CCrmOwnerType::OrderName]['FIELDS'];

			$matchedIds = \Bitrix\Crm\Order\Matcher\Internals\OrderPropsMatchTable::getList([
				'select' => ['SALE_PROP_ID'],
				'filter' => ['SALE_PROP_ID' => array_column($allOrderProperties, 'id')]
			])->fetchAll();
			$matchedIds = array_column($matchedIds, 'SALE_PROP_ID');

			foreach ($allOrderProperties as $property)
			{
				if (in_array($property['id'], $matchedIds))
				{
					continue;
				}

				$fieldsToRender[] = $property;
			}

			if (empty($fieldsToRender))
			{
				$fieldsToRender = [
					[
						'type' => 'empty-list-label',
						'caption' => Loc::getMessage('CRM_ORDERFORM_EMPTY_LIST_LABEL')
					]
				];
			}

			$fieldsTree[\CCrmOwnerType::OrderName]['FIELDS'] = $fieldsToRender;
		}

		return $fieldsTree;
	}

	protected function initResultAssignedBy()
	{
		$this->arResult['ASSIGNED_BY'] = array(
			'LIST' => array(),
			'WORK_TIME' => false,
			'IS_SUPPORTED_WORK_TIME' => false
		);

		$responsibleQueue = new ResponsibleQueue($this->arParams['PERSON_TYPE_ID']);
		$list = $responsibleQueue->getList();

		$this->arResult['ASSIGNED_BY']['IS_SUPPORTED_WORK_TIME'] = $responsibleQueue->isSupportedWorkTime();
		$this->arResult['ASSIGNED_BY']['WORK_TIME'] = $responsibleQueue->isWorkTimeCheckEnabled();

		foreach ($list as $item)
		{
			$userFields = \Bitrix\Main\UserTable::getRowById($item);
			if (!$userFields)
			{
				continue;
			}

			$this->arResult['ASSIGNED_BY']['LIST'][] = array(
				'ID' => $item,
				'NAME' => CUser::FormatName(
					$this->arParams['NAME_TEMPLATE'],
					array(
						'LOGIN' => $userFields['LOGIN'],
						'NAME' => $userFields['NAME'],
						'LAST_NAME' => $userFields['LAST_NAME'],
						'SECOND_NAME' => $userFields['SECOND_NAME']
					),
					true, false
				)
			);
		}

		if (empty($this->arResult['ASSIGNED_BY']['LIST']))
		{
			global $USER;
			$userId = $USER->GetID();
			$userFields = \Bitrix\Main\UserTable::getRowById($userId);

			$this->arResult['ASSIGNED_BY']['LIST'][] = array(
				'ID' => $userId,
				'NAME' => CUser::FormatName(
					$this->arParams['NAME_TEMPLATE'],
					array(
						'LOGIN' => $userFields['LOGIN'],
						'NAME' => $userFields['NAME'],
						'LAST_NAME' => $userFields['LAST_NAME'],
						'SECOND_NAME' => $userFields['SECOND_NAME']
					),
					true, false
				)
			);
		}

		$this->arResult['CONFIG_ASSIGNED_BY'] = array(
			'valueInputName' => 'ASSIGNED_BY_ID',
			'selected' => array(),
			'multiple' => true,
			'required' => true,
		);
		foreach ($this->arResult['ASSIGNED_BY']['LIST'] as $assignedBy)
		{
			$this->arResult['CONFIG_ASSIGNED_BY']['selected'][] = array(
				'id' => 'U'.(int)$assignedBy['ID'],
				'entityId' => (int)$assignedBy['ID'],
				'entityType' => 'users',
				'name' => htmlspecialcharsBx($assignedBy['NAME']),
				'avatar' => '',
				'desc' => '&nbsp;'
			);
		}
	}

	protected function prepareResult()
	{
		$personTypeId = $this->arParams['PERSON_TYPE_ID'];

		$this->arResult['PERSON_TYPES'] = $this->getPersonTypes();
		$this->arResult['SELECTED_PERSON_TYPE_ID'] = $personTypeId;

		// all fields dictionary
		$this->arResult['AVAILABLE_FIELDS'] = FieldSynchronizer::getFields($personTypeId);
		// fields to render in tree
		$this->arResult['AVAILABLE_FIELDS_TREE'] = static::getFieldsTreeToRender($personTypeId);

		$this->arResult['FORM'] = $this->getForm($personTypeId);

		$this->arResult['ENTITY_SCHEMES'] = static::getSchemesByInvoice();
		$this->arResult['AVAILABLE_ENTITIES'] = static::getAvailableEntities();

		$this->arResult['RELATION_ENTITIES'] = static::getRelationEntities();

		$this->initResultAssignedBy();
		$this->initDuplicateModes();
	}

	protected function initialLoadAction()
	{
		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('CRM_ORDERFORM_TITLE'));

		$this->prepareResult();

		$this->includeComponentTemplate();
	}

	protected function checkEditPermission()
	{
		if (!$this->arResult['PERM_CAN_EDIT'])
		{
			$this->errors[] = Loc::getMessage('CRM_PERMISSION_DENIED');

			return false;
		}

		return true;
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

	protected function checkRequiredFields()
	{
		$hasRequiredFields = true;

		$fieldCodes = array_keys($this->request->get('checkFields') ?: []);
		$requiredFields = $this->getRequiredFields($fieldCodes, $this->arParams['PERSON_TYPE_ID']);

		if (!empty($requiredFields))
		{
			$hasRequiredFields = false;
			$this->errors[] = Loc::getMessage('CRM_ORDERFORM_REQUIERD_FIELDS_ERROR');
		}

		return $hasRequiredFields;
	}

	protected function getRequiredFields($fieldCodes, $personTypeId)
	{
		$requiredFields = [];
		$entities = [];

		$availableFields = FieldSynchronizer::getFieldsByCode($personTypeId);

		foreach ($fieldCodes as $fieldCode)
		{
			if (
				isset($availableFields[$fieldCode])
				&& $availableFields[$fieldCode]['entity_name'] !== CCrmOwnerType::OrderName
			)
			{
				$entities[$availableFields[$fieldCode]['entity_name']] = true;
			}
		}

		foreach ($availableFields as $field)
		{
			if (isset($entities[$field['entity_name']]) && $field['required'])
			{
				// temporary hack
				if ($field['entity_field_name'] === 'SECOND_NAME')
					continue;

				$requiredFields[] = $field['name'];
			}
		}

		if (in_array('CONTACT_FULL_NAME', $fieldCodes))
		{
			return [];
		}

		return array_values(array_diff($requiredFields, $fieldCodes));
	}

	protected function sendJsonAnswer($result)
	{
		global $APPLICATION;

		$APPLICATION->RestartBuffer();
		header('Content-Type: application/json');

		echo \Bitrix\Main\Web\Json::encode(
			$result + [
				'error' => $this->hasErrors(),
				'errors' => $this->errors,
				'errorText' => implode('<br>', $this->errors)
			]
		);

		\CMain::FinalActions();
		die();
	}

	protected function checkFieldsAjaxAction()
	{
		$requiredFields = [];

		if ($this->checkPostRequest() && $this->checkEditPermission())
		{
			$schemeId = $this->request->get('schemeId') ?: [];

			if ($this->getSchemes($schemeId))
			{
				$fieldCodes = $this->request->get('fieldCodes') ?: [];
				$requiredFields = $this->getRequiredFields($fieldCodes, $this->arParams['PERSON_TYPE_ID']);
			}
		}

		static::sendJsonAnswer(['requiredFieldCodes' => $requiredFields]);
	}

	protected function getDestinationDataAjaxAction()
	{
		$result = ['LAST' => []];

		if ($this->checkPostRequest() && $this->checkEditPermission())
		{
			if (\Bitrix\Main\Loader::includeModule('socialnetwork'))
			{
				$arStructure = CSocNetLogDestination::GetStucture([]);
				$result['DEPARTMENT'] = $arStructure['department'];
				$result['DEPARTMENT_RELATION'] = $arStructure['department_relation'];
				$result['DEPARTMENT_RELATION_HEAD'] = $arStructure['department_relation_head'];

				$result['DEST_SORT'] = CSocNetLogDestination::GetDestinationSort([
					'DEST_CONTEXT' => 'CRM_AUTOMATION',
				]);

				CSocNetLogDestination::fillLastDestination(
					$result['DEST_SORT'],
					$result['LAST']
				);

				$destinationUser = [];

				if (!empty($result['LAST']['USERS']) && is_array($result['LAST']['USERS']))
				{
					foreach ($result['LAST']['USERS'] as $value)
					{
						$destinationUser[] = str_replace('U', '', $value);
					}
				}

				$result['USERS'] = \CSocNetLogDestination::getUsers(['id' => $destinationUser]);
				$result['ROLES'] = [];
			}
		}

		static::sendJsonAnswer(['DATA' => $result]);
	}

	protected function saveForm()
	{
		$personTypeId = $this->arParams['PERSON_TYPE_ID'];

		$fields = is_array($this->request->get('FIELD')) ? $this->request->get('FIELD') : [];

		if (!empty($_FILES['FIELD']) && is_array($_FILES['FIELD']))
		{
			$fields = \Bitrix\Sale\Internals\Input\File::getPostWithFiles([$fields], [$_FILES['FIELD']])[0];
		}

		$relations = is_array($this->request->get('DEPENDENCIES')) ? $this->request->get('DEPENDENCIES') : [];

		$result = FieldSynchronizer::save($personTypeId, $fields, $relations);
		if (!$result->isSuccess())
		{
			$this->errors = array_merge($this->errors, $result->getErrorMessages());
		}

		$assignedById = explode(',', $this->request->get('ASSIGNED_BY_ID') ?: '');
		$assignedById = is_array($assignedById) ? $assignedById : [$assignedById];
		$assignedWorkTime = $this->request->get('ASSIGNED_WORK_TIME') === 'Y';

		$responsibleQueue = new ResponsibleQueue($personTypeId);
		$responsibleQueue->setList($assignedById, $assignedWorkTime);

		$duplicateMode = $this->request->get('DUPLICATE_MODE');
		FieldSynchronizer::updateDuplicateMode($personTypeId, $duplicateMode);
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
		if ($this->checkPostRequest() && $this->checkEditPermission() && $this->checkRequiredFields())
		{
			$this->saveForm();
		}

		$result = [];

		if ($this->arParams['IFRAME'])
		{
			$result['properties'] = $this->getFormFields($this->arParams['PERSON_TYPE_ID']);
		}
		else
		{
			$result['redirect'] = true;
		}

		$this->sendJsonAnswer($result);
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
		if (!$this->checkModules() || !$this->checkPermissions() || !$this->checkPersonType())
		{
			$this->showErrors();
			return;
		}

		$this->action = $this->prepareAction();
		$this->doAction($this->action);
	}
}
