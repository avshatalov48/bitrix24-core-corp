<?php /** @noinspection ReturnTypeCanBeDeclaredInspection */

namespace Bitrix\Crm\Component\EntityDetails;

use Bitrix\Crm;
use Bitrix\Crm\Component\ComponentError;
use Bitrix\Crm\Conversion;
use Bitrix\Crm\Entity\Traits\VisibilityConfig;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Security\EntityPermissionType;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\UI\EntitySelector;
use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

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
	/** @var Conversion\EntityConversionWizard|null */
	protected $conversionWizard;
	/** @var Crm\ItemIdentifier|null */
	protected $conversionSource;

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
		$result = static::createClient($entityTypeID, $entityData, $options);
		if ($result->isSuccess())
		{
			return (int)$result->getData()['id'];
		}

		return 0;
	}

	public static function updateEntity($entityTypeID, $entityID, array $entityData, array $options = array())
	{
		$result = static::updateClient(new Crm\ItemIdentifier(
				$entityTypeID,
				$entityID
			), $entityData, $options
		);

		return $result->isSuccess();
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
		foreach ($multifieldData as $item)
		{
			$ID = isset($item['ID']) ? (int)$item['ID'] : 0;
			$typeID = $item['TYPE_ID'] ?? '';
			$value = $item['VALUE'] ?? '';
			if ($typeID === '')
			{
				continue;
			}

			if ($ID <= 0 && $value === '')
			{
				continue;
			}

			if ($typeID === 'EMAIL' && !check_email($value))
			{
				if ($ID <= 0)
				{
					continue;
				}

				$value = '';
			}

			$valueCountryCode = $typeID === 'PHONE' ? $item['VALUE_COUNTRY_CODE'] : '';

			if (!isset($multifields[$typeID]))
			{
				$multifields[$typeID] = [];
			}

			if ($ID > 0)
			{
				$valueType = isset($multifields[$typeID][$ID]) && $multifields[$typeID][$ID]['VALUE_TYPE']
					? $multifields[$typeID][$ID]['VALUE_TYPE']
					: 'WORK';

				$multifields[$typeID][$ID] = [
					'VALUE' => $value,
					'VALUE_TYPE' => $valueType,
					'VALUE_COUNTRY_CODE' => $valueCountryCode,
				];
			}
			else
			{
				$multifields[$typeID]["n{$counter}"] = [
					'VALUE' => $value,
					'VALUE_TYPE' => 'WORK',
					'VALUE_COUNTRY_CODE' => $valueCountryCode,
				];

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
				'VALUE_TYPE' => $fields['VALUE_TYPE'] ?? '',
			];
		}

		return $multifields;
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
		if (!$error instanceof Main\Error)
		{
			$error = new Main\Error($this->getErrorMessage($error));
		}

		$this->errorCollection[] = $error;
	}

	protected function getErrorMessage($error)
	{
		return ComponentError::getMessage($error);
	}

	protected function showErrors()
	{
		$messages = array();
		/** @var Error $error */
		foreach($this->errorCollection as $error)
		{
			$message = $error->getMessage();
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

	protected function getConversionWizard(): ?Conversion\EntityConversionWizard
	{
		if (is_null($this->conversionWizard))
		{
			$this->conversionWizard = $this->initializeConversionWizard();
		}

		return $this->conversionWizard;
	}

	protected function getConversionSource(): ?Crm\ItemIdentifier
	{
		if (is_null($this->conversionSource))
		{
			$this->initializeConversionWizard();
		}

		return $this->conversionSource;
	}

	protected function initializeConversionWizard(): ?Conversion\EntityConversionWizard
	{
		$wizard = null;

		if (!is_null($this->conversionSource))
		{
			$wizard = $this->initializeConversionWizardFromSource($this->conversionSource);
		}

		if (is_null($wizard))
		{
			$wizard = $this->initializeConversionWizardFromRequest($this->request);
		}

		if (is_null($wizard) && !empty($this->arParams['CONVERSION_SOURCE']))
		{
			$wizard = Conversion\ConversionManager::loadWizardByParams($this->arParams['CONVERSION_SOURCE']);
		}

		if (!$wizard || !$wizard->isConvertingTo($this->getEntityTypeID()))
		{
			return null;
		}

		$this->conversionSource = new Crm\ItemIdentifier($wizard->getEntityTypeID(), $wizard->getEntityID());

		$wizard->setSliderEnabled(true);

		return $wizard;
	}

	private function initializeConversionWizardFromSource(
		Crm\ItemIdentifier $conversionSource
	): ?Conversion\EntityConversionWizard
	{
		$wizard = null;
		//todo temporary only for new invoices. remove after complete refactoring
		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($this->getEntityTypeID()))
		{
			$wizard = Conversion\ConversionManager::loadWizard(
				$conversionSource,
			);
		}
		else
		{
			$wizardClass = Conversion\ConversionManager::getWizardClass($conversionSource->getEntityTypeId());
			if ($wizardClass)
			{
				$wizard = $wizardClass::load($conversionSource->getEntityId());
			}
		}

		return $wizard;
	}

	//todo remove overwritten method in the child and inline this method after complete refactoring
	protected function initializeConversionWizardFromRequest(Main\Request $request): ?Conversion\EntityConversionWizard
	{
		return Conversion\ConversionManager::loadWizardByRequest($request);
	}

	protected function isPreviewItemBeforeCopyMode(): bool
	{
		return (int)$this->getRequestParamOrDefault('copy', 0) === 1;
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
		return EntitySelector::CONTEXT;
	}

	/**
	 * Creates new contact or company with $entityData.
	 * Data in Result
	 *
	 * @param int $entityTypeID
	 * @param array $entityData
	 * @param array $options
	 * @return Result
	 * @throws Main\NotSupportedException
	 */
	public static function createClient(int $entityTypeID, array $entityData, array $options = []): Result
	{
		$result = new Result();
		$resultData = [];

		Container::getInstance()->getLocalization()->loadMessages();
		$currentUserId = null;
		$currentUserPermissions = $options['userPermissions'] ?? null;
		if ($currentUserPermissions instanceof \CCrmPerms)
		{
			$currentUserId = $currentUserPermissions->GetUserID();
		}
		$userPermissions = Container::getInstance()->getUserPermissions($currentUserId);

		$checkPermissions = $options['checkPermissions'] ?? true;

		$title = trim($entityData['title'] ?? '');
		if (empty($title))
		{
			return $result->addError(new Main\Error('field title is empty'));
		}
		$isMyCompany = isset($entityData['isMyCompany']) && $entityData['isMyCompany'] === true;
		if (
			$checkPermissions
			&& (
				(
					!$isMyCompany
					&& !$userPermissions->checkAddPermissions($entityTypeID)
				)
				|| (
					$isMyCompany
					&& !$userPermissions->getMyCompanyPermissions()->canAdd()
				)
			)
		)
		{
			return $result->addError(new Main\Error(Main\Localization\Loc::getMessage('CRM_COMMON_ERROR_ACCESS_DENIED')));
		}

		if ($entityTypeID === \CCrmOwnerType::Company)
		{
			$fields = [
				'TITLE' => $title,
				'CATEGORY_ID' => isset($entityData['categoryId']) ? (int)$entityData['categoryId'] : 0,
			];
			$fields['OPENED'] = \Bitrix\Crm\Settings\CompanySettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
			if ($isMyCompany)
			{
				$fields['IS_MY_COMPANY'] = 'Y';
			}
		}
		elseif($entityTypeID === \CCrmOwnerType::Contact)
		{
			$fields = [];
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
			$fields['CATEGORY_ID'] = isset($entityData['categoryId']) ? (int)$entityData['categoryId'] : 0;
			$fields['OPENED'] = \Bitrix\Crm\Settings\ContactSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		$multifieldData = (isset($entityData['multifields']) && is_array($entityData['multifields']))
			? $entityData['multifields']
			: []
		;

		if (!empty($multifieldData))
		{
			$multifields = BaseComponent::prepareMultifieldsForSave($entityTypeID, 0, $multifieldData);
			if (!empty($multifields))
			{
				$fields['FM'] = $multifields;
			}
		}

		$entity = Crm\Entity\EntityManager::resolveByTypeID($entityTypeID);
		if (!$entity)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}
		$entityID = $entity->create($fields);
		if ($entityID > 0)
		{
			$resultData['id'] = (int)$entityID;
			$requisites = (isset($entityData['requisites']) && is_array($entityData['requisites']))
				? $entityData['requisites']
				: []
			;
			if (!empty($requisites))
			{
				$entityRequisites = [];
				$entityBankDetails = [];
				EntityRequisite::intertalizeFormData(
					$requisites,
					$entityTypeID,
					$entityRequisites,
					$entityBankDetails
				);
				if (!empty($entityRequisites) || !empty($entityBankDetails))
				{
					$saveRequisitesResult = EntityRequisite::saveFormData(
						$entityTypeID,
						$entityID,
						$entityRequisites,
						$entityBankDetails
					);
					if ($saveRequisitesResult->isSuccess())
					{
						$resultData = array_merge($resultData, $saveRequisitesResult->getData());
					}
					// error from requisites processing are not passed further
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

		return $result->setData($resultData);
	}

	/**
	 * @param Crm\ItemIdentifier $identifier
	 * @param array $entityData
	 * @param array $options
	 * @return Result
	 * @throws Main\ArgumentException
	 * @throws Main\NotImplementedException
	 * @throws Main\NotSupportedException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function updateClient(Crm\ItemIdentifier $identifier, array $entityData, array $options = []): Result
	{
		$result = new Result();
		$resultData = [];

		if (empty($entityData))
		{
			return $result->addError(new Main\Error('empty entityData'));
		}

		Container::getInstance()->getLocalization()->loadMessages();
		$currentUserId = null;
		$currentUserPermissions = $options['userPermissions'] ?? null;
		if ($currentUserPermissions instanceof \CCrmPerms)
		{
			$currentUserId = $currentUserPermissions->GetUserID();
		}
		$userPermissions = Container::getInstance()->getUserPermissions($currentUserId);
		$checkPermissions = $options['checkPermissions'] ?? true;

		$entityTypeID = $identifier->getEntityTypeId();
		$entityID = $identifier->getEntityId();

		$isMyCompany = isset($entityData['isMyCompany']) && $entityData['isMyCompany'] === true;
		if (
			$checkPermissions
			&& (
				(
					!$isMyCompany
					&& !$userPermissions->checkUpdatePermissions($entityTypeID, $entityID)
				)
				|| (
					$isMyCompany
					&& !$userPermissions->getMyCompanyPermissions()->canUpdate()
				)
			)
		)
		{
			return $result->addError(new Main\Error(Main\Localization\Loc::getMessage('CRM_COMMON_ERROR_ACCESS_DENIED')));
		}

		$entity = Crm\Entity\EntityManager::resolveByTypeID($entityTypeID);
		if (!$entity)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		$presentFields = $entity->getByID($entityID);
		if (!is_array($presentFields))
		{
			return $result->addError(new Main\Error(Main\Localization\Loc::getMessage('CRM_TYPE_ITEM_NOT_FOUND')));
		}

		$fields = [];
		$title = trim($entityData['title'] ?? '');
		if ($title !== '')
		{
			if ($entityTypeID === \CCrmOwnerType::Company)
			{
				if (!isset($presentFields['TITLE']) || $presentFields['TITLE'] !== $title)
				{
					$fields['TITLE'] = $title;
				}
			}
			elseif ($entityTypeID === \CCrmOwnerType::Contact)
			{
				$nameFormatID = Crm\Format\PersonNameFormatter::getFormatID();
				$nameFormat = Crm\Format\PersonNameFormatter::getFormat();

				if ($title !== \CCrmContact::PrepareFormattedName($presentFields))
				{
					if ($title === \CCrmContact::GetDefaultName())
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

					if (
						isset($presentFields['NAME'])
						&& (!isset($fields['NAME']) || $fields['NAME'] === '')
						&& !Crm\Format\PersonNameFormatter::hasFirstName($nameFormat)
					)
					{
						$fields['NAME'] = $presentFields['NAME'];
					}

					if (
						isset($presentFields['SECOND_NAME'])
						&& (!isset($fields['SECOND_NAME']) || $fields['SECOND_NAME'] === '')
						&& !Crm\Format\PersonNameFormatter::hasSecondName($nameFormat)
					)
					{
						$fields['SECOND_NAME'] = $presentFields['SECOND_NAME'];
					}

					if (
						isset($presentFields['LAST_NAME'])
						&& (!isset($fields['LAST_NAME']) || $fields['LAST_NAME'] === '')
						&& !Crm\Format\PersonNameFormatter::hasLastName($nameFormat)
					)
					{
						$fields['LAST_NAME'] = $presentFields['LAST_NAME'];
					}

					if(
						Crm\Comparer\ComparerBase::areFieldsEquals($fields, $presentFields, 'NAME')
						&& Crm\Comparer\ComparerBase::areFieldsEquals($fields, $presentFields, 'SECOND_NAME')
						&& Crm\Comparer\ComparerBase::areFieldsEquals($fields, $presentFields, 'LAST_NAME')
					)
					{
						$fields = [];
					}
				}
			}
			else
			{
				$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
				throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
			}
		}

		$multifieldData = (isset($entityData['multifields']) && is_array($entityData['multifields']))
			? $entityData['multifields']
			: []
		;

		if (!empty($multifieldData))
		{
			$multifields = BaseComponent::prepareMultifieldsForSave($entityTypeID, $entityID, $multifieldData);
			if (!empty($multifields))
			{
				$presentMultifields = static::getMultifields($entityTypeID, $entityID);
				if (count($presentMultifields) === count($multifields))
				{
					$areMultifieldsEquals = true;
					foreach(['PHONE', 'EMAIL'] as $multifieldType)
					{
						$multifieldItems = $multifields[$multifieldType] ?? [];
						$presentMultifieldsItems = $presentMultifields[$multifieldType] ?? [];

						foreach($multifieldItems as $multifieldID => $multifieldData)
						{
							if(
								!isset($presentMultifieldsItems[$multifieldID])
								|| $presentMultifieldsItems[$multifieldID]['VALUE'] !== $multifieldData['VALUE']
							)
							{
								$areMultifieldsEquals = false;
								break;
							}
						}

						if (!$areMultifieldsEquals)
						{
							break;
						}
					}

					if ($areMultifieldsEquals)
					{
						$multifields = [];
					}
				}

				if (!empty($multifields))
				{
					$fields['FM'] = $multifields;
				}
			}
		}

		$requisites = (isset($entityData['requisites']) && is_array($entityData['requisites']))
			? $entityData['requisites']
			: []
		;

		if (empty($fields) && empty($requisites))
		{
			return $result->setData($resultData);
		}

		$isSuccess = true;
		if (!empty($fields))
		{
			$isSuccess = $entity->update($entityID, $fields);
		}
		if ($isSuccess)
		{
			if (!empty($requisites))
			{
				$entityRequisites = [];
				$entityBankDetails = [];
				EntityRequisite::intertalizeFormData(
					$requisites,
					$entityTypeID,
					$entityRequisites,
					$entityBankDetails
				);
				if (!empty($entityRequisites) || !empty($entityBankDetails))
				{
					$saveRequisitesResult = EntityRequisite::saveFormData(
						$entityTypeID,
						$entityID,
						$entityRequisites,
						$entityBankDetails
					);
					if ($saveRequisitesResult->isSuccess())
					{
						$resultData = array_merge($resultData, $saveRequisitesResult->getData());
					}
					// error from requisites processing are not passed further
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

		return $result->setData($resultData);
	}
}
