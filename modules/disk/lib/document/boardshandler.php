<?php

namespace Bitrix\Disk\Document;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Localization\Loc;

class BoardsHandler extends DocumentHandler
{
	const ERROR_METHOD_IS_NOT_SUPPORTED = 'DISK_BOARDS_HANDLER_22001';

	/**
	 * @inheritdoc
	 */
	public static function getCode()
	{
		return 'board';
	}

	/**
	 * @inheritdoc
	 */
	public static function getName(): string
	{
		return Loc::getMessage('DISK_BOARDS_HANDLER_NAME');
	}

	/**
	 * Execute this method for check potential possibility get access token.
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function checkAccessibleTokenService(): bool
	{
		return true;
	}

	/**
	 * Return link for authorize user in external service.
	 * @param string $mode
	 * @return string
	 */
	public function getUrlForAuthorizeInTokenService($mode = 'modal'): string
	{
		return '';
	}

	/**
	 * Request and store access token (self::accessToken) for self::userId
	 * @return $this
	 */
	public function queryAccessToken(): self
	{
		return $this;
	}

	/**
	 * @return bool
	 */
	public function hasAccessToken(): bool
	{
		return true;
	}

	/**
	 * Create new blank file in cloud service.
	 * It is not necessary set shared rights on file.
	 * @param FileData $fileData
	 * @return FileData|null
	 */
	public function createBlankFile(FileData $fileData): ?FileData
	{
		$this->errorCollection->add([
			new Error(Loc::getMessage('DISK_BOARDS_VIEWER_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED'), self::ERROR_METHOD_IS_NOT_SUPPORTED)
		]);

		return null;
	}

	/**
	 * Create file in cloud service by upload from us server.
	 * Necessary set shared rights on file for common work.
	 *
	 * @param FileData $fileData
	 * @return FileData|null
	 */
	public function createFile(FileData $fileData): ?FileData
	{
		$this->errorCollection->add([
			new Error(Loc::getMessage('DISK_BOARDS_VIEWER_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED'), self::ERROR_METHOD_IS_NOT_SUPPORTED)
		]);

		return null;
	}

	/**
	 * Download file from cloud service by FileData::id, put contents in FileData::src
	 * @param FileData $fileData
	 * @return FileData|null
	 */
	public function downloadFile(FileData $fileData): ?FileData
	{
		$this->errorCollection->add([
			new Error(Loc::getMessage('DISK_BOARDS_VIEWER_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED'), self::ERROR_METHOD_IS_NOT_SUPPORTED)
		]);

		return null;
	}

	/**
	 * Gets a file's metadata by ID.
	 *
	 * @param FileData $fileData
	 * @return array|null Describes file (id, title, size)
	 */
	public function getFileMetadata(FileData $fileData): ?FileData
	{
		$this->errorCollection->add([
			new Error(Loc::getMessage('DISK_BOARDS_VIEWER_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED'), self::ERROR_METHOD_IS_NOT_SUPPORTED)
		]);

		return null;
	}

	/**
	 * Download part of file from cloud service by FileData::id, put contents in FileData::src
	 * @param FileData $fileData
	 * @param          $startRange
	 * @param          $chunkSize
	 * @return FileData|null
	 */
	public function downloadPartFile(FileData $fileData, $startRange, $chunkSize): ?FileData
	{
		$this->errorCollection->add([
			new Error(Loc::getMessage('DISK_BOARDS_VIEWER_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED'), self::ERROR_METHOD_IS_NOT_SUPPORTED)
		]);

		return null;
	}

	/**
	 * Delete file from cloud service by FileData::id
	 * @param FileData $fileData
	 * @return bool
	 */
	public function deleteFile(FileData $fileData): bool
	{
		$this->errorCollection->add([
			new Error(Loc::getMessage('DISK_BOARDS_VIEWER_HANDLER_ERROR_METHOD_IS_NOT_SUPPORTED'), self::ERROR_METHOD_IS_NOT_SUPPORTED)
		]);

		return false;
	}

	public function getDataForViewFile(FileData $fileData): string
	{
		return '';
	}
}