<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */

if (!$USER->CanDoOperation("controller_counters_view") || !CModule::IncludeModule("controller"))
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");

IncludeModuleLangFile(__FILE__);

$message = null;
$ID = intval($_REQUEST["ID"]);

if (
	$_SERVER["REQUEST_METHOD"] == "POST"
	&& check_bitrix_sessid()
	&& $USER->CanDoOperation("controller_counters_manage")
	&& (
		isset($_POST["save"])
		|| isset($_POST["apply"])
		|| isset($_POST["delete"])
	)
)
{
	if (isset($_POST["delete"]) && $_POST["delete"] === "y")
	{
		CControllerCounter::Delete($ID);

		if ($back_url == '')
			LocalRedirect("controller_counter_admin.php?lang=".LANGUAGE_ID);
		else
			LocalRedirect($back_url);

	}
	else
	{
		$arFields = array(
			"COUNTER_TYPE" => $_POST["COUNTER_TYPE"],
			"COUNTER_FORMAT" => $_POST["COUNTER_FORMAT"],
			"NAME" => $_POST["NAME"],
			"COMMAND" => $_POST["COMMAND"],
			"CONTROLLER_GROUP_ID" => $_POST["CONTROLLER_GROUP_ID"],
		);

		$obCounter = new CControllerCounter;

		if ($ID > 0)
			$res = $obCounter->Update($ID, $arFields);
		else
			$res = $ID = $obCounter->Add($arFields);

		if (!$res)
		{
			if ($e = $APPLICATION->GetException())
				$message = new CAdminMessage(GetMessage("CTRL_COUNTER_EDIT_ERROR"), $e);
		}
		else
		{
			if (isset($_POST["save"]))
			{
				if ($back_url == '')
					LocalRedirect("controller_counter_admin.php?lang=".LANGUAGE_ID);
				else
					LocalRedirect($back_url);
			}
			else
			{
				LocalRedirect("controller_counter_edit.php?lang=".LANGUAGE_ID."&ID=".$ID);
			}
		}
	}
}

$arCounter = CControllerCounter::GetArrayByID($ID);
if (!is_array($arCounter))
	$ID = 0;

if ($message !== null)
{
	$arCounter = array(
		"COUNTER_TYPE" => $_POST["COUNTER_TYPE"],
		"COUNTER_FORMAT" => $_POST["COUNTER_FORMAT"],
		"NAME" => $_POST["NAME"],
		"COMMAND" => $_POST["COMMAND"],
		"CONTROLLER_GROUP_ID" => is_array($_POST["CONTROLLER_GROUP_ID"])? $_POST["CONTROLLER_GROUP_ID"]: array(),
	);
}
elseif ($ID <= 0)
{
	$arCounter = array(
		"COUNTER_TYPE" => "F",
		"COUNTER_FORMAT" => "",
		"NAME" => "",
		"COMMAND" => "",
		"CONTROLLER_GROUP_ID" => array(),
	);
}

$sDocTitle = $ID > 0? GetMessage("CTRL_CNT_EDIT_TITLE", array("#ID#" => $ID)): GetMessage("CTRL_CNT_EDIT_TITLE_NEW");
$APPLICATION->SetTitle($sDocTitle);

/***************************************************************************
 * HTML form
 ****************************************************************************/

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
$aMenu = array(
	array(
		"ICON" => "btn_list",
		"TEXT" => GetMessage("CTRL_COUNTER_EDIT_TOOLBAR_LIST"),
		"LINK" => "controller_counter_admin.php?lang=".LANGUAGE_ID
	)
);

if ($ID > 0 && $USER->CanDoOperation("controller_counters_view"))
{
		$aMenu[] = array(
			"TEXT" => GetMessage("CTRL_COUNTER_EDIT_TOOLBAR_HISTORY_TEXT"),
			"TITLE" => GetMessage("CTRL_COUNTER_EDIT_TOOLBAR_HISTORY"),
			"LINK" => "controller_counter_history.php?COUNTER_ID=".$ID."&apply_filter=Y&lang=".LANG,
		);
}

if ($ID > 0 && $USER->CanDoOperation("controller_counters_manage"))
{
	$aMenu[] = array("SEPARATOR" => "Y");

	$aMenu[] = array(
		"ICON" => "btn_new",
		"TEXT" => GetMessage("CTRL_COUNTER_EDIT_TOOLBAR_NEW"),
		"LINK" => "controller_counter_edit.php?lang=".LANGUAGE_ID
	);

	$aMenu[] = array(
		"TEXT" => GetMessage("CTRL_COUNTER_EDIT_TOOLBAR_DELETE"),
		"ICON" => "btn_delete",
		"LINK" => "javascript:jsDelete('form1', '".GetMessage("CTRL_COUNTER_EDIT_TOOLBAR_DELETE_CONFIRM")."')",
	);
}

$context = new CAdminContextMenu($aMenu);
$context->Show();

$aTabs = array(
	array(
		"DIV" => "edit1",
		"TAB" => GetMessage("CTRL_COUNTER_EDIT_TAB1"),
		"TITLE" => GetMessage("CTRL_COUNTER_EDIT_TAB1_TITLE"),
	),
	array(
		"DIV" => "edit2",
		"TAB" => GetMessage("CTRL_COUNTER_EDIT_CONTROLLER_GROUP"),
		"TITLE" => GetMessage("CTRL_COUNTER_EDIT_CONTROLLER_GROUP_TITLE"),
	),
	array(
		"DIV" => "edit3",
		"TAB" => GetMessage("CTRL_COUNTER_EDIT_COMMAND"),
		"TITLE" => GetMessage("CTRL_COUNTER_EDIT_COMMAND_TITLE"),
	),
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);

if ($message)
	echo $message->Show();

?>
<script>
	function jsDelete(form_id, message)
	{
		var _form = BX(form_id);
		var _flag = BX('delete');
		if (_form && _flag)
		{
			if (confirm(message))
			{
				_flag.value = 'y';
				_form.submit();
			}
		}
	}
</script>

<form method="POST" action="<? echo $APPLICATION->GetCurPage() ?>?lang=<?=LANGUAGE_ID?>&amp;ID=<?=$ID?>" name="form1" id="form1">
	<? $tabControl->Begin(); ?>
	<? $tabControl->BeginNextTab(); ?>
	<tr class="adm-detail-required-field">
		<td width="40%"><? echo GetMessage("CTRL_COUNTER_EDIT_NAME") ?>:</td>
		<td width="60%">
			<input type="text" name="NAME" size="53" maxlength="255" value="<? echo htmlspecialcharsbx($arCounter["NAME"]) ?>">
		</td>
	</tr>
	<tr class="adm-detail-required-field">
		<td><? echo GetMessage("CTRL_COUNTER_EDIT_COUNTER_TYPE") ?>:</td>
		<td><select name="COUNTER_TYPE">
				<? foreach (CControllerCounter::GetTypeArray() as $key => $value): ?>
					<option value="<? echo htmlspecialcharsbx($key) ?>"<? if ($arCounter["COUNTER_TYPE"] == $key) echo " selected" ?>><? echo htmlspecialcharsEx($value) ?></option>
				<? endforeach; ?>
			</select>
		</td>
	</tr>
	<tr>
		<td><? echo GetMessage("CTRL_COUNTER_EDIT_COUNTER_FORMAT") ?>:</td>
		<td><select name="COUNTER_FORMAT">
				<? foreach (CControllerCounter::GetFormatArray() as $key => $value): ?>
					<option value="<? echo htmlspecialcharsbx($key) ?>"<? if ($arCounter["COUNTER_FORMAT"] == $key) echo " selected" ?>><? echo htmlspecialcharsEx($value) ?></option>
				<? endforeach; ?>
			</select>
		</td>
	</tr>
	<? $tabControl->BeginNextTab(); ?>
	<tr valign="top">
		<td width="40%" class="adm-detail-valign-top">&nbsp;</td>
		<td width="60%" valign="top">
			<div class="checkboxes">
				<input type="checkbox" id="group_filter_checkbox" />
				<input type="text" value="" id="group_filter">
				<?
				$dbr_group = CControllerGroup::GetList(Array("SORT" => "ASC", "NAME" => "ASC", "ID" => "ASC"));
				while ($ar_group = $dbr_group->GetNext()):
					?>
					<div class="adm-list-item">
						<input
							type="checkbox"
							name="CONTROLLER_GROUP_ID[]"
							id="CONTROLLER_GROUP_ID_<? echo htmlspecialcharsbx($ar_group["ID"]) ?>"
							value="<? echo htmlspecialcharsbx($ar_group["ID"]) ?>"
							<? if (in_array($ar_group["ID"], $arCounter["CONTROLLER_GROUP_ID"])) echo " checked" ?>
						/>
						<label
							for="CONTROLLER_GROUP_ID_<? echo htmlspecialcharsbx($ar_group["ID"]) ?>"
						><? echo htmlspecialcharsEx($ar_group["NAME"]) ?></label>
					</div>
				<? endwhile; ?>
			</div>
			<script>
				function group_filter_change()
				{
					var group_filter = BX('group_filter');
					var check_boxes = document.getElementsByName('CONTROLLER_GROUP_ID[]');
					var all_checked = true;
					var all_unchecked = true;
					for (var i = 0; i < check_boxes.length; i++)
					{
						var found = false;
						var labels = check_boxes[i].labels;
						for (var j = 0; j < labels.length; j++)
						{
							if (labels[j].innerHTML.indexOf(group_filter.value) != -1)
							{
								found = true;
								break;
							}
						}
						check_boxes[i].parentNode.style.display = found? 'block': 'none';
						if (found)
						{
							all_checked = all_checked && check_boxes[i].checked;
							all_unchecked = all_unchecked && !check_boxes[i].checked;
						}
					}
				}
				function group_filter_checkbox_change()
				{
					var group_filter = BX('group_filter');
					var group_filter_checkbox = BX('group_filter_checkbox');
					var check_boxes = document.getElementsByName('CONTROLLER_GROUP_ID[]');
					for (var i = 0; i < check_boxes.length; i++)
					{
						if (check_boxes[i].parentNode.style.display != 'none')
							check_boxes[i].checked = group_filter_checkbox.checked;
					}
				}
				BX.ready(function(){
					BX.bind(BX('group_filter'), "bxchange", group_filter_change);
					BX.bind(BX('group_filter_checkbox'), "click", group_filter_checkbox_change);
				});
			</script>
		</td>
	</tr>
	<? $tabControl->BeginNextTab(); ?>
	<tr>
		<td colspan="2">
			<textarea name="COMMAND" id="COMMAND" style="width:100%" rows="20"><? echo htmlspecialcharsbx($arCounter["COMMAND"]) ?></textarea>
		</td>
	</tr>
	<? $tabControl->EndTab(); ?>
	<? $tabControl->Buttons(array(
		"back_url" => $back_url? $back_url: "controller_counter_admin.php?lang=".LANGUAGE_ID,
		"disabled" => !$USER->CanDoOperation("controller_counters_manage"),
	)); ?>
	<? $tabControl->End(); ?>
	<? echo bitrix_sessid_post(); ?>
	<input type="hidden" name="lang" value="<? echo LANGUAGE_ID ?>">
	<? if ($ID > 0): ?>
		<input type="hidden" name="ID" value="<?=$ID?>">
		<input type="hidden" name="delete" id="delete" value="">
	<? endif; ?>
	<? if ($back_url != ''): ?>
		<input type="hidden" name="back_url" value="<? echo htmlspecialcharsbx($back_url) ?>">
	<? endif ?>
	<input type="hidden" value="Y" name="apply">
</form>
<?
if (COption::GetOptionString('fileman', "use_code_editor", "Y") == "Y" && CModule::IncludeModule('fileman'))
{
	CCodeEditor::Show(array(
		'textareaId' => 'COMMAND',
		'height' => 350,
		'forceSyntax' => 'php',
	));
}
?>
<? require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php"); ?>
