<?php

namespace Bitrix\HumanResources\Controller;

use Bitrix\HumanResources\Access\StructureActionDictionary;
use Bitrix\HumanResources\Contract\Repository\NodeMemberRepository;
use Bitrix\HumanResources\Contract\Service\NodeMemberService;
use Bitrix\HumanResources\Contract\Service\UserService;
use Bitrix\HumanResources\Engine\Controller;
use Bitrix\HumanResources\Attribute;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Request;
use Bitrix\Main\SystemException;

final class Structure extends Controller
{
	private readonly NodeRepository $nodeRepository;
	private readonly NodeMemberService $nodeMemberService;
	private readonly UserService $userService;
	private readonly NodeMemberRepository $nodeMemberRepository;

	public function __construct(Request $request = null)
	{
		$this->nodeMemberService = Container::getNodeMemberService();
		$this->userService = Container::getUserService();
		$this->nodeRepository = Container::getNodeRepository();
		$this->nodeMemberRepository = Container::getNodeMemberRepository();
		parent::__construct($request);
	}

	#[Attribute\StructureActionAccess(StructureActionDictionary::ACTION_STRUCTURE_VIEW)]
	public function getAction(Item\Structure $structure): ?array
	{
		try
		{
			$nodes = $this->nodeRepository->getAllByStructureId($structure->id);
		}
		catch (WrongStructureItemException $e)
		{
			$this->addErrors($e->getErrors()->toArray());

			return [];
		}
		catch (ObjectPropertyException|ArgumentException|SystemException $e)
		{
			return [];
		}

		$result = [];
		foreach ($nodes as $node)
		{
			$headEmployees = $this->nodeMemberService->getDefaultHeadRoleEmployees($node->id);
			$userCollection = $this->userService->getUserCollectionFromMemberCollection($headEmployees);

			$headUsers = [];
			foreach ($userCollection as $user)
			{
				$headUsers[] = [
					'id' => $user->id,
					'name' => $this->userService->getUserName($user),
					'avatar' => $this->userService->getUserAvatar($user, 45),
				];
			}

			$result[] = [
				'id' => $node->id,
				'parentId' => $node->parentId,
				'name' => $node->name,
				'headUsers' => $headUsers,
				'employeesCount' => $this->nodeMemberRepository->countAllByByNodeId($node->id),
			];
		}

		return $result;
	}
}