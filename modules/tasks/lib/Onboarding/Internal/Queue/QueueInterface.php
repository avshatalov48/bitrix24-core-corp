<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Internal\Queue;

use Bitrix\Tasks\Onboarding\Internal\Type;
use Bitrix\Tasks\Onboarding\Transfer;

interface QueueInterface
{
	public function save(Transfer\QueueJobCollection $jobs): void;
	public function process(int ...$ids): void;
	public function unprocess(int ...$ids): void;
	public function clearById(int ...$ids): void;
	public function clearByCode(string ...$codes): void;
	public function clearByTaskAndUserId(int $taskId = 0, int $userId = 0): void;

	/**
	 * @param Type[] $types
	 */
	public function clearByUserJobParams(array $types, int $userId, int $taskId = 0): void;
}