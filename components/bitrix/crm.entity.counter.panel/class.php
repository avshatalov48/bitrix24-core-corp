<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Crm\Counter\EntityCounter;
use Bitrix\Crm\Counter\EntityCounterFactory;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\MessageHelper;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\CounterSettings;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CCrmEntityCounterPanelComponent extends CBitrixComponent
{
	/**
	 * @var int
	 */
	protected $userID = 0;

	/**
	 * @var string
	 */
	protected $guid = '';

	/**
	 * @var string
	 */
	protected $entityTypeName = '';

	/**
	 * @var int
	 */
	protected $entityTypeID = CCrmOwnerType::Undefined;

	/**
	 * @var int
	 */
	protected int $categoryId = 0;

	/**
	 * @var array
	 */
	protected $extras = [];

	/**
	 * @var string
	 */
	protected $entityListUrl = '';

	/**
	 * @var array
	 */
	protected $errors = [];

	/**
	 * @var bool
	 */
	protected $isVisible = true;

	/**
	 * @var bool
	 */
	protected $recalculate = false;

	public function executeComponent()
	{
		$this->initialize();

		if ($this->isVisible)
		{
			foreach ($this->errors as $message)
			{
				ShowError($message);
			}

			$this->includeComponentTemplate();
		}
	}

	protected function initialize(): void
	{
		if (!Bitrix\Main\Loader::includeModule('crm'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED');

			return;
		}

		$this->userID = Container::getInstance()->getContext()->getUserId();
		$this->arResult['USER_ID'] = $this->userID;

		$dbUsers = CUser::GetList(
			'last_name',
			'asc',
			['ID' => $this->userID],
			['FIELDS' => ['ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'LOGIN', 'TITLE']]
		);

		$userFields = $dbUsers->Fetch();

		$this->arResult['USER_NAME'] =  is_array($userFields)
			? CUser::FormatName(CSite::GetNameFormat(false), $userFields)
			: "[{$this->userID}]";

		$this->guid = $this->arResult['GUID'] = $this->arParams['GUID'] ?? 'counter_panel';
		if (isset($this->arParams['ENTITY_TYPE_NAME']))
		{
			$this->entityTypeName = $this->arParams['ENTITY_TYPE_NAME'];
		}

		$this->entityTypeID = CCrmOwnerType::ResolveID($this->entityTypeName);

		if (!CCrmOwnerType::IsDefined($this->entityTypeID))
		{
			$this->errors[] = Loc::getMessage('CRM_COUNTER_ENTITY_TYPE_NOT_DEFINED');

			return;
		}

		if (isset($this->arParams['EXTRAS']) && is_array($this->arParams['EXTRAS']))
		{
			$this->extras = $this->arParams['EXTRAS'];
		}

		// setup category ID
		if (isset($this->arParams['EXTRAS']['DEAL_CATEGORY_ID']))
		{
			$this->categoryId = (int)$this->arParams['EXTRAS']['DEAL_CATEGORY_ID'];
		}
		elseif (isset($this->arParams['EXTRAS']['CATEGORY_ID']))
		{
			$this->categoryId = (int)$this->arParams['EXTRAS']['CATEGORY_ID'];
		}

		$this->isVisible = Container::getInstance()
			->getUserPermissions($this->userID)
			->checkReadPermissions($this->entityTypeID, 0, $this->categoryId)
		;

		if (isset($this->arParams['PATH_TO_ENTITY_LIST']))
		{
			$this->entityListUrl = $this->arParams['PATH_TO_ENTITY_LIST'];
		}

		$this->recalculate = isset($_REQUEST['recalc']) && mb_strtoupper($_REQUEST['recalc']) === 'Y';

		// fill current user data
		$data = [];
		$codes = [];
		$total = 0;
		$this->fillCountersData(
			$total,
			$codes,
			$data,
			$this->extras
		);

		// fill other users data
		$otherData = [];
		$otherCodes = [];
		$otherTotal = 0;
		$withExcludeUsers = false;
		if ($this->isOtherCountersSupported())
		{
			$withExcludeUsers = true;

			$this->fillCountersData(
				$otherTotal,
				$otherCodes,
				$otherData,
				array_merge($this->extras, ['EXCLUDE_USERS'=> true])
			);
		}

		$this->arResult['ENTITY_TYPE_ID'] = $this->entityTypeID;
		$this->arResult['CATEGORY_ID'] = $this->categoryId;
		$this->arResult['EXTRAS'] = $this->extras;
		$this->arResult['TOTAL'] = $total; 							// temporarily unused
		$this->arResult['OTHER_TOTAL'] = $otherTotal; 				// temporarily unused
		$this->arResult['CODES'] = array_merge($codes, $otherCodes);
		$this->arResult['DATA'] = array_merge($data, $otherData);
		$this->arResult['ENTITY_NUMBER_DECLENSIONS'] = MessageHelper::getEntityNumberDeclensionMessages($this->entityTypeID);
		$this->arResult['ENTITY_PLURALS'] = MessageHelper::getEntityPluralMessages($this->entityTypeID);
		$this->arResult['WITH_EXCLUDE_USERS'] = $withExcludeUsers;
		$this->arResult['FILTER_RESPONSIBLE_FILED_NAME'] = $this->filterResponsibleFiledName();
	}

	private function fillCountersData(int &$total, array &$codes, array &$data, array $extras): void
	{
		$allSupportedTypes = EntityCounterType::getAllSupported($this->entityTypeID, true);

		foreach ($allSupportedTypes as $typeId)
		{
			if (EntityCounterType::isGroupingForArray($typeId, $allSupportedTypes))
			{
				$codes[] = EntityCounter::prepareCode($this->entityTypeID, $typeId, $extras);

				continue;
			}

			$counter = EntityCounterFactory::create($this->entityTypeID, $typeId, $this->userID, $extras);
			$code = $counter->getCode();
			$value = $counter->getValue($this->recalculate);
			$data[$code] = array(
				'TYPE_ID' => $typeId,
				'TYPE_NAME' => EntityCounterType::resolveName($typeId),
				'CODE' => $code,
				'VALUE' => $value,
				'URL' => $counter->prepareDetailsPageUrl($this->entityListUrl)
			);

			$total += $value;

			$codes[] = $code;
		}
	}

	private function isOtherCountersSupported(): bool
	{
		$uPermissions = Container::getInstance()->getUserPermissions();
		$permissionEntityType = $uPermissions::getPermissionEntityType($this->entityTypeID, $this->categoryId);
		$hasAllPermissions = $uPermissions->isAdmin() || $uPermissions->getCrmPermissions()->GetPermType($permissionEntityType) >= $uPermissions::PERMISSION_ALL;

		$factory = Container::getInstance()->getFactory($this->entityTypeID);
		$isAllowedEntity = $factory && $factory->isCountersEnabled();

		return $isAllowedEntity && $hasAllPermissions;
	}

	private function filterResponsibleFiledName(): string
	{
		if ($this->entityTypeID === \CCrmOwnerType::Activity)
		{
			return 'RESPONSIBLE_ID';
		}

		if (CounterSettings::getInstance()->useActivityResponsible())
		{
			return 'ACTIVITY_RESPONSIBLE_IDS';
		}

		return ($this->entityTypeID === \CCrmOwnerType::Order ? 'RESPONSIBLE_ID' : 'ASSIGNED_BY_ID');
	}
}
