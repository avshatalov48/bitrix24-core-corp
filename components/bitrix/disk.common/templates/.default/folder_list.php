<?php
use Bitrix\Disk\Banner;
use Bitrix\Disk\Desktop;
use Bitrix\Disk\Integration\Bitrix24Manager;
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

$folder = \Bitrix\Disk\Folder::loadById($arResult['VARIABLES']['FOLDER_ID']);
?>
<div class="bx-disk-container posr" id="bx-disk-container">
			<div class="bx-disk-grid-left">
				<?php
				$APPLICATION->IncludeComponent(
					'bitrix:disk.folder.list',
					'',
					array_merge(array_intersect_key($arResult, array(
						'STORAGE' => true,
						'PATH_TO_FOLDER_LIST' => true,
						'PATH_TO_FILE_HISTORY' => true,
						'PATH_TO_FILE_VIEW' => true,
						'PATH_TO_DISK_BIZPROC_WORKFLOW_ADMIN' => true,
						'PATH_TO_DISK_START_BIZPROC' => true,
						'PATH_TO_DISK_TASK_LIST' => true,
						'PATH_TO_DISK_TASK' => true,
						'PATH_TO_DISK_VOLUME' => true,
					)), array(
						'FOLDER' => $folder,
						'RELATIVE_PATH' => $arResult['VARIABLES']['RELATIVE_PATH'],
						'RELATIVE_ITEMS' => $arResult['VARIABLES']['RELATIVE_ITEMS'],
					)),
					$component
				);?>
			</div>
<?php
if (Bitrix24Manager::isFeatureEnabled('disk_common_storage'))
{
	$APPLICATION->IncludeComponent(
		'bitrix:disk.file.upload',
		'',
		[
			'STORAGE' => $arResult['STORAGE'],
			'FOLDER' => $folder,
			'CID' => 'FolderList',
			'DROPZONE' => 'BX("bx-disk-container")'
		],
		$component,
		["HIDE_ICONS" => "Y"]
	);
}
?>
<script type="text/javascript">
BX.ready(function(){
	if (BX('BXDiskRightInputPlug') && BX.DiskUpload.getObj('FolderList'))
	{
		BX.DiskUpload.getObj('FolderList').agent.init(BX('BXDiskRightInputPlug'));
	}
});
</script>
</div>

