<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Agent;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Update\AgentInterface;
use Bitrix\Tasks\Update\AgentTrait;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Repository\ReminderReadRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Service\Task\ReminderService;

final class Reminder implements AgentInterface
{
	use AgentTrait;

	public function __construct(
		private readonly ReminderService $reminderService,
		private readonly ReminderReadRepositoryInterface $reminderReadRepository,
	)
	{

	}

	public static function execute(): string
	{
		$agent = new self(
			Container::getInstance()->getReminderService(),
			Container::getInstance()->getReminderReadRepository(),
		);

		$agent->run();

		return $agent::getAgentName();
	}

	private function run(): void
	{
		$now = new DateTime();

		$reminders = $this->reminderReadRepository->getByDate($now);
		if ($reminders->isEmpty())
		{
			return;
		}

		foreach ($reminders as $reminder)
		{
			$this->reminderService->send($reminder);
		}

		$this->reminderService->recalculate($reminders);
	}
}
