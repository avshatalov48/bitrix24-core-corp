<?php

namespace Bitrix\HumanResources\Service;

use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\NodeMember;

class RoleHelperService implements Contract\Service\RoleHelperService
{
	private readonly Contract\Repository\RoleRepository $roleRepository;

	public function __construct(
		?Contract\Repository\RoleRepository $roleRepository = null,
	)
	{
		$this->roleRepository = $roleRepository ?? Container::getRoleRepository();
	}

	public function getById(int $roleId): ?Item\Role
	{
		return $this->roleRepository->getById($roleId);
	}

	public function getEmployeeRoleId(): ?int
	{
		return $this->roleRepository->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['EMPLOYEE'])?->id;
	}

	public function getHeadRoleId(): ?int
	{
		return $this->roleRepository->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['HEAD'])?->id;
	}
}