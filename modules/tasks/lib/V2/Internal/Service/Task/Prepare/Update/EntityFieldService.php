<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Prepare\Update;

use Bitrix\Tasks\Control\Handler\TariffFieldHandler;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\ParseTags;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\PrepareDBFields;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\PrepareFields;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\RunBeforeUpdateEvent;

class EntityFieldService
{
	public function prepare(Entity\Task $task, UpdateConfig $config, array $currentTask): array
	{
		$mapper = Container::getInstance()->getOrmTaskMapper();

		$fields = $mapper->mapFromEntity($task);
		$fields = (new PrepareFields($config))($fields, $currentTask);

		$fields = (new ParseTags($config))($fields, $currentTask);
		$fields = (new RunBeforeUpdateEvent($config))($fields, $currentTask);

		$tariff = new TariffFieldHandler($fields);

		$fields = $tariff->getFields();

		$dbFields = (new PrepareDBFields($config))($fields, $currentTask);
		$dbFields['ID'] = $task->id;

		return [$mapper->mapToEntity($dbFields), $fields];
	}
}