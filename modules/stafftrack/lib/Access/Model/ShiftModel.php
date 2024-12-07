<?php

namespace Bitrix\StaffTrack\Access\Model;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\StaffTrack\Model;
use Bitrix\StaffTrack\Shift\ShiftDto;

class ShiftModel implements AccessibleShift
{
	private int $id = 0;
	private int $userId = 0;
	private static array $cache = [];

	public static function createFromId(int $itemId): AccessibleItem
	{
		if ($itemId === 0)
		{
			return self::createNew();
		}

		if (!isset(static::$cache[$itemId]))
		{
			$model = self::createNew();
			$model->setId($itemId);
			static::$cache[$itemId] = $model;
		}

		return static::$cache[$itemId];
	}

	public static function createNew(): self
	{
		return new self();
	}

	public static function createFromDto(ShiftDto $shiftDto): self
	{
		/** @var self $accessModel */
		$accessModel = self::createFromId($shiftDto->id);
		$accessModel->setUserId($shiftDto->userId);

		return $accessModel;
	}

	public static function createFromObject(Model\Shift $shift): self
	{
		/** @var self $accessModel */
		$accessModel = self::createFromId((int)$shift->getId());
		$accessModel->setUserId($shift->getUserId());

		return $accessModel;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function setId(int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function setUserId(int $userId): self
	{
		$this->userId = $userId;

		return $this;
	}
}