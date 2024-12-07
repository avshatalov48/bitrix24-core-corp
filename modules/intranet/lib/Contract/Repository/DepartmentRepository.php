<?php

namespace Bitrix\Intranet\Contract\Repository;

use Bitrix\Intranet\Enum\DepartmentActiveFilter;
use Bitrix\Intranet\Enum\DepthLevel;
use Bitrix\Intranet\User;
use Bitrix\Intranet\Entity\Department;
use Bitrix\Intranet\Entity\Collection\DepartmentCollection;

interface DepartmentRepository
{
	public function getDepartmentHead(int $departmentId): ?User;

	public function getRootDepartment(): ?Department;

	public function getDepartmentsByName(?string $name = null, int $limit = 100): DepartmentCollection;

	public function findAllByIds(array $ids): DepartmentCollection;

	public function findAllByXmlId(string $xmlId): DepartmentCollection;

	public function getDepartmentByHeadId(
		int $headId,
		DepartmentActiveFilter $activeFilter = DepartmentActiveFilter::ALL
	): DepartmentCollection;

	public function setHead(int $departmentId, int $userId): void;

	public function unsetHead(int $departmentId): void;

	public function save(Department $department): Department;

	public function getById(int $departmentId): ?Department;

	public function delete(int $departmentId): void;

	public function getAllTree(
		Department $rootDepartment = null,
		DepthLevel $depthLevel = DepthLevel::FULL,
		DepartmentActiveFilter $activeFilter = DepartmentActiveFilter::ALL
	): DepartmentCollection;
}