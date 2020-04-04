<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!defined('ADMIN_THEME_ID'))
{
	define('ADMIN_THEME_ID', '.default');
}

$wdDialogName = 'AttachFileDialog';
$ob = $arParams["OBJECT"];
$APPLICATION->RestartBuffer();
while(ob_end_clean()) {true;}

$multi = ((isset($_REQUEST['MULTI']) && ($_REQUEST['MULTI']=='Y')) ? 'true' : 'false');
$userID = $GLOBALS['USER']->GetID();

$bFakemove = (isset($_REQUEST['ACTION']) && ($_REQUEST['ACTION'] == 'FAKEMOVE'));
$mode = "{'onlyFiles' : true}";
if ($bFakemove)
	$mode = "{'folder' : true}";

$typeItems = "{\n";
if (isset($ob->attributes['user_id']))
{
	$typeItems .= str_replace( // user doc lib
		array("#GROUP_ID#", "#GROUP_NAME#", "#LINK#", "#IBLOCK_ID#", "#SECTION_ID#"),
		array($userID, CUtil::JSEscape(GetMessage('WD_MY_DOCUMENTS')), CUtil::JSEscape($ob->base_url), intval($ob->IBLOCK_ID), intval($ob->arRootSection['ID'])),
		"'U#GROUP_ID#' : {'id' : 'U#GROUP_ID#', 'name' : '#GROUP_NAME#', 'type' : 'user', 'link' : '#LINK#', 'iblock_id': '#IBLOCK_ID#', 'section_id': '#SECTION_ID#'},\n"
	);
}
else
{
	$userLib = CWebDavIblock::LibOptions('user_files', false, SITE_ID);
	if ($userLib && isset($userLib['id']) && ($iblockID = intval($userLib['id'])))
	{
		$sectionID = CIBlockWebdavSocnet::GetSectionID($iblockID, 'user', $userID);

		$libPath = CWebDavIblock::LibOptions('lib_paths', true, $iblockID);
		if ($libPath)
		{
			$tplUser = '#user_id#';
			if ($tplPos = strpos($libPath, $tplUser))
			{
				$libPath = substr($libPath, 0, $tplPos + strlen($tplUser)) . '/files/lib/';
			}
			$libPath = str_replace('#user_id#', $userID, $libPath);
		}
	}
	if ($sectionID && $libPath)
	{
		$typeItems .= str_replace( // user doc lib
			array("#GROUP_ID#", "#GROUP_NAME#", "#LINK#", "#IBLOCK_ID#", "#SECTION_ID#"),
			array($userID, CUtil::JSEscape(GetMessage('WD_MY_DOCUMENTS')), CUtil::JSEscape($libPath), $iblockID, $sectionID),
			"'U#GROUP_ID#' : {'id' : 'U#GROUP_ID#', 'name' : '#GROUP_NAME#', 'type' : 'user', 'link' : '#LINK#', 'iblock_id': '#IBLOCK_ID#', 'section_id': '#SECTION_ID#'},\n"
		);
	}
}

$sharedLibID = CWebDavIblock::LibOptions('shared_files', false, SITE_ID);
if ($sharedLibID &&
	isset($sharedLibID['id']) &&
	intval($sharedLibID['id']) > 0 &&
	isset($sharedLibID['base_url']) &&
	strlen($sharedLibID['base_url']) > 0
)
{
	if(substr($sharedLibID['base_url'], -1) == "/")
	{
		$sharedLibID['base_url'] .= "index.php";
	}
	$typeItems .= str_replace( // user doc lib
		array("#GROUP_ID#", "#GROUP_NAME#", "#LINK#", "#IBLOCK_ID#"),
		array(intval($sharedLibID['id']), CUtil::JSEscape(GetMessage('WD_SHARED_DOCUMENTS')), CUtil::JSEscape($sharedLibID['base_url']), intval($sharedLibID['id'])),
		"'IB#GROUP_ID#' : {'id' : 'IB#GROUP_ID#', 'name' : '#GROUP_NAME#', 'type' : 'library', 'link' : '#LINK#', 'iblock_id': '#IBLOCK_ID#', 'section_id':'0'},\n"
	);
}

if (isset($arResult['USER_GROUPS']))
{
	foreach ($arResult['USER_GROUPS'] as $groupID=>$arGroup) // user groups
	{
		if (intval($arGroup['SECTION_ID']) > 0)
		{
			$typeItems .= str_replace(
				array("#GROUP_ID#", "#GROUP_NAME#", "#LINK#", "#SECTION_ID#"),
				array(intval($groupID), CUtil::JSEscape($arGroup['GROUP_NAME']), CUtil::JSEscape($arGroup['PATH_FILES']), intval($arGroup['SECTION_ID'])),
				"'SG#GROUP_ID#' : {'id' : 'SG#GROUP_ID#', 'name' : '#GROUP_NAME#', 'type' : 'socnet', 'link' : '#LINK#', 'section_id': '#SECTION_ID#'},\n"
			);
		}
	}
}
$typeItems .= "}";


$disabledItems = array();
$items = "{\n";
foreach ($arResult["GRID_DATA"] as $row)
{
	if(!isset($row['data']['NAME']))
	{
		continue;
	}
	$timeStampXUnix = $row['data']['TIMESTAMP_X_UNIX'];
	$timeStampXUnixD = FormatDate('X', $timeStampXUnix);
	if($timeStampXUnix == null)
	{
		$timeStampXUnix = MakeTimeStamp($row['data']['TIMESTAMP_X']);
		$timeStampXUnixD = GetTime($timeStampXUnix,"SHORT");
	}

	//element if WF_NEW = 'Y' and WF_STATUS_ID = 2 - not public
	if($row['data']['TYPE'] === "E" && $row['data']['WF_STATUS_ID'] != 1)
	{
		$disabledItems[$row['id']] = array(
			'hint' => GetMessageJS('WD_DESCR_DISABLE_ATTACH_NON_PUBLIC_FILE'),
		);
	}

	$data = array(
		"#ID#" => $row['id'],
		"#TYPE#" => $row['data']['FTYPE'],
		"#NAME#" => CUtil::JSEscape($row['data']['NAME']),
		"#PATH#" => CUtil::JSEscape($row['data']['PATH']),
		"#LINK#" => CUtil::JSEscape(($row['data']['TYPE'] === "S") ? $row['data']['URL']['THIS'] : $row['data']['URL']['EDIT']),
		"#SIZE_FORMATTED#" => isset($row['data']['FILE_SIZE']) ? $row['data']['FILE_SIZE'] : '',
		"#SIZE#" => isset($row['data']['FILE']['FILE_SIZE']) ? intval($row['data']['FILE']['FILE_SIZE']) : 0,
		"#MODIFIED_BY#" => CUtil::JSEscape($row['data']['MODIFIED_BY']['FULL_NAME']),
		"#MODIFIED_DATE_FORMATTED#" => $timeStampXUnixD,
		"#MODIFIED_DATE#" => $timeStampXUnix,
	);
	$items .= str_replace(
		array_keys($data),
		array_values($data),
		"'#ID#' : {'id' : '#ID#', 'type': '#TYPE#', 'link': '#LINK#', 'name': '#NAME#', 'path': '#PATH#', 'size': '#SIZE_FORMATTED#', 'sizeInt': '#SIZE#', 'modifyBy': '#MODIFIED_BY#', 'modifyDate': '#MODIFIED_DATE_FORMATTED#', 'modifyDateInt': '#MODIFIED_DATE#'},\n"
	);
}
$items .= "}";
if (isset($_REQUEST['WD_LOAD_ITEMS']))
{
?>
	{
		'FORM_NAME' : '<?=CUtil::JSEscape((isset($_REQUEST['FORM_NAME'])?$_REQUEST['FORM_NAME']:''))?>',
		'FORM_ITEMS' : <?=$items?>,
		'FORM_ITEMS_DISABLED': <?= CUtil::PhpToJSObject($disabledItems) ?>,
		'FORM_PATH' : '<?=CUtil::JSEscape($ob->_path)?>',
		'FORM_IBLOCK_ID' : '<?=intval($ob->IBLOCK_ID)?>'
	};
<?
	die();
}
else
{
	$nop = "{}";
	$saveAction = $nop;
	if ($_REQUEST['ACTION'] == 'MOVE' && isset($_REQUEST['ID']))
	{
		ob_start();
/*?>
		{
			var targetSectionID = false;
			for (i in selected) {
				if (i.substr(0,1) == 'S') {
					targetSectionID = i.substr(1);
					break;
				}
			}
			if (targetSectionID) {
				targetUrl = '<?=CUtil::JSEscape(POST_FORM_ACTION_URI)?>';
				BX.ajax.post(targetUrl, {
					'sessid' : BX.bitrix_sessid(),
					'ACTION': "<?=CUtil::JSEscape($_REQUEST['ACTION'])?>",
					'AJAX' : 'Y',
					'action_button_WebDAV20' : 'Y',
					'IBLOCK_SECTION_ID': targetSectionID,
					'redirect' : 'N',
					'overwrite' : 1,
<? if (isset($_REQUEST['ID'])) { ?>
					'ID[]' :	"<?=CUtil::JSEscape($_REQUEST['ID'])?>",
<? } ?>
<? if (isset($_REQUEST['fake']) && ($_REQUEST['fake'] == 'Y')) { ?>
					'fake' :	'Y'
<? } ?>
				});
			}
		}
<?*/
		$saveAction = ob_get_clean();
	}
?>
	<script>
		BX.WebDavFileDialog.init({
			'name' : '<?=$wdDialogName?>',

			'bindPopup' : { 'node' : null, 'offsetTop' : 0, 'offsetLeft': 0},

			'localize' : {
				'title' : '<?=( $bFakemove ? GetMessage("WD_SAVE_DOCUMENT_TITLE") : GetMessage('WD_SELECT_DOCUMENT_TITLE'))?>',
				'saveButton' : '<?=( $bFakemove ? GetMessage("WD_SELECT_FOLDER") : GetMessage('WD_SELECT_DOCUMENT') )?>',
				'cancelButton' : '<?=GetMessage("WD_CANCEL")?>'
			},

			'callback' : {
				'saveButton' : function(tab, path, selected) <?=$saveAction?>,
				'cancelButton' : function(tab, path, selected) <?=$nop?>
			},

			'type' : {
				'user' : {'id' : 'user', 'order': 1},
				'library' : {'id' : 'library', 'order': 2},
				'socnet' : {'id' : 'socnet', 'name' : '<?=GetMessage("WD_MY_GROUPS")?>', 'order': 3}
			},
			'typeItems' : <?=$typeItems?>,
			'items' : <?=$items?>,

			'itemsDisabled' : <?= json_encode($disabledItems) ?>,
			'itemsSelected' : {},
			'itemsSelectEnabled' : <?=$mode?>, // all, onlyFiles, folder, archive, image, file, video, txt, word, excel, ppt
			'itemsSelectMulti' : <?=$multi?>,

			'gridColumn' : {
				'name' : {'id' : 'name', 'name' : '<?=GetMessage("WD_TITLE_NAME")?>', 'sort' : 'name', 'style': 'width: 310px', 'order': 1},
				'size' : {'id' : 'size', 'name' : '<?=GetMessage("WD_TITLE_FILE_SIZE")?>', 'sort' : 'sizeInt', 'style': 'width: 79px', 'order': 2},
				'modifyBy' : {'id' : 'modifyBy', 'name' : '<?=GetMessage("WD_TITLE_MODIFIED_BY")?>', 'sort' : 'modifyBy', 'style': 'width: 122px', 'order': 3},
				'modifyDate' : {'id' : 'modifyDate', 'name' : '<?=GetMessage("WD_TITLE_TIMESTAMP")?>', 'sort' : 'modifyDateInt', 'style': 'width: 90px', 'order': 4}
			},
			'gridOrder' : {'column': 'name', 'order':'asc'}
		});
	</script>
<?
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");?>
