<?php

namespace Bitrix\HumanResources\Integration\Socialnetwork;

use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Repository\NodeRelationRepository;
use Bitrix\HumanResources\Result\Integration\Socialnetwork\CreateCollabResult;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\RelationEntityType;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Filter\Condition;
use Bitrix\SocialNetwork\Collab\Access\CollabAccessController;
use Bitrix\SocialNetwork\Collab\Access\CollabDictionary;
use Bitrix\Socialnetwork\Collab\Control\Command\CollabAddCommand;
use Bitrix\Socialnetwork\Collab\Control\Decorator\RequirementDecorator;
use Bitrix\Socialnetwork\Collab\Provider\CollabProvider;
use Bitrix\Socialnetwork\Collab\Provider\CollabQuery;
use Bitrix\Socialnetwork\Control\Decorator\AccessDecorator;
use Bitrix\Socialnetwork\Integration\Im\Chat\Workgroup;

class CollabService
{
	private NodeRelationRepository $nodeRelationRepository;

	public function __construct()
	{
		$this->nodeRelationRepository = Container::getNodeRelationRepository();
	}

	public function isAvailable(): bool
	{
		return Loader::includeModule('socialnetwork');
	}

	public function create(Item\Node $node, array $headIds, int $userId): CreateCollabResult
	{
		$result = new CreateCollabResult();

		if (!$this->isAvailable())
		{
			return $result->addError(new Error(Loc::getMessage('HUMANRESOURCES_COLLAB_SERVICE_NOT_AVAILABLE_MSGVER_1')));
		}

		$collabName = $this->getCollabName($node, $userId);

		$collabData = [
			'ownerId' => $headIds[array_rand($headIds)],
			'name' => $collabName,
			'initiatorId' => $userId,
		];
		$command = CollabAddCommand::createFromArray($collabData);

		$collabService = ServiceLocator::getInstance()->get('socialnetwork.collab.service');
		$addResult = (new RequirementDecorator(new AccessDecorator($collabService)))->add($command);

		if (!$addResult->isSuccess())
		{
			return $result->addErrors($addResult->getErrors());
		}

		return $result->setCollabId((int)$addResult->getCollab()?->getId());
	}

	public function filterByPermissions(array $ids, int $userId): array
	{
		if (!$this->isAvailable())
		{
			return [];
		}

		// ToDo: improve performance
		return array_filter($ids, fn(int $item): bool => CollabAccessController::can($userId, CollabDictionary::INVITE, $item));
	}

	public function getCollabsByNode(Item\Node $node, int $userId): array
	{
		if (!$this->isAvailable())
		{
			return ['collabs' => [], 'noAccessCollabs' => 0];
		}

		$collabCollection = $this->nodeRelationRepository->findRelationsByNodeIdAndRelationType(
			nodeId: $node->id,
			relationEntityType: RelationEntityType::COLLAB,
			limit: 0,
		);

		$collabIds = $collabCollection->map(fn($item): int => $item->entityId);
		$indirectCollabs = [];
		foreach ($collabCollection as $item)
		{
			if ($item->nodeId !== $node->id)
			{
				$indirectCollabs[$item->entityId] = $item->nodeId;
			}
		}

		if (count($collabIds) === 0)
		{
			return ['collabs' => [], 'noAccessCollabs' => 0];
		}

		$collabProvider = new CollabProvider();

		$allCollabQuery = (new CollabQuery($userId))
			->addWhere(new Condition('ID', 'in', $collabIds))
			->setSelect(['ID'])
		;
		$allCollabsCount = $collabProvider->getCount($allCollabQuery);

		$collabQuery = (new CollabQuery($userId))
			->addWhere(new Condition('ID', 'in', $collabIds))
			->setSelect(['ID', 'NAME', 'IMAGE_ID'])
			->setAccessCheck()
		;

		$collabs = $collabProvider->getList($collabQuery);
		$collabsChatData = Workgroup::getChatData([
			'group_id' => $collabs->getIdList(),
		]);

		$noAccessCollabs = $allCollabsCount - $collabs->count();
		$items = $collabs->toArray();

		$avatarsArray = [];
		if (!empty($items))
		{
			$res = \CFile::getList(arFilter: ['@ID' => array_map(static fn(array $item): int => (int)$item['IMAGE_ID'], $items)]);
			while ($file = $res->fetch()) {
				$fileInfo = \CFile::ResizeImageGet(
					$file,
					['width' => 100, 'height' => 100],
					BX_RESIZE_IMAGE_EXACT,
				);
				$avatarsArray[$file['ID']] = $fileInfo['src'] ?? null;
			}
		}

		$collabsResult = array_map(
			static fn(array $item): array => [
				'id' => (int)$item['ID'],
				'title' => $item['NAME'],
				'type' => RelationEntityType::COLLAB,
				'subtitle' => Loc::getMessage('HUMANRESOURCES_COLLAB_SERVICE_COLLAB_SUBTITLE'),
				'avatar' => $avatarsArray[$item['IMAGE_ID']] ?? null,
				'originalNodeId' => $indirectCollabs[$item['ID']] ?? null,
				'dialogId' => 'chat' . ($collabsChatData[$item['ID']] ?? null),
				'hasAccess' => true,
			],
			array_values($items),
		);

		return [
			'collabs' => $collabsResult,
			'collabsNoAccess' => $noAccessCollabs,
		];
	}

	/**
	 * Collabs with the same name can't be created (socialnetwork restriction), so we need to ensure that the name is unique.
	 * To do that we check existing collabs with the same name and append a number to the name if needed.
	 *
	 * @param Item\Node $node
	 * @param int $userId
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 */
	private function getCollabName(Item\Node $node, int $userId): string
	{
		$duplicateTitleStart = Loc::getMessage('HUMANRESOURCES_COLLAB_SERVICE_NAME_SEPARATOR', [
			'#COLLAB_NAME#' => $node->name,
		]);
		$suffixPosition = (string)(mb_strlen($duplicateTitleStart) + 1);

		// check collab with same name
		$collabProvider = new CollabProvider();
		$allCollabQuery = (new CollabQuery($userId))
			->setSelect([
				'ID',
				new \Bitrix\Main\ORM\Fields\ExpressionField(
					'NUM_SUFFIX',
					"CAST(SUBSTRING(%s, $suffixPosition) AS UNSIGNED)",
					['NAME'],
				),
			])
			->setWhere(\Bitrix\Main\ORM\Query\Query::filter()
				->logic('or')
				->where('NAME', '=', $node->name)
				->whereLike('NAME', $duplicateTitleStart . '%')
			)
			->setOrder(['ID' => 'DESC'])
			->setLimit(50)
		;

		$collabs = $collabProvider->getList($allCollabQuery)->toArray();

		if (empty($collabs))
		{
			return $node->name;
		}

		$maxNumSuffix = array_reduce($collabs, fn(int $max, array $item) => max($max, (int)$item['NUM_SUFFIX']), 0);

		return $duplicateTitleStart . ($maxNumSuffix + 1);
	}
}
