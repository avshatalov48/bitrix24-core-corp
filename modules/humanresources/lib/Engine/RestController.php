<?php

namespace Bitrix\HumanResources\Engine;

use Bitrix\HumanResources\Rest\View\HumanResourcesViewManager;
use Bitrix\Main;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Error;
use Bitrix\Rest\Integration\Controller\Base;

abstract class RestController extends Base
{
	protected function getDefaultPreFilters(): array
	{
		return [
			new Main\Engine\ActionFilter\Scope(Main\Engine\ActionFilter\Scope::REST),
		];
	}

	/**
	 * @param Error $error
	 * @return null
	 */
	protected function responseWithError(Main\Error $error)
	{
		$this->addError($error);
		return null;
	}

	/**
	 * @param array<Main\Error> $error
	 * @return null
	 */
	protected function responseWithErrors(array $error)
	{
		$this->addErrors($error);
		return null;
	}

	protected function getViewFields(): ?array
	{
		$view =
			$this
				->getViewManager()
				->getView($this)
		;

		if (!$view)
		{
			return null;
		}

		return $view->prepareFieldInfos($view->getFields());
	}

	protected function createViewManager(Action $action)
	{
		return new HumanResourcesViewManager($action);
	}

	/* @todo implement right check in concrete controllers */
	protected function checkCreatePermissionEntity(): Main\Result
	{
		return new Main\Result();
	}

	protected function checkDeletePermissionEntity(): Main\Result
	{
		return new Main\Result();
	}

	protected function checkGetFieldsPermissionEntity(): Main\Result
	{
		return new Main\Result();
	}

	protected function checkUpdatePermissionEntity(): Main\Result
	{
		return new Main\Result();
	}

	protected function checkModifyPermissionEntity(): Main\Result
	{
		return new Main\Result();
	}

	protected function checkReadPermissionEntity(): Main\Result
	{
		return new Main\Result();
	}

	protected function checkPermissionEntity($name, $arguments = []): Main\Result
	{
		return new Main\Result();
	}
}