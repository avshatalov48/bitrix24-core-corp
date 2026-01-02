<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Stakeholder;

use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Service\Consistency\ConsistencyResolverInterface;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\UpdateUserFields;
use Bitrix\Tasks\V2\Internal\Service\Task\MemberService;

class SetAuditorsHandler
{
	public function __construct(
		private readonly MemberService $memberService,
		private readonly ConsistencyResolverInterface $consistencyResolver,
	)
	{

	}

	public function __invoke(SetAuditorsCommand $command): Task
	{
		[$task, $fields] = $this->consistencyResolver->resolve('task.member')->wrap(
			fn (): array => $this->memberService->setAuditors($command->taskId, $command->auditorIds, $command->config)
		);

		// this action is outside of consistency because it is containing nested transactions
		(new UpdateUserFields($command->config))($fields, $command->taskId);

		return $task;
	}
}