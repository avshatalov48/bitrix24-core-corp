<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\UI\Buttons;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\UI\Buttons\JsCode;

Extension::load([
	'ui.buttons',
	'ui.buttons.icons',
	'ui.hint',
	'tasks.deadline.menu',
]);

$APPLICATION->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");

Loc::loadMessages(__FILE__);

$section = $arResult['DATA']['SECTION'];

if ($section === 'TEMPLATES')
{
	$button = $arParams['ADD_BUTTON'];

	$addButton = new Buttons\Button([
		'tag' => Buttons\Tag::LINK,
		'color' => Buttons\Color::PRIMARY,
		'text' => HtmlFilter::encode($button['NAME']),
		'icon' => Buttons\Icon::ADD,
	]);
	$addButton->addAttribute('href', HtmlFilter::encode($button['URL']));
	Toolbar::addButton($addButton);
}
elseif ($section === 'EDIT_TASK')
{
	$taskEditPopupMenuOptions = new Buttons\Button([
		'color' => Buttons\Color::LIGHT_BORDER,
		'icon' => Buttons\Icon::SETTING,
	]);
	$taskEditPopupMenuOptions->addAttribute('id', 'taskEditPopupMenuOptions');
	Toolbar::addButton($taskEditPopupMenuOptions);

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
		$restPlacementHandlerList = \Bitrix\Rest\PlacementTable::getHandlersList(\CTaskRestService::PLACEMENT_TASK_VIEW_TOP_PANEL);
		\CJSCore::Init('applayout');
		foreach ($restPlacementHandlerList as $app)
		{
			$appBtn = new Buttons\Button([
				'text' => (trim($app['TITLE']) ? $app['TITLE'] : $app['APP_NAME']),
				'click' => new JsCode('BX.rest.AppLayout.openApplication(
                    '.$app['APP_ID'].',
                    {
                        TASK_ID: "'.$arResult['ENTITY_ID'].'"
                    },
                    {
                        PLACEMENT: "'.\CTaskRestService::PLACEMENT_TASK_VIEW_TOP_PANEL.'",
                        PLACEMENT_ID: "'.$app['ID'].'"
                    }
                );'),
			]);

			$appBtn->addClass("task-top-panel-restapp-{$app['APP_ID']}");
			$appBtn->setMaxWidth('150px');
			Toolbar::addButton($appBtn);
		}
	}
	$button = $arParams['ADD_BUTTON'];
	$mutedClass = $arResult['DATA']['MUTED'] ? Buttons\Icon::UNFOLLOW : Buttons\Icon::FOLLOW;
	$mutedHint = $arResult['DATA']['MUTED']
		? Loc::getMessage('TASKS_INTERFACE_FILTER_BUTTONS_MUTE_BUTTON_HINT_UNMUTE')
		: Loc::getMessage('TASKS_INTERFACE_FILTER_BUTTONS_MUTE_BUTTON_HINT_MUTE');

	$taskViewBtn = new Buttons\Button([
		'color' => Buttons\Color::LIGHT_BORDER,
		'icon' => $mutedClass,
	]);
	$taskViewBtn->addAttribute('id', 'taskViewMute');
	$taskViewBtn->addAttribute('data-hint', $mutedHint);
	$taskViewBtn->addAttribute('data-hint-no-icon');
	Toolbar::addButton($taskViewBtn);

	$taskViewPopupMenuOptions = new Buttons\Button([
		'color' => Buttons\Color::LIGHT_BORDER,
		'icon' => Buttons\Icon::SETTING,
	]);
	$taskViewPopupMenuOptions->addAttribute('id', 'taskViewPopupMenuOptions');
	Toolbar::addButton($taskViewPopupMenuOptions);

	$splitButton = new Buttons\Split\AddButton([
		'color' => Buttons\Color::PRIMARY,
		'mainButton' => [
			'text' => HtmlFilter::encode($button['NAME']),
			'tag' => Buttons\Tag::LINK,
			'link' => HtmlFilter::encode($button['URL']),
		],
	]);
	$splitButton->getMainButton()->addAttribute('id', (HtmlFilter::encode($button['ID']) . '-btn'));
	$splitButton->getMenuButton()->addAttribute('id', HtmlFilter::encode($button['ID']));
	Toolbar::addButton($splitButton);
}?>

<script>
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
