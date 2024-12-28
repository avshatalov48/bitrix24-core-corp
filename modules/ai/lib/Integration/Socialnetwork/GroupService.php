<?php declare(strict_types=1);

namespace Bitrix\AI\Integration\Socialnetwork;

use Bitrix\Main\LoaderException;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Socialnetwork\Internals\Registry\UserRegistry;
use Bitrix\Main\Loader;

class GroupService
{
	protected bool $hasModule = false;
	protected bool $hasCheckModule = false;
	protected ?array $notVisibleGroupListInKeys = null;

	public function getNotVisibleGroupListInKeys(): array
	{
		if (is_null($this->notVisibleGroupListInKeys))
		{
			if (!$this->hasSocialnetwork())
			{
				$this->notVisibleGroupListInKeys = [];

				return [];
			}

			$this->notVisibleGroupListInKeys = $this->getPrepareNotVisibleGroupList();
		}

		return $this->notVisibleGroupListInKeys ?? [];
	}

	private function getPrepareNotVisibleGroupList(): array
	{
		$list = WorkgroupTable::query()
			->setSelect(['ID'])
			->where('VISIBLE', '=', false)
			->fetchCollection()
		;

		if ($list->isEmpty())
		{
			return [];
		}

		$result = [];
		foreach ($list as $item)
		{
			$result[$item->getId()] = true;
		}

		return $result;
	}

	public function getGroupIdsForUser($userId): array
	{
		if (!$this->hasSocialnetwork())
		{
			return [];
		}

		return array_keys(UserRegistry::getInstance($userId)->getUserGroups());
	}

	protected function hasSocialnetwork(): bool
	{
		if (!$this->hasCheckModule)
		{
			$this->hasCheckModule = true;
			try
			{
				$this->hasModule = Loader::includeModule('socialnetwork');
			}
			catch (LoaderException $exception)
			{
				return false;
			}
		}

		return $this->hasModule;
	}

	public function getAllGroupCodes(): array
	{
		$list = WorkgroupTable::query()
			->setSelect(['ID'])
			->fetchCollection()
		;

		if ($list->isEmpty())
		{
			return [];
		}

		return $list->getIdList();
	}
}
