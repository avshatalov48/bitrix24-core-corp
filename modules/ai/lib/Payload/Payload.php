<?php

namespace Bitrix\AI\Payload;

use Bitrix\AI\Container;
use Bitrix\AI\Engine\IEngine;
use Bitrix\AI\Payload\Tokens\HiddenToken;
use Bitrix\AI\Facade\User;
use Bitrix\AI\Payload\Tokens\TokenProcessor;
use Bitrix\AI\Prompt\Role;
use Bitrix\AI\ShareRole\Service\ShareService;

abstract class Payload
{
	protected const DEFAULT_USAGE_COST = 1;

	protected const PROPERTY_CUSTOM_COST = 'customCost';

	protected array $markers = [];
	protected array $hiddenTokens = [];
	protected array $processedReplacements = [];
	protected TokenProcessor $tokenProcessor;
	protected ?Role $role = null;
	protected IEngine $engine;

	private bool $instructionFormatted = false;
	protected bool $useCache = false;

	protected ?int $customCost = null;

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

		if (!is_null($this->role) && $this->role->getIndustryCode() === 'custom')
		{
			$shareService = self::getShareService();
			if (!($shareService->hasAccessOnRoleByCode($this->role->getCode(), User::getCurrentUserId())))
			{
				$this->role = Role::getUniversalRole();
				return $this;
			}

			$systemRole = Role::getLibrarySystemRole();
			$markers = array_merge($this->getMarkers(), ['custom_role' => $role->getInstruction()]);
			$this->setMarkers($markers);
			$this->role->setInstruction($systemRole->getInstruction());
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
	 * @param HiddenToken[] $tokens
	 * @return $this
	 */
	final public function setHiddenTokens(array $tokens): static
	{
		$this->hiddenTokens = $tokens;
		$this->tokenProcessor = new TokenProcessor(...$this->hiddenTokens);

		return $this;
	}

	final public function getHiddenTokens(): array
	{
		return $this->hiddenTokens;
	}

	/**
	 * Returns processed replacements. Should be used after QueueJob evaluation.
	 * @return array
	 */
	final public function getProcessedReplacements(): array
	{
		return $this->processedReplacements;
	}

	final public function setProcessedReplacements(array $processedReplacements): void
	{
		$this->processedReplacements = $processedReplacements;
	}

	final public function getTokenProcessor(): TokenProcessor
	{
		if (!isset($this->tokenProcessor))
		{
			$this->tokenProcessor = new TokenProcessor();
		}

		return $this->tokenProcessor;
	}

	final public function hasHiddenTokens(): bool
	{
		return !empty($this->hiddenTokens);
	}

	final public function hideTokens(string $value): string
	{
		if ($this->hasHiddenTokens())
		{
			$value = $this->tokenProcessor->hideTokens($value);
		}

		return $value;
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

	public function setCost(int $cost): static
	{
		$this->customCost = $cost;

		return $this;
	}

	public static function getShareService(): ShareService
	{
		return Container::init()->getItem(ShareService::class);
	}

	protected static function setCustomCost(Payload $payload, $unpackedData): void
	{
		if (!is_null($unpackedData[static::PROPERTY_CUSTOM_COST]))
		{
			$payload->setCost($unpackedData[static::PROPERTY_CUSTOM_COST]);
		}
	}
}
