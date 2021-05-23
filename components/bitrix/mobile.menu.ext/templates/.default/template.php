<? use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}


/**
 * @var $USER CAllUser
 * @var $APPLICATION CAllMain
 * @var $userPerms CCrmPerms
 * @var $this CBitrixComponentTemplate
 */
if (!CModule::IncludeModule("mobileapp"))
{
	die();
}

$langMessages = \Bitrix\Main\Localization\Loc::loadLanguageFile(\Bitrix\Main\Application::getDocumentRoot().$this->GetFile());
$jsLangMessages = CUtil::PhpToJSObject($langMessages);
$langMessageJS = <<<JS
	(window.BX||top.BX).message($jsLangMessages);
JS;

\Bitrix\Main\Page\Asset::getInstance()->addString("<script type=\"text/javascript\">$langMessageJS</script>");

$APPLICATION->SetPageProperty("BodyClass", "menu-page");
$bExtranet = (CModule::IncludeModule("extranet") && CExtranet::IsExtranetSite());
$diskEnabled =
	\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) &&
	CModule::includeModule('disk');

$initData = array(
	"userId" => $GLOBALS['USER']->GetID(),
	"siteDir" => SITE_DIR,
	"canInvite" => (IsModuleInstalled("bitrix24") && $USER->CanDoOperation('bitrix24_invite')),
	"calendarFirstVisit" => (CUserOptions::GetOption("mobile", "calendar_first_visit", "Y") == "Y"),
	"profileUrl" => SITE_DIR . "mobile/users/?ID=" . $arResult["USER"]["ID"],
	"helpUrl" => SITE_DIR . "mobile/help/",
	"timemanUrl" => SITE_DIR . "mobile/timeman/",
	"marketPlaceApps" => $arResult["MARKETPLACE_MENU"]
);

$userColor = '#404f5d';

if (CModule::IncludeModule('im'))
{
	$userColor = Bitrix\Im\Color::getColorByNumber($initData['userId']);
	$arOnline = CIMStatus::GetList(Array('ID' => $initData['userId']));
	if (isset($arOnline['users'][$initData['userId']]['color']))
	{
		$userColor = $arOnline['users'][$initData['userId']]['color'];
	}
}
?>
<div class="menu-user" id="menu-user"
		style="background-color: <?= $userColor ?>;<? if ($arResult["USER"]["AVATAR"]): ?>background: url('<?= $arResult["USER"]["AVATAR"]["src"] ?>') no-repeat; background-size: cover; background-position: center;<? endif ?>">
	<div class="menu-user-info">
		<div class="menu-user-name"><?= $arResult["USER"]["FULL_NAME"] ?></div>
		<div class="menu-user-portal"><?= $arResult["HOST"] ?></div>
		<div class="menu-user-login"><?= $arResult["USER"]["LOGIN"] ?></div>
	</div>

	<? $showHelpIcon = CMobile::$platform == "ios" && !$bExtranet; ?>
	<div class="menu-user-actions<? if (!$showHelpIcon): ?> menu-user-actions-50<? endif ?>">
		<div class="menu-user-action menu-user-accounts" id="menu-user-accounts">
			<span><?= GetMessage("MB_MY_BITRIX24") ?></span>
		</div>
		<? if ($showHelpIcon): ?>
			<div class="menu-user-action menu-user-help" id="menu-user-help">
				<span><?= htmlspecialcharsbx(GetMessage('MB_HELP')) ?></span>
			</div>
		<? endif ?>
		<? if (!$bExtranet && IsModuleInstalled("timeman")): ?>
			<? $APPLICATION->IncludeComponent('bitrix:timeman', 'mobile', array(), $component, array("HIDE_ICONS" => "Y")) ?>
		<? endif ?>
		<div class="menu-user-action menu-user-logout" id="menu-user-logout">
			<span><?= htmlspecialcharsbx(GetMessage('MB_EXIT')) ?></span>
		</div>
	</div>
</div>
<div class="menu-items" id="menu-items">

	<? foreach ($arResult["MENU"] as $menuSection):
		if (count($menuSection["items"]) > 0):?>
			<div class="menu-separator"><?= $menuSection["name"]?></div>
			<div class="menu-section <?=$menuSection["css_style"]?>">
				<? foreach ($menuSection["items"] as $menuItem):
					if($menuItem["hidden"]) continue;?>
					<div class="menu-item" <? foreach ($menuItem["attrs"] as $attr=>$attrValue) echo $attr."=\"".$attrValue."\" " ;?>>
						<div class="menu-item-inner <?=$menuItem["css_class"]?>"><?= $menuItem["name"] ?></div>
						<?if ($menuItem["counter"]):?>
							<div class="menu-item-counter" id="<?=$menuItem["counter"]["id"]?>">
								<span class="menu-item-counter-value"></span>
								<span class="menu-item-counter-plus"></span>
							</div>
						<?endif?>
					</div>
				<? endforeach; ?>
			</div>
		<? endif; ?>
	<? endforeach; ?>

</div>

<script type="text/javascript">

	BX.ready(function ()
	{
		MobileMenu.MenuSettings.set(<?=CUtil::PhpToJsObject($initData)?>);
		MobileMenu.init(null);
	});

</script>
