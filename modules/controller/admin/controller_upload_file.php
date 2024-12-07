<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
/** @var CMain $APPLICATION */
/** @var CDatabase $DB */
/** @var CUser $USER */
/** @var CUserTypeManager $USER_FIELD_MANAGER */

if (!$USER->CanDoOperation('controller_upload_file') || !CModule::IncludeModule('controller'))
{
	$APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
}
require_once $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/controller/prolog.php';

IncludeModuleLangFile(__FILE__);

$maxSafeCount = (isset($_REQUEST['force']) && $_REQUEST['force'] == 'Y' ? false : COption::GetOptionString('controller', 'safe_count'));
$cnt = 0;
$sTableID = 'tbl_controller_upload';
$lAdmin = new CAdminList($sTableID);

$filename = Rel2Abs('/', trim($_REQUEST['filename']));
//Trailing slash indicates that we have a directory here
//never remove it due to security reasons
$path_to = Rel2Abs('/', trim($_REQUEST['path_to']) . '/');

if (
	$filename <> ''
	&& $path_to <> ''
	&& check_bitrix_sessid()
)
{
	$lAdmin->BeginPrologContent();
	$arFilter = [
		'CONTROLLER_GROUP_ID' => $_REQUEST['controller_group_id'],
		'DISCONNECTED' => 'N',
	];

	$arFilter['ID'] = $_REQUEST['controller_member_id'];
	if (!is_array($arFilter['ID']))
	{
		$IDs = explode(' ', $arFilter['ID']);
		$arFilter['ID'] = [];

		foreach ($IDs as $id)
		{
			$id = intval(trim($id));
			if ($id > 0)
			{
				$arFilter['ID'][] = $id;
			}
		}
	}
	if (is_array($arFilter['ID']) && !$arFilter['ID'])
	{
		unset($arFilter['ID']);
	}

	$sendfile = false;
	if (file_exists($_SERVER['DOCUMENT_ROOT'] . $filename))
	{
		$sendfile = CControllerTools::PackFileArchive($_SERVER['DOCUMENT_ROOT'] . $filename);
		if ($sendfile !== false)
		{
			$arParams = [
				'FILE' => $sendfile,
				'PATH_TO' => $path_to,
			];

			$runQueue = [];
			$dbr_members = CControllerMember::GetList(['ID' => 'ASC'], $arFilter);
			while ($ar_member = $dbr_members->Fetch())
			{
				$runQueue[$ar_member['ID']] = $ar_member['NAME'];
				$cnt++;
				if ($maxSafeCount !== false && $cnt > $maxSafeCount)
				{
					$runQueue = [];
					break;
				}
			}

			foreach ($runQueue as $memberId => $memberName)
			{
				$cnt++;
				echo BeginNote();
				echo '<b>' . htmlspecialcharsEx($memberName) . ':</b><br>';
				$result = CControllerMember::RunCommandWithLog($memberId, ' ', $arParams, false, 'sendfile');
				if ($result === false)
				{
					$e = $APPLICATION->GetException();
					echo 'Error: ' . $e->GetString();
				}
				elseif ($result === '')
				{
					echo 'OK';
				}
				else
				{
					echo nl2br($result);
				}
				echo EndNote();
			}

			if ($maxSafeCount !== false && $cnt > $maxSafeCount)
			{
				echo BeginNote();
				echo GetMessage('CTRLR_UPLOAD_ERR_TOO_MANY_SELECTED');
				echo EndNote();
				?><script>top.document.getElementById('tr_force').style.display='';</script><?php
			}
			else
			{
				if ($cnt <= 0)
				{
					echo BeginNote();
					echo GetMessage('CTRLR_UPLOAD_ERR_NSELECTED');
					echo EndNote();
				}
			}
		}
		else
		{
			ShowError(GetMessage('CTRLR_UPLOAD_ERR_PACK'));
		}
	}
	else
	{
		ShowError(GetMessage('CTRLR_UPLOAD_ERR_FILE'));
	}

	$lAdmin->EndPrologContent();
}

$lAdmin->BeginEpilogContent();
?>
	<input type="hidden" name="controller_member_id" id="controller_member_id" value="<?=htmlspecialcharsbx($_REQUEST['controller_member_id'])?>">
	<input type="hidden" name="controller_group_id" id="controller_group_id" value="<?=htmlspecialcharsbx($_REQUEST['controller_group_id'])?>">
	<input type="hidden" name="filename" id="filename" value="<?=htmlspecialcharsbx($filename);?>">
	<input type="hidden" name="path_to" id="path_to" value="<?=htmlspecialcharsbx($path_to);?>">
	<input type="hidden" name="force" id="force" value="N">
<?php
$lAdmin->EndEpilogContent();

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage('CTRLR_UPLOAD_TITLE'));

require $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/prolog_admin_after.php';
?>
<script>
function __FPHPSubmit()
{
	if(confirm('<?php echo GetMessage('CTRLR_UPLOAD_CONFIRM')?>'))
	{
		document.getElementById('controller_member_id').value = document.getElementById('fcontroller_member_id').value;
		document.getElementById('controller_group_id').value = document.getElementById('fcontroller_group_id').value;

		var filename = document.getElementById('ffilename').value;
		var path_to = document.getElementById('fpath_to').value;

		if(filename.length > 0 && path_to.length <= 0)
		{
			alert('<?=CUtil::addslashes(GetMessage('CTRLR_UPLOAD_ERROR_NO_PATH_TO'))?>');
			document.getElementById('fpath_to').focus();
			return;
		}

		document.getElementById('filename').value = filename;
		document.getElementById('path_to').value = path_to;
		document.getElementById('force').value = (document.getElementById('fforce').checked?'Y':'N');

		<?=$lAdmin->ActionPost($APPLICATION->GetCurPageParam('mode=frame', ['mode', 'PAGEN_1']))?>
	}
}
</script>
<?php
$aTabs = [
	[
		'DIV' => 'tab1',
		'TAB' => GetMessage('CTRLR_UPLOAD_FILE_TAB'),
		'TITLE' => GetMessage('CTRLR_UPLOAD_FILE_TAB_TITLE'),
	],
];
$editTab = new CAdminTabControl('editTab', $aTabs, true, true);
?>
<form name="form1" action="<?php echo $APPLICATION->GetCurPage()?>" method="POST">
<input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
<?php
$arGroups = [];
$dbr_groups = CControllerGroup::GetList(['SORT' => 'ASC','NAME' => 'ASC','ID' => 'ASC']);
while ($ar_groups = $dbr_groups->GetNext())
{
	$arGroups[$ar_groups['ID']] = $ar_groups['NAME'];
}


$filter = new CAdminFilter(
	$sTableID . '_filter_id',
	[GetMessage('CTRLR_UPLOAD_FILTER_GROUP')]
);

$filter->Begin();
?>
<tr>
	<td nowrap><label for="fcontroller_member_id"><?=GetMessage('CTRLR_UPLOAD_FILTER_SITE')?></label>:</td>
	<td nowrap>
		<?php
		$dbr_members = CControllerMember::GetList([
			'SORT' => 'ASC',
			'NAME' => 'ASC',
			'ID' => 'ASC',
		], [
			'DISCONNECTED' => 'N',
		], [
			'ID',
			'NAME',
		], [
		], [
			'nTopCount' => $maxSafeCount + 1,
		]);
		$arMembers = [];
		$c = 0;
		while ($ar_member = $dbr_members->Fetch())
		{
			$arMembers[$ar_member['ID']] = $ar_member['NAME'];
			$c++;
			if ($maxSafeCount !== false && $c > $maxSafeCount)
			{
				$arMembers = [];
				break;
			}
		}

		if ($arMembers):?>
			<select name="fcontroller_member_id" id="fcontroller_member_id">
				<option value=""><?php echo GetMessage('CTRLR_UPLOAD_FILTER_SITE_ALL')?></option>
				<?php foreach ($arMembers as $ID => $NAME):?>
					<option
						value="<?php echo htmlspecialcharsbx($ID)?>"
						<?php echo ($_REQUEST['controller_member_id'] == $ID) ? ' selected' : ''?>
					><?php echo htmlspecialcharsEx($NAME . ' [' . $ID . ']')?></option>
				<?php endforeach?>
			</select>
		<?php else:?>
			<input
				type="text"
				name="fcontroller_member_id"
				id="fcontroller_member_id"
				value="<?php echo htmlspecialcharsbx($_REQUEST['controller_member_id'])?>"
				size="47"
			/>
		<?php endif?>
	</td>
</tr>
<tr>
	<td nowrap><label for="fcontroller_group_id"><?php echo htmlspecialcharsEx(GetMessage('CTRLR_UPLOAD_FILTER_GROUP'))?></label>:</td>
	<td nowrap><?php echo htmlspecialcharsEx($_REQUEST['controller_group_id'])?>
	<select name="fcontroller_group_id" id="fcontroller_group_id">
		<option value=""><?php echo GetMessage('CTRLR_UPLOAD_FILTER_GROUP_ANY')?></option>
	<?php foreach ($arGroups as $group_id => $group_name):?>
		<option value="<?=$group_id?>" <?php echo ($group_id == $_REQUEST['controller_group_id']) ? 'selected' : ''?>><?=$group_name?></option>
	<?php endforeach;?>
	</select>
	</td>
</tr>
<?php
$filter->Buttons();
?>
<?php
$filter->End();
?>


<?=bitrix_sessid_post()?>
<?php
$editTab->Begin();
$editTab->BeginNextTab();
?>
<tr>
	<td width="40%">
		<label for="ffilename"><?=GetMessage('CTRLR_UPLOAD_SEND_FILE_FROM')?></label>
	</td>
	<td width="60%">
		<input type="text" id="ffilename" name="ffilename" value="">
		<script>
		function setFile(filename, path, site)
		{
			if(filename == path)
			{
				document.getElementById('ffilename').value = filename;
			}
			else
			{
				if(path != '/')
					path += '/';
				document.getElementById('ffilename').value = path + filename;
			}
		}
		</script><?php
		CAdminFileDialog::ShowScript(

			[
				'event' => 'OpenFileBrowserWindFile',
				'arResultDest' => ['FUNCTION_NAME' => 'setFile'],
				//"arPath" => Array("SITE" => "ru", 'PATH' => "/"),
				'select' => 'DF',// F - file only, D - folder only, DF - files & dirs
				'operation' => 'O',// O - open, S - save
				'showUploadTab' => true,
				'showAddToMenuTab' => true,
				//"fileFilter" => '',
				'allowAllFiles' => true,
				'SaveConfig' => true
			]
		);
		?><input type="button" onclick="OpenFileBrowserWindFile();" value="<?php echo GetMessage('CTRLR_UPLOAD_OPEN_FILE_BUTTON')?>">
	</td>
</tr>
<tr>
	<td>
		<label for="fpath_to"><?=GetMessage('CTRLR_UPLOAD_SEND_FILE_TO')?></label>
	</td>
	<td>
		<input type="text" id="fpath_to" name="fpath_to">
	</td>
</tr>
<tr style="display:none" id="tr_force">
	<td>
		<input type="checkbox" id="fforce" name="fforce" value="Y">
		<label for="fforce"><?php echo GetMessage('CTRLR_UPLOAD_FORCE_RUN')?></label>
	</td>
</tr>
<?php $editTab->Buttons();?>
<input
	type="button"
	accesskey="x"
	name="execute"
	value="<?php echo GetMessage('CTRLR_UPLOAD_BUTT_RUN')?>"
	onclick="return __FPHPSubmit();"
	class="adm-btn-save"
	<?php echo (!$USER->CanDoOperation('controller_upload_file')) ? 'disabled="disabled"' : ''?>
>
<input type="reset" value="<?php echo GetMessage('CTRLR_UPLOAD_BUTT_CLEAR')?>">
<?php
$editTab->End();
?>
</form>
<?php
$lAdmin->DisplayList();

require $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/epilog_admin.php';
