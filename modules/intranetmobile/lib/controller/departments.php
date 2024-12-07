<?php
namespace Bitrix\IntranetMobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Intranet\ActionFilter\AdminUser;

class Departments extends Base
{
	public function configureActions(): array
	{
		return [
			'createDepartment' => [
				'+prefilters' => [
					new CloseSession(),
					new AdminUser()
				],
			],
			'getRootDepartment' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function createDepartmentAction(string $name, int $parentDepartmentId, int $headOfDepartmentId)
	{
		$department = new \Bitrix\Intranet\Entity\Department(
			$name,
			parentId: $parentDepartmentId,
		);

		$departmentRepository = ServiceContainer::getInstance()->departmentRepository();

		$department = $departmentRepository->save($department);
		$departmentRepository->setHead($department->getId(), $headOfDepartmentId);

		return [
			'id' => $department->getId(),
		];
	}

	public function getRootDepartmentAction()
	{
		$departmentRepository = ServiceContainer::getInstance()->departmentRepository();
		$rootDepartment = $departmentRepository->getRootDepartment();

		return [
			'id' => $rootDepartment->getId(),
			'name' => $rootDepartment->getName(),
		];
	}
}