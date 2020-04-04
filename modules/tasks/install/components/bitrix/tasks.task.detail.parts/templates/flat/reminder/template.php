<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die(); 

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$templateId = $arResult['TEMPLATE_DATA']['ID'];
$prefix = htmlspecialcharsbx($arResult['TEMPLATE_DATA']['INPUT_PREFIX']);
?>

<div id="bx-component-scope-<?=htmlspecialcharsbx($templateId)?>">

	<div data-bx-id="reminder-items">

        <script type="text/html" data-bx-id="reminder-item">

            <span data-bx-id="reminder-item" data-item-value="{{VALUE}}" class="task-reminder-container {{ITEM_SET_INVISIBLE}}">
                <span data-bx-id="item-info" class="task-reminder-info {{TRANSPORT_CLASS}}">
                    <span data-bx-id="item-edit" class="task-reminder-info-text">{{REMINDER_TEXT}}</span><span data-bx-id="reminder-item-delete" class="task-reminder-inp-del" title="<?=Loc::getMessage('TASKS_TTDP_TEMPLATE_REMINDER_DELETE')?>"></span>
                </span>

				<input data-bx-id="item-transport" type="hidden" name="<?=$prefix?>[{{VALUE}}][TRANSPORT]" value="{{TRANSPORT}}" />
				<input data-bx-id="item-recipient-type" type="hidden" name="<?=$prefix?>[{{VALUE}}][RECEPIENT_TYPE]" value="{{RECEPIENT_TYPE}}" />
                <input data-bx-id="item-remind-date" type="hidden" name="<?=$prefix?>[{{VALUE}}][REMIND_DATE]" value="{{REMIND_DATE}}" />
                <input data-bx-id="item-type" type="hidden" name="<?=$prefix?>[{{VALUE}}][TYPE]" value="{{TYPE}}" />
            </span>

        </script>

	</div>

    <?if($arResult['TEMPLATE_DATA']['ENABLE_ADD_BUTTON']):?>

        <div class="task-dashed-link">
            + <span data-bx-id="reminder-open-form" class="task-dashed-link-inner"><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_REMINDER_ADD')?></span>
            <span class="task-field-label"><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_REMINDER_ADD_REMARK')?></span>
        </div>

    <?endif?>

    <?//popup content?>
    <div class="hidden">
        <span data-bx-id="reminder-form" class="task-reminder-popup-container type-a transport-j">

            <label class="task-field-popup-label"><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_REMINDER_REMIND_BY')?>:</label>
            <span class="task-reminder-type-changer">
                <span class="task-dashed-link"><a data-bx-id="form-change-type" class="task-dashed-link-inner" href="javascript:void(0)">
                    <span class="type-a-control" title="<?=Loc::getMessage('TASKS_TTDP_TEMPLATE_REMINDER_TYPE_A_EX')?>"><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_REMINDER_TYPE_A')?></span>
                    <span class="type-d-control" title="<?=Loc::getMessage('TASKS_TTDP_TEMPLATE_REMINDER_TYPE_D_EX')?>"><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_REMINDER_TYPE_D')?></span>
                </a></span>
            </span>
            <span class="task-field-popup-label task-reminder-type-fixed">
                <span title="<?=Loc::getMessage('TASKS_TTDP_TEMPLATE_REMINDER_NO_DEADLINE')?>"><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_REMINDER_TYPE_A')?></span>
            </span>

            <div class="task-popup-field">

                <span class="type-a-control">
                    <span data-bx-id="form-date" class="task-popup-inp-container task-reminder-date">
                        <input data-bx-id="datepicker-display" type="text" class="task-popup-inp" readonly="readonly">
                        <span data-bx-id="datepicker-clear" class="task-option-inp-del"></span>
                        <input data-bx-id="datepicker-value" type="hidden" value="" />
                    </span>
                </span>

                <span class="type-d-control">
                    <span class="task-popup-inp-container-num">
                        <input  data-bx-id="form-time-multiplier" value="1" type="text" class="task-popup-inp" />
                    </span>
                    <span class="task-popup-inp-container task-options-inp-container-unit">
                        <select data-bx-id="form-time-unit" class="task-popup-inp">
                            <option value="D"><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_REMINDER_DAYS')?></option>
                            <option value="H"><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_REMINDER_HOURS')?></option>
                        </select>
                    </span>
                </span>

                <span class="task-popup-inp-container task-options-inp-container-period">
                    <select data-bx-id="form-change-recipient" class="task-popup-inp">
                        <option value="R"><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_REMINDER_TO_RESPONSIBLE')?></option>
                        <option value="O"><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_REMINDER_TO_CREATOR')?></option>
                        <option value="S"><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_REMINDER_TO_SELF')?></option>
                    </select>
                </span>
                <span class="task-popup-inp-container task-popup-inp-container-buttons">
                    <a data-bx-id="form-change-transport" data-transport="<?=CTaskReminders::REMINDER_TRANSPORT_JABBER?>" href="javascript:void(0)" class="task-options-reminder-link-mes" title="<?=Loc::getMessage('TASKS_TTDP_TEMPLATE_REMINDER_TRANSPORT_J_EX')?>"></a><a data-bx-id="form-change-transport" data-transport="<?=CTaskReminders::REMINDER_TRANSPORT_EMAIL?>" href="javascript:void(0)" class="task-options-reminder-link-mail" title="<?=Loc::getMessage('TASKS_TTDP_TEMPLATE_REMINDER_TRANSPORT_E_EX')?>"></a>
                </span>

            </div>
            <button data-bx-id="form-submit" class="webform-small-button webform-small-button-blue">
                <span class="webform-small-button-text">
                    <div class="task-popup-label-add"><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_REMINDER_ADD_ALT')?></div>
                    <div class="task-popup-label-update"><?=Loc::getMessage('TASKS_TTDP_TEMPLATE_REMINDER_UPDATE')?></div>
                </span>
            </button>
        </span>
    </div>

	<?// in case of all items removed, the field should be sent anyway?>
	<input type="hidden" name="<?=$prefix?>[]" value="">

</div>

<script>
	new BX.Tasks.Component.TaskDetailPartsReminder(<?=CUtil::PhpToJSObject(array(
		'id' => $templateId,
		'registerDispatcher' => true,
		'auxData' => array( // currently no more, no less
			'COMPANY_WORKTIME' => array(
				'HOURS' => $arResult['TEMPLATE_DATA']['COMPANY_WORKTIME']['HOURS']
			)
		),
        'data' => $arResult['TEMPLATE_DATA']['ITEMS']['DATA'],
        'can' => $arResult['TEMPLATE_DATA']['ITEMS']['CAN'],
        'taskId' => $arResult['TEMPLATE_DATA']['TASK_ID'],
        'taskDeadline' => $arResult['TEMPLATE_DATA']['TASK_DEADLINE'],
        'autoSync' => !!$arResult['TEMPLATE_DATA']['AUTO_SYNC'],
        'itemFx' => $arResult['TEMPLATE_DATA']['ITEM_FX'] == 'horizontal' ? 'horizontal' : 'vertical',
        'itemFxHoverDelete' => true
	), false, false, true)?>);
</script>