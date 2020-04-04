<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */
use Bitrix\Main\Localization\Loc;
use \Bitrix\Controller\AuthLogTable;

if (!$USER->CanDoOperation("controller_auth_log_view") || !\Bitrix\Main\Loader::includeModule("controller"))
{
	$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");

Loc::loadMessages(__FILE__);

$tableID = "t_controller_auth_log";
$sorting = new CAdminSorting($tableID, "ID", "DESC");
/** @global string $by */
/** @global string $order */
$adminList = new CAdminList($tableID, $sorting);

$arFilterRows = array(
	"USER_ID" => Loc::getMessage("CONTROLLER_AUTH_LOG_USER_ID"),
	"FROM_CONTROLLER_MEMBER" => Loc::getMessage("CONTROLLER_AUTH_LOG_FROM_CONTROLLER_MEMBER"),
	"TO_CONTROLLER_MEMBER" => Loc::getMessage("CONTROLLER_AUTH_LOG_TO_CONTROLLER_MEMBER"),
	"TIMESTAMP_X" => Loc::getMessage("CONTROLLER_AUTH_LOG_TIMESTAMP_X"),
);

$filter = new CAdminFilter(
	$sTableID."_filter_id",
	$arFilterRows
);

$arFilterFields = Array(
	"find_user_id",
	"find_from_controller_member",
	"find_to_controller_member",
	"find_timestamp_x_from",
	"find_timestamp_x_to",
);

$adminFilter = $adminList->InitFilter($arFilterFields);

$listFilter = array();
if ($adminFilter["find_user_id"] > 0)
	$listFilter["=USER_ID"] = intval($adminFilter["find_user_id"]);
if (preg_match("/^[0-9]+$/", $adminFilter["find_from_controller_member"]))
	$listFilter["=FROM_CONTROLLER_MEMBER_ID"] = intval($adminFilter["find_from_controller_member"]);
elseif ($adminFilter["find_from_controller_member"] <> "")
	$listFilter["=FROM_CONTROLLER_MEMBER.NAME"] = $adminFilter["find_from_controller_member"];
if (preg_match("/^[0-9]+$/", $adminFilter["find_to_controller_member"]))
	$listFilter["=TO_CONTROLLER_MEMBER_ID"] = intval($adminFilter["find_to_controller_member"]);
elseif ($adminFilter["find_to_controller_member"] <> "")
	$listFilter["=TO_CONTROLLER_MEMBER.NAME"] = $adminFilter["find_to_controller_member"];

$nav = new \Bitrix\Main\UI\AdminPageNavigation("nav-controller-auth-log");
\Bitrix\Main\Application::getConnection()->startTracker();
\Bitrix\Main\Application::getConnection()->getTracker()->startFileLog("/tmp/debug03.log");
$authLogList = AuthLogTable::getList(array(
	'select' => array(
		'ID',
		'TIMESTAMP_X',
		'FROM_CONTROLLER_MEMBER_ID',
		'FROM_CONTROLLER_MEMBER.NAME',
		'TO_CONTROLLER_MEMBER_ID',
		'TO_CONTROLLER_MEMBER.NAME',
		'TYPE',
		'USER_ID',
		'USER_NAME',
	),
	'filter' => $listFilter,
	'order' => array(strtoupper($by) => $order),
	'count_total' => true,
	'offset' => $nav->getOffset(),
	'limit' => $nav->getLimit(),
));
\Bitrix\Main\Application::getConnection()->getTracker()->stopFileLog();
$nav->setRecordCount($authLogList->getCount());

$adminList->setNavigation($nav, Loc::getMessage("CONTROLLER_AUTH_LOG_PAGES"));

$adminList->AddHeaders(array(
	array(
		"id" => "ID",
		"content" => Loc::getMessage("CONTROLLER_AUTH_LOG_ID"),
		"sort" => "ID",
		"default" => true,
	),
	array(
		"id" => "TIMESTAMP_X",
		"content" => Loc::getMessage("CONTROLLER_AUTH_LOG_TIMESTAMP_X"),
		"sort" => "TIMESTAMP_X",
		"default" => true,
	),
	array(
		"id" => "FROM_CONTROLLER_MEMBER",
		"content" => Loc::getMessage("CONTROLLER_AUTH_LOG_FROM_CONTROLLER_MEMBER"),
		"default" => true,
	),
	array(
		"id" => "TO_CONTROLLER_MEMBER",
		"content" => Loc::getMessage("CONTROLLER_AUTH_LOG_TO_CONTROLLER_MEMBER"),
		"default" => true,
	),
	array(
		"id" => "TYPE",
		"content" => Loc::getMessage("CONTROLLER_AUTH_LOG_TYPE"),
		"default" => true,
	),
	array(
		"id" => "USER",
		"content" => Loc::getMessage("CONTROLLER_AUTH_LOG_USER"),
		"default" => true,
	),
));

while ($authLog = $authLogList->fetch())
{
	$row = &$adminList->AddRow(intval($authLog["ID"]), $authLog);

	$row->AddViewField("ID", htmlspecialcharsEx($authLog["ID"]));
	$row->AddViewField("TIMESTAMP_X", htmlspecialcharsEx($authLog["TIMESTAMP_X"]));

	if ($authLog['FROM_CONTROLLER_MEMBER_ID'] > 0)
	{
		$htmlLink = 'controller_member_edit.php?lang='.LANGUAGE_ID.'&ID='.urlencode($authLog['FROM_CONTROLLER_MEMBER_ID']);
		$htmlName = $authLog['CONTROLLER_AUTH_LOG_FROM_CONTROLLER_MEMBER_NAME'].' ['.$authLog['FROM_CONTROLLER_MEMBER_ID'].']';
		$row->AddViewField("FROM_CONTROLLER_MEMBER", '<a href="'.htmlspecialcharsbx($htmlLink).'">'.htmlspecialcharsEx($htmlName).'</a>');
	}

	if ($authLog['TO_CONTROLLER_MEMBER_ID'] > 0)
	{
		$htmlLink = 'controller_member_edit.php?lang='.LANGUAGE_ID.'&ID='.urlencode($authLog['TO_CONTROLLER_MEMBER_ID']);
		$htmlName = $authLog['CONTROLLER_AUTH_LOG_TO_CONTROLLER_MEMBER_NAME'].' ['.$authLog['TO_CONTROLLER_MEMBER_ID'].']';
		$row->AddViewField("TO_CONTROLLER_MEMBER", '<a href="'.htmlspecialcharsbx($htmlLink).'">'.htmlspecialcharsEx($htmlName).'</a>');
	}

	if ($authLog['USER_ID'] > 0)
	{
		$htmlLink = 'user_edit.php?lang='.LANGUAGE_ID.'&ID='.urlencode($authLog['USER_ID']);
		$htmlName = $authLog['USER_NAME'];
		$row->AddViewField("USER", '<a href="'.htmlspecialcharsbx($htmlLink).'">'.htmlspecialcharsEx($htmlName).'</a>');
	}
	elseif ($authLog['USER_NAME'] <> '')
	{
		$row->AddViewField("USER", htmlspecialcharsEx($authLog['USER_NAME']));
	}
}

$adminList->AddAdminContextMenu(array());

$adminList->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage("CONTROLLER_AUTH_LOG_TITLE"));

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
?>
	<form name="form1" method="GET" action="<? echo $APPLICATION->GetCurPage() ?>?">
		<? $filter->Begin(); ?>
		<tr>
			<td nowrap><label for="find_user_id"><?=GetMessage("CONTROLLER_AUTH_LOG_USER_ID")?></label>:</td>
			<td nowrap>
				<input
					type="text"
					name="find_user_id"
					id="find_user_id"
					value="<? echo htmlspecialcharsbx($adminFilter['find_user_id']) ?>"
					size="47"
				>
			</td>
		</tr>
		<tr>
			<td nowrap><label for="find_from_controller_member"><?=GetMessage("CONTROLLER_AUTH_LOG_FROM_CONTROLLER_MEMBER")?></label>:</td>
			<td nowrap>
				<input
					type="text"
					name="find_from_controller_member"
					id="find_from_controller_member"
					value="<? echo htmlspecialcharsbx($adminFilter['find_from_controller_member']) ?>"
					size="47"
				>
			</td>
		</tr>
		<tr>
			<td nowrap><label for="find_to_controller_member"><?=GetMessage("CONTROLLER_AUTH_LOG_TO_CONTROLLER_MEMBER")?></label>:</td>
			<td nowrap>
				<input
					type="text"
					name="find_to_controller_member"
					id="find_to_controller_member"
					value="<? echo htmlspecialcharsbx($adminFilter['find_to_controller_member']) ?>"
					size="47"
				>
			</td>
		</tr>
		<tr>
			<td nowrap><?=GetMessage("CONTROLLER_AUTH_LOG_TIMESTAMP_X")?>:</td>
			<td nowrap><? echo CalendarPeriod("find_timestamp_x_from", $adminFilter['find_timestamp_x_from'], "find_timestamp_x_to", $adminFilter['find_timestamp_x_to'], "form1", "Y") ?></td>
		</tr>
		<?
		$filter->Buttons(array(
			"table_id" => $sTableID,
			"url" => $APPLICATION->GetCurPage(),
			"form" => "form1",
		));
		$filter->End();
		?>

	</form>
<?

$adminList->DisplayList();

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
