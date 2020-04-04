<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

foreach($arResult['VERSIONS'] as $id => $version)
{
	if (isset($version["DOWNLOAD_URL"]))
	{
		$version["DOWNLOAD_URL"] = str_replace("/bitrix/tools/disk/uf.php", SITE_DIR."mobile/ajax.php", $version["DOWNLOAD_URL"]);	
		$version["DOWNLOAD_URL"] = $version["DOWNLOAD_URL"].(strpos($version["DOWNLOAD_URL"], "?") === false ? "?" : "&")."mobile_action=disk_uf_view&filename={$version['NAME']}";
		
		$arResult["VERSIONS"][$id] = $version;		
	}
}
?>