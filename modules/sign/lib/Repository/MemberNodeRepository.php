<?php

namespace Bitrix\Sign\Repository;

use Bitrix\Main\Error;
use Bitrix\Main;
use Bitrix\Sign\Internal;
use Bitrix\Sign\Item\Hr\MemberNode;
use Bitrix\Sign\Item\Hr\MemberNodeCollection;
use Bitrix\Sign\Item\Hr\MemberUser;
use Bitrix\Sign\Item\Hr\MemberUserCollection;
use Bitrix\Sign\Item\Hr\NodeSync;
use Bitrix\Sign\Item\Hr\NodeSyncCollection;
use Bitrix\Sign\Item\Hr\UsersToMembersMap;
use Bitrix\Sign\Type\Hr\NodeSyncStatus;
use Bitrix\Sign\Type\Member\EntityType;
use Bitrix\Sign\Type\Member\Role;

class MemberNodeRepository
{
	public const NODE_WITHOUT_DEPT = 0;

	public function addNodeForSync(int $documentId, int $nodeId, bool $isFlat): Main\Result
	{
		return Internal\NodeSyncTable::add([
			'DOCUMENT_ID' => $documentId,
			'NODE_ID' => $nodeId,
			'IS_FLAT' => $isFlat,
			'STATUS' => NodeSyncStatus::Waiting->value,
			'PAGE' => 0,
		]);
	}

	public function addRelationMultiple(MemberNodeCollection $collection): Main\Result
	{
		if ($collection->count() === 0)
		{
			return new Main\Result();
		}

		$relations = [];
		/** @var MemberNode $item */
		foreach ($collection as $item)
		{
			$relations[] = [
				'DOCUMENT_ID' => $item->documentId,
				'MEMBER_ID' => $item->memberId,
				'NODE_SYNC_ID' => $item->nodeSyncId,
				'USER_ID' => $item->userId,
			];
		}

		return Internal\MemberNodeTable::addMulti($relations);
	}

	/**
	 * @see UsersToMembersMap
	 */
	public function getSignerMembersByUsers(int $documentId, array $userIds): MemberUserCollection
	{
		if (!$documentId || !$userIds)
		{
			return new MemberUserCollection();
		}

		$rows = Internal\MemberTable::getList([
			'select' => ['MEMBER_ID' => 'ID', 'USER_ID' => 'ENTITY_ID'],
			'filter' => [
				'=DOCUMENT_ID' => $documentId,
				'=ROLE' => Role::convertRoleToInt(Role::SIGNER),
				'=ENTITY_TYPE' => EntityType::USER,
				'@ENTITY_ID' => $userIds,
			],
		]);

		$collection = new MemberUserCollection();
		while ($row = $rows->fetch())
		{
			$collection->add(new MemberUser($row['MEMBER_ID'], $row['USER_ID']));
		}
		return $collection;
	}

	public function getRelationsByDocumentIdAndNodeSyncId(int $documentId, int $nodeSyncId): MemberNodeCollection
	{
		$items = Internal\MemberNodeTable::query()
			->setSelect(['MEMBER_ID', 'USER_ID', 'NODE_SYNC_ID'])
			->where('DOCUMENT_ID', $documentId)
			->where('NODE_SYNC_ID', $nodeSyncId)
			->exec()
		;

		$collection = new MemberNodeCollection();
		while ($item = $items->fetchObject())
		{
			$collection->add(
				new MemberNode(
					documentId: $documentId,
					memberId: $item->getMemberId(),
					nodeSyncId: $item->getNodeSyncId(),
					userId: $item->getUserId(),
				),
			);
		}
		return $collection;
	}

	public function getNodeForSync(int $documentId, NodeSyncStatus $status): ?NodeSync
	{
		$node = $this->prepareDocumentNodesQuery($documentId, $status->value, 1)
			->exec()
			->fetchObject()
		;

		if (!$node)
		{
			return null;
		}

		return $this->extractNodeSyncItemFromModel($node);
	}

	public function getNodesForDocument(int $documentId, int $limit = 0, int $offset = 0): NodeSyncCollection
	{
		return $this->extractNodeSyncItemCollectionByModelCollection(
			$this->prepareDocumentNodesQuery($documentId, null, $limit, $offset)
				->exec()
				->fetchCollection()
		);
	}

	public function updateSyncStatus(NodeSync $item): Main\Result
	{
		return Internal\NodeSyncTable::update([
			'ID' => $item->id,
		], [
			'STATUS' => $item->status->value,
			'PAGE' => $item->page,
		]);
	}

	public function resetMemberSyncForDocument(int $documentId): void
	{
		Internal\NodeSyncTable::deleteByFilter(['=DOCUMENT_ID' => $documentId]);
		Internal\MemberNodeTable::deleteByFilter(['=DOCUMENT_ID' => $documentId]);
	}

	/**
	 * @return Main\ORM\Query\Query|Internal\EO_NodeSync_Query
	 */
	private function prepareDocumentNodesQuery(int $documentId, ?int $status = null, int $limit = 0, int $offset = 0): Main\ORM\Query\Query
	{
		$query = Internal\NodeSyncTable::query()
			->setSelect(['*'])
			->where('DOCUMENT_ID', $documentId)
		;

		if ($status !== null && in_array($status, [
			NodeSyncStatus::Waiting->value,
			NodeSyncStatus::Sync->value,
			NodeSyncStatus::Done->value,
		], true))
		{
			$query->where('STATUS', $status);
		}

		if ($limit)
		{
			$query->setLimit($limit);
		}

		if ($offset)
		{
			$query->setOffset($offset);
		}

		return $query;
	}

	private function extractNodeSyncItemCollectionByModelCollection(Internal\NodeSyncCollection $models): NodeSyncCollection
	{
		return new NodeSyncCollection(
			...array_map([$this, 'extractNodeSyncItemFromModel'],
			$models->getAll(),
		));
	}

	private function extractNodeSyncItemFromModel(Internal\NodeSync $model): NodeSync
	{
		return new NodeSync(
			id: $model->getId(),
			documentId: $model->getDocumentId(),
			nodeId: $model->getNodeId(),
			status: NodeSyncStatus::from($model->getStatus()),
			page: $model->getPage(),
			isFlat: $model->getIsFlat(),
		);
	}
}
