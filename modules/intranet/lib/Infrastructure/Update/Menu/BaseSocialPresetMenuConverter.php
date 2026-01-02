<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Infrastructure\Update\Menu;

use Bitrix\Intranet\Integration\Templates;
use Bitrix\Main\Update\Stepper;
use Bitrix\Main\UserTable;

abstract class BaseSocialPresetMenuConverter extends Stepper
{
	protected static $moduleId = 'intranet';
	protected int $limit = 50;

	abstract protected function processGlobal(Templates\Air\MenuConverter $menuConverter): void;

	abstract protected function processForUser(Templates\Air\MenuConverter $menuConverter, int $userId): void;

	public function execute(array &$option): bool
	{
		$menuConverter = new Templates\Air\MenuConverter();

		if (empty($option))
		{
			$option['steps'] = 0;
			$option['count'] = 1;
			$option['lastId'] = 0;

			$this->processGlobal($menuConverter);
		}

		$userIds = $this->getUserIdsByLastId((int)($option['lastId'] ?? 0));

		foreach ($userIds as $id)
		{
			$this->processForUser($menuConverter, $id);
		}

		if (count($userIds) < $this->limit)
		{
			return self::FINISH_EXECUTION;
		}

		$option['lastId'] = $userIds[array_key_last($userIds)];

		return self::CONTINUE_EXECUTION;
	}

	protected function getUserIdsByLastId(int $lastId): array
	{
		$result = UserTable::query()
			->setSelect(['ID'])
			->addFilter('=IS_REAL_USER', 'Y')
			->addFilter('>ID', $lastId)
			->setLimit($this->limit)
			->exec()
			->fetchAll();

		return array_map(static fn($item) => (int)$item['ID'], $result);
	}
}
