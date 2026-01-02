<?php

namespace Bitrix\HumanResources\Contract\Command;

use Bitrix\Main\Result;
use Bitrix\Main\Type\Contract\Arrayable;

/**
 * @template V of Result
 */
interface CommandInterface extends Arrayable
{
	/**
	 * @return V
	 */
	public function run(): mixed;
	public function runInBackground(): bool;
	public function runWithDelay(int $milliseconds): bool;
}
