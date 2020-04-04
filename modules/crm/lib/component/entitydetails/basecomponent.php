<?php
namespace Bitrix\Crm\Component\EntityDetails;

use Bitrix\Main;

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

	public static function prepareMultifieldsForSave(array $data, array &$entityFields)
	{
		foreach($data as $item)
		{
			$typeID = isset($item['TYPE_ID']) ? $item['TYPE_ID'] : '';
			$value = isset($item['VALUE']) ? $item['VALUE'] : '';
			if($typeID === '' || $value === '')
			{
				continue;
			}

			if($typeID === 'EMAIL' && !check_email($value))
			{
				continue;
			}

			if(!isset($entityFields['FM']))
			{
				$entityFields['FM'] = array();
			}

			if(!isset($entityFields['FM'][$typeID]))
			{
				$entityFields['FM'][$typeID] = array();
			}

			$qty = count($entityFields['FM'][$typeID]);
			$entityFields['FM'][$typeID]["n{$qty}"] = array('VALUE' => $value, 'VALUE_TYPE' => 'WORK');
		}
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