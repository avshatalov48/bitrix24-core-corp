<?php

namespace Bitrix\Crm\Restriction;

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\Buttons\JsHandler;

class DynamicTypesLimit
{
	public const FEATURE_SLIDER_NAME = 'limit_smart_process_automation';
	public const FEATURE_NAME = 'crm_smart_processes';

	public const ERROR_CODE_CREATE_TYPE_RESTRICTED = 'CREATE_DYNAMIC_TYPE_RESTRICTED';
	public const ERROR_CODE_UPDATE_TYPE_RESTRICTED = 'UPDATE_DYNAMIC_TYPE_RESTRICTED';
	public const ERROR_CODE_CREATE_ITEM_RESTRICTED = 'CREATE_DYNAMIC_ITEM_RESTRICTED';
	public const ERROR_CODE_UPDATE_ITEM_RESTRICTED = 'UPDATE_DYNAMIC_ITEM_RESTRICTED';

	protected $isEnabled;
	/** @var Feature */
	protected $feature = Feature::class;

	public function __construct()
	{
		$this->isEnabled = $this->includeModule();
	}

	protected function includeModule(): bool
	{
		try
		{
			return Loader::includeModule('bitrix24');
		}
		catch(LoaderException $exception)
		{
			return false;
		}
	}

	/**
	 * Return true if this restriction is enabled.
	 *
	 * @return bool
	 */
	public function isEnabled(): bool
	{
		return $this->isEnabled;
	}

	/**
	 * Return true if creating of new dynamic types is restricted.
	 *
	 * @return bool
	 */
	public function isCreateTypeRestricted(): bool
	{
		return !$this->isFeatureEnabled();
	}

	/**
	 * Return true if creating of new item of type with $entityTypeId is restricted.
	 *
	 * @param int $entityTypeId
	 * @return bool
	 */
	public function isCreateItemRestricted(int $entityTypeId): bool
	{
		if (!\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			return false;
		}

		return !$this->isFeatureEnabled();
	}

	/**
	 * Return true if updating of an item of type with $entityTypeId is restricted.
	 *
	 * @param int $entityTypeId
	 * @return bool
	 */
	public function isUpdateItemRestricted(int $entityTypeId): bool
	{
		if (!\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			return false;
		}

		return !$this->isFeatureEnabled();
	}

	/**
	 * Return true if config of type with $entityTypeId is restricted.
	 *
	 * @param int $entityTypeId
	 * @return bool
	 */
	public function isTypeSettingsRestricted(int $entityTypeId): bool
	{
		if (!\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			return false;
		}

		return !$this->isFeatureEnabled();
	}

	protected function isFeatureEnabled(): bool
	{
		if ($this->isEnabled())
		{
			return $this->feature::isFeatureEnabled(static::FEATURE_NAME);
		}

		return true;
	}

	public function getCreateTypeRestrictedError(): Error
	{
		return new Error(
			Loc::getMessage('CRM_RESTRICTION_DYNAMIC_TYPES_CREATE_RESTRICTED'),
			static::ERROR_CODE_CREATE_TYPE_RESTRICTED
		);
	}

	public function getUpdateTypeRestrictedError(): Error
	{
		return new Error(
			Loc::getMessage('CRM_RESTRICTION_DYNAMIC_TYPES_UPDATE_RESTRICTED'),
			static::ERROR_CODE_UPDATE_TYPE_RESTRICTED
		);
	}

	public function getCreateItemRestrictedError(): Error
	{
		return new Error(
			Loc::getMessage('CRM_RESTRICTION_DYNAMIC_TYPES_ITEM_CREATE_RESTRICTED'),
			static::ERROR_CODE_CREATE_ITEM_RESTRICTED
		);
	}

	public function getUpdateItemRestrictedError(): Error
	{
		return new Error(
			Loc::getMessage('CRM_RESTRICTION_DYNAMIC_TYPES_ITEM_UPDATE_RESTRICTED'),
			static::ERROR_CODE_UPDATE_ITEM_RESTRICTED
		);
	}

	public function getShowFeatureJsHandler(): JsHandler
	{
		return new JsHandler('BX.Crm.Router.Instance.showFeatureSlider');
	}

	public function getShowFeatureJsFunctionString(): string
	{
		return 'BX.Crm.Router.Instance.showFeatureSlider();';
	}
}
