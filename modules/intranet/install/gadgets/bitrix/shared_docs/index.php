<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$diskEnabled =
	\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) === 'Y' &&
	CModule::includeModule('disk');

$arGadgetParams["LIST_URL"] = (isset($arGadgetParams["LIST_URL"])?$arGadgetParams["LIST_URL"]:"/docs/");
$arGadgetParams["DETAIL_URL"] = (isset($arGadgetParams["DETAIL_URL"])?$arGadgetParams["DETAIL_URL"]:"/docs/shared/element/view/#ELEMENT_ID#/");
$arGadgetParams["DOCS_COUNT"] = (isset($arGadgetParams["DOCS_COUNT"])?$arGadgetParams["DOCS_COUNT"]:"5");

if (
	!isset($arGadgetParams["IBLOCK_TYPE"]) 
	|| empty($arGadgetParams["IBLOCK_TYPE"])
)
{
	$arGadgetParams["IBLOCK_TYPE"] = "library";
}

$emptyIblockId = !isset($arGadgetParams["IBLOCK_ID"]) || intval($arGadgetParams["IBLOCK_ID"]) <= 0;
if ($emptyIblockId)
{
	if (CModule::IncludeModule("iblock"))
	{
		$dbRes = CIBlock::GetList(
			Array(), 
			Array(
				'TYPE' => $arGadgetParams["IBLOCK_TYPE"], 
				'SITE_ID' => SITE_ID, 
				'ACTIVE' => 'Y', 
				'CODE' => 'shared_files_'.SITE_ID
			)
		);
		if ($arRes = $dbRes->Fetch())
		{
			$arGadgetParams["IBLOCK_ID"] = $arRes["ID"];
		}	
	}
}
if($diskEnabled)
{
	$storage = null;
	if($arGadgetParams['STORAGE_ID'])
	{
		$storage = \Bitrix\Disk\Storage::loadById($arGadgetParams['STORAGE_ID'], array('ROOT_OBJECT'));
	}
	elseif($emptyIblockId)
	{
		$storage = \Bitrix\Disk\Driver::getInstance()->getStorageByCommonId('shared_files_' . SITE_ID);
	}
	elseif($arGadgetParams['IBLOCK_ID'])
	{
		$storage = \Bitrix\Disk\Storage::load(array(
			'MODULE_ID' => \Bitrix\Disk\Driver::INTERNAL_MODULE_ID,
			'ENTITY_TYPE' => \Bitrix\Disk\ProxyType\Common::className(),
			'XML_ID' => (int)$arGadgetParams['IBLOCK_ID'],
		), array('ROOT_OBJECT'));
	}

	if($storage)
	{
		$APPLICATION->IncludeComponent(
			"bitrix:disk.last.files",
			".default",
			array(
				'MAX_COUNT_FILES' => $arGadgetParams["DOCS_COUNT"],
				'STORAGE' => $storage,
			),
			false,
			array("HIDE_ICONS"=>"Y")
		);
		return;
	}
}


?>
<?$APPLICATION->IncludeComponent(
	"bitrix:webdav.list",
	".default",
	Array(
		"IBLOCK_TYPE"	=>	$arGadgetParams["IBLOCK_TYPE"],
		"IBLOCK_ID"	=>	$arGadgetParams["IBLOCK_ID"],
		"DOCS_COUNT" => $arGadgetParams["DOCS_COUNT"],
		"DOCS_COUNT_NEED_USE" => "Y",
		"DETAIL_URL" => $arGadgetParams["DETAIL_URL"],
		"DISPLAY_DATE" => $arGadgetParams["DISPLAY_DATE"],
		"DISPLAY_PICTURE" => $arGadgetParams["DISPLAY_PICTURE"],
		"DISPLAY_PREVIEW_TEXT" => $arGadgetParams["DISPLAY_PREVIEW_TEXT"],
		"CACHE_TYPE" => $arGadgetParams["CACHE_TYPE"],
		"CACHE_TIME" => $arGadgetParams["CACHE_TIME"],

		"SORT_BY1" => "TIMESTAMP_X",
		"SORT_ORDER1" => "DESC",
		"SORT_BY2" => "SORT",
		"SORT_ORDER2" => "ASC",
		"FILTER_NAME" => "",
		"FIELD_CODE" => array(0=>"TIMESTAMP_X",1=>"",2=>"",),
		"PROPERTY_CODE" => array(0=>"",1=>"",2=>"",),
		"AJAX_MODE" => "N",
		"AJAX_OPTION_SHADOW" => "Y",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"AJAX_OPTION_HISTORY" => "N",
		"CACHE_FILTER" => "N",
		"PREVIEW_TRUNCATE_LEN" => "",
		"ACTIVE_DATE_FORMAT" => $arParams["DATE_FORMAT"],
		"DISPLAY_PANEL" => "N",
		"SET_TITLE" => "N",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "Y",
		"ADD_SECTIONS_CHAIN" => "Y",
		"HIDE_LINK_WHEN_NO_DETAIL" => "N",
		"PARENT_SECTION" => "",
		"DISPLAY_TOP_PAGER" => "N",
		"DISPLAY_BOTTOM_PAGER" => "N",
		"PAGER_TITLE" => "",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => "",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"DISPLAY_NAME" => "Y",
	),
	false,
	Array("HIDE_ICONS"=>"Y")
);?>

<?if(strlen($arGadgetParams["LIST_URL"])>0):?>
<br />
<div align="right"><a href="<?=htmlspecialcharsbx($arGadgetParams["LIST_URL"])?>"><?echo GetMessage("GD_SHARED_DOCS_MORE")?></a> <a href="<?=htmlspecialcharsbx($arGadgetParams["LIST_URL"])?>"><img width="7" height="7" border="0" src="/images/icons/arrows.gif" /></a>
<br /></div>
<?endif?>
