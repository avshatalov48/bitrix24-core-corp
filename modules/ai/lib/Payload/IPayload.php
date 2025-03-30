<?php

namespace Bitrix\AI\Payload;

use Bitrix\AI\Engine\IEngine;
use Bitrix\AI\Payload\Tokens\TokenProcessor;
use Bitrix\AI\Prompt\Role;

interface IPayload
{
	/**
	 * Sets markers for replacing in payload.
	 * If your payload contains some markers (for {example}) you should use this method.
	 *
	 * @param array $markers Markers for replace.
	 * @return static
	 */
	public function setMarkers(array $markers): static;

	/**
	 * Returns markers was sets.
	 *
	 * @return array
	 */
	public function getMarkers(): array;

	public function setHiddenTokens(array $tokens): static;
	public function getHiddenTokens(): array;
	public function getProcessedReplacements(): array;
	public function setProcessedReplacements(array $processedReplacements): void;
	public function getTokenProcessor(): TokenProcessor;

	/**
	 * Sets Engine instance for Payload.
	 *
	 * @param IEngine $engine Engine instance.
	 * @return static
	 */
	public function setEngine(IEngine $engine): static;

	/**
	 * Sets Role instance for Payload.
	 *
	 * @param Role $role Role instance.
	 * @param bool $append If true, content of Role will be appended, otherwise Role will be replaced.
	 * @return self
	 */
	public function setRole(Role $role, bool $append = false): static;

	/**
	 * Returns Role instance
	 *
	 * @return Role|null
	 */
	public function getRole(): ?Role;

	/**
	 * Returns current payload data.
	 * Method MUST replace all markers in payload, and apply conditions.
	 *
	 * @return mixed
	 */
	public function getData(): mixed;

	/**
	 * Returns current payload data without any transformations as is.
	 *
	 * @return mixed
	 */
	public function getRawData(): mixed;

	/**
	 * Returns cost of usage for specific Payload.
	 *
	 * @return int
	 */
	public function getCost(): int;

	public function setCost(int $cost): static;

	/**
	 * Packs the payload's data in string and returns it.
	 *
	 * @return string
	 */
	public function pack(): string;

	/**
	 * Unpacks data and creates Payload instance from it.
	 *
	 * @param string $packedData Packed data by using method self::pack().
	 * @return static|null
	 */
	public static function unpack(string $packedData): ?static;

	/**
	 * Check can we cache current payload
	 *
	 * @return bool
	 */
	public function shouldUseCache():bool;
}
