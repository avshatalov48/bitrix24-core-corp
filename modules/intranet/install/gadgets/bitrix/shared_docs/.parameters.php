<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentProps = CComponentUtil::GetComponentProps("bitrix:webdav.list", $arCurrentValues);

$diskEnabled =
	\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) === 'Y' &&
	CModule::includeModule('disk');

$arParameters = Array(
		"PARAMETERS"=> Array(
			"IBLOCK_TYPE"	=>	$arComponentProps["PARAMETERS"]["IBLOCK_TYPE"],
			"IBLOCK_ID"	=>	$arComponentProps["PARAMETERS"]["IBLOCK_ID"],
			"LIST_URL"	=> Array(
				"NAME" => GetMessage("GD_SHARED_DOCS_P_URL"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "/docs/",
			),
			"DETAIL_URL"	=> $arComponentProps["PARAMETERS"]["DETAIL_URL"],
			"CACHE_TYPE"=>$arComponentProps["PARAMETERS"]["CACHE_TYPE"],
			"CACHE_TIME"=>$arComponentProps["PARAMETERS"]["CACHE_TIME"],
		),
		"USER_PARAMETERS"=> Array(
			"DOCS_COUNT" => Array(
				"NAME" => GetMessage("GD_SHARED_DOCS_P_CNT"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "5",
			),
			"DISPLAY_DATE"	=>	Array(
					"NAME" => GetMessage("GD_SHARED_DOCS_P_DATA"),
					"TYPE" => "CHECKBOX",
					"DEFAULT" => "Y",
				),
			"DISPLAY_PICTURE"	=>	Array(
					"NAME" => GetMessage("GD_SHARED_DOCS_P_PIC"),
					"TYPE" => "CHECKBOX",
					"DEFAULT" => "Y",
				),
			"DISPLAY_PREVIEW_TEXT"	=>	Array(
					"NAME" => GetMessage("GD_SHARED_DOCS_P_PREV"),
					"TYPE" => "CHECKBOX",
					"DEFAULT" => "Y",
				),
		),
	);

$arParameters["PARAMETERS"]["DETAIL_URL"]["DEFAULT"] = "/docs/shared/element/view/#ELEMENT_ID#/";

if($diskEnabled)
{
	$commonStorages = array();
	$query = \Bitrix\Disk\Storage::getList(array("filter"=>array("=ENTITY_TYPE" => Bitrix\Disk\ProxyType\Common::className())));
	while($row = $query->fetch())
	{
		$commonStorages[$row['ID']] = $row['NAME'];
	}

	$arParameters = array(
		'USER_PARAMETERS' => array(
			'DOCS_COUNT' => $arParameters['USER_PARAMETERS']['DOCS_COUNT'],
		),
		'PARAMETERS' => array(
			"STORAGE_ID" => Array(
				"PARENT" => "BASE",
				"NAME" => GetMessage("T_COMMON_DISK_LIST_STORAGE_ID"),
				"TYPE" => "LIST",
				"VALUES" => $commonStorages,
				"DEFAULT" => '={$_REQUEST["ID"]}',
				"ADDITIONAL_VALUES" => "Y",
				"REFRESH" => "Y",
			),
		),
	);
}
?>
