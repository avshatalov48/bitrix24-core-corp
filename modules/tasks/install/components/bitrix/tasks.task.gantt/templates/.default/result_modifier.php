<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Tasks\Util;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\Main\Text\HtmlFilter;

\Bitrix\Main\UI\Extension::load([
	"ui.notification",
	"ui.avatar",
]);

//region TITLE
if ($arResult['IS_COLLAB'])
{
	$sTitle = $sTitleShort = GetMessage("TASKS_TITLE");
}
elseif ($arParams['PROJECT_VIEW'] === 'Y')
{
	$sTitle = $sTitleShort = GetMessage("TASKS_TITLE_PROJECT");
}
elseif($arParams['GROUP_ID'] > 0)
{
	$sTitle = $sTitleShort = GetMessage("TASKS_TITLE_GROUP_TASKS");
}
else
{
	if ($arParams[ "USER_ID" ] == Util\User::getId())
	{
		$sTitle = $sTitleShort = GetMessage("TASKS_TITLE_MY");
	}
	else
	{
		$sTitle = CUser::FormatName($arParams[ "NAME_TEMPLATE" ], $arResult[ "USER" ], true, false) . ": " . GetMessage("TASKS_TITLE");
		$sTitleShort = GetMessage("TASKS_TITLE");
	}
}
$APPLICATION->SetPageProperty("title", $sTitle);
$APPLICATION->SetTitle($sTitleShort);

if ($arResult['IS_COLLAB'])
{
	$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
	$APPLICATION->SetPageProperty(
		'BodyClass',
		"{$bodyClass} sn-collab-tasks__wrapper"
	);
	Toolbar::deleteFavoriteStar();

	$this->SetViewTarget('in_pagetitle') ?>
	<div class="sn-collab-icon__wrapper">
		<div id="sn-collab-icon-<?=HtmlFilter::encode($arResult["OWNER_ID"])?>" class="sn-collab-icon__hexagon-bg"></div>
	</div>
	<div class="sn-collab__subtitle"><?=HtmlFilter::encode($arResult["COLLAB_NAME"])?></div>
	<?php $this->EndViewTarget();
}
//endregion TITLE

$arResult["GROUPS"] = array();

$groupByProject =
	isset($arParams['VIEW_STATE']['SUBMODES']['VIEW_SUBMODE_WITH_GROUPS']['SELECTED']) &&
	$arParams['VIEW_STATE']['SUBMODES']['VIEW_SUBMODE_WITH_GROUPS']['SELECTED'] === "Y"
;

if ($groupByProject)
{
	$arOpenedProjects = CUserOptions::GetOption("tasks", "opened_projects", array());
	$arGroupsIDs = $arResult['GROUP_IDS'] ?? null;

	if (!empty($arGroupsIDs))
	{
		$rsGroups = CSocNetGroup::GetList(array("ID" => "ASC"), array("ID" => array_unique($arGroupsIDs)));
		while ($arGroup = $rsGroups->GetNext())
		{
			if (!empty($arGroup['NAME']))
			{
				$arGroup['NAME'] = \Bitrix\Main\Text\Emoji::decode($arGroup['NAME']);
			}
			if (!empty($arGroup['DESCRIPTION']))
			{
				$arGroup['DESCRIPTION'] = \Bitrix\Main\Text\Emoji::decode($arGroup['DESCRIPTION']);
			}
			$arGroup["EXPANDED"] = array_key_exists($arGroup["ID"], $arOpenedProjects) && $arOpenedProjects[$arGroup["ID"]] == "false" ? false : true;
			$arGroup["CAN_CREATE_TASKS"] = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, $arGroup["ID"], "tasks", "create_tasks");
			$arGroup["CAN_EDIT_TASKS"] = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, $arGroup["ID"], "tasks", "edit_tasks");
			$arResult["GROUPS"][$arGroup["ID"]] = $arGroup;
		}
	}
}

if (isset($arParams[ "SET_NAVCHAIN" ]) && $arParams[ "SET_NAVCHAIN" ] != "N")
{
	$APPLICATION->AddChainItem(GetMessage("TASKS_TITLE"));
}
/** END:TITLE */
if ($arResult['IS_COLLAB']): ?>
<script>
	BX.ready(() => {
		const collabImagePath = "<?=$arResult["COLLAB_IMAGE"]?>" || null;
		const collabName = "<?=HtmlFilter::encode($arResult["COLLAB_NAME"])?>";
		const ownerId = "<?=HtmlFilter::encode($arResult["OWNER_ID"])?>";
		const avatar = new BX.UI.AvatarHexagonGuest({
			size: 42,
			userName: collabName.toUpperCase(),
			baseColor: '#19CC45',
			userpicPath: collabImagePath,
		});
		avatar.renderTo(BX('sn-collab-icon-' + ownerId));
	});
</script>
<?php endif;
