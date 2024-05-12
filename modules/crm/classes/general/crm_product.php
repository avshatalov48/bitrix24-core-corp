<?php

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Catalog;

/*
 * CRM Product.
 * It is based on IBlock module.
 * */
class CCrmProduct
{
	public const CACHE_NAME = 'CRM_CATALOG_PRODUCT_CACHE';
	public const TABLE_ALIAS = 'P';

	protected const EVENT_ON_AFTER_UPDATE = 'OnAfterCrmProductUpdate';

	protected static $LAST_ERROR = '';
	protected static $FIELD_INFOS = null;
	private static $defaultCatalogId = null;
	private static $selectedPriceTypeId = null;
	private static $bVatMode = null;
	private static array $arVatRates = [];

	private static int $allowElementHandlers = 0;

	protected static bool $catalogIncluded;

	public static function getDefaultCatalogId()
	{
		if (self::$defaultCatalogId === null)
			self::$defaultCatalogId = CCrmCatalog::EnsureDefaultExists();
		return self::$defaultCatalogId;
	}

	/**
	 * @return int
	 */
	public static function getSelectedPriceTypeId(): int
	{
		if (self::$selectedPriceTypeId === null)
		{
			$priceTypeId = (int)Main\Config\Option::get('crm', 'selected_catalog_group_id');
			if (Loader::includeModule('catalog'))
			{
				if ($priceTypeId > 0)
				{
					$list = Catalog\GroupTable::getTypeList();
					$getPriceType = !isset($list[$priceTypeId]);
					unset($list);
				}
				else
				{
					$getPriceType = true;
				}
				if ($getPriceType)
				{
					$baseCatalogGroup = \CCatalogGroup::GetBaseGroup();
					if (!empty($baseCatalogGroup))
					{
						$priceTypeId = (int)$baseCatalogGroup['ID'];
						Main\Config\Option::set('crm', 'selected_catalog_group_id', $priceTypeId, '');
					}
					unset($baseCatalogGroup);
				}
				unset($getPriceType);
			}
			self::$selectedPriceTypeId = $priceTypeId;
		}
		return self::$selectedPriceTypeId;
	}

	/**
	 * @param int $productID
	 * @param int|false $priceTypeId
	 * @return array|false
	 */
	public static function getPrice($productID, $priceTypeId = false)
	{
		if (!Loader::includeModule('catalog'))
		{
			return false;
		}

		$productID = (int)$productID;
		if ($productID <= 0)
			return false;

		$priceTypeId = ($priceTypeId === false ? self::getSelectedPriceTypeId() : (int)$priceTypeId);
		if ($priceTypeId < 1)
			return false;

		//TODO: possible replace with Catalog\PriceTable::getlist - if use no for update
		$iterator = Catalog\Model\Price::getList(array(
			'select' => array(
				'ID', 'PRODUCT_ID', 'CATALOG_GROUP_ID',
				'PRICE', 'CURRENCY', 'QUANTITY_FROM', 'QUANTITY_TO', 'EXTRA_ID',
				'TIMESTAMP_X', 'TMP_ID'
			),
			'filter' => array(
				'=PRODUCT_ID' => $productID,
				'=CATALOG_GROUP_ID' => $priceTypeId
			),
			'order' => array('QUANTITY_FROM' => 'ASC', 'QUANTITY_TO' => 'ASC'),
			'limit' => 1
		));
		$row = $iterator->fetch();
		unset($iterator);

		return (!empty($row) ? $row : false);
	}

	public static function setPrice($productID, $priceValue = 0.0, $currency = false, $priceTypeId = false)
	{
		if (!Loader::includeModule('catalog'))
		{
			return false;
		}

		$productID = intval($productID);

		if ($currency === false)
			$currency = CCrmCurrency::GetBaseCurrencyID();
		if (mb_strlen($currency) < 3)
			return false;

		if ($priceTypeId === false)
			$priceTypeId = self::getSelectedPriceTypeId();
		if (intval($priceTypeId) < 1)
			return false;

		$priceValue = doubleval($priceValue);
		if (!is_finite($priceValue))
			$priceValue = 0.0;
		if ($arFields = self::getPrice($productID, $priceTypeId))
		{
			$ID = $arFields["ID"];
			$arFields = array(
				"PRICE" => $priceValue,
				"CURRENCY" => $currency
			);
			$ID = CPrice::Update($ID, $arFields);
		}
		else
		{
			$arFields = array(
				"PRICE" => $priceValue,
				"CURRENCY" => $currency,
				"QUANTITY_FROM" => null,
				"QUANTITY_TO" => null,
				"EXTRA_ID" => false,
				"CATALOG_GROUP_ID" => $priceTypeId,
				"PRODUCT_ID" => $productID
			);

			$ID = CPrice::Add($arFields);
		}

		return ($ID) ? $ID : false;
	}

	// CRUD -->
	public static function Add($arFields)
	{
		if (!Loader::includeModule('catalog'))
		{
			return false;
		}

		$element = new CIBlockElement();
		$ID = $arFields['ID'] ?? null;
		if ($ID !== null)
		{
			$ID = (int)$ID;
			if ($ID <= 0)
			{
				unset($arFields['ID']);
				$ID = null;
			}
		}
		if ($ID === null)
		{
			//Try to create a CIBlockElement
			$arElement = array();

			if (isset($arFields['CATALOG_ID']))
			{
				$arElement['IBLOCK_ID'] = (int)$arFields['CATALOG_ID'];
			}
			else
			{
				$arFields['CATALOG_ID'] = (int)CCrmCatalog::EnsureDefaultExists();
				$arElement['IBLOCK_ID'] = $arFields['CATALOG_ID'];
			}

			if(isset($arFields['NAME']))
			{
				$arElement['NAME'] = $arFields['NAME'];
			}

			if (isset($arFields['CODE']))
			{
				$arElement['CODE'] = $arFields['CODE'];
			}
			elseif (isset($arElement['NAME']))
			{
				$mnemonicCode = $element->generateMnemonicCode($arElement['NAME'], $arElement['IBLOCK_ID']);
				if ($mnemonicCode !== null)
				{
					$arElement['CODE'] = $mnemonicCode;
				}
			}

			if(isset($arFields['SORT']))
			{
				$arElement['SORT'] = $arFields['SORT'];
			}

			if(isset($arFields['ACTIVE']))
			{
				$arElement['ACTIVE'] = $arFields['ACTIVE'];
			}

			if(isset($arFields['DETAIL_PICTURE']))
			{
				$arElement['DETAIL_PICTURE'] = $arFields['DETAIL_PICTURE'];
			}

			if(isset($arFields['DESCRIPTION']))
			{
				$arElement['DETAIL_TEXT'] = $arFields['DESCRIPTION'];
				$arElement['DETAIL_TEXT_TYPE'] = 'text';
			}

			if(isset($arFields['DESCRIPTION_TYPE']))
			{
				$arElement['DETAIL_TEXT_TYPE'] = $arFields['DESCRIPTION_TYPE'];
			}

			if(isset($arFields['PREVIEW_PICTURE']))
			{
				$arElement['PREVIEW_PICTURE'] = $arFields['PREVIEW_PICTURE'];
			}

			if(isset($arFields['PREVIEW_TEXT']))
			{
				$arElement['PREVIEW_TEXT'] = $arFields['PREVIEW_TEXT'];
				$arElement['PREVIEW_TEXT_TYPE'] = 'text';
			}

			if(isset($arFields['PREVIEW_TEXT_TYPE']))
			{
				$arElement['PREVIEW_TEXT_TYPE'] = $arFields['PREVIEW_TEXT_TYPE'];
			}

			if(isset($arFields['SECTION_ID']))
			{
				$arElement['IBLOCK_SECTION_ID'] = $arFields['SECTION_ID'];
				$arElement['IBLOCK_SECTION'] = array($arElement['IBLOCK_SECTION_ID']);
			}

			if(isset($arFields['XML_ID']))
			{
				$arElement['XML_ID'] = $arFields['XML_ID'];
			}
			else
			{
				if(isset($arFields['ORIGINATOR_ID']) || isset($arFields['ORIGIN_ID']))
				{
					if (isset($arFields['ORIGINATOR_ID']) && isset($arFields['ORIGIN_ID']))
					{
						$arElement['XML_ID'] = $arFields['ORIGINATOR_ID'].'#'.$arFields['ORIGIN_ID'];
					}
					else
					{
						if (isset($arFields['ORIGINATOR_ID'])) $arElement['XML_ID'] = $arFields['ORIGINATOR_ID'].'#';
						else $arElement['XML_ID'] = '#'.$arFields['ORIGIN_ID'];
					}
				}
				else
				{
					if ($arElement['IBLOCK_ID'] != self::getDefaultCatalogId())
						$arElement['XML_ID'] = '#';
				}
			}

			if(isset($arFields['DATE_CREATE']))
			{
				$arElement['DATE_CREATE'] = $arFields['DATE_CREATE'];
			}

			// May be false or null
			if(array_key_exists('TIMESTAMP_X', $arFields))
			{
				$arElement['TIMESTAMP_X'] = $arFields['TIMESTAMP_X'];
			}

			if(isset($arFields['CREATED_BY']))
			{
				$arElement['CREATED_BY'] = $arFields['CREATED_BY'];
			}

			if(isset($arFields['MODIFIED_BY']))
			{
				$arElement['MODIFIED_BY'] = $arFields['MODIFIED_BY'];
			}

			if(isset($arFields['PROPERTY_VALUES']))
			{
				$arElement['PROPERTY_VALUES'] = $arFields['PROPERTY_VALUES'];
			}

			if(!$element->CheckFields($arElement))
			{
				self::RegisterError($element->LAST_ERROR);
				return false;
			}

			$ID = (int)$element->Add($arElement);
			if($ID <= 0)
			{
				self::$LAST_ERROR = $element->LAST_ERROR;
				return false;
			}
			$arFields['ID'] = $ID;
		}

		if (!self::CheckFields('ADD', $arFields, 0))
		{
			$element->Delete($ID);
			return false;
		}

		$arCatalogProductFields = array('ID' => $ID, 'QUANTITY' => 0);
		if (isset($arFields['VAT_INCLUDED']))
			$arCatalogProductFields['VAT_INCLUDED'] = $arFields['VAT_INCLUDED'];
		if (isset($arFields['VAT_ID']) && !empty($arFields['VAT_ID']))
			$arCatalogProductFields['VAT_ID'] = $arFields['VAT_ID'];
		if (isset($arFields['MEASURE']) && !empty($arFields['MEASURE']))
			$arCatalogProductFields['MEASURE'] = (int)$arFields['MEASURE'];
		if (isset($arFields['TYPE']))
		{
			$productType = (int)$arFields['TYPE'];
			$productTypeList = Catalog\ProductTable::getProductTypes(true);
			if (isset($productTypeList[$productType]))
			{
				$arCatalogProductFields['TYPE'] = $productType;
			}
		}
		if (self::innerProductModify($arCatalogProductFields))
		{
			if (isset($arFields['PRICE']))
			{
				self::setPrice(
					$ID,
					$arFields['PRICE'],
					$arFields['CURRENCY_ID'] ?? false
				);
			}
		}
		else
		{
			$element->Delete($ID);
			return false;
		}

		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		global $USER;

		if (!Loader::includeModule('catalog'))
		{
			return false;
		}

		if (!self::CheckFields('UPDATE', $arFields, $ID))
		{
			return false;
		}

		$iblockElementUpdated = false;
		$needUpdateIblockElement = false;

		if(isset($arFields['NAME'])
			|| isset($arFields['CODE'])
			|| isset($arFields['SECTION_ID'])
			|| isset($arFields['SORT'])
			|| isset($arFields['ACTIVE'])
			|| isset($arFields['DETAIL_PICTURE'])
			|| isset($arFields['DESCRIPTION'])
			|| isset($arFields['DESCRIPTION_TYPE'])
			|| isset($arFields['PREVIEW_PICTURE'])
			|| isset($arFields['PREVIEW_TEXT'])
			|| isset($arFields['PREVIEW_TEXT_TYPE'])
			|| isset($arFields['ORIGINATOR_ID'])
			|| isset($arFields['ORIGIN_ID'])
			|| isset($arFields['XML_ID'])
			|| isset($arFields['PROPERTY_VALUES'])
			|| isset($arFields['DATE_CREATE'])
			|| array_key_exists('TIMESTAMP_X', $arFields)    // May be false or null
			|| isset($arFields['CREATED_BY'])
			|| isset($arFields['MODIFIED_BY']))
		{
			$element =  new CIBlockElement();
			$obResult = $element->GetById($ID);
			if($arElement = $obResult->Fetch())
			{
				unset($arElement['DATE_CREATE']);
				unset($arElement['CREATED_BY']);
				unset($arElement['MODIFIED_BY']);
				unset($arElement['TIMESTAMP_X']);
				if(isset($arFields['NAME']))
				{
					$arElement['NAME'] = $arFields['NAME'];
				}

				if(isset($arFields['CODE']))
				{
					$arElement['CODE'] = $arFields['CODE'];
				}

				if (isset($arElement['IN_SECTIONS']) && $arElement['IN_SECTIONS'] !== 'N')
				{
					$sections = [];
					$res = CIBlockElement::GetElementGroups($ID, true, ['ID']);
					while($row = $res->Fetch())
					{
						$sections[] = (int)$row['ID'];
					}
					if (count($sections) > 0)
					{
						$arElement['IBLOCK_SECTION'] = $sections;
					}
					unset($sections, $res, $row);
				}

				if(isset($arFields['SECTION_ID']))
				{
					$newSectionId = (int)$arFields['SECTION_ID'];
					if (is_array($arElement['IBLOCK_SECTION']) && isset($arElement['IBLOCK_SECTION_ID']))
					{
						$oldSectionId = (int)$arElement['IBLOCK_SECTION_ID'];
						$key = array_search($oldSectionId, $arElement['IBLOCK_SECTION'], true);
						if ($key !== false)
						{
							$arElement['IBLOCK_SECTION'][$key] = $newSectionId;
						}
						unset($oldSectionId, $key);
					}
					$arElement['IBLOCK_SECTION_ID'] = $newSectionId;
					unset($newSectionId);
				}

				if(isset($arFields['SORT']))
				{
					$arElement['SORT'] = $arFields['SORT'];
				}

				if(isset($arFields['ACTIVE']))
				{
					$arElement['ACTIVE'] = $arFields['ACTIVE'];
				}

				if(isset($arFields['DETAIL_PICTURE']))
				{
					$arElement['DETAIL_PICTURE'] = $arFields['DETAIL_PICTURE'];
				}
				else
				{
					unset($arElement["DETAIL_PICTURE"]);
				}

				if(isset($arFields['DESCRIPTION']))
				{
					$arElement['DETAIL_TEXT'] = $arFields['DESCRIPTION'];
				}

				if(isset($arFields['DESCRIPTION_TYPE']))
				{
					$arElement['DETAIL_TEXT_TYPE'] = $arFields['DESCRIPTION_TYPE'];
				}

				if(isset($arFields['PREVIEW_PICTURE']))
				{
					$arElement['PREVIEW_PICTURE'] = $arFields['PREVIEW_PICTURE'];
				}
				else
				{
					unset($arElement["PREVIEW_PICTURE"]);
				}

				if(isset($arFields['PREVIEW_TEXT']))
				{
					$arElement['PREVIEW_TEXT'] = $arFields['PREVIEW_TEXT'];
					$arElement['PREVIEW_TEXT_TYPE'] = 'text';
				}

				if(isset($arFields['PREVIEW_TEXT_TYPE']))
				{
					$arElement['PREVIEW_TEXT_TYPE'] = $arFields['PREVIEW_TEXT_TYPE'];
				}

				if(isset($arFields['XML_ID']))
				{
					$arElement['XML_ID'] = $arElement['EXTERNAL_ID'] = $arFields['XML_ID'];
				}
				else
				{
					if (isset($arFields['ORIGINATOR_ID']) || isset($arFields['ORIGIN_ID']))
					{
						if ($arFields['ORIGINATOR_ID'] <> '' && $arFields['ORIGIN_ID'] <> '')
						{
							$arElement['XML_ID'] = $arFields['ORIGINATOR_ID'].'#'.$arFields['ORIGIN_ID'];
						}
						else
						{
							$delimiterPos = mb_strpos($arElement['XML_ID'], '#');
							if ($arFields['ORIGINATOR_ID'] <> '')
							{
								if ($delimiterPos !== false)
								{
									$arElement['XML_ID'] = $arFields['ORIGINATOR_ID'].mb_substr($arElement['XML_ID'], $delimiterPos);
								}
								else $arElement['XML_ID'] = $arFields['ORIGINATOR_ID'];
							}
							else
							{
								if ($delimiterPos !== false)
								{
									$arElement['XML_ID'] = mb_substr($arElement['XML_ID'], 0, $delimiterPos).$arFields['ORIGIN_ID'];
								}
								else $arElement['XML_ID'] = '#'.$arFields['ORIGINATOR_ID'];
							}
						}
					}
				}

				if(isset($arFields['DATE_CREATE']))
				{
					$arElement['DATE_CREATE'] = $arFields['DATE_CREATE'];
				}

				// May be false or null
				if(array_key_exists('TIMESTAMP_X', $arFields))
				{
					$arElement['TIMESTAMP_X'] = $arFields['TIMESTAMP_X'];
				}

				if(isset($arFields['CREATED_BY']))
				{
					$arElement['CREATED_BY'] = $arFields['CREATED_BY'];
				}

				if(isset($arFields['MODIFIED_BY']))
				{
					$arElement['MODIFIED_BY'] = $arFields['MODIFIED_BY'];
				}
				else
				{
					if (isset($USER) && $USER instanceof \CUser)
					{
						$arElement['MODIFIED_BY'] = $USER->GetID();
					}
				}

				if(isset($arFields['PROPERTY_VALUES']) && is_array($arFields['PROPERTY_VALUES']))
				{
					$arElement['PROPERTY_VALUES'] = $arFields['PROPERTY_VALUES'];
				}

				if(!$element->Update($ID, $arElement))
				{
					self::$LAST_ERROR = $element->LAST_ERROR;
					return false;
				}

				$iblockElementUpdated = true;
			}
		}

		// update VAT
		$arCatalogProductFields = array();
		if (isset($arFields['VAT_INCLUDED']))
			$arCatalogProductFields['VAT_INCLUDED'] = $arFields['VAT_INCLUDED'];
		if (isset($arFields['VAT_ID']) && !empty($arFields['VAT_ID']))
			$arCatalogProductFields['VAT_ID'] = $arFields['VAT_ID'];
		if (isset($arFields['MEASURE']) && !empty($arFields['MEASURE']))
			$arCatalogProductFields['MEASURE'] = (int)$arFields['MEASURE'];
		if (!empty($arCatalogProductFields))
		{
			$arCatalogProductFields['ID'] = (int)$ID;
			if (!self::innerProductModify($arCatalogProductFields))
			{
				return false;
			}
			if (!$iblockElementUpdated)
			{
				$needUpdateIblockElement = true;
			}
		}

		if (isset($arFields['PRICE']) && isset($arFields['CURRENCY_ID']))
		{
			self::setPrice($ID, $arFields['PRICE'], $arFields['CURRENCY_ID']);
			if (!$iblockElementUpdated)
			{
				$needUpdateIblockElement = true;
			}
		}
		else
		{
			if (isset($arFields['PRICE']) || isset($arFields['CURRENCY_ID']))
			{
				$price = $currency = false;
				if (!isset($arFields['PRICE']))
				{
					$basePriceInfo = self::getPrice($ID);
					if ($basePriceInfo !== false && is_array($basePriceInfo) && isset($basePriceInfo['PRICE']))
					{
						$price = $basePriceInfo['PRICE'];
						$currency = $arFields['CURRENCY_ID'];
					}
				}
				elseif (!isset($arFields['CURRENCY_ID']))
				{
					$basePriceInfo = self::getPrice($ID);
					if ($basePriceInfo !== false && is_array($basePriceInfo) && isset($basePriceInfo['PRICE']))
					{
						$price = $arFields['PRICE'];
						$currency = $basePriceInfo['CURRENCY'];
					}
					else
					{
						$price = $arFields['PRICE'];
						$currency = CCrmCurrency::GetBaseCurrencyID();
						if ($currency === '')
							$currency = false;
					}
				}
				else
				{
					$price = $arFields['PRICE'];
					$currency = $arFields['CURRENCY_ID'];
				}
				if ($price !== false && $currency !== false)
				{
					CCrmProduct::setPrice($ID, $price, $currency);
					if (!$iblockElementUpdated)
					{
						$needUpdateIblockElement = true;
					}
				}
			}
		}

		if ($needUpdateIblockElement)
		{
			$element =  new CIBlockElement();
			$element->Update($ID, ['ID' => $ID], false, false, false, false);
		}

		CCrmEntityHelper::RemoveCached(self::CACHE_NAME, $ID);

		foreach (GetModuleEvents("crm", self::EVENT_ON_AFTER_UPDATE, true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));

		return true;
	}

	public static function Delete($ID)
	{
		global $APPLICATION;

		if (!Loader::includeModule('iblock'))
		{
			return false;
		}

		$ID = (int)$ID;

		$arProduct = self::GetByID($ID);
		if(!is_array($arProduct))
		{
			// Is no exists
			return true;
		}

		if (!self::IsAllowedDelete($ID))
		{
			return false;
		}

		self::disableElementHandlers();

		$element = new CIBlockElement();
		$result = $element->Delete($ID);
		if (!$result)
		{
			if ($ex = $APPLICATION->GetException())
			{
				self::RegisterError($ex->GetString());
			}
		}
		unset($element);

		self::enableElementHandlers();

		if ($result)
		{
			self::DeleteInternal($ID);
		}

		return $result;
	}

	public static function handlerAfterProductUpdate(Main\Event $event): void
	{
		if (!isset(self::$catalogIncluded))
		{
			self::$catalogIncluded = Loader::includeModule('catalog');
		}

		$id = $event->getParameter('id');
		$fields = $event->getParameter('fields');

		if (isset($fields['ID']))
		{
			unset($fields['ID']);
		}

		$datetimeFields = [
			'TIMESTAMP_X',
			'DATE_CREATE',
			'ACTIVE_FROM',
			'ACTIVE_TO',
		];
		foreach ($datetimeFields as $fieldName)
		{
			if (isset($fields[$fieldName]) && $fields[$fieldName] instanceof Main\Type\DateTime)
			{
				$fields[$fieldName] = $fields[$fieldName]->toString();
			}
		}
		unset($fieldName);
		if (isset($fields['PRICES']))
		{
			$crmPriceType = self::getSelectedPriceTypeId();
			if (self::$catalogIncluded && is_array($fields['PRICES']))
			{
				foreach ($fields['PRICES'] as $price)
				{
					if (isset($price['CATALOG_GROUP_ID']) && $price['CATALOG_GROUP_ID'] == $crmPriceType)
					{
						$fields['PRICE'] = $price['PRICE'];
						$fields['CURRENCY'] = $price['CURRENCY'];
						break;
					}
				}
				unset($price);
			}
			unset($fields['PRICES']);
		}

		foreach (GetModuleEvents('crm', self::EVENT_ON_AFTER_UPDATE, true) as $crmEvent)
		{
			ExecuteModuleEventEx($crmEvent, [$id, $fields]);
		}
		unset($crmEvent);
		unset($fields, $id);
	}

	private static function innerProductModify(array $fields): bool
	{
		$existProduct = false;
		$data = Catalog\Model\Product::getCacheItem($fields['ID'], true);
		if (!empty($data))
		{
			$existProduct = !empty($data['ID']);
		}
		unset($data);

		if (!$existProduct)
		{
			if (Catalog\Product\SystemField\ProductMapping::isAllowed())
			{
				$fieldName = Catalog\Product\SystemField\ProductMapping::getUserFieldBaseParam()['FIELD_NAME'];
				if (!array_key_exists($fieldName, $fields))
				{
					$userField = Catalog\Product\SystemField\ProductMapping::load();
					if (!empty($userField))
					{
						$value = (!empty($userField['SETTINGS']['DEFAULT_VALUE']) && is_array($userField['SETTINGS']['DEFAULT_VALUE'])
							? $userField['SETTINGS']['DEFAULT_VALUE']
							: null
						);
						if ($value === null)
						{
							/** @var Catalog\Product\SystemField\Type\HighloadBlock $className */
							$className = Catalog\Product\SystemField\ProductMapping::getTypeId();

							$list = $className::getIdByXmlId(
								$userField['SETTINGS']['HLBLOCK_ID'],
								[Catalog\Product\SystemField\ProductMapping::MAP_LANDING]
							);
							if (isset($list[Catalog\Product\SystemField\ProductMapping::MAP_LANDING]))
							{
								$value = [
									$list[Catalog\Product\SystemField\ProductMapping::MAP_LANDING],
								];
							}
						}
						if ($value !== null)
						{
							$fields[$fieldName] = $value;
						}
					}
				}
			}
		}

		$result = $existProduct
			? Catalog\Model\Product::update($fields['ID'], $fields)
			: Catalog\Model\Product::add($fields)
		;
		$success = $result->isSuccess();
		if (!$success)
		{
			self::$LAST_ERROR = implode(' ', $result->getErrorMessages());
		}
		unset($result);

		return $success;
	}
	//<-- CRUD

	// Contract -->
	public static function GetList($arOrder = array(), $arFilter = array(), $arSelectFields = array(), $arNavStartParams = false, $arGroupBy = false)
	{
		if (!Loader::includeModule('iblock'))
		{
			return false;
		}

		$arProductFields = self::GetFields();

		// Rewrite order
		// <editor-fold defaultstate="collapsed" desc="Rewrite order ...">
		$arOrderRewrited = array();
		foreach ($arOrder as $k => $v)
		{
			$uk = mb_strtoupper($k);
			if ((isset($arProductFields[$uk]) && $arProductFields[$uk] !== false)
				|| preg_match('/^PROPERTY_\d+$/', $uk))
				$arOrderRewrited[$uk] = $v;
		}

		if (isset($arOrder['ORIGINATOR_ID']) && !empty($arOrder['ORIGINATOR_ID']))
		{
			$arOrderRewrited['XML_ID'] = $arOrder['ORIGINATOR_ID'];
		} elseif (isset($arOrder['ORIGIN_ID']) && !empty($arOrder['ORIGIN_ID']))
		{
			$arOrderRewrited['XML_ID'] = $arOrder['ORIGIN_ID'];
		}
		// </editor-fold>

		// Rewrite filter
		// <editor-fold defaultstate="collapsed" desc="Rewrite filter ...">
		$arAdditionalFilter = $arFilterRewrited = array();

		$arOptions = array();
		if (isset($arFilter['~REAL_PRICE']))
		{
			$arOptions['REAL_PRICE'] = true;
			unset($arFilter['~REAL_PRICE']);
		}

		if (isset($arFilter['INCLUDE_SUBSECTIONS']) && $arFilter['INCLUDE_SUBSECTIONS'] === 'Y')
		{
			$arFilterRewrited['INCLUDE_SUBSECTIONS'] = 'Y';
		}
		foreach ($arProductFields as $fieldProduct => $fieldIblock)
		{
			foreach($arFilter as $k => $v)
			{
				$matches = array();
				if (preg_match('/^([!><=%?][><=%]?[<]?|)'.$fieldProduct.'$/', $k, $matches))
				{
					if ($fieldIblock)
					{
						if($fieldIblock === 'IBLOCK_SECTION_ID')
						{
							//HACK: IBLOCK_SECTION_ID is not supported in filter
							$fieldIblock = 'SECTION_ID';
						}

						$arFilterRewrited[$matches[1].$fieldIblock] = $v;
					}
					else
					{
						$arAdditionalFilter[$k] = $v;
					}
				}
				else if (preg_match('/^([!><=%?][><=%]?[<]?|)(PROPERTY_\d+)$/', $k, $matches))
				{
					$arFilterRewrited[$matches[1].$matches[2]] = $v;
				}
			}
		}

		if (
			isset($arFilter['ORIGINATOR_ID']) && !empty($arFilter['ORIGINATOR_ID'])
			&& isset($arFilter['ORIGIN_ID']) && !empty($arFilter['ORIGIN_ID'])
		)
		{
			$arFilterRewrited['XML_ID'] = $arFilter['ORIGINATOR_ID'].'#'.$arFilter['ORIGIN_ID'];
		} elseif (isset($arFilter['ORIGINATOR_ID']) && !empty($arFilter['ORIGINATOR_ID']))
		{
			$arFilterRewrited['%XML_ID'] = $arFilter['ORIGINATOR_ID'].'#';
		} elseif (isset($arFilter['ORIGIN_ID']) && !empty($arFilter['ORIGIN_ID']))
		{
			$arFilterRewrited['%XML_ID'] = '#'.$arFilter['ORIGIN_ID'];
		}

		if(!isset($arFilter['ID']) || isset($arFilter['CATALOG_ID']))
		{
			$catalogID = isset($arFilter['CATALOG_ID']) ? intval($arFilter['CATALOG_ID']) : 0;
			if($catalogID > 0 && !CCrmCatalog::Exists($catalogID))
			{
				$catalogID = 0;
			}

			if($catalogID <= 0)
			{
				$catalogID = CCrmCatalog::EnsureDefaultExists();
			}

			$arFilterRewrited['IBLOCK_ID'] = $catalogID;
		}

		// </editor-fold>

		// Rewrite select
		// <editor-fold defaultstate="collapsed" desc="Rewrite select ...">
		$arSelect = $arSelectFields;
		if (!is_array($arSelect))
		{
			$arSelect = array();
		}

		if (empty($arSelect))
		{
			$arSelect = array();
			foreach (array_keys($arProductFields) as $fieldName)
			{
				if (!in_array($fieldName, array('PRICE', 'CURRENCY_ID', 'VAT_ID', 'VAT_INCLUDED', 'MEASURE'), true))
					$arSelect[] = $fieldName;
			}
		}
		else if (in_array('*', $arSelect, true))
		{
			$arSelect = array_keys($arProductFields);
		}

		$arAdditionalSelect = $arSelectRewrited = array();
		foreach ($arProductFields as $fieldProduct => $fieldIblock)
		{
			if (in_array($fieldProduct, $arSelect, true))
			{
				if ($fieldIblock) $arSelectRewrited[] = $fieldIblock;
				else $arAdditionalSelect[] = $fieldProduct;
			}
		}
		foreach ($arSelect as $v)
		{
			$isField = isset($arProductFields[$v]);
			$isIblockField = ($isField && $arProductFields[$v] !== false);
			if ($isIblockField)
			{
				$arSelectRewrited[] = $arProductFields[$v];
			}
			else if ($isField)
			{
				$arAdditionalSelect[] = $v;
			}
			else if (preg_match('/^PROPERTY_\d+$/', $v))
			{
				$arSelectRewrited[] = $v;
			}
			unset($isField, $isIblockField);
		}
		if (!in_array('ID', $arSelectRewrited, true))
			$arSelectRewrited[] = 'ID';

		if (!in_array('XML_ID', $arSelectRewrited, true))
		{
			$bSelectXmlId = false;
			foreach ($arSelect as $k => $v)
			{
				if ($v === 'ORIGINATOR_ID' || $v === 'ORIGIN_ID')
				{
					$bSelectXmlId = true;
					break;
				}
			}
			if ($bSelectXmlId) $arAdditionalSelect[] = $arSelectRewrited[] = 'XML_ID';
		}
		// </editor-fold>

		$arNavStartParamsRewrited = false;
		if (is_array($arNavStartParams))
			$arNavStartParamsRewrited = $arNavStartParams;
		else
		{
			if (is_numeric($arNavStartParams))
			{
				$nTopCount = intval($arNavStartParams);
				if ($nTopCount > 0)
					$arNavStartParamsRewrited = array('nTopCount' => $nTopCount);
			}
		}

		$dbRes = CIBlockElement::GetList($arOrderRewrited, $arFilterRewrited, ($arGroupBy === false) ? false : array(), $arNavStartParamsRewrited, $arSelectRewrited);
		if ($arGroupBy === false)
			$dbRes = new CCrmProductResult($dbRes, $arProductFields, $arAdditionalFilter, $arAdditionalSelect, $arOptions);

		return $dbRes;
	}

	/**
	 * @param array $arProductID
	 * @param bool $priceTypeId
	 * @return Main\ORM\Query\Result|false
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function GetPrices($arProductID = array(), $priceTypeId = false)
	{
		if (!Loader::includeModule('catalog'))
		{
			return false;
		}

		if (empty($arProductID) || !is_array($arProductID))
		{
			return false;
		}

		Main\Type\Collection::normalizeArrayValuesByInt($arProductID, true);
		if (empty($arProductID))
		{
			return false;
		}

		if ($priceTypeId === false)
			$priceTypeId = self::getSelectedPriceTypeId();

		return Catalog\PriceTable::getList(array(
			'select' => array('ID', 'PRODUCT_ID', 'PRICE', 'CURRENCY', 'QUANTITY_FROM', 'QUANTITY_TO'),
			'filter' => array('@PRODUCT_ID' => $arProductID, '=CATALOG_GROUP_ID' => $priceTypeId),
			'order' => array('QUANTITY_FROM' => 'ASC', 'QUANTITY_TO' => 'ASC')
		));
	}

	/**
	 * @param array $arProductID
	 * @return Main\ORM\Query\Result|false
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function GetCatalogProductFields($arProductID = array())
	{
		if (!Loader::includeModule('catalog'))
		{
			return false;
		}

		if (empty($arProductID) || !is_array($arProductID))
		{
			return false;
		}
		Main\Type\Collection::normalizeArrayValuesByInt($arProductID, true);
		if (empty($arProductID))
		{
			return false;
		}

		return Catalog\ProductTable::getList(array(
			'select' => array('ID', 'VAT_ID', 'VAT_INCLUDED', 'MEASURE'),
			'filter' => array('@ID' => $arProductID)
		));
	}

	/**
	 * @param array $arProductID
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function PrepareCatalogProductFields(array $arProductID)
	{
		if (!Loader::includeModule('catalog'))
		{
			return array();
		}

		if (empty($arProductID) || !is_array($arProductID))
		{
			return array();
		}
		Main\Type\Collection::normalizeArrayValuesByInt($arProductID, true);
		if (empty($arProductID))
		{
			return array();
		}

		$result = array();
		// use for show - direct query without product cache (Catalog\Model\Product)
		$iterator = Catalog\ProductTable::getList(array(
			'select' => array('ID', 'VAT_ID', 'VAT_INCLUDED', 'MEASURE'),
			'filter' => array('@ID' => $arProductID)
		));
		while ($fields = $iterator->fetch())
		{
			$productID = (int)$fields['ID'];
			$result[$productID] = array(
				'PRODUCT_ID' => $productID,
				'TAX_ID' => isset($fields['VAT_ID']) ? (int)$fields['VAT_ID'] : 0,
				'TAX_INCLUDED' => isset($fields['VAT_INCLUDED']) && mb_strtoupper($fields['VAT_INCLUDED']) === 'Y',
				'MEASURE' => isset($fields['MEASURE']) ? (int)$fields['MEASURE'] : 0
			);
		}
		unset($fields, $iterator);

		return $result;
	}

	public static function RecalculatePriceVat($price, $bVatIncluded, $vatId)
	{
		$result = $price;

		if (self::$bVatMode === null)
		{
			self::$bVatMode = CCrmTax::isVatMode();
			if (self::$bVatMode)
				self::$arVatRates = CCrmVat::GetAll();
		}

		if (self::$bVatMode)
		{
			if($bVatIncluded !== 'Y')
			{
				if (isset(self::$arVatRates[$vatId]))
				{
					$vatRate = self::$arVatRates[$vatId]['RATE'];
					$result = (doubleval($vatRate)/100 + 1) * doubleval($price);
				}
			}
		}

		return $result;
	}

	public static function DistributeProductSelect($arSelect, &$arPricesSelect, &$arCatalogProductSelect)
	{
		$tmpSelect = array();
		foreach ($arSelect as $fieldName)
		{
			switch ($fieldName)
			{
				case 'PRICE':
				case 'CURRENCY_ID':
					$arPricesSelect[] = $fieldName;
					break;
				case 'VAT_ID':
				case 'VAT_INCLUDED':
				case 'MEASURE':
					$arCatalogProductSelect[] = $fieldName;
					break;
				default:
					$tmpSelect[] = $fieldName;
			}
		}
		return $tmpSelect;
	}

	public static function ObtainPricesVats(&$arProducts, &$arProductId, &$arPricesSelect, &$arCatalogProductSelect, $bRealPrice = false)
	{
		if (is_array($arProducts) && is_array($arProductId) && is_array($arPricesSelect) && is_array($arCatalogProductSelect)
			&& count($arProductId) > 0 && count($arProducts) > 0 && (count($arPricesSelect) + count($arCatalogProductSelect)) > 0)
		{
			$arEntitiesFieldsets = array();
			if (count($arPricesSelect) > 0)
				$arEntitiesFieldsets[] = array(
					'name' => 'price',
					'class' => 'CCrmProduct',
					'method' => 'GetPrices',
					'fieldset' => &$arPricesSelect,
					'idField' => 'PRODUCT_ID',
					'fieldMap' => array(
						'PRICE' => 'PRICE',
						'CURRENCY_ID' => 'CURRENCY'
					)
				);
			if (count($arCatalogProductSelect) > 0 || (in_array('PRICE', $arPricesSelect, true) && !$bRealPrice))
				$arEntitiesFieldsets[] = array(
					'name' => 'vat',
					'class' => 'CCrmProduct',
					'method' => 'GetCatalogProductFields',
					'fieldset' => &$arCatalogProductSelect,
					'idField' => 'ID',
					'fieldMap' => array(
						'VAT_INCLUDED' => 'VAT_INCLUDED',
						'VAT_ID' => 'VAT_ID',
						'MEASURE' => 'MEASURE'
					)
				);
			$nProducts = count($arProductId);
			$nStepSize = 500;
			$nSteps = intval(floor($nProducts / $nStepSize)) + 1;
			$nOffset = $nRange = 0;
			$arStepProductId = $fieldset = $arRow = array();
			$fieldName = '';
			while ($nSteps > 0)
			{
				$nRange = ($nSteps > 1) ? $nStepSize : $nProducts - $nOffset;
				if ($nRange > 0)
				{
					$arStepProductId = array_slice($arProductId, $nOffset, $nRange);
					foreach ($arEntitiesFieldsets as $fieldset)
					{
						$dbStep = call_user_func(array($fieldset['class'], $fieldset['method']), $arStepProductId);
						if ($dbStep)
						{
							/** @var Main\ORM\Query\Result $dbStep */
							while ($arRow = $dbStep->fetch())
							{
								foreach ($fieldset['fieldset'] as $fieldName)
								{
									if (isset($arProducts[$arRow[$fieldset['idField']]]))
									{
										$arProduct = &$arProducts[$arRow[$fieldset['idField']]];
										if (array_key_exists($fieldName, $arProduct) && array_key_exists($fieldset['fieldMap'][$fieldName], $arRow))
										{
											$prefix = array_key_exists('~'.$fieldName, $arProduct) ? '~' : '';
											$arProduct[$prefix.$fieldName] = $arRow[$fieldset['fieldMap'][$fieldName]];
											if (!empty($prefix))
												$arProduct[$fieldName] = htmlspecialcharsbx($arProduct[$prefix.$fieldName]);
										}
									}
								}
								if ($fieldset['name'] === 'vat'
									&& (!isset($bRealPrice) || $bRealPrice !== true))
								{
									if (isset($arProducts[$arRow[$fieldset['idField']]]))
									{
										$arProduct = &$arProducts[$arRow[$fieldset['idField']]];
										$prefix = isset($arProduct['~PRICE']) ? '~' : '';
										if (isset($arProduct[$prefix.'PRICE'])
											&& doubleval($arProduct[$prefix.'PRICE']) != 0.0
											&& $arRow['VAT_INCLUDED'] !== 'Y'
											&& intval($arRow['VAT_ID']) > 0)
										{
											$arProduct[$prefix.'PRICE'] = self::RecalculatePriceVat(
												$arProduct[$prefix.'PRICE'], $arRow['VAT_INCLUDED'], $arRow['VAT_ID']
											);
											if (!empty($prefix))
												$arProduct['PRICE'] = htmlspecialcharsbx($arProduct[$prefix.'PRICE']);
										}
									}
								}
							}
						}
					}
				}
				$nOffset += $nStepSize;
				$nSteps--;
			}
		}
	}

	public static function Exists($ID)
	{
		$dbRes = CCrmProduct::GetList(array(), array('ID'=> $ID), array('ID'));
		return $dbRes->Fetch() ? true : false;
	}

	public static function EnsureDefaultCatalogScope($productID)
	{
		$defaultCatalogID = CCrmCatalog::GetDefaultID();
		if($defaultCatalogID <= 0)
		{
			return false;
		}

		$dbRes = CCrmProduct::GetList(array(), array('ID'=> $productID), array('ID', 'CATALOG_ID'));
		$fields = $dbRes->Fetch();
		return is_array($fields) && isset($fields['CATALOG_ID']) && (int)$fields['CATALOG_ID'] === $defaultCatalogID;
	}

	public static function GetByID($ID, $bRealPrice = false)
	{
		$arResult = CCrmEntityHelper::GetCached(self::CACHE_NAME.($bRealPrice !== false ? '_RP' : ''), $ID);
		if (is_array($arResult))
		{
			return $arResult;
		}

		$ID = (int)$ID;
		if ($ID <= 0)
		{
			return false;
		}

		$arFilter = array('=ID' => $ID);
		if ($bRealPrice !== false) $arFilter['~REAL_PRICE'] = true;

		$dbRes = CCrmProduct::GetList(array(), $arFilter, array('*'), array('nTopCount' => 1));
		$arResult = $dbRes->GetNext();

		if(is_array($arResult))
		{
			CCrmEntityHelper::SetCached(self::CACHE_NAME.($bRealPrice !== false ? '_RP' : ''), $ID, $arResult);
		}
		return $arResult;
	}

	public static function GetByName($name)
	{
		$dbRes = CCrmProduct::GetList(array(), array('NAME' => strval($name)), array('*'), array('nTopCount' => 1));
		return $dbRes->GetNext();
	}

	public static function GetByOriginID($originID, $catalogID = 0)
	{
		$catalogID = intval($catalogID);
		if($catalogID <= 0)
		{
			$catalogID = CCrmCatalog::GetDefaultID();
		}

		if($catalogID <= 0)
		{
			return false;
		}

		$dbRes = CCrmProduct::GetList(array(), array('CATALOG_ID' => $catalogID, 'ORIGIN_ID' => $originID),
			array('*'), array('nTopCount' => 1));
		return ($dbRes->GetNext());
	}

	public static function FormatPrice($arProduct)
	{
		$price = isset($arProduct['PRICE']) ? round(doubleval($arProduct['PRICE']), 2) : 0.00;
		/*if($price == 0.00)
		{
			return '';
		}*/

		$currencyID = isset($arProduct['CURRENCY_ID']) ? strval($arProduct['CURRENCY_ID']) : '';
		return CCrmCurrency::MoneyToString($price, $currencyID);
	}

	public static function GetProductName($productID)
	{
		$productID = intval($productID);
		if($productID <=0)
		{
			return '';
		}

		$dbResult = self::GetList(array(), array('ID' => $productID), array('NAME'));
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		return is_array($fields) && isset($fields['NAME']) ? $fields['NAME'] : '';
	}

	public static function GetLastError()
	{
		return self::$LAST_ERROR;
	}
	//<-- Contract

	//Service -->
	protected static function GetFields()
	{
		return array(
//			'ID' => array('FIELD' => 'P.ID', 'TYPE' => 'int'),
//			'CATALOG_ID' => array('FIELD' => 'P.CATALOG_ID', 'TYPE' => 'int'),
//			'PRICE' => array('FIELD' => 'P.PRICE', 'TYPE' => 'double'),
//			'CURRENCY_ID' => array('FIELD' => 'P.CURRENCY_ID', 'TYPE' => 'string'),
//			'ORIGINATOR_ID' => array('FIELD' => 'P.ORIGINATOR_ID', 'TYPE' => 'string'),
//			'ORIGIN_ID' => array('FIELD' => 'P.ORIGIN_ID', 'TYPE' => 'string'),
//			'NAME' => array('FIELD' => 'E.NAME', 'TYPE' => 'string', 'FROM' => 'INNER JOIN b_iblock_element E ON P.ID = E.ID'),
//			'ACTIVE' => array('FIELD' => 'E.ACTIVE', 'TYPE' => 'char', 'FROM' => 'INNER JOIN b_iblock_element E ON P.ID = E.ID'),
//			'SECTION_ID' => array('FIELD' => 'E.IBLOCK_SECTION_ID', 'TYPE' => 'int', 'FROM' => 'INNER JOIN b_iblock_element E ON P.ID = E.ID'),
//			'DESCRIPTION' => array('FIELD' => 'E.DETAIL_TEXT', 'TYPE' => 'string', 'FROM' => 'INNER JOIN b_iblock_element E ON P.ID = E.ID'),
//			'SORT' => array('FIELD' => 'E.SORT', 'TYPE' => 'int', 'FROM' => 'INNER JOIN b_iblock_element E ON P.ID = E.ID')

			// Value of an element contains the corresponding field of iblock element.
			// If value is false, it means that the field is stored in the catalog module.
			// The ORIGINATOR_ID and ORIGIN_ID fields stick together and stored in the XML_ID field of iblock element
			// in the format of 'ORIGINATOR_ID#ORIGIN_ID'.
			'ID' => 'ID',
			'CATALOG_ID' => 'IBLOCK_ID',
			'PRICE' => false,
			'CURRENCY_ID' => false,
			'ORIGINATOR_ID' => false,
			'ORIGIN_ID' => false,
			'NAME' => 'NAME',
			'CODE' => 'CODE',
			'ACTIVE' => 'ACTIVE',
			'SECTION_ID' => 'IBLOCK_SECTION_ID',
			'PREVIEW_PICTURE' => 'PREVIEW_PICTURE',
			'PREVIEW_TEXT' => 'PREVIEW_TEXT',
			'PREVIEW_TEXT_TYPE' => 'PREVIEW_TEXT_TYPE',
			'DETAIL_PICTURE' => 'DETAIL_PICTURE',
			'DESCRIPTION' => 'DETAIL_TEXT',
			'DESCRIPTION_TYPE' => 'DETAIL_TEXT_TYPE',
			'SORT' => 'SORT',
			'VAT_ID' => false,
			'VAT_INCLUDED' => false,
			'MEASURE' => false,
			'XML_ID' => 'XML_ID',
			'TIMESTAMP_X' => 'TIMESTAMP_X',
			'DATE_CREATE' => 'DATE_CREATE',
			'MODIFIED_BY' => 'MODIFIED_BY',
			'CREATED_BY' => 'CREATED_BY',
			'SHOW_COUNTER' => 'SHOW_COUNTER',
			'TYPE' => 'TYPE',
		);
	}

	public static function GetFieldCaption($fieldName)
	{
		$result = Loc::getMessage("CRM_PRODUCT_FIELD_{$fieldName}");
		return is_string($result) ? $result : '';
	}

	// Get Fields Metadata
	public static function GetFieldsInfo()
	{
		if(!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'CATALOG_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'PRICE' => array('TYPE' => 'double'),
				'CURRENCY_ID' => array('TYPE' => 'string'),
				'NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				'CODE' => array('TYPE' => 'string'),
				'DESCRIPTION' => array('TYPE' => 'string'),
				'DESCRIPTION_TYPE' => array('TYPE' => 'string'),
				'ACTIVE' => array('TYPE' => 'char'),
				'SECTION_ID' => array('TYPE' => 'integer'),
				'SORT' => array('TYPE' => 'integer'),
				'VAT_ID' => array('TYPE' => 'integer'),
				'VAT_INCLUDED' => array('TYPE' => 'char'),
				'MEASURE' => array('TYPE' => 'integer'),
				'XML_ID' => array('TYPE' => 'string'),
				'PREVIEW_PICTURE' => array('TYPE' => 'product_file'),
				'DETAIL_PICTURE' => array('TYPE' => 'product_file'),
				'DATE_CREATE' => array('TYPE' => 'datetime'),
				'TIMESTAMP_X' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Immutable)
				),
				'MODIFIED_BY' => array('TYPE' => 'integer'),
				'CREATED_BY' => array('TYPE' => 'integer')
			);
		}
		return self::$FIELD_INFOS;
	}

	//Check fields before ADD and UPDATE.
	private static function CheckFields($sAction, &$arFields, $ID)
	{
		if($sAction == 'ADD')
		{
			if (!is_set($arFields, 'ID'))
			{
				self::RegisterError('Could not find ID. ID that is treated as a IBLOCK_ELEMENT_ID.');
				return false;
			}

			$elementID = intval($arFields['ID']);
			if($elementID <= 0)
			{
				self::RegisterError('ID that is treated as a IBLOCK_ELEMENT_ID is invalid.');
				return false;
			}

			if (!self::IsIBlockElementExists($elementID))
			{
				self::RegisterError("Could not find IBlockElement(ID = $elementID).");
				return false;
			}

			if (!is_set($arFields, 'CATALOG_ID'))
			{
				self::RegisterError('Could not find CATALOG_ID. CATALOG_ID that is treated as a IBLOCK_ID.');
				return false;
			}

			$blockID = intval($arFields['CATALOG_ID']);
			if($blockID <= 0)
			{
				self::RegisterError('CATALOG_ID that is treated as a IBLOCK_ID is invalid.');
				return false;
			}

			$blocks = CIBlock::GetList(array(), array('ID' => $blockID), false);
			if (!($blocks = $blocks->Fetch()))
			{
				self::RegisterError("Could not find IBlock(ID = $blockID).");
				return false;
			}
		}
		else//if($sAction == 'UPDATE')
		{
			if(!self::Exists($ID))
			{
				self::RegisterError("Could not find CrmProduct(ID = $ID).");
				return false;
			}
		}

		return true;
	}

	private static function RegisterError($msg)
	{
		global $APPLICATION;
		$APPLICATION->ThrowException(new CAdminException(array(array('text' => $msg))));
		self::$LAST_ERROR = $msg;
	}

	private static function IsIBlockElementExists($ID)
	{
		return (CIBlockElement::GetIBlockByID($ID) !== false);
	}

	/**
	 * @param int $ID
	 * @return bool
	 */
	private static function IsAllowedDelete(int $ID): bool
	{
		if ($ID <= 0)
		{
			return true;
		}

		foreach (GetModuleEvents('crm', 'OnBeforeCrmProductDelete', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, [$ID]) === false)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @param int $ID
	 * @return void
	 */
	private static function DeleteInternal(int $ID): void
	{
		CCrmEntityHelper::RemoveCached(self::CACHE_NAME, $ID);
		foreach (GetModuleEvents('crm', 'OnCrmProductDelete', true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($ID));
		}
	}

	// <-- Service

	// Event handlers -->
	/**
	 * @return void
	 */
	private static function disableElementHandlers(): void
	{
		self::$allowElementHandlers--;
	}

	/**
	 * @return void
	 */
	private static function enableElementHandlers(): void
	{
		self::$allowElementHandlers++;
	}

	/**
	 * @return bool
	 */
	private static function allowedElementHandlers(): bool
	{
		return (self::$allowElementHandlers >= 0);
	}

	/**
	 * @param int $ID
	 * @return bool
	 */
	public static function handlerOnBeforeIBlockElementDelete($ID): bool
	{
		if (!self::allowedElementHandlers())
		{
			return true;
		}

		$ID = (int)$ID;

		$iblockId = (int)CIBlockElement::GetIBlockByID($ID);
		if ($iblockId <= 0)
		{
			return true;
		}

		$parentIblockId = 0;
		if (!isset(self::$catalogIncluded))
		{
			self::$catalogIncluded = Loader::includeModule('catalog');
		}
		if (self::$catalogIncluded)
		{
			$catalog = CCatalogSku::GetInfoByOfferIBlock($iblockId);
			if (!empty($catalog))
			{
				$parentIblockId = $catalog['PRODUCT_IBLOCK_ID'];
			}
		}
		if (
			CCrmCatalog::Exists($iblockId)
			|| ($parentIblockId > 0 && CCrmCatalog::Exists($parentIblockId))
		)
		{
			return self::IsAllowedDelete($ID);
		}

		return true;
	}

	/**
	 * @param array $element
	 * @return void
	 */
	public static function handlerOnAfterIBlockElementDelete(array $element): void
	{
		if (!self::allowedElementHandlers())
		{
			return;
		}
		self::DeleteInternal((int)($element['ID'] ?? 0));
	}

	/**
	 * @deprecated
	 * @noinspection PhpUnusedParameterInspection
	 *
	 * @param int $ID
	 * @return true
	 */
	public static function OnIBlockElementDelete($ID)
	{
		return true;
	}
	// <-- Event handlers
	// Checking User Permissions -->
	public static function CheckCreatePermission()
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}
	public static function CheckUpdatePermission($ID)
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}
	public static function CheckDeletePermission($ID)
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}
	public static function CheckReadPermission($ID = 0)
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ');
	}
	// <-- Checking User Permissions
}
