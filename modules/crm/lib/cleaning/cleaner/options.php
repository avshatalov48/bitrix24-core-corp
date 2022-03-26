<?php

namespace Bitrix\Crm\Cleaning\Cleaner;

use Bitrix\Main\ArgumentOutOfRangeException;

final class Options
{
	public const ENVIRONMENT_HIT = 'environment_hit';
	public const ENVIRONMENT_AGENT = 'environment_agent';

	/** @var int */
	private $entityTypeId;
	/** @var int */
	private $entityId;
	/** @var string */
	private $environment = self::ENVIRONMENT_HIT;

	/**
	 * @param int $entityTypeId - type id of cleaning target
	 * @param int $entityId - id of cleaning target
	 *
	 * @throws ArgumentOutOfRangeException
	 */
	public function __construct(int $entityTypeId, int $entityId)
	{
		if ($entityTypeId <= 0)
		{
			throw new ArgumentOutOfRangeException('entityTypeId', 1);
		}

		if ($entityId <= 0)
		{
			throw new ArgumentOutOfRangeException('entityId', 1);
		}

		$this->entityTypeId = $entityTypeId;
		$this->entityId = $entityId;
	}

	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	public function getEntityId(): int
	{
		return $this->entityId;
	}

	public function setEnvironment(string $environment): self
	{
		if (
			$environment !== static::ENVIRONMENT_HIT
			&& $environment !== static::ENVIRONMENT_AGENT
		)
		{
			throw new ArgumentOutOfRangeException('environment');
		}

		$this->environment = $environment;

		return $this;
	}

	public function getEnvironment(): string
	{
		return $this->environment;
	}
}
