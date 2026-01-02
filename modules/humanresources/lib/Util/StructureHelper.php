<?php

namespace Bitrix\HumanResources\Util;

use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\NodeEntityType;
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
	 *     heads: array<int, array{
	 *         id: int,
	 *         name: string,
	 *         avatar: string,
	 *         url: string,
	 *         role: string,
	 *         workPosition: string|null
	 *     }>,
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
			'entityType' => $node->type->value,
			'colorName' => $node->colorName,
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
		$headRole = null;

		if ($node->type === NodeEntityType::DEPARTMENT)
		{
			static $departmentHeadRole = null;
			if (!$departmentHeadRole)
			{
				$departmentHeadRole = $roleRepository->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['HEAD']);
			}

			$headRole = $departmentHeadRole;
		}

		if ($node->type === NodeEntityType::TEAM)
		{
			static $teamHeadRole = null;
			if (!$teamHeadRole)
			{
				$teamHeadRole = $roleRepository->findByXmlId(NodeMember::TEAM_ROLE_XML_ID['TEAM_HEAD']);
			}

			$headRole = $teamHeadRole;
		}

		$nodeMemberRepository = Container::getNodeMemberRepository();
		$userService = Container::getUserService();
		if ($headRole)
		{
			$headEmployees = $nodeMemberRepository->findAllByRoleIdAndNodeId($headRole->id, $node->id);
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

		$deputyHeadRole = null;
		if ($node->type === NodeEntityType::DEPARTMENT)
		{
			static $departmentDeputyHeadRole = null;
			if (!$departmentDeputyHeadRole)
			{
				$departmentDeputyHeadRole = $roleRepository->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['DEPUTY_HEAD']);
			}

			$deputyHeadRole = $departmentDeputyHeadRole;
		}

		if ($node->type === NodeEntityType::TEAM)
		{
			static $teamDeputyHeadRole = null;
			if (!$teamDeputyHeadRole)
			{
				$teamDeputyHeadRole = $roleRepository->findByXmlId(NodeMember::TEAM_ROLE_XML_ID['TEAM_DEPUTY_HEAD']);
			}

			$deputyHeadRole = $teamDeputyHeadRole;
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