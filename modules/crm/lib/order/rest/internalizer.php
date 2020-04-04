<?php


namespace Bitrix\Crm\Order\Rest;


use Bitrix\Rest\RestException;
use Bitrix\Main\Engine\Controller;

class Internalizer extends \Bitrix\Sale\Rest\Internalizer
{
	protected function getEntity(Controller $controller)
	{
		$entity = null;
		if($controller instanceof \Bitrix\Sale\Controller\Order)
		{
			$entity = new \Bitrix\Crm\Order\Rest\Entity\Order();
		}
		else
		{
			$entity = parent::getEntity($controller);
		}

		return $entity;
	}
}