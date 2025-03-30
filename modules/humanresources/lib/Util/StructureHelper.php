<?php

namespace Bitrix\HumanResources\Util;

use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class StructureHelper
{
	public static function getDefaultStructure(): ?Item\Structure
	{
		static $structure = null;

		if (!$structure)
		{
			$structure = Container::getStructureRepository()
				->getByXmlId(Item\Structure::DEFAULT_STRUCTURE_XML_ID);
			;
		}

		if (!$structure)
		{
			return null;
		}

		return $structure;
	}
	/**
	 * @return Node|null
	 * @throws WrongStructureItemException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function getRootStructureDepartment(): ?Item\Node
	{
		static $rootDepartment = null;

		if ($rootDepartment)
		{
			return $rootDepartment;
		}

		if ($structure = self::getDefaultStructure())
		{
			$rootDepartment =  Container::getNodeRepository()->getRootNodeByStructureId($structure->id);

		}

		return $rootDepartment;
	}

	/**
	 * returns an array of department data along with the data of the heads
	 * @return array{
	 *     id: int,
	 *     parentId: int|null,
	 *     name: string,
	 *     description: string|null,
	 *     heads: array{
	 *         id: int,
	 *         name: string,
	 *         avatar: string,
	 *         url: string,
	 *         role: string,
	 *         workPosition: string|null
	 *     },
	 *     userCount: int
	 * }
	 */
	public static function getNodeInfo(Item\Node $node, bool $withHeads = false): array
	{
		$nodeMemberRepository = Container::getNodeMemberRepository();
		static $countByStructureId = [];

		if (!isset($countByStructureId[$node->structureId]))
		{
			$structure = Container::getStructureRepository()->getById($node->structureId);
			if ($structure)
			{
				$countByStructureId[$node->structureId] = $nodeMemberRepository->countAllByStructureAndGroupByNode($structure);
			}
		}

		$result = [
			'id' => $node->id,
			'parentId' => $node->parentId,
			'name' => $node->name,
			'description' => $node->description ?? '',
			'accessCode' => $node->accessCode,
			'userCount' => $countByStructureId[$node->structureId][$node->id] ?? 0,
		];

		if ($withHeads)
		{
			$result['heads'] = self::getNodeHeads($node);
		}

		return $result;
	}

	public static function getNodeHeads(Item\Node $node): array
	{
		$headUsers = [];
		$roleRepository  = Container::getRoleRepository();
		static $headRole = null;

		if (!$headRole)
		{
			$headRole = $roleRepository->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['HEAD']);
		}

		$nodeMemberService = Container::getNodeMemberService();
		$userService = Container::getUserService();
		if ($headRole)
		{
			$headEmployees = $nodeMemberService->getDefaultHeadRoleEmployees($node->id);
			if (!$headEmployees->empty())
			{
				$headUserCollection = $userService->getUserCollectionFromMemberCollection($headEmployees);
				foreach ($headUserCollection as $user)
				{
					$baseUserInfo = $userService->getBaseInformation($user);
					$baseUserInfo['role'] = $headRole->xmlId;
					$headUsers[] = $baseUserInfo;
				}
			}
		}

		$nodeMemberRepository = Container::getNodeMemberRepository();

		static $deputyHeadRole = null;

		if (!$deputyHeadRole)
		{
			$deputyHeadRole = $roleRepository->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['DEPUTY_HEAD']);
		}

		if ($deputyHeadRole)
		{
			$deputyHeadEmployees = $nodeMemberRepository->findAllByRoleIdAndNodeId($deputyHeadRole->id, $node->id);
			if (!$deputyHeadEmployees->empty())
			{
				$deputyHeadUserCollection = $userService->getUserCollectionFromMemberCollection($deputyHeadEmployees);
				foreach ($deputyHeadUserCollection as $user)
				{
					$baseUserInfo = $userService->getBaseInformation($user);
					$baseUserInfo['role'] = $deputyHeadRole->xmlId;
					$headUsers[] = $baseUserInfo;
				}
			}
		}

		return $headUsers;
	}
}