<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */
use Bitrix\Main\Localization\Loc;
use \Bitrix\Controller\GroupMapTable;

if (!$USER->CanDoOperation("controller_auth_view") || !\Bitrix\Main\Loader::includeModule("controller"))
{
	$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");

Loc::loadMessages(__FILE__);

/** @var $request \Bitrix\Main\HttpRequest */
$request = \Bitrix\Main\Context::getCurrent()->getRequest();

if ($request["type"] === "loc")
{
	$type = "loc";
	$filter = array(
		"!=CONTROLLER_GROUP_ID" => false,
		"!=REMOTE_GROUP_CODE" => false,
	);
	$headers = array(
		array("id" => "ID", "content" => Loc::getMessage("CONTROLLER_GROUP_MAP_ID"), "sort" => "ID", "default" => true),
		array("id" => "CONTROLLER_GROUP_ID", "content" => Loc::getMessage("CONTROLLER_GROUP_MAP_CONTROLLER_GROUP_ID"), "default" => true),
		array("id" => "REMOTE_GROUP_CODE", "content" => Loc::getMessage("CONTROLLER_GROUP_MAP_REMOTE_GROUP_CODE"), "default" => true),
	);
	$controls = array(
		"CONTROLLER_GROUP_ID",
		"REMOTE_GROUP_CODE",
	);
	$title = Loc::getMessage("CONTROLLER_GROUP_MAP_CS_TITLE");
}
elseif ($request["type"] === "trans")
{
	$type = "trans";
	$filter = array(
		"!=LOCAL_GROUP_CODE" => false,
		"!=REMOTE_GROUP_CODE" => false,
	);
	$headers = array(
		array("id" => "ID", "content" => Loc::getMessage("CONTROLLER_GROUP_MAP_ID"), "sort" => "ID", "default" => true),
		array("id" => "LOCAL_GROUP_CODE", "content" => Loc::getMessage("CONTROLLER_GROUP_MAP_LOCAL_GROUP_CODE"), "default" => true),
		array("id" => "REMOTE_GROUP_CODE", "content" => Loc::getMessage("CONTROLLER_GROUP_MAP_REMOTE_GROUP_CODE"), "default" => true),
	);
	$controls = array(
		"LOCAL_GROUP_CODE",
		"REMOTE_GROUP_CODE",
	);
	$title = Loc::getMessage("CONTROLLER_GROUP_MAP_SS_TITLE");
}
else
{
	$type = "";
	$filter = array(
		"!=LOCAL_GROUP_CODE" => false,
		"!=CONTROLLER_GROUP_ID" => false,
	);
	$headers = array(
		array("id" => "ID", "content" => Loc::getMessage("CONTROLLER_GROUP_MAP_ID"), "sort" => "ID", "default" => true),
		array("id" => "LOCAL_GROUP_CODE", "content" => Loc::getMessage("CONTROLLER_GROUP_MAP_LOCAL_GROUP_CODE"), "default" => true),
		array("id" => "CONTROLLER_GROUP_ID", "content" => Loc::getMessage("CONTROLLER_GROUP_MAP_CONTROLLER_GROUP_ID"), "default" => true),
	);
	$controls = array(
		"LOCAL_GROUP_CODE",
		"CONTROLLER_GROUP_ID",
	);
	$title = Loc::getMessage("CONTROLLER_GROUP_MAP_SC_TITLE");
}

$tableID = "t_controller_group_map_".$type;
$sorting = new CAdminSorting($tableID, "ID", "ASC");
/** @global string $by */
/** @global string $order */
$adminList = new CAdminList($tableID, $sorting);

$groups = array();
$groupList = CGroup::GetList($o = "sort", $b = "asc");
while ($group = $groupList->GetNext())
{
	$groups[$group['ID']] = $group['NAME'];
}

$groupMap = array();
$data = GroupMapTable::getList(array(
	"filter" => $filter,
));
while ($record = $data->fetch())
{
	$groupMap[$record['ID']] = $record;
}

if ($adminList->EditAction() && $USER->CanDoOperation("controller_auth_manage"))
{
	foreach ($request["FIELDS"] as $ID => $fields)
	{
		$errors = array();
		foreach ($controls as $controlName)
		{
			if (strlen($fields[$controlName]) <= 0)
			{
				$errors[] = Loc::getMessage("CONTROLLER_GROUP_MAP_".$controlName."_ERROR");
			}
		}

		if ($ID === "new")
		{
			if ($errors)
			{
				$adminList->AddUpdateError(implode("<br>", $errors));
			}
			elseif (!GroupMapTable::isExists($fields))
			{
				$result = GroupMapTable::add($fields);
				if (!$result->isSuccess())
				{
					$adminList->AddUpdateError(implode("<br>", $result->getErrorMessages()));
				}
			}
		}
		else
		{
			if (!isset($groupMap[$ID]))
				continue;
			if (!$adminList->IsUpdated($ID))
				continue;

			if ($errors)
			{
				$adminList->AddUpdateError("(ID=".$ID.") ".implode("<br>", $errors));
			}
			elseif (!GroupMapTable::isExists($fields))
			{
				$result = GroupMapTable::update($ID, $fields);
				if (!$result->isSuccess())
				{
					$adminList->AddUpdateError("(ID=".$ID.") ".implode("<br>", $result->getErrorMessages()), $ID);
				}
			}
		}
	}
}

if (($arID = $adminList->GroupAction()) && $USER->CanDoOperation("controller_auth_manage"))
{
	if ($request['action_target'] == 'selected')
	{
		$arID = array_keys($groupMap);
	}

	foreach ($arID as $ID)
	{
		if (!isset($groupMap[$ID]))
			continue;

		switch ($request['action_button'])
		{
		case "delete":
			$result = GroupMapTable::delete($ID);
			if (!$result->isSuccess())
			{
				$adminList->AddGroupError("(ID=".$ID.") ".implode("<br>", $result->getErrorMessages()), $ID);
			}
			break;
		}
	}
}

$APPLICATION->SetTitle($title);

$nav = new \Bitrix\Main\UI\AdminPageNavigation("nav-controller-group-map");

$groupMapList = GroupMapTable::getList(array(
	'filter' => $filter,
	'order' => array(strtoupper($by) => $order),
	'count_total' => true,
	'offset' => $nav->getOffset(),
	'limit' => $nav->getLimit(),
));

$nav->setRecordCount($groupMapList->getCount());

$adminList->setNavigation($nav, Loc::getMessage("CONTROLLER_GROUP_MAP_PAGES"));

$adminList->AddHeaders($headers);

while ($groupMap = $groupMapList->fetch())
{
	$row = &$adminList->AddRow(intval($groupMap["ID"]), $groupMap);
	$row->AddViewField("ID", htmlspecialcharsEx($groupMap["ID"]));
	$row->AddSelectField("CONTROLLER_GROUP_ID", $groups);
	$row->AddInputField("REMOTE_GROUP_CODE", array("size" => "30"));
	$row->AddInputField("LOCAL_GROUP_CODE", array("size" => "30"));

	$arActions = array();
	if ($USER->CanDoOperation("controller_auth_manage"))
	{
		$arActions[] = array(
			"ICON" => "delete",
			"TEXT" => Loc::getMessage("CONTROLLER_GROUP_MAP_DELETE"),
			"ACTION" => "if(confirm('".Loc::getMessage('CONTROLLER_GROUP_MAP_CONFIRM_DEL')."')) ".$adminList->ActionDoGroup(intval($groupMap["ID"]), "delete", "type=".$type),
		);
	}

	$row->AddActions($arActions);
}

$adminList->AddGroupActionTable(array(
	"delete" => true,
));

if ($USER->CanDoOperation("controller_auth_manage"))
{
	$aContext = array(
		array(
			"TEXT" => Loc::getMessage("CONTROLLER_GROUP_MAP_ADD"),
			"LINK" => "javascript:show_add_form()",
			"TITLE" => Loc::getMessage("CONTROLLER_GROUP_MAP_ADD_TITLE"),
			"ICON" => "btn_new"
		),
	);
}
else
{
	$aContext = array();
}
$adminList->AddAdminContextMenu($aContext);

$adminList->CheckListMode();

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");

if ($USER->CanDoOperation("controller_auth_manage"))
{
	CUtil::InitJSCore(array('fx'));

	$aTabs = array(
		array(
			"DIV" => "edit1",
			"TAB" => Loc::getMessage("CONTROLLER_GROUP_MAP_NEW_TAB"),
			"ICON" => "main_user_edit",
		),
	);
	$tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);
	?>
	<script>

		function show_add_form()
		{
			(new BX.fx({
				start: 0,
				finish: 200,
				time: 0.5,
				type: 'accelerated',
				callback: function (res)
				{
					BX('add_form', true).style.height = res + 'px';
				},
				callback_start: function ()
				{
					BX('add_form', true).style.height = '0px';
					BX('add_form', true).style.overflow = 'hidden';
					BX('add_form', true).style.display = 'block';
				},
				callback_complete: function ()
				{
					BX('add_form', true).style.height = 'auto';
					BX('add_form', true).style.overflow = 'auto';
				}
			})).start();
		}
		function hide_add_form()
		{
			BX('add_form').style.display = 'none';
		}

		function add_action()
		{
			ShowWaitWindow();
			BX('add_action_button').enabled = false;

			BX.ajax.submit(BX('editform'),
				function (result)
				{
					BX('<?echo $tableID?>_result_div').innerHTML = result;
					CloseWaitWindow();
					BX('add_action_button').disabled = false;
				}
			);

		}

	</script>
	<div id="add_form" style="display:none;height:200px;">
		<form method="POST" action="<? echo htmlspecialcharsbx($APPLICATION->GetCurPageParam()) ?>"
			enctype="multipart/form-data" name="editform" id="editform">
			<?
			$tabControl->Begin();
			$tabControl->BeginNextTab();
			foreach ($controls as $controlName)
			{
				?>
				<tr class="adm-detail-required-field">
					<td width="40%"><? echo Loc::getMessage("CONTROLLER_GROUP_MAP_".$controlName) ?>:</td>
					<td width="60%">
						<?if ($controlName === "CONTROLLER_GROUP_ID"):?>
							<? $groupList = CGroup::GetList($o = "sort", $b = "asc"); ?>
							<select name="FIELDS[new][<? echo $controlName ?>]">
								<option value=""></option>
								<? foreach ($groups as $groupId => $groupName): ?>
									<option value="<?=$groupId?>"><?=$groupName?> [<?echo $groupId?>]
									</option>
								<? endforeach; ?>
							</select>
						<?else:?>
							<input type="text" id="<? echo $controlName ?>" name="FIELDS[new][<? echo $controlName ?>]" size="30" value="">
						<?endif?>
					</td>
				</tr>
				<?
			}
			$tabControl->Buttons(false);
			?>
			<input type="hidden" name="mode" value="frame">
			<input type="hidden" name="save" value="y">
			<input type="hidden" name="ID" value="new">
			<? echo bitrix_sessid_post(); ?>
			<input type="hidden" name="lang" value="<? echo LANGUAGE_ID ?>">
			<input type="button" id="add_action_button" onclick="add_action();"
				value="<? echo Loc::getMessage("CONTROLLER_GROUP_MAP_ADD_BTN") ?>" class="adm-btn-save">
			<input type="button" value="<? echo Loc::getMessage("CONTROLLER_GROUP_MAP_CANCEL_BTN") ?>"
				onclick="hide_add_form()">
			<?
			$tabControl->End();
			?>
		</form>
	</div>
	<?
}

$adminList->DisplayList();

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
