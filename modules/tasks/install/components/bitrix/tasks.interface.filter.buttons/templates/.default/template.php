<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

Extension::load([
	'ui.buttons',
	'ui.buttons.icons',
]);

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
	$APPLICATION->IncludeComponent(
		'bitrix:ui.feedback.form',
		'',
		$arResult['DATA']['FEEDBACK_FORM_PARAMETERS']
	);
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
	$button = $arParams['ADD_BUTTON'];

	$APPLICATION->IncludeComponent(
		'bitrix:ui.feedback.form',
		'',
		$arResult['DATA']['FEEDBACK_FORM_PARAMETERS']
	);
	?>

	<?php
	// получить список встроенных приложений
	if(\Bitrix\Main\Loader::includeModule('rest'))
	{
		?><div class="task-top-panel-restapp"><?php
		$restPlacementHandlerList = \Bitrix\Rest\PlacementTable::getHandlersList(\CTaskRestService::PLACEMENT_TASK_VIEW_TOP_PANEL);

		\CJSCore::Init('applayout');
		foreach($restPlacementHandlerList as $app):?>
			<div class="task-top-panel-restapp-<?=$app['APP_ID']?>">
				<a href="javascript:;" onclick="BX.rest.AppLayout.openApplication(<?=$app['APP_ID']?>, {},{PLACEMENT: '<?=\CTaskRestService::PLACEMENT_TASK_VIEW_TOP_PANEL?>',PLACEMENT_ID:  '<?=$app['ID']?>'});">
					<?=trim($app['TITLE']) ? $app['TITLE'] : $app['APP_NAME']?>
				</a>
			</div>
		<?php endforeach;
		?></div><?php
	}

	?>
	<button id="taskViewPopupMenuOptions" class="ui-btn ui-btn-light-border ui-btn-themes ui-btn-icon-setting"></button>
	<span class="ui-btn-double ui-btn-primary">
		<a class="ui-btn-main" href="<?=HtmlFilter::encode($button['URL'])?>" id="<?=HtmlFilter::encode($button['ID'])?>-btn">
			<?=HtmlFilter::encode($button['NAME'])?>
		</a>
		<span class="ui-btn-extra" id="<?=HtmlFilter::encode($button['ID'])?>"></span>
	</span>
	<?php
}?>

<?php $this->EndViewTarget(); ?>

<script type="text/javascript">
	BX.ready(function()
	{
		BX.message({
			"POPUP_MENU_CHECKLIST_SECTION": '<?=GetMessageJs('TASKS_INTERFACE_FILTER_BUTTONS_POPUP_MENU_CHECKLIST_SECTION')?>',
			"POPUP_MENU_SHOW_COMPLETED": '<?=GetMessageJS('TASKS_INTERFACE_FILTER_BUTTONS_POPUP_MENU_SHOW_COMPLETED')?>',
			"POPUP_MENU_SHOW_ONLY_MINE": '<?=GetMessageJs('TASKS_INTERFACE_FILTER_BUTTONS_POPUP_MENU_SHOW_ONLY_MINE')?>'
		});

		new BX.Tasks.InterfaceFilterButtons(<?=Json::encode([
			'section' => $arParams['SECTION'],
			'checklistShowCompleted' => $arResult['CHECKLIST_OPTION_SHOW_COMPLETED'],
		])?>);
	});
</script>
