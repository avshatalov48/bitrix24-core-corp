<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller;

use Bitrix\Tasks\V2\FormV2Feature;
use Bitrix\Tasks\V2\Infrastructure\Controller\ActionFilter\Rule\OnlyAllowedPortal;

class Feature extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Feature.turnOn
	 */
	#[OnlyAllowedPortal]
	public function turnOnAction(string $feature): bool
	{
		return FormV2Feature::turnOn($feature);
	}

	/**
	 * @ajaxAction tasks.V2.Feature.turnOn
	 */
	#[OnlyAllowedPortal]
	public function turnOffAction(string $feature): bool
	{
		return FormV2Feature::turnOff($feature);
	}

	/**
	 * @ajaxAction tasks.V2.Feature.isOn
	 */
	#[OnlyAllowedPortal]
	public function isOnAction(string $feature): bool
	{
		return FormV2Feature::isOn($feature);
	}
}
