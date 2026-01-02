<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Command\Trait;

use Bitrix\Tasks\Onboarding\DI\OnboardingContainer;

trait ContainerTrait
{
	private function getContainer(): OnboardingContainer
	{
		return OnboardingContainer::getInstance();
	}
}