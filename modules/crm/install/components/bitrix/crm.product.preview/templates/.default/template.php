<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm-preview.css');
?>

<div class="crm-preview">
	<div class="crm-preview-header">
		<span class="crm-preview-header-icon"></span>
		<span class="crm-preview-header-title">
			<?=GetMessage("CRM_TITLE_PRODUCT")?>:
			<a href="<?=htmlspecialcharsbx($arParams['URL'])?>" target="_blank"><?=htmlspecialcharsbx($arResult['NAME'])?></a>
		</span>
	</div>
	<table class="crm-preview-info">
		<tr>
			<td><?= GetMessage('CRM_FIELD_SECTION')?>: </td>
			<td><?=htmlspecialcharsbx($arResult['SECTION_NAME'])?></td>
		</tr>
		<tr>
			<td><?= GetMessage('CRM_FIELD_PRICE')?>: </td>
			<td><?=htmlspecialcharsbx($arResult['PRICE_FORMATTED'])?></td>
		</tr>
		<tr>
			<td><?= GetMessage('CRM_FIELD_ACTIVE')?>: </td>
			<td><?=htmlspecialcharsbx($arResult['ACTIVE_FORMATTED'])?></td>
		</tr>
	</table>
</div>