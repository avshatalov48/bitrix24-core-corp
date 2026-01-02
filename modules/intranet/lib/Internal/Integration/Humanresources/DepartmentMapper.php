<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Integration\Humanresources;

use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Item\Structure;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\Intranet\Entity\Department;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\SystemException;

class DepartmentMapper
{
	public function createDepartmentFromNode(Node $node): \Bitrix\Intranet\Entity\Department
	{
		return new \Bitrix\Intranet\Entity\Department(
			name: $node->name,
			id: $node->id,
			parentId: $node->parentId,
			createdBy: $node->createdBy,
			createdAt: $node->createdAt,
			updatedAt: $node->updatedAt,
			xmlId: $node->xmlId,
			sort: $node->sort,
			isActive: $node->active,
			isGlobalActive: $node->globalActive,
			depth: $node->depth,
			accessCode: $node->accessCode,
			isIblockSource: false,
		);
	}

	/**
	 * @throws SystemException
	 * @throws LoaderException
	 */
	public function createNodeFromDepartment(Department $department): Node
	{
		if (!Loader::includeModule('humanresources'))
		{
			throw new SystemException('The "humanresources" module is not installed');
		}

		$structure = Container::getStructureRepository()
			->getByXmlId(Structure::DEFAULT_STRUCTURE_XML_ID);

		if (!$structure?->id)
		{
			throw new SystemException('Company structure record is not found');
		}

		return new Node(
			$department->getName(),
			NodeEntityType::DEPARTMENT,
			$structure->id,
			id: $department->getId(),
			parentId: $department->getParentId(),
			depth: $department->getDepth(),
			xmlId: $department->getXmlId(),
			active: $department->isActive(),
			globalActive: $department->isGlobalActive(),
			sort: $department->getSort(),
		);
	}
}