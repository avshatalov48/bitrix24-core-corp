<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\UI;

UI\Extension::load("ui.tooltip");

global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm-preview.css');
?>

<div class="crm-preview">
	<div class="crm-preview-header">
		<span class="crm-preview-header-icon crm-preview-header-icon-deal"></span>
		<span class="crm-preview-header-title">
			<?=GetMessage("CRM_TITLE_DEAL")?>:
			<a href="<?=htmlspecialcharsbx($arParams['URL'])?>" target="_blank"><?=htmlspecialcharsbx($arResult['TITLE'])?></a>
		</span>
	</div>


	<table class="crm-preview-info">
		<tr>
			<td><?= GetMessage('CRM_FIELD_ASSIGNED_BY')?>: </td>
			<td>
				<a id="a_<?=htmlspecialcharsbx($arResult['ASSIGNED_BY_UNIQID'])?>" href="<?=htmlspecialcharsbx($arResult["ASSIGNED_BY_PROFILE"])?>" bx-tooltip-user-id="<?=htmlspecialcharsbx($arResult["ASSIGNED_BY_ID"])?>">
					<?=htmlspecialcharsbx($arResult['ASSIGNED_BY_FORMATTED_NAME'])?>
				</a>
			</td>
		</tr>
		<tr>
			<td><?= GetMessage('CRM_FIELD_STAGE')?>: </td>
			<td><?=htmlspecialcharsbx($arResult['STAGE_TEXT'])?></td>
		</tr>
		<tr><td colspan="4"><div class="crm-preview-info-spacer"></div></td></tr>
		<tr>
			<td><?= GetMessage('CRM_FIELD_TYPE')?>: </td>
			<td><?=htmlspecialcharsbx($arResult['TYPE_TEXT'])?></td>
		</tr>
		<tr>
			<td><?= GetMessage('CRM_FIELD_OPPORTUNITY')?>: </td>
			<td><?=htmlspecialcharsbx($arResult['FORMATTED_SUM'])?></td>
		</tr>
	</table>
</div>