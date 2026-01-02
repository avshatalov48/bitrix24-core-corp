<?php
use \Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\UI\Buttons;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loc::loadMessages(__FILE__);

/**
 * This template is used as wrapper for tasks.task: edit & view, to enable some extra logic when displayed inside iframe
 */

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var TasksBaseComponent $component */

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest()->toArray();

Toolbar::deleteFavoriteStar();
Toolbar::enableMultiLineTitle();

$parameters = array();
if(is_array($arParams['FORM_PARAMETERS']))
{
	$parameters = $arParams['FORM_PARAMETERS'];
}

$edit = $arParams['ACTION'] == 'edit';
$existingTask = intval($parameters['ID']) > 0;
$isIFrame = $arResult['IFRAME'] == 'Y';
$iFrameType = $arResult['IFRAME_TYPE'];
$isSideSlider = $iFrameType == 'SIDE_SLIDER';

$isFlowEnabled = FlowFeature::isOn();

if($edit)
{
	$APPLICATION->SetPageProperty('BodyClass', 'task-iframe-content');

	$template = '.default';
	$parameters['SUB_ENTITY_SELECT'] = array(
		"TAG",
		"CHECKLIST",
		"REMINDER",
		"PROJECTDEPENDENCE",
		"TEMPLATE",
		"RELATEDTASK",
	);
	$parameters['ACTION'] = 'edit';
}
else
{
	$template = 'view';
	$parameters['SUB_ENTITY_SELECT'] = array(
		"TAG",
		"CHECKLIST",
		"REMINDER",
		"PROJECTDEPENDENCE",
        "TEMPLATE",
		"TEMPLATE.SOURCE",
		"LOG",
		"ELAPSEDTIME",
		"DAYPLAN"
	);
	$parameters['ACTION'] = 'view';
}

$parameters['AUX_DATA_SELECT'] = array(
	"COMPANY_WORKTIME",
	"USER_FIELDS"
);
if($isIFrame)
{
	// turn off some controls
	//$parameters['ENABLE_CANCEL_BUTTON'] = 'N';
	$parameters['ENABLE_FOOTER_UNPIN'] = 'N';
	$parameters['ENABLE_MENU_TOOLBAR'] = 'N';

	$parameters['REDIRECT_ON_SUCCESS'] = 'Y';
	$parameters['CANCEL_ACTION_IS_EVENT'] = true; // fire global event "NOOP" when "Cancel" button pressed

	// no redirect to list on delete, we will close popup manually
	$parameters['REDIRECT_TO_LIST_ON_DELETE'] = 'N';


	if($isSideSlider)
	{
		$parameters['ENABLE_MENU_TOOLBAR'] = 'Y';
		$parameters['TOOLBAR_PARAMETERS'] = array(
			'SHOW_SECTIONS_BAR' => 'N',
			'SHOW_FILTER_BAR' => 'N',
			'SHOW_COUNTERS_BAR' => 'N',
			'TEMPLATES_TOOLBAR_USE_SLIDER' => 'N',
		);
	}

	if($edit)
	{
		$parameters['TOOLBAR_PARAMETERS'] = array_merge($parameters['TOOLBAR_PARAMETERS'] ?? [], array(
			'CUSTOM_ELEMENTS' => array(),
			'TEMPLATES_TOOLBAR_LABEL' => Loc::getMessage('TASKS_TIP_TEMPLATE_LINK_COPIED_TEMPLATE_BAR_TITLE'),
		));
	}

	$parameters['SHOW_COPY_URL_LINK'] = 'N';
}
else
{
	if($edit && !$existingTask)
	{
		$parameters['TOOLBAR_PARAMETERS'] = array(
			'TEMPLATES_TOOLBAR_USE_SLIDER' => 'N', // do not open slider if we are at "new task" page
		);
	}
}

?>
<?php if(
		isset($arParams['HIDE_MENU_PANEL'])
		&& $arParams['HIDE_MENU_PANEL'] === 'Y'
	):
	$parameters['TOOLBAR_PARAMETERS'] = array(
		'SHOW_SECTIONS_BAR' => 'N',
		'SHOW_FILTER_BAR' => 'N',
		'SHOW_COUNTERS_BAR' => 'N',
	);
endif?>

<?if($isIFrame):?>

	<?php

	$urlParameters = array('IFRAME' => 'Y');
	if($iFrameType != '')
	{
		$urlParameters['IFRAME_TYPE'] = $iFrameType;
	}

	$parameters['TASK_URL_PARAMETERS'] = $urlParameters;
	?>

	<?if($isSideSlider):?>
		<?php
			if ($isFlowEnabled && !$arResult['isExtranet'])
			{
				$selectorBtn = new Buttons\Button([
					'size' => !defined('AIR_SITE_TEMPLATE') ? Buttons\Size::EXTRA_SMALL : null,
					'style' => Buttons\Style::ROUND,
					'round' => true,
					'color' => Buttons\Color::LINK,
				]);
				$selectorBtn->setNoCaps(true);
				$selectorBtn->addAttribute('id', 'tasks-flow-selector-container');
				Toolbar::addButton($selectorBtn);
			}

			if($existingTask)
			{
				Toolbar::setCopyLinkButton();
			}
		?>
		<script>
			new BX.Tasks.Component.IframePopup.SideSlider({
				scope: BX('ui-page-slider-content')
			});
		</script>
	<?endif?>
<?
endif;

if(\Bitrix\Tasks\Util\Restriction::canManageTask())
{

	$APPLICATION->IncludeComponent(
		"bitrix:tasks.task",
		$template,
		$parameters,
		$component,
		array("HIDE_ICONS" => "Y")
	);
}
else
{
	$APPLICATION->IncludeComponent("bitrix:bitrix24.business.tools.info", "", array(
		"SHOW_TITLE" => "Y"
	));
}
