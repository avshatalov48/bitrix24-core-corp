<?php
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
?>

<div class='bx-disk-container posr'>
<?php
global $APPLICATION;

$file = \Bitrix\Disk\File::loadById($arResult['VARIABLES']['FILE_ID']);
if (!$file)
{
	return;
}

$componentParameters = array_merge(
	array_intersect_key(
		$arResult,
		array(
			'STORAGE' => true,
			'PATH_TO_FOLDER_LIST' => true,
			'PATH_TO_FILE_VIEW' => true,
			'PATH_TO_DISK_START_BIZPROC' => true,
			'PATH_TO_DISK_TASK' => true,
			'PATH_TO_FILE_HISTORY' => true,
		)
	),
	array(
		'STORAGE' => $file->getStorage(),
		'FILE' => $file,
		'FILE_ID' => $arResult['VARIABLES']['FILE_ID'],
	)
);

$APPLICATION->IncludeComponent(
	'bitrix:disk.file.history',
	"",
	$componentParameters
);
?>
</div>