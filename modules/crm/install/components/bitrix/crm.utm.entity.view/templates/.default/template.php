<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;


?>
<?if(count($arResult['ITEMS']) == 0):?>
	<?=Loc::getMessage('CRM_UTM_VIEW_NOT_FOUND')?>
<?else:?>
	<table>
		<?foreach ($arResult['ITEMS'] as $item):?>
		<tr>
			<td style="opacity: 0.7;"><?=htmlspecialcharsbx($item['NAME'])?>:</td>
			<td><?=htmlspecialcharsbx($item['VALUE'])?></td>
		</tr>
		<?endforeach;?>
	</table>
<?endif;?>
<?if (false):?>
<div class="crm-analytics-entity-field">
	<div class="crm-analytics-entity-field-inner">
		<div class="crm-analytics-entity-field-row">
			<div class="crm-analytics-entity-field-item crm-analytics-entity-field-item-source">
				<span class="crm-analytics-entity-field-title">Source</span>
				<span class="crm-analytics-entity-field-value crm-analytics-entity-field-value-google">Google Ads</span>
			</div>
			<div class="crm-analytics-entity-field-item">
				<span class="crm-analytics-entity-field-title">Campaign</span>
				<span class="crm-analytics-entity-field-value crm-analytics-entity-field-value-google">Pay per click(CPC)</span>
			</div>
		</div>
		<div class="crm-analytics-entity-field-row">
			<div class="crm-analytics-entity-field-item">
				<span class="crm-analytics-entity-field-title">Keywords</span>
				<span class="crm-analytics-entity-field-value">cakes</span>
			</div>
		</div>
	</div>
</div>
<?endif;?>