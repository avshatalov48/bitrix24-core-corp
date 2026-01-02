<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\UI\Buttons;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\UI\Toolbar\ButtonLocation;

$quickBtn = new Buttons\Button([
	'color' => Buttons\Color::LIGHT_BORDER,
	'icon' => Buttons\Icon::TASK,
]);
$quickBtn->addClass('ui-btn-themes webform-cogwheel tasks-quick-form-button ');
$quickBtn->addAttribute('id', 'task-quick-form-button');
$quickBtn->addAttribute('title', Loc::getMessage('TASKS_ADD_QUICK_TASK'));

Toolbar::addButton($quickBtn, ButtonLocation::RIGHT);
