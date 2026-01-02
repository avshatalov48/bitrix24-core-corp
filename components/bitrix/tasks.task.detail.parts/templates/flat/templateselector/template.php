<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var array $arParams */
/** @var array $arResult */

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\V2\Internal\DI\Container;use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\UI\Buttons;

Loc::loadMessages(__FILE__);

$templateId = $arResult['TEMPLATE_DATA']['ID'];
$templates = $arResult['TEMPLATE_DATA']['DATA']['TEMPLATES'];

CJSCore::Init('tasks_style_legacy');
if (!Container::getInstance()->getToolService()->isTemplatesAvailable())
{
	return;
}

$templateSelectorText = $arParams['TEMPLATE_DATA']['BUTTON_LABEL'];
$templateBtn = new Buttons\Button([
	'color' => Buttons\Color::LIGHT_BORDER,
	'text' => $templateSelectorText,
]);

$templateBtn->addAttribute('data-bx-id', 'templateselector-open');
$templateBtn->addAttribute('id', 'templateselector-open');
$templateBtn->addAttribute('title', Loc::getMessage('TASKS_TTDP_TEMPLATESELECTOR_CREATE_HINT'));
$templateBtn->setDropdown(true);

Toolbar::addButton($templateBtn);
?>

<script>
	new BX.Tasks.Component.TaskDetailPartsTemplateSelector(<?=CUtil::PhpToJSObject(array(
		'id' => $templateId,
		'menuItems' => $arResult['MENU_ITEMS'],
		'toTemplates' => CComponentEngine::MakePathFromTemplate($arParams['TEMPLATE_DATA']["PATH_TO_TASKS_TEMPLATES"], array()),
		'useSlider' => $arParams['TEMPLATE_DATA']['USE_SLIDER'] != 'N',
		'commonUrl' => $arResult['COMMON_URL'],
	), false, false, true)?>);
</script>
