<?php
namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Order\EntityBinding;
use Bitrix\Crm\Order\Order;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\PageNavigation;
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
		/** @var \Bitrix\Crm\RestView\OrderEntity $view */
		$view = $this->getViewManager()->getView($this);

		return [
			'ORDER_ENTITY' => $view->prepareFieldInfos($view->getFields()),
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
		$internalResult = $this->checkFields($fields);
		if (!$internalResult->isSuccess())
		{
			$this->addErrors($internalResult->getErrors());

			return null;
		}

		$internalResult = $this->existsByFilter($fields);
		if ($internalResult->isSuccess()) // already exists
		{
			$this->addError(new Error(
				'Duplicate entry for key [ownerId, ownerTypeId, orderId]',
				201650000001
			));

			return null;
		}

		/** @var Order $order */
		$order = $this->loadOrder($fields['ORDER_ID']);
		if ($order === null)
		{
			return null;
		}

		$internalResult = $this->setEntityBinding($order, $fields['OWNER_ID'], $fields['OWNER_TYPE_ID']);
		if (!$internalResult->isSuccess())
		{
			$this->addError(new Error('setEntityBinding error', 201650000010));

			return null;
		}

		$result = $order->save();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		$registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);
		/** @var EntityBinding $entityBindingClassName */
		$entityBindingClassName = $registry->get(ENTITY_CRM_ORDER_ENTITY_BINDING);

		$iterator = $entityBindingClassName::getList([
			'select' => ['*'],
			'filter'=>[
				'OWNER_TYPE_ID'=>$fields['OWNER_TYPE_ID'],
				'OWNER_ID'=>$fields['OWNER_ID'],
				'ORDER_ID'=>$fields['ORDER_ID'],
			],
			'limit' => 1,
		]);
		$deal = $iterator->fetch();
		unset($iterator);

		return [
			'DEAL_ORDER' => $deal,
		];
	}

	/**
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function deleteByFilterAction($fields): ?bool
	{
		$result = $this->checkFields($fields);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		$result = $this->existsByFilter($fields);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		/** @var Order $order */
		$order = $this->loadOrder($fields['ORDER_ID']);
		if ($order === null)
		{
			return null;
		}

		$this->deleteEntityBinding($order);
		$result = $order->save();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}

	//@codingStandardsIgnoreStart
	public function listAction(
		PageNavigation $pageNavigation,
		array $select = [],
		array $filter = [],
		array $order = [],
		bool $__calculateTotalCount = true
	): ?Page
	{
		if (!Loader::includeModule('sale'))
		{
			$this->addErrorModuleSaleAbsent();

			return null;
		}

		$select = empty($select) ? ['*'] : $select;
		$order = empty($order) ? ['OWNER_ID' => 'ASC'] : $order;

		$registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);
		/** @var EntityBinding $entityBindingClassName */
		$entityBindingClassName = $registry->get(ENTITY_CRM_ORDER_ENTITY_BINDING);

		$iterator = $entityBindingClassName::getList([
			'select' => $select,
			'filter' => $filter,
			'order' => $order,
			'offset' => $pageNavigation->getOffset(),
			'limit' => $pageNavigation->getLimit(),
			'count_total' => $__calculateTotalCount,
		]);
		$tradeBindings = $iterator->fetchAll();
		$totalCount = $__calculateTotalCount ? $iterator->getCount() : 0;
		unset($iterator);

		return new Page('ORDER_ENTITY', $tradeBindings, $totalCount);
	}
	//@codingStandardsIgnoreEnd
	//endregion

	protected function checkFields($fields): Result
	{
		$result = new Result();

		if (($fields['OWNER_ID'] ?? 0) <= 0)
		{
			$result->addError(new Error('ownerId - parameter is empty', 201640400001));
		}

		if (($fields['OWNER_TYPE_ID'] ?? 0) <= 0)
		{
			$result->addError(new Error('ownerTypeId - parameter is empty', 201640400002));
		}

		if (($fields['ORDER_ID'] ?? 0) <= 0)
		{
			$result->addError(new Error('orderId - parameter is empty', 201640400003));
		}

		return $result;
	}

	protected function existsByFilter($filter)
	{
		$result = new \Bitrix\Main\Result();

		$registry = \Bitrix\Sale\Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);
		/** @var EntityBinding $entityBindingClassName */
		$entityBindingClassName = $registry->get(ENTITY_CRM_ORDER_ENTITY_BINDING);

		$row = $entityBindingClassName::getList([
			'select' => [
				'ORDER_ID'
			],
			'filter' => [
				'=OWNER_TYPE_ID' => $filter['OWNER_TYPE_ID'],
				'=OWNER_ID' => $filter['OWNER_ID'],
				'=ORDER_ID' => $filter['ORDER_ID'],
			],
			'limit' => 1,
		])->fetch();
		if (empty($row))
		{
			$result->addError(new Error('entity relation is not exists', 201640400004));
		}

		return $result;
	}

	protected function loadOrder($id): ?Order
	{
		if (!Loader::includeModule('sale'))
		{
			$this->addErrorModuleSaleAbsent();

			return null;
		}

		$registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);

		/** @var Order $orderClass */
		$orderClass = $registry->getOrderClassName();

		/** @var null|Order $order */
		$order = $orderClass::load($id);
		if ($order)
		{
			return $order;
		}

		$this->addError(new Error('order does not exist', 200540400001));

		return null;
	}

	/**
	 * @param Order $order
	 * @param int $entityId
	 * @param int $entityTypeId
	 * @return Result
	 */
	protected function setEntityBinding(Order $order, int $entityId = 0, int $entityTypeId = 0): Result
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

	protected function checkPermissionEntity($name, $arguments = [])
	{
		if ($name === 'deletebyfilter')
		{
			$result = $this->checkModifyPermissionEntity();
		}
		else
		{
			$result = parent::checkPermissionEntity($name);
		}

		return $result;
	}

	protected function checkReadPermissionEntity(): Result
	{
		$result = new Result();

		$saleModulePermissions = self::getApplication()->GetGroupRight('sale');
		if ($saleModulePermissions === 'D')
		{
			$result->addError(new Error('Access Denied', 200040300010));
		}

		return $result;
	}

	protected function checkModifyPermissionEntity(): Result
	{
		$result = new Result();

		$saleModulePermissions = self::getApplication()->GetGroupRight('sale');
		if ($saleModulePermissions  < 'W')
		{
			$result->addError(new Error('Access Denied', 200040300020));
		}

		return $result;
	}

	private function getErrorModuleSaleAbsent(): Error
	{
		return new Error('module sale does not exist', '200540400002');
	}

	private function addErrorModuleSaleAbsent(): void
	{
		$this->addError($this->getErrorModuleSaleAbsent());
	}
}
