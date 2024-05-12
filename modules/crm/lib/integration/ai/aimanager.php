<?php

namespace Bitrix\Crm\Integration\AI;

use Bitrix\AI\Context;
use Bitrix\AI\Engine;
use Bitrix\AI\Limiter\Usage;
use Bitrix\Crm\Integration\AI\Operation\FillItemFieldsFromCallTranscription;
use Bitrix\Crm\Integration\AI\Operation\Orchestrator;
use Bitrix\Crm\Integration\AI\Operation\SummarizeCallTranscription;
use Bitrix\Crm\Integration\AI\Operation\TranscribeCallRecording;
use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Crm\Integration\Market\Router;
use Bitrix\Crm\Integration\StorageType;
use Bitrix\Crm\Integration\VoxImplantManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Diag\Logger;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Security\Random;
use CCrmActivity;
use CCrmOwnerType;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class AIManager
{
	public const SUPPORTED_ENTITY_TYPE_IDS = FillItemFieldsFromCallTranscription::SUPPORTED_TARGET_ENTITY_TYPE_IDS;
	public const AI_LICENCE_FEATURE_NAME = 'ai_available_by_version';
	public const AI_PROVIDER_PARTNER_CRM = 'ai_provider_partner_crm';
	public const AI_DISABLED_SLIDER_CODE = 'limit_copilot_off';

	private const AI_CALL_PROCESSING_OPTION_NAME = 'AI_CALL_PROCESSING_ENABLED';
	private const AI_CALL_PROCESSING_AUTOMATICALLY_OPTION_NAME = 'AI_CALL_PROCESSING_ALLOWED_AUTO';
	private const AI_LIMIT_SLIDERS_MAP = [
		'Daily' => 'limit_copilot_max_number_daily_requests',
		'Monthly' => 'limit_copilot_requests',
	];
	private const AI_APP_COLLECTION_MARKET_MAP = [
		'ru' => 19021440,
		'by' => 19021806,
		'kz' => 19021810,
	];
	private const AI_APP_COLLECTION_MARKET_DEFAULT = 19021800;

	private const AUDIO_FILE_MIN_SIZE = 5 * 1024;
	private const AUDIO_FILE_MAX_SIZE = 25 * 1024 * 1024;
	private const AUDIO_MIN_CALL_TIME = 10;
	private const AUDIO_MAX_CALL_TIME = 60 * 60;

	public static function isAvailable(): bool
	{
		static $regionBlacklist = [
			'ua',
		];

		$region = Application::getInstance()->getLicense()->getRegion();
		if (
			$region === null // block AI in unknown region just in case
			|| in_array(mb_strtolower($region), $regionBlacklist, true)
		)
		{
			return false;
		}

		return Loader::includeModule('ai') && Loader::includeModule('bitrix24');
	}

	public static function isEnabledInGlobalSettings(string $code = EventHandler::SETTINGS_FILL_ITEM_FROM_CALL_ENABLED_CODE): bool
	{
		if (!self::isAvailable())
		{
			return false;
		}

		static $manager = null;
		if (!$manager)
		{
			$manager = new \Bitrix\AI\Tuning\Manager();
		}

		if ($code === EventHandler::SETTINGS_FILL_ITEM_FROM_CALL_ENABLED_CODE)
		{
			$item = $manager->getItem(EventHandler::SETTINGS_FILL_ITEM_FROM_CALL_ENABLED_CODE);
		}
		elseif ($code === EventHandler::SETTINGS_FILL_CRM_TEXT_ENABLED_CODE)
		{
			if (!self::isEngineAvailable(EventHandler::ENGINE_CATEGORY))
			{
				return false;
			}

			$item = $manager->getItem(EventHandler::SETTINGS_FILL_CRM_TEXT_ENABLED_CODE);
		}

		return isset($item) && $item->getValue();
	}

	public static function isEngineAvailable(string $type): bool
	{
		if (!self::isAvailable())
		{
			return false;
		}

		$engine = Engine::getByCategory($type, Context::getFake());
		if (!$engine)
		{
			return false;
		}

		return true;
	}

	public static function isAiCallProcessingEnabled(): bool
	{
		return
			self::isAvailable()
			&& Option::get('crm', self::AI_CALL_PROCESSING_OPTION_NAME, false)
		;
	}

	public static function isAiCallAutomaticProcessingAllowed(): bool
	{
		$region = Application::getInstance()->getLicense()->getRegion();
		$defaultValue = !in_array($region, ['ru', 'by', 'kz']);

		return
			self::isAiCallProcessingEnabled()
			&& Option::get('crm', self::AI_CALL_PROCESSING_AUTOMATICALLY_OPTION_NAME, $defaultValue)
		;
	}

	public static function isAiLicenceExceededAccepted(): bool
	{
		return
			self::isAvailable()
			&& Bitrix24Manager::isFeatureEnabled(self::AI_LICENCE_FEATURE_NAME)
		;
	}

	public static function setAiCallProcessingEnabled(bool $isEnabled): void
	{
		Option::set('crm', self::AI_CALL_PROCESSING_OPTION_NAME, $isEnabled);
		if ($isEnabled)
		{
			\Bitrix\Main\Config\Option::set('bitrix24', 'eula_231115_is_ready', 'Y');
			\Bitrix\Main\Config\Option::set('ai', '~enable_settings', 'Y');
		}
	}

	public static function setAiCallAutomaticProcessingAllowed(?bool $isAllowed): void
	{
		if (is_null($isAllowed))
		{
			Option::delete('crm', ['name' => self::AI_CALL_PROCESSING_AUTOMATICALLY_OPTION_NAME]);
		}
		else
		{
			Option::set('crm', self::AI_CALL_PROCESSING_AUTOMATICALLY_OPTION_NAME, $isAllowed);
		}
	}

	public static function isStubMode(): bool
	{
		return Option::get('crm', 'dev_ai_stub_mode', 'N') === 'Y';
	}

	public static function isLaunchOperationsSuccess(int $entityTypeId, int $entityId, int $activityId, bool $checkBindings = true): bool
	{
		if (
			$activityId <= 0
			|| !in_array($entityTypeId, self::SUPPORTED_ENTITY_TYPE_IDS, true)
		)
		{
			return false;
		}

		$repoInstance = JobRepository::getInstance();

		if ($checkBindings)
		{
			$bindings = CCrmActivity::GetBindings($activityId);
			$bindings = is_array($bindings) ? $bindings : [];
			$bindings = array_filter(
				$bindings,
				static fn(array $row) => in_array((int)$row['OWNER_TYPE_ID'], self::SUPPORTED_ENTITY_TYPE_IDS, true) && $entityTypeId !== (int)$row['OWNER_TYPE_ID']
			);

			foreach ($bindings as $binding)
			{
				if (self::isLaunchOperationsSuccess($binding['OWNER_TYPE_ID'], $binding['OWNER_ID'], $activityId, false))
				{
					return true;
				}
			}
		}

		return
			$repoInstance->getTranscribeCallRecordingResultByActivity($activityId)?->isSuccess()
			&& $repoInstance->getSummarizeCallTranscriptionResultByActivity($activityId)?->isSuccess()
			&& $repoInstance->getFillItemFieldsFromCallTranscriptionResult(
				new ItemIdentifier($entityTypeId, $entityId),
				$activityId
			)?->isSuccess()
		;
	}

	public static function isLaunchOperationsPending(int $entityTypeId, int $entityId, int $activityId): bool
	{
		if (
			$activityId <= 0
			|| !in_array($entityTypeId, self::SUPPORTED_ENTITY_TYPE_IDS, true)
		)
		{
			return false;
		}

		$repoInstance = JobRepository::getInstance();

		return
			$repoInstance->getTranscribeCallRecordingResultByActivity($activityId)?->isPending()
			|| $repoInstance->getSummarizeCallTranscriptionResultByActivity($activityId)?->isPending()
			|| $repoInstance->getFillItemFieldsFromCallTranscriptionResult(
				new ItemIdentifier($entityTypeId, $entityId),
				$activityId
			)?->isPending()
		;
	}

	public static function isLaunchOperationsErrorsLimitExceeded(int $entityTypeId, int $entityId, int $activityId): bool
	{
		if (
			$activityId <= 0
			|| !in_array($entityTypeId, self::SUPPORTED_ENTITY_TYPE_IDS, true)
		)
		{
			return true;
		}

		$repoInstance = JobRepository::getInstance();

		$transcribeJobResult = $repoInstance->getTranscribeCallRecordingResultByActivity($activityId);
		if ($transcribeJobResult?->isErrorsLimitExceeded())
		{
			return true;
		}

		$summarizeJobResult = $repoInstance->getSummarizeCallTranscriptionResultByActivity($activityId);
		if ($summarizeJobResult?->isErrorsLimitExceeded())
		{
			return true;
		}

		$fillItemFieldsJobResult = $repoInstance->getFillItemFieldsFromCallTranscriptionResult(
			new ItemIdentifier($entityTypeId, $entityId),
			$activityId
		);
		if ($fillItemFieldsJobResult?->isErrorsLimitExceeded())
		{
			return true;
		}

		return false;
	}

	public static function registerStubJob(Engine $engine, mixed $payload): string
	{
		$hash = md5(Random::getString(10, true));

		Application::getInstance()->addBackgroundJob(static function() use ($hash, $engine, $payload) {
			$result = new \Bitrix\AI\Result($payload, $payload);

			$event = new Event(
				'ai',
				'onQueueJobExecute',
				[
					'queue' => $hash,
					'engine' => $engine->getIEngine(),
					'result' => $result,
					'error' => null,
				]
			);

			$waitTime = (int)Option::get('crm', 'dev_ai_stub_mode_wait_time', 3);
			if ($waitTime > 0)
			{
				sleep($waitTime);
			}

			$event->send();
		});

		return $hash;
	}

	/**
	 * @internal
	 */
	public static function launchFillItemFromCallRecordingScenario(
		int $activityId,
		?int $userId = null,
		?int $storageTypeId = null,
		?int $storageElementId = null,
	): Result
	{
		$jobRepo = JobRepository::getInstance();

		$transcriptionResult = $jobRepo->getTranscribeCallRecordingResultByActivity($activityId);
		if ($transcriptionResult?->isPending())
		{
			return $transcriptionResult;
		}
		elseif (!$transcriptionResult?->isSuccess())
		{
			return self::launchCallRecordingTranscription($activityId, $userId, $storageTypeId, $storageElementId);
		}

		$summarizeResult = $jobRepo->getSummarizeCallTranscriptionResultByActivity($activityId);
		if ($summarizeResult?->isPending())
		{
			return $summarizeResult;
		}
		elseif (!$summarizeResult?->isSuccess())
		{
			$operation = new SummarizeCallTranscription(
				new ItemIdentifier(\CCrmOwnerType::Activity, $activityId),
				$transcriptionResult->getPayload()->transcription,
				$userId,
				$transcriptionResult->getJobId(),
			);

			return $operation->launch();
		}

		$fillTarget = (new Orchestrator())->findPossibleFillFieldsTarget($activityId);
		if (!$fillTarget)
		{
			return (new Result(FillItemFieldsFromCallTranscription::TYPE_ID))->addError(
				ErrorCode::getNotFoundError(),
			);
		}

		$fillResult = $jobRepo->getFillItemFieldsFromCallTranscriptionResult($fillTarget, $activityId);
		if ($fillResult?->isPending())
		{
			return $fillResult;
		}
		elseif (!$fillResult?->isSuccess())
		{
			$operation = new FillItemFieldsFromCallTranscription(
				$fillTarget,
				$summarizeResult->getPayload()->summary,
				$userId,
				$summarizeResult->getJobId(),
			);

			return $operation->launch();
		}

		return $fillResult;
	}

	public static function launchCallRecordingTranscription(
		int $activityId,
		?int $userId = null,
		?int $storageTypeId = null,
		?int $storageElementId = null,
		bool $isManualLaunch = true,
	): Result
	{
		$result = new Result(TranscribeCallRecording::TYPE_ID);

		if (!self::isAvailable() || !self::isAiCallProcessingEnabled())
		{
			return $result->addError(ErrorCode::getAINotAvailableError());
		}

		if ($activityId <= 0)
		{
			return $result->addError(ErrorCode::getNotFoundError());
		}

		if (!TranscribeCallRecording::isSuitableTarget(new ItemIdentifier(CCrmOwnerType::Activity, $activityId)))
		{
			return $result->addError(ErrorCode::getNotSuitableTargetError());
		}

		if (!StorageType::isDefined($storageTypeId) || $storageElementId <= 0)
		{
			$activity = Container::getInstance()->getActivityBroker()->getById($activityId);
			if (!is_array($activity))
			{
				return $result->addError(ErrorCode::getNotFoundError());
			}

			$storageTypeId = $activity['STORAGE_TYPE_ID'] ?? null;

			if (!empty($activity['STORAGE_ELEMENT_IDS']) && is_string($activity['STORAGE_ELEMENT_IDS']))
			{
				$fileIds = unserialize($activity['STORAGE_ELEMENT_IDS'], ['allowed_classes' => false]);
				if (is_array($fileIds))
				{
					$storageElementId = max(array_filter(array_map('intval', $fileIds), fn(int $id) => $id > 0));
				}
			}
		}

		if (!StorageType::isDefined($storageTypeId) || $storageElementId <= 0)
		{
			return $result->addError(ErrorCode::getFileNotFoundError());
		}

		$operation = new TranscribeCallRecording(
			new ItemIdentifier(CCrmOwnerType::Activity, $activityId),
			$storageTypeId,
			$storageElementId,
			$userId,
		);

		$operation->setIsManualLaunch($isManualLaunch);

		return $operation->launch();
	}

	public static function getAllOperationTypes(): array
	{
		return [
			TranscribeCallRecording::TYPE_ID,
			SummarizeCallTranscription::TYPE_ID,
			FillItemFieldsFromCallTranscription::TYPE_ID,
		];
	}

	public static function logger(): LoggerInterface
	{
		$customLoggerFromSettings = Logger::create('crm.integration.AI');
		if ($customLoggerFromSettings)
		{
			return $customLoggerFromSettings;
		}

		if (ModuleManager::isModuleInstalled('bitrix24'))
		{
			$logger = new class extends Logger {
				protected function logMessage(string $level, string $message): void
				{
					$host = Application::getInstance()->getContext()->getServer()->getHttpHost();
					AddMessage2Log("crm.integration.AI {$host} {$level} {$message}", 'crm');
				}
			};

			$logger->setLevel(Option::get('crm', 'log_integration_ai_level', LogLevel::CRITICAL));

			return $logger;
		}

		return new NullLogger();
	}

	public static function getLimitSliderCode(Engine $engine): ?string
	{
		if (!self::isAvailable())
		{
			return null;
		}

		$code = null;
		$limiter = new Usage($engine->getIEngine()->getContext());
		$limiter->isInLimit($code);

		return self::AI_LIMIT_SLIDERS_MAP[$code] ?? null;
	}

	public static function getAiAppCollectionMarketLink(): string
	{
		$region = mb_strtolower(Application::getInstance()->getLicense()->getRegion());
		$collectionId = self::AI_APP_COLLECTION_MARKET_MAP[$region] ?? self::AI_APP_COLLECTION_MARKET_DEFAULT;

		return Router::getBasePath() . 'collection/' . $collectionId . '/';
	}

	public static function checkForSuitableAudios(string $originId, int $storageTypeId, string $storageElementIdsSerialized): \Bitrix\Main\Result
	{
		$result = new \Bitrix\Main\Result();

		self::logger()->debug(
			'{date}: Check for suitable audios. Storage type={storageTypeId}, elementIds={storageElementIds}, originId={originId}' . PHP_EOL,
			[
				'storageTypeId' => $storageTypeId,
				'storageElementIds' => $storageElementIdsSerialized,
				'originId' => $originId,
			],
		);

		if (!StorageType::isDefined($storageTypeId))
		{
			self::logger()->error(
				'{date}: Error while check for suitable audios. Wrong storage type={storageTypeId}' . PHP_EOL,
				[
					'storageTypeId' => $storageTypeId,
				],
			);
			$result->addError(new Error('Wrong storage type', 'WRONG_STORAGE_TYPE'));

			return $result;
		}

		$storageElementIds = (array)unserialize($storageElementIdsSerialized, ['allowed_classes' => false]);
		$storageElementIds = array_map('intval', $storageElementIds);
		$storageElementIds = array_filter($storageElementIds, fn(int $id) => $id > 0);

		if (empty($storageElementIds))
		{
			self::logger()->error(
				'{date}: Error while check for suitable audios. Empty storageElementIds' . PHP_EOL,
			);
			$result->addError(new Error('Empty storage elements', 'EMPTY_STORAGE_ELEMENTS'));

			return $result;
		}
		$fileId = max($storageElementIds);

		$bFileId = null;
		if ($storageTypeId === StorageType::Disk && \Bitrix\Main\Loader::includeModule('disk'))
		{
			$bFileId = \Bitrix\Disk\File::loadById($fileId)?->getFileId();
		}
		elseif ($storageTypeId === StorageType::File)
		{
			$bFileId = $fileId;
		}

		if ($bFileId <= 0)
		{
			self::logger()->error(
				'{date}: Error while check for suitable audios. Wrong bFileId {bFileId}' . PHP_EOL,
				[
					'bFileId' => $bFileId,
				]
			);
			$result->addError(new Error('Wrong bFileId', 'WRONG_BFILE_ID'));

			return $result;
		}

		$file = \CFile::GetFileArray($bFileId);
		$fileExt = (string)\GetFileExtension($file['ORIGINAL_NAME'] ?? '');

		// check if wrong extension:
		if (!in_array(mb_strtolower($fileExt), \Bitrix\Crm\Service\Timeline\Config::ALLOWED_AUDIO_EXTENSIONS, true))
		{
			self::logger()->error(
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
			self::logger()->error(
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
			self::logger()->error(
				'{date}: Error while check for suitable audios. File is too large: {size} of {maxSize}' . PHP_EOL,
				[
					'size' => $fileSize,
					'maxSize' => $maxFileSize,
				]
			);
			$result->addError(new Error('File is too large', 'FILE_TOO_LARGE'));

			return $result;
		}

		if ($originId !== '')
		{
			$callId = mb_substr($originId, 3);
			$callInfo = VoxImplantManager::getCallInfo($callId);
			$callDuration = $callInfo['DURATION'] ?? 0;

			$minCallDuration = (int)Option::get('crm', 'ai_integration_audio_min_call_time', self::AUDIO_MIN_CALL_TIME);
			$maxCallDuration = (int)Option::get('crm', 'ai_integration_audio_max_call_time', self::AUDIO_MAX_CALL_TIME);

			if ($callDuration > 0 && $callDuration < $minCallDuration)
			{
				self::logger()->error(
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
				self::logger()->error(
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
}
