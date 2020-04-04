<?php


namespace Bitrix\Crm\Controller;


use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\UI\PageNavigation;

class OrderRequisiteLink extends Controller
{
	public function fieldsAction()
	{
		$entity = new \Bitrix\Crm\Order\Rest\Entity\OrderRequisiteLink();
		return ['ORDER_REQUISITE_LINK'=>$entity->getFields()];
	}

	public function listAction($select=[], $filter, $order=[], PageNavigation $pageNavigation)
	{
		$select = empty($select)? ['*']:$select;
		$order = empty($order)? ['ID'=>'ASC']:$order;

		$links = EntityLink::getList(
			[
				'select'=>$select,
				'filter'=>$filter,
				'order'=>$order,
				'offset' => $pageNavigation->getOffset(),
				'limit' => $pageNavigation->getLimit()
			]
		)->fetchAll();

		return new Page('ORDER_REQUISITE_LINKS', $links, function() use ($filter)
		{
			return count(
				EntityLink::getList(['filter'=>$filter])->fetchAll()
			);
		});
	}

	static public function prepareFields($fields)
	{
		return ['ORDER_REQUISITE_LINKS'=>isset($fields['ORDER_REQUISITE_LINKS'])?$fields['ORDER_REQUISITE_LINKS']:[]];
	}
}