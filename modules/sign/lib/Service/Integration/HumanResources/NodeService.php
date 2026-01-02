<?php

namespace Bitrix\Sign\Service\Integration\HumanResources;

use Bitrix\HumanResources\Config\Storage;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Contract\Service\NodeMemberService as NodeMemberServiceContract;
use Bitrix\HumanResources\Item\Collection\NodeMemberCollection;
use Bitrix\HumanResources\Contract\Service\NodeService as NodeServiceContract;
use Bitrix\Main\Loader;
use Bitrix\Sign\Item\Hr\EntitySelector\Entity;
use Bitrix\Sign\Type\Hr\EntitySelector\EntityType;

final class NodeService
{
	private ?NodeServiceContract $nodeService = null;
	private ?NodeMemberServiceContract $nodeMemberService = null;

	public function __construct()
	{
		if (!$this->isAvailable())
		{
			return;
		}

		$this->nodeService = Container::instance()->getNodeService();
		$this->nodeMemberService = Container::instance()->getNodeMemberService();
	}

	private function isAvailable(): bool
	{
		return Loader::includeModule('humanresources');
	}

	public function isNodeExists(int $nodeId): bool
	{
		if (!$this->isAvailable())
		{
			return false;
		}

		if ($nodeId < 1)
		{
			return false;
		}

		return $this->nodeService?->getNodeInformation($nodeId) !== null;
	}

	public function getAllEmployeesByEntitySelector(Entity $entity): ?NodeMemberCollection
	{
		if (!$this->isAvailable())
		{
			return null;
		}

		if ($entity->entityId < 1)
		{
			return null;
		}

		return $this->nodeMemberService?->getAllEmployees(
			$entity->entityId,
			$entity->entityType !== EntityType::FlatDepartment,
		);
	}

	public function isCompanyStructureConverted(): bool
		{
			if (!$this->isAvailable())
			{
				return false;
			}

			return Storage::instance()->isCompanyStructureConverted();
		}
}