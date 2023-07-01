<?php

namespace Bitrix\Tasks\Rest\Controllers\ViewedGroup;

use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Result;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Rest\Integration\Controller;
use Bitrix\Rest\Integration\TaskViewManager;
use Bitrix\Tasks\Internals\Task\ViewedGroupTable;

class Base extends Controller\Base
{
	protected const ITEM = '';
	protected const LIST = '';

	protected const VIEWED_TYPE = '';

	//region Actions
	public function getFieldsAction()
	{
		$view = $this->getViewManager()
			->getView($this);

		return [
			static::ITEM => $view->prepareFieldInfos(
				$view->getFields()
			)
		];
	}

	public function listAction($select = [], $filter = [], $order = [], PageNavigation $pageNavigation = null)
	{
		$filter['TYPE_ID'] = static::VIEWED_TYPE;
		$order = empty($order) ? ['GROUP_ID' => 'ASC'] : $order;

		return new Page(static::LIST,
			$this->getList($select, $filter, $order, $pageNavigation),
			$this->count($filter)
		);
	}


	protected function createViewManager(Action $action)
	{
		return new TaskViewManager($action);
	}

	protected function getEntityTable()
	{
		return new ViewedGroupTable();
	}

	// endregion

	protected function checkReadPermissionEntity()
	{
		return new Result();
	}

	protected function checkCreatePermissionEntity()
	{
		return new Result();
	}

	protected function checkPermissionEntity($name, $arguments=[])
	{
		$name = mb_strtolower($name);

		if($name == 'markasread')
		{
			$r = $this->checkCreatePermissionEntity();
		}
		else
		{
			$r = parent::checkPermissionEntity($name);
		}

		return $r;
	}
}