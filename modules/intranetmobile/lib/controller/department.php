<?php

namespace Bitrix\IntranetMobile\Controller;

use Bitrix\Intranet\ActionFilter\IntranetUser;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\IntranetMobile\Provider\DepartmentProvider;

class Department extends Base
{
	public function configureActions(): array
	{
		return [
			'getParents' => [
				'+prefilters' => [
					new CloseSession(),
					new IntranetUser(),
				],
			],
		];
	}

	protected function getQueryActionNames(): array
	{
		return [
			'getParents',
		];
	}

	/**
	 * @restMethod intranetmobile.department.getParents
	 * @return array
	 */
	public function getParentsAction(int $departmentId): array
	{
		return (new DepartmentProvider())->getParents($departmentId);
	}
}
