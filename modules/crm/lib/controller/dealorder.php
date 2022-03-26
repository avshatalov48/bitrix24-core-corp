<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Order\DealBinding;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Error;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Sale\Order;
use Bitrix\Sale\Registry;
use Bitrix\Sale\Result;


class DealOrder extends \Bitrix\Sale\Controller\Controller
{
	//region Actions

	/**
	 * @return array
	 */
	public function getFieldsAction(): array
	{
		$entity = new \Bitrix\Crm\Order\Rest\Entity\DealOrder();
		return [
			'DEAL_ORDER' => $entity->prepareFieldInfos(
				$entity->getFields()
			)
		];
	}

	/**
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function addAction(array $fields): ?array
	{
		$r = new Result();

		$res = $this->existsByFilter([
			'DEAL_ID'=>$fields['DEAL_ID'],
			'ORDER_ID'=>$fields['ORDER_ID']
		]);

		if($res->isSuccess() == false)
		{
			/** @var Order $order */
			$order = $this->loadOrder($fields['ORDER_ID']);

			if($this->setDealBinding($order, $fields['DEAL_ID'])
				->isSuccess())
			{
				$r = $order->save();
			}
			else
			{
				$r->addError(new Error('setDealBinding error', 201650000010));
			}
		}
		else
		{
			$r->addError(new Error('Duplicate entry for key [dealId, orderId]', 201650000001));
		}

		if(!$r->isSuccess())
		{
			$this->addErrors($r->getErrors());
			return null;
		}
		else
		{
			$registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);
			/** @var DealBinding $dealBindingClassName */
			$dealBindingClassName = $registry->get(ENTITY_CRM_ORDER_DEAL_BINDING);

			return [
				'DEAL_ORDER'=>
					$dealBindingClassName::getList([
						'filter'=>[
							'DEAL_ID'=>$fields['DEAL_ID'],
							'ORDER_ID'=>$fields['ORDER_ID']
						]
					])->fetchAll()[0]
			];
		}
	}

	/**
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function deleteByFilterAction($fields): ?bool
	{
		$r = $this->checkFields($fields);

		if($r->isSuccess())
		{
			$r = $this->existsByFilter($fields);
			if($r->isSuccess())
			{
				/** @var Order $order */
				$order = $this->loadOrder($fields['ORDER_ID']);

				$this->deleteDealBinding($order);
				$r = $order->save();
			}
		}

		if($r->isSuccess())
		{
			return true;
		}
		else
		{
			$this->addErrors($r->getErrors());
			return null;
		}
	}

	public function listAction($select=[], $filter=[], $order=[], PageNavigation $pageNavigation): Page
	{
		$select = empty($select)? ['*']:$select;
		$order = empty($order)? ['DEAL_ID'=>'ASC']:$order;

		$registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);
		/** @var DealBinding $dealBindingClassName */
		$dealBindingClassName = $registry->get(ENTITY_CRM_ORDER_DEAL_BINDING);

		$tradeBindings = $dealBindingClassName::getList(
			[
				'select'=>$select,
				'filter'=>$filter,
				'order'=>$order,
				'offset' => $pageNavigation->getOffset(),
				'limit' => $pageNavigation->getLimit()
			]
		)->fetchAll();

		return new Page('DEAL_ORDER', $tradeBindings, function() use ($filter)
		{
			$registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);
			/** @var DealBinding $dealBindingClassName */
			$dealBindingClassName = $registry->get(ENTITY_CRM_ORDER_DEAL_BINDING);

			return count(
				$dealBindingClassName::getList(['filter'=>$filter])->fetchAll()
			);
		});
	}
	//endregion

	protected function checkFields($fields)
	{
		$r = new Result();

		if(isset($fields['DEAL_ID']) == false && $fields['DEAL_ID'] <> '')
			$r->addError(new Error('dealId - parametrs is empty', 201640400001));

		if(isset($fields['ORDER_ID'])  == false && $fields['ORDER_ID'] <> '')
			$r->addError(new Error('orderId - parametrs is empty', 201640400002));

		return $r;
	}

	protected function existsByFilter($filter)
	{
		$r = new \Bitrix\Main\Result();

		// \Bitrix\Main\Loader::includeModule('sale');

		$registry = \Bitrix\Sale\Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);
		/** @var DealBinding $dealBindingClassName */
		$dealBindingClassName = $registry->get(ENTITY_CRM_ORDER_DEAL_BINDING);

		$row = $dealBindingClassName::getList(['filter'=>['DEAL_ID'=>$filter['DEAL_ID'], 'ORDER_ID'=>$filter['ORDER_ID']]])->fetchAll();
		if(isset($row[0]['DEAL_ID']) == false)
			$r->addError(new Error('deal relation is not exists', 201640400004));

		return $r;
	}

	protected function loadOrder($id)
	{
		$registry = \Bitrix\Sale\Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);

		/** @var \Bitrix\Sale\Order $className */
		$orderClass = $registry->getOrderClassName();

		/** @var \Bitrix\Sale\Order $className */
		$order = $orderClass::load($id);
		if ($order )
		{
			return $order;
		}
		else
		{
			$this->addError(new Error('order is not exists', 200540400001));
		}
	}

	/**
	 * @param Order $order
	 * @param int $dialId
	 * @return Result
	 */
	protected function setDealBinding(Order $order, $dialId = 0 ): Result
	{
		$dealId = $dialId ?? 0;

		if ($dealId)
		{
			$dealBinding = $order->getDealBinding();

			if ($dealBinding === null)
			{
				$dealBinding = $order->createDealBinding();
			}

			if ($dealBinding)
			{
				$dealBinding->setField('DEAL_ID', $dialId);
			}
		}

		return new Result();
	}

	/**
	 * @param Order $order
	 * @param int $dialId
	 * @return Result
	 */
	protected function deleteDealBinding(Order $order): Result
	{
		$dealBinding = $order->getDealBinding();
		if ($dealBinding)
		{
			$dealBinding->delete();
		}
		return new Result();
	}

	protected function checkPermissionEntity($name)
	{
		if($name == 'deletebyfilter')
		{
			$r = $this->checkReadPermissionEntity();
		}
		else
		{
			$r = parent::checkPermissionEntity($name);
		}
		return $r;
	}
}
