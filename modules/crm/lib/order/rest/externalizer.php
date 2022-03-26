<?php


namespace Bitrix\Crm\Order\Rest;

use Bitrix\Crm\Order\Rest\Entity\DealOrder;
use Bitrix\Crm\Order\Rest\Entity\OrderContactCompany;
use Bitrix\Crm\Order\Rest\Entity\RequisiteLink;
use Bitrix\Main\Engine\Controller;

class Externalizer extends \Bitrix\Sale\Rest\Externalizer
{
	protected function getEntity(Controller $controller)
	{
		if($controller instanceof \Bitrix\Sale\Controller\Order)
		{
			$entity = new \Bitrix\Crm\Order\Rest\Entity\Order();
		}
		elseif ($controller instanceof \Bitrix\Crm\Controller\OrderContactCompany)
		{
			$entity = new OrderContactCompany();
		}
		elseif ($controller instanceof \Bitrix\Crm\Controller\RequisiteLink)
		{
			$entity = new RequisiteLink();
		}
		elseif ($controller instanceof \Bitrix\Crm\Controller\DealOrder)
		{
			$entity = new DealOrder();
		}
		else
		{
			$entity = parent::getEntity($controller);
		}

		return $entity;
	}
}