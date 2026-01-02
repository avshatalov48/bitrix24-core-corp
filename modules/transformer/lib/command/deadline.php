<?php

namespace Bitrix\Transformer\Command;

use Bitrix\Main\Type\DateTime;
use Bitrix\Transformer\Command;
use Bitrix\Transformer\Log;
use Bitrix\Transformer\WellKnownControllerCommand;

/**
 * @internal
 */
final class Deadline
{
	private const MINUTE = 60;
	private const HOUR = 60 * self::MINUTE;
	private const DAY = 24 * self::HOUR;

	private int $timeout;

	private function __construct()
	{
	}

	public static function createByCommand(Command $command): self
	{
		$params = $command->getParams();
		if (!isset($params['timeout']))
		{
			return self::getDefaultByCommand($command);
		}

		if (!is_numeric($params['timeout']) || (int)$params['timeout'] < self::getMinTimeoutByCommand($command))
		{
			Log::logger()->error(
				'Invalid timeout {timeout} provided, using default',
				[
					'timeout' => $params['timeout'],
					'guid' => $command->getGuid(),
					'params' => $params,
					'command' => $command->getCommandName(),
				]
			);

			return self::getDefaultByCommand($command);
		}

		$instance = new self();
		$instance->timeout = (int)$params['timeout'];
		return $instance;
	}

	public static function getDefaultByCommand(Command $command): self
	{
		$instance = new self();
		$instance->timeout = self::getDefaultTimeoutByCommand($command);

		return $instance;
	}

	private static function getDefaultTimeoutByCommand(Command $command): int
	{
		return match (WellKnownControllerCommand::tryFrom($command->getCommandName()))
		{
			WellKnownControllerCommand::Document => self::HOUR,
			WellKnownControllerCommand::Video => 6 * self::HOUR,
			default => self::DAY,
		};
	}

	/**
	 * Timeout lower than this isn't practical
	 */
	private static function getMinTimeoutByCommand(Command $command): int
	{
		return match (WellKnownControllerCommand::tryFrom($command->getCommandName()))
		{
			WellKnownControllerCommand::Document => 5 * self::MINUTE,
			WellKnownControllerCommand::Video => 30 * self::MINUTE,
			default => self::HOUR,
		};
	}

	public function getDeadline(?DateTime $now = null): DateTime
	{
		$now ??= new DateTime();

		return (clone $now)->add("+ {$this->timeout} seconds");
	}
}
