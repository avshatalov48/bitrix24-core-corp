<?php
use Bitrix\Disk\Desktop;
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
?>

<div class="bx-disk-container posr">
	<table style="width: 100%;" cellpadding="0" cellspacing="0">
		<tr>
			<td>
				<?php
				$APPLICATION->IncludeComponent(
					'bitrix:disk.folder.list',
					'',
					array_merge(array_intersect_key($arResult, array(
						'STORAGE' => true,
						'PATH_TO_TRASHCAN_FILE_VIEW' => true,
						'PATH_TO_TRASHCAN_LIST' => true,
						'PATH_TO_FOLDER_LIST' => true,
						'PATH_TO_FILE_VIEW' => true,
						'PATH_TO_FILE_HISTORY' => true,
					)), array(
						'TRASH_MODE' => true,
						'FOLDER_ID' => $arResult['VARIABLES']['FOLDER_ID'],
						'RELATIVE_PATH' => $arResult['VARIABLES']['RELATIVE_PATH'],
						'RELATIVE_ITEMS' => $arResult['VARIABLES']['RELATIVE_ITEMS'],
					)),
					$component
				);?>
			</td>
		</tr>

	</table>
</div>
