<?php


namespace Bitrix\Crm\Controller;


use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\UI\PageNavigation;

class RequisiteLink extends Controller
{
	public function getFieldsAction()
	{
		$entity = new \Bitrix\Crm\Order\Rest\Entity\RequisiteLink();
		return ['REQUISITE_LINK'=>$entity->prepareFieldInfos(
			$entity->getFields()
		)];
	}

	public function listAction($select=[], $filter=[], $order=[], PageNavigation $pageNavigation)
	{
		$select = empty($select)? ['*']:$select;
		$order = empty($order)? ['ENTITY_TYPE_ID'=>'ASC']:$order;

		$links = EntityLink::getList(
			[
				'select'=>$select,
				'filter'=>$filter,
				'order'=>$order,
				'offset' => $pageNavigation->getOffset(),
				'limit' => $pageNavigation->getLimit()
			]
		)->fetchAll();

		return new Page('REQUISITE_LINKS', $links, function() use ($filter)
		{
			return count(
				EntityLink::getList(['filter'=>$filter])->fetchAll()
			);
		});
	}

	static public function prepareFields($fields)
	{
		return isset($fields['REQUISITE_LINK'])?['REQUISITE_LINK'=>$fields['REQUISITE_LINK']]:[];
	}
}