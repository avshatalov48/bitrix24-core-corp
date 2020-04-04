<?php

use Bitrix\Main\Loader;

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
/** @var CBitrixComponent $component */

$folder = \Bitrix\Disk\Folder::loadById($arResult['VARIABLES']['FOLDER_ID']);

$pageId = "group_files";
include("util_group_menu.php");
include("util_group_profile.php");
?>

<div class="bx-disk-container posr" id="bx-disk-container">
	<table style="width: 100%;" cellpadding="0" cellspacing="0">
		<tr>
			<td>
				<?php

				if (Loader::includeModule("disk"))
				{
					$APPLICATION->includeComponent(
						"bitrix:socialnetwork.copy.checker",
						"",
						[
							"QUEUE_ID" => $arResult["VARIABLES"]["group_id"],
							"HELPER" => new Bitrix\Disk\Copy\Integration\Group()
						],
						$component,
						["HIDE_ICONS" => "Y"]
					);
				}

				$APPLICATION->IncludeComponent(
					'bitrix:disk.folder.list',
					'',
					array_merge($arResult, array(
						'STORAGE' => $arResult['VARIABLES']['STORAGE'],
						'PATH_TO_FOLDER_LIST' => CComponentEngine::MakePathFromTemplate($arResult['PATH_TO_GROUP_DISK'], array('group_id' => $arResult['VARIABLES']['group_id'])),
						'PATH_TO_FOLDER_VIEW' => CComponentEngine::MakePathFromTemplate($arResult['PATH_TO_GROUP_DISK_FILE'], array('group_id' => $arResult['VARIABLES']['group_id'])),
						'PATH_TO_FILE_VIEW' => CComponentEngine::MakePathFromTemplate($arResult['PATH_TO_GROUP_DISK_FILE'], array('group_id' => $arResult['VARIABLES']['group_id'])),
						'PATH_TO_DISK_BIZPROC_WORKFLOW_ADMIN' => CComponentEngine::MakePathFromTemplate($arResult['PATH_TO_GROUP_DISK_BIZPROC_WORKFLOW_ADMIN'], array('group_id' => $arResult['VARIABLES']['group_id'])),
						'PATH_TO_DISK_START_BIZPROC' => CComponentEngine::MakePathFromTemplate($arResult['PATH_TO_GROUP_DISK_START_BIZPROC'], array('group_id' => $arResult['VARIABLES']['group_id'])),
						'PATH_TO_DISK_TASK_LIST' => CComponentEngine::MakePathFromTemplate($arResult['PATH_TO_GROUP_DISK_TASK_LIST'], array('group_id' => $arResult['VARIABLES']['group_id'])),
						'PATH_TO_DISK_TASK' => CComponentEngine::MakePathFromTemplate($arResult['PATH_TO_GROUP_DISK_TASK'], array('group_id' => $arResult['VARIABLES']['group_id'])),
						'PATH_TO_FILE_HISTORY' => CComponentEngine::MakePathFromTemplate(
							$arResult['PATH_TO_GROUP_DISK_FILE_HISTORY'],
							array('group_id' => $arResult['VARIABLES']['group_id'])
						),
						'FOLDER' => $folder,
						'RELATIVE_PATH' => $arResult['VARIABLES']['RELATIVE_PATH'],
						'RELATIVE_ITEMS' => $arResult['VARIABLES']['RELATIVE_ITEMS'],
					)),
					$component
				);?>
			</td>
		</tr>
	</table>
<?$APPLICATION->IncludeComponent(
	'bitrix:disk.file.upload',
	'',
	array(
		'STORAGE' => $arResult['VARIABLES']['STORAGE'],
		'FOLDER' => $folder,
		'CID' => 'FolderList',
		'INPUT_CONTAINER' => '((BX.__tmpvar=BX.findChild(BX("folder_toolbar"), {className : "element-upload"}, true))&&BX.__tmpvar?BX.__tmpvar.parentNode:null)',
		'DROPZONE' => 'BX("bx-disk-container")'
	),
	$component,
	array("HIDE_ICONS" => "Y")
);?>
<script type="text/javascript">
BX.ready(function(){
	if (BX('BXDiskRightInputPlug') && BX.DiskUpload.getObj('FolderList'))
	{
		BX.DiskUpload.getObj('FolderList').agent.init(BX('BXDiskRightInputPlug'));
	}
});
</script>
</div>
