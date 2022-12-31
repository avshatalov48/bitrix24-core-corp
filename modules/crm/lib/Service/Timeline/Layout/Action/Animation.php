<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Action;

use Bitrix\Crm\Service\Timeline\Layout\Base;
use Bitrix\Main\ArgumentOutOfRangeException;

class Animation extends Base
{
	/**
	 * Apply animation for block that triggered the action
	 */
	public const TARGET_BLOCK = 'block';

	/**
	 * Apply animation for whole timeline item
	 */
	public const TARGET_ITEM = 'item';

	/**
	 * Disable animated target
	 */
	public const TYPE_DISABLE = 'disable';

	/**
	 * Show loader on animated target
	 */
	public const TYPE_LOADER = 'loader';

	protected string $target;
	protected string $type;
	protected bool $forever = false;

	public static function disableBlock(): self
	{
		return new self(self::TARGET_BLOCK, self::TYPE_DISABLE);
	}

	public static function showLoaderForBlock(): self
	{
		return new self(self::TARGET_BLOCK, self::TYPE_LOADER);
	}

	public static function disableItem(): self
	{
		return new self(self::TARGET_ITEM, self::TYPE_DISABLE);
	}

	public static function showLoaderForItem(): self
	{
		return new self(self::TARGET_ITEM, self::TYPE_LOADER);
	}

	public function __construct(string $target, string $type)
	{
		if (!$this->isValidTarget($target))
		{
			throw new ArgumentOutOfRangeException('target');
		}
		if (!$this->isValidType($type))
		{
			throw new ArgumentOutOfRangeException('type');
		}
		$this->target = $target;
		$this->type = $type;
	}

	private function isValidTarget(string $target): bool
	{
		return in_array($target, [
			self::TARGET_BLOCK,
			self::TARGET_ITEM,
		], true);
	}

	private function isValidType(string $type): bool
	{
		return in_array($type, [
			self::TYPE_DISABLE,
			self::TYPE_LOADER,
		], true);
	}

	public function getTarget(): string
	{
		return $this->target;
	}

	public function setTarget(string $target): self
	{
		$this->target = $target;

		return $this;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function setType(string $type): self
	{
		$this->type = $type;

		return $this;
	}

	public function isForever(): bool
	{
		return $this->forever;
	}

	/**
	 * Do not stop animation till timeline item is updated with a push
	 */
	public function setForever(bool $forever = true): self
	{
		$this->forever = $forever;

		return $this;
	}

	public function toArray()
	{
		return [
			'target' => $this->getTarget(),
			'type' => $this->getType(),
			'forever' => $this->isForever(),
		];
	}
}
