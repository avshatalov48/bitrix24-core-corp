<?php /** @noinspection ReturnTypeCanBeDeclaredInspection */

namespace Bitrix\Crm\Component\EntityDetails;

use Bitrix\Crm;
use Bitrix\Crm\Component\ComponentError;
use Bitrix\Crm\Entity\Traits\VisibilityConfig;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Security\EntityPermissionType;
use Bitrix\Main;

abstract class BaseComponent extends Crm\Component\Base
{
	use VisibilityConfig;

	/** @var string */
	protected $guid = '';
	/** @var int */
	protected $userID = 0;
	/** @var  \CCrmPerms|null */
	protected $userPermissions;
	/** @var \CCrmUserType|null  */
	protected $userType;
	/** @var array|null */
	protected $userFields;
	/** @var array|null */
	protected $userFieldInfos;
	/** @var \Bitrix\Main\UserField\Dispatcher|null */
	protected $userFieldDispatcher;
	/** @var int */
	protected $entityID = 0;
	/** @var int */
	protected $mode = ComponentMode::UNDEFINED;
	/** @var Crm\Conversion\EntityConversionWizard|null */
	protected $conversionWizard;

	//---
	/** @var array|null */
	protected $entityData;
	/** @var array|null */
	protected $entityDataScheme;
	/**
	 * @var bool
	 * @deprecated Use $this->isEditMode() instead
	 * @see BaseComponent::isEditMode()
	 */
	protected $isEditMode = false;
	/**
	 * @var bool
	 * @deprecated Use $this->isCopyMode() instead
	 * @see BaseComponent::isCopyMode()
	 */
	protected $isCopyMode = false;
	//---

	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->userID = \CCrmSecurityHelper::GetCurrentUserID();
		$this->userPermissions = \CCrmPerms::GetCurrentUserPermissions();

		$userFieldEntityID = $this->getUserFieldEntityID();
		if($userFieldEntityID !== '')
		{
			$this->userType = new \CCrmUserType(Main\Application::getUserTypeManager(), $userFieldEntityID);
			$this->userFieldDispatcher = Main\UserField\Dispatcher::instance();
		}
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
		$currentUserPermissions = $options['userPermissions'] ?? null;
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
		if($entityID > 0)
		{
			$requisites =  isset($entityData['requisites']) && is_array($entityData['requisites'])
				? $entityData['requisites']  : array();
			if(!empty($requisites))
			{
				$entityRequisites = array();
				$entityBankDetails = array();
				EntityRequisite::intertalizeFormData(
					$requisites,
					$entityTypeID,
					$entityRequisites,
					$entityBankDetails
				);
				if (!empty($entityRequisites) || !empty($entityBankDetails))
				{
					EntityRequisite::saveFormData(
						$entityTypeID,
						$entityID,
						$entityRequisites,
						$entityBankDetails
					);
				}
			}

			if (isset($options['startWorkFlows']) && $options['startWorkFlows'])
			{
				\CCrmBizProcHelper::AutoStartWorkflows(
					$entityTypeID,
					$entityID,
					\CCrmBizProcEventType::Create,
					$arErrors
				);
			}
		}
		return $entityID;
	}

	public static function updateEntity($entityTypeID, $entityID, array $entityData, array $options = array())
	{
		if(empty($entityData))
		{
			return false;
		}

		$currentUserPermissions = $options['userPermissions'] ?? null;
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
					foreach(['PHONE', 'EMAIL'] as $multifieldType)
					{
						$multifieldItems = $multifields[$multifieldType] ?? [];
						$presentMultifieldsItems = $presentMultifields[$multifieldType] ?? [];

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

		$requisites =  isset($entityData['requisites']) && is_array($entityData['requisites'])
			? $entityData['requisites']  : array();

		if(empty($fields) && empty($requisites))
		{
			return false;
		}

		$result = true;
		if(!empty($fields))
		{
			$result = $entity->update($entityID, $fields);
		}
		if($result)
		{
			if(!empty($requisites))
			{
				$entityRequisites = array();
				$entityBankDetails = array();
				EntityRequisite::intertalizeFormData(
					$requisites,
					$entityTypeID,
					$entityRequisites,
					$entityBankDetails
				);
				if (!empty($entityRequisites) || !empty($entityBankDetails))
				{
					EntityRequisite::saveFormData(
						$entityTypeID,
						$entityID,
						$entityRequisites,
						$entityBankDetails
					);
				}
			}

			if (isset($options['startWorkFlows']) && $options['startWorkFlows'])
			{
				\CCrmBizProcHelper::AutoStartWorkflows(
					$entityTypeID,
					$entityID,
					\CCrmBizProcEventType::Edit,
					$arErrors
				);
			}
		}
		return $result;
	}

	public static function deleteEntity(int $entityTypeID, $entityID, array $options = array())
	{
		$entity = Crm\Entity\EntityManager::resolveByTypeID($entityTypeID);
		if(!$entity)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		$entity->delete($entityID, $options);
	}

	public static function prepareMultifieldsForSave($entityTypeID, $entityID, array $multifieldData)
	{
		$multifields = [];
		if ($entityID > 0)
		{
			$multifields = static::getMultifields($entityTypeID, $entityID);
		}

		$counter = 0;
		foreach($multifieldData as $item)
		{
			$ID = isset($item['ID']) ? (int)$item['ID'] : 0;
			$typeID = $item['TYPE_ID'] ?? '';
			$value = $item['VALUE'] ?? '';
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

				$value = '';
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

		$multifields = [];
		while($fields = $dbResult->Fetch())
		{
			$typeID = $fields['TYPE_ID'];
			if(!isset($multifields[$typeID]))
			{
				$multifields[$typeID] = [];
			}

			$multifields[$typeID][$fields['ID']] = [
				'VALUE' => $fields['VALUE'] ?? '',
				'VALUE_TYPE' => $fields['VALUE_TYPE'] ?? ''
			];
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
			$value = $fields['VALUE'] ?? '';
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

		return $value ?? $default;
	}

	protected static function getUser($userID)
	{
		return Crm\Service\Container::getInstance()->getUserBroker()->getById($userID);
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
		$this->errorCollection[] = new Main\Error($this->getErrorMessage($error));
	}

	protected function getErrorMessage($error)
	{
		return ComponentError::getMessage($error);
	}

	protected function showErrors()
	{
		$messages = array();
		foreach($this->errorCollection as $error)
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

			if ($this->getConversionWizard())
			{
				$this->mode = ComponentMode::CONVERSION;
			}
			else
			{
				$this->mode = ComponentMode::CREATION;
			}
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

	protected function getConversionWizard(): ?Crm\Conversion\EntityConversionWizard
	{
		if (!$this->conversionWizard)
		{
			$this->conversionWizard = $this->initializeConversionWizard();
		}

		return $this->conversionWizard;
	}

	protected function initializeConversionWizard(): ?Crm\Conversion\EntityConversionWizard
	{
		return null;
	}

	protected function isConversionMode(): bool
	{
		return ($this->mode === ComponentMode::CONVERSION);
	}

	protected function isEditMode(): bool
	{
		return ($this->mode === ComponentMode::MODIFICATION);
	}

	protected function isCopyMode(): bool
	{
		return ($this->mode === ComponentMode::COPING);
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

	/**
	 * Return data from UserFieldTable about user fields of the current entity
	 *
	 * @return array
	 */
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

	/**
	 * Returns user fields description for the editor
	 *
	 * @return array
	 * @noinspection ReturnTypeCanBeDeclaredInspection
	 */
	public function prepareEntityUserFieldInfos()
	{
		if($this->userFieldInfos !== null)
		{
			return $this->userFieldInfos;
		}

		$this->userFieldInfos = Crm\Service\EditorAdapter::prepareEntityUserFields(
			$this->prepareEntityUserFields(),
			$this->prepareEntityFieldvisibilityConfigs($this->getEntityTypeID()),
			$this->getEntityTypeID(),
			$this->getEntityID(),
			$this->getFileHandlerUrl()
		);

		return $this->userFieldInfos;
	}

	protected function getComponentName(): string
	{
		return str_replace('bitrix:', '', $this->getName());
	}

	protected function getEntitySelectorContext(): string
	{
		return $this->getComponentName();
	}
}
