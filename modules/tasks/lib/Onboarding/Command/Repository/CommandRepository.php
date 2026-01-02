<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Command\Repository;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Onboarding\Command\CommandCollection;
use Bitrix\Tasks\Onboarding\Command\CommandFactory;
use Bitrix\Tasks\Onboarding\Command\CommandRepositoryInterface;
use Bitrix\Tasks\Onboarding\Internal\Model\QueueTable;

class CommandRepository implements CommandRepositoryInterface
{
	public function getAll(DateTime $from = new DateTime(), int $limit = 50): CommandCollection
	{
		$rows = QueueTable::query()
			->setSelect(['ID', 'TASK_ID', 'USER_ID', 'TYPE', 'CODE'])
			->where('IS_PROCESSED', false)
			->where('NEXT_EXECUTION', '<=', $from)
			->setLimit($limit)
			->exec()
			->fetchCollection();

		$commands = new CommandCollection();

		foreach ($rows as $row)
		{
			$command = CommandFactory::createCommand(
				$row->getId(),
				$row->getTaskId(),
				$row->getUserId(),
				$row->getType(),
				$row->getCode(),
			);

			if ($command === null)
			{
				continue;
			}

			$commands->add($command);
		}

		return $commands;
	}
}