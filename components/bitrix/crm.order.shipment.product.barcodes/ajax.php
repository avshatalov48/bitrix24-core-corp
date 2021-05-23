<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!\Bitrix\Main\Loader::includeModule("crm"))
{
	throw new \Bitrix\Main\SystemException('Module CRM is not installed');
}

if(!\Bitrix\Main\Loader::includeModule("sale"))
{
	throw new \Bitrix\Main\SystemException('Module Sale is not installed');
}

class CCrmOrderShipmentProductListComponentAjaxController extends \Bitrix\Main\Engine\Controller
{
	protected function checkProductBarcodeAction($barcode, $basketId, $orderId, $storeId)
	{
		if(!\Bitrix\Main\Loader::includeModule("catalog"))
		{
			throw new \Bitrix\Main\SystemException('Module Catalog is not installed');
		}

		$basketItem = null;
		$result = false;

		/** @var \Bitrix\Sale\Order $order */
		$order = \Bitrix\Crm\Order\Order::load($orderId);
		if ($order)
		{
			$basket = $order->getBasket();
			if ($basket)
				$basketItem = $basket->getItemById($basketId);
		}

		if ($basketItem)
		{
			$params = array(
				'BARCODE' => $barcode,
				'STORE_ID' => $storeId
			);

			$result = \Bitrix\Sale\Provider::checkProductBarcode($basketItem, $params);
		}

		if ($result)
			$this->addResultData('RESULT', 'OK');
		else
			$this->addResultError('ERROR');
	}
}
