<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
$isBitrix24Template = SITE_TEMPLATE_ID === "bitrix24";

\Bitrix\Main\Loader::includeModule('ui');
\Bitrix\Main\UI\Extension::load(["ui.entity-selector","ui.buttons", "ui.buttons.icons", "popup"]);

$APPLICATION->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");

if ($isBitrix24Template)
{
	$this->SetViewTarget('in_pagetitle');

	if (array_key_exists('PROJECT_VIEW', $arParams) && $arParams['PROJECT_VIEW'] === 'Y')
	{
		include(dirname(__FILE__).'/project_selector.php');
	}

	$this->EndViewTarget();
}

if ($isBitrix24Template)
{
	$this->SetViewTarget('inside_pagetitle');
}

if (isset($arParams['FILTER']) && is_array($arParams['FILTER']))
{
	include(dirname(__FILE__) . '/filter_selector.php');
}
?>

<? if (!$isBitrix24Template): ?>
	<div class="tasks-interface-filter-container">
<? endif ?>

<?php
if ($arParams['MENU_GROUP_ID'] == 0 || $arParams['SHOW_CREATE_TASK_BUTTON'] != 'N')
{
	include(dirname(__FILE__) . '/create_button.php');
}

include(dirname(__FILE__).'/filter.php');

if ($arParams['USE_GROUP_SELECTOR'] == 'Y' && $arParams['PROJECT_VIEW'] !== 'Y')
{
	include(dirname(__FILE__).'/group_selector.php');
}

if ($arResult['SPRINT'])
{
	include(dirname(__FILE__).'/sprint_selector.php');
}
?>

<div class="pagetitle-container pagetitle-align-right-container">

	<?php
	if ($arParams['SHOW_USER_SORT'] == 'Y' ||
			  $arParams['USE_GROUP_BY_SUBTASKS'] == 'Y' ||
			  $arParams['USE_GROUP_BY_GROUPS'] == 'Y' ||
			  $arParams['USE_EXPORT'] == 'Y' ||
			  !empty($arParams['POPUP_MENU_ITEMS'])
	)
	{
		include(dirname(__FILE__).'/popup_menu.php');
	}
	if ($arParams["SHOW_QUICK_FORM_BUTTON"] != "N")
	{
		include(dirname(__FILE__).'/quick_form.php');
	}
	?>

</div>

<? if (!$isBitrix24Template): ?>
	</div>
<? endif ?>

<?php
if ($isBitrix24Template)
{
	$this->EndViewTarget();
}
?>

<?php CJSCore::Init("spotlight"); ?>