<?php


namespace Bitrix\Crm\Order\Rest\Normalizer;

class Director
{
	public function normalize(ObjectNormalizer $normalizer, \Bitrix\Sale\Order $order)
	{
		try{
			$normalizer->init($order)
				->orderNormalize()
				->basketNormalize()
				->propertiesValueNormalize()
				->paymentsNormalize()
				->shipmentsNormalize()
				->tradeBindingsNormalize()
				->clientNormalize()
				->requisiteLinkNormalize()
				->applyDiscountNormalize()
				->taxNormalize();
		}
		catch(\Bitrix\Main\SystemException $e)
		{
			return null;
		}
		return $normalizer->getFields();
	}
}