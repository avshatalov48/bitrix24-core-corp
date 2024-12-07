<?php

namespace Bitrix\HumanResources\Controller\Structure\Node\Member;

use Bitrix\HumanResources\Access\StructureAccessController;
use Bitrix\HumanResources\Access\StructureActionDictionary;
use Bitrix\HumanResources\Attribute;
use Bitrix\HumanResources\Contract\Repository\NodeMemberRepository;
use Bitrix\HumanResources\Engine\Controller;
use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Repository\RoleRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Type\AccessibleItemType;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Request;

final class Role extends Controller
{
	private readonly NodeMemberRepository $nodeMemberRepository;
	private readonly RoleRepository $roleRepository;

	public function __construct(Request $request = null)
	{
		$this->nodeMemberRepository = Container::getNodeMemberRepository();
		$this->roleRepository = Container::getRoleRepository();
		parent::__construct($request);
	}

	#[Attribute\StructureActionAccess(
		permission: StructureActionDictionary::ACTION_DEPARTMENT_EDIT,
		itemType: AccessibleItemType::NODE_MEMBER,
		itemIdRequestKey: 'memberId',
	)]
	public function setAction(
		Item\NodeMember $nodeMember,
		Item\Role $role,
	): array
	{
		$nodeMember->role = $role->id;

		try
		{
			$this->nodeMemberRepository->update($nodeMember);
		}
		catch (UpdateFailedException $e)
		{
			$this->addErrors($e->getErrors()->toArray());
		}

		return [
			$nodeMember,
		];
	}

	public function listAction(int $limit = 50, int $offset = 0): array
	{
		return [
			$this->roleRepository->list($limit, $offset)->getItemMap(),
		];
	}
}