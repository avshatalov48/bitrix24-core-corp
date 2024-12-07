<?php

namespace Bitrix\Crm\Integration\AI;

use Bitrix\Crm\Integration\AI\Dto\SummarizeCallTranscriptionPayload;
use Bitrix\Crm\Integration\AI\Dto\TranscribeCallRecordingPayload;
use Bitrix\Crm\Integration\AI\Model\QueueTable;
use Bitrix\Crm\Integration\AI\Operation\FillItemFieldsFromCallTranscription;
use Bitrix\Crm\Integration\AI\Operation\SummarizeCallTranscription;
use Bitrix\Crm\Integration\AI\Operation\TranscribeCallRecording;
use Bitrix\Crm\Integration\StorageManager;
use Bitrix\Crm\Integration\StorageType;
use Bitrix\Main\Error;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\IoException;

final class Feedback
{
	public static function grantConsent(Result $fillFieldsResult): \Bitrix\Main\Result
	{
		$validate = self::validate($fillFieldsResult);
		if (!$validate->isSuccess())
		{
			return $validate;
		}

		return QueueTable::update($fillFieldsResult->getJobId(), [
			'IS_FEEDBACK_CONSENT_GRANTED' => true,
		]);
	}

	public static function wasSent(Result $fillFieldsResult): bool
	{
		if (self::validate($fillFieldsResult)->isSuccess())
		{
			return (bool)QueueTable::query()
				->setSelect(['IS_FEEDBACK_SENT'])
				->where('ID', $fillFieldsResult->getJobId())
				->fetchObject()
				?->requireIsFeedbackSent()
			;
		}

		return false;
	}

	public static function addToSendQueue(Result $fillFieldsResult): \Bitrix\Main\Result
	{
		$result = new \Bitrix\Main\Result();

		$validate = self::validate($fillFieldsResult);
		if (!$validate->isSuccess())
		{
			return $result->addErrors($validate->getErrors());
		}

		$job = QueueTable::query()
			->setSelect(['IS_FEEDBACK_CONSENT_GRANTED', 'IS_FEEDBACK_SENT'])
			->where('ID', $fillFieldsResult->getJobId())
			->fetchObject()
		;
		if (!$job)
		{
			return $result->addError(ErrorCode::getNotFoundError());
		}

		if (!$job->requireIsFeedbackConsentGranted())
		{
			return $result->addError(new Error('no consent'));
		}

		if ($job->requireIsFeedbackSent())
		{
			return $result;
		}

		$summarize = QueueTable::query()
			->setSelect(['PARENT_ID', 'RESULT'])
			->where('ID', $fillFieldsResult->getParentJobId())
			->where('TYPE_ID', SummarizeCallTranscription::TYPE_ID)
			->where('EXECUTION_STATUS', QueueTable::EXECUTION_STATUS_SUCCESS) // no sense sending feedback on failed job
			->setLimit(1)
			->fetchObject()
		;
		if (!$summarize || $summarize->requireParentId() <= 0)
		{
			return $result->addError(ErrorCode::getNotFoundError());
		}

		$transcribe = QueueTable::query()
			->setSelect(['STORAGE_TYPE_ID', 'STORAGE_ELEMENT_ID', 'RESULT'])
			->where('ID', $summarize->requireParentId())
			->where('TYPE_ID', TranscribeCallRecording::TYPE_ID)
			->where('EXECUTION_STATUS', QueueTable::EXECUTION_STATUS_SUCCESS)
			->setLimit(1)
			->fetchObject()
		;
		if (!$transcribe)
		{
			return $result->addError(ErrorCode::getNotFoundError());
		}

		[$fileName, $fileContent] = self::prepareFile(
			$transcribe->requireStorageTypeId(),
			$transcribe->requireStorageElementId(),
		);
		if (empty($fileName) || empty($fileContent))
		{
			return $result->addError(ErrorCode::getFileNotFoundError());
		}

		/** @var TranscribeCallRecordingPayload|null $transcribePayload */
		$transcribePayload = TranscribeCallRecording::constructPayload((string)$transcribe->requireResult());
		if (!$transcribePayload)
		{
			return $result->addError(ErrorCode::getPayloadNotFoundError());
		}

		/** @var SummarizeCallTranscriptionPayload|null $summarizePayload */
		$summarizePayload = SummarizeCallTranscription::constructPayload((string)$summarize->requireResult());
		if (!$summarizePayload)
		{
			return $result->addError(ErrorCode::getPayloadNotFoundError());
		}

		$uploader = (new Feedback\Uploader(
			$fileName,
			$fileContent,
			$transcribePayload,
			$summarizePayload,
			$fillFieldsResult->getTarget(),
			$fillFieldsResult->getPayload(),
			$fillFieldsResult->getLanguageId() ?? ''
		));

		$uploader->sendAsync(
			static function () use ($job): void {
				$job->setIsFeedbackSent(true);
				$job->save();
			},
			static function () use ($job): void {
				$job->setIsFeedbackSent(false);
				$job->save();
			},
		);

		return $result;
	}

	private static function validate(Result $fillFieldsResult): \Bitrix\Main\Result
	{
		$result = new \Bitrix\Main\Result();

		if ($fillFieldsResult->getTypeId() !== FillItemFieldsFromCallTranscription::TYPE_ID)
		{
			return $result->addError(
				new Error(
					'Feedback can be sent only on ' . FillItemFieldsFromCallTranscription::class,
					ErrorCode::OPERATION_TYPE_NOT_SUPPORTED
				)
			);
		}

		if (
			!$fillFieldsResult->getPayload()
			|| !$fillFieldsResult->getTarget()
			|| $fillFieldsResult->getJobId() <= 0
			|| $fillFieldsResult->getParentJobId() <= 0
		)
		{
			return $result->addError(
				new Error(
					'Result should have filled payload, target, job id and parent job id fields',
					ErrorCode::REQUIRED_ARG_MISSING,
				)
			);
		}

		return $result;
	}

	private static function prepareFile(int $storageTypeId, int $storageElementId): array
	{
		if (!StorageType::isDefined($storageTypeId) || $storageElementId <= 0)
		{
			return ['', ''];
		}

		$fileArray = StorageManager::makeFileArray($storageElementId, $storageTypeId);
		if (!is_array($fileArray) || empty($fileArray['tmp_name']))
		{
			return ['', ''];
		}

		$name = $fileArray['ORIGINAL_NAME'] ?? $fileArray['name'] ?? null;
		if (!is_string($name) || empty($name))
		{
			return ['', ''];
		}

		$content = '';
		try
		{
			$content = (string)File::getFileContents($fileArray['tmp_name']);
		}
		catch (IoException $exception)
		{
			AIManager::logger()->warning(
				'{date}: {class}: Got exception when trying to check file: {exception}' . PHP_EOL,
				['class' => self::class, 'exception' => $exception],
			);
		}

		return [$name, $content];
	}
}
