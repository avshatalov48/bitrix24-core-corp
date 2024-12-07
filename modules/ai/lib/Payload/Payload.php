<?php

namespace Bitrix\AI\Payload;

use Bitrix\AI\Engine\IEngine;
use Bitrix\AI\Prompt\Role;

abstract class Payload
{
	protected const DEFAULT_USAGE_COST = 1;

	protected array $markers = [];
	protected ?Role $role = null;
	protected IEngine $engine;

	private bool $instructionFormatted = false;
	protected bool $useCache = false;

	/**
	 * Returns current payload data without any transformations as is.
	 *
	 * @return mixed
	 */
	public function getRawData(): mixed
	{
		return $this->payload;
	}

	/**
	 * Sets Engine instance for Payload.
	 *
	 * @param IEngine $engine Engine instance.
	 * @return self
	 */
	public function setEngine(IEngine $engine): static
	{
		$this->engine = $engine;
		return $this;
	}

	/**
	 * Sets Role instance for Payload.
	 *
	 * @param Role|null $role Role instance.
	 * @param bool $append If true, content of Role will be appended, otherwise Role will be replaced.
	 * @return self
	 */
	public function setRole(?Role $role, bool $append = false): static
	{
		if ($append && !is_null($role))
		{
			$this->role->appendInstruction($role->getInstruction());
		}
		else
		{
			$this->role = $role;
		}

		return $this;
	}

	/**
	 * Returns Role instance
	 *
	 * @return Role|null
	 */
	public function getRole(): ?Role
	{
		if (!$this->role)
		{
			return $this->role;
		}

		if (!$this->instructionFormatted)
		{
			$this->instructionFormatted = true;
			$instruction = $this->role->getInstruction();
			$instruction = (new Formatter($instruction, $this->engine))->format($this->markers);
			$this->role->setInstruction($instruction);
		}

		return $this->role;
	}

	/**
	 * Sets markers for replacing in payload.
	 * If your payload contains some markers (for {example}) you should use this method.
	 *
	 * @param array $markers Markers for replace.
	 * @return static
	 */
	public function setMarkers(array $markers): static
	{
		$this->markers = $markers;
		return $this;
	}

	/**
	 * Returns markers was sets.
	 *
	 * @return array
	 */
	public function getMarkers(): array
	{
		return $this->markers;
	}

	/**
	 * Returns cost of usage for specific Payload.
	 *
	 * @return int
	 */
	public function getCost(): int
	{
		return self::DEFAULT_USAGE_COST;
	}

	/**
	 * @return bool
	 */
	public function shouldUseCache():bool
	{
		return $this->useCache;
	}
}
