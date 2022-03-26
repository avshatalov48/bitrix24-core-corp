<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

abstract class Action
{
	/** @var Context */
	private $context;
	/** @var Item|null */
	private $itemBeforeSave;

	public function __construct()
	{
		Loc::loadMessages(__FILE__);
	}

	abstract public function process(Item $item): Result;

	final public function setContext(Context $context): self
	{
		$this->context = $context;

		return $this;
	}

	protected function getContext(): Context
	{
		if (!$this->context)
		{
			throw new InvalidOperationException('Context should be set for processing of ' . static::class);
		}

		return $this->context;
	}

	final public function setItemBeforeSave(Item $itemBeforeSave): self
	{
		$this->itemBeforeSave = $itemBeforeSave;

		return $this;
	}

	protected function getItemBeforeSave(): ?Item
	{
		return $this->itemBeforeSave;
	}
}
