<?php
use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
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

?>
<div class="news-list">
<?
foreach($arResult['FILES'] as $file)
{
	?>
		<p class="docs-item">

		</p>

		<div class="news-date-time intranet-date"><?= htmlspecialcharsbx($file['UPDATE_DATE']) ?></div>
		<a href="<?= $file['VIEW_URL'] ?>"><?= htmlspecialcharsbx($file['NAME']) ?></a><br>

		<p></p>
	<?
}
unset($file);

?>
</div>