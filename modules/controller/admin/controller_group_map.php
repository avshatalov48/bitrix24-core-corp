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


if ($_REQUEST["type"] === "loc")
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
elseif ($_REQUEST["type"] === "trans")
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
$sorting = new CAdminUiSorting($tableID, "ID", "DESC");
/** @global string $by */
/** @global string $order */
$adminList = new CAdminUiList($tableID, $sorting);

$groups = array();
$groupList = CGroup::GetList();
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
	foreach ($_REQUEST["FIELDS"] as $ID => $fields)
	{
		$errors = array();
		foreach ($controls as $controlName)
		{
			if ($fields[$controlName] == '')
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


$arID = $adminList->GroupAction();
if ($arID && $USER->CanDoOperation("controller_auth_manage"))
{
	if ($_REQUEST['action_target'] == 'selected')
	{
		$arID = array_keys($groupMap);
	}

	foreach ($arID as $ID)
	{
		if (!isset($groupMap[$ID]))
			continue;

		switch ($_REQUEST['action_button'])
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

$nav = $adminList->getPageNavigation("nav-controller-group-map");

$groupMapList = GroupMapTable::getList(array(
	'filter' => $filter,
	'order' => array(mb_strtoupper($by) => $order),
	'count_total' => true,
	'offset' => $nav->getOffset(),
	'limit' => $nav->getLimit(),
));

$nav->setRecordCount($groupMapList->getCount());

$adminList->setNavigation($nav, Loc::getMessage("CONTROLLER_GROUP_MAP_PAGES"));

$adminList->AddHeaders($headers);

if ($USER->CanDoOperation("controller_auth_manage"))
{
	$row = &$adminList->AddRow('new', []);
	$row->AddViewField("ID", '<script>showNewRow();</script>');
	$row->AddSelectField("CONTROLLER_GROUP_ID", $groups);
	$row->AddInputField("REMOTE_GROUP_CODE", array("size" => "30"));
	$row->AddInputField("LOCAL_GROUP_CODE", array("size" => "30"));
}

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

if ($USER->CanDoOperation("controller_auth_manage"))
{
	$adminList->AddGroupActionTable(array(
		"edit" => true,
		"delete" => true,
	));

	$aContext = array(
		array(
			"ICON" => "btn_new",
			"TEXT" => GetMessage("MAIN_ADD"),
			"LINK" => "javascript:showNewRow(1)",
			"TITLE" => GetMessage("MAIN_ADD")
		),
	);

	$adminList->BeginPrologContent();
	?>
	<script>
		function showNewRow(show)
		{
			var tr = BX.findChildren(BX('<?=$tableID?>'), {
				tag: 'TR',
				class: 'main-grid-row main-grid-row-body',
				attr: {'data-id': 'new'}}, true);
			if (tr)
			{
				tr[0].style.display = show? 'table-row': 'none';
				if (show)
				{
					var gridInstance = BX.Main.gridManager.getById('<?=$tableID?>').instance;
					var newRow = gridInstance.getRows().getById('new');
					newRow.select();
					var editButton = gridInstance.getActionsPanel().getButtons()
							.find(function(button) {
								return button.id === "grid_edit_button_control";
							});
					BX.fireEvent(editButton, 'click');
				}
			}
		}
	</script>
	<?
	$adminList->EndPrologContent();
}
else
{
	$aContext = array();
}
$adminList->AddAdminContextMenu($aContext);


$adminList->CheckListMode();

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");

$adminList->DisplayList();

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
