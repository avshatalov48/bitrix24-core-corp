<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$arResult["ENTITIES"] = array(
	CCrmOwnerType::LeadName => CCrmOwnerType::GetDescription(CCrmOwnerType::Lead),
	CCrmOwnerType::ContactName => CCrmOwnerType::GetDescription(CCrmOwnerType::Contact),
	CCrmOwnerType::CompanyName => CCrmOwnerType::GetDescription(CCrmOwnerType::Company),
	CCrmOwnerType::DealName => CCrmOwnerType::GetDescription(CCrmOwnerType::Deal),
	CCrmOwnerType::InvoiceName => CCrmOwnerType::GetDescription(CCrmOwnerType::Invoice),
	CCrmOwnerType::QuoteName => CCrmOwnerType::GetDescription(CCrmOwnerType::Quote)
);

$this->IncludeComponentTemplate();


