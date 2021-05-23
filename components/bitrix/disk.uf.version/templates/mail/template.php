<?php
use Bitrix\Main\Localization\Loc;

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

?><div><?
foreach($arResult['VERSIONS'] as $version)
{
	$title = Loc::getMessage('DISK_UF_VERSION_HISTORY_FILE_MAIL', array('#NUMBER#' => $version['GLOBAL_CONTENT_VERSION']));
	if($arResult['ONLY_HEAD_VERSION'])
	{
		$title = Loc::getMessage('DISK_UF_HEAD_VERSION_HISTORY_FILE_MAIL');
	}

	?><div class="post-item-file-version"><?= $title ?></div><?
	?><div><?
		?><span><?
			?><span><?=htmlspecialcharsbx($version['NAME'])?></span><span>(<?=$version['SIZE']?>)</span><?
		?></span><?
	?></div><?
}
?></div>
