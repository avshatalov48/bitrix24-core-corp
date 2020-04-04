<?php
namespace Bitrix\Crm\Component\EntityDetails;

use Bitrix\Main;

use Bitrix\Crm;
use Bitrix\Crm\Security\EntityPermissionType;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Component\ComponentError;

class BaseComponent extends \CBitrixComponent
{
	/** @var string */
	protected $guid = '';
	/** @var int */
	protected $userID = 0;
	/** @var  \CCrmPerms|null */
	protected $userPermissions = null;
	/** @var \CCrmUserType|null  */
	protected $userType = null;
	/** @var array|null */
	protected $userFields = null;
	/** @var array|null */
	protected $userFieldInfos = null;
	/** @var \Bitrix\Main\UserField\Dispatcher|null */
	protected $userFieldDispatcher = null;
	/** @var int */
	protected $entityID = 0;
	/** @var int */
	protected $mode = ComponentMode::UNDEFINED;
	/** @var array|null */
	protected $errors = null;

	//---
	/** @var array|null */
	protected $entityData = null;
	/** @var array|null */
	protected $entityDataScheme = null;
	/** @var bool */
	protected $isEditMode = false;
	/** @var bool */
	protected $isCopyMode = false;
	//---

	public function __construct($component = null)
	{
		/** @global \CUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;

		parent::__construct($component);

		$this->userID = \CCrmSecurityHelper::GetCurrentUserID();
		$this->userPermissions = \CCrmPerms::GetCurrentUserPermissions();

		$userFieldEntityID = $this->getUserFieldEntityID();
		if($userFieldEntityID !== '')
		{
			$this->userType = new \CCrmUserType($USER_FIELD_MANAGER, $userFieldEntityID);
			$this->userFieldDispatcher = Main\UserField\Dispatcher::instance();
		}

		$this->errors = array();
	}

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Undefined;
	}
	public function getEntityID()
	{
		return $this->entityID;
	}
	public function setEntityID($entityID)
	{
		$this->entityID = $entityID;

		$this->userFields = null;
		$this->prepareEntityUserFields();

		$this->userFieldInfos = null;
		$this->prepareEntityUserFieldInfos();
	}
	public function getMode()
	{
		return $this->mode;
	}

	public static function createEntity($entityTypeID, array $entityData, array $options = array())
	{
		$currentUserPermissions = isset($options['userPermissions']) ? $options['userPermissions'] : null;
		if(!($currentUserPermissions instanceof \CCrmPerms))
		{
			$currentUserPermissions = \CCrmPerms::GetCurrentUserPermissions();
		}

		$title = isset($entityData['title']) ? trim($entityData['title']) : '';
		if(!($title !== '' && EntityAuthorization::checkCreatePermission($entityTypeID, $currentUserPermissions)))
		{
			return 0;
		}

		if($entityTypeID === \CCrmOwnerType::Company)
		{
			$fields = array('TITLE' => $title);
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			$fields = array();
			if($title === \CCrmContact::GetDefaultName())
			{
				$fields['NAME'] = $title;
			}
			else
			{
				Crm\Format\PersonNameFormatter::tryParseName(
					$title,
					Crm\Format\PersonNameFormatter::getFormatID(),
					$fields
				);
			}
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		$multifieldData =  isset($entityData['multifields']) && is_array($entityData['multifields'])
			? $entityData['multifields']  : array();

		if(!empty($multifieldData))
		{
			$multifields = self::prepareMultifieldsForSave($entityTypeID, 0, $multifieldData);
			if(!empty($multifields))
			{
				$fields['FM'] = $multifields;
			}
		}

		$entity = Crm\Entity\EntityManager::resolveByTypeID($entityTypeID);
		if(!$entity)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}
		$entityID = $entity->create($fields);
		if($entityID > 0 && isset($options['startWorkFlows']) && $options['startWorkFlows'])
		{
			\CCrmBizProcHelper::AutoStartWorkflows(
				$entityTypeID,
				$entityID,
				\CCrmBizProcEventType::Create,
				$arErrors
			);
		}
		return $entityID;
	}
	public static function updateEntity($entityTypeID, $entityID, array $entityData, array $options = array())
	{
		if(empty($entityData))
		{
			return false;
		}

		$currentUserPermissions = isset($options['userPermissions']) ? $options['userPermissions'] : null;
		if(!($currentUserPermissions instanceof \CCrmPerms))
		{
			$currentUserPermissions = \CCrmPerms::GetCurrentUserPermissions();
		}

		if(!EntityAuthorization::checkUpdatePermission($entityTypeID, $entityID, $currentUserPermissions))
		{
			return false;
		}

		$entity = Crm\Entity\EntityManager::resolveByTypeID($entityTypeID);
		if(!$entity)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		$presentFields = $entity->getByID($entityID);
		if(!is_array($presentFields))
		{
			return false;
		}

		$fields = array();
		$title = isset($entityData['title']) ? trim($entityData['title']) : '';
		if($title !== '')
		{
			if($entityTypeID === \CCrmOwnerType::Company)
			{
				if(!isset($presentFields['TITLE']) || $presentFields['TITLE'] !== $title)
				{
					$fields['TITLE'] = $title;
				}
			}
			elseif($entityTypeID === \CCrmOwnerType::Contact)
			{
				$nameFormatID = Crm\Format\PersonNameFormatter::getFormatID();
				$nameFormat = Crm\Format\PersonNameFormatter::getFormat();

				if($title !== \CCrmContact::PrepareFormattedName($presentFields))
				{
					if($title === \CCrmContact::GetDefaultName())
					{
						$fields['NAME'] = $title;
					}
					else
					{
						Crm\Format\PersonNameFormatter::tryParseName(
							$title,
							$nameFormatID,
							$fields
						);
					}

					if(isset($presentFields['NAME'])
						&& (!isset($fields['NAME']) || $fields['NAME'] === '')
						&& !Crm\Format\PersonNameFormatter::hasFirstName($nameFormat)
					)
					{
						$fields['NAME'] = $presentFields['NAME'];
					}

					if(isset($presentFields['SECOND_NAME'])
						&& (!isset($fields['SECOND_NAME']) || $fields['SECOND_NAME'] === '')
						&& !Crm\Format\PersonNameFormatter::hasSecondName($nameFormat)
					)
					{
						$fields['SECOND_NAME'] = $presentFields['SECOND_NAME'];
					}

					if(isset($presentFields['LAST_NAME'])
						&& (!isset($fields['LAST_NAME']) || $fields['LAST_NAME'] === '')
						&& !Crm\Format\PersonNameFormatter::hasLastName($nameFormat)
					)
					{
						$fields['LAST_NAME'] = $presentFields['LAST_NAME'];
					}

					if(Crm\Comparer\ComparerBase::areFieldsEquals($fields, $presentFields, 'NAME')
						&& Crm\Comparer\ComparerBase::areFieldsEquals($fields, $presentFields, 'SECOND_NAME')
						&& Crm\Comparer\ComparerBase::areFieldsEquals($fields, $presentFields, 'LAST_NAME')
					)
					{
						$fields = array();
					}
				}
			}
			else
			{
				$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
				throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
			}
		}

		$multifieldData =  isset($entityData['multifields']) && is_array($entityData['multifields'])
			? $entityData['multifields']  : array();

		if(!empty($multifieldData))
		{
			$multifields = self::prepareMultifieldsForSave($entityTypeID, $entityID, $multifieldData);
			if(!empty($multifields))
			{
				$presentMultifields = self::getMultifields($entityTypeID, $entityID);
				if(count($presentMultifields) === count($multifields))
				{
					$areMultifieldsEquals = true;
					foreach(array('PHONE', 'EMAIL') as $multifieldType)
					{
						$multifieldItems = isset($multifields[$multifieldType])
							? $multifields[$multifieldType] : array();
						$presentMultifieldsItems = isset($presentMultifields[$multifieldType])
							? $presentMultifields[$multifieldType] : array();

						foreach($multifieldItems as $multifieldID => $multifieldData)
						{
							if(!isset($presentMultifieldsItems[$multifieldID])
								|| $presentMultifieldsItems[$multifieldID]['VALUE'] !== $multifieldData['VALUE']
							)
							{
								$areMultifieldsEquals = false;
								break;
							}
						}

						if(!$areMultifieldsEquals)
						{
							break;
						}
					}

					if($areMultifieldsEquals)
					{
						$multifields = array();
					}
				}

				if(!empty($multifields))
				{
					$fields['FM'] = $multifields;
				}
			}
		}

		if(empty($fields))
		{
			return false;
		}

		$result = $entity->update($entityID, $fields);
		if($result && isset($options['startWorkFlows']) && $options['startWorkFlows'])
		{
			\CCrmBizProcHelper::AutoStartWorkflows(
				$entityTypeID,
				$entityID,
				\CCrmBizProcEventType::Edit,
				$arErrors
			);
		}
		return $result;
	}
	public static function deleteEntity($entityTypeID, $entityID, array $options = array())
	{
		$entity = Crm\Entity\EntityManager::resolveByTypeID($entityTypeID);
		if(!$entity)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		$entity->delete($entityID, $options);
	}

	public static function prepareLastBoundEntityIDs($entityTypeID, $ownerEntityTypeID, array $params = null)
	{
		if($params === null)
		{
			$params = array();
		}

		$userID = (isset($params['userID']) && $params['userID'] > 0)
			? (int)$params['userID'] : \CCrmSecurityHelper::GetCurrentUserID();
		$userPermissions = isset($params['userPermissions'])
			? $params['userPermissions'] : \CCrmPerms::GetCurrentUserPermissions();

		$results = array();
		if($ownerEntityTypeID === \CCrmOwnerType::Deal && \CCrmDeal::CheckReadPermission(0, $userPermissions))
		{
			if($entityTypeID === \CCrmOwnerType::Contact)
			{
				$companyID = isset($params['companyID']) ? (int)$params['companyID'] : 0;
				if($companyID > 0)
				{
					$dbResult = \CCrmDeal::GetListEx(
						array('ID' => 'DESC'),
						array(
							'=COMPANY_ID' => $companyID,
							'=ASSIGNED_BY_ID' => $userID,
							'CHECK_PERMISSIONS' => 'N'
						),
						false,
						array('nTopCount' => 5),
						array('ID')
					);

					$ownerIDs = array();
					while($ary = $dbResult->Fetch())
					{
						$ownerIDs[] = (int)$ary['ID'];
					}

					$ownerIDs = array();
					while($ary = $dbResult->Fetch())
					{
						$ownerIDs[] = (int)$ary['ID'];
					}

					foreach($ownerIDs as $ownerID)
					{
						$entityIDs = Crm\Binding\DealContactTable::getDealContactIDs($ownerID);
						foreach($entityIDs as $entityID)
						{
							if(\CCrmContact::CheckReadPermission($entityID, $userPermissions))
							{
								$results[] = $entityID;
							}
						}

						if(!empty($results))
						{
							break;
						}
					}

					if(empty($results))
					{
						$results = Crm\Binding\ContactCompanyTable::getCompanyContactIDs($companyID);
					}
				}
			}
		}
		return $results;
	}

	public static function prepareMultifieldsForSave($entityTypeID, $entityID, array $multifieldData)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		$multifields = array();
		if(\CCrmOwnerType::IsDefined($entityTypeID) && $entityID > 0)
		{
			$dbResult = \CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => \CCrmOwnerType::ResolveName($entityTypeID), 'ELEMENT_ID' => $entityID)
			);
			while($fields = $dbResult->Fetch())
			{
				$typeID = $fields['TYPE_ID'];
				if(!isset($multifields[$typeID]))
				{
					$multifields[$typeID] = array();
				}

				$multifields[$typeID][$fields['ID']] = array(
					'VALUE' => isset($fields['VALUE']) ? $fields['VALUE'] : '',
					'VALUE_TYPE' => isset($fields['VALUE_TYPE']) ? $fields['VALUE_TYPE'] : ''
				);
			}
		}

		$counter = 0;
		foreach($multifieldData as $item)
		{
			$ID = isset($item['ID']) ? (int)$item['ID'] : 0;
			$typeID = isset($item['TYPE_ID']) ? $item['TYPE_ID'] : '';
			$value = isset($item['VALUE']) ? $item['VALUE'] : '';
			if($typeID === '')
			{
				continue;
			}

			if($ID <= 0 && $value === '')
			{
				continue;
			}

			if($typeID === 'EMAIL' && !check_email($value))
			{
				if($ID <= 0)
				{
					continue;
				}
				else
				{
					$value = '';
				}
			}

			if(!isset($multifields[$typeID]))
			{
				$multifields[$typeID] = array();
			}

			if($ID > 0)
			{
				$valueType = isset($multifields[$typeID][$ID]) && $multifields[$typeID][$ID]['VALUE_TYPE']
					? $multifields[$typeID][$ID]['VALUE_TYPE'] : 'WORK';
				$multifields[$typeID][$ID] = array('VALUE' => $value, 'VALUE_TYPE' => $valueType);
			}
			else
			{
				$multifields[$typeID]["n{$counter}"] = array('VALUE' => $value, 'VALUE_TYPE' => 'WORK');
				$counter++;
			}
		}
		return $multifields;
	}
	protected static function getMultifields($entityTypeID, $entityID)
	{
		$dbResult = \CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => \CCrmOwnerType::ResolveName($entityTypeID), 'ELEMENT_ID' => $entityID)
		);

		$multifields = array();
		while($fields = $dbResult->Fetch())
		{
			$typeID = $fields['TYPE_ID'];
			if(!isset($multifields[$typeID]))
			{
				$multifields[$typeID] = array();
			}

			$multifields[$typeID][$fields['ID']] = array(
				'VALUE' => isset($fields['VALUE']) ? $fields['VALUE'] : '',
				'VALUE_TYPE' => isset($fields['VALUE_TYPE']) ? $fields['VALUE_TYPE'] : ''
			);
		}

		return $multifields;
	}
	protected static function prepareMultifieldData($entityTypeID, $entityID, $typeID, array &$data)
	{
		$dbResult = \CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => \CCrmOwnerType::ResolveName($entityTypeID),
				'ELEMENT_ID' => $entityID,
				'TYPE_ID' => $typeID
			)
		);

		$entityKey = "{$entityTypeID}_{$entityID}";
		while($fields = $dbResult->Fetch())
		{
			$value = isset($fields['VALUE']) ? $fields['VALUE'] : '';
			$valueType = $fields['VALUE_TYPE'];
			$multiFieldComplexID = $fields['COMPLEX_ID'];

			if($value === '')
			{
				continue;
			}

			if(!isset($data[$typeID]))
			{
				$data[$typeID] = array();
			}

			if(!isset($data[$typeID][$entityKey]))
			{
				$data[$typeID][$entityKey] = array();
			}

			//Is required for phone & email & messenger menu
			if($typeID === 'PHONE' || $typeID === 'EMAIL'
				|| ($typeID === 'IM' && preg_match('/^imol\|/', $value) === 1)
			)
			{
				$formattedValue = $typeID === 'PHONE'
					? Main\PhoneNumber\Parser::getInstance()->parse($value)->format()
					: $value;

				$data[$typeID][$entityKey][] = array(
					'ID' => $fields['ID'],
					'VALUE' => $value,
					'VALUE_TYPE' => $valueType,
					'VALUE_FORMATTED' => $formattedValue,
					'COMPLEX_ID' => $multiFieldComplexID,
					'COMPLEX_NAME' => \CCrmFieldMulti::GetEntityNameByComplex($multiFieldComplexID, false)
				);
			}
			else
			{
				$data[$typeID][$entityKey][] = $value;
			}
		}
	}

	protected function getRequestParamOrDefault($paramName, $default = null)
	{
		$value = $this->request->get($paramName);
		return $value !== null ? $value : $default;
	}

	protected static function getUser($userID)
	{
		$dbUsers = \CUser::GetList(
			$by = 'ID',
			$order = 'ASC',
			array('ID' => $userID),
			array('FIELDS' => array('ID',  'LOGIN', 'PERSONAL_PHOTO', 'NAME', 'SECOND_NAME', 'LAST_NAME'))
		);
		return is_object($dbUsers) ? $dbUsers->Fetch() : null;
	}

	protected function getUserFieldEntityID()
	{
		return '';
	}
	protected function getFileHandlerUrl()
	{
		return '';
	}
	protected function checkIfEntityExists()
	{
		return false;
	}
	protected function checkEntityPermission($permissionTypeID)
	{
		return EntityAuthorization::checkPermission(
			$permissionTypeID,
			$this->getEntityTypeID(),
			$this->entityID,
			$this->userPermissions
		);
	}
	protected function addError($error)
	{
		$this->errors[] = $error;
	}
	protected function getErrorMessage($error)
	{
		return ComponentError::getMessage($error);
	}
	protected function getErrors()
	{
		return $this->errors;
	}
	protected function showErrors()
	{
		$messages = array();
		foreach($this->errors as $error)
		{
			$message = $this->getErrorMessage($error);
			if($message !== '')
			{
				$messages[] = $message;
			}
		}

		if(!empty($messages))
		{
			ShowError(implode("\r\n", $messages));
		}
	}
	protected function tryToDetectMode()
	{
		if($this->entityID <= 0)
		{
			if(!$this->checkEntityPermission(EntityPermissionType::CREATE))
			{
				$this->addError(ComponentError::PERMISSION_DENIED);
				return false;
			}

			$this->mode = ComponentMode::CREATION;
		}
		else
		{
			if(!$this->checkIfEntityExists())
			{
				$this->addError(ComponentError::ENTITY_NOT_FOUND);
				return false;
			}

			if($this->getRequestParamOrDefault('copy', '') !== '')
			{
				if(!($this->checkEntityPermission(EntityPermissionType::READ)
					&& $this->checkEntityPermission(EntityPermissionType::CREATE))
				)
				{
					$this->addError(ComponentError::PERMISSION_DENIED);
					return false;
				}

				$this->mode = ComponentMode::COPING;
			}
			else
			{
				if(!$this->checkEntityPermission(EntityPermissionType::READ))
				{
					$this->addError(ComponentError::PERMISSION_DENIED);
					return false;
				}

				$this->mode = $this->checkEntityPermission(EntityPermissionType::UPDATE)
					? ComponentMode::MODIFICATION
					: ComponentMode::VIEW;
			}
		}

		$this->arResult['COMPONENT_MODE'] = $this->mode;
		return true;
	}
	protected function getEntityFieldsInfo()
	{
		throw new Main\NotImplementedException('Method getEntityDataScheme must be overridden');
	}
	public function prepareEntityDataScheme()
	{
		if($this->entityDataScheme === null)
		{
			$this->entityDataScheme = $this->getEntityFieldsInfo();
			$this->userType->PrepareFieldsInfo($this->entityDataScheme);
		}
		return $this->entityDataScheme;
	}
	public function prepareEntityUserFields()
	{
		if($this->userFields !== null)
		{
			return $this->userFields;
		}

		if($this->userType !== null)
		{
			$this->userFields = $this->userType->GetEntityFields($this->entityID);
		}
		else
		{
			$this->userFields = array();
		}

		return $this->userFields;
	}
	public function prepareEntityUserFieldInfos()
	{
		if($this->userFieldInfos !== null)
		{
			return $this->userFieldInfos;
		}

		$this->userFieldInfos = array();
		$userFields = $this->prepareEntityUserFields();
		$enumerationFields = array();
		$userFieldEntityID = $this->getUserFieldEntityID();
		foreach($userFields as $userField)
		{
			$fieldName = $userField['FIELD_NAME'];
			$fieldInfo = array(
				'USER_TYPE_ID' => $userField['USER_TYPE_ID'],
				'ENTITY_ID' => $userFieldEntityID,
				'ENTITY_VALUE_ID' => $this->entityID,
				'FIELD' => $fieldName,
				'MULTIPLE' => $userField['MULTIPLE'],
				'MANDATORY' => $userField['MANDATORY'],
				'SETTINGS' => isset($userField['SETTINGS']) ? $userField['SETTINGS'] : null
			);

			if($userField['USER_TYPE_ID'] === 'enumeration')
			{
				$enumerationFields[$fieldName] = $userField;
			}
			elseif($userField['USER_TYPE_ID'] === 'file')
			{
				$fieldInfo['ADDITIONAL'] = array(
					'URL_TEMPLATE' => \CComponentEngine::MakePathFromTemplate(
						$this->getFileHandlerUrl(),
						array(
							'owner_id' => $this->entityID,
							'field_name' => $fieldName
						)
					)
				);
			}

			$this->userFieldInfos[$fieldName] = array(
				'name' => $fieldName,
				'title' => isset($userField['EDIT_FORM_LABEL']) ? $userField['EDIT_FORM_LABEL'] : $fieldName,
				'type' => 'userField',
				'data' => array('fieldInfo' => $fieldInfo)
			);

			if(isset($userField['MANDATORY']) && $userField['MANDATORY'] === 'Y')
			{
				$this->userFieldInfos[$fieldName]['required'] = true;
			}
		}

		if(!empty($enumerationFields))
		{
			$enumInfos = \CCrmUserType::PrepareEnumerationInfos($enumerationFields);
			foreach($enumInfos as $fieldName => $enums)
			{
				if(isset($this->userFieldInfos[$fieldName])
					&& isset($this->userFieldInfos[$fieldName]['data'])
					&& isset($this->userFieldInfos[$fieldName]['data']['fieldInfo'])
				)
				{
					$this->userFieldInfos[$fieldName]['data']['fieldInfo']['ENUM'] = $enums;
				}
			}
		}

		return $this->userFieldInfos;
	}
}