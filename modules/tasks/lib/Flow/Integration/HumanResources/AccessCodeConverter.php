<?php

namespace Bitrix\Tasks\Flow\Integration\HumanResources;

use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use Closure;

class AccessCodeConverter
{
	/**
	 * @var string[]
	 */
	private array $accessCodes;
	private NodeRepository $nodeRepository;
	/**
	 * @var array<int, bool>
	 */
	private static array $isUserActive = [];

	/**
	 * @throws LoaderException
	 */
	public function __construct(string ...$accessCodes)
	{
		if (!Loader::includeModule('humanresources'))
		{
			throw new LoaderException('Humanresources is not loaded');
		}

		$this->accessCodes = $accessCodes;
		$this->nodeRepository = Container::getNodeRepository();
	}

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @return int[]
	 */
	public function getUserIds(): array
	{
		$userIds = $this->getUserIdsByDepartmentEntities();

		$userIds = array_merge($userIds, $this->getUserIdsByUserEntities());

		return array_unique($userIds);
	}

	/**
	 * @throws SystemException
	 * @throws LoaderException
	 * @return int[]
	 */
	private function getUserIdsByUserEntities(): array
	{
		$userIds = $this->getUsers()->getAccessCodeIdList();

		if (empty($userIds))
		{
			return [];
		}

		$userIdsToQuery = array_filter(
			$userIds,
			static fn($userId) => !isset(static::$isUserActive[$userId])
		);

		if (!empty($userIdsToQuery))
		{
			$this->fillUserIdsActivity($userIdsToQuery);
		}

		return array_filter(
			$userIds,
			static fn($userId) => static::$isUserActive[$userId]
		);
	}

	private function fillUserIdsActivity(array $userIds): void
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();
		$orderField = new ExpressionField(
			'ID_SEQUENCE',
			$sqlHelper->getOrderByIntField('%s', $userIds, false),
			array_fill(0, count($userIds), 'ID')
		);

		$query = UserTable::query()
			->setSelect(['ID', 'ACTIVE'])
			->where('ACTIVE', 'Y')
			->whereIn('ID', $userIds)
			->registerRuntimeField($orderField)
			->setOrder($orderField->getName())
		;

		$activeUserIds = $query->exec()->fetchCollection()->getIdList();

		foreach ($userIds as $userId)
		{
			static::$isUserActive[$userId] = in_array($userId, $activeUserIds, true);
		}
	}

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @return int[]
	 */
	private function getUserIdsByDepartmentEntities(): array
	{
		$departmentIds = $this->getDepartments()->accessCodes;
		$departmentIdsWithoutRecursive = array_map(
			static fn(string $departmentId): string => str_replace("DR", "D", $departmentId),
			$departmentIds,
		);

		$nodeCollection = $this->nodeRepository->findAllByAccessCodes($departmentIdsWithoutRecursive);

		$userIds = [];

		/**
		 * @var Node $node
		 */
		foreach ($nodeCollection as $node)
		{
			$departmentId = $this->getEntityIdByAccessCode($node->accessCode);
			$withAllChildNodes = in_array("DR{$departmentId}", $this->accessCodes, true);

			$memberCollectionByNode = Container::getNodeMemberService()->getAllEmployees(
				$node->id,
				$withAllChildNodes,
			);

			foreach ($memberCollectionByNode->getItemMap() as $member)
			{
				static::$isUserActive[$member->entityId] = true;
				$userIds[$member->entityId] = true;
			}
		}

		return array_keys($userIds);
	}

	private function getEntityIdByAccessCode(string $accessCode): int
	{
		return (new AccessCode($accessCode))->getEntityId();
	}

	public function getAccessCodeIdList(): array
	{
		$ids = array_map(static fn(string $code): int => (new AccessCode($code))->getEntityId(), $this->accessCodes);

		return array_filter(array_unique($ids));
	}

	/**
	 * @throws LoaderException
	 */
	public function getUsers(): static
	{
		$users = array_filter($this->accessCodes, $this->getFilter(AccessCode::TYPE_USER));
		return new static(...$users);
	}

	/**
	 * @throws LoaderException
	 */
	public function getDepartments(): static
	{
		$departments = array_filter($this->accessCodes, $this->getFilter(AccessCode::TYPE_DEPARTMENT));
		return new static(...$departments);
	}

	private function getFilter(string $entityType): Closure
	{
		return static fn (string $code): bool => (new AccessCode($code))->getEntityType() === $entityType;
	}

	public function hasUserAll(): bool
	{
		return in_array('UA', $this->accessCodes, true);
	}
}