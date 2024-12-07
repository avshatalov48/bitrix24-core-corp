<?php
declare(strict_types=1);

namespace Bitrix\AI\Context\Memory\Contract;

/**
 * @template Message
 */
interface MemoryBuilder
{
	/**
	 * Sets limit for messages.
	 * It's maximum number of messages to return after build.
	 * Pay attention that it's not a number of messages to load.
	 * @param int $limit Limit.
	 * @return $this
	 * @see self::useMergeMode()
	 *
	 */
	public function setLimit(int $limit): static;

	/**
	 * Sets time interval for merge mode.
	 * Merge mode is used to merge messages with
	 * the same author in a short time interval in a row.
	 *
	 * @param int $timeInterval Time interval in seconds.
	 * @return self
	 */
	public function useMergeMode(int $timeInterval): static;

	/**
	 * Builds messages.
	 * It's a main method to get messages.
	 * It returns messages according to set limit.
	 *
	 * @return array<Message> List of messages.
	 */
	public function build(): array;
}