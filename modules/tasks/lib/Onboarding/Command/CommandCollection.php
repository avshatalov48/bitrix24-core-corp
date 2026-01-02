<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Command;

use ArrayIterator;
use Bitrix\Main\Type\Collection;
use IteratorAggregate;

/** @method CommandInterface[] getIterator() */
final class CommandCollection implements IteratorAggregate
{
	/** @var CommandInterface[] */
	private array $commands;
	private ?ArrayIterator $iterator = null;

	public function __construct(CommandInterface ...$commands)
	{
		$this->commands = $commands;
	}

	public function add(CommandInterface $command): void
	{
		$this->commands[] = $command;
	}

	public function getIdList(): array
	{
		$ids = [];
		foreach ($this->commands as $command)
		{
			$ids[] = $command->getId();
		}

		Collection::normalizeArrayValuesByInt($ids, false);

		return $ids;
	}

	public function isEmpty(): bool
	{
		return empty($this->commands);
	}

	public function getIterator(): ArrayIterator
	{
		if ($this->iterator === null)
		{
			$this->iterator = new ArrayIterator($this->commands);
		}

		return $this->iterator;
	}

	public function removeByCode(string $code, ?int $id = null): self
	{
		foreach ($this->commands as $i => $command)
		{
			if ($command->getId() === $id || $command->getCode() !== $code)
			{
				continue;
			}

			unset($this->commands[$i]);

			$this->getIterator()->offsetUnset($i);
		}

		return $this;
	}
}