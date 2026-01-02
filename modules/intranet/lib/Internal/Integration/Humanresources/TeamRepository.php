<?php

namespace Bitrix\Intranet\Internal\Integration\Humanresources;

use Bitrix\HumanResources\Builder\Structure\Filter\Column\EntityIdFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\Column\IdFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\Column\Node\NodeTypeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\NodeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\NodeMemberFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\SelectionCondition\Node\NodeAccessFilter;
use Bitrix\HumanResources\Builder\Structure\NodeDataBuilder;
use Bitrix\HumanResources\Builder\Structure\NodeMemberDataBuilder;
use Bitrix\HumanResources\Item\Collection;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Type\StructureAction;
use Bitrix\Main;

class TeamRepository
{
	private bool $available;

	public function __construct()
	{
		$this->available = Main\Loader::includeModule('humanresources');
	}

	public function getAllByUserId(int $userId): ?Collection\NodeCollection
	{
		if (!$this->available)
		{
			return null;
		}

		if (!$userId)
		{
			return null;
		}

		$nodeDataBuilder = new NodeMemberDataBuilder();
		$filter =
			new NodeMemberFilter(
				entityIdFilter: EntityIdFilter::fromEntityId($userId),
				nodeFilter: new NodeFilter(
					entityTypeFilter: NodeTypeFilter::fromNodeType(NodeEntityType::TEAM),
				),
				findRelatedMembers: false,
			)
		;

		$userTeamMembers =
			$nodeDataBuilder
				->setFilter($filter)
				->getAll()
		;

		$teamIds = $userTeamMembers->getNodeIds();
		if (empty($teamIds))
		{
			return null;
		}

		return
			NodeDataBuilder::createWithFilter(
				new NodeFilter(
					IdFilter::fromIds($teamIds),
					entityTypeFilter: NodeTypeFilter::fromNodeType(NodeEntityType::TEAM),
					depthLevel: 0,
					accessFilter: new NodeAccessFilter(StructureAction::ViewAction),
				)
			)
			->getAll()
		;
	}
}
