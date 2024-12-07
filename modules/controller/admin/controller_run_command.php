<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
/** @var CMain $APPLICATION */
/** @var CDatabase $DB */
/** @var CUser $USER */
/** @var CUserTypeManager $USER_FIELD_MANAGER */
$member_id = intval($_REQUEST['member']);

if (!CModule::IncludeModule('controller'))
{
	$APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
}

$bCanRunCommand = false;
if ($USER->CanDoOperation('controller_run_command'))
{
	$bCanRunCommand = true;
}
else
{
	foreach (\Bitrix\Controller\AuthGrantTable::getControllerMemberScopes($member_id, $USER->GetID(), $USER->GetUserGroupArray()) as $grant )
	{
		if ($grant['SCOPE'] === 'php')
		{
			$bCanRunCommand = true;
		}
	}
}

if (!$bCanRunCommand)
{
	$APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
}

require_once $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/controller/prolog.php';

IncludeModuleLangFile(__FILE__);

$remove = 0;
if (isset($_REQUEST['remove']) && preg_match('/^tab(\d+)$/', $_REQUEST['remove'], $match) && check_bitrix_sessid())
{
	$remove = $match[1];
}

if (isset($_REQUEST['query_count']) && $_REQUEST['query_count'] > 1 && check_bitrix_sessid())
{
	$query_count = intval($_REQUEST['query_count']);
	CUserOptions::SetOption('controller_run_command', 'count', $query_count);
}
$query_count = CUserOptions::GetOption('controller_run_command', 'count', 1);
if ($query_count <= 1)
{
	$remove = 0;
}

if (isset($_REQUEST['save']) && check_bitrix_sessid())
{
	require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_js.php';
	$i = 1;
	while (isset($_POST['query' . $i]))
	{
		$saved = CUserOptions::GetOption('controller_run_command', 'query' . $i, '');
		if ($saved !== $_POST['query' . $i])
		{
			CUserOptions::SetOption('controller_run_command', 'query' . $i, $_POST['query' . $i]);
		}
		$i++;
	}
	while (CUserOptions::GetOption('controller_run_command', 'query' . $i, '') <> '')
	{
		CUserOptions::DeleteOption('controller_run_command', 'query' . $i);
		$i++;
	}
	echo 'saved';
	require $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/epilog_admin_js.php';
	die();
}

$maxSafeCount = (isset($_REQUEST['force']) && $_REQUEST['force'] == 'Y' ? false : COption::GetOptionString('controller', 'safe_count'));
$cnt = 0;
$sTableID = 'tbl_controller_run';

if (
	isset($_REQUEST['query'])
	&& $_REQUEST['query'] !== ''
	&& check_bitrix_sessid()
	&& !isset($_POST['add'])
	&& !$remove
)
{
	require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_js.php';

	$arFilter = [
		'DISCONNECTED' => 'N',
		'CONTROLLER_GROUP_ID' => $_REQUEST['controller_group_id'],
	];

	if ($member_id > 0)
	{
		$arFilter['=ID'] = $member_id;
	}
	elseif (isset($_REQUEST['controller_member_id']) && !empty($_REQUEST['controller_member_id']))
	{
		if (!is_array($_REQUEST['controller_member_id']))
		{
			$IDs = array_map('trim', explode(' ', $_REQUEST['controller_member_id']));
		}
		else
		{
			$IDs = array_map('trim', $_REQUEST['controller_member_id']);
		}

		$arFilterID = [];
		$arFilterNAME = [];

		foreach ($IDs as $id)
		{
			if (is_numeric($id))
			{
				$arFilterID[] = $id;
			}
			else
			{
				$arFilterNAME[] = mb_strtoupper($id);
			}
		}

		if (!empty($arFilterID) || !empty($arFilterNAME))
		{
			$arFilter[0] = ['LOGIC' => 'OR'];
			if (!empty($arFilterID))
			{
				$arFilter[0]['=ID'] = $arFilterID;
			}
			if (!empty($arFilterNAME))
			{
				$arFilter[0]['NAME'] = $arFilterNAME;
			}
		}
	}

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

	$cnt_ok = 0;
	foreach ($runQueue as $memberId => $memberName)
	{
		if ($_REQUEST['add_task'] == 'Y')
		{
			if (CControllerTask::Add([
				'TASK_ID' => 'REMOTE_COMMAND',
				'CONTROLLER_MEMBER_ID' => $memberId,
				'INIT_EXECUTE' => $_REQUEST['query'],
			])
			)
			{
				$cnt_ok++;
			}
		}
		else
		{
			echo BeginNote();
			echo '<b>' . htmlspecialcharsEx($memberName) . ':</b><br>';
			$result = CControllerMember::RunCommandWithLog($memberId, $_REQUEST['query']);
			if ($result === false)
			{
				$e = $APPLICATION->GetException();
				echo 'Error: ' . $e->GetString();
			}
			else
			{
				echo nl2br($result);
			}
			echo EndNote();
		}
	}

	if ($maxSafeCount !== false && $cnt > $maxSafeCount)
	{
		echo BeginNote();
		echo GetMessage('CTRLR_RUN_ERR_TOO_MANY_SELECTED');
		echo EndNote();
		?>
		<script>top.document.getElementById('tr_force').style.display = '';</script><?php
	}
	else
	{
		if ($cnt <= 0)
		{
			echo BeginNote();
			echo GetMessage('CTRLR_RUN_ERR_NSELECTED');
			echo EndNote();
		}

		if ($_REQUEST['add_task'] == 'Y')
		{
			echo BeginNote();
			echo GetMessage('CTRLR_RUN_SUCCESS', [
				'#SUCCESS_CNT#' => $cnt_ok,
				'#CNT#' => $cnt,
				'#LANG#' => LANGUAGE_ID,
			]);
			echo EndNote();
		}
	}

	require $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/epilog_admin_js.php';
	die();
}

$APPLICATION->SetTitle(GetMessage('CTRLR_RUN_TITLE'));

if (
	$_SERVER['REQUEST_METHOD'] == 'POST'
	&& $_POST['ajax'] === 'y'
	&& (isset($_POST['add']) || isset($_POST['remove']))
)
{
	require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_js.php';
}
else
{
	require $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/prolog_admin_after.php';
}

$controller_member_id = isset($_REQUEST['controller_member_id']) && !is_array($_REQUEST['controller_member_id']) ? intval($_REQUEST['controller_member_id']) : 0;
$controller_group_id = isset($_REQUEST['controller_group_id']) && !is_array($_REQUEST['controller_group_id']) ? intval($_REQUEST['controller_group_id']) : 0;

$aTabs = [];
for ($i = 1; $i <= $query_count - ($remove ? 1 : 0); $i++)
{
	$aTabs[] = [
		'DIV' => 'tab' . $i,
		'TAB' => GetMessage('CTRLR_RUN_COMMAND_FIELD') . ' (' . $i . ')',
		'TITLE' => GetMessage('CTRLR_RUN_COMMAND_TAB_TITLE'),
	];
}
$aTabs[] = [
	'DIV' => 'tab_plus',
	'TAB' => '',
	'ONSELECT' => 'AddNewTab();',
];
$editTab = new CAdminTabControl('editTab', $aTabs);

?>
	<script>
		var tabActionInProgress = false;
		function TabAction(action, param, showWait)
		{
			var firstTab = BX('tab_cont_tab1');
			if (!firstTab)
				return;

			tabActionInProgress = true;
			var data = {
				ajax: 'y'
			};
			data[action] = param;

			var lastIndex = 1;
			while (BX('tab_cont_tab' + lastIndex))
			{
				data['query' + lastIndex] = BX('query' + lastIndex).value;
				lastIndex++;
			}
			if (action == 'add')
				data['query_count'] = lastIndex;

			var selectedTab = BX('editTab_active_tab');
			if (action == 'add')
				data[selectedTab.name] = 'tab' + lastIndex;
			else
				data[selectedTab.name] = param;

			if (showWait)
			{
				ShowWaitWindow();
			}

			BX.ajax.post(
				'controller_run_command.php?lang=' + phpVars.LANGUAGE_ID + '&sessid=' + phpVars.bitrix_sessid, data,
				function(result){
					if (result && result != 'saved')
					{
						document.getElementById('whole_form').innerHTML = result;
						queries = [];
						CloseWaitWindow();
					}
					tabActionInProgress = false;
				}
			);
		}

		function AddNewTab()
		{
			TabAction('add', 'y');
		}

		function RemoveTab(event)
		{
			if (event)
			{
				var tab = event.target.parentNode;
				var m = tab.id.match(/^tab_cont_(.+)$/);
				if (m)
				{
					TabAction('remove', m[1]);
				}
			}
		}

		var oldQueries = {};
		function saveQueries(firstRun)
		{
			var newQueries = {};
			var lastIndex = 1;
			while (BX('query' + lastIndex))
			{
				newQueries['query' + lastIndex] = BX('query' + lastIndex).value;
				lastIndex++;
			}

			if (firstRun)
			{
				oldQueries = newQueries;
				return;
			}

			if (!tabActionInProgress && !compareMaps(oldQueries, newQueries))
			{
				oldQueries = newQueries;
				TabAction('save', 'y', false);
			}
		}

		var queries = [];
		function adjustTabTitles()
		{
			var lastIndex = 1;
			while (BX('tab_cont_tab' + lastIndex))
			{
				var query = BX('query' + lastIndex).value;
				if (query != queries[lastIndex])
				{
					var m = query.match(/^\/\/title:\s*(.+)\n/);
					if (m)
						BX('tab_cont_tab' + lastIndex).innerHTML = BX.util.htmlspecialchars(m[1]);
					
					var close = BX.findChildren(BX('tab_cont_tab' + lastIndex), {className: 'adm-detail-tab-close'}, true);
					if (!close || close.length == 0)
					{
						var button = BX.create('SPAN', {props: {className: 'adm-detail-tab-close'}});
						BX('tab_cont_tab' + lastIndex).appendChild(button);
						BX.bind(button, 'click', RemoveTab);
						//BX.bind(BX('query' + lastIndex), "keyup", saveQueries);
					}
				}
				queries[lastIndex] = query;
				lastIndex++;
			}
			var plus = BX.findChildren(BX('tab_cont_tab_plus'), {className: 'adm-detail-tab-plus'}, true);
			if(!plus || plus.length == 0)
			{
				button = BX.create('SPAN', {props: {className: 'adm-detail-tab-plus'}});
				BX('tab_cont_tab_plus').appendChild(button);
			}
		}

		BX.ready(
			function init()
			{
				saveQueries(true);
				adjustTabTitles();
				setInterval(function()
				{
					saveQueries();
					adjustTabTitles();
				}, 250);
				BX.addCustomEvent('OnAfterActionSelectionChanged', function()
				{
					saveQueries();
					adjustTabTitles();
				});
			}
		);
		function __FPHPSubmit()
		{
			if (confirm('<?php echo GetMessage('CTRLR_RUN_CONFIRM')?>'))
			{
				var selectedTab = BX('editTab_active_tab');
				var m = selectedTab.value.match(/^tab(\d+)$/);

				var data = {
					query: BX('query' + m[1]).value,
					add_task: (BX('add_task' + m[1]).checked ? 'Y' : 'N'),
					force: (BX('force').checked ? 'Y' : 'N'),
					ajax: 'y',
					sessid: phpVars.bitrix_sessid,
					controller_member_id: BX('controller_member_id').value,
					controller_group_id: BX('controller_group_id').value,
				}
				window.scrollTo(0, 500);
				ShowWaitWindow();
				BX.ajax.post(
					'controller_run_command.php?lang=' + phpVars.LANGUAGE_ID,
					data,
					function(result){
						BX('result_div').innerHTML = result;
						CloseWaitWindow();
					}
				);
			}
		}
		function compareMaps(map1, map2)
		{
			if (Object.getOwnPropertyNames(map1).length !== Object.getOwnPropertyNames(map2).length)
			{
				return false;
			}
			for (key in map1)
			{
				if (map1.hasOwnProperty(key))
				{
					var val = map1[key];
					var testVal = map2[key];
					// in cases of an undefined value, make sure the key
					// actually exists on the object so there are no false positives
					if (testVal !== val || (testVal === undefined && !map2.hasOwnProperty(key)))
					{
						return false;
					}
				}
			}
			return true;
		}
	</script>
	<div id="whole_form">
	<?php
	if (
		$_SERVER['REQUEST_METHOD'] == 'POST'
		&& $_POST['ajax'] === 'y'
		&& (isset($_POST['add']) || $remove)
	)
	{
		$APPLICATION->RestartBuffer();
		?>
		<script>window.editTab = null;</script>
		<?php
	}
	?>
	<form name="form1" action="<?php echo $APPLICATION->GetCurPage() ?>" method="POST">
		<input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
		<?php
		if ($member_id > 0)
		{
			echo GetMessage('CTRLR_RUN_FILTER_SITE') . ': #' . $member_id . '<br><br>';
		}
		else
		{
			$arGroups = [];
			$dbr_groups = CControllerGroup::GetList(['SORT' => 'ASC', 'NAME' => 'ASC', 'ID' => 'ASC']);
			while ($ar_groups = $dbr_groups->GetNext())
			{
				$arGroups[$ar_groups['ID']] = $ar_groups['NAME'];
			}

			$filter = new CAdminFilter(
				$sTableID . '_filter_id',
				[GetMessage('CTRLR_RUN_FILTER_GROUP')]
			);

			$filter->Begin();
			?>
			<tr>
				<td nowrap><label
						for="controller_member_id"><?= GetMessage('CTRLR_RUN_FILTER_SITE') ?></label>:
				</td>
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
						<select name="controller_member_id" id="controller_member_id">
							<option value=""><?php echo GetMessage('CTRLR_RUN_FILTER_SITE_ALL') ?></option>
							<?php foreach ($arMembers as $ID => $NAME): ?>
								<option
									value="<?php echo htmlspecialcharsbx($ID) ?>"
									<?php echo ($controller_member_id == $ID) ? ' selected' : ''?>
								><?php echo htmlspecialcharsEx($NAME . ' [' . $ID . ']') ?></option>
							<?php endforeach ?>
						</select>
						<?php
					else:?>
						<input
							type="text"
							name="controller_member_id"
							id="controller_member_id"
							value="<?php echo htmlspecialcharsbx($controller_member_id) ?>"
							size="47"
						/>
					<?php endif ?>
				</td>
			</tr>
			<tr>
				<td nowrap>
					<label for="controller_group_id"><?php echo htmlspecialcharsEx(GetMessage('CTRLR_RUN_FILTER_GROUP')) ?></label>:
				</td>
				<td nowrap><?php echo htmlspecialcharsEx($controller_group_id) ?>
					<select name="controller_group_id" id="controller_group_id">
						<option value=""><?php echo GetMessage('CTRLR_RUN_FILTER_GROUP_ANY') ?></option>
						<?php foreach ($arGroups as $group_id => $group_name): ?>
							<option
								value="<?= $group_id ?>"
								<?php echo ($group_id == $controller_group_id) ? 'selected' : ''?>
							><?= $group_name ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<?php
			$filter->Buttons();
			$filter->End();
		}
		?>


		<?=bitrix_sessid_post()?>
		<?php
		$editTab->Begin();
		for ($i = 1; $i <= $query_count - ($remove ? 1 : 0); $i++)
		{
			$index = $remove ? ($i >= $remove ? $i + 1 : $i) : $i;
			if (isset($_REQUEST['query' . $index]))
			{
				$query = $_REQUEST['query' . $index];
			}
			else
			{
				$query = CUserOptions::GetOption('controller_run_command', 'query' . $index, '');
			}

			$editTab->BeginNextTab();
			?>
			<tr>
				<td>
					<input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
					<textarea name="query<?php echo $i?>" id="query<?php echo $i?>" rows="15" style="width:100%;" title=""><?php echo htmlspecialcharsbx($query); ?></textarea>
					<?php
					if (COption::GetOptionString('fileman', 'use_code_editor', 'Y') == 'Y' && CModule::IncludeModule('fileman'))
					{
						CCodeEditor::Show([
							'textareaId' => 'query' . $i,
							'height' => 350,
							'forceSyntax' => 'php',
						]);
					}
					?>
				</td>
			</tr>
			<tr>
				<td><br>
					<div class="adm-list">
						<div class="adm-list-item">
							<div class="adm-list-control"><input type="checkbox" id="add_task<?php echo $i?>" name="add_task<?php echo $i?>" title="<?php echo GetMessage('CTRLR_RUN_ADD_TASK_LABEL') ?>" value="Y"></div>
							<div class="adm-list-label"><label for="add_task<?php echo $i?>" title="<?php echo GetMessage('CTRLR_RUN_ADD_TASK_LABEL')?>"><?php echo GetMessage('CTRLR_RUN_ADD_TASK')?></label></div>
						</div>
						<div class="adm-list-item" style="display:none" id="tr_force">
							<div class="adm-list-control"><input type="checkbox" id="force" name="force" value="Y"></div>
							<div class="adm-list-label"><label for="force"><?php echo GetMessage('CTRLR_RUN_FORCE_RUN') ?></label></div>
						</div>
					</div>
				</td>
			</tr>
		<?php
		}
		$editTab->Buttons();
		?>
		<input
			type="button"
			accesskey="x"
			name="execute"
			value="<?php echo GetMessage('CTRLR_RUN_BUTT_RUN') ?>"
			onclick="return __FPHPSubmit();"
			class="adm-btn-save"
			<?php echo (!$bCanRunCommand) ? 'disabled="disabled"' : ''?>
		>
		<?php
		$editTab->End();
		?>
	</form>
	</div>
<?php
if (
	$_SERVER['REQUEST_METHOD'] == 'POST'
	&& $_POST['ajax'] === 'y'
	&& (isset($_POST['add']) || $remove)
)
{
	if ($remove)
	{
		CUserOptions::SetOption('controller_run_command', 'count', $query_count - 1);
	}
	?><script>adjustTabTitles();</script><?php

	require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_js.php';
}
else
{
	?><div id="result_div"></div><?php
}

require $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/epilog_admin.php';
