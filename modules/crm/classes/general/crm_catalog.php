<?php
use Bitrix\Main\Loader;
use Bitrix\Catalog;
use Bitrix\Iblock;
use Bitrix\Crm;

if (!Loader::includeModule('iblock'))
{
	return;
}

IncludeModuleLangFile(__FILE__);

/*
 * CRM Product Catalogue.
 * It is based on IBlock module.
 * */
class CAllCrmCatalog
{
	const CACHE_NAME = 'CRM_CATALOG_CACHE';
	const TABLE_ALIAS = 'C';

	//Is used as default IBlock Type ID
	const CATALOG_TYPE_ID = 'CRM_PRODUCT_CATALOG';

	protected static $DEFAULT_CATALOG_XML_ID = null;

	protected static $FIELD_INFOS = null;
	protected static $LAST_ERROR = '';

	//Catalog Type ID is used as IBlock Type ID
	public static function GetCatalogTypeID()
	{
		$result = COption::GetOptionString('crm', 'product_catalog_type_id', '');
		return isset($result[0]) ? $result : self::CATALOG_TYPE_ID;
	}

	public static function GetCatalogId($externalName = "", $originatorID = 0, $siteID = null)
	{
		$iblockType = self::GetCatalogTypeID();
		$iblockId = 0;
		$catalogId = 0;

		if ($siteID == null)
			$siteID = SITE_ID;

		$dbIBlockType = CIBlockType::GetList(array(), array("=ID" => $iblockType));
		if (!($arIBlockType = $dbIBlockType->Fetch()))
		{
			$langTmp = "";
			$dbSite = CSite::GetByID($siteID);
			if ($arSite = $dbSite->Fetch())
				$langTmp = $arSite["LANGUAGE_ID"];

			$ib = new CIBlockType;
			$arFields = Array(
				"ID" => $iblockType,
				"LANG" => array($langTmp => array("NAME" => GetMessage("CRM_PROCUCT_CATALOG_TITLE")))
			);
			$ib->Add($arFields);
		}

		$dbIBlock = CIBlock::GetList(array(), array("XML_ID" => "crm_external_".$originatorID, "IBLOCK_TYPE_ID" => $iblockType));
		if ($arIBlock = $dbIBlock->Fetch())
			$iblockId = $arIBlock["ID"];

		if ($iblockId == 0)
		{
			$ib = new CIBlock();
			$arFields = array(
				"IBLOCK_TYPE_ID" => $iblockType,
				"XML_ID" => "crm_external_".$originatorID,
				"LID" => $siteID,
				"NAME" => $externalName,
				"ACTIVE" => 'Y',
				"SORT" => 100,
				"INDEX_ELEMENT" => "N",
				"WORKFLOW" => 'N',
				"BIZPROC" => 'N',
				"VERSION" => 1,
				"GROUP_ID" => array(1 => "X", 2 => "R"),
				"LIST_MODE" => 'S'
			);

			$iblockId = $ib->Add($arFields);
			$iblockId = intval($iblockId);
			if ($iblockId <= 0)
			{
				self::RegisterError($ib->LAST_ERROR);
				return false;
			}
		}

		$dbCatalog = CCrmCatalog::GetList(array(), array("IBLOCK_ID" => $iblockId));
		if ($arCatalog = $dbCatalog->Fetch())
			$catalogId = $arCatalog["ID"];

		if ($catalogId == 0)
		{
			$res = CCrmCatalog::Add(array(
				"ID" => $iblockId,
				"ORIGINATOR_ID" => $originatorID,
			));
			if (!$res)
			{
				/** @var $ex \CAdminException */
				if (($ex = $GLOBALS["APPLICATION"]->GetException()) !== false)
					self::RegisterError($ex->GetString());
				else
					self::RegisterError('Catalog creation error');

				return false;
			}

			$catalogId = $iblockId;
		}

		return $catalogId;
	}

	public static function GetFieldCaption($fieldName)
	{
		$result = GetMessage("CRM_CATALOG_FIELD_{$fieldName}");
		return is_string($result) ? $result : '';
	}

	public static function GetFieldsInfo()
	{
		if(!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'NAME' =>  array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Immutable)
				),
				'ORIGINATOR_ID' => array('TYPE' => 'string'),
				'ORIGIN_ID' => array('TYPE' => 'string'),
				'XML_ID' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				)
			);
		}

		return self::$FIELD_INFOS;
	}

	public static function Add($arFields)
	{
		if (!Loader::includeModule('catalog'))
		{
			return false;
		}

		self::$LAST_ERROR = '';
		global $DB;
		$tableName = CCrmCatalog::TABLE_NAME;

		if (!self::CheckFields('ADD', $arFields, 0))
		{
			return false;
		}

		$DB->Add($tableName, $arFields, array(), '');

		if($DB->db_Error <> '')
		{
			self::RegisterError($DB->db_Error);
			return false;
		}

		// -------------- register in catalog module -------------->
		$catalogId = $arFields['ID'];
		$arFields = array(
			'IBLOCK_ID' => $catalogId
		);

		// get default vat
		// TODO: replace this code to use preset vat id
		$defCatVatId = 0;
		$iterator = \Bitrix\Catalog\VatTable::getList([
			'select' => ['ID', 'SORT'],
			'order' => ['SORT' => 'ASC'],
			'limit' => 1
		]);
		$vat = $iterator->fetch();
		if (!empty($vat))
		{
			$defCatVatId = (int)$vat['ID'];
		}
		unset($vat, $iterator);
		if ($defCatVatId > 0)
		{
			$arFields['VAT_ID'] = $defCatVatId;
		}
		unset($defCatVatId);

		// add crm iblock to catalog
		$CCatalog = new CCatalog();
		$dbRes = $CCatalog->GetList(array(), array('ID' => $catalogId), false, false, array('ID'));
		if (!$dbRes->Fetch())    // if catalog iblock is not exists
		{
			if ($CCatalog->Add($arFields))
			{
				COption::SetOptionString('catalog', 'save_product_without_price', 'Y');
				COption::SetOptionString('catalog', 'default_can_buy_zero', 'Y');
			}
			else
			{
				self::RegisterError(GetMessage('CRM_ERR_REGISTER_CATALOG'));
				return false;
			}
		}
		// <------------- register in catalog module --------------

		return true;
	}

	public static function Update($ID, $arFields)
	{
		self::$LAST_ERROR = '';

		global $DB;
		$tableName = CCrmCatalog::TABLE_NAME;

		if (!self::CheckFields('UPDATE', $arFields, $ID))
		{
			return false;
		}

		$sUpdate = trim($DB->PrepareUpdate($tableName, $arFields));
		if (!empty($sUpdate))
		{
			$sQuery = 'UPDATE '.$tableName.' SET '.$sUpdate.' WHERE ID = '.$ID;
			$DB->Query($sQuery);

			CCrmEntityHelper::RemoveCached(self::CACHE_NAME, $ID);
		}

		return true;
	}

	public static function Delete($ID)
	{
		if (!Loader::includeModule('catalog'))
		{
			return false;
		}

		self::$LAST_ERROR = '';
		global $DB;
		$tableName = CCrmCatalog::TABLE_NAME;

		$ID = intval($ID);

		if(!is_array(self::GetByID($ID)))
		{
			// Is no exists
			return true;
		}

		$events = GetModuleEvents('crm', 'OnBeforeCrmCatalogDelete');
		while ($arEvent = $events->Fetch())
		{
			if (ExecuteModuleEventEx($arEvent, array($ID)) === false)
			{
				return false;
			}
		}

		$dbRes = CCrmProduct::GetList(array(), array('CATALOG_ID' => $ID), array('ID'));
		while ($arRes = $dbRes->Fetch())
		{
			$productID = $arRes['ID'];
			if (!CCrmProduct::Delete($productID))
			{
				self::RegisterError(sprintf('Deletion of CrmCatalog(ID=%d) is canceled. Could not delete CrmProduct(ID = %d).', $ID, $productID));
				return false;
			}
		}

		if(!$DB->Query('DELETE FROM '.$tableName.' WHERE ID = '.$ID, true))
		{
			return false;
		}

		// -------------- remove from catalog module -------------->
		$CCatalog = new CCatalog();
		if (!$CCatalog->Delete($ID))
		{
			return false;
		}
		// <-------------- remove from catalog module --------------

		CCrmEntityHelper::RemoveCached(self::CACHE_NAME, $ID);

		$events = GetModuleEvents('crm', 'OnCrmCatalogDelete');
		while ($arEvent = $events->Fetch())
		{
			ExecuteModuleEventEx($arEvent, array($ID));
		}

		return true;
	}

	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		$lb = new CCrmEntityListBuilder(
			CCrmCatalog::DB_TYPE,
			CCrmCatalog::TABLE_NAME,
			self::TABLE_ALIAS,
			self::GetFields(),
			'',
			'',
			array()
		);

		return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
	}

	public static function GetAllIDs()
	{
		$result = array();
		$dbResult = self::GetList(array(), array(), false, false, array('ID'));
		while($fields = $dbResult->Fetch())
		{
			$result[] = (int)$fields['ID'];
		}
		return $result;
	}

	// Service -->
	public static function Exists($ID)
	{
		$dbRes = CCrmCatalog::GetList(array(), array('ID'=> $ID), false, false, array('ID'));
		return $dbRes->Fetch() ? true : false;
	}

	protected static function GetFields()
	{
		return
			array
			(
				'ID' => array('FIELD' => 'C.ID', 'TYPE' => 'int'),
				'IBLOCK_ID' => array('FIELD' => 'C.ID', 'TYPE' => 'int'),
				'ORIGINATOR_ID' => array('FIELD' => 'C.ORIGINATOR_ID', 'TYPE' => 'string'),
				'ORIGIN_ID' => array('FIELD' => 'C.ORIGIN_ID', 'TYPE' => 'string'),
				//'IBLOCK_TYPE_ID' => array('FIELD' => 'I.IBLOCK_TYPE_ID', 'TYPE' => 'int', 'FROM' => 'INNER JOIN b_iblock I ON C.ID = I.ID'),
				'NAME' => array('FIELD' => 'I.NAME', 'TYPE' => 'string', 'FROM' => 'INNER JOIN b_iblock I ON C.ID = I.ID'),
				'XML_ID' => array('FIELD' => 'I.XML_ID', 'TYPE' => 'string', 'FROM' => 'INNER JOIN b_iblock I ON C.ID = I.ID')
			);
	}

	/*
	 * Check fields before ADD and UPDATE.
	 * */
	private static function CheckFields($sAction, &$arFields, $ID)
	{
		self::$LAST_ERROR = '';

		if($sAction == 'ADD')
		{
			if (!isset($arFields['ID']))
			{
				self::RegisterError('Could not find ID.');
				return false;
			}


			$iblockID = intval($arFields['ID']);
			if($iblockID <= 0)
			{
				self::RegisterError('ID that is treated as a IBLOCK_ID is invalid.');
				return false;
			}

			if (intval(CIBlock::GetArrayByID($iblockID, 'ID')) !== $iblockID)
			{
				self::RegisterError(sprintf('Could not find IBlock(ID = %d).', $iblockID));
				return false;
			}
		}
		else//if($sAction == 'UPDATE')
		{
			if(!self::Exists($ID))
			{
				self::RegisterError(sprintf('Could not find CrmCatalog(ID = %d).', $ID));
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
	// <-- Service

	// Contract -->
	public static function GetByID($ID)
	{
		$arResult = CCrmEntityHelper::GetCached(self::CACHE_NAME, $ID);
		if (is_array($arResult))
		{
			return $arResult;
		}

		$dbRes = CCrmCatalog::GetList(array(), array('ID' => intval($ID)));
		$arResult = $dbRes->Fetch();

		if(is_array($arResult))
		{
			CCrmEntityHelper::SetCached(self::CACHE_NAME, $ID, $arResult);
		}
		return $arResult;
	}

	/**
	 * @deprecated
	 * @see Crm\Product\Catalog::getDefaultId
	 *
	 * @return int
	 */
	public static function GetDefaultID(): int
	{
		return (int)Crm\Product\Catalog::getDefaultId();
	}

	public static function EnsureDefaultExists()
	{
		$ID = (int)Crm\Product\Catalog::getDefaultId();

		// Create new IBlock
		if($ID <= 0)
		{
			if(($ID = self::CreateCatalog()) > 0)
			{
				COption::SetOptionString('crm', 'default_product_catalog_id', $ID);
				self::setCrmGroupRights($ID);
			}
		}
		return $ID;
	}

	public static function GetDefaultCatalogXmlId()
	{
		if (self::$DEFAULT_CATALOG_XML_ID === null)
		{
			$catalogId = intval(self::EnsureDefaultExists());
			if ($catalogId > 0)
			{
				$ib = new CIBlock();
				$arIb = $ib->GetByID($catalogId)->Fetch();
				if (is_array($arIb) && isset($arIb['XML_ID']) && !empty($arIb['XML_ID']))
					self::$DEFAULT_CATALOG_XML_ID = $arIb['XML_ID'];
			}

			if (self::$DEFAULT_CATALOG_XML_ID === null)
				self::$DEFAULT_CATALOG_XML_ID = '';
		}

		return self::$DEFAULT_CATALOG_XML_ID;
	}

	public static function CreateCatalog($originatorID = '', $name = '', $siteID = null)
	{
		if(!is_string($originatorID) || $originatorID == '')
		{
			$originatorID = null;
		}

		if ($siteID == null)
		{
			$siteID = SITE_ID;
		}

		$langID = LANGUAGE_ID;
		$dbSite = CSite::GetById($siteID);
		if ($arSite = $dbSite->Fetch())
		{
			$langID = $arSite['LANGUAGE_ID'];
		}

		//check type type
		$typeID = self::GetCatalogTypeID();
		//$rsIBlockTypes = CIBlockType::GetByID($typeID); // CIBlockType::GetByID() is unstable
		$rsIBlockTypes = CIBlockType::GetList(array(), array("=ID" => $typeID));
		if (!$rsIBlockTypes->Fetch())
		{
			$iblocktype = new CIBlockType();

			$result = $iblocktype->Add(
				array(
					'ID' => $typeID,
					'SECTIONS' => 'Y',
					'IN_RSS'=>'N',
					'SORT' => 100,
					'LANG' => array(
						$langID => array(
							'NAME' => GetMessage('CRM_PRODUCT_CATALOG_TYPE_TITLE'),
							'SECTION_NAME'=> GetMessage('CRM_PRODUCT_CATALOG_SECTION_NAME'),
							'ELEMENT_NAME'=> GetMessage('CRM_PRODUCT_CATALOG_PRODUCT_NAME')
						)
					)
				)
			);

			if(!$result)
			{
				self::RegisterError($iblocktype->LAST_ERROR);
				return false;
			}
		}

		$catalogTitle = ($name != '' ? $name : GetMessage('CRM_PRODUCT_CATALOG_TITLE'));
		$offersTitle = GetMessage(
			'CRM_PRODUCT_CATALOG_OFFERS_TITLE_FORMAT',
			['#CATALOG#' => $catalogTitle]
		);

		$fields = \CIBlock::GetFieldsDefaults();

		$code = $fields['CODE'];
		$code['DEFAULT_VALUE'] = unserialize($code['DEFAULT_VALUE'], ['allowed_classes' => false]);
		$code['DEFAULT_VALUE']['TRANSLITERATION'] = 'Y';
		$code['DEFAULT_VALUE']['USE_GOOGLE'] = 'N';
		$code['DEFAULT_VALUE']['TRANS_LEN'] = 255;

		$sectionCode = $fields['SECTION_CODE'];
		$sectionCode['DEFAULT_VALUE'] = unserialize($sectionCode['DEFAULT_VALUE'], ['allowed_classes' => false]);
		$sectionCode['DEFAULT_VALUE']['TRANSLITERATION'] = 'Y';
		$sectionCode['DEFAULT_VALUE']['USE_GOOGLE'] = 'N';
		$sectionCode['DEFAULT_VALUE']['TRANS_LEN'] = 255;

		$fields['CODE'] = $code;
		$fields['SECTION_CODE'] = $sectionCode;
		unset($sectionCode, $code);

		$isB24 = Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24');

		//creation of iblock
		$iblock = new CIBlock();
		$iblockID = $iblock->Add(
			array(
				'NAME' => $catalogTitle,
				'ACTIVE' => 'Y',
				'IBLOCK_TYPE_ID' => $typeID,
				'LID' => $siteID,
				'SORT' => 100,
				'XML_ID' => 'crm_external_'.$originatorID,
				'INDEX_ELEMENT' => 'N',
				'WORKFLOW' => 'N',
				'BIZPROC' => 'N',
				'VERSION' => 1,
				'GROUP_ID' => array(1 => 'X', 2 => 'R'),
				'LIST_MODE' => Iblock\IblockTable::LIST_MODE_COMBINED,
				'FIELDS' => $fields,
				'FULLTEXT_INDEX' => $isB24 ? 'Y' : 'N',
			)
		);

		if($iblockID === false)
		{
			self::RegisterError($iblock->LAST_ERROR);
			return false;
		}

		self::createMorePhoto($iblockID);

		//creation of catalog
		$result = CCrmCatalog::Add(
			array
			(
				'ID' => $iblockID,
				'ORIGINATOR_ID' => $originatorID
			)
		);

		if($result === false)
		{
			self::RegisterError('Catalog creation error');
			return false;
		}

		if (Loader::includeModule('catalog'))
		{
			$offersId = $iblock->Add(
				[
					'NAME' => $offersTitle,
					'ACTIVE' => 'Y',
					'IBLOCK_TYPE_ID' => $typeID,
					'LID' => $siteID,
					'SORT' => 200,
					'XML_ID' => 'crm_external_offers_'.$originatorID,
					'INDEX_ELEMENT' => 'N',
					'WORKFLOW' => 'N',
					'BIZPROC' => 'N',
					'VERSION' => 1,
					'GROUP_ID' => array(1 => 'X', 2 => 'R'),
					'LIST_MODE' => 'S',
					'FIELDS' => $fields,
					'FULLTEXT_INDEX' => $isB24 ? 'Y' : 'N',
				]
			);
			if ($offersId === false)
			{
				self::RegisterError($iblock->LAST_ERROR);
				return false;
			}

			$propertyId = \CIBlockPropertyTools::createProperty(
				$offersId,
				\CIBlockPropertyTools::CODE_SKU_LINK,
				['LINK_IBLOCK_ID' => $iblockID]
			);
			if (!$propertyId)
			{
				foreach (CIBlockPropertyTools::getErrors() as $propertyError)
					self::RegisterError($propertyError);
				return false;
			}

			self::createMorePhoto($offersId);

			$offersFields = [
				'IBLOCK_ID' => $offersId,
				'PRODUCT_IBLOCK_ID' => $iblockID,
				'SKU_PROPERTY_ID' => $propertyId
			];
			// get default vat
			$iterator = Catalog\VatTable::getList([
				'select' => ['ID', 'SORT'],
				'order' => ['SORT' => 'ASC'],
				'limit' => 1
			]);
			$row = $iterator->fetch();
			unset($iterator);
			if (!empty($row))
				$offersFields['VAT_ID'] = (int)$row['ID'];
			unset($row);

			if (!\CCatalog::Add($offersFields))
			{
				self::RegisterError(GetMessage('CRM_ERR_REGISTER_OFFERS'));
				return false;
			}
		}

		return $iblockID;
	}

	public static function GetLastError()
	{
		return self::$LAST_ERROR;
	}
	// <-- Contract
	// Event handlers -->
	public static function OnIBlockDelete($ID)
	{
		return CCrmCatalog::Delete($ID);
	}
	// <-- Event handlers

	/**
	 * @param $iblockId
	 * @return void
	 *
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected static function setCrmGroupRights($iblockId)
	{
		$iblockTypeId = self::GetCatalogTypeID();
		$crmAdminGroupId = \CCrmSaleHelper::getShopGroupIdByType('admin');
		$crmManagerGroupId = \CCrmSaleHelper::getShopGroupIdByType('manager');
		if (!empty($crmAdminGroupId))
		{
			\CIBlockRights::setGroupRight($crmAdminGroupId, $iblockTypeId, 'X', $iblockId);
		}
		if (!empty($crmManagerGroupId))
		{
			\CIBlockRights::setGroupRight($crmManagerGroupId, $iblockTypeId, 'W', $iblockId);
		}
		if (Loader::includeModule('catalog'))
		{
			$catalog = \CCatalogSku::GetInfoByProductIBlock($iblockId);
			if (!empty($catalog))
			{
				if (!empty($crmAdminGroupId))
				{
					\CIBlockRights::setGroupRight($crmAdminGroupId, $iblockTypeId, 'X', $catalog['IBLOCK_ID']);
				}
				if (!empty($crmManagerGroupId))
				{
					\CIBlockRights::setGroupRight($crmManagerGroupId, $iblockTypeId, 'W', $catalog['IBLOCK_ID']);
				}
			}
			unset($catalog);
		}
		unset($crmManagerGroupId, $crmAdminGroupId);
		unset($iblockTypeId);
	}

	// TODO: remove after refactoring
	private static function createMorePhoto(int $iblockId): void
	{
		if (!Loader::includeModule('iblock'))
		{
			return;
		}

		$propertyId = \CIBlockPropertyTools::createProperty(
			$iblockId,
			\CIBlockPropertyTools::CODE_MORE_PHOTO
		);
		if (empty($propertyId))
		{
			return;
		}

		$features = [];
		$iterator = Iblock\PropertyFeatureTable::getList([
			'select' => ['*'],
			'filter' => ['=PROPERTY_ID' => $propertyId]
		]);
		while ($row = $iterator->fetch())
		{
			$features[] = [
				'MODULE_ID' => $row['MODULE_ID'],
				'FEATURE_ID' => $row['FEATURE_ID'],
				'IS_ENABLED' => $row['IS_ENABLED']
			];
		}
		unset($row, $iterator);
		$features[] = [
			'MODULE_ID' => 'iblock',
			'FEATURE_ID' => Iblock\Model\PropertyFeature::FEATURE_ID_LIST_PAGE_SHOW,
			'IS_ENABLED' => 'Y'
		];
		$features[] = [
			'MODULE_ID' => 'iblock',
			'FEATURE_ID' => Iblock\Model\PropertyFeature::FEATURE_ID_DETAIL_PAGE_SHOW,
			'IS_ENABLED' => 'Y'
		];

		$internaResult = Iblock\Model\PropertyFeature::setFeatures($propertyId, $features);
		$result = $internaResult->isSuccess();
		unset($features, $internaResult);
	}
}