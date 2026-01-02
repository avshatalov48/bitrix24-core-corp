<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Command;

use Bitrix\Main\Type\DateTime;

interface CommandRepositoryInterface
{
	public function getAll(DateTime $from = new DateTime(), int $limit = 50): CommandCollection;
}