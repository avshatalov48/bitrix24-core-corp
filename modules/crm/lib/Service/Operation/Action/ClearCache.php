<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Main\Result;

class ClearCache extends Action
{
	protected $cacheManager;
	protected $directoryName;
	protected $tagPrefix;
	protected $dependantFields;

	public function __construct(?string $directoryName = null, ?string $tagPrefix = null, array $dependantFields = [])
	{
		$this->directoryName = $directoryName;
		$this->tagPrefix = $tagPrefix;
		$this->dependantFields = $dependantFields;
		parent::__construct();
	}

	public function process(Item $item): Result
	{
		$cacheManager = $this->getCacheManager();

		if (!$cacheManager)
		{
			return new Result();
		}

		if ($this->directoryName)
		{
			/** @var \CCacheManager $cacheManager */
			$cacheManager->CleanDir($this->directoryName);
			$cacheManager->ClearByTag($this->directoryName);
		}

		if ($this->isDependantFieldsChanged($item))
		{
			$tag = $this->getTag($item);
			if ($tag)
			{
				$cacheManager->ClearByTag($tag);
			}
		}

		return new Result();
	}

	public function setCacheManager(\CCacheManager $cacheManager)
	{
		$this->cacheManager = $cacheManager;

		return $this;
	}

	public function getCacheManager()
	{
		if ($this->cacheManager)
		{
			return $this->cacheManager;
		}

		if (defined('BX_COMP_MANAGED_CACHE') && BX_COMP_MANAGED_CACHE)
		{
			return $GLOBALS['CACHE_MANAGER'] ?? null;
		}

		return null;
	}

	protected function getTag(Item $item): ?string
	{
		if (!$this->tagPrefix || $item->isNew())
		{
			return null;
		}

		return $this->tagPrefix . $item->getId();
	}

	protected function isDependantFieldsChanged(Item $item): bool
	{
		$itemBeforeSave = $this->getItemBeforeSave();

		if (empty($this->dependantFields) || !$itemBeforeSave)
		{
			return true;
		}

		foreach($this->dependantFields as $fieldName)
		{
			if($itemBeforeSave->remindActual($fieldName) !== $item->get($fieldName))
			{
				return true;
			}
		}

		return false;
	}
}
