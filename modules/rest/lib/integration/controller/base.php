<?php


namespace Bitrix\Rest\Integration\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\ActionFilter\ClosureWrapper;
use Bitrix\Main\Error;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Rest\Integration\Externalizer;
use Bitrix\Rest\Integration\ViewManager;

class Base extends Controller
{
	protected $viewManager;

	/**
	 * @param $actionName
	 * @return Engine\ClosureAction|Engine\InlineAction|null
	 * @throws SystemException
	 */
	protected function create($actionName)
	{
		$action = parent::create($actionName);

		if($this->getScope() == Engine\Controller::SCOPE_REST)
		{
			$this->viewManager = $this->createViewManager($action);
		}

		return $action;
	}

	/**
	 * @param Action $action
	 * @throws NotImplementedException
	 */
	protected function createViewManager(Action $action)
	{
		throw new NotImplementedException('The method createViewManager is not implemented.');
	}

	/**
	 * @return array
	 */
	public function configureActions()
	{
		return [
			'getFields' => [
				'+prefilters' => [
					function()
					{
						/** @var ClosureWrapper $this */
						/** @var Action $action */
						$action = $this->getAction();
						if($action->getController()->getScope() !== \Bitrix\Main\Engine\Controller::SCOPE_REST)
						{
							throw new SystemException('the method is only available in the rest service');
						}
					}
				],
			],
		];
	}

	/**
	 * @return ViewManager
	 */
	public function getViewManager()
	{
		return $this->viewManager;
	}

	/**
	 * @param Action $action
	 * @return bool|null
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	protected function processBeforeAction(Engine\Action $action)
	{
		$r = $this->checkPermission($action->getName(), $action->getArguments());

		if($r->isSuccess())
		{
			if($this->getScope() == Engine\Controller::SCOPE_REST)
			{
				$internalizer = new \Bitrix\Rest\Integration\Internalizer($this->getViewManager());

				$r = $internalizer->process();

				if($r->isSuccess())
				{
					$action->setArguments($r->getData()['data']);
					return parent::processBeforeAction($action);
				}
				else
				{
					$this->addErrors($r->getErrors());
					return null;
				}
			}
			else
			{
				return parent::processBeforeAction($action);
			}
		}
		else
		{
			$this->addErrors($r->getErrors());
			return null;
		}
	}

	protected function processAfterAction(Engine\Action $action, $result)
	{
		if($this->getScope() == Engine\Controller::SCOPE_REST)
		{
			if($this->errorCollection->count()==0)
			{

				if($result instanceof Engine\Response\DataType\Page || is_array($result))
				{
					$data = $result instanceof Engine\Response\DataType\Page ?
						$result->toArray():$result;

					$externalizer = new Externalizer(
						$this->getViewManager(),
						$data
					);

					return $result instanceof Engine\Response\DataType\Page ?
						$externalizer->getPage($result):$externalizer;
				}
			}
		}

		return parent::processAfterAction($action, $result);
	}

	/**
	 * @param $name
	 * @param array $arguments
	 * @return Result
	 * @throws NotImplementedException
	 */
	private function checkPermission($name, $arguments=[])
	{


		if($name == 'add')
		{
			$r = $this->checkCreatePermissionEntity();
		}
		elseif ($name == 'update')
		{
			$r = $this->checkUpdatePermissionEntity();
		}
		elseif ($name == 'list')
		{
			$r = $this->checkReadPermissionEntity();
		}
		elseif ($name == 'getfields')
		{
			$r = $this->checkGetFieldsPermissionEntity();
		}
		elseif ($name == 'get')
		{
			$r = $this->checkReadPermissionEntity();
		}
		elseif ($name == 'delete')
		{
			$r = $this->checkDeletePermissionEntity();
		}
		else
		{
			$r = $this->checkPermissionEntity($name, $arguments);
		}

		return $r;
	}

	/**
	 * @throws NotImplementedException
	 */
	protected function checkGetFieldsPermissionEntity()
	{
		return $this->checkReadPermissionEntity();
	}

	/**
	 * @throws NotImplementedException
	 */
	protected function checkReadPermissionEntity()
	{
		throw new NotImplementedException('Check read permission. The method checkReadPermissionEntity is not implemented.');
	}

	/**
	 * @return Result
	 * @throws NotImplementedException
	 */
	protected function checkModifyPermissionEntity()
	{
		throw new NotImplementedException('Check modify permission. The method checkModifyPermissionEntity is not implemented.');
	}

	/**
	 * @return Result
	 * @throws NotImplementedException
	 */
	protected function checkCreatePermissionEntity()
	{
		return $this->checkModifyPermissionEntity();
	}

	/**
	 * @return Result
	 * @throws NotImplementedException
	 */
	protected function checkUpdatePermissionEntity()
	{
		return $this->checkModifyPermissionEntity();
	}

	/**
	 * @return Result
	 * @throws NotImplementedException
	 */
	protected function checkDeletePermissionEntity()
	{
		return $this->checkModifyPermissionEntity();
	}

	/**
	 * @param $name
	 * @param array $arguments
	 * @return Result
	 * @throws NotImplementedException
	 */
	protected function checkPermissionEntity($name, $arguments=[])
	{
		throw new NotImplementedException('Check permission entity. The method '.$name.' is not implemented.');
	}

	/**
	 * @return \Bitrix\Main\Entity\DataManager
	 * @throws NotImplementedException
	 */
	protected function getEntityTable()
	{
		throw new NotImplementedException('The method getEntityTable is not implemented.');
	}

	/**
	 * @param $select
	 * @param $filter
	 * @param $order
	 * @param PageNavigation $pageNavigation
	 * @return array
	 * @throws NotImplementedException
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 */
	protected function getList($select, $filter, $order, PageNavigation $pageNavigation)
	{
		$entityTable = $this->getEntityTable();

		$select = empty($select)? ['*']:$select;
		$order = empty($order)? ['ID'=>'ASC']:$order;

		$items = $entityTable::getList(
			[
				'select'=>$select,
				'filter'=>$filter,
				'order'=>$order,
				'offset' => $pageNavigation->getOffset(),
				'limit' => $pageNavigation->getLimit()
			]
		)->fetchAll();

		return $items;
	}

	/**
	 * @param $filter
	 * @return int
	 * @throws NotImplementedException
	 * @throws SystemException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 */
	protected function count($filter)
	{
		$entityTable = $this->getEntityTable();

		return $entityTable::getCount([$filter]);
	}

	/**
	 * @param $id
	 * @return array|false
	 * @throws NotImplementedException
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 */
	protected function get($id)
	{
		$entityTable = $this->getEntityTable();

		return $entityTable::getById($id)->fetch();
	}

	/**
	 * @param array $fields
	 * @return \Bitrix\Main\ORM\Data\AddResult
	 * @throws NotImplementedException
	 */
	public function add(array $fields)
	{
		$entityTable = $this->getEntityTable();
		return $entityTable::add($fields);
	}

	/**
	 * @param $id
	 * @param array $fields
	 * @return \Bitrix\Main\ORM\Data\UpdateResult|null
	 * @throws NotImplementedException
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 */
	protected function update($id, array $fields)
	{
		$entityTable = $this->getEntityTable();

		/** @var \Bitrix\Main\Result $r */
		$r = $this->exists($id);
		if($r->isSuccess())
		{
			return $entityTable::update($id, $fields);
		}
		else
		{
			$this->addErrors($r->getErrors());
			return null;
		}
	}

	/**
	 * @param $id
	 * @return \Bitrix\Main\ORM\Data\DeleteResult|null
	 * @throws NotImplementedException
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 */
	protected function delete($id)
	{
		$entityTable = $this->getEntityTable();

		$r = $this->exists($id);
		if($r->isSuccess())
		{
			return $entityTable::delete($id);
		}
		else
		{
			$this->addErrors($r->getErrors());
			return null;
		}
	}

	/**
	 * @param $id
	 * @return Result
	 * @throws NotImplementedException
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 */
	protected function exists($id)
	{
		$r = new \Bitrix\Main\Result();
		if(isset($this->get($id)['ID']) == false)
			$r->addError(new Error('Entity is not exists'));

		return $r;
	}

	/**
	 * @param $filter
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function existsByFilter($filter)
	{
		$r = new Result();
		$ar = $this->getEntityTable()::getList(['filter'=>$filter])->fetchAll();

		if(count($ar) <= 0)
			$r->addError(new Error('Entity is not exists'));
		return $r;
	}
}