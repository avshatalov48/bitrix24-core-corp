<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die(); 

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\Intranet\Settings;

Loc::loadMessages(__FILE__);

$templateId = $arResult['TEMPLATE_DATA']['ID'];
$templates = $arResult['TEMPLATE_DATA']['DATA']['TEMPLATES'];

CJSCore::Init('tasks_style_legacy');
if (!(new Settings())->isToolAvailable(Settings::TOOLS['templates']))
{
	return;
}
?>

<div id="bx-component-scope-<?=$templateId?>" class="task-template-selector">

	<?$hasButton = $arParams['TEMPLATE_DATA']['BUTTON_LABEL'] != '';?>

	<button data-bx-id="templateselector-open" class="ui-btn ui-btn-light-border ui-btn-themes ui-btn-dropdown" title="<?=Loc::getMessage('TASKS_TTDP_TEMPLATESELECTOR_CREATE_HINT')?>">
		<?if($hasButton):?>
			<?=htmlspecialcharsbx($arParams['TEMPLATE_DATA']['BUTTON_LABEL'])?>
		<?endif?>
	</button>

</div>

<script>
	new BX.Tasks.Component.TaskDetailPartsTemplateSelector(<?=CUtil::PhpToJSObject(array(
		'id' => $templateId,
		'menuItems' => $arResult['MENU_ITEMS'],
		'toTemplates' => CComponentEngine::MakePathFromTemplate($arParams['TEMPLATE_DATA']["PATH_TO_TASKS_TEMPLATES"], array()),
		'useSlider' => $arParams['TEMPLATE_DATA']['USE_SLIDER'] != 'N',
		'commonUrl' => $arResult['COMMON_URL'],
	), false, false, true)?>);
</script>