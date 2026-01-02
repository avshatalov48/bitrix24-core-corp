<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Disk\Access\Service;

use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\V2\Internal\Access\Factory\ControllerFactoryInterface;
use Bitrix\Tasks\V2\Internal\Access\Factory\Type;

class DiskFileAccessService
{
	public function __construct(
		private readonly ControllerFactoryInterface $controllerFactory,
	)
	{

	}

	public function canReadTaskAttachments(int $taskId, int $userId): bool
	{
		$controller = $this->controllerFactory->create(Type::Task, $userId);
		if ($controller === null)
		{
			return false;
		}

		return $controller::can($userId, ActionDictionary::ACTION_TASK_READ, $taskId);
	}
}
