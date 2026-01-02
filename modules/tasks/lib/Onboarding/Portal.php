<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;

final class Portal
{
	private static ?self $instance = null;

	public static function getInstance(): self
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function isNew(): bool
	{
		$creationDate = (new \Bitrix\Tasks\Integration\Bitrix24\Portal())->getCreationDateTime();
		if ($creationDate === null)
		{
			return false;
		}

		$onboardingStart = $this->getOnboardingStart();

		return $creationDate->getTimestamp() >= $onboardingStart->getTimestamp();
	}

	public function getOnboardingStart(): DateTime
	{
		return new DateTime('2025-02-01', 'Y-m-d');
	}
}