<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\UI;

UI\Extension::load("ui.tooltip");

global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm-preview.css');
?>

<div class="crm-preview">
	<div class="crm-preview-header">
		<span class="crm-preview-header-icon crm-preview-header-icon-quote"></span>
		<span class="crm-preview-header-title">
			<?=GetMessage("CRM_TITLE_QUOTE")?>:
			<a href="<?=htmlspecialcharsbx($arParams['URL'])?>" target="_blank"><?=htmlspecialcharsbx($arResult['TITLE'])?></a>
		</span>
	</div>
	<table class="crm-preview-info">
		<tr>
			<td><?= GetMessage('CRM_FIELD_ASSIGNED_BY')?>: </td>
			<td>
				<a id="a_<?=htmlspecialcharsbx($arResult['ASSIGNED_BY_UNIQID'])?>" href="<?=htmlspecialcharsbx($arResult["ASSIGNED_BY_PROFILE"])?>" target="_blank" bx-tooltip-user-id="<?=htmlspecialcharsbx($arResult["ASSIGNED_BY_ID"])?>">
					<?=htmlspecialcharsbx($arResult['ASSIGNED_BY_FORMATTED_NAME'])?>
				</a>
			</td>
			<td><?= GetMessage('CRM_FIELD_STATUS')?>: </td>
			<td><?=htmlspecialcharsbx($arResult['STATUS_TEXT'])?></td>
		</tr>
		<tr>
			<td><?= GetMessage('CRM_FIELD_BEGINDATE')?>: </td>
			<td><?=($arResult["BEGINDATE"] <> '' ? FormatDateFromDB($arResult["BEGINDATE"], "SHORT") : GetMessage("CRM_NO_DATE"))?></td>
			<td><?= GetMessage('CRM_FIELD_CLOSEDATE')?>: </td>
			<td><?=($arResult["CLOSEDATE"] <> '' ? FormatDateFromDB($arResult["CLOSEDATE"], "SHORT") : GetMessage("CRM_NO_DATE"))?></td>
		</tr>
		<tr><td colspan="4"><div class="crm-preview-info-spacer"></div></td></tr>
		<tr>
			<td>
				<span <?=($arResult['LEAD_ID'] == 0 ? 'style="display:none;"' : '' )?>>
					<?= GetMessage('CRM_FIELD_LEAD')?>:
				</span>
			</td>
			<td>
				<span <?=($arResult['LEAD_ID'] == 0 ? 'style="display:none;"' : '' )?>>
					<a href="<?=htmlspecialcharsbx($arResult['LEAD_PROFILE'])?>" target="_blank"><?=htmlspecialcharsbx($arResult['LEAD_TITLE'])?></a>
				</span>
			</td>
			<td>
				<span <?=($arResult['DEAL_ID'] == 0 ? 'style="display:none;"' : '' )?>>
					<?= GetMessage('CRM_FIELD_DEAL')?>:
				</span>
			</td>
			<td>
				<span <?=($arResult['DEAL_ID'] == 0 ? 'style="display:none;"' : '' )?>>
					<a href="<?=htmlspecialcharsbx($arResult['DEAL_PROFILE'])?>" target="_blank">
						<?=htmlspecialcharsbx($arResult['DEAL_TITLE'])?>
					</a>
				</span>
			</td>
		</tr>
		<tr>
			<td><?= GetMessage('CRM_FIELD_OPPORTUNITY')?>: </td>
			<td><?=htmlspecialcharsbx($arResult['FORMATTED_SUM'])?></td>
		</tr>
	</table>
</div>
