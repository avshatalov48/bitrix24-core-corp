<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document\OnlyOffice\Clients\CommandService;

use Bitrix\Main\Result;

interface CommandServiceClientInterface
{
	/**
	 * Renames the document
	 * @link https://api1.onlyoffice.com/editors/command/meta
	 * @param string $documentKey Document identifier
	 * @param string $newName New document name
	 * @return Result
	 */
	public function rename(string $documentKey, string $newName): Result;

	/**
	 * Disconnects the users with the identifiers specified in the $userIds parameter from the document editing service.
	 * @link https://api1.onlyoffice.com/editors/command/drop
	 * @param string $documentKey Document identifier
	 * @param string[] $userIds Array of user identifiers
	 * @return Result
	 */
	public function drop(string $documentKey, array $userIds): Result;
}