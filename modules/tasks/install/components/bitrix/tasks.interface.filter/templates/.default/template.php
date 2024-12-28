<?php

use Bitrix\Main\Localization\Loc;
// use Bitrix\Tasks\Integration\Socialnetwork\Context\Context;
use Bitrix\Main\UI\Extension;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
$isBitrix24Template = SITE_TEMPLATE_ID === "bitrix24";

\Bitrix\Main\Loader::includeModule('ui');
Extension::load([
	"ui.entity-selector",
	"ui.buttons",
	"ui.buttons.icons",
	"popup",
	"ui.fonts.opensans",
	"ui.dialogs.checkbox-list",
	'ui.tour',
	'ui.design-tokens',
]);

$APPLICATION->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");
// $isCollab = isset($arParams['CONTEXT']) && $arParams['CONTEXT'] === Context::getCollab();

if ($isBitrix24Template)
{
	$this->SetViewTarget('in_pagetitle');

	if (array_key_exists('PROJECT_VIEW', $arParams) && $arParams['PROJECT_VIEW'] === 'Y')
	{
		include(__DIR__.'/project_selector.php');
	}

	$this->EndViewTarget();
}

if ($isBitrix24Template)
{
	$this->SetViewTarget('inside_pagetitle');
}

if (isset($arParams['FILTER']) && is_array($arParams['FILTER']))
{
	include(__DIR__ . '/filter_selector.php');
}
?>

<? if (!$isBitrix24Template): ?>
	<div class="tasks-interface-filter-container">
<? endif ?>

<?php
if ((int)$arParams['MENU_GROUP_ID'] === 0 || $arParams['SHOW_CREATE_TASK_BUTTON'] !== 'N')
{
	include(__DIR__ . '/create_button.php');
}

include(__DIR__.'/filter.php');

if ($arParams['USE_GROUP_SELECTOR'] === 'Y' && $arParams['PROJECT_VIEW'] !== 'Y')
{
	include(__DIR__.'/group_selector.php');
}

if ($arResult['SPRINT'])
{
	include(__DIR__.'/sprint_selector.php');
}

$contentClass = 'pagetitle-container pagetitle-align-right-container ';
?>

<div class="<?=$contentClass?>">
	<?php
	// if ($isCollab)
	// {
	// 	include(__DIR__.'/reports.php');
	// }

	if ($arParams['SHOW_USER_SORT'] === 'Y' ||
			  $arParams['USE_GROUP_BY_SUBTASKS'] === 'Y' ||
			  $arParams['USE_GROUP_BY_GROUPS'] === 'Y' ||
			  $arParams['USE_EXPORT'] == 'Y' ||
			  !empty($arParams['POPUP_MENU_ITEMS'])
	)
	{
		include(__DIR__.'/popup_menu.php');
	}
	if ($arParams["SHOW_QUICK_FORM_BUTTON"] !== "N")
	{
		include(__DIR__.'/quick_form.php');
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

<?php CJSCore::Init("spotlight");
if ($arResult['showPresetTourGuide'])
{
?>
<script>
	BX.ready(() => {
		BX.message({
			TASKS_INTERFACE_FILTER_PRESETS_MOVED_TITLE: '<?= Loc::getMessage('TASKS_INTERFACE_FILTER_PRESETS_MOVED_TITLE') ?>',
			TASKS_INTERFACE_FILTER_PRESETS_MOVED_TEXT: '<?= Loc::getMessage('TASKS_INTERFACE_FILTER_PRESETS_MOVED_TEXT_V2') ?>',
		});

		BX.Tasks.Preset.Aha = new BX.Tasks.Preset({
			filterId: '<?= $arParams["FILTER_ID"] ?>'
		})
		BX.Tasks.Preset.Aha.payAttention();
	})
</script>
<?php
}

