<?php

namespace Bitrix\HumanResources\Public\Service;

use Bitrix\HumanResources\Repository\NodeRepository;
use Bitrix\HumanResources\Repository\NodeSettingsRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Type\NodeSettingsAuthorityType;
use Bitrix\HumanResources\Type\NodeSettingsAuthorityTypeCollection;
use Bitrix\HumanResources\Type\NodeSettingsType;

class NodeSettingsService
{
	private NodeSettingsRepository $nodeSettingsRepository;

	public function __construct(?NodeSettingsRepository $nodeSettingsRepository = null)
	{
		$this->nodeSettingsRepository = $nodeSettingsRepository ?? Container::getNodeSettingsRepository();
	}

	/**
	 * Get NodeSettingsType::BusinessProcTeamRule settings values
	 *
	 * @param int $nodeId
	 * @return NodeSettingsAuthorityTypeCollection
	 * @throws \Bitrix\HumanResources\Exception\WrongStructureItemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getBusinessProcAuthoritySettings(int $nodeId): NodeSettingsAuthorityTypeCollection
	{
		$entityCollection = $this->nodeSettingsRepository->getByNodeAndTypes($nodeId, [NodeSettingsType::BusinessProcAuthority]);

		$authorityCollection = new NodeSettingsAuthorityTypeCollection();
		foreach ($entityCollection as $entity)
		{
			$authorityCollection->add(NodeSettingsAuthorityType::tryFrom($entity->settingsValue));
		}

		return $authorityCollection;
	}
}
