<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserGroupTable;

class CCrmSaleHelper
{
	private static $listUserIdWithShopAccess = array();
	private static $listUserIdWithSessionGroups = array();

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
			$item['ID_TMP'] = $item['ID'];
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

	public static function getShopGroupIdByType($type)
	{
		$groupId = null;
		$queryObject = CGroup::getList($by = "ID", $order = "ASC", array("STRING_ID" => "CRM_SHOP_".strtoupper($type)));
		if ($group = $queryObject->fetch())
		{
			$groupId = $group["ID"];
		}
		return $groupId;
	}

	/**
	 * @param string $type
	 * @return bool|mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function isShopAccess($type = "")
	{
		$shopEnabled = \Bitrix\Main\Config\Option::get("crm", "crm_shop_enabled", "N");
		if ($shopEnabled == "N")
		{
			return false;
		}

		CCrmInvoice::installExternalEntities();

		global $USER;

		if (!is_object($USER))
		{
			return false;
		}

		$userId = $USER->GetID();

		if (array_key_exists($userId, self::$listUserIdWithShopAccess))
		{
			if (isset(self::$listUserIdWithShopAccess[$userId][$type]))
			{
				if (!empty(self::$listUserIdWithShopAccess[$userId][$type]))
				{
					self::setCurrentUserToGroup(self::getShopGroupIdByType($type));
					return true;
				}
				else
				{
					return false;
				}
			}
		}

		if ($USER->isAdmin())
		{
			self::$listUserIdWithShopAccess[$userId] = array($type => true);
			self::setCurrentUserToGroup(self::getShopGroupIdByType("admin"));
			return true;
		}

		$listGroupId = array();
		if ($type)
		{
			$listGroupId[] = self::getShopGroupIdByType($type);
		}
		else
		{
			$listGroupId[] = self::getShopGroupIdByType("admin");
			$listGroupId[] = self::getShopGroupIdByType("manager");
		}

		if ($userId && $listGroupId)
		{
			$listCurrentGroupId = array();
			$groupListObject = CUser::getUserGroupList($userId);
			while($groupList = $groupListObject->fetch())
			{
				$listCurrentGroupId[] = $groupList["GROUP_ID"];
			}

			$isAccess = false;
			foreach ($listGroupId as $groupId)
			{
				if (in_array($groupId, $listCurrentGroupId))
				{
					$isAccess = true;
				}
			}

			$isAccess = ($isAccess ? $isAccess : self::setUserToShopGroup($userId, $type));

			if ($isAccess)
			{
				self::setCurrentUserToGroup(self::getShopGroupIdByType($type));
			}

			self::$listUserIdWithShopAccess[$userId] = array($type => $isAccess);
			return $isAccess;
		}
		else
		{
			self::$listUserIdWithShopAccess[$userId] = array($type => false);
			return false;
		}
	}

	/**
	 * @param $userId
	 * @param $type
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function setUserToShopGroup($userId, $type)
	{
		$isAccess = false;

		if (CModule::IncludeModule("bitrix24") && CBitrix24::IsPortalAdmin($userId))
		{
			self::addUserToShopGroup(array($userId));
			$isAccess = true;
		}
		else
		{
			$listUserId = self::getListUserIdFromCrmRoles();
			if (in_array($userId, $listUserId))
			{
				$CrmPerms = new CCrmPerms($userId);
				if ($type == "admin" && !$CrmPerms->havePerm("CONFIG", BX_CRM_PERM_CONFIG, "WRITE"))
				{
					$isAccess = false;
				}
				else
				{
					self::addUserToShopGroup(array($userId));
					$isAccess = true;
				}
			}
		}

		return $isAccess;
	}

	private static function setCurrentUserToGroup($groupId)
	{
		global $USER;
		if (!is_object($USER))
		{
			return;
		}

		$userId = $USER->GetId();
		if (in_array($userId, self::$listUserIdWithSessionGroups))
		{
			return;
		}

		$groupId = intval($groupId);

		if (!$groupId)
		{
			$CrmPerms = new CCrmPerms($userId);
			if ($CrmPerms->havePerm("CONFIG", BX_CRM_PERM_CONFIG, "WRITE"))
			{
				$groupId = self::getShopGroupIdByType("admin");
			}
			else
			{
				$groupId = self::getShopGroupIdByType("manager");
			}
		}

		$groups = $USER->GetUserGroupArray();
		if ($groupId && !in_array($groupId, $groups))
		{
			$groups[] = $groupId;
			$USER->SetUserGroupArray($groups);
			self::$listUserIdWithSessionGroups[] = $userId;
		}
	}

	/**
	 * @param array $listUserId
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function addUserToShopGroup($listUserId = array())
	{
		if (IsModuleInstalled("bitrix24"))
		{
			if (empty($listUserId))
			{
				$listUserId = self::getListUserIdFromCrmRoles();
			}

			if ($listUserId)
			{
				foreach ($listUserId as $userId)
				{
					$CrmPerms = new CCrmPerms($userId);
					if ($CrmPerms->havePerm("CONFIG", BX_CRM_PERM_CONFIG, "WRITE"))
					{
						$groupId = self::getShopGroupIdByType("admin");
					}
					else
					{
						$groupId = self::getShopGroupIdByType("manager");
					}
					if ($groupId)
					{
						$queryObject = UserGroupTable::getByPrimary(
							array("GROUP_ID" => $groupId, "USER_ID" => $userId),
							array("select" => array("GROUP_ID"))
						);
						if (!$queryObject->fetch())
						{
							UserGroupTable::add(array("GROUP_ID" => $groupId, "USER_ID" => $userId));
						}
					}
				}
			}
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
			if (strlen($groupObject->LAST_ERROR) <= 0 && $groupId)
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

		if (IsModuleInstalled("bitrix24"))
		{
			CCrmSaleHelper::addUserToShopGroup();
		}

		return "";
	}

	/**
	 * @throws \Bitrix\Main\Db\SqlQueryException
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

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getListUserIdFromCrmRoles()
	{
		$listUserId = array();

		$cacheTime = 86400;
		$cacheId = "crm-list-crm-roles";
		$cacheDir = "/crm/list_crm_roles/";
		$cache = new CPHPCache;

		if ($cache->initCache($cacheTime, $cacheId, $cacheDir))
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
				$params = unserialize($data['SR_PARAMS']);
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
}