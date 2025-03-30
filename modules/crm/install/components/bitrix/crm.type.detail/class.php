<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Component\Base;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Model\Dynamic\Type;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service;
use Bitrix\Crm\UserField\UserFieldManager;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\Buttons;
use Bitrix\UI\Toolbar\ButtonLocation;

if (!Loader::includeModule('crm'))
{
	return;
}

class CrmTypeDetailComponent extends Base
{
	private const TAB_IDS = [
		'custom-section',
		'common',
		'fields',
		'relation',
		'user-fields',
		// 'conversion',
	];

	/** @var Type */
	protected $type;

	public function onPrepareComponentParams($arParams): array
	{
		$this->fillParameterFromRequest('entityTypeId', $arParams);
		$this->fillParameterFromRequest('automatedSolutionId', $arParams);
		$this->fillParameterFromRequest('activeTabId', $arParams);

		return parent::onPrepareComponentParams($arParams);
	}

	protected function init(): void
	{
		parent::init();

		if ($this->getErrors())
		{
			return;
		}

		$userPermissions = $this->userPermissions;
		$entityTypeId = (int) $this->arParams['entityTypeId'];
		$automatedSolutionId = (!empty($this->arParams['automatedSolutionId']) && (int)$this->arParams['automatedSolutionId'] > 0)
			? (int)$this->arParams['automatedSolutionId']
			: null
		;
		if($entityTypeId > 0)
		{
			$this->type = Service\Container::getInstance()->getTypeByEntityTypeId($entityTypeId);
			if(!$this->type)
			{
				$this->errorCollection[] = new Error(Loc::getMessage('CRM_TYPE_TYPE_NOT_FOUND'));
				return;
			}

			$requestUrl = $this->request->getRequestUri();
			$consistentUrl = Service\Container::getInstance()->getRouter()->getConsistentUrlFromPartlyDefined($requestUrl);
			if ($consistentUrl)
			{
				LocalRedirect($consistentUrl->getUri());
				return;
			}

			if (!$userPermissions->canUpdateType($this->type->getEntityTypeId()))
			{
				$this->errorCollection[] = new Error(Loc::getMessage('CRM_TYPE_TYPE_ACCESS_DENIED'));
			}
			else
			{
				$this->getApplication()->setTitle(
					Loc::getMessage('CRM_TYPE_TYPE_EDIT_TITLE', [
						'#TITLE#' => htmlspecialcharsbx($this->type->getTitle()),
					])
				);
			}
		}
		elseif($userPermissions->canAddType($automatedSolutionId))
		{
			$this->type = $this->createDraftType();
			$this->getApplication()->setTitle(Loc::getMessage('CRM_TYPE_DETAIL_NEW_TITLE'));
		}
		else
		{
			$this->errorCollection[] = new Error(Loc::getMessage('CRM_TYPE_TYPE_ACCESS_DENIED'));
		}
	}

	protected function createDraftType(): Type
	{
		/** @var Type $type */
		$type = Service\Container::getInstance()->getDynamicTypeDataClass()::createObject();
		$type->unset('CREATED_TIME');
		$type->unset('UPDATED_TIME');
		$type->unset('CREATED_BY');
		$type->unset('UPDATED_BY');

		if (!empty($this->arParams['automatedSolutionId']) && (int)$this->arParams['automatedSolutionId'] > 0)
		{
			$type->setCustomSectionId($this->arParams['automatedSolutionId']);
		}

		return $type;
	}

	public function executeComponent()
	{
		$this->init();

		if($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		$this->arResult['type'] = $this->getType()->jsonSerialize();
		$this->arResult['isCategoriesControlDisabled'] = false;
		$this->arResult['isRecyclebinControlDisabled'] = false;
		if ($this->type->getIsCategoriesEnabled() && $this->type->getId() > 0)
		{
			$factory = Service\Container::getInstance()->getFactory($this->type->getEntityTypeId());
			if ($factory)
			{
				$categories = $factory->getCategories();
				if (count($categories) > 1)
				{
					$this->arResult['isCategoriesControlDisabled'] = true;
				}
			}
		}
		if ($this->type->getIsRecyclebinEnabled() && $this->type->getId() > 0)
		{
			$recyblebinController = Bitrix\Crm\Recycling\DynamicController::getInstance($this->type->getEntityTypeId());
			if ($recyblebinController->countItemsInRecycleBin() > 0)
			{
				$this->arResult['isRecyclebinControlDisabled'] = true;
			}
		}
		$this->arResult['presets'] = Service\Container::getInstance()->getTypePresetBroker()->getList();
		$this->arResult['presetCategories'] = Service\Container::getInstance()->getTypePresetBroker()->getCategories();
		$this->arResult['listUrl'] = Service\Container::getInstance()->getRouter()->getTypeListUrl()->getUri();
		$this->arResult['relations'] = $this->getRelations();
		$this->arResult['isCustomSectionsAvailable'] = Integration\IntranetManager::isCustomSectionsAvailable();
		$this->arResult['linkedUserFields'] = $this->getLinkedUserFields();
		$this->arResult['isExternal'] = $this->request->get('isExternal') === 'Y';
		if (!$this->type->getId())
		{
			$this->arResult['canEditAutomatedSolution'] = $this->arResult['isExternal']
				? $this->userPermissions->canEditAutomatedSolutions()
				: $this->userPermissions->isCrmAdmin();
		}
		else
		{
			$this->arResult['canEditAutomatedSolution'] = $this->type->getCustomSectionId() > 0 ? $this->userPermissions->canEditAutomatedSolutions() : $this->userPermissions->isCrmAdmin();
		}
		$this->arResult['canToggleAutomatedSolutionSwitcher'] = $this->userPermissions->canEditAutomatedSolutions() && $this->userPermissions->isCrmAdmin();

		$this->initializeRestrictionValues();

		$this->arResult['activeTabId'] = $this->arParams['activeTabId'] ?? null;
		if (!in_array($this->arResult['activeTabId'], self::TAB_IDS, true))
		{
			$this->arResult['activeTabId'] = $this->arResult['isExternal'] ? 'custom-section' : 'common';
		}

		$this->arResult['permissionsUrl'] = $this->router->getEntityPermissionsUrl($this->type->getEntityTypeId());

		$this->includeComponentTemplate();
	}

	public function getType(): Type
	{
		return $this->type;
	}

	protected function getRelations(): array
	{
		$relationManager = Service\Container::getInstance()->getRelationManager();
		$parent = [];
		$child = [];
		$entityTypeId = $this->type->getEntityTypeId();
		$availableTypes = $relationManager->getAvailableForParentBindingEntityTypes($entityTypeId);
		foreach ($availableTypes as $typeId => $type)
		{
			$relation = $relationManager->getRelation(new RelationIdentifier($typeId, $entityTypeId));
			$parent[] = [
				'title' => $type['title'],
				'entityTypeId' => $typeId,
				'isChecked' => $relation ? true : false,
				'isChildrenListEnabled' => $relation ? $relation->isChildrenListEnabled() : false,
			];
		}
		$availableTypes = $relationManager->getAvailableForChildBindingEntityTypes($entityTypeId);
		foreach ($availableTypes as $typeId => $type)
		{
			$relation = $relationManager->getRelation(new RelationIdentifier($entityTypeId, $typeId));
			$child[] = [
				'title' => $type['title'],
				'entityTypeId' => $typeId,
				'isChecked' => $relation ? true : false,
				'isChildrenListEnabled' => $relation ? $relation->isChildrenListEnabled() : false,
			];
		}

		return [
			'parent' => $parent,
			'child' => $child,
		];
	}

	protected function getEntityTitle(int $entityTypeId): string
	{
		$factory = Service\Container::getInstance()->getFactory($entityTypeId);
		if ($factory)
		{
			return $factory->getEntityDescription();
		}

		return \CCrmOwnerType::GetDescription($entityTypeId);
	}

	protected function getPresets(): array
	{
		$presets = [];

		foreach (Service\Container::getInstance()->getTypePresetBroker()->getList() as $preset)
		{
			$presets[] = $preset->jsonSerialize();
		}

		return $presets;
	}

	protected function getToolbarParameters(): array
	{
		$parameters = [];
		$parameters['buttons'][ButtonLocation::RIGHT][] = new Buttons\Button([
			'color' => Buttons\Color::LIGHT_BORDER,
			'text' => Loc::getMessage('CRM_COMMON_HELP'),
			'onclick' => new Buttons\JsHandler('BX.Crm.Router.Instance.openTypeHelpPage'),
		]);

		return array_merge(parent::getToolbarParameters(), $parameters);
	}

	protected function getLinkedUserFields(): array
	{
		$linkedDescriptions = UserFieldManager::getLinkedUserFieldsDescription();
		$entityTypeName = \CCrmOwnerType::ResolveName($this->type->getEntityTypeId());

		$result = [];
		foreach (UserFieldManager::getLinkedUserFieldsMap() as $userFieldName => $userField)
		{
			$isEnabled = (!$this->type->isNew() && UserFieldManager::isEntityEnabledInUserField($userField, $entityTypeName));

			$result[$userFieldName] = $linkedDescriptions[$userFieldName];
			$result[$userFieldName]['isEnabled'] = $isEnabled;
		}

		return $result;
	}

	protected function initializeRestrictionValues(): void
	{
		$restrictions = RestrictionManager::getDynamicTypesLimitRestriction();
		$isRestricted = $restrictions->isCreateTypeRestricted();

		$this->arResult['isRestricted'] = $isRestricted;
		$this->arResult['restrictionErrorMessage'] = $isRestricted
			? $restrictions->getCreateTypeRestrictedError()->getMessage()
			: ''
		;
		$this->arResult['restrictionSliderCode'] = $isRestricted && $restrictions->canShowRestrictionSlider()
			? $restrictions->getCreateTypeRestrictionSliderCode()
			: ''
		;
	}
}
