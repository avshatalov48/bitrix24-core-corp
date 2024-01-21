<?php

namespace Bitrix\Crm\Integration\AI\Operation;

use Bitrix\Crm\Activity\Provider\Call;
use Bitrix\Crm\Badge;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Integration\AI\Analytics;
use Bitrix\Crm\Integration\AI\Dto\TranscribeCallRecordingPayload;
use Bitrix\Crm\Integration\AI\ErrorCode;
use Bitrix\Crm\Integration\AI\Model\EO_Queue;
use Bitrix\Crm\Integration\AI\Result;
use Bitrix\Crm\Integration\StorageType;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline\Ai\Call\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Web\Uri;

final class TranscribeCallRecording extends AbstractOperation
{
	public const TYPE_ID = 1;
	public const CONTEXT_ID = 'transcribe_call_recording';

	public const SUPPORTED_TARGET_ENTITY_TYPE_IDS = [
		\CCrmOwnerType::Activity,
	];

	public const SUPPORTED_AUDIO_EXTENSIONS = \Bitrix\Crm\Service\Timeline\Config::ALLOWED_AUDIO_EXTENSIONS;

	protected const PAYLOAD_CLASS = TranscribeCallRecordingPayload::class;
	protected const ENGINE_CATEGORY = 'audio';

	public function __construct(
		ItemIdentifier $target,
		private int $storageTypeId,
		private int $storageElementId,
		?int $userId = null,
		?int $parentJobId = null,
	)
	{
		parent::__construct($target, $userId, $parentJobId);
	}

	public static function isSuitableTarget(ItemIdentifier $target): bool
	{
		if ($target->getEntityTypeId() === \CCrmOwnerType::Activity)
		{
			$activity = Container::getInstance()->getActivityBroker()->getById($target->getEntityId());
			if (
				is_array($activity)
				&& isset($activity['PROVIDER_ID'])
				&& $activity['PROVIDER_ID'] === Call::ACTIVITY_PROVIDER_ID
				// check that it's a real call from voximplant or another telephony
				&& !empty($activity['ORIGIN_ID'])
			)
			{
				return true;
			}
		}

		return false;
	}

	protected function getAIPayload(): \Bitrix\Main\Result
	{
		$result = new \Bitrix\Main\Result();

		[$fileUrl, $contentType, $originalFileName] = $this->getFileInfo(
			$this->storageTypeId,
			$this->storageElementId,
		);
		if (!$fileUrl || !$contentType)
		{
			return $result->addError(ErrorCode::getFileNotFoundError());
		}

		if (
			!empty($originalFileName)
			&& !in_array(GetFileExtension($originalFileName), self::SUPPORTED_AUDIO_EXTENSIONS, true)
		)
		{
			return $result->addError(new Error(
				'File is not a supported as a call recording.'
				. ' Allowed extensions are: ' . implode(', ', self::SUPPORTED_AUDIO_EXTENSIONS),
				ErrorCode::FILE_NOT_SUPPORTED,
				['supportedExtensions' => self::SUPPORTED_AUDIO_EXTENSIONS],
			));
		}

		return $result->setData([
			'payload' =>
				(new \Bitrix\AI\Payload\Audio($fileUrl))
					->setMarkers(['type' => $contentType])
			,
		]);
	}

	private function getFileInfo(int $storageTypeId, int $fileId): array
	{
		if ($fileId <= 0)
		{
			return ['', '', ''];
		}

		$bFileId = null;
		if ($storageTypeId === StorageType::Disk)
		{
			if (\Bitrix\Main\Loader::includeModule('disk'))
			{
				$bFileId = \Bitrix\Disk\File::loadById($fileId)?->getFileId();
			}
		}
		elseif ($storageTypeId === StorageType::File)
		{
			$bFileId = $fileId;
		}

		if ($bFileId <= 0)
		{
			return ['', '', ''];
		}

		$file = \CFile::GetFileArray($bFileId);
		if (!is_array($file) || empty($file['SRC']) || empty($file['CONTENT_TYPE']))
		{
			return ['', '', ''];
		}

		$uri = new Uri($file['SRC']);
		//todo check with 'clouds' installed
		if (empty($uri->getHost()))
		{
			// it seems that file is stored locally in /upload
			$host = \Bitrix\AI\Config::getValue('public_url') ?: \Bitrix\Main\Engine\UrlManager::getInstance()->getHostUrl();
			$uri = (new Uri($host))->setPath($file['SRC']);
		}

		return [
			(string)$uri,
			(string)$file['CONTENT_TYPE'],
			(string)($file['ORIGINAL_NAME'] ?? null),
		];
	}


	protected function getStubPayload(): string
	{
		return 'This is stub call transcription';
	}

	protected function getJobAddFields(): array
	{
		return
			['STORAGE_TYPE_ID' => $this->storageTypeId, 'STORAGE_ELEMENT_ID' => $this->storageElementId]
			+ parent::getJobAddFields()
		;
	}

	protected function getJobUpdateFields(): array
	{
		return
			['STORAGE_TYPE_ID' => $this->storageTypeId, 'STORAGE_ELEMENT_ID' => $this->storageElementId]
			+ parent::getJobUpdateFields()
		;
	}

	protected static function notifyTimelineAfterSuccessfulLaunch(Result $result): void
	{
		$nextTarget = (new Orchestrator())->findPossibleFillFieldsTarget($result->getTarget()?->getEntityId());
		if ($nextTarget)
		{
			Controller::getInstance()->onStartRecordTranscript(
				$nextTarget,
				$result->getTarget()?->getEntityId(),
				$result->getUserId(),
			);
		}
	}

	protected static function notifyTimelineAfterSuccessfulJobFinish(Result $result): void
	{
		$nextTarget = (new Orchestrator())->findPossibleFillFieldsTarget($result->getTarget()?->getEntityId());
		if ($nextTarget)
		{
			Controller::getInstance()->onFinishRecordTranscript(
				$nextTarget,
				$result->getTarget()?->getEntityId(),
				[],
				$result->getUserId(),
			);
		}
	}

	protected static function notifyAboutJobError(
		Result $result,
		bool $withSyncBadges = true,
		bool $withSendAnalytics = true
	): void
	{
		$activityId = $result->getTarget()?->getEntityId();
		$nextTarget = (new Orchestrator())->findPossibleFillFieldsTarget($activityId);
		if ($nextTarget)
		{
			if ($withSyncBadges)
			{
				Controller::getInstance()->onLaunchError(
					$nextTarget,
					$activityId,
					[
						'OPERATION_TYPE_ID' => self::TYPE_ID,
						'ERRORS' => $result->getErrorMessages(),
					],
					$result->getUserId(),
				);

				self::syncBadges($activityId, Badge\Type\AiCallFieldsFillingResult::ERROR_PROCESS_VALUE);
			}

			self::notifyTimelinesAboutActivityUpdate($activityId);

			if ($withSendAnalytics)
			{
				self::sendAnalyticsWrapper($activityId, Analytics::STATUS_ERROR_GPT);
			}
		}
	}

	protected static function extractPayloadFromAIResult(\Bitrix\AI\Result $result, EO_Queue $job): Dto
	{
		return new TranscribeCallRecordingPayload([
			'transcription' => $result->getPrettifiedData(),
		]);
	}
}
