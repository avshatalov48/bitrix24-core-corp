<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Crm\Conversion\LeadConversionScheme;
\Bitrix\Main\UI\Extension::load("ui.forms");

?>

<div id="crm_kanban_delete_confirm" class="crm-kanban-column-popup" <?
	?>data-title="<?= \htmlspecialcharsbx(Loc::getMessage('CRM_KANBAN_POPUP_CONFIRM'))?>" <?
	?>data-deletetitle="<?= \htmlspecialcharsbx(Loc::getMessage('CRM_KANBAN_POPUP_PARAMS_DELETE'))?>">
	<div class="crm-kanban-popup-wrapper">
		<?= Loc::getMessage('CRM_KANBAN_POPUP_CONFIRM_DELETE');?>
	</div>
</div>


<?if ($arParams['ENTITY_TYPE_CHR'] == 'INVOICE'):?>

<div id="crm_kanban_invoice_win" class="crm-kanban-column-popup" data-title="<?= htmlspecialcharsbx(Loc::getMessage('CRM_KANBAN_POPUP_INVOICE'))?>">
	<div class="crm-kanban-popup-wrapper">
		<table class="crm-kanban-popup-table">
			<tr>
				<td>
					<span class="crm-kanban-popup-text"><?= Loc::getMessage('CRM_KANBAN_POPUP_DATE')?></span>
				</td>
				<td>
					<input class="ui-ctl-element" data-field="date" data-default="<?= htmlspecialcharsbx($date)?>" onclick="BX.calendar({node: this, field: this});">
				</td>
			</tr>
			<tr>
				<td>
					<span class="crm-kanban-popup-text"><?= Loc::getMessage('CRM_KANBAN_POPUP_DOC_NUM')?></span>
				</td>
				<td>
					<input class="ui-ctl-element" data-field="docnum">
				</td>
			</tr>
			<tr>
				<td colspan="2" class="crm-kanban-popup-border">
					<span class="crm-kanban-popup-text"><?= Loc::getMessage('CRM_KANBAN_POPUP_COMMENT')?></span>
					<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
						<textarea class="ui-ctl-element" data-field="comment"></textarea>
					</div>
				</td>
			</tr>
		</table>
	</div>
</div>


<div id="crm_kanban_invoice_loose" class="crm-kanban-column-popup" data-title="<?= htmlspecialcharsbx(Loc::getMessage('CRM_KANBAN_POPUP_INVOICE'))?>">
	<div class="crm-kanban-popup-wrapper">
		<table class="crm-kanban-popup-table">
			<tr>
				<td>
					<span class="crm-kanban-popup-text"><?= Loc::getMessage('CRM_KANBAN_POPUP_DATE')?></span>
				</td>
				<td>
					<input class="crm-kanban-popup-input crm-kanban-popup-field" data-field="date" data-default="<?= htmlspecialcharsbx($date)?>" onclick="BX.calendar({node: this, field: this});">
				</td>
			</tr>
			<tr>
				<td colspan="2" class="crm-kanban-popup-border">
					<span class="crm-kanban-popup-text"><?= Loc::getMessage('CRM_KANBAN_POPUP_COMMENT')?></span>
					<textarea class="crm-kanban-popup-textarea crm-kanban-popup-field" data-field="comment"></textarea>
				</td>
			</tr>
		</table>
	</div>
</div>

<?elseif ($arParams['ENTITY_TYPE_CHR'] == 'LEAD'):?>

<div id="crm_kanban_lead_win" class="crm-kanban-column-popup" data-title="<?= htmlspecialcharsbx(Loc::getMessage('CRM_KANBAN_POPUP_LEAD'));?>">
	<div class="crm-kanban-popup-wrapper">
		<div class="crm-kanban-popup-convert-list">
			<?foreach (LeadConversionScheme::getJavaScriptDescriptions(true) as $code => $value):?>
			<div class="kanban-converttype" data-type="<?= strtolower($code);?>" onclick="BX.Crm.KanbanComponent.leadConvert('<?= \CUtil::JSEscape($code);?>');"><?= htmlspecialcharsbx($value);?></div>
			<?endforeach;?>
			<div class="kanban-converttype" data-type="select" onclick="BX.Crm.KanbanComponent.leadConvert('SELECT');"><?= Loc::getMessage('CRM_KANBAN_POPUP_LEAD_SELECT');?></div>
		</div>
	</div>
</div>

<?endif;?>