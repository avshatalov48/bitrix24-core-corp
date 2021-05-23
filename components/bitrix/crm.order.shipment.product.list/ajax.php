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

class CCrmOrderShipmentProductListAjaxController extends \Bitrix\Main\Engine\Controller
{
	public function checkProductBarcodeAction($barcode, $orderId, $basketId, $storeId)
	{
		$basketItem = null;
		$result = 'UNKNOWN';

		/** @var Bitrix\Crm\Order\ $order */
		$order = \Bitrix\Crm\Order\Order::load($orderId);

		if($order)
		{
			$basket = $order->getBasket();

			if ($basket)
			{
				$basketItem = $basket->getItemById($basketId);
			}
		}

		if ($basketItem)
		{
			$params = array(
				'BARCODE' => $barcode,
				'STORE_ID' => $storeId
			);

			$result = \Bitrix\Sale\Provider::checkProductBarcode($basketItem, $params) ? 'OK' : 'ERROR';
		}

		return $result;
	}
}
