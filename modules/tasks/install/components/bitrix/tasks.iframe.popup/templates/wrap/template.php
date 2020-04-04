<?
use \Bitrix\Main\Localization\Loc;

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

if($edit)
{
	$template = '.default';
	$parameters['SUB_ENTITY_SELECT'] = array(
		"TAG",
		"CHECKLIST",
		"REMINDER",
		"PROJECTDEPENDENCE",
		"TEMPLATE",
		"RELATEDTASK",
	);
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
		$parameters['TOOLBAR_PARAMETERS'] = array_merge($parameters['TOOLBAR_PARAMETERS'], array(
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
<?php if($arParams['HIDE_MENU_PANEL'] == 'Y'):
	$parameters['TOOLBAR_PARAMETERS'] = array(
		'SHOW_SECTIONS_BAR' => 'N',
		'SHOW_FILTER_BAR' => 'N',
		'SHOW_COUNTERS_BAR' => 'N',
	);
endif?>

<?if($isIFrame):?>

	<?
	// to stay inside iframe after form submit and also after clicking on "to task" links
	// for other links target="_top" is used
	$urlParameters = array('IFRAME' => 'Y');
	if($iFrameType != '')
	{
		$urlParameters['IFRAME_TYPE'] = $iFrameType;
	}

	$parameters['TASK_URL_PARAMETERS'] = $urlParameters;

	global $APPLICATION;
	$APPLICATION->RestartBuffer();
	?>

	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=LANGUAGE_ID?>" lang="<?=LANGUAGE_ID?>">
		<head>
			<script data-skip-moving="true">
				// Prevent loading page without header and footer
				if (window === window.top)
				{
					window.location = "<?=CUtil::JSEscape($APPLICATION->GetCurPageParam("", array("IFRAME", "IFRAME_TYPE"))); ?>" + window.location.hash;
				}
			</script>

			<?
			//The fastest way to close Slider Loader.
			Bitrix\Main\Page\Asset::getInstance()->setJsToBody(true);
			Bitrix\Main\Page\Asset::getInstance()->addString("
				<script>
				(function() {
					var template = '$template';
					var slider = top.BX && top.BX.SidePanel && top.BX.SidePanel.Instance.getSliderByWindow(window);
					if (slider)
					{
						slider.closeLoader();
						if (template === 'view' && slider.setPrintable)
						{
							slider.setPrintable(true);
						}
					}
				})();
				</script>
			", false, \Bitrix\Main\Page\AssetLocation::AFTER_CSS);
			?>

			<? $APPLICATION->ShowHead(); ?>
		</head>
		<body id="tasks-iframe-popup-scope" class="
			template-<?=SITE_TEMPLATE_ID?> <?$APPLICATION->ShowProperty("BodyClass");?> <?if($isSideSlider):?>task-iframe-popup-side-slider<?endif?>" onload="window.top.BX.onCustomEvent(window.top, 'tasksIframeLoad');" onunload="window.top.BX.onCustomEvent(window.top, 'tasksIframeUnload');">

			<?if($isSideSlider):?>
				<div class="tasks-iframe-header">
					<div class="pagetitle-wrap">
						<div class="pagetitle-inner-container">
							<div class="pagetitle-menu" id="pagetitle-menu"><?
								$APPLICATION->ShowViewContent("pagetitle")
								?></div>
							<div class="pagetitle">
								<span id="pagetitle" class="pagetitle-item"><?$APPLICATION->ShowTitle(false);?><?if($existingTask):?><span class="task-page-link-btn js-id-copy-page-url" title="<?=Loc::getMessage('TASKS_TIP_TEMPLATE_COPY_CURRENT_URL')?>"></span><?endif?></span>
							</div>
						</div>
					</div>
				</div>
				<?// side slider needs for an additional controller, but in case of standard iframe there is a controller already: tasks.iframe.popup default template?>
				<script>
					new BX.Tasks.Component.IframePopup.SideSlider({
						scope: BX('tasks-iframe-popup-scope')
					});
				</script>
			<?endif?>

			<div class="task-iframe-workarea <?if($isSideSlider):?>task-iframe-workarea-own-padding<?endif?>" id="tasks-content-outer">
				<?$APPLICATION->ShowViewContent("below_pagetitle");?>
				<div class="task-iframe-sidebar">
					<? $APPLICATION->ShowViewContent("sidebar"); ?>
				</div>
				<div class="task-iframe-content">
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

if($isIFrame):?>
				</div>
			</div>
		</body>
	</html><?
	require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
	die();?>

<?endif?>