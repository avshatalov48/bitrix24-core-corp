<?php


namespace Bitrix\Crm\Order\Rest\Normalizer;


use Bitrix\Crm\Order\Order;

class ObjectNormalizer extends \Bitrix\Sale\Rest\Normalizer\ObjectNormalizer
{
	public function clientNormalize()
	{
		$r=[];
		/** @var Order $order */
		$order = $this->getOrder();
		foreach ($order->getContactCompanyCollection() as $item)
		{
			$r[] = $item->getFieldValues();
		}
		$this->fields['ORDER']['CLIENTS']=$r;

		return $this;
	}

	public function requisiteLinkNormalize()
	{
		/** @var Order $order */
		$order = $this->getOrder();
		$this->fields['ORDER']['REQUISITE_LINK']=$order->getRequisiteLink();

		return $this;
	}
}