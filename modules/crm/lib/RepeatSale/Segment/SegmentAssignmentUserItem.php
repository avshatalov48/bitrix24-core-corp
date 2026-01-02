<?php

namespace Bitrix\Crm\RepeatSale\Segment;

use Bitrix\Crm\RepeatSale\Segment\Entity\RepeatSaleSegmentAssignmentUser;

final class SegmentAssignmentUserItem
{
	private ?int $id;
	private int $userId;
	private int $segmentId;

	public static function createFromEntity(RepeatSaleSegmentAssignmentUser $item): self
	{
		$instance = new self();

		$instance->id = $item->getId();
		$instance->userId = $item->getUserId();
		$instance->segmentId = $item->getSegmentId();

		return $instance;
	}

	public static function createFromArray(array $data): self
	{
		$instance = new self();

		$instance->id = $data['id'] ?? null;
		$instance->userId = $data['userId'];
		$instance->segmentId = $data['segmentId'];

		return $instance;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'userId' => $this->userId,
			'segmentId' => $this->segmentId,
		];
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getSegmentId(): int
	{
		return $this->segmentId;
	}
}
