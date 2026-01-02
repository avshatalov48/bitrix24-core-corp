<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Service;

use Bitrix\Tasks\V2\Internal\Access\Factory\ControllerFactoryInterface;
use Bitrix\Tasks\V2\Internal\Access\Factory\Type;
use Bitrix\Tasks\V2\Internal\Access\Task\ActionDictionary;

class FlowRightService
{
	use UserRightsTrait;

	public function __construct(
		private readonly ControllerFactoryInterface $controllerFactory,
	)
	{

	}

	public function getUserRights(int $userId, array $rules = ActionDictionary::USER_ACTIONS['flow']): array
	{
		return $this->getUserRightsByType(
			userId: $userId,
			rules: $rules,
			type: Type::Flow,
			controllerFactory: $this->controllerFactory,
		);
	}
}
