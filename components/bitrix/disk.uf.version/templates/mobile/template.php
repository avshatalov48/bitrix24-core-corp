<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var \Bitrix\Disk\Internals\BaseComponent $component */

?><div id="wdif-version-block-<?=$arResult['UID']?>" class="post-item-file-version-wrap"><?
foreach($arResult['VERSIONS'] as $version)
{
	$title = Loc::getMessage('DISK_UF_VERSION_HISTORY_FILE_MOBILE', array('#NUMBER#' => $version['GLOBAL_CONTENT_VERSION']));
	if($arResult['ONLY_HEAD_VERSION'])
	{
		$title = Loc::getMessage('DISK_UF_HEAD_VERSION_HISTORY_FILE_MOBILE');
	}

	?><div class="post-item-file-version"><?= Loc::getMessage('DISK_UF_VERSION_HISTORY_FILE_MOBILE', array('#NUMBER#' => $version['GLOBAL_CONTENT_VERSION'])) ?></div><?
	?><div id="wdif-doc-version-<?=$version['ID'] . $arResult['UID']?>" class="post-item-attached-file"><?
		if (in_array(ToLower($version["EXTENSION"]), array("exe")))
		{
			?><span><?
				?><span><?=htmlspecialcharsbx($version['NAME'])?></span><span>(<?=$version['SIZE']?>)</span><?
			?></span><?
		}
		else
		{
			?><a 
				onclick="app.openDocument({'url' : '<?=$version['DOWNLOAD_URL']?>'});" 
				href="javascript:void()" 
				class="post-item-attached-file-link" 
			><?
					?><span><?=htmlspecialcharsbx($version['NAME'])?></span><?
					?><span>(<?=$version['SIZE']?>)</span><?
			?></a><?
		}
	?></div><?
}
?></div>
