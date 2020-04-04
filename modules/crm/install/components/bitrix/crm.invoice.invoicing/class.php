<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Sale\PaySystem;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

class CrmInvoiceInvoicingComponent extends CBitrixComponent
{
	/**
	 * @return array
	 */
	public function getPaySystemList()
	{
		if (!Loader::includeModule('sale'))
			die();

		$paySystem = array();

		$dbRes = PaySystem\Manager::getList(array('filter' => array('IS_CASH' => 'N', 'ACTIVE' => 'Y')));
		while ($data = $dbRes->fetch())
		{
			$service = new PaySystem\Service($data);
			if ($service->isRequested() && $service->isTuned())
				$paySystem[$data['ID']] = $data['NAME'];
		}

		return $paySystem;
	}

	protected function prepareData()
	{
		$ajaxOptions = array('ajaxUrl' => '/bitrix/components/bitrix/crm.invoice.invoicing/ajax.php');

		$this->arResult['AJAX_OPTIONS'] = json_encode($ajaxOptions);
		$this->arResult['HEADERS'] = $this->getHeaders();
		$this->arResult['PAY_SYSTEM_LIST'] = $this->getPaySystemList();
		$this->arResult['INVOICING_TYPE'] = array(
			'F' => Loc::getMessage('CRM_FILTER_INVOICING_TYPE_FULL'),
			'M' => Loc::getMessage('CRM_FILTER_INVOICING_TYPE_MARKED'),
			'MN' => Loc::getMessage('CRM_FILTER_INVOICING_TYPE_MARKED_NEW'),
			'U' => Loc::getMessage('CRM_FILTER_INVOICING_TYPE_UNMARKED'),
		);
	}

	/**
	 * @return array
	 */
	public function getHeaders()
	{
		return array(
			array('id' => 'DOC_NUMBER', 'name' => GetMessage('CRM_COLUMN_DOC_NUMBER'), 'default' => true, 'editable' => false),
			array('id' => 'ACCOUNT_NUMBER', 'name' => GetMessage('CRM_COLUMN_ACCOUNT_NUMBER'), 'default' => true, 'editable' => false),
			array('id' => 'CONTRACTOR_KPP', 'name' => GetMessage('CRM_COLUMN_CONTRACTOR_KPP'), 'default' => true, 'editable' => false),
			array('id' => 'CONTRACTOR_INN', 'name' => GetMessage('CRM_COLUMN_CONTRACTOR_INN'), 'default' => true, 'editable' => false),
			array('id' => 'CHARGE_DATE', 'name' => GetMessage('CRM_COLUMN_DATE_CHARGE'), 'default' => true, 'editable' => false),
			array('id' => 'PRICE', 'name' => GetMessage('CRM_COLUMN_PRICE'), 'default' => true, 'editable' => false, 'align' => 'right', 'type' => 'number'),
		);
	}

	/**
	 * @return mixed
	 */
	public function executeComponent()
	{
		$this->prepareData();
		$this->includeComponentTemplate();
	}
}