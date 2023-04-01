<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Order\EntityBinding;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Error;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Sale\Order;
use Bitrix\Sale\Registry;
use Bitrix\Sale\Result;


class OrderEntity extends Controller
{
	//region Actions

	/**
	 * @return array
	 */
	public function getFieldsAction()
	{
		$view = $this->getViewManager()
			->getView($this);

		return ['ORDER_ENTITY'=>$view->prepareFieldInfos(
			$view->getFields()
		)];
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
			'OWNER_TYPE_ID'=>$fields['OWNER_TYPE_ID'],
			'OWNER_ID'=>$fields['OWNER_ID'],
			'ORDER_ID'=>$fields['ORDER_ID']
		]);

		if($res->isSuccess() == false)
		{
			/** @var Order $order */
			$order = $this->loadOrder($fields['ORDER_ID']);

			if($this->setEntityBinding($order, $fields['OWNER_ID'], $fields['OWNER_TYPE_ID'])
				->isSuccess())
			{
				$r = $order->save();
			}
			else
			{
				$r->addError(new Error('setEntityBinding error', 201650000010));
			}
		}
		else
		{
			$r->addError(new Error('Duplicate entry for key [ownerId, ownerTypeId, orderId]', 201650000001));
		}

		if(!$r->isSuccess())
		{
			$this->addErrors($r->getErrors());
			return null;
		}
		else
		{
			$registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);
			/** @var EntityBinding $entityBindingClassName */
			$entityBindingClassName = $registry->get(ENTITY_CRM_ORDER_ENTITY_BINDING);

			return [
				'DEAL_ORDER'=>
					$entityBindingClassName::getList([
						'filter'=>[
							'OWNER_TYPE_ID'=>$fields['OWNER_TYPE_ID'],
							'OWNER_ID'=>$fields['OWNER_ID'],
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

				$this->deleteEntityBinding($order);
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

	public function listAction(PageNavigation $pageNavigation, array $select = [], array $filter = [], array $order = []): Page
	{
		$select = empty($select)? ['*']:$select;
		$order = empty($order)? ['OWNER_ID'=>'ASC']:$order;

		$registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);
		/** @var DealBinding $entityBindingClassName */
		$entityBindingClassName = $registry->get(ENTITY_CRM_ORDER_ENTITY_BINDING);

		$tradeBindings = $entityBindingClassName::getList(
			[
				'select'=>$select,
				'filter'=>$filter,
				'order'=>$order,
				'offset' => $pageNavigation->getOffset(),
				'limit' => $pageNavigation->getLimit()
			]
		)->fetchAll();

		return new Page('ORDER_ENTITY', $tradeBindings, function() use ($filter)
		{
			$registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);
			/** @var EntityBinding $entityBindingClassName */
			$entityBindingClassName = $registry->get(ENTITY_CRM_ORDER_ENTITY_BINDING);

			return count(
				$entityBindingClassName::getList(['filter'=>$filter])->fetchAll()
			);
		});
	}
	//endregion

	protected function checkFields($fields)
	{
		$r = new Result();

		if(isset($fields['OWNER_ID']) == false && $fields['OWNER_ID'] <> '')
			$r->addError(new Error('ownerId - parameter is empty', 201640400001));

		if(isset($fields['OWNER_TYPE_ID']) == false && $fields['OWNER_TYPE_ID'] <> '')
			$r->addError(new Error('ownerTypeId - parameter is empty', 201640400002));

		if(isset($fields['ORDER_ID'])  == false && $fields['ORDER_ID'] <> '')
			$r->addError(new Error('orderId - parameter is empty', 201640400003));

		return $r;
	}

	protected function existsByFilter($filter)
	{
		$r = new \Bitrix\Main\Result();

		$registry = \Bitrix\Sale\Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);
		/** @var EntityBinding $entityBindingClassName */
		$entityBindingClassName = $registry->get(ENTITY_CRM_ORDER_ENTITY_BINDING);

		$row = $entityBindingClassName::getList([
			'filter' => [
				'=OWNER_TYPE_ID'=>$filter['OWNER_TYPE_ID'],
				'=OWNER_ID'=>$filter['OWNER_ID'],
				'=ORDER_ID'=>$filter['ORDER_ID']],
			'limit' => 1
		])->fetchAll();
		if(isset($row[0]['ORDER_ID']) == false)
			$r->addError(new Error('entity relation is not exists', 201640400004));

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
	 * @param int $entityId
	 * @return Result
	 */
	protected function setEntityBinding(Order $order, $entityId = 0, $entityTypeId = 0 ): Result
	{
		$entityId = $entityId ?? 0;
		$entityTypeId = $entityTypeId ?? 0;

		if ($entityId)
		{
			$binding = $order->getEntityBinding();

			if ($binding === null)
			{
				$binding = $order->createEntityBinding();
			}

			if ($binding)
			{
				$binding->setField('OWNER_ID', $entityId);
				$binding->setField('OWNER_TYPE_ID', $entityTypeId);
			}
		}

		return new Result();
	}

	/**
	 * @param Order $order
	 * @param int $dialId
	 * @return Result
	 */
	protected function deleteEntityBinding(Order $order): Result
	{
		$binding = $order->getEntityBinding();
		if ($binding)
		{
			$binding->delete();
		}
		return new Result();
	}

	protected function checkPermissionEntity($name, $arguments=[])
	{
		if($name == 'deletebyfilter')
		{
			$r = $this->checkModifyPermissionEntity();
		}
		else
		{
			$r = parent::checkPermissionEntity($name);
		}
		return $r;
	}

	protected function checkReadPermissionEntity(): Result
	{
		$r = new Result();

		$saleModulePermissions = self::getApplication()->GetGroupRight("sale");
		if ($saleModulePermissions  == "D")
		{
			$r->addError(new Error('Access Denied', 200040300010));
		}
		return $r;
	}

	protected function checkModifyPermissionEntity(): Result
	{
		$r = new Result();

		$saleModulePermissions = self::getApplication()->GetGroupRight("sale");
		if ($saleModulePermissions  < "W")
		{
			$r->addError(new Error('Access Denied', 200040300020));
		}
		return $r;
	}
}
