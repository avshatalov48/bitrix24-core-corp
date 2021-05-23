<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

class CrmInvoicePreviewComponent extends \CBitrixComponent
{
	protected function prepareData()
	{
		$this->arResult = CCrmInvoice::GetList(
			array(),
			array(
				'ID' => $this->arParams['invoiceId'],
				'CHECK_PERMISSIONS' => 'N'
			)
		)->Fetch();

		$this->arResult['STATUS_TEXT'] = $this->getReferenceValue('INVOICE_STATUS', $this->arResult['STATUS_ID']);

		if(empty($this->arResult['CURRENCY']))
			$this->arResult['CURRENCY'] = CCrmCurrency::GetBaseCurrencyID();

		$this->arResult['FORMATTED_SUM'] = CCrmCurrency::MoneyToString($this->arResult['PRICE'], $this->arResult['CURRENCY']);

		$this->arResult['RESPONSIBLE_FORMATTED_NAME'] = CUser::FormatName(
			$this->arParams['NAME_TEMPLATE'],
			array(
				'LOGIN' => $this->arResult['RESPONSIBLE_LOGIN'],
				'NAME' => $this->arResult['RESPONSIBLE_NAME'],
				'LAST_NAME' => $this->arResult['RESPONSIBLE_LAST_NAME'],
				'SECOND_NAME' => $this->arResult['RESPONSIBLE_SECOND_NAME']
			),
			true, false
		);
		$this->arResult['RESPONSIBLE_FORMATTED_NAME'] = htmlspecialcharsbx($this->arResult['RESPONSIBLE_FORMATTED_NAME']);
		$this->arResult['RESPONSIBLE_PROFILE'] = CComponentEngine::MakePathFromTemplate(
			$this->arParams["PATH_TO_USER_PROFILE"],
			array("user_id" => $this->arResult["RESPONSIBLE_ID"])
		);
		$this->arResult['RESPONSIBLE_UNIQID'] = 'u_'.$this->randString();

		if ($this->arResult['UF_DEAL_ID'] > 0)
		{
			$this->arResult['DEAL_PROFILE'] = CComponentEngine::MakePathFromTemplate(
				$this->arParams['PATH_TO_DEAL_SHOW'],
				array('deal_id' => $this->arResult['UF_DEAL_ID'])
			);
			$this->arResult['UF_DEAL_TITLE'] = CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $this->arResult['UF_DEAL_ID'], false);
		}
		if ($this->arResult['UF_QUOTE_ID'] > 0)
		{
			$this->arResult['QUOTE_PROFILE'] = CComponentEngine::MakePathFromTemplate(
				$this->arParams['PATH_TO_QUOTE_SHOW'],
				array('quote_id' => $this->arResult['UF_QUOTE_ID'])
			);
			$this->arResult['UF_QUOTE_TITLE'] = CCrmOwnerType::GetCaption(CCrmOwnerType::Quote, $this->arResult['UF_QUOTE_ID'], false);
		}

	}

	protected function getReferenceValue($referenceName, $elementId)
	{
		$referenceValues = CCrmStatus::GetStatusListEx($referenceName);
		if(isset($referenceValues[$elementId]))
		{
			return htmlspecialcharsbx($referenceValues[$elementId]);
		}
		return null;
	}

	public function executeComponent()
	{
		$this->prepareData();
		if($this->arResult['ID'] > 0)
		{
			$this->includeComponentTemplate();
		}
	}
}