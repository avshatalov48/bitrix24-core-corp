<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Trait;

use Bitrix\Main\Type\Collection;

trait ParticipantTrait
{
	private function getParticipants(array $taskData): array
	{
		$participants = array_unique(
			array_merge(
				[
					$taskData["CREATED_BY"] ?? 0,
					$taskData["RESPONSIBLE_ID"] ?? 0,
				],
				$taskData["ACCOMPLICES"] ?? [],
				$taskData["AUDITORS"] ?? [],
			)
		);

		Collection::normalizeArrayValuesByInt($participants, false);

		return $participants;
	}
}