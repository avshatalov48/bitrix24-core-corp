<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Application;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Counter\Name;
use Bitrix\Tasks\Util\User; ?>
<?
CUtil::InitJSCore(array("popup"));

if (
	!isset($arResult["User"]["ID"])
	|| (
		$USER->IsAuthorized()
		&& $arResult["User"]["ID"] == $USER->GetID()
		&& $arParams["PAGE_ID"] != "user"
	)
)
{
	return;
}


$this->addExternalCss(SITE_TEMPLATE_PATH."/css/profile_menu.css");
$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "")."profile-menu-mode");

if (!(isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y"))
{
	$this->SetViewTarget("above_pagetitle", 100);
}
elseif($arParams['PAGE_ID'] == 'user')
{
	$this->SetViewTarget("below_pagetitle", 100);
}

$className = '';
if ($arResult["User"]["TYPE"] == 'extranet')
{
	$className = ' profile-menu-user-info-extranet';
}
elseif ($arResult["User"]["TYPE"] == 'email')
{
	$className = ' profile-menu-user-info-email';
}
/*
elseif ($arResult["User"]["TYPE"] == 'imconnector')
{
	$className = ' profile-menu-user-info-imconnector';
}
elseif ($arResult["User"]["TYPE"] == 'bot')
{
	$className = ' profile-menu-user-info-bot';
}
elseif ($arResult["User"]["TYPE"] == 'replica')
{
	$className = ' profile-menu-user-info-replica';
}
*/
elseif ($arResult["User"]["IS_EXTRANET"] == 'Y')
{
	$className = ' profile-menu-user-info-extranet';
}
?>

<?
$requestUri = Application::getInstance()->getContext()->getRequest()->getRequestUri();

$items = array(
	"profile" => array
	(
		"TEXT" => GetMessage("SONET_UM_GENERAL"),
		"CLASS" => "",
		"CLASS_SUBMENU_ITEM" => "",
		"ID" => "profile",
		"SUB_LINK" => "",
		"COUNTER" => "",
		"COUNTER_ID" => "",
		"IS_ACTIVE" => true,
		"IS_LOCKED" => "",
		"IS_DISABLED" => 1
	),
);

if (
	is_array($arResult["CanView"])
	&& !!$arResult["CanView"]['tasks']
)
{
	$uri = new \Bitrix\Main\Web\Uri($arResult["Urls"]['tasks']);
	$uri->addParams(array("IFRAME" => "Y"));
	$redirect = $uri->getUri();

	$items = array_merge($items, array(
		"tasks" => array
		(
			"ID" => "tasks",
			"TEXT" => $arResult["Title"]['tasks'],
			"ON_CLICK" => "BX.SidePanel.Instance.open('".$uri->getUri()."', { width: 1000, loader: 'intranet:tasklist', })",
			'SUB_LINK' => array(
				'CLASS' => '',
				'URL' => SITE_DIR."company/personal/user/".$arResult["User"]["ID"]."/tasks/task/edit/0/"
			),
			'IS_ACTIVE' => (mb_strpos($requestUri, $arResult["Urls"]['tasks']) === 0)
		)
	));
}

if (
	is_array($arResult["CanView"])
	&& !!$arResult["CanView"]['calendar']
)
{
	$uri = new \Bitrix\Main\Web\Uri($arResult["Urls"]['calendar']);
	$uri->addParams(array("IFRAME" => "Y"));
	$redirect = $uri->getUri();

	$items = array_merge($items, array(
		"calendar" => array
		(
			"ID" => "calendar",
			"TEXT" => $arResult["Title"]['calendar'],
			"ON_CLICK" => "BX.SidePanel.Instance.open('".$uri->getUri()."', { width: 1000, loader: 'intranet:calendar' })",
			'IS_ACTIVE' => (mb_strpos($requestUri, $arResult["Urls"]['calendar']) === 0)
		)
	));
}

if (
	is_array($arResult["CanView"])
	&& !!$arResult["CanView"]['files']
)
{
	$uri = new \Bitrix\Main\Web\Uri($arResult["Urls"]['files']);
	$uri->addParams(array("IFRAME" => "Y"));
	$redirect = $uri->getUri();

	$items = array_merge($items, array(
		"files" => array
		(
			"ID" => "files",
			"TEXT" => $arResult["Title"]['files'],
			"ON_CLICK" => "BX.SidePanel.Instance.open('".$uri->getUri()."', { width: 1000, loader: 'intranet:disk' })",
			'IS_ACTIVE' => (mb_strpos($requestUri, $arResult["Urls"]['files']) === 0)
		)
	));
}

if (
	is_array($arResult["CanView"])
	&& !!$arResult["CanView"]['blog']
)
{
	$uri = new \Bitrix\Main\Web\Uri($arResult["Urls"]['blog']);
	$uri->addParams(array("IFRAME" => "Y"));
	$redirect = $uri->getUri();

	$items = array_merge($items, array(
		"blog" => array
		(
			"ID" => "blog",
			"TEXT" => $arResult["Title"]['blog'],
			"ON_CLICK" => "BX.SidePanel.Instance.open('".$uri->getUri()."', {
				loader: 'intranet:livefeed', 
				width: 1000 
			})",
			'IS_ACTIVE' => (mb_strpos($requestUri, $arResult["Urls"]['blog']) === 0),
			'URL' => $uri->getUri()
		)
	));
}

if (
	is_array($arResult["CanView"])
	&& !!$arResult["CanView"]['tasks']
	&& checkEffectiveRights($arResult["User"]["ID"])
)
{
	$uri = new \Bitrix\Main\Web\Uri($arResult["Urls"]['tasks']);
	$uri->addParams(array("IFRAME" => "Y"));
	$redirect = $uri->getUri();
	\CModule::includeModule('tasks');

	$efficiencyUrl = (
		$arResult['isExtranetSite']
			? SITE_DIR."contacts/personal/user/".$arResult["User"]["ID"]."/tasks/effective/"
			: SITE_DIR."company/personal/user/".$arResult["User"]["ID"]."/tasks/effective/"
	);
	$items['effective_counter'] = array(
		"TEXT" => GetMessage("SONET_UM_EFFICIENCY"),
		"ON_CLICK" => "BX.SidePanel.Instance.open('".$efficiencyUrl."', { width: 1000 })",
		"COUNTER" => Counter::getInstance($arResult["User"]["ID"])->get(Name::EFFECTIVE),
		'MAX_COUNTER_SIZE'=>100,
		'COUNTER_ID' => 'effective_counter',
		'ID' => 'effective_counter',
		'CLASS' => 'effective_counter',
		'IS_ACTIVE' => (mb_strpos($requestUri, $efficiencyUrl) === 0)
	);
}

if (
	isset($items['effective_counter'])
	&& isset($items['tasks'])
	&& $items['effective_counter']['IS_ACTIVE']
)
{
	$items['tasks']['IS_ACTIVE'] = false;
}

foreach($items as $key => $item)
{
	if (
		$key != 'profile'
		&& $item['IS_ACTIVE']
	)
	{
		$items['profile']['IS_ACTIVE'] = false;
	}
}

$items = array_values($items);

function checkEffectiveRights($viewedUser)
{
	//TODO move to tasks/security later
	\Bitrix\Main\Loader::includeModule('tasks');
	$currentUser = User::getId();

	if (!$viewedUser)
	{
		return false;
	}

	return
		$currentUser == $viewedUser ||
		User::isSuper($currentUser) ||
		User::isBossRecursively($currentUser, $viewedUser);
}

if (
	is_array($arResult["CurrentUserPerms"])
	&& is_array($arResult["CurrentUserPerms"]["Operations"])
	&& !!$arResult["CurrentUserPerms"]["Operations"]["timeman"]
)
{
	$items = array_merge($items, array(
		array
		(
			"ID" => "timeman",
			"TEXT"     => GetMessage("SONET_UM_TIME"),
			"ON_CLICK" => "BX.SidePanel.Instance.open('".SITE_DIR."timeman/timeman.php?USERS=U".$arResult["User"]["ID"]."&apply_filter=Y', { width: 1000, loader: 'intranet:worktime', })"
		),
		array
		(
			"ID" => "work_report",
			"TEXT"     => GetMessage("SONET_UM_REPORTS"),
			"ON_CLICK" => "BX.SidePanel.Instance.open('".SITE_DIR."timeman/work_report.php', { width: 1000, loader: 'intranet:workreport' })"
		)
	));
}

if (
	is_array($arResult["CurrentUserPerms"])
	&& is_array($arResult["CurrentUserPerms"]["Operations"])
	&& !!$arResult["CurrentUserPerms"]["Operations"]['viewgroups']
)
{
	$uri = new \Bitrix\Main\Web\Uri($arResult["Urls"]['groups']);
	$uri->addParams(array("IFRAME" => "Y"));
	$redirect = $uri->getUri();

	$items = array_merge($items, array(
		array
		(
			"ID" => "groups",
			"TEXT" => GetMessage("SONET_UM_GROUPS"),
			"ON_CLICK" => "BX.SidePanel.Instance.open('".$arResult["Urls"]['groups']."', { width: 1000, loader: 'intranet:grouplist' })"
		)
	));
}

/* photogallery is not available in slider yet
if (
	is_array($arResult["CanView"])
	&& !!$arResult["CanView"]['photo']
	&& !IsModuleInstalled("bitrix24")
)
{
	$uri = new \Bitrix\Main\Web\Uri($arResult["Urls"]['photo']);
	$uri->addParams(array("IFRAME" => "Y"));
	$redirect = $uri->getUri();

	$items = array_merge($items, array(
		array
		(
			"ID" => "photo",
			"TEXT" => $arResult["Title"]['photo'],
			"ON_CLICK" => "BX.SidePanel.Instance.open('".$uri->getUri()."', {
				width: 1000 
			})",
			'IS_ACTIVE' => (mb_strpos($requestUri, $arResult["Urls"]['photo']) === 0)
		)
	));
}*/

$APPLICATION->IncludeComponent(
	"bitrix:main.interface.buttons",
	"",
	array(
		"ID" => "socialnetwork_profile_menu_user_".$arResult["User"]["ID"],
		"ITEMS" => $items,
		"DISABLE_SETTINGS" => !(
			$USER->isAuthorized()
			&& (
				$USER->getId() == $arResult["User"]["ID"]
				|| (\Bitrix\Main\Loader::includeModule('socialnetwork') && \CSocNetUser::isCurrentUserModuleAdmin())
			)
		)
	)
);
?>
<?$this->EndViewTarget();?>