<?php

namespace Bitrix\Tasks\Flow\Control\Middleware\Implementation;

use Bitrix\Main\Loader;
use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Flow\Control\Exception\MiddlewareException;
use Bitrix\Tasks\Flow\Control\Middleware\AbstractMiddleware;
use Bitrix\Tasks\Integration\Intranet\Flow\Department;

class DepartmentMiddleware extends AbstractMiddleware
{
	/**
	 * @throws MiddlewareException
	 */
	public function handle(AbstractCommand $request)
	{
		$departmentIds = $request->getDepartmentIdList();
		$departments = $this->load(...$departmentIds);

		foreach ($departments as $departmentId => $departmentTitle)
		{
			if ($departmentTitle === null)
			{
				throw new MiddlewareException("Department {$departmentId} doesn't exists");
			}
		}

		return parent::handle($request);
	}

	private function load(int ...$departmentIds): array
	{
		if (!Loader::includeModule('intranet'))
		{
			return [];
		}

		return Department::getDepartmentsData(...$departmentIds);
	}
}