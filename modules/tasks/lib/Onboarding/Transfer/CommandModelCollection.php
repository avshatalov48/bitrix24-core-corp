<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Transfer;

use ArrayIterator;
use Bitrix\Main\Validation\Rule\ElementsType;
use Bitrix\Main\Validation\Rule\Recursive\Validatable;
use IteratorAggregate;

/** @method CommandModel[] getIterator() */
final class CommandModelCollection implements IteratorAggregate
{
	#[ElementsType(className: CommandModel::class)]
	#[Validatable(true)]
	private array $commandModels;

	public function __construct(CommandModel ...$commandModels)
	{
		$this->commandModels = $commandModels;
	}

	public function isEmpty(): bool
	{
		return empty($this->commandModels);
	}

	public function add(CommandModel $commandModel): void
	{
		$this->commandModels[] = $commandModel;
	}

	public function merge(self $commandModelCollection): self
	{
		foreach ($commandModelCollection as $commandModel)
		{
			if (!$this->contains($commandModel))
			{
				$this->commandModels[] = $commandModel;
			}
		}

		return $this;
	}

	private function contains(CommandModel $commandModel): bool
	{
		foreach ($this->commandModels as $model)
		{
			if ($model->isEqual($commandModel))
			{
				return true;
			}
		}

		return false;
	}

	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->commandModels);
	}
}