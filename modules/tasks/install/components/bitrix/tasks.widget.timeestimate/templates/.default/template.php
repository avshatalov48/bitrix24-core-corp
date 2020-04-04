<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$helper = $arResult['HELPER'];
?>

<?$helper->displayFatals();?>
<?if(!$helper->checkHasFatals()):?>

	<div id="<?=$helper->getScopeId()?>" class="tasks">

		<?$helper->displayWarnings();?>
		
		<?$data = $arParams['ENTITY_DATA'];?>
		<?$inputPrefix = htmlspecialcharsbx($arParams['INPUT_PREFIX']);?>

		<?$checked = $data['ALLOW_TIME_TRACKING'] == 'Y';?>
		<label class="task-field-label task-field-label-tm"><input class="js-id-timeestimate-flag task-options-checkbox" data-target="allow-time-tracking" data-flag-name="ALLOW_TIME_TRACKING" <?=($checked? 'checked' : '')?> type="checkbox"><?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_TIME_TO_DO')?></label>
		<input class="js-id-timeestimate-allow-time-tracking" type="hidden" name="<?=$inputPrefix?>[ALLOW_TIME_TRACKING]" value="<?=($checked ? 'Y' : 'N')?>" />
		<span class="js-id-timeestimate-inputs task-options-inp-container-time task-openable-block<?if(!$checked):?> invisible<?endif?>">
            <span class="task-options-inp-container">
                <input type="text" class="js-id-timeestimate-time js-id-timeestimate-hour task-options-inp" value="<?=($arResult['HOURS'] ? $arResult['HOURS'] : '')?>" />
            </span>
			<?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_OF_HOURS')?>
			<span class="task-options-inp-container">
                <input type="text" class="js-id-timeestimate-time js-id-timeestimate-minute task-options-inp" value="<?=($arResult['MINUTES'] ? $arResult['MINUTES'] : '')?>" />
            </span>
			<?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_OF_MINUTES')?>
			<input class="js-id-timeestimate-second" type="hidden" name="<?=$inputPrefix?>[TIME_ESTIMATE]" value="<?=intval($data['TIME_ESTIMATE'])?>" />
        </span>

	</div>

	<?$helper->initializeExtension();?>

<?endif?>