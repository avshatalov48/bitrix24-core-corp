<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CUserTypeManager $USER_FIELD_MANAGER */
$member_id = intval($_REQUEST['member']);

if (!CModule::IncludeModule("controller"))
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

$bCanRunCommand = false;
if ($USER->CanDoOperation("controller_run_command"))
{
	$bCanRunCommand = true;
}
else
{
	foreach (\Bitrix\Controller\AuthGrantTable::getControllerMemberScopes($member_id, $USER->GetID(), $USER->GetUserGroupArray()) as $grant )
	{
		if ($grant["SCOPE"] === "php")
		{
			$bCanRunCommand = true;
		}
	}
}

if (!$bCanRunCommand)
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");

IncludeModuleLangFile(__FILE__);

$remove = 0;
if (isset($_REQUEST["remove"]) && preg_match('/^tab(\d+)$/', $_REQUEST["remove"], $match) && check_bitrix_sessid())
{
	$remove = $match[1];
}

if (isset($_REQUEST["query_count"]) && $_REQUEST["query_count"] > 1 && check_bitrix_sessid())
{
	$query_count = intval($_REQUEST["query_count"]);
	CUserOptions::SetOption("controller_run_command", "count", $query_count);
}
$query_count = CUserOptions::GetOption("controller_run_command", "count", 1);
if ($query_count <= 1)
	$remove = 0;

if (isset($_REQUEST["save"]) && check_bitrix_sessid())
{
	CUtil::JSPostUnescape();
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
	$i = 1;
	while (isset($_POST["query".$i]))
	{
		$saved = CUserOptions::GetOption("controller_run_command", "query".$i, '');
		if ($saved !== $_POST["query".$i])
		{
			CUserOptions::SetOption("controller_run_command", "query".$i, $_POST["query".$i]);
		}
		$i++;
	}
	while(CUserOptions::GetOption("controller_run_command", "query".$i, '') <> '')
	{
		CUserOptions::DeleteOption("controller_run_command", "query".$i);
		$i++;
	}
	echo "saved";
	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_js.php");
	die();
}

$maxSafeCount = (isset($_REQUEST["force"]) && $_REQUEST["force"] == "Y"? false: COption::GetOptionString("controller", "safe_count"));
$cnt = 0;
$sTableID = "tbl_controller_run";

if (
	$query <> ""
	&& check_bitrix_sessid()
	&& !isset($_POST["add"])
	&& !$remove
)
{
	CUtil::JSPostUnescape();
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");

	$arFilter = array(
		"DISCONNECTED" => "N",
		"CONTROLLER_GROUP_ID" => $_REQUEST['controller_group_id'],
	);

	if ($member_id > 0)
	{
		$arFilter["=ID"] = $member_id;
	}
	elseif (isset($_REQUEST['controller_member_id']) && trim($_REQUEST['controller_member_id']) != "")
	{
		if (!is_array($_REQUEST['controller_member_id']))
			$IDs = array_map("trim", explode(" ", $_REQUEST['controller_member_id']));
		else
			$IDs = array_map("trim", $_REQUEST['controller_member_id']);

		$arFilterID = array();
		$arFilterNAME = array();

		foreach ($IDs as $id)
		{
			if (is_numeric($id))
				$arFilterID[] = $id;
			else
				$arFilterNAME[] = mb_strtoupper($id);
		}

		if (!empty($arFilterID) || !empty($arFilterNAME))
		{
			$arFilter[0] = array("LOGIC" => "OR");
			if (!empty($arFilterID))
				$arFilter[0]["=ID"] = $arFilterID;
			if (!empty($arFilterNAME))
				$arFilter[0]["NAME"] = $arFilterNAME;
		}
	}

	$runQueue = array();
	$dbr_members = CControllerMember::GetList(Array("ID" => "ASC"), $arFilter);
	while ($ar_member = $dbr_members->Fetch())
	{
		$runQueue[$ar_member["ID"]] = $ar_member["NAME"];
		$cnt++;
		if ($maxSafeCount !== false && $cnt > $maxSafeCount)
		{
			$runQueue = array();
			break;
		}
	}

	$cnt_ok = 0;
	foreach ($runQueue as $memberId => $memberName)
	{
		if ($_REQUEST['add_task'] == "Y")
		{
			if (CControllerTask::Add(array(
				"TASK_ID" => "REMOTE_COMMAND",
				"CONTROLLER_MEMBER_ID" => $memberId,
				"INIT_EXECUTE" => $query,
			))
			)
			{
				$cnt_ok++;
			}
		}
		else
		{
			echo BeginNote();
			echo "<b>".htmlspecialcharsEx($memberName).":</b><br>";
			$result = CControllerMember::RunCommandWithLog($memberId, $query);
			if ($result === false)
			{
				$e = $APPLICATION->GetException();
				echo "Error: ".$e->GetString();
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
		echo GetMessage("CTRLR_RUN_ERR_TOO_MANY_SELECTED");
		echo EndNote();
		?>
		<script>top.document.getElementById('tr_force').style.display = '';</script><?
	}
	else
	{
		if ($cnt <= 0)
		{
			echo BeginNote();
			echo GetMessage("CTRLR_RUN_ERR_NSELECTED");
			echo EndNote();
		}

		if ($_REQUEST['add_task'] == "Y")
		{
			echo BeginNote();
			echo GetMessage("CTRLR_RUN_SUCCESS", array(
				"#SUCCESS_CNT#" => $cnt_ok,
				"#CNT#" => $cnt,
				"#LANG#" => LANGUAGE_ID,
			));
			echo EndNote();
		}
	}

	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_js.php");
	die();
}

$APPLICATION->SetTitle(GetMessage("CTRLR_RUN_TITLE"));

if(
	$_SERVER['REQUEST_METHOD'] == 'POST'
	&& $_POST["ajax"] === "y"
	&& (isset($_POST["add"]) || $remove)
)
{
	CUtil::JSPostUnescape();
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
}
else
{
	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
}

$aTabs = array();
for ($i = 1; $i <= $query_count - ($remove? 1: 0); $i++)
{
	$aTabs[] = array(
		"DIV" => "tab".$i,
		"TAB" => GetMessage("CTRLR_RUN_COMMAND_FIELD")." (".$i.")",
		"TITLE" => GetMessage("CTRLR_RUN_COMMAND_TAB_TITLE"),
	);
}
$aTabs[] = array(
	"DIV" => "tab_plus",
	"TAB" => '',
	"ONSELECT" => "AddNewTab();",
);
$editTab = new CAdminTabControl("editTab", $aTabs);

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
			if (confirm('<?echo GetMessage("CTRLR_RUN_CONFIRM")?>'))
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
			var testVal;
			if (map1.size !== map2.size)
			{
				return false;
			}
			for (key in map1)
			{
				if (map1.hasOwnProperty(key))
				{
					val = map1[key];
					testVal = map2[key];
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
	<?
	if(
		$_SERVER['REQUEST_METHOD'] == 'POST'
		&& $_POST["ajax"] === "y"
		&& (isset($_POST["add"]) || $remove)
	)
	{
		$APPLICATION->RestartBuffer();
		?>
		<script>window.editTab = null;</script>
		<?
	}
	?>
	<form name="form1" action="<? echo $APPLICATION->GetCurPage() ?>" method="POST">
		<input type="hidden" name="lang" value="<?=LANG?>">
		<?
		if ($member_id > 0)
		{
			echo GetMessage("CTRLR_RUN_FILTER_SITE").': #'.$member_id."<br><br>";
		}
		else
		{
			$arGroups = Array();
			$dbr_groups = CControllerGroup::GetList(Array("SORT" => "ASC", "NAME" => "ASC", "ID" => "ASC"));
			while ($ar_groups = $dbr_groups->GetNext())
			{
				$arGroups[$ar_groups["ID"]] = $ar_groups["NAME"];
			}

			$filter = new CAdminFilter(
				$sTableID."_filter_id",
				Array(GetMessage("CTRLR_RUN_FILTER_GROUP"))
			);

			$filter->Begin();
			?>
			<tr>
				<td nowrap><label
						for="controller_member_id"><?= GetMessage("CTRLR_RUN_FILTER_SITE") ?></label>:
				</td>
				<td nowrap>
					<?
					$dbr_members = CControllerMember::GetList(array(
						"SORT" => "ASC",
						"NAME" => "ASC",
						"ID" => "ASC",
					), array(
						"DISCONNECTED" => "N",
					), array(
						"ID",
						"NAME",
					), array(
					), array(
						"nTopCount" => $maxSafeCount+1,
					));
					$arMembers = array();
					$c = 0;
					while ($ar_member = $dbr_members->Fetch())
					{
						$arMembers[$ar_member["ID"]] = $ar_member["NAME"];
						$c++;
						if ($maxSafeCount !== false && $c > $maxSafeCount)
						{
							$arMembers = array();
							break;
						}
					}

					if ($arMembers):?>
						<select name="controller_member_id" id="controller_member_id">
							<option value=""><? echo GetMessage("CTRLR_RUN_FILTER_SITE_ALL") ?></option>
							<? foreach ($arMembers as $ID => $NAME): ?>
								<option
									value="<? echo htmlspecialcharsbx($ID) ?>"
									<? if ($controller_member_id == $ID) echo ' selected'; ?>
								><? echo htmlspecialcharsEx($NAME." [".$ID."]") ?></option>
							<? endforeach ?>
						</select>
						<?
					else:?>
						<input
							type="text"
							name="controller_member_id"
							id="controller_member_id"
							value="<? echo htmlspecialcharsbx($controller_member_id) ?>"
							size="47"
						/>
					<? endif ?>
				</td>
			</tr>
			<tr>
				<td nowrap>
					<label for="controller_group_id"><? echo htmlspecialcharsEx(GetMessage("CTRLR_RUN_FILTER_GROUP")) ?></label>:
				</td>
				<td nowrap><? echo htmlspecialcharsEx($controller_group_id) ?>
					<select name="controller_group_id" id="controller_group_id">
						<option value=""><? echo GetMessage("CTRLR_RUN_FILTER_GROUP_ANY") ?></option>
						<? foreach ($arGroups as $group_id => $group_name): ?>
							<option
								value="<?= $group_id ?>"
								<? if ($group_id == $controller_group_id) echo "selected" ?>
							><?= $group_name ?></option>
						<? endforeach; ?>
					</select>
				</td>
			</tr>
			<?
			$filter->Buttons();
			$filter->End();
		}
		?>


		<?=bitrix_sessid_post()?>
		<?
		$editTab->Begin();
		for ($i = 1; $i <= $query_count - ($remove? 1: 0); $i++)
		{
			$index = $remove? ($i >= $remove? $i + 1: $i): $i;
			if (isset($_REQUEST['query'.$index]))
				$query = $_REQUEST['query'.$index];
			else
				$query = CUserOptions::GetOption("controller_run_command", "query".$index, '');
			
			$editTab->BeginNextTab();
			?>
			<tr>
				<td>
					<input type="hidden" name="lang" value="<?=LANG?>">
					<textarea name="query<?echo $i?>" id="query<?echo $i?>" rows="15" style="width:100%;" title=""><? echo htmlspecialcharsbx($query); ?></textarea>
					<?
					if (COption::GetOptionString('fileman', "use_code_editor", "Y") == "Y" && CModule::IncludeModule('fileman'))
					{
						CCodeEditor::Show(array(
							'textareaId' => 'query'.$i,
							'height' => 350,
							'forceSyntax' => 'php',
						));
					}
					?>
				</td>
			</tr>
			<tr>
				<td><br>
					<div class="adm-list">
						<div class="adm-list-item">
							<div class="adm-list-control"><input type="checkbox" id="add_task<?echo $i?>" name="add_task<?echo $i?>" title="<? echo GetMessage("CTRLR_RUN_ADD_TASK_LABEL") ?>" value="Y"></div>
							<div class="adm-list-label"><label for="add_task<?echo $i?>" title="<?echo GetMessage("CTRLR_RUN_ADD_TASK_LABEL")?>"><?echo GetMessage("CTRLR_RUN_ADD_TASK")?></label></div>
						</div>
						<div class="adm-list-item" style="display:none" id="tr_force">
							<div class="adm-list-control"><input type="checkbox" id="force" name="force" value="Y"></div>
							<div class="adm-list-label"><label for="force"><? echo GetMessage("CTRLR_RUN_FORCE_RUN") ?></label></div>
						</div>
					</div>
				</td>
			</tr>
		<?
		}
		$editTab->Buttons();
		?>
		<input
			type="button"
			accesskey="x"
			name="execute"
			value="<? echo GetMessage("CTRLR_RUN_BUTT_RUN") ?>"
			onclick="return __FPHPSubmit();"
			class="adm-btn-save"
			<? if (!$bCanRunCommand) echo 'disabled="disabled"' ?>
		>
		<?
		$editTab->End();
		?>
	</form>
	</div>
<?
if(
	$_SERVER['REQUEST_METHOD'] == 'POST'
	&& $_POST["ajax"] === "y"
	&& (isset($_POST["add"]) || $remove)
)
{
	if ($remove)
	{
		CUserOptions::SetOption("controller_run_command", "count", $query_count - 1);
	}
	?><script>adjustTabTitles();</script><?

	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
}
else
{
	?><div id="result_div"></div><?
}

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
