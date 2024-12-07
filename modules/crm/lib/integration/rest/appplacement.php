<?php

namespace Bitrix\Crm\Integration\Rest;

use Bitrix\Crm\Integration;
use Bitrix\Crm\Service\Container;

/**
 * Class AppPlacement
 *
 * Mostly rest placements in CRM are handled by intranet binding menu API. Therefore, it is recommended to look at
 * @see Integration\Intranet\BindingMenu firstly.
 *
 * This class contains codes of rest app placements that are somehow exceptional and handled by CRM only.
 */
class AppPlacement
{
	public const ANALYTICS_MENU = 'CRM_ANALYTICS_MENU';
	public const REQUISITE_EDIT_FORM = 'CRM_REQUISITE_EDIT_FORM';
	public const ONEC_PAGE = '1C_PAGE';
	public const DETAIL_SEARCH = 'CRM_DETAIL_SEARCH';
	public const REQUISITE_AUTOCOMPLETE = 'CRM_REQUISITE_AUTOCOMPLETE';
	public const BANK_DETAIL_AUTOCOMPLETE = 'CRM_BANK_DETAIL_AUTOCOMPLETE';

	//region Grid context actions
	/**
	 * For old entities, this placement is handled by crm exclusively.
	 * But for smart processes, it's handled by intranet.binding.menu
	 * For consistency, use of the mentioned method is recommended.
	 *
	 * @see AppPlacement::getListMenuPlacementCode()
	 */

	/** @deprecated Use the method instead */
	public const LEAD_LIST_MENU = 'CRM_LEAD_LIST_MENU';
	/** @deprecated Use the method instead */
	public const DEAL_LIST_MENU = 'CRM_DEAL_LIST_MENU';
	/** @deprecated Use the method instead */
	public const INVOICE_LIST_MENU = 'CRM_INVOICE_LIST_MENU';
	/** @deprecated Use the method instead */
	public const QUOTE_LIST_MENU = 'CRM_QUOTE_LIST_MENU';
	/** @deprecated Use the method instead */
	public const CONTACT_LIST_MENU = 'CRM_CONTACT_LIST_MENU';
	/** @deprecated Use the method instead */
	public const COMPANY_LIST_MENU = 'CRM_COMPANY_LIST_MENU';
	/** @deprecated Use the method instead */
	public const ACTIVITY_LIST_MENU = 'CRM_ACTIVITY_LIST_MENU';
	//endregion

	//region Additional apps tabs on a detail page
	/**
	 * Use the method below instead of these deprecated consts
	 * @see AppPlacement::getDetailTabPlacementCode()
	 */

	/** @deprecated Use the method instead */
	public const LEAD_DETAIL_TAB = 'CRM_LEAD_DETAIL_TAB';
	/** @deprecated Use the method instead */
	public const DEAL_DETAIL_TAB = 'CRM_DEAL_DETAIL_TAB';
	/** @deprecated Use the method instead */
	public const CONTACT_DETAIL_TAB = 'CRM_CONTACT_DETAIL_TAB';
	/** @deprecated Use the method instead */
	public const COMPANY_DETAIL_TAB = 'CRM_COMPANY_DETAIL_TAB';
	/** @deprecated Use the method instead */
	public const ORDER_DETAIL_TAB = 'CRM_ORDER_DETAIL_TAB';
	//endregion

	//region Additional apps tabs in timeline on a detail page
	/**
	 * Use the method below instead of these deprecated consts
	 * @see AppPlacement::getDetailActivityPlacementCode()
	 */

	/** @deprecated Use the method instead */
	public const LEAD_DETAIL_ACTIVITY = 'CRM_LEAD_DETAIL_ACTIVITY';
	/** @deprecated Use the method instead */
	public const DEAL_DETAIL_ACTIVITY = 'CRM_DEAL_DETAIL_ACTIVITY';
	/** @deprecated Use the method instead */
	public const CONTACT_DETAIL_ACTIVITY = 'CRM_CONTACT_DETAIL_ACTIVITY';
	/** @deprecated Use the method instead */
	public const COMPANY_DETAIL_ACTIVITY = 'CRM_COMPANY_DETAIL_ACTIVITY';
	/** @deprecated Use the method instead */
	public const ORDER_DETAIL_ACTIVITY = 'CRM_ORDER_DETAIL_ACTIVITY';
	//endregion

	//region 'Applications' button in toolbar on a detail page. Now handled by intranet.binding.menu
	/**
	 * Use the method below instead of these deprecated consts
	 * @see AppPlacement::getDetailToolbarPlacementCode()
	 */

	/** @deprecated Use the method instead. */
	public const LEAD_DETAIL_TOOLBAR = 'CRM_LEAD_DETAIL_TOOLBAR';
	/** @deprecated Use the method instead. */
	public const DEAL_DETAIL_TOOLBAR = 'CRM_DEAL_DETAIL_TOOLBAR';
	/** @deprecated Use the method instead. */
	public const CONTACT_DETAIL_TOOLBAR = 'CRM_CONTACT_DETAIL_TOOLBAR';
	/** @deprecated Use the method instead. */
	public const COMPANY_DETAIL_TOOLBAR = 'CRM_COMPANY_DETAIL_TOOLBAR';
	//endregion

	/**
	 * Returns code of rest placement that is located in context menu of grid row
	 *
	 * @param int $entityTypeId
	 *
	 * @return string
	 */
	public static function getListMenuPlacementCode(int $entityTypeId): string
	{
		return Integration\Intranet\BindingMenu\CodeBuilder::getRestPlacementCode(
			Integration\Intranet\BindingMenu\SectionCode::GRID_CONTEXT_ACTIONS,
			$entityTypeId
		);
	}

	/**
	 * Returns code of rest placement that can be used to add new tabs to element detail page
	 *
	 * @param int $entityTypeId
	 *
	 * @return string
	 */
	public static function getDetailTabPlacementCode(int $entityTypeId): string
	{
		$entityTypeName = mb_strtoupper(\CCrmOwnerType::ResolveName($entityTypeId));

		return "CRM_{$entityTypeName}_DETAIL_TAB";
	}

	/**
	 * Returns code of rest placement that can be used to add new tabs to timeline of element detail page
	 *
	 * @param int $entityTypeId
	 *
	 * @return string
	 */
	public static function getDetailActivityPlacementCode(int $entityTypeId): string
	{
		$entityTypeName = mb_strtoupper(\CCrmOwnerType::ResolveName($entityTypeId));

		return "CRM_{$entityTypeName}_DETAIL_ACTIVITY";
	}

	/**
	 * Returns code of rest placement that can be used to add new items to the 'Applications' button in toolbar
	 * of element detail page
	 *
	 * @param int $entityTypeId
	 *
	 * @return string
	 */
	public static function getDetailToolbarPlacementCode(int $entityTypeId): string
	{
		return Integration\Intranet\BindingMenu\CodeBuilder::getRestPlacementCode(
			Integration\Intranet\BindingMenu\SectionCode::DETAIL,
			$entityTypeId,
		);
	}

	public static function getAllForType(int $entityTypeId): array
	{
		return [
			static::getListMenuPlacementCode($entityTypeId),
			static::getDetailTabPlacementCode($entityTypeId),
			static::getDetailActivityPlacementCode($entityTypeId),
		];
	}

	public static function getAll()
	{
		return array_merge(
			static::getAllListMenuCodes(),
			static::getAllDetailTabCodes(),
			static::getAllDetailActivityCodes(),
			[
				self::ANALYTICS_MENU,
				self::REQUISITE_EDIT_FORM,
				self::ONEC_PAGE,
				self::DETAIL_SEARCH,
				self::REQUISITE_AUTOCOMPLETE,
				self::BANK_DETAIL_AUTOCOMPLETE,
			],
		);
	}

	/**
	 * @return string[]
	 */
	private static function getAllListMenuCodes(): array
	{
		$supportedEntityTypesList = [
			\CCrmOwnerType::Lead,
			\CCrmOwnerType::Deal,
			\CCrmOwnerType::Invoice,
			\CCrmOwnerType::Quote,
			\CCrmOwnerType::Contact,
			\CCrmOwnerType::Company,
			\CCrmOwnerType::Activity,
			\CCrmOwnerType::SmartInvoice,
		];

		static::mixinDynamicEntityTypes($supportedEntityTypesList);

		$listMenuCodes = [];
		foreach ($supportedEntityTypesList as $entityTypeId)
		{
			$listMenuCodes[] = static::getListMenuPlacementCode($entityTypeId);
		}

		return $listMenuCodes;
	}

	/**
	 * @return string[]
	 */
	private static function getAllDetailTabCodes(): array
	{
		$supportedEntityTypesList = [
			\CCrmOwnerType::Lead,
			\CCrmOwnerType::Deal,
			\CCrmOwnerType::Quote,
			\CCrmOwnerType::Contact,
			\CCrmOwnerType::Company,
			\CCrmOwnerType::Order,
			\CCrmOwnerType::SmartInvoice,
		];

		static::mixinDynamicEntityTypes($supportedEntityTypesList);

		$detailTabCodes = [];
		foreach ($supportedEntityTypesList as $entityTypeId)
		{
			$detailTabCodes[] = static::getDetailTabPlacementCode($entityTypeId);
		}

		return $detailTabCodes;
	}

	/**
	 * @return string[]
	 */
	public static function getAllDetailActivityCodes(): array
	{
		$supportedEntityTypesList = [
			\CCrmOwnerType::Lead,
			\CCrmOwnerType::Deal,
			\CCrmOwnerType::Quote,
			\CCrmOwnerType::Contact,
			\CCrmOwnerType::Company,
			\CCrmOwnerType::Order,
			\CCrmOwnerType::SmartInvoice,
		];

		static::mixinDynamicEntityTypes($supportedEntityTypesList);

		$detailActivityCodes = [];
		foreach ($supportedEntityTypesList as $entityTypeId)
		{
			$detailActivityCodes[] = static::getDetailActivityPlacementCode($entityTypeId);
		}

		return $detailActivityCodes;
	}

	private static function mixinDynamicEntityTypes(array &$entityTypeIdList): void
	{
		$dynamicTypesMap = Container::getInstance()->getDynamicTypesMap()->load([
			'isLoadStages' => false,
			'isLoadCategories' => false,
		]);

		foreach ($dynamicTypesMap->getTypes() as $type)
		{
			$entityTypeIdList[] = $type->getEntityTypeId();
		}
	}
}
