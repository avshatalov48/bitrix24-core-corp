<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
CUtil::InitJSCore(array('ajax', 'popup'));

global $APPLICATION;
$APPLICATION->AddHeadScript('/bitrix/js/crm/crm.js');
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');

if($arResult['REPORT_OWNER_ID'] === ''):
$APPLICATION->SetAdditionalCSS('/bitrix/js/report/css/report.css');
?><form method="POST" name="reportOwnerForm" id="reportOwnerForm" action="<?=CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_REPORT_CONSTRUCT"], array("report_id" => $arResult['REPORT_ID'], 'action' => $arResult['ACTION']));?>">
<?= bitrix_sessid_post('csrf_token')?>
<div class="reports-constructor">
	<div class="webform-main-fields">
		<div class="webform-corners-top">
			<div class="webform-left-corner"></div>
			<div class="webform-right-corner"></div>
		</div>
		<div class="webform-content">
			<div class="reports-title-label"><?=htmlspecialcharsbx(GetMessage('CRM_REPORT_SELECT_OWNER'))?></div>
			<select id="report-helper-selector" name="reportOwnerID" class="filter-dropdown" style="min-width: 250px;"><?
				$ownerInfos = CCrmReportManager::getOwnerInfos();
				foreach ($ownerInfos as &$ownerInfo) :
					?><option value="<?=htmlspecialcharsbx($ownerInfo['ID'])?>"><?= htmlspecialcharsbx($ownerInfo['TITLE']) ?></option><?
				endforeach;
				unset($ownerInfo);?>
			</select>
		</div>
	</div>
</div>
<div class="webform-buttons task-buttons">
	<a class="webform-button webform-button-create" id="reportOwnerSelectButton">
		<span class="webform-button-left"></span>
		<span class="webform-button-text"><?= htmlspecialcharsbx(GetMessage('CRM_REPORT_CONSTRUCT_BUTTON_CONTINUE'))?></span>
		<span class="webform-button-right"></span>
	</a>
	<a class="webform-button-link webform-button-link-cancel" href="<?=htmlspecialcharsbx($arParams['PATH_TO_REPORT_REPORT'])?>"><?= htmlspecialcharsbx(GetMessage('CRM_REPORT_CONSTRUCT_BUTTON_CANCEL')) ?></a>
</div>
</form>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.bind(
				BX('reportOwnerSelectButton'),
				'click',
				function (e)
				{
					BX.PreventDefault(e);
					BX('reportOwnerForm').submit();
				}
			);
		}
	);
</script><?
return;
else:
?><script type="text/javascript">
	BX.ready(
		function()
		{
			var form = BX('task-filter-form');
			if(!form)
			{
				return;
			}

			form.appendChild(
				BX.create(
					'INPUT',
					{
						'attrs':
						{
							'type': 'hidden',
							'name': 'reportOwnerID',
							'value': '<?= CUtil::JSEscape($arResult['REPORT_OWNER_ID']) ?>'
						}
					}
				)
			);

			var popupTitle = '<?=CUtil::JSEscape(htmlspecialcharsbx(GetMessage('REPORT_POPUP_COLUMN_TITLE_'.strtoupper($arResult['REPORT_OWNER_ID']))))?>';

			BX.findChild(
					BX('reports-add_col-popup-cont'),
					{ 'className': 'reports-add_col-popup-title' }).innerHTML = popupTitle;

			BX.findChild(
					BX('reports-add_filcol-popup-cont'),
					{ 'className': 'reports-add_col-popup-title' }).innerHTML = popupTitle;
		}
	);
</script>
<?
endif;

?><style>
.report-filter-compare-User {display: none;}
.report-filter-compare-Group {display: none;}
.report-filter-compare-COMPANY_BY {display: none;}
.report-filter-compare-CONTACT_BY {display: none;}
.report-filter-compare-LEAD_BY {display: none;}
.report-filter-compare-DEAL-OWNER {display: none;}
</style>

<!-- filter value control -->
<div id="report-filter-value-control-examples-custom" style="display: none">
	<span name="report-filter-value-control-CATEGORY_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option><?
			$categories = \Bitrix\Crm\Category\DealCategory::getSelectListItems();
			foreach($categories as $key => $val):
				?><option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option><?
			endforeach;
		?></select>
	</span>
	<span name="report-filter-value-control-STAGE_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option><?
			$categoryGroups = \Bitrix\Crm\Category\DealCategory::getStageGroupInfos();
			foreach($categoryGroups as $group):
				$groupName = isset($group['name']) ? $group['name'] : '';
				if($groupName !== ''):?><optgroup label="<?=htmlspecialcharsbx($groupName)?>"><?endif;
				$groupItems = isset($group['items']) && is_array($group['items'])
					? $group['items'] : array();
				foreach($groupItems as $key => $val):
					?><option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option><?
				endforeach;
				if($groupName !== ''):?></optgroup><?endif;
			endforeach;
		?></select>
	</span>
	<span name="report-filter-value-control-STATUS_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<?
			if ($arResult['REPORT_OWNER_ID'] === 'crm_invoice')
				$arResult['enumValues']['STATUS_ID'] = CCrmStatus::GetStatusList('INVOICE_STATUS');
			else
				$arResult['enumValues']['STATUS_ID'] = CCrmStatus::GetStatusList('STATUS');
			?>
			<? foreach($arResult['enumValues']['STATUS_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>
	<?
	if ($arResult['REPORT_OWNER_ID'] === 'crm_invoice'):
	?><span name="report-filter-value-control-PERSON_TYPE_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['PERSON_TYPE_ID'] = CCrmReportHelperBase::getInvoicePersonTypeList();
			foreach($arResult['enumValues']['PERSON_TYPE_ID'] as $key => $val):
				?>          <option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>
	<span name="report-filter-value-control-PAY_SYSTEM_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['PAY_SYSTEM_ID'] = CCrmReportHelperBase::getInvoicePaySystemList();
			foreach($arResult['enumValues']['PAY_SYSTEM_ID'] as $key => $val):
				?>          <option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span><?
	endif;
	?>
	<span name="report-filter-value-control-SOURCE_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['SOURCE_ID'] = CCrmStatus::GetStatusList('SOURCE'); ?>
			<? foreach($arResult['enumValues']['SOURCE_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>
		<span name="report-filter-value-control-DEAL_OWNER.STAGE_ID" class="report-filter-vcc">
			<select class="reports-filter-select-small" name="value">
				<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option><?
				$categoryGroups = \Bitrix\Crm\Category\DealCategory::getStageGroupInfos();
				foreach($categoryGroups as $group):
					$groupName = isset($group['name']) ? $group['name'] : '';
					if($groupName !== ''):?><optgroup label="<?=htmlspecialcharsbx($groupName)?>"><?endif;
					$groupItems = isset($group['items']) && is_array($group['items'])
						? $group['items'] : array();
					foreach($groupItems as $key => $val):
						?><option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option><?
					endforeach;
					if($groupName !== ''):?></optgroup><?endif;
				endforeach;
			?></select>
		</span>

	<span name="report-filter-value-control-TYPE_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<?
			if ($arResult['REPORT_OWNER_ID'] === 'crm_activity')
				$arResult['enumValues']['TYPE_ID'] = CCrmActivityType::PrepareFilterItems();
			else
				$arResult['enumValues']['TYPE_ID'] = CCrmStatus::GetStatusList('DEAL_TYPE');
			?>
			<? foreach($arResult['enumValues']['TYPE_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>
	<span name="report-filter-value-control-DEAL_OWNER.TYPE_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['TYPE_ID'] = CCrmStatus::GetStatusList('DEAL_TYPE'); ?>
			<? foreach($arResult['enumValues']['TYPE_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-DIRECTION" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<?
			$directions = CCrmActivityDirection::GetAllDescriptions(0);
			unset($directions[CCrmActivityDirection::Undefined]);
			$arResult['enumValues']['DIRECTION'] = $directions;
			unset($directions);
			?>
			<? foreach($arResult['enumValues']['DIRECTION'] as $key => $val): ?>
				<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-PRIORITY" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['DIRECTION'] = CCrmActivityPriority::PrepareFilterItems(); ?>
			<? foreach($arResult['enumValues']['DIRECTION'] as $key => $val): ?>
				<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-CURRENCY_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['CURRENCY_ID'] = CCrmCurrencyHelper::PrepareListItems(); ?>
			<? foreach($arResult['enumValues']['CURRENCY_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-DEAL_OWNER.CURRENCY_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['CURRENCY_ID'] = CCrmCurrencyHelper::PrepareListItems(); ?>
			<? foreach($arResult['enumValues']['CURRENCY_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-LEAD_BY.CURRENCY_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['LEAD_BY.CURRENCY_ID'] = CCrmCurrencyHelper::PrepareListItems(); ?>
			<? foreach($arResult['enumValues']['LEAD_BY.CURRENCY_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-LEAD_BY.STATUS_BY.STATUS_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['LEAD_BY.STATUS_BY.STATUS_ID'] = CCrmStatus::GetStatusList('STATUS'); ?>
			<? foreach($arResult['enumValues']['LEAD_BY.STATUS_BY.STATUS_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-LEAD_BY.SOURCE_BY.STATUS_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['LEAD_BY.SOURCE_BY.STATUS_ID'] = CCrmStatus::GetStatusList('SOURCE'); ?>
			<? foreach($arResult['enumValues']['LEAD_BY.SOURCE_BY.STATUS_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-EVENT_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['EVENT_ID'] = CCrmStatus::GetStatusList('EVENT_TYPE'); ?>
			<? foreach($arResult['enumValues']['EVENT_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-DEAL_OWNER.EVENT_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['EVENT_ID'] = CCrmStatus::GetStatusList('EVENT_TYPE'); ?>
			<? foreach($arResult['enumValues']['EVENT_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-COMPANY_BY" callback="crmCompanySelector">
		<a href="" class="report-select-popup-link" caller="true"><?=GetMessage('REPORT_CHOOSE')?></a>
		<input type="hidden" name="value" />
	</span>

	<span name="report-filter-value-control-DEAL_OWNER.COMPANY_BY" callback="crmCompanySelector">
		<a href="" class="report-select-popup-link" caller="true"><?=GetMessage('REPORT_CHOOSE')?></a>
		<input type="hidden" name="value" />
	</span>

	<span name="report-filter-value-control-INVOICE_UTS.COMPANY_BY" callback="crmCompanySelector">
		<a href="" class="report-select-popup-link" caller="true"><?=GetMessage('REPORT_CHOOSE')?></a>
		<input type="hidden" name="value" />
	</span>

	<span name="report-filter-value-control-CONTACT_BY" callback="crmContactSelector">
		<a href="" class="report-select-popup-link" caller="true"><?=GetMessage('REPORT_CHOOSE')?></a>
		<input type="hidden" name="value" />
	</span>

	<span name="report-filter-value-control-DEAL_OWNER.CONTACT_BY" callback="crmContactSelector">
		<a href="" class="report-select-popup-link" caller="true"><?=GetMessage('REPORT_CHOOSE')?></a>
		<input type="hidden" name="value" />
	</span>

	<span name="report-filter-value-control-INVOICE_UTS.CONTACT_BY" callback="crmContactSelector">
		<a href="" class="report-select-popup-link" caller="true"><?=GetMessage('REPORT_CHOOSE')?></a>
		<input type="hidden" name="value" />
	</span>

	<span name="report-filter-value-control-LEAD_BY" callback="crmLeadSelector">
		<a href="" class="report-select-popup-link" caller="true"><?=GetMessage('REPORT_CHOOSE')?></a>
		<input type="hidden" name="value" />
	</span>

	<span name="report-filter-value-control-COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID'] = CCrmStatus::GetStatusList('COMPANY_TYPE'); ?>
			<? foreach($arResult['enumValues']['COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-DEAL_OWNER.COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID'] = CCrmStatus::GetStatusList('COMPANY_TYPE'); ?>
			<? foreach($arResult['enumValues']['COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-COMPANY_BY.INDUSTRY_BY.STATUS_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['COMPANY_BY.INDUSTRY_BY.STATUS_ID'] = CCrmStatus::GetStatusList('INDUSTRY'); ?>
			<? foreach($arResult['enumValues']['COMPANY_BY.INDUSTRY_BY.STATUS_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-DEAL_OWNER.COMPANY_BY.INDUSTRY_BY.STATUS_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['COMPANY_BY.INDUSTRY_BY.STATUS_ID'] = CCrmStatus::GetStatusList('INDUSTRY'); ?>
			<? foreach($arResult['enumValues']['COMPANY_BY.INDUSTRY_BY.STATUS_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-COMPANY_BY.EMPLOYEES_BY.STATUS_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['COMPANY_BY.EMPLOYEES_BY.STATUS_ID'] = CCrmStatus::GetStatusList('EMPLOYEES'); ?>
			<? foreach($arResult['enumValues']['COMPANY_BY.EMPLOYEES_BY.STATUS_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-DEAL_OWNER.COMPANY_BY.EMPLOYEES_BY.STATUS_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['COMPANY_BY.EMPLOYEES_BY.STATUS_ID'] = CCrmStatus::GetStatusList('EMPLOYEES'); ?>
			<? foreach($arResult['enumValues']['COMPANY_BY.EMPLOYEES_BY.STATUS_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-CONTACT_BY.TYPE_BY.STATUS_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['CONTACT_BY.TYPE_BY.STATUS_ID'] = CCrmStatus::GetStatusList('CONTACT_TYPE'); ?>
			<? foreach($arResult['enumValues']['CONTACT_BY.TYPE_BY.STATUS_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-DEAL_OWNER.CONTACT_BY.TYPE_BY.STATUS_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['CONTACT_BY.TYPE_BY.STATUS_ID'] = CCrmStatus::GetStatusList('CONTACT_TYPE'); ?>
			<? foreach($arResult['enumValues']['CONTACT_BY.TYPE_BY.STATUS_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-CONTACT_BY.SOURCE_BY.STATUS_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['CONTACT_BY.SOURCE_BY.STATUS_ID'] = CCrmStatus::GetStatusList('SOURCE'); ?>
			<? foreach($arResult['enumValues']['CONTACT_BY.SOURCE_BY.STATUS_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-DEAL_OWNER.CONTACT_BY.SOURCE_BY.STATUS_ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['CONTACT_BY.SOURCE_BY.STATUS_ID'] = CCrmStatus::GetStatusList('SOURCE'); ?>
			<? foreach($arResult['enumValues']['CONTACT_BY.SOURCE_BY.STATUS_ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-ORIGINATOR_BY.ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['ORIGINATOR_BY.ID'] = CCrmExternalSaleHelper::PrepareListItems() ?>
			<? foreach($arResult['enumValues']['ORIGINATOR_BY.ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-DEAL_OWNER.ORIGINATOR_BY.ID" class="report-filter-vcc">
		<select class="reports-filter-select-small" name="value">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arResult['enumValues']['ORIGINATOR_BY.ID'] = CCrmExternalSaleHelper::PrepareListItems() ?>
			<? foreach($arResult['enumValues']['ORIGINATOR_BY.ID'] as $key => $val): ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<? endforeach; ?>
		</select>
	</span>

	<span name="report-filter-value-control-DEAL_OWNER" callback="crmDealSelector">
		<a href="" class="report-select-popup-link" caller="true"><?=GetMessage('REPORT_CHOOSE')?></a>
		<input type="hidden" name="value" />
	</span>

	<span name="report-filter-value-control-INVOICE_UTS.DEAL_BY" callback="crmDealSelector">
		<a href="" class="report-select-popup-link" caller="true"><?=GetMessage('REPORT_CHOOSE')?></a>
		<input type="hidden" name="value" />
	</span>
	<?
	//CCrmCompany
	$arCompanyTypeList = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
	$arCompanyIndustryList = CCrmStatus::GetStatusListEx('INDUSTRY');
	$obRes = CCrmCompany::GetListEx(
		array('ID' => 'DESC'),
		array(),
		false,
		array('nTopCount' => 50),
		array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY',  'LOGO')
	);
	$arFiles = array();
	$arCompanies = array();
	while ($arRes = $obRes->Fetch())
	{
		if (!empty($arRes['LOGO']) && !isset($arFiles[$arRes['LOGO']]))
		{
			if ($arFile = CFile::GetFileArray($arRes['LOGO']))
			{
				$arFiles[$arRes['LOGO']] = CHTTP::URN2URI($arFile['SRC']);
			}
		}

		$arRes['SID'] = $arRes['ID'];

		$arDesc = Array();
		if (isset($arCompanyTypeList[$arRes['COMPANY_TYPE']]))
			$arDesc[] = $arCompanyTypeList[$arRes['COMPANY_TYPE']];
		if (isset($arCompanyIndustryList[$arRes['INDUSTRY']]))
			$arDesc[] = $arCompanyIndustryList[$arRes['INDUSTRY']];

		$arCompanies[] = array(
			'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
			'desc' => implode(', ', $arDesc),
			'id' => $arRes['SID'],
			'url' => CComponentEngine::MakePathFromTemplate(
				COption::GetOptionString('crm', 'path_to_company_show'),
				array('company_id' => $arRes['ID'])
			),
			'image' => isset($arFiles[$arRes['LOGO']]) ? $arFiles[$arRes['LOGO']] : '',
			'type'  => 'company',
			'selected' => false
		);
	}

	//CrmContact
	$obRes = CCrmContact::GetListEx(
		array('LAST_NAME' => 'ASC', 'NAME' => 'ASC'),
		array(),
		false,
		array('nTopCount' => 50),
		array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO')
	);
	$arContacts = array();
	while ($arRes = $obRes->Fetch())
	{
		if (!empty($arRes['PHOTO']) && !isset($arFiles[$arRes['PHOTO']]))
		{
			if ($arFile = CFile::GetFileArray($arRes['PHOTO']))
			{
				$arFiles[$arRes['PHOTO']] = CHTTP::URN2URI($arFile['SRC']);
			}
		}

		$arContacts[] =
			array(
				'id' => $arRes['ID'],
				'url' => CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_contact_show'),
					array('contact_id' => $arRes['ID'])
				),
				'title' => (str_replace(array(';', ','), ' ', CCrmContact::PrepareFormattedName($arRes))),
				'desc' => empty($arRes['COMPANY_TITLE'])? '': $arRes['COMPANY_TITLE'],
				'image' => isset($arFiles[$arRes['PHOTO']])? $arFiles[$arRes['PHOTO']] : '',
				'type' => 'contact',
				'selected' => false
			);
	}

	//CrmLead
	$arLeads = array();
	$obRes = CCrmLead::GetListEx(
		array('TITLE' => 'ASC'),
		array(),
		false,
		array('nTopCount' => 50),
		array('ID', 'TITLE', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'STATUS_ID')
	);
	while ($arRes = $obRes->Fetch())
	{
		$arLeads[] =
			array(
				'id' => $arRes['ID'],
				'url' => CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_lead_show'),
					array('lead_id' => $arRes['ID'])
				),
				'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
				'desc' => CCrmLead::PrepareFormattedName($arRes),
				'type' => 'lead',
				'selected' => false
			)
		;
	}

	//CrmDeal
	$arDeals = array();
	$obRes = CCrmDeal::GetListEx(
		array('TITLE' => 'ASC'),
		array(),
		false,
		array('nTopCount' => 50),
		array('ID', 'TITLE')
	);
	while ($arRes = $obRes->Fetch())
	{
		$arDeals[] =
			array(
				'id' => $arRes['ID'],
				'url' => CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_deal_show'),
					array('deal_id' => $arRes['ID'])
				),
				'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
				'desc' => '',
				'type' => 'deal',
				'selected' => false
			)
		;
	}
	?>

	<script type="text/javascript">
		var crmCompanyElements = <? echo CUtil::PhpToJsObject($arCompanies); ?>;
		var crmContactElements = <? echo CUtil::PhpToJsObject($arContacts); ?>;
		var crmLeadElements = <? echo CUtil::PhpToJsObject($arLeads); ?>;
		var crmDealElements = <? echo CUtil::PhpToJsObject($arDeals); ?>;

		var crmCompanyDialogID = '';
		var crmContactDialogID = '';
		var crmLeadDialogID = '';
		var crmDealDialogID = '';

		var crmCompanySelector_LAST_CALLER = null;
		var crmContactSelector_LAST_CALLER = null;
		var crmLeadSelector_LAST_CALLER = null;
		var crmDealSelector_LAST_CALLER = null;

		function openCrmEntityDialog(name, typeName, elements, caller, onClose)
		{
			var dlgID = CRM.Set(caller,
				name,
				typeName, //subName for dlgID
				elements,
				false,
				false,
				[typeName],
				{
					'company': '<?=CUtil::JSEscape(GetMessage('CRM_FF_COMPANY'))?>',
					'contact': '<?=CUtil::JSEscape(GetMessage('CRM_FF_CONTACT'))?>',
					'lead': '<?=CUtil::JSEscape(GetMessage('CRM_FF_LEAD'))?>',
					'ok': '<?=CUtil::JSEscape(GetMessage('CRM_FF_OK'))?>',
					'cancel': '<?=CUtil::JSEscape(GetMessage('CRM_FF_CANCEL'))?>',
					'close': '<?=CUtil::JSEscape(GetMessage('CRM_FF_CLOSE'))?>',
					'wait': '<?=CUtil::JSEscape(GetMessage('CRM_FF_SEARCH'))?>',
					'noresult': '<?=CUtil::JSEscape(GetMessage('CRM_FF_NO_RESULT'))?>',
					'add' : '<?=CUtil::JSEscape(GetMessage('CRM_FF_CHOISE'))?>',
					'edit' : '<?=CUtil::JSEscape(GetMessage('CRM_FF_CHANGE'))?>',
					'search' : '<?=CUtil::JSEscape(GetMessage('CRM_FF_SEARCH'))?>',
					'last' : '<?=CUtil::JSEscape(GetMessage('CRM_FF_LAST'))?>'
				},
				true
			);

			var dlg = obCrm[dlgID];
			dlg.AddOnSaveListener(onClose);
			dlg.Open();

			return dlgID;
		}

		function crmCompanySelector(span)
		{
			BX.bind(
				BX.findChild(span, { 'tag':'a' }, false, false),
				'click',
				function(e)
				{
					if(!e)
					{
						e = window.event;
					}

					crmCompanySelector_LAST_CALLER = this;
					crmCompanyDialogID =  openCrmEntityDialog('company', 'company', crmCompanyElements, crmCompanySelector_LAST_CALLER, onCrmCompanyDialogClose);
					BX.PreventDefault(e);
				});
		}

		function onCrmCompanyDialogClose(arElements)
		{
			if(!arElements || typeof(arElements['company']) == 'undefined')
			{
				return;
			}

			var element = arElements['company']['0'];
			if(element)
			{
				crmCompanySelectorCatch({ 'id':element['id'], 'name':element['title'] });
			}
			else
			{
				crmCompanySelectorCatch(null);
			}

			obCrm[crmCompanyDialogID].RemoveOnSaveListener(onCrmCompanyDialogClose);
		}

		function crmCompanySelectorCatch(item)
		{
			if(item && BX.type.isNotEmptyString(item['name']))
			{
				crmCompanySelector_LAST_CALLER.innerHTML = BX.util.htmlspecialchars(item['name']);
				BX.addClass(crmCompanySelector_LAST_CALLER, 'report-select-popup-link-active');
			}
			else
			{
				crmCompanySelector_LAST_CALLER.innerHTML = '<?=GetMessageJS('REPORT_CHOOSE')?>';
				BX.removeClass(crmCompanySelector_LAST_CALLER, 'report-select-popup-link-active');
			}

			var h = BX.findNextSibling(crmCompanySelector_LAST_CALLER, { 'tag':'input', 'type':'hidden', 'name':'value' });
			h.setAttribute('value', item ? item['id'] : '');
		}

		function crmContactSelector(span)
		{
			BX.bind(
				BX.findChild(span, { 'tag':'a' }, false, false),
				'click',
				function(e)
				{
					if(!e)
					{
						e = window.event;
					}

					crmContactSelector_LAST_CALLER = this;
					crmContactDialogID =  openCrmEntityDialog('contact', 'contact', crmContactElements, crmContactSelector_LAST_CALLER, onCrmContactDialogClose);
					BX.PreventDefault(e);
				});
		}

		function onCrmContactDialogClose(arElements)
		{
			if(!arElements || typeof(arElements['contact']) == 'undefined')
			{
				return;
			}

			var element = arElements['contact']['0'];
			if(element)
			{
				crmContactSelectorCatch({ 'id':element['id'], 'name':element['title'] });
			}
			else
			{
				crmContactSelectorCatch(null);
			}

			obCrm[crmContactDialogID].RemoveOnSaveListener(onCrmContactDialogClose);
		}

		function crmContactSelectorCatch(item)
		{
			if(item && BX.type.isNotEmptyString(item['name']))
			{
				crmContactSelector_LAST_CALLER.innerHTML = BX.util.htmlspecialchars(item['name']);
				BX.addClass(crmContactSelector_LAST_CALLER, 'report-select-popup-link-active');
			}
			else
			{
				crmContactSelector_LAST_CALLER.innerHTML = '<?=GetMessageJS('REPORT_CHOOSE')?>';
				BX.removeClass(crmContactSelector_LAST_CALLER, 'report-select-popup-link-active');
			}

			var h = BX.findNextSibling(crmContactSelector_LAST_CALLER, { 'tag':'input', 'type':'hidden', 'name':'value' });
			h.setAttribute('value', item ? item['id'] : '');
		}

		function crmLeadSelector(span)
		{
			BX.bind(
				BX.findChild(span, { 'tag':'a' }, false, false),
				'click',
				function(e)
				{
					if(!e)
					{
						e = window.event;
					}

					crmLeadSelector_LAST_CALLER = this;
					crmLeadDialogID =  openCrmEntityDialog('lead', 'lead', crmLeadElements, crmLeadSelector_LAST_CALLER, onCrmLeadDialogClose);
					BX.PreventDefault(e);
				});
		}

		function onCrmLeadDialogClose(arElements)
		{
			if(!arElements || typeof(arElements['lead']) == 'undefined')
			{
				return;
			}

			var element = arElements['lead']['0'];
			if(element)
			{
				crmLeadSelectorCatch({ 'id':element['id'], 'name':element['title'] });
			}
			else
			{
				crmLeadSelectorCatch(null);
			}

			obCrm[crmLeadDialogID].RemoveOnSaveListener(onCrmLeadDialogClose);
		}

		function crmLeadSelectorCatch(item)
		{
			if(item && BX.type.isNotEmptyString(item['name']))
			{
				crmLeadSelector_LAST_CALLER.innerHTML = BX.util.htmlspecialchars(item['name']);
				BX.addClass(crmLeadSelector_LAST_CALLER, 'report-select-popup-link-active');
			}
			else
			{
				crmLeadSelector_LAST_CALLER.innerHTML = '<?=GetMessageJS('REPORT_CHOOSE')?>';
				BX.removeClass(crmLeadSelector_LAST_CALLER, 'report-select-popup-link-active');
			}

			var h = BX.findNextSibling(crmLeadSelector_LAST_CALLER, { 'tag':'input', 'type':'hidden', 'name':'value' });
			h.setAttribute('value', item ? item['id'] : '');
		}

		function crmDealSelector(span)
		{
			BX.bind(
					BX.findChild(span, { 'tag':'a' }, false, false),
					'click',
					function(e)
					{
						if(!e)
						{
							e = window.event;
						}

						crmDealSelector_LAST_CALLER = this;
						crmDealDialogID =  openCrmEntityDialog('deal', 'deal', crmDealElements, crmDealSelector_LAST_CALLER, onCrmDealDialogClose);
						BX.PreventDefault(e);
					});
		}

		function onCrmDealDialogClose(arElements)
		{
			if(!arElements || typeof(arElements['deal']) == 'undefined')
			{
				return;
			}

			var element = arElements['deal']['0'];
			if(element)
			{
				crmDealSelectorCatch({ 'id':element['id'], 'name':element['title'] });
			}
			else
			{
				crmDealSelectorCatch(null);
			}

			obCrm[crmDealDialogID].RemoveOnSaveListener(onCrmDealDialogClose);
		}

		function crmDealSelectorCatch(item)
		{
			if(item && BX.type.isNotEmptyString(item['name']))
			{
				crmDealSelector_LAST_CALLER.innerHTML = BX.util.htmlspecialchars(item['name']);
				BX.addClass(crmDealSelector_LAST_CALLER, 'report-select-popup-link-active');
			}
			else
			{
				crmDealSelector_LAST_CALLER.innerHTML = '<?=GetMessageJS('REPORT_CHOOSE')?>';
				BX.removeClass(crmDealSelector_LAST_CALLER, 'report-select-popup-link-active');
			}

			var h = BX.findNextSibling(crmDealSelector_LAST_CALLER, { 'tag':'input', 'type':'hidden', 'name':'value' });
			h.setAttribute('value', item ? item['id'] : '');
		}
	</script>
</div><?
$APPLICATION->IncludeComponent(
	'bitrix:report.construct',
	'',
	Array(
		'USER_ID' => $arParams['USER_ID'],
		'REPORT_ID' => $arParams['REPORT_ID'],
		'ACTION' => $arParams['ACTION'],
		'PATH_TO_REPORT_LIST' => $arParams['PATH_TO_REPORT_REPORT'],
		'PATH_TO_REPORT_CONSTRUCT' => $arParams['PATH_TO_REPORT_CONSTRUCT'],
		'PATH_TO_REPORT_VIEW' => $arParams['PATH_TO_REPORT_VIEW'],
		'REPORT_HELPER_CLASS' => $arResult['REPORT_HELPER_CLASS'],
		'USE_CHART' => true
	),
	$component
);
?>