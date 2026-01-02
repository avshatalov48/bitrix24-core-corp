<?php

namespace Bitrix\HumanResources\Access\AuthProvider;

use Bitrix\HumanResources\Builder\Structure\Filter\Column\EntityIdFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\Column\IdFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\Column\Node\NodeTypeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\NodeFilter;
use Bitrix\HumanResources\Builder\Structure\Filter\NodeMemberFilter;
use Bitrix\HumanResources\Builder\Structure\NodeDataBuilder;
use Bitrix\HumanResources\Builder\Structure\NodeMemberDataBuilder;
use Bitrix\HumanResources\Enum\DepthLevel;
use Bitrix\HumanResources\Enum\Direction;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Type\AccessCodeType;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\Main\Application;
use Bitrix\Main\UserAccessTable;
use CAuthProvider;

/**
 * @see \CAccess provider is instantiated via the OnAuthProvidersBuildList event
 * @see IProviderInterface not implemented because teams do not need to be shown in the admin panel. todo implement
 * @see AccessCodeType available access code types
 */
class StructureAuthProvider extends CAuthProvider
{
	private const PROVIDER_ID = 'hr_structure';
	private const LOCK_NAME = 'auth_provider_lock';
	private const CHUNK_SIZE = 500;
	private ?int $structureId = null;

	public function __construct()
	{
		$this->id = self::PROVIDER_ID;
	}

	/**
	 * @param string[] $arCodes
	 * @return list<array{provider:string, name:string}>
	 */
	public function GetNames($arCodes): array
	{
		if (!is_array($arCodes) || empty($arCodes))
		{
			return [];
		}

		$pattern = self::buildAccessCodePattern(
			AccessCodeType::HrTeamType,
			AccessCodeType::HrTeamRecursiveType,
			AccessCodeType::HrDepartmentType,
			AccessCodeType::HrDepartmentRecursiveType,
		);

		$nodeIds = array_filter(
			array_map(
				static fn($code) => preg_match($pattern, $code, $m) ? (int)$m[2] : null,
				$arCodes,
			),
		);

		if ($nodeIds === [])
		{
			return [];
		}

		$nodes = (new NodeDataBuilder())
			->setFilter(
				new NodeFilter(
					idFilter: IdFilter::fromIds($nodeIds),
					entityTypeFilter: NodeTypeFilter::fromNodeTypes([
						NodeEntityType::TEAM,
						NodeEntityType::DEPARTMENT,
					]),
				),
			)
			->getAll()
		;

		$result = [];
		foreach ($nodes as $node)
		{
			foreach (self::getSimpleAndRecursiveAccessCode($node) as $code)
			{
				$accessCodeName = $code->buildAccessCode($node->id);

				$result[$accessCodeName] = [
					'provider' => 'hr_structure_auth_provider',
					'name' => $node->name,
				];
			}
		}

		return $result;
	}

	/**
	 * @see \CIntranetAuthProvider::UpdateCodes
	 * @see \CAccess::UpdateCodes
	 * @return array<string> user access codes
	 */
	public function UpdateCodes($USER_ID): array
	{
		$userId = (int)$USER_ID;
		if ($userId <= 0)
		{
			return [];
		}

		$nodeIds = $this->getUserNodeIds($userId);
		if (empty($nodeIds))
		{
			return [];
		}

		$codes = $this->generateAccessCodes($nodeIds);
		$this->saveAccessCodes($userId, $codes);

		return $codes;
	}

	/**
	 * @return list<int>
	 */
	private function getUserNodeIds(int $userId): array
	{
		$usersNodeMembersMembers = (new NodeMemberDataBuilder())
			->setFilter(
				new NodeMemberFilter(
					entityIdFilter: EntityIdFilter::fromEntityId($userId),
					nodeFilter: new NodeFilter(
						entityTypeFilter: NodeTypeFilter::fromNodeTypes([
							NodeEntityType::TEAM,
							NodeEntityType::DEPARTMENT,
						]),
						structureId: $this->structureId,
						depthLevel: DepthLevel::NONE,
					),
					findRelatedMembers: true,
				),
			)
			->getAll()
		;

		return $usersNodeMembersMembers->getNodeIds();
	}

	/**
	 * @param list<int> $nodeIds
	 * @return list<string>
	 */
	private function generateAccessCodes(array $nodeIds): array
	{
		$codes = [];
		$nodesAndItsParents = (new NodeDataBuilder())
			->setFilter(
				new NodeFilter(
					idFilter: IdFilter::fromIds($nodeIds),
					entityTypeFilter: NodeTypeFilter::fromNodeTypes([
						NodeEntityType::TEAM,
						NodeEntityType::DEPARTMENT,
					]),
					structureId: $this->structureId,
					direction: Direction::ROOT,
					depthLevel: DepthLevel::FULL,
				),
			)
			->getAll()
		;
		$nodesWithUser = $nodesAndItsParents->getNodesByIds($nodeIds);

		foreach ($nodesAndItsParents as $nodeOrParent)
		{
			[
				$simpleType,
				$recursiveType,
			] = self::getSimpleAndRecursiveAccessCode($nodeOrParent);

			$isUserNode = $nodesWithUser->containsNodeWithId($nodeOrParent->id);
			if ($isUserNode)
			{
				$codes[] = $simpleType->buildAccessCode($nodeOrParent->id);
				$codes[] = $recursiveType->buildAccessCode($nodeOrParent->id);

				continue;
			}

			$childrenNodesWithSameEntityType = $nodesAndItsParents
				->getChildrenNodesFor($nodeOrParent)
				->intersect($nodesWithUser)
				->filterByEntityTypes($nodeOrParent->type)
			;

			if (!$childrenNodesWithSameEntityType->empty())
			{
				$codes[] = $recursiveType->buildAccessCode($nodeOrParent->id);
			}
		}

		return array_unique($codes);
	}

	/**
	 * @param list<string> $accessCodes
	 */
	private function saveAccessCodes(int $userId, array $accessCodes): void
	{
		if (empty($accessCodes) || !$userId)
		{
			return;
		}

		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();
		$lockName = $this->getLockNameByUserId($userId);

		if (!$connection->lock($lockName))
		{
			return;
		}

		try
		{
			foreach (array_chunk($accessCodes, self::CHUNK_SIZE) as $chunk)
			{
				$sql = $helper->getInsertIgnore(
					UserAccessTable::getTableName(),
					'(USER_ID, PROVIDER_ID, ACCESS_CODE)',
					$this->buildUserAccessInsertValues($userId, $chunk),
				);
				$connection->queryExecute($sql);
			}
		}
		finally
		{
			$connection->unlock($lockName);
		}
	}

	/** @param list<string> $accessCodes */
	private function buildUserAccessInsertValues(int $userId, array $accessCodes): string
	{
		$helper = Application::getConnection()->getSqlHelper();
		$rows = [];

		foreach ($accessCodes as $code)
		{
			[, $values] = $helper->prepareInsert(
				UserAccessTable::getTableName(),
				[
					'USER_ID' => $userId,
					'PROVIDER_ID' => self::PROVIDER_ID,
					'ACCESS_CODE' => $code,
				],
			);
			$rows[] = "({$values})";
		}

		return 'VALUES ' . implode(', ', $rows);
	}

	/**
	 * @return list<array{
	 *   ID:string,
	 *   NAME:string,
	 *   PROVIDER_NAME:string,
	 *   SORT:int,
	 *   CLASS:class-string
	 * }>
	 */
	public static function getProviders(): array
	{
		return [
			[
				'ID' => self::PROVIDER_ID,
				'NAME' => 'hr_structure_auth_provider_name',
				'PROVIDER_NAME' => 'hr_structure_auth_provider',
				'SORT' => 400,
				'CLASS' => self::class,
			],
		];
	}

	private static function buildAccessCodePattern(AccessCodeType ...$types): string
	{
		if (empty($types))
		{
			throw new \InvalidArgumentException('Need at least one type');
		}

		return '^(' . implode('|', array_map(fn($type) => $type->value, $types)) . ')([0-9]+)$^';
	}

	/**
	 * @return list{0: AccessCodeType, 1: AccessCodeType}
	 */
	private static function getSimpleAndRecursiveAccessCode(Node $node): array
	{
		if ($node->isTeam())
		{
			$type = AccessCodeType::HrTeamType;
			$recursiveType = AccessCodeType::HrTeamRecursiveType;
		}
		elseif ($node->isDepartment())
		{
			$type = AccessCodeType::HrDepartmentType;
			$recursiveType = AccessCodeType::HrDepartmentRecursiveType;
		}
		else
		{
			throw new \InvalidArgumentException('Node must be team or department');
		}

		return [
			$type,
			$recursiveType,
		];
	}

	public function recalculateCodesForNode(Node $node): void
	{
		if (!$node->isTeam() && !$node->isDepartment())
		{
			return;
		}

		$childrenMembers = (new NodeMemberDataBuilder())
			->addFilter(
				new NodeMemberFilter(
					nodeFilter: new NodeFilter(
						idFilter: IdFilter::fromId($node->id),
						entityTypeFilter: NodeTypeFilter::fromNodeTypes([
							NodeEntityType::TEAM,
							NodeEntityType::DEPARTMENT,
						]),
						direction: Direction::CHILD,
						depthLevel: DepthLevel::FULL,
					),
				),
			)
			->getAll()
		;

		foreach ($childrenMembers as $member)
		{
			$this->recalculateCodesForNodeMember($member);
		}
	}

	public function recalculateCodesForNodeMember(NodeMember $member): void
	{
		if ($member->entityType->isUser())
		{
			$this->DeleteByUser($member->entityId);
		}
	}

	public function setStructureId(?int $structureId): void
	{
		$this->structureId = $structureId;
	}

	private function getLockNameByUserId(int $userId): string
	{
		return self::PROVIDER_ID . '_' . $userId . '_' . self::LOCK_NAME;
	}
}
