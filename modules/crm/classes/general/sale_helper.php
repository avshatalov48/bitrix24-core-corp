<?php

use Bitrix\Catalog\Access\ShopGroupAssistant;
use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\Config\State;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Loader;
use Bitrix\Crm;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Main;
use Bitrix\Main\UserTable;
use Bitrix\Sale;
use Bitrix\Catalog\Access\Permission\Catalog\IblockCatalogPermissions;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Catalog\StoreDocumentTable;

class CCrmSaleHelper
{
	public const CROUP_PREFIX = 'CRM_SHOP_';
	public const GROUP_CRM_ADMIN = 'ADMIN';
	public const GROUP_CRM_MANAGER = 'MANAGER';

	private static $userIdsWithShopAccess = [];

	public static function getGroupCode(string $code): string
	{
		return self::CROUP_PREFIX.$code;
	}

	public static function Calculate($productRows, $currencyID, $personTypeID, $enableSaleDiscount = false, $siteId = SITE_ID, $arOptions = array())
	{
		if(!CModule::IncludeModule('sale'))
		{
			return array('err'=> '1');
		}

		$saleUserId = intval(CSaleUser::GetAnonymousUserID());
		if ($saleUserId <= 0)
		{
			return array('err'=> '2');
		}

		if(!is_array($productRows) && empty($productRows))
		{
			return array('err'=> '3');
		}

		$bTaxMode = isset($arOptions['ALLOW_LD_TAX']) ? $arOptions['ALLOW_LD_TAX'] === 'Y' : CCrmTax::isTaxMode();
		if ($bTaxMode)
		{
			foreach ($productRows as &$productRow)
			{
				$productRow['TAX_RATE'] = 0.0;
				$productRow['TAX_INCLUDED'] = 'N';
			}
			unset($productRow);
		}

		$cartItems = self::PrepareShoppingCartItems($productRows, $currencyID, $siteId);
		foreach ($cartItems as &$item) // tmp hack not to update basket quantity data from catalog
		{
			$item['ID_TMP'] = $item['ID'] ?? null;
			unset($item['ID']);
		}
		unset($item);

		$errors = array();
		$cartItems = Bitrix\Crm\Invoice\Compatible\BasketHelper::doGetUserShoppingCart(
			$siteId, $saleUserId, $cartItems, $errors, 0, true
		);

		foreach ($cartItems as &$item)
		{
			$item['ID'] = $item['ID_TMP'];
			unset($item['ID_TMP']);
		}
		unset($item);

		$personTypeID = intval($personTypeID);
		if($personTypeID <= 0)
		{
			$personTypes = CCrmPaySystem::getPersonTypeIDs();
			if (isset($personTypes['CONTACT']))
			{
				$personTypeID = intval($personTypes['CONTACT']);
			}
		}

		if ($personTypeID <= 0)
		{
			return array('err'=> '4');
		}

		$orderPropsValues = array();
		$paySystemId = 0;
		if (is_array($arOptions) && !empty($arOptions))
		{
			if (isset($arOptions['LOCATION_ID']) && CCrmTax::isTaxMode())
			{
				$locationPropertyID = self::getLocationPropertyId($personTypeID);
				if ($locationPropertyID !== false)
					$orderPropsValues[$locationPropertyID] = $arOptions['LOCATION_ID'];
			}
			if (isset($arOptions['PAY_SYSTEM_ID']))
				$paySystemId = intval($arOptions['PAY_SYSTEM_ID']);
		}
		$warnings = array();

		$options = array(
			'CURRENCY' => $currencyID
		);
		if(!$enableSaleDiscount)
		{
			$options['CART_FIX'] = 'Y';
		}

		if (!is_array($cartItems))
		{
			$cartItems = [];
		}

		$arOrder = CSaleOrder::makeOrderArray($siteId, $saleUserId, $cartItems, $options);

		$invoiceCompatible = \Bitrix\Crm\Invoice\Compatible\Invoice::create($arOrder);
		$options['ORDER'] = $invoiceCompatible->getOrder();

		$result = CSaleOrder::DoCalculateOrder(
			$siteId,
			$saleUserId,
			$cartItems,
			$personTypeID,
			$orderPropsValues,
			0,
			$paySystemId,
			$options,
			$errors,
			$warnings
		);

		if ($bTaxMode)
		{
			$totalTax = isset($result['TAX_VALUE']) ? round(doubleval($result['TAX_VALUE']), 2) : 0.0;
			$totalModified = false;
			$taxes = (is_array($result['TAX_LIST'])) ? $result['TAX_LIST'] : null;
			$moneyFormat = CCurrencyLang::GetCurrencyFormat($currencyID);
			$moneyDecimals = isset($moneyFormat['DECIMALS']) ?  intval($moneyFormat['DECIMALS']) : 2;
			unset($moneyFormat);
			if (is_array($taxes))
			{
				foreach ($taxes as $taxInfo)
				{
					if ($taxInfo["IS_IN_PRICE"] == "Y")
					{
						$taxValue = roundEx($taxInfo["VALUE_MONEY"], $moneyDecimals);
						$totalTax += $taxValue;
						$totalModified = true;
					}
				}
			}
			if ($totalModified)
				$result['TAX_VALUE'] = $totalTax;
		}

		return $result;
	}
	private static function PrepareShoppingCartItems(&$productRows, $currencyID, $siteId)
	{
		$items = array();

		foreach($productRows as $k => &$v)
		{
			$item = array();
			$item['PRODUCT_ID'] = isset($v['PRODUCT_ID']) ? intval($v['PRODUCT_ID']) : 0;

			$isCustomized = isset($v['CUSTOMIZED']) && $v['CUSTOMIZED'] === 'Y';
			if($item['PRODUCT_ID'] > 0 && !$isCustomized)
			{
				$item['MODULE'] = 'catalog';
				$item['PRODUCT_PROVIDER_CLASS'] = 'CCatalogProductProvider';
			}
			else
			{
				$item['MODULE'] = $item['PRODUCT_PROVIDER_CLASS'] = '';
			}

			if($isCustomized)
			{
				$item['CUSTOM_PRICE'] = 'Y';
			}

			$item['TABLE_ROW_ID'] = $k;

			$item['QUANTITY'] = isset($v['QUANTITY']) ? doubleval($v['QUANTITY']) : 0;
			$item['QUANTITY_DEFAULT'] = $item['QUANTITY'];

			$taxRate = isset($v['TAX_RATE']) ? round(doubleval($v['TAX_RATE']), 2) : 0.0;
			$inclusivePrice = isset($v['PRICE']) ? doubleval($v['PRICE']) : 0.0;
			$exclusivePrice = isset($v['PRICE_EXCLUSIVE'])
				? doubleval($v['PRICE_EXCLUSIVE'])
				: (($taxRate !== 0.0)
					? CCrmProductRow::CalculateExclusivePrice($inclusivePrice, $taxRate)
					: $inclusivePrice);
			$isTaxIncluded = isset($v['TAX_INCLUDED']) && $v['TAX_INCLUDED'] === 'Y';

			$item['VAT_INCLUDED'] = $isTaxIncluded ? 'Y' : 'N';
			$item['PRICE'] = $isTaxIncluded ? $inclusivePrice : $exclusivePrice;
			$item['PRICE_DEFAULT'] = $item['PRICE'];

			$item['CURRENCY'] = $currencyID;

			// discount info
			$item['CRM_PR_FIELDS'] = array();
			$item['CRM_PR_FIELDS']['DISCOUNT_TYPE_ID'] = isset($v['DISCOUNT_TYPE_ID']) ?
				intval($v['DISCOUNT_TYPE_ID']) : \Bitrix\Crm\Discount::PERCENTAGE;
			$item['CRM_PR_FIELDS']['DISCOUNT_RATE'] = isset($v['DISCOUNT_RATE']) ?
				round(doubleval($v['DISCOUNT_RATE']), 2) : 0.0;
			$item['CRM_PR_FIELDS']['DISCOUNT_SUM'] = isset($v['DISCOUNT_SUM']) ?
				round(doubleval($v['DISCOUNT_SUM']), 2) : 0.0;

			// tax info
			$allowLDTax = CCrmTax::isTaxMode();
			if ($allowLDTax)
			{
				$item['CRM_PR_FIELDS']['TAX_RATE'] = 0.0;
				$item['CRM_PR_FIELDS']['TAX_INCLUDED'] = 'N';
			}
			else
			{
				$item['CRM_PR_FIELDS']['TAX_RATE'] = $taxRate;
				$item['CRM_PR_FIELDS']['TAX_INCLUDED'] =
					(isset($v['TAX_INCLUDED']) && $v['TAX_INCLUDED'] === 'Y') ? 'Y' : 'N';
			}

			// price netto, price brutto
			$priceNetto = 0.0;
			if (isset($v['PRICE_NETTO']) && $v['PRICE_NETTO'] != 0.0)
			{
				$priceNetto = doubleval($v['PRICE_NETTO']);
			}
			else
			{
				if($item['CRM_PR_FIELDS']['DISCOUNT_TYPE_ID'] === \Bitrix\Crm\Discount::MONETARY)
				{
					$priceNetto = $exclusivePrice + $item['CRM_PR_FIELDS']['DISCOUNT_SUM'];
				}
				else
				{
					$discoutRate = $item['CRM_PR_FIELDS']['DISCOUNT_RATE'];
					$discoutSum = $discoutRate < 100
						? \Bitrix\Crm\Discount::calculateDiscountByDiscountPrice($exclusivePrice, $discoutRate)
						: $item['CRM_PR_FIELDS']['DISCOUNT_SUM'];
					$priceNetto = $exclusivePrice + $discoutSum;
				}
			}
			$item['CRM_PR_FIELDS']['PRICE_NETTO'] = round($priceNetto, 2);

			if ($item['CRM_PR_FIELDS']['DISCOUNT_SUM'] === 0.0)
			{
				$item['CRM_PR_FIELDS']['PRICE_BRUTTO'] = $item['PRICE'];
			}
			else
			{
				if (isset($v['PRICE_BRUTTO']) && $v['PRICE_BRUTTO'] != 0.0)
				{
					$item['CRM_PR_FIELDS']['PRICE_BRUTTO'] = round(doubleval($v['PRICE_BRUTTO']), 2);
				}
				else
				{
					$item['CRM_PR_FIELDS']['PRICE_BRUTTO'] = round(
						CCrmProductRow::CalculateInclusivePrice($priceNetto, $item['CRM_PR_FIELDS']['TAX_RATE']), 2);
				}
			}

			if(isset($v['VAT_RATE']))
			{
				$item['VAT_RATE'] = $v['VAT_RATE'];
			}
			elseif(isset($v['TAX_RATE']))
			{
				$item['VAT_RATE'] = $v['TAX_RATE'] / 100;
			}

			if(isset($v['MEASURE_CODE']))
			{
				$item['MEASURE_CODE'] = $v['MEASURE_CODE'];
			}

			if(isset($v['MEASURE_NAME']))
			{
				$item['MEASURE_NAME'] = $v['MEASURE_NAME'];
			}

			$item['NAME'] = isset($v['NAME']) ? $v['NAME'] : (isset($v['PRODUCT_NAME']) ? $v['PRODUCT_NAME'] : '');
			$item['LID'] = $siteId;
			$item['CAN_BUY'] = 'Y';

			$items[] = &$item;
			unset($item);
		}
		unset($v);

		return $items;
	}
	private static function getLocationPropertyId($personTypeId)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$locationPropertyId = null;
		$dbRes = \Bitrix\Crm\Invoice\Property::getList([
			'select' => ['ID'],
			'filter' => [
				'PERSON_TYPE_ID' => $personTypeId,
				'ACTIVE' => 'Y',
				'TYPE' => 'LOCATION',
				'IS_LOCATION' => 'Y',
				'IS_LOCATION4TAX' => 'Y'
			],
			'order' => ['SORT' => 'ASC']
		]);

		if ($arOrderProp = $dbRes->fetch())
		{
			$locationPropertyId = $arOrderProp['ID'];
		}
		else
		{
			return false;
		}
		$locationPropertyId = intval($locationPropertyId);
		if ($locationPropertyId <= 0)
			return false;
		return $locationPropertyId;
	}

	public static function getShopGroupIdByType($type): ?int
	{
		$groupId = null;
		$queryObject = CGroup::getList("ID", "ASC", array("STRING_ID" => "CRM_SHOP_".mb_strtoupper($type)));
		if ($group = $queryObject->fetch())
		{
			$groupId = (int)$group["ID"];
		}
		return $groupId;
	}

	/**
	 * @param string $role
	 * @return bool
	 * @throws ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws SqlQueryException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function isShopAccess(string $role = ""): bool
	{
		if (!Loader::includeModule('catalog'))
		{
			return false;
		}

		global $USER;
		if (!is_object($USER))
		{
			return false;
		}

		$userId = $USER->getID();
		if (!$userId)
		{
			return false;
		}

		if ($role !== 'admin' && $role !== 'manager')
		{
			return self::isShopAccess('manager') || self::isShopAccess('admin');
		}

		if (self::isCacheAccess($userId, $role))
		{
			return self::getCacheAccess($userId, $role);
		}

		$action =
			$role === 'admin'
				? ActionDictionary::ACTION_CATALOG_SETTINGS_ACCESS
				: ActionDictionary::ACTION_CATALOG_READ
		;

		if (!AccessController::getCurrent()->check($action))
		{
			self::addToCacheAccess($userId, $role, false);

			return false;
		}

		self::addToCacheAccess($userId, $role, true);

		if (!AccessController::getCurrent()->hasIblockAccess($action))
		{
			self::addShopAccessByUserId($userId);
		}

		return true;
	}

	private static function addToCacheAccess(int $userId, string $role, bool $access): void
	{
		self::$userIdsWithShopAccess[$userId] = self::$userIdsWithShopAccess[$userId] ?? [];
		self::$userIdsWithShopAccess[$userId][$role] = $access;
	}

	private static function isCacheAccess(int $userId, string $role): bool
	{
		return (isset(self::$userIdsWithShopAccess[$userId][$role]));
	}

	private static function getCacheAccess(int $userId, string $role): bool
	{
		return self::$userIdsWithShopAccess[$userId][$role];
	}

	/**
	 * @deprecated
	 *
	 * @param CUser $user
	 * @param string $role
	 * @return bool
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 * @throws LoaderException
	 */
	private static function isCrmAccess(CUser $user, $role = "")
	{
		$userId = $user->getID();
		if ($user->isAdmin() || (Loader::includeModule("bitrix24") && CBitrix24::IsPortalAdmin($userId)))
		{
			return true;
		}
		else
		{
			if ($role == "admin")
			{
				return self::checkAdminAccess($userId);
			}
			else
			{
				return self::checkManagerAccess($userId);
			}
		}
	}

	/**
	 * @param int $userId
	 * @return bool
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private static function checkAdminAccess(int $userId)
	{
		$listUserId = self::getListUserIdFromCrmRoles();
		if (in_array($userId, $listUserId))
		{
			$CrmPerms = new CCrmPerms($userId);
			if ($CrmPerms->havePerm("CONFIG", BX_CRM_PERM_CONFIG, "WRITE"))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param int $userId
	 * @return bool
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private static function checkManagerAccess(int $userId)
	{
		$listUserId = self::getListUserIdFromCrmRoles();
		return (in_array($userId, $listUserId));
	}

	private static function getShopRole($userId)
	{
		$user = new CUser();
		$groups = $user->getUserGroup($userId);
		if (in_array(1, $groups) || (Loader::includeModule("bitrix24") && CBitrix24::isPortalAdmin($userId)))
		{
			return "admin";
		}
		else
		{
			$listUserId = self::getListUserIdFromCrmRoles();
			if (in_array($userId, $listUserId))
			{
				$CrmPerms = new CCrmPerms($userId);
				if ($CrmPerms->havePerm("CONFIG", BX_CRM_PERM_CONFIG, "WRITE"))
				{
					return "admin";
				}
				else
				{
					return "manager";
				}
			}
			return "";
		}
	}

	private static function addToDbAccess($userId, $shopRole)
	{
		$groupId = self::getShopGroupIdByType($shopRole);
		if ($groupId)
		{
			$queryObject = CUser::getByID($userId);
			$user = $queryObject->fetch();
			if ($user && $user['EXTERNAL_AUTH_ID'] !== '__controller')
			{
				CUser::appendUserGroup($userId, [$groupId]);
			}
		}
	}

	/**
	 * @param array $newUserIds List new user id for add to shop groups.
	 * @param array $currentUserIds List current user id from shop groups for delete.
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function addUserToShopGroup($newUserIds = [], $currentUserIds = [])
	{
		if (empty($newUserIds))
		{
			$newUserIds = self::getListUserIdFromCrmRoles(true);
		}

		self::deleteUserFromShopGroupByUserIds($currentUserIds);

		self::addUserToShopGroupByUserIds($newUserIds);
	}

	public static function deleteAllUserFromShopGroup()
	{
		self::deleteUserFromShopGroupByUserIds(self::getCurrentUsersShopGroups());
	}

	/**
	 * @deprecated
	 *
	 * Proxy for starting \Bitrix\Catalog\Access\Permission\Catalog\IblockCatalogPermissionStepper
	 *
	 * @return void
	 * @throws LoaderException
	 */
	public static function updateShopAccess()
	{
		if (Loader::includeModule('catalog'))
		{
			\Bitrix\Catalog\Access\Permission\Catalog\IblockCatalogPermissionStepper::bind(1);
		}
	}

	/**
	 * @deprecated
	 *
	 * Old binging for crm user to sale and iblock groups.
	 * Instead of it use \Bitrix\Catalog\Access\Permission\Catalog\IblockCatalogPermissionStepper.
	 *
	 * @return void
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public static function updateShopAccessByAgent(): void
	{
		if (Loader::includeModule('catalog'))
		{
			$userGroups = [
				'admin' => [],
				'manager' => [],
			];
			$userIds = self::getListUserIdFromCrmRoles(true);
			array_push($userIds, ...CGroup::getGroupUser(1));
			foreach ($userIds as $userId)
			{
				$groupCode = ShopGroupAssistant::getShopUserGroupCode($userId);
				if ($groupCode === self::CROUP_PREFIX . self::GROUP_CRM_ADMIN)
				{
					$userGroups['admin'][] = $userId;
				}
				elseif ($groupCode === self::CROUP_PREFIX . self::GROUP_CRM_MANAGER)
				{
					$userGroups['manager'][] = $userId;
				}
			}

			foreach ($userGroups as $groupId => $userIds)
			{
				self::updateShopAccessGroup($userIds, $groupId);
			}
		}
		else
		{
			self::deleteAllUserFromShopGroup();
		}
	}

	/**
	 * @deprecated
	 *
	 * @param array $userIds
	 * @param string $groupType
	 * @return void
	 */
	private static function updateShopAccessGroup(array $userIds, string $groupType): void
	{
		$groupCode = self::getShopGroupIdByType($groupType);
		$currentGroupUserIds = CGroup::getGroupUser($groupCode);
		$removeFromGroup = array_diff($currentGroupUserIds, $userIds);
		if ($removeFromGroup)
		{
			self::deleteUserFromShopGroupByUserIds($removeFromGroup, [$groupCode]);
		}

		$addToGroup = array_diff($userIds, $currentGroupUserIds);
		foreach ($addToGroup as $userId)
		{
			self::addToDbAccess($userId, $groupType);
		}
	}

	/**
	 * Used for appending user in agent
	 *
	 * @param $userId
	 * @return void
	 * @throws LoaderException
	 */
	public static function addShopAccessByUserId($userId)
	{
		if (Loader::includeModule("catalog"))
		{
			$emptyDepartmentTypeFirst = serialize([]);
			$emptyDepartmentTypeSecond = serialize([0]);
			$externalTypes = UserTable::getExternalUserTypes();
			$externalTypes[] = null;
			$filter = [
				'=ID' => $userId,
				'!=UF_DEPARTMENT' => [null, $emptyDepartmentTypeFirst, $emptyDepartmentTypeSecond],
				'!=EXTERNAL_AUTH_ID' => $externalTypes,
			];

			$userData = UserTable::getRow([
				'filter' => $filter,
				'select' => ['ID'],
			]);

			if (!$userData)
			{
				return;
			}

			ShopGroupAssistant::addShopAccess($userId);
		}
	}

	public static function runAgentAddGroupToShop()
	{
		global $APPLICATION;

		$groupObject = new CGroup;

		$groupsData = array(
			array(
				"ACTIVE" => "Y",
				"C_SORT" => 100,
				"NAME" => Loc::getMessage("SALE_USER_GROUP_SHOP_ADMIN_NAME"),
				"STRING_ID" => "CRM_SHOP_ADMIN",
				"DESCRIPTION" => Loc::getMessage("SALE_USER_GROUP_SHOP_ADMIN_DESC"),
				"BASE_RIGHTS" => array("sale" => "W"),
				"TASK_RIGHTS" => array("catalog" => "W", "main" => "R", "iblock" => "X")
			),
			array(
				"ACTIVE" => "Y",
				"C_SORT" => 100,
				"NAME" => Loc::getMessage("SALE_USER_GROUP_SHOP_MANAGER_NAME"),
				"STRING_ID" => "CRM_SHOP_MANAGER",
				"DESCRIPTION" => Loc::getMessage("SALE_USER_GROUP_SHOP_MANAGER_DESC"),
				"BASE_RIGHTS" => array("sale" => "U"),
				"TASK_RIGHTS" => array("catalog" => "W", "iblock" => "W")
			),
		);

		foreach ($groupsData as $groupData)
		{
			$groupId = $groupObject->add($groupData);
			if ($groupObject->LAST_ERROR == '' && $groupId)
			{
				foreach($groupData["BASE_RIGHTS"] as $moduleId => $letter)
				{
					$APPLICATION->setGroupRight($moduleId, $groupId, $letter, false);
				}
				foreach($groupData["TASK_RIGHTS"] as $moduleId => $letter)
				{
					switch ($moduleId)
					{
						case "iblock":
							if (CModule::includeModule("iblock"))
							{
								CIBlockRights::setGroupRight($groupId, "CRM_PRODUCT_CATALOG", $letter);
							}
							break;
						default:
							CGroup::SetModulePermission($groupId, $moduleId, CTask::GetIdByLetter($letter, $moduleId));
					}
				}
			}
		}

		CCrmSaleHelper::addUserToShopGroup();

		return "";
	}

	/**
	 * @param bool $resetCash Reset cache.
	 * @return array
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function getListUserIdFromCrmRoles($resetCash = false)
	{
		$listUserId = array();

		$cacheTime = 86400;
		$cacheId = "crm-list-crm-roles";
		$cacheDir = "/crm/list_crm_roles/";
		$cache = new CPHPCache;

		if (!$resetCash && $cache->initCache($cacheTime, $cacheId, $cacheDir))
		{
			$listUserId = $cache->getVars();
		}
		else
		{
			$objectQuery = CCrmRole::getRelation();
			while ($relation = $objectQuery->fetch())
			{
				$relationCode = $relation["RELATION"];
				if (preg_match('/^(U|IU)[0-9]+$/', $relationCode, $matches))
				{
					if (!empty($matches[1]))
					{
						$listUserId[str_replace($matches[1], "", $relationCode)] = true;
					}
				}
				elseif (preg_match('/^(G)[0-9]+$/', $relationCode, $matches))
				{
					if (!empty($matches[1]))
					{
						$groupId = str_replace($matches[1], "", $relationCode);
						foreach (CGroup::getGroupUser($groupId) as $userId)
						{
							$listUserId[$userId] = true;
						}
					}
				}
				elseif (preg_match('/^(D|DR)[0-9]+$/', $relationCode, $matches))
				{
					if (!empty($matches[1]))
					{
						$listDepartmentId = array();
						$listDepartmentId[] = str_replace($matches[1], "", $relationCode);
						if ($matches[1] == "DR" && CModule::includeModule("iblock"))
						{
							$currentDepartmentId = current($listDepartmentId);
							if ($currentDepartmentId)
							{
								$parentSectionObject = CIBlockSection::getList(array(),
									array("=ID" => $currentDepartmentId));
								$parentSection = $parentSectionObject->getNext();
								$sectionFilter = array (
									"LEFT_MARGIN" => $parentSection["LEFT_MARGIN"],
									"RIGHT_MARGIN" => $parentSection["RIGHT_MARGIN"],
									"IBLOCK_ID" => $parentSection["IBLOCK_ID"]
								);
								$sectionObject = CIBlockSection::getList(array("left_margin"=>"asc"), $sectionFilter);
								while($section = $sectionObject->getNext())
								{
									$listDepartmentId[] =  $section["ID"];
								}
							}
						}
						if ($listDepartmentId)
						{
							$connection = Bitrix\Main\Application::getConnection();
							if ($connection->isTableExists("b_user") && $connection->isTableExists("b_uts_user")
								&& $connection->isTableExists("b_utm_user"))
							{
								$strSql = "
								SELECT user.ID AS ID
								FROM b_user user
								LEFT JOIN b_uts_user uts_object ON user.ID = uts_object.VALUE_ID
								WHERE user.ID IN (SELECT inner_user.ID AS ID FROM b_user inner_user
								LEFT JOIN b_utm_user utm_object ON utm_object.VALUE_ID = inner_user.ID
								WHERE (utm_object.VALUE_INT in (".implode(',', $listDepartmentId).")))
								ORDER BY user.ID DESC
							";
								$result = $connection->query($strSql);
								while ($user = $result->fetch())
								{
									$listUserId[$user["ID"]] = true;
								}
							}
						}
					}
				}
				elseif (preg_match("/^SG([0-9]+)_[A-Z]$/", $relationCode, $matches) && CModule::includeModule("socialnetwork"))
				{
					$groupId = (int)$matches[1];
					$role = (isset($matches[2]) ? $matches[2] : "K");
					$userToGroup = Bitrix\Socialnetwork\UserToGroupTable::getList(array(
						"filter" => array("=GROUP_ID" => $groupId, "@ROLE" => $role),
						"select" => array("USER_ID")
					));
					while($user = $userToGroup->fetch())
					{
						$listUserId[$user["USER_ID"]] = true;
					}
				}
			}
			$listUserId = array_keys($listUserId);

			if (!empty($listUserId))
			{
				if ($cache->startDataCache())
				{
					$cache->endDataCache($listUserId);
				}
			}
		}

		return $listUserId;
	}

	public static function getCurrentUsersShopGroups()
	{
		$userIds = [];

		$shopGroupIds = [
			self::getShopGroupIdByType("admin"),
			self::getShopGroupIdByType("manager")
		];

		$queryObject = CUser::getList("ID", "asc",
			["GROUPS_ID" => $shopGroupIds], ["SELECT" => ["ID"]]);
		while ($user = $queryObject->fetch())
		{
			$userIds[] = $user["ID"];
		}

		return $userIds;
	}

	/**
	 * @param $userId
	 * @return int|null
	 */
	private static function getShopGroupIdByUserId($userId)
	{
		$CrmPerms = new CCrmPerms($userId);
		if ($CrmPerms->havePerm("CONFIG", BX_CRM_PERM_CONFIG, "WRITE"))
		{
			return self::getShopGroupIdByType("admin");
		}

		return self::getShopGroupIdByType("manager");
	}

	private static function addUserToShopGroupByUserIds($newUserIds)
	{
		if (!$newUserIds)
		{
			return;
		}

		global $USER;

		$currentUserId = 0;
		if ($USER instanceof \CUser)
		{
			$currentUserId = (int)$USER->GetID();
		}

		foreach ($newUserIds as $userId)
		{
			$groupId = self::getShopGroupIdByUserId($userId);
			if ($groupId)
			{
				CUser::appendUserGroup($userId, [$groupId]);
				if ($currentUserId == $userId)
				{
					$USER->CheckAuthActions();
				}
			}
		}
	}

	private static function deleteUserFromShopGroupByUserIds(array $currentUserIds = [], array $shopGroupIds = null): void
	{
		if (!$currentUserIds)
		{
			return;
		}

		$connection = Bitrix\Main\Application::getConnection();
		if ($connection->isTableExists("b_user_group"))
		{
			if (!$shopGroupIds)
			{
				$shopGroupIds = [
					self::getShopGroupIdByType("admin"),
					self::getShopGroupIdByType("manager")
				];
				$shopGroupIds = array_filter($shopGroupIds);

				if (!$shopGroupIds)
				{
					return;
				}
			}

			$result = array_search(1, $currentUserIds);
			if ($result !== false)
			{
				unset($currentUserIds[$result]);
			}
			if ($currentUserIds)
			{
				$strSql = "DELETE FROM b_user_group WHERE GROUP_ID IN (".
					implode(',', $shopGroupIds).") and USER_ID IN (" .implode(',', $currentUserIds) . ")";
				$connection->queryExecute($strSql);
			}
		}
	}

	/**
	 * @throws SqlQueryException
	 * @deprecated
	 */
	public static function deleteUserFromShopGroup()
	{
		if (IsModuleInstalled("bitrix24"))
		{
			$listGroupId = array(self::getShopGroupIdByType("admin"), self::getShopGroupIdByType("manager"));
			$connection = Bitrix\Main\Application::getConnection();
			if ($connection->isTableExists("b_user_group"))
			{
				foreach ($listGroupId as $groupId)
				{
					$groupId = intval($groupId);
					if ($groupId)
					{
						$listUserId = array();
						foreach (CGroup::getGroupUser($groupId) as $userId)
						{
							$listUserId[] = $userId;
						}
						if ($listUserId)
						{
							$strSql = "DELETE FROM b_user_group WHERE GROUP_ID = $groupId and USER_ID IN (" .
								implode(',', $listUserId) . ")";
							$connection->queryExecute($strSql);
						}
					}
				}
			}
		}
	}

	public static function divideInvoiceOrderPersonTypeAgent()
	{
		if (!\Bitrix\Main\Loader::includeModule('sale'))
		{
			return '';
		}

		$dbRes = \Bitrix\Crm\Invoice\PersonType::getList([
			'filter' => [
				'@CODE' => ['CRM_CONTACT', 'CRM_COMPANY']
			]
		]);

		if ($dbRes->fetch())
		{
			return '';
		}

		global $DB;

		$DB->Query("
			UPDATE
				b_sale_person_type
			SET
				ENTITY_REGISTRY_TYPE=NULL
			WHERE
				CODE='CRM_CONTACT'
				OR CODE='CRM_COMPANY'
		");

		$DB->Query("
			UPDATE
				b_sale_person_type
			SET
				ENTITY_REGISTRY_TYPE='CRM_INVOICE'
			WHERE (
				CODE='CRM_CONTACT'
				OR CODE='CRM_COMPANY'
			)
			AND ENTITY_REGISTRY_TYPE IS NULL
		");

		$dbRes = $DB->Query("SELECT id FROM b_sale_person_type WHERE CODE='CRM_CONTACT' OR CODE='CRM_COMPANY'");
		if ($dbRes->Fetch())
		{
			$dbRes = $DB->Query("SELECT id FROM b_sale_person_type WHERE ENTITY_REGISTRY_TYPE='ORDER'");
			if (!$dbRes->Fetch())
			{
				$DB->Query("
					INSERT INTO
						b_sale_person_type (LID, NAME, SORT, ACTIVE, CODE, ENTITY_REGISTRY_TYPE)
					SELECT
						bspt.LID, bspt.NAME, bspt.SORT, bspt.ACTIVE, bspt.CODE, 'ORDER'
					FROM
						b_sale_person_type  bspt
					WHERE
						bspt.CODE='CRM_CONTACT' OR bspt.CODE='CRM_COMPANY'
				");

				$DB->Query("
					INSERT INTO
						b_sale_person_type_site (PERSON_TYPE_ID, SITE_ID)
					SELECT
						 bspt2.ID, bspts.SITE_ID
					FROM
						b_sale_person_type_site bspts
					INNER JOIN b_sale_person_type bspt ON bspt.ID=bspts.PERSON_TYPE_ID
					INNER JOIN b_sale_person_type bspt2 ON bspt.CODE=bspt2.CODE AND bspt2.ENTITY_REGISTRY_TYPE='ORDER'
					WHERE(
							bspt.CODE='CRM_CONTACT'
							OR bspt.CODE='CRM_COMPANY'
						)
						AND bspt.ENTITY_REGISTRY_TYPE='CRM_INVOICE'
				");
			}
		}

		$DB->Query("
			UPDATE
				b_sale_person_type
			SET
				ENTITY_REGISTRY_TYPE='ORDER'
			WHERE
				ENTITY_REGISTRY_TYPE IS NULL
		");

		$DB->Query("
			UPDATE
				b_sale_order bso
			INNER JOIN b_sale_person_type bspt ON bso.PERSON_TYPE_ID=bspt.ID
			INNER JOIN b_sale_person_type bspt2 ON bspt.CODE=bspt2.CODE AND bspt2.ENTITY_REGISTRY_TYPE='ORDER'
			SET
				bso.PERSON_TYPE_ID=bspt2.ID
			WHERE(
				bspt.CODE='CRM_CONTACT'
				OR bspt.CODE='CRM_COMPANY'
			)
			AND bspt.ENTITY_REGISTRY_TYPE='CRM_INVOICE'
		");

		$DB->Query("
			UPDATE
				b_sale_order_props bsop
			INNER JOIN b_sale_person_type bspt ON bsop.PERSON_TYPE_ID=bspt.ID
			INNER JOIN b_sale_person_type bspt2 ON bspt.CODE=bspt2.CODE AND bspt2.ENTITY_REGISTRY_TYPE='ORDER'
			SET
				bsop.PERSON_TYPE_ID=bspt2.ID
			WHERE(
				bspt.CODE='CRM_CONTACT'
				OR bspt.CODE='CRM_COMPANY'
			)
			AND bsop.ENTITY_REGISTRY_TYPE='ORDER'
		");

		$dbRes = $DB->Query("SELECT * FROM b_sale_bizval_persondomain bsbp LEFT JOIN b_sale_person_type bspt ON bspt.ID=bsbp.PERSON_TYPE_ID WHERE bspt.ENTITY_REGISTRY_TYPE='CRM_INVOICE'");
		if (!$dbRes->Fetch())
		{
			$DB->Query("
				INSERT INTO
					b_sale_bizval_persondomain (PERSON_TYPE_ID, DOMAIN)
				SELECT
					 bspt2.ID, bspts.DOMAIN
				FROM
					b_sale_bizval_persondomain bspts
				INNER JOIN b_sale_person_type bspt ON bspt.ID=bspts.PERSON_TYPE_ID
				INNER JOIN b_sale_person_type bspt2 ON bspt.CODE=bspt2.CODE AND bspt2.ENTITY_REGISTRY_TYPE='ORDER'
				WHERE (
						bspt.CODE = 'CRM_CONTACT'
						OR bspt.CODE = 'CRM_COMPANY'
					)
					AND bspt.ENTITY_REGISTRY_TYPE='CRM_INVOICE'
			");
		}

		$DB->Query("
			UPDATE
				b_crm_order_props_form bcopf
			INNER JOIN b_sale_person_type bspt ON bcopf.PERSON_TYPE_ID=bspt.ID
			INNER JOIN b_sale_person_type bspt2 ON bspt.CODE=bspt2.CODE AND bspt2.ENTITY_REGISTRY_TYPE='ORDER'
			SET
				bcopf.PERSON_TYPE_ID=bspt2.ID
			WHERE(
				bspt.CODE='CRM_CONTACT'
				OR bspt.CODE='CRM_COMPANY'
			)
			AND bspt.ENTITY_REGISTRY_TYPE='CRM_INVOICE'
		");

		$DB->Query("
			UPDATE
				b_crm_order_props_form_queue bcopfq
			INNER JOIN b_sale_person_type bspt ON bcopfq.PERSON_TYPE_ID=bspt.ID
			INNER JOIN b_sale_person_type bspt2 ON bspt.CODE=bspt2.CODE AND bspt2.ENTITY_REGISTRY_TYPE='ORDER'
			SET
				bcopfq.PERSON_TYPE_ID=bspt2.ID
			WHERE(
				bspt.CODE='CRM_CONTACT'
				OR bspt.CODE='CRM_COMPANY'
			)
			AND bspt.ENTITY_REGISTRY_TYPE='CRM_INVOICE'
		");

		$dbRes = $DB->Query("SELECT bsopg.ID FROM b_sale_order_props_group bsopg
			LEFT JOIN b_sale_person_type bspt ON bsopg.PERSON_TYPE_ID=bspt.ID
			WHERE bspt.ENTITY_REGISTRY_TYPE='ORDER'
		");

		if (!$dbRes->Fetch())
		{
			$DB->Query("
				INSERT INTO b_sale_order_props_group(PERSON_TYPE_ID, SORT, NAME)
				SELECT bspt2.ID, bsopg.SORT, bsopg.NAME FROM b_sale_order_props_group bsopg
				LEFT JOIN b_sale_person_type bspt ON bspt.ID=bsopg.PERSON_TYPE_ID AND bspt.ENTITY_REGISTRY_TYPE='CRM_INVOICE'
				LEFT JOIN b_sale_person_type bspt2 ON bspt2.CODE=bspt.CODE AND bspt2.ENTITY_REGISTRY_TYPE='ORDER'
				WHERE bspt.CODE='CRM_CONTACT' OR  bspt.CODE='CRM_COMPANY'
			");

			$DB->Query("
				UPDATE b_sale_order_props bsop
				INNER JOIN b_sale_order_props_group bsopg ON bsopg.ID=bsop.PROPS_GROUP_ID
				INNER JOIN b_sale_order_props_group bsopg2 ON bsopg2.NAME=bsopg.NAME AND bsopg2.PERSON_TYPE_ID=bsop.PERSON_TYPE_ID
				SET bsop.PROPS_GROUP_ID=bsopg2.ID
				WHERE bsop.ENTITY_REGISTRY_TYPE='ORDER'
			");
		}

		$map = [];
		$dbRes = $DB->Query("
			SELECT
				bspt.ID as INVOICE_PT_ID, bspt2.ID as ORDER_PT_ID
			FROM
				b_sale_person_type bspt
			INNER JOIN b_sale_person_type bspt2 ON bspt.CODE=bspt2.CODE AND bspt2.ENTITY_REGISTRY_TYPE='ORDER'
			WHERE
			(
				bspt.CODE='CRM_CONTACT'
				OR bspt.CODE='CRM_COMPANY'
			)
			AND
			bspt.ENTITY_REGISTRY_TYPE='CRM_INVOICE'
		");
		while ($data = $dbRes->Fetch())
		{
			$map[$data['INVOICE_PT_ID']] = $data['ORDER_PT_ID'];
		}

		$classList = [
			'\\Bitrix\\Sale\\Services\\PaySystem\\Restrictions\\PersonType',
			'\\Bitrix\\Sale\\Delivery\\Restrictions\\PersonType'
		];
		foreach ($classList as $class)
		{
			$dbRes = $DB->Query("
				SELECT
					bssr.ID AS SR_ID, bssr.PARAMS AS SR_PARAMS
				FROM
					b_sale_service_rstr bssr
				INNER JOIN b_sale_pay_system_action bspsa ON bssr.SERVICE_ID=bspsa.ID AND bssr.SERVICE_TYPE=1
				WHERE
					bssr.CLASS_NAME='".$DB->ForSql($class)."'
					AND bspsa.ENTITY_REGISTRY_TYPE='ORDER'"
			);
			while ($data = $dbRes->Fetch())
			{
				$params = unserialize($data['SR_PARAMS'], ['allowed_classes' => false]);
				foreach ($params['PERSON_TYPE_ID'] as $key => $id)
				{
					if (isset($map[$id]))
					{
						$params['PERSON_TYPE_ID'][$key] = $map[$id];
					}
				}

				$DB->Query("UPDATE b_sale_service_rstr SET PARAMS='".serialize($params)."' WHERE ID=".$data['SR_ID']);
			}
		}

		return '';
	}

	/**
	 * @return bool
	 */
	public static function isEnabledReservation(): bool
	{
		if (Loader::includeModule("sale"))
		{
			return \Bitrix\Sale\Configuration::isEnabledReservation();
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public static function isWithOrdersMode(): bool
	{
		return Option::get('crm', 'enable_order_deal_create', 'N') === 'N';
	}

	public static function deactivateModeOrders() : bool
	{
		if (!Loader::includeModule('sale'))
		{
			return false;
		}

		Option::set('crm', 'enable_order_deal_create', 'Y');

		$eventManager = Main\EventManager::getInstance();

		$eventManager->registerEventHandler(
			'sale',
			Sale\Cashbox\CheckManager::EVENT_ON_CHECK_COLLATE_DOCUMENTS,
			'crm',
			'\Bitrix\Crm\Order\EventsHandler\Check',
			'OnCheckCollateDocuments'
		);

		$eventManager->registerEventHandler(
			'sale',
			'OnSaleShipmentEntitySaved',
			'crm',
			'\Bitrix\Crm\Order\EventsHandler\Shipment',
			'OnSaleShipmentEntitySaved'
		);

		self::clearCacheMenu();

		return true;
	}

	public static function activateModeOrders() : bool
	{
		if (!Loader::includeModule('sale'))
		{
			return false;
		}

		Option::set('crm', 'enable_order_deal_create', 'N');

		$eventManager = Main\EventManager::getInstance();

		$eventManager->unRegisterEventHandler(
			'sale',
			Sale\Cashbox\CheckManager::EVENT_ON_CHECK_COLLATE_DOCUMENTS,
			'crm',
			'\Bitrix\Crm\Order\EventsHandler\Check',
			'OnCheckCollateDocuments'
		);

		$eventManager->unRegisterEventHandler(
			'sale',
			'OnSaleShipmentEntitySaved',
			'crm',
			'\Bitrix\Crm\Order\EventsHandler\Shipment',
			'OnSaleShipmentEntitySaved'
		);

		Crm\Automation\Demo\Wizard::installOrderPresets();

		self::clearCacheMenu();

		return true;
	}

	private static function clearCacheMenu()
	{
		if (defined('BX_COMP_MANAGED_CACHE'))
		{
			$GLOBALS['CACHE_MANAGER']->ClearByTag('bitrix24_left_menu');
		}
	}

	/**
	 * Checks whether all the conditions for processing inventory management are met.
	 *
	 * ATTENTION: this is not a check whether inventory management is ENABLED,
	 * but a check whether it is POSSIBLE to use it.
	 *
	 * @return bool
	 */
	public static function isProcessInventoryManagement(): bool
	{
		return
			!static::isWithOrdersMode()
			&& RestrictionManager::getInventoryControlIntegrationRestriction()->hasPermission()
			&& Loader::includeModule('catalog')
			&& State::isUsedInventoryManagement()
		;
	}

	public static function isRealizationCreationAvailable(): bool
	{
		return (
			self::isProcessInventoryManagement()
			&& AccessController::getCurrent()->checkByValue(
				ActionDictionary::ACTION_STORE_DOCUMENT_MODIFY,
				StoreDocumentTable::TYPE_SALES_ORDERS
			)
		);
	}
}
