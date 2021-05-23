<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

Extension::load([
	'ui.buttons',
	'ui.buttons.icons',
	'ui.hint',
]);

$APPLICATION->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");

Loc::loadMessages(__FILE__);

$this->SetViewTarget('pagetitle');

$section = $arResult['DATA']['SECTION'];

if ($section === 'TEMPLATES')
{
	$button = $arParams['ADD_BUTTON'];
	?>
	<a class="webform-small-button webform-small-button-blue webform-small-button-add sonet-groups-add-button" href="<?=HtmlFilter::encode($button['URL'])?>">
	    <span class="webform-small-button-icon"></span>
		<?=HtmlFilter::encode($button['NAME'])?>
	</a>
	<?php
}
elseif ($section === 'EDIT_TASK')
{
	?>
	<button id="taskEditPopupMenuOptions" class="ui-btn ui-btn-light-border ui-btn-themes ui-btn-icon-setting"></button>
	<?php
	$APPLICATION->IncludeComponent(
		"bitrix:tasks.task.detail.parts",
		"flat",
		array(
			"MODE" => "VIEW TASK",
			"BLOCKS" => array("templateselector"),
			"TEMPLATE_DATA" => array(
				"ID" => "templateselector",
				"DATA" => array(
					"TEMPLATES" => $arParams["TEMPLATES"],
				),
				"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
				"PATH_TO_TASKS_TEMPLATES" => $arParams["PATH_TO_TASKS_TEMPLATES"],
				"BUTTON_LABEL" => $arParams['TEMPLATES_TOOLBAR_LABEL'],
				"USE_SLIDER" => $arParams['TEMPLATES_TOOLBAR_USE_SLIDER']
			)
		),
		null,
		array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
	);
}
elseif ($section === 'VIEW_TASK')
{
	if (Loader::includeModule('rest'))
	{
		?>
		<div class="task-top-panel-restapp">
			<?php
			$restPlacementHandlerList = \Bitrix\Rest\PlacementTable::getHandlersList(\CTaskRestService::PLACEMENT_TASK_VIEW_TOP_PANEL);
			\CJSCore::Init('applayout');
			foreach ($restPlacementHandlerList as $app):?>
				<div class="task-top-panel-restapp-<?=$app['APP_ID']?>">
					<a href="#" onclick="BX.rest.AppLayout.openApplication(<?=$app['APP_ID']?>, {TASK_ID: '<?=$arResult['ENTITY_ID']?>'}, {PLACEMENT: '<?=\CTaskRestService::PLACEMENT_TASK_VIEW_TOP_PANEL?>', PLACEMENT_ID: '<?=$app['ID']?>'});">
						<?=(trim($app['TITLE']) ? $app['TITLE'] : $app['APP_NAME'])?>
					</a>
				</div>
			<?php endforeach?>
		</div>
		<?php
	}
	$button = $arParams['ADD_BUTTON'];
	$mutedClass = ($arResult['DATA']['MUTED'] ? 'unfollow' : 'follow');
	$mutedHint = $arResult['DATA']['MUTED']
		? Loc::getMessage('TASKS_INTERFACE_FILTER_BUTTONS_MUTE_BUTTON_HINT_UNMUTE')
		: Loc::getMessage('TASKS_INTERFACE_FILTER_BUTTONS_MUTE_BUTTON_HINT_MUTE');
	?>
	<button id="taskViewMute" class="ui-btn ui-btn-light-border ui-btn-themes ui-btn-icon-<?=$mutedClass?>"
			data-hint="<?= $mutedHint ?>" data-hint-no-icon></button>
	<button id="taskViewPopupMenuOptions" class="ui-btn ui-btn-light-border ui-btn-themes ui-btn-icon-setting"></button>
	<span class="ui-btn-split ui-btn-primary">
		<a class="ui-btn-main" href="<?=HtmlFilter::encode($button['URL'])?>" id="<?=HtmlFilter::encode($button['ID'])?>-btn">
			<?=HtmlFilter::encode($button['NAME'])?>
		</a>
		<span class="ui-btn-extra" id="<?=HtmlFilter::encode($button['ID'])?>"></span>
	</span>
	<?php
}?>

<?php $this->EndViewTarget(); ?>

<script type="text/javascript">
	BX.ready(function() {
		BX.message({
			"POPUP_MENU_CHECKLIST_SECTION": '<?= GetMessageJs('TASKS_INTERFACE_FILTER_BUTTONS_POPUP_MENU_CHECKLIST_SECTION') ?>',
			"POPUP_MENU_SHOW_COMPLETED": '<?= GetMessageJS('TASKS_INTERFACE_FILTER_BUTTONS_POPUP_MENU_SHOW_COMPLETED') ?>',
			"POPUP_MENU_SHOW_ONLY_MINE": '<?= GetMessageJs('TASKS_INTERFACE_FILTER_BUTTONS_POPUP_MENU_SHOW_ONLY_MINE') ?>',
			"MUTE_BUTTON_HINT_MUTE": '<?= GetMessageJS('TASKS_INTERFACE_FILTER_BUTTONS_MUTE_BUTTON_HINT_MUTE') ?>',
			"MUTE_BUTTON_HINT_UNMUTE": '<?= GetMessageJS('TASKS_INTERFACE_FILTER_BUTTONS_MUTE_BUTTON_HINT_UNMUTE') ?>'
		});

		new BX.Tasks.InterfaceFilterButtons(<?= Json::encode([
			'section' => $section,
			'entityId' => $arResult['ENTITY_ID'],
			'muted' => $arResult['DATA']['MUTED'],
			'checklistShowCompleted' => $arResult['DATA']['CHECKLIST_OPTION_SHOW_COMPLETED'],
		]) ?>);

		BX.UI.Hint.init();
	});
</script>
