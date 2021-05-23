<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$templateId = $arResult['TEMPLATE_DATA']['ID'];
?>

<span id="bx-component-scope-<?=$templateId?>" class="task-form-field t-empty">

    <span class="js-id-tdp-mem-sel-items">
	    <script type="text/html" data-bx-id="tdp-mem-sel-item">
		    <?ob_start();?>
	            <span class="js-id-tdp-mem-sel-item task-form-field-item {{ITEM_SET_INVISIBLE}}" data-item-value="{{VALUE}}" data-bx-type="{{TYPE_SET}}">
					<a class="task-form-field-item-text" href="{{URL}}" target="_blank" class="task-options-destination-text">
						{{DISPLAY}}
					</a>
					<span class="js-id-tdp-mem-sel-item-delete task-form-field-item-delete" title="<?=Loc::getMessage('TASKS_COMMON_CANCEL_SELECT')?>"></span>

		            <?// being usually embedded into a form, this control can produce some inputs ?>
		            <?foreach($arParams['INPUT_TEMPLATE_SET'] as $field => $template):?>
			            <input type="hidden" name="<?=htmlspecialcharsbx($template)?>" value="{{<?=htmlspecialcharsbx($field)?>}}" />
		            <?endforeach?>

				</span>
		    <?$template = ob_get_clean();?>
		    <?=$template?>
        </script>
	</span>

    <span class="task-form-field-controls">
        <span class="task-form-field-loading"><?=Loc::getMessage('TASKS_COMMON_LOADING')?>...</span>
        <input class="js-id-tdp-mem-sel-search js-id-network-selector-search task-form-field-search task-form-field-input" type="text" value="" autocomplete="off" />
        <a href="javascript:void(0);" class="js-id-tdp-mem-sel-open-form task-form-field-add-item feed-add-destination-link">
	        <span class="task-form-field-when-filled"><?=Loc::getMessage('TASKS_COMMON_ADD_MORE')?></span>
	        <span class="task-form-field-when-empty"><?=Loc::getMessage('TASKS_COMMON_ADD')?></span>
        </a>
    </span>

</span>

<script>
	new BX.Tasks.Component.TaskDetailPartsMemberSelector(<?=\Bitrix\Tasks\UI::toJSON(array(
		'id' => $templateId,
		'path' => $arResult['PATH'],
		'data' => $arParams['DATA'],
	))?>);
</script>