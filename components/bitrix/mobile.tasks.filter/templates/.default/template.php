<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(is_array($arResult['ERRORS']['FATAL']) && !empty($arResult['ERRORS']['FATAL']))
{
	foreach($arResult['ERRORS']['FATAL'] as $error);
	{
		ShowError($error);
	}
	return;
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$GLOBALS['APPLICATION']->SetPageProperty('BodyClass', 'task-filter-page');

$GLOBALS['APPLICATION']->addHeadScript(SITE_TEMPLATE_PATH.'/tasks/logic.js');
$GLOBALS['APPLICATION']->addHeadScript($templateFolder.'/logic.js');

$GLOBALS['APPLICATION']->addHeadScript(SITE_TEMPLATE_PATH.'/tasks/style.css');

// bug: due to some strange third-party reasons, style.css can not be displayed, so a little hack here
$GLOBALS['APPLICATION']->ShowCSS();

// enabling application cache
$frame = \Bitrix\Main\Page\Frame::getInstance();
$frame->setEnable();
$frame->setUseAppCache();
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("page", "tasks.filter");
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("MobileAPIVersion", CMobile::getApiVersion());
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("MobilePlatform", CMobile::getPlatform());
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("version", "v1.2");
\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("LanguageId", LANGUAGE_ID);
?>

<div id="tasks-filter">
	<div class="task-title"><?=GetMessage('MB_TASKS_TASK_FILTER_TITLE')?></div>
	<div class="task-filter-block" data-bx-id="filter-filters">

		<?if(is_array($arResult['PRESETS_TREE'])):?>
			<?foreach($arResult['PRESETS_TREE'] as $preset):?>

				<div 
					class="task-filter-row"
					data-bx-id="filter-variant"
					data-filter="<?=htmlspecialcharsbx($preset['FILTER'])?>"
				>
					<?=str_repeat('&nbsp;', intval($preset['DEPTH_LEVEL']) * 6)?><?=htmlspecialcharsbx($preset['Name'])?>
				</div>

			<?endforeach?>
		<?endif?>

		<script type="text/html" data-bx-template-id="filter-custom-preset">
			<div 
				class="task-filter-row"
				data-bx-id="filter-variant"
				data-filter="{{filter}}"
			>
				{{name}}
			</div>
		</script>

	</div>
</div>

<script>
	BX.message(<?=CUtil::PhpToJSObject(array(
		'PAGE_TITLE' => 				Loc::getMessage('MB_TASKS_GENERAL_TITLE'),
		'MB_TASKS_PULLDOWN_PULL' => 	Loc::getMessage('MB_TASKS_TASKS_FILTER_PULLDOWN_PULL'),
		'MB_TASKS_PULLDOWN_DOWN' => 	Loc::getMessage('MB_TASKS_TASKS_FILTER_PULLDOWN_DOWN'),
		'MB_TASKS_PULLDOWN_LOADING' => 	Loc::getMessage('MB_TASKS_TASKS_FILTER_PULLDOWN_LOADING')
	))?>);
</script>

<?
$frame->startDynamicWithID("mobile-tasks-filter");
?>
	<script>
		new BX.Mobile.Tasks.filter(<?=CUtil::PhpToJSObject(array(
			'scope' => 'tasks-filter',
			'userId' => intval($arResult['USER_ID']),
			'chosenFilter' => $arResult['CURRENT_PRESET_ID'],
			'customFilters' => $arResult['CUSTOM_PRESETS']
		))?>);
	</script>
<?
$frame->finishDynamicWithID("mobile-tasks-filter", $stub = "", $containerId = null, $useBrowserStorage = true);