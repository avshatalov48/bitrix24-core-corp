<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->IncludeComponent(
	'bitrix:report.view',
	'',
	array(
		'REPORT_ID' => $arParams['REPORT_ID'],
		'PATH_TO_REPORT_LIST' => $arParams['PATH_TO_REPORT_REPORT'],
		'PATH_TO_REPORT_CONSTRUCT' => $arParams['PATH_TO_REPORT_CONSTRUCT'],
		'PATH_TO_REPORT_VIEW' => $arParams['PATH_TO_REPORT_VIEW'],
		'REPORT_HELPER_CLASS' => $arResult['REPORT_HELPER_CLASS'],
		'USER_NAME_FORMAT' => $arResult['NAME_TEMPLATE'],
		'USE_CHART' => true,
		'STEXPORT_PARAMS' => array('serviceUrl' => '/bitrix/components/bitrix/crm.report.view/stexport.ajax.php')
	),
	false
);

CUtil::InitJSCore(array('ajax', 'popup', 'ui.fonts.opensans'));

$APPLICATION->AddHeadScript('/bitrix/js/crm/crm.js');
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');

$arFiles = array();

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

//CCrmCompany
$arCompanyTypeList = CCrmStatus::GetStatusList('COMPANY_TYPE');
$arCompanyIndustryList = CCrmStatus::GetStatusList('INDUSTRY');
$obRes = CCrmCompany::GetListEx(
	array('ID' => 'DESC'),
	array('@CATEGORY_ID' => 0),
	false,
	array('nTopCount' => 50),
	array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY',  'LOGO')
);
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
$arContactTypeList = CCrmStatus::GetStatusList('CONTACT_TYPE');
$obRes = CCrmContact::GetListEx(
	array('LAST_NAME' => 'ASC', 'NAME' => 'ASC'),
	array('@CATEGORY_ID' => 0),
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
	array('ID', 'TITLE', 'NAME', 'SECOND_NAME', 'LAST_NAME')
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
?>
<div id="report-chfilter-examples-custom" style="display: none;">

	<div class="filter-field filter-field-deal chfilter-field-\Bitrix\Crm\Deal" callback="crmDealSelector">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<span class="webform-field-textbox-inner">
			<input id="%ID%" type="text" class="webform-field-textbox" caller="true" />
			<input type="hidden" name="%NAME%" value=""/>
			<a href="" class="webform-field-textbox-clear"></a>
		</span>
	</div>

	<div class="filter-field filter-field-deal chfilter-field-\Bitrix\Crm\DealTable" callback="crmDealSelector">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<span class="webform-field-textbox-inner">
			<input id="%ID%" type="text" class="webform-field-textbox" caller="true" />
			<input type="hidden" name="%NAME%" value=""/>
			<a href="" class="webform-field-textbox-clear"></a>
		</span>
	</div>

	<div class="filter-field filter-field-company chfilter-field-\Bitrix\Crm\Company" callback="crmCompanySelector">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<span class="webform-field-textbox-inner">
			<input id="%ID%" type="text" class="webform-field-textbox" caller="true" />
			<input type="hidden" name="%NAME%" value=""/>
			<a href="" class="webform-field-textbox-clear"></a>
		</span>
	</div>

	<div class="filter-field filter-field-company chfilter-field-\Bitrix\Crm\CompanyTable" callback="crmCompanySelector">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<span class="webform-field-textbox-inner">
			<input id="%ID%" type="text" class="webform-field-textbox" caller="true" />
			<input type="hidden" name="%NAME%" value=""/>
			<a href="" class="webform-field-textbox-clear"></a>
		</span>
	</div>

	<div class="filter-field filter-field-contact chfilter-field-\Bitrix\Crm\Contact" callback="crmContactSelector">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<span class="webform-field-textbox-inner">
			<input id="%ID%" type="text" class="webform-field-textbox" caller="true" />
			<input type="hidden" name="%NAME%" value=""/>
			<a href="" class="webform-field-textbox-clear"></a>
		</span>
	</div>

	<div class="filter-field filter-field-contact chfilter-field-\Bitrix\Crm\ContactTable" callback="crmContactSelector">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<span class="webform-field-textbox-inner">
			<input id="%ID%" type="text" class="webform-field-textbox" caller="true" />
			<input type="hidden" name="%NAME%" value=""/>
			<a href="" class="webform-field-textbox-clear"></a>
		</span>
	</div>

	<div class="filter-field filter-field-lead chfilter-field-LEAD_BY" callback="crmLeadSelector">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<span class="webform-field-textbox-inner">
			<input id="%ID%" type="text" class="webform-field-textbox" caller="true" />
			<input type="hidden" name="%NAME%" value=""/>
			<a href="" class="webform-field-textbox-clear"></a>
		</span>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-LEAD_BY.STATUS_BY.STATUS_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arEventTypes = CCrmStatus::GetStatusList('STATUS'); ?>
			<? foreach($arEventTypes as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-LEAD_BY.SOURCE_BY.STATUS_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arEventTypes = CCrmStatus::GetStatusList('SOURCE'); ?>
			<? foreach($arEventTypes as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-type chfilter-field-TYPE_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<?
			if ($arResult['REPORT_OWNER_ID'] === 'crm_activity')
				$arTypes = CCrmActivityType::PrepareFilterItems();
			else
				$arTypes = CCrmStatus::GetStatusList('DEAL_TYPE');
			?>
			<? foreach($arTypes as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-type chfilter-field-DEAL_OWNER.TYPE_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<?
			if ($arResult['REPORT_OWNER_ID'] === 'crm_activity')
				$arTypes = CCrmActivityType::PrepareFilterItems();
			else
				$arTypes = CCrmStatus::GetStatusList('DEAL_TYPE');
			?>
			<? foreach($arTypes as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-type chfilter-field-DIRECTION" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<?
			$arDirections = CCrmActivityDirection::GetAllDescriptions(0);
			unset($arDirections[CCrmActivityDirection::Undefined]);
			?>
			<? foreach($arDirections as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-type chfilter-field-PRIORITY" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $priorities = CCrmActivityPriority::PrepareFilterItems(); ?>
			<? foreach($priorities as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-currency chfilter-field-CURRENCY_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arTypes = CCrmCurrencyHelper::PrepareListItems(); ?>
			<? foreach($arTypes as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-EVENT_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arEventTypes = CCrmStatus::GetStatusList('EVENT_TYPE'); ?>
			<? foreach($arEventTypes as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-CATEGORY_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option><?
			$categories = \Bitrix\Crm\Category\DealCategory::getSelectListItems();
			foreach($categories as $key => $val):
				?><option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option><?
			endforeach;
		?></select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-STAGE_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option><?
			$categoryGroups = \Bitrix\Crm\Category\DealCategory::getStageGroupInfos();
			foreach($categoryGroups as $group):
				$groupName = isset($group['name']) ? $group['name'] : '';
				if($groupName !== ''):?><optgroup label="<?=htmlspecialcharsbx($groupName)?>"><?endif;
				$groupItems = isset($group['items']) && is_array($group['items']) ? $group['items'] : array();
				foreach($groupItems as $key => $val):
					?><option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option><?
				endforeach;
				if($groupName !== ''):?></optgroup><?endif;
			endforeach;
		?></select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-DEAL_OWNER.STAGE_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option><?
			$categoryGroups = \Bitrix\Crm\Category\DealCategory::getStageGroupInfos();
			foreach($categoryGroups as $group):
				$groupName = isset($group['name']) ? $group['name'] : '';
				if($groupName !== ''):?><optgroup label="<?=htmlspecialcharsbx($groupName)?>"><?endif;
				$groupItems = isset($group['items']) && is_array($group['items']) ? $group['items'] : array();
				foreach($groupItems as $key => $val):
				?><option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option><?
				endforeach;
				if($groupName !== ''):?></optgroup><?endif;
			endforeach;
		?></select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-STATUS_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<?
			if ($arResult['REPORT_OWNER_ID'] === 'crm_invoice')
				$arStages = CCrmStatus::GetStatusList('INVOICE_STATUS');
			else
				$arStages = CCrmStatus::GetStatusList('STATUS');
			?>
			<? foreach($arStages as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<?
	if ($arResult['REPORT_OWNER_ID'] === 'crm_invoice'):
	?><div class="filter-field filter-field-eventType chfilter-field-PERSON_TYPE_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<?
			$arOptions = CCrmReportHelperBase::getInvoicePersonTypeList();
			foreach($arOptions as $key => $val){ ?>
				<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-PAY_SYSTEM_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<?
			$arOptions = CCrmReportHelperBase::getInvoicePaySystemList();
			foreach($arOptions as $key => $val){ ?>
				<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div><?
	endif;
	?>

	<div class="filter-field filter-field-eventType chfilter-field-SOURCE_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arStages = CCrmStatus::GetStatusList('SOURCE'); ?>
			<? foreach($arStages as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-WEBFORM_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $webFormNames = Bitrix\Crm\WebForm\Manager::getListNames(); ?>
			<? foreach($webFormNames as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-INVOICE_UTS.DEAL_BY.WEBFORM_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $webFormNames = Bitrix\Crm\WebForm\Manager::getListNames(); ?>
			<? foreach($webFormNames as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-DEAL_OWNER.WEBFORM_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $webFormNames = Bitrix\Crm\WebForm\Manager::getListNames(); ?>
			<? foreach($webFormNames as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? foreach($arCompanyTypeList as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-DEAL_OWNER.COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? foreach($arCompanyTypeList as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-COMPANY_BY.INDUSTRY_BY.STATUS_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arEventTypes = CCrmStatus::GetStatusList('INDUSTRY'); ?>
			<? foreach($arEventTypes as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-COMPANY_BY.EMPLOYEES_BY.STATUS_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arEventTypes = CCrmStatus::GetStatusList('EMPLOYEES'); ?>
			<? foreach($arEventTypes as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-CONTACT_BY.TYPE_BY.STATUS_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? foreach($arContactTypeList as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-DEAL_OWNER.CONTACT_BY.TYPE_BY.STATUS_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? foreach($arContactTypeList as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-CONTACT_BY.SOURCE_BY.STATUS_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arEventTypes = CCrmStatus::GetStatusList('SOURCE'); ?>
			<? foreach($arEventTypes as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-DEAL_OWNER.CONTACT_BY.SOURCE_BY.STATUS_ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<? $arEventTypes = CCrmStatus::GetStatusList('SOURCE'); ?>
			<? foreach($arEventTypes as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

	<div class="filter-field filter-field-eventType chfilter-field-ORIGINATOR_BY.ID" callback="RTFilter_chooseBoolean">
		<label for="%ID%" class="filter-field-title">%TITLE% "%COMPARE%"</label>
		<select id="%ID%" name="%NAME%" class="filter-dropdown" caller="true">
			<option value=""><?=GetMessage('CRM_REPORT_INCLUDE_ALL')?></option>
			<?  $arOriginatorList = CCrmExternalSaleHelper::PrepareListItems() ?>
			<? foreach($arOriginatorList as $key => $val){ ?>
			<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
			<?}?>
		</select>
	</div>

</div>

<?$this->SetViewTarget("sidebar_tools_1", 100);?>
<? $reportCurrencyID = CCrmReportHelper::GetReportCurrencyID(); ?>
<div class="sidebar-block">
	<b class="r2"></b>
	<b class="r1"></b>
	<b class="r0"></b>
	<div class="sidebar-block-inner">
		<div class="filter-block">
			<label for="crmReportCurrencyID" class="filter-field-title"><?= str_replace('#CURRENCY#', CCrmCurrency::GetEncodedCurrencyName(CCrmCurrency::GetAccountCurrencyID()), GetMessage('CRM_REPORT_CURRENCY_INFO')) ?></label>
		</div>
	</div>
	<i class="r0"></i>
	<i class="r1"></i>
	<i class="r2"></i>
</div>
<?$this->EndViewTarget();?>
<script type="text/javascript">
	var crmDealElements = <? echo CUtil::PhpToJsObject($arDeals); ?>;
	var crmCompanyElements = <? echo CUtil::PhpToJsObject($arCompanies); ?>;
	var crmContactElements = <? echo CUtil::PhpToJsObject($arContacts); ?>;
	var crmLeadElements = <? echo CUtil::PhpToJsObject($arLeads); ?>;

	var crmCompanyDialogID = '';
	var crmContactDialogID = '';
	var crmLeadDialogID = '';

	var crmDealSelector_LAST_CALLER = null;
	var crmCompanySelector_LAST_CALLER = null;
	var crmContactSelector_LAST_CALLER = null;
	var crmLeadSelector_LAST_CALLER = null;

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

	function crmCompanySelector(caller)
	{
		crmCompanySelector_LAST_CALLER = caller;
		crmCompanyDialogID =  openCrmEntityDialog('company', 'company', crmCompanyElements, crmCompanySelector_LAST_CALLER, onCrmCompanyDialogClose);
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
			crmCompanySelector_LAST_CALLER.value = item['name'] + ' [' + item['id'] + ']';
		}
		else
		{
			crmCompanySelector_LAST_CALLER.value = '';
		}

		var h = BX.findNextSibling(crmCompanySelector_LAST_CALLER, { 'tag':'input', 'attr':{ 'type':'hidden' } });
		h.value = item ? item['id'] : '';
	}

	function crmCompanySelectorClear(e)
	{
		crmCompanySelector_LAST_CALLER = BX.findChild(this.parentNode, { 'tag':'input', 'class':'webform-field-textbox'});

		BX.PreventDefault(e);
		crmCompanySelectorCatch(null);
	}

	function crmDealSelector(caller)
	{
		crmDealSelector_LAST_CALLER = caller;
		crmDealDialogID =  openCrmEntityDialog('deal', 'deal', crmDealElements, crmDealSelector_LAST_CALLER, onCrmDealDialogClose);
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
			crmDealSelector_LAST_CALLER.value = item['name'] + ' [' + item['id'] + ']';
		}
		else
		{
			crmDealSelector_LAST_CALLER.value = '';
		}

		var h = BX.findNextSibling(crmDealSelector_LAST_CALLER, { 'tag':'input', 'attr':{ 'type':'hidden' } });
		h.value = item ? item['id'] : '';
	}

	function crmDealSelectorClear(e)
	{
		crmDealSelector_LAST_CALLER = BX.findChild(this.parentNode, { 'tag':'input', 'class':'webform-field-textbox'});

		BX.PreventDefault(e);
		crmDealSelectorCatch(null);
	}

	function crmContactSelector(caller)
	{
		crmContactSelector_LAST_CALLER = caller;
		crmContactDialogID =  openCrmEntityDialog('contact', 'contact', crmContactElements, crmContactSelector_LAST_CALLER, onCrmContactDialogClose);
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
			crmContactSelector_LAST_CALLER.value = item['name'] + ' [' + item['id'] + ']';
		}
		else
		{
			crmContactSelector_LAST_CALLER.value = '';
		}

		var h = BX.findNextSibling(crmContactSelector_LAST_CALLER, { 'tag':'input', 'attr':{ 'type':'hidden' } });
		h.value = item ? item['id'] : '';
	}

	function crmContactSelectorClear(e)
	{
		crmContactSelector_LAST_CALLER = BX.findChild(this.parentNode, { 'tag':'input', 'class':'webform-field-textbox'});

		BX.PreventDefault(e);
		crmContactSelectorCatch(null);
	}

	function crmLeadSelector(caller)
	{
		crmLeadSelector_LAST_CALLER = caller;
		crmLeadDialogID =  openCrmEntityDialog('lead', 'lead', crmLeadElements, crmLeadSelector_LAST_CALLER, onCrmLeadDialogClose);
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
			crmLeadSelector_LAST_CALLER.value = item['name'] + ' [' + item['id'] + ']';
		}
		else
		{
			crmLeadSelector_LAST_CALLER.value = '';
		}

		var h = BX.findNextSibling(crmLeadSelector_LAST_CALLER, { 'tag':'input', 'attr':{ 'type':'hidden' } });
		h.value = item ? item['id'] : '';
	}

	function crmLeadSelectorClear(e)
	{
		crmLeadSelector_LAST_CALLER = BX.findChild(this.parentNode, { 'tag':'input', 'class':'webform-field-textbox'});

		BX.PreventDefault(e);
		crmLeadSelectorCatch(null);
	}

	BX.ready(function()
	{
		window.setTimeout(
			function()
			{
				var i, temp, deal, company, contact;

				// Deal
				i = 0; temp = []; deal = [];
				temp[i++] = BX.findChildren(BX('report-rewrite-filter'), { 'class':'chfilter-field-\\Bitrix\\Crm\\Deal' }, true);
				temp[i++] = BX.findChildren(BX('report-rewrite-filter'), { 'class':'chfilter-field-\\Bitrix\\Crm\\DealTable' }, true);
				for (i in temp) if (temp[i]) deal = deal.concat(temp[i]);
				if(deal)
				{
					for (i in deal)
					{
						BX.bind(
							BX.findChild(deal[i], { 'tag':'input', 'class':'webform-field-textbox' }, true),
							'click',
							function(e)
							{
								if(!e)
								{
									e = window.event;
								}

								crmDealSelector(this);
								BX.PreventDefault(e);
							}
						);
						BX.bind(BX.findChild(deal[i], { 'tag':'a', 'class':'webform-field-textbox-clear' }, true), 'click', crmDealSelectorClear);
					}
				}

				// Company
				i = 0; temp = []; company = [];
				temp[i++] = BX.findChildren(BX('report-rewrite-filter'), { 'class':'chfilter-field-\\Bitrix\\Crm\\Company' }, true);
				temp[i++] = BX.findChildren(BX('report-rewrite-filter'), { 'class':'chfilter-field-\\Bitrix\\Crm\\CompanyTable' }, true);
				for (i in temp) if (temp[i]) company = company.concat(temp[i]);
				if(company)
				{
					for (i in company)
					{
						BX.bind(
							BX.findChild(company[i], { 'tag':'input', 'class':'webform-field-textbox' }, true),
							'click',
							function(e)
							{
								if(!e)
								{
									e = window.event;
								}

								crmCompanySelector(this);
								BX.PreventDefault(e);
							}
						);
						BX.bind(BX.findChild(company[i], { 'tag':'a', 'class':'webform-field-textbox-clear' }, true), 'click', crmCompanySelectorClear);
					}
				}

				// Contact
				i = 0; temp = []; contact = [];
				temp[i++] = BX.findChildren(BX('report-rewrite-filter'), { 'class':'chfilter-field-\\Bitrix\\Crm\\Contact' }, true);
				temp[i++] = BX.findChildren(BX('report-rewrite-filter'), { 'class':'chfilter-field-\\Bitrix\\Crm\\ContactTable' }, true);
				for (i in temp) if (temp[i]) contact = contact.concat(temp[i]);
				if(contact)
				{
					for (i in contact)
					{
						BX.bind(
							BX.findChild(contact[i], { 'tag':'input', 'class':'webform-field-textbox' }, true),
							'click',
							function(e)
							{
								if(!e)
								{
									e = window.event;
								}

								crmContactSelector(this);
								BX.PreventDefault(e);
							}
						);
						BX.bind(BX.findChild(contact[i], { 'tag':'a', 'class':'webform-field-textbox-clear' }, true), 'click', crmContactSelectorClear);
					}
				}

				// Lead
				var lead = BX.findChild(BX('report-rewrite-filter'), { 'class':'chfilter-field-LEAD_BY' }, true);
				if(lead)
				{
					BX.bind(
						BX.findChild(lead, { 'tag':'input', 'class':'webform-field-textbox' }, true),
						'click',
						function(e)
						{
							if(!e)
							{
								e = window.event;
							}

							crmLeadSelector(this);
							BX.PreventDefault(e);
						}
					);
					BX.bind(BX.findChild(lead, { 'tag':'a', 'class':'webform-field-textbox-clear' }, true), 'click', crmLeadSelectorClear);
				}
			},
		500);
	});
</script>
