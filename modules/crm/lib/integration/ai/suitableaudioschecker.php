<?php

namespace Bitrix\Crm\Integration\AI;

use Bitrix\Crm\Integration\StorageType;
use Bitrix\Crm\Integration\VoxImplantManager;
use Bitrix\Crm\Service\Timeline;
use Bitrix\Disk\File;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Psr\Log\LoggerInterface;

final class SuitableAudiosChecker
{
	private const AUDIO_FILE_MIN_SIZE = 60 * 1024;
	private const AUDIO_FILE_MAX_SIZE = 25 * 1024 * 1024;
	private const AUDIO_MIN_CALL_TIME = 10;
	private const AUDIO_MAX_CALL_TIME = 60 * 60;

	private LoggerInterface $logger;
	private bool $isDiskEnabled;

	public function __construct(
		readonly string $originId,
		readonly int $storageTypeId,
		readonly string $storageElementIdsSerialized
	)
	{
		$this->logger = AIManager::logger();

		$this->isDiskEnabled = Loader::includeModule('disk');
	}

	public function run(): Result
	{
		$result = new Result();

		$this->logger->debug(
			'{date}: Check for suitable audios. Storage type={storageTypeId}, elementIds={storageElementIds}, originId={originId}' . PHP_EOL,
			[
				'storageTypeId' => $this->storageTypeId,
				'storageElementIds' => $this->storageElementIdsSerialized,
				'originId' => $this->originId,
			],
		);

		if (!StorageType::isDefined($this->storageTypeId))
		{
			$this->logger->error(
				'{date}: Error while check for suitable audios. Wrong storage type={storageTypeId}' . PHP_EOL,
				[
					'storageTypeId' => $this->storageTypeId,
				],
			);
			$result->addError(new Error('Wrong storage type', 'WRONG_STORAGE_TYPE'));

			return $result;
		}

		$storageElementIds = $this->getStorageElementIds();
		if (empty($storageElementIds))
		{
			$this->logger->error(
				'{date}: Error while check for suitable audios. Empty storageElementIds' . PHP_EOL,
			);
			$result->addError(new Error('Empty storage elements', 'EMPTY_STORAGE_ELEMENTS'));

			return $result;
		}

		$fileId = max($storageElementIds);
		$fileIdFormBFile = $this->getFileIdFormBFile($fileId);
		if ($fileIdFormBFile <= 0)
		{
			$this->logger->error(
				'{date}: Error while check for suitable audios. Wrong bFileId {bFileId}' . PHP_EOL,
				[
					'bFileId' => $fileIdFormBFile,
				]
			);
			$result->addError(new Error('Wrong fileIdFormBFile', 'WRONG_BFILE_ID'));

			return $result;
		}

		$file = \CFile::GetFileArray($fileIdFormBFile);
		$fileExt = (string)\GetFileExtension($file['ORIGINAL_NAME'] ?? '');

		// check if wrong extension:
		if (!in_array(mb_strtolower($fileExt), Timeline\Config::ALLOWED_AUDIO_EXTENSIONS, true))
		{
			$this->logger->error(
				'{date}: Error while check for suitable audios. Wrong file extension {ext}' . PHP_EOL,
				[
					'ext' => $fileExt,
				]
			);
			$result->addError(new Error('Wrong file extension '.$fileExt, 'WRONG_FILE_EXTENSION'));

			return $result;
		}

		$minFileSize = (int)Option::get('crm', 'ai_integration_audiofile_min_size', self::AUDIO_FILE_MIN_SIZE);
		$maxFileSize = (int)Option::get('crm', 'ai_integration_audiofile_max_size', self::AUDIO_FILE_MAX_SIZE);
		$fileSize = (int)($file['FILE_SIZE'] ?? 0);
		if ($fileSize < $minFileSize)
		{
			$this->logger->error(
				'{date}: Error while check for suitable audios. File is too small: {size} of {minSize}' . PHP_EOL,
				[
					'size' => $fileSize,
					'minSize' => $minFileSize,
				]
			);
			$result->addError(new Error('File is too small', 'FILE_TOO_SMALL'));

			return $result;
		}

		if ($fileSize > $maxFileSize)
		{
			$this->logger->error(
				'{date}: Error while check for suitable audios. File is too large: {size} of {maxSize}' . PHP_EOL,
				[
					'size' => $fileSize,
					'maxSize' => $maxFileSize,
				]
			);
			$result->addError(new Error('File is too large', 'FILE_TOO_LARGE'));

			return $result;
		}

		if (VoxImplantManager::isVoxImplantOriginId($this->originId))
		{
			$callId = VoxImplantManager::extractCallIdFromOriginId($this->originId);
			$callDuration = VoxImplantManager::getCallDuration($callId) ?? 0;

			$minCallDuration = (int)Option::get('crm', 'ai_integration_audio_min_call_time', self::AUDIO_MIN_CALL_TIME);
			$maxCallDuration = (int)Option::get('crm', 'ai_integration_audio_max_call_time', self::AUDIO_MAX_CALL_TIME);

			if ($callDuration > 0 && $callDuration < $minCallDuration)
			{
				$this->logger->error(
					'{date}: Error while check for suitable audios. Call is too short: {callDuration} of {minCallDuration}' . PHP_EOL,
					[
						'callDuration' => $callDuration,
						'minCallDuration' => $minCallDuration,
					]
				);
				$result->addError(new Error('Call is too short', 'CALL_TOO_SHORT'));

				return $result;
			}

			if ($callDuration > $maxCallDuration)
			{
				$this->logger->error(
					'{date}: Error while check for suitable audios. Call is too long: {callDuration} of {maxCallDuration}' . PHP_EOL,
					[
						'callDuration' => $callDuration,
						'maxCallDuration' => $maxCallDuration,
					]
				);
				$result->addError(new Error('Call is too long', 'CALL_TOO_LONG'));

				return $result;
			}
		}

		return $result;
	}

	private function getStorageElementIds(): array
	{
		$result = (array)unserialize(
			$this->storageElementIdsSerialized,
			['allowed_classes' => false]
		);
		$result = array_map('intval', $result);

		return array_filter($result, static fn(int $id) => $id > 0);
	}

	private function getFileIdFormBFile(int $fileId): ?int
	{
		$result = null;
		if ($this->storageTypeId === StorageType::Disk && $this->isDiskEnabled)
		{
			$result = File::loadById($fileId)?->getFileId();
		}
		elseif ($this->storageTypeId === StorageType::File)
		{
			$result = $fileId;
		}

		return $result;
	}
}
