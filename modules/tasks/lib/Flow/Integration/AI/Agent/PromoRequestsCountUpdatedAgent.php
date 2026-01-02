<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Agent;

use Bitrix\Tasks\Flow\Integration\AI\Stepper\PromoRequestsCountUpdatedStepper;
use Bitrix\Tasks\Update\AgentInterface;
use CAgent;

class PromoRequestsCountUpdatedAgent implements AgentInterface
{
	private const MODULE_ID = 'tasks';

	public static function addAgent(): void
	{
		$isAgentExists = is_array(
			\CAgent::GetList(
				[],
				['MODULE_ID' => self::MODULE_ID, 'NAME' => self::getAgentName()],
			)->Fetch()
		);
		if ($isAgentExists)
		{
			return;
		}

		$date = new \DateTime('first day of next month midnight');

		\CAgent::AddAgent(
			name: self::getAgentName(),
			module: self::MODULE_ID,
			interval: 5,
			next_exec: ConvertTimeStamp($date->getTimestamp(), "FULL"),
		);
	}

	public static function getAgentName(bool $withSlash = true): string
	{
		$prefix = $withSlash ? '\\' : '';
		return $prefix . static::class . "::execute();";
	}

	public static function removeAgent(): void
	{
		CAgent::RemoveAgent(static::getAgentName(), self::MODULE_ID);
		// backward compatibility
		CAgent::RemoveAgent(static::getAgentName(false), self::MODULE_ID);
	}

	public static function execute(): string
	{
		self::removeAgent();

		return (new self())->run();
	}

	private function run(): string
	{
		PromoRequestsCountUpdatedStepper::bind(0);

		return '';
	}
}