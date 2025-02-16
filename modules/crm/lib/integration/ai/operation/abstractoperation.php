<?php

namespace Bitrix\Crm\Integration\AI\Operation;

use Bitrix\AI\Context;
use Bitrix\AI\Engine;
use Bitrix\AI\Tuning\Manager;
use Bitrix\Crm\Badge;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Config;
use Bitrix\Crm\Integration\AI\ErrorCode;
use Bitrix\Crm\Integration\AI\EventHandler;
use Bitrix\Crm\Integration\AI\Model\EO_Queue;
use Bitrix\Crm\Integration\AI\Model\QueueTable;
use Bitrix\Crm\Integration\AI\Result;
use Bitrix\Crm\Integration\Analytics\Builder\AI\AIBaseEvent;
use Bitrix\Crm\Integration\Analytics\Builder\AI\CallParsingEvent;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Monitor;
use Bitrix\Crm\Timeline\ActivityController;
use Bitrix\Crm\Timeline\AI\Call\Controller;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\FieldTypeMask;
use Bitrix\Main\Web\Json;
use Bitrix\Rest\Marketplace;
use CCrmActivity;
use CCrmOwnerType;

/**
 * @todo refactor
 */
abstract class AbstractOperation
{
	public const TYPE_ID = 0;
	public const CONTEXT_ID = '';

	protected static int $engineId = 0;

	/** @var class-string<Dto> */
	protected const PAYLOAD_CLASS = Dto::class;
	protected const ENGINE_CATEGORY = 'text';
	protected const ENGINE_CODE = EventHandler::SETTINGS_FILL_ITEM_FROM_CALL_ENGINE_TEXT_CODE;

	private bool $isManualLaunch = true;
	private ?string $scenario;
	private ?string $contextLanguageId;

	public function __construct(
		protected ItemIdentifier $target,
		protected ?int $userId = null,
		private ?int $parentJobId = null,
	)
	{
		$this->userId ??= Container::getInstance()->getContext()->getUserId();
		$this->contextLanguageId = $this->getContextLanguageId();
		$this->scenario = Scenario::FULL_SCENARIO;
	}

	public static function isSuitableTarget(ItemIdentifier $target): bool
	{
		return true;
	}

	protected static function checkPreviousJobs(ItemIdentifier $target, int $parentId): \Bitrix\Main\Result
	{
		$result = new \Bitrix\Main\Result();

		$previousJob = static::findDuplicateJob($target, $parentId);
		if (!$previousJob)
		{
			return $result;
		}

		$result->setData(['previousJob' => $previousJob]);

		if (
			$previousJob->requireExecutionStatus() === QueueTable::EXECUTION_STATUS_SUCCESS
			|| $previousJob->requireExecutionStatus() === QueueTable::EXECUTION_STATUS_PENDING
		)
		{
			return $result->addError(ErrorCode::getJobAlreadyExistsError());
		}

		if (
			$previousJob->requireExecutionStatus() === QueueTable::EXECUTION_STATUS_ERROR
			&& $previousJob->requireRetryCount() >= Result::MAX_RETRY_COUNT
		)
		{
			return $result->addError(ErrorCode::getJobMaxRetriesExceededError());
		}

		return $result;
	}

	protected static function findDuplicateJob(ItemIdentifier $target, int $parentId): ?EO_Queue
	{
		return QueueTable::query()
			->setSelect(['ID', 'EXECUTION_STATUS', 'RETRY_COUNT'])
			->where('ENTITY_TYPE_ID', $target->getEntityTypeId())
			->where('ENTITY_ID', $target->getEntityId())
			->where('TYPE_ID', static::TYPE_ID)
			// we don't care about parent id - only one job of this type is allowed
			->setLimit(1)
			->fetchObject()
		;
	}

	public function setIsManualLaunch(bool $isManualLaunch): self
	{
		$this->isManualLaunch = $isManualLaunch;

		return $this;
	}

	public function setScenario(string $scenario): self
	{
		$this->scenario = $scenario;

		return $this;
	}

	public function launch(): Result
	{
		AIManager::logger()->debug(
			'{date}: {class}: Trying to launch operation {operationType} on target {target}'
			. ' for user {userId} and parent job {parentJobId}' . PHP_EOL,
			[
				'class' => static::class,
				'operationType' => static::TYPE_ID,
				'target' => $this->target,
				'userId' => $this->userId,
				'parentJobId' => $this->parentJobId,
			],
		);

		$result = new Result(
			static::TYPE_ID,
			$this->target,
			$this->userId,
			parentJobId: $this->parentJobId,
			isManualLaunch: $this->isManualLaunch,
		);

		if (!AIManager::isAILicenceAccepted($this->userId))
		{
			AIManager::logger()->error(
				'{date}: {class}: Cant start operation {operationType} on {target} because the license agreement to use AI has not been accepted' . PHP_EOL,
				['class' => static::class, 'target' => $this->target, 'operationType' => static::TYPE_ID],
			);

			$result->addError(ErrorCode::getLicenseNotAcceptedError());

			static::notifyAboutJobError($result, false);

			return $result;
		}

		if (!AIManager::isAvailable())
		{
			AIManager::logger()->error(
				'{date}: {class}: Cant start operation {operationType} on {target} because AI module is not installed' . PHP_EOL,
				['class' => static::class, 'target' => $this->target, 'operationType' => static::TYPE_ID],
			);

			$result->addError(ErrorCode::getAINotAvailableError());

			static::notifyAboutJobError($result, false);

			return $result;
		}

		if (!Scenario::isEnabledScenario($this->scenario))
		{
			AIManager::logger()->error(
				'{date}: {class}: Cant start operation because AI operations in CRM is disabled (see settings). Target {target}, operation {operationType}, scenario {scenario}' . PHP_EOL,
				[
					'class' => static::class,
					'target' => $this->target,
					'operationType' => static::TYPE_ID,
					'scenario' => $this->scenario,
				],
			);

			$result->addError(ErrorCode::getAIDisabledError(['sliderCode' => Scenario::SLIDER_CODE_MAP[$this->scenario]]));

			static::notifyAboutJobError($result, false);

			return $result;
		}

		if (!static::isSuitableTarget($this->target))
		{
			AIManager::logger()->error(
				'{date}: {class}: Target {target} is not suitable for this operation {operationType}' . PHP_EOL,
				['class' => static::class, 'target' => $this->target, 'operationType' => static::TYPE_ID],
			);

			$result->addError(ErrorCode::getNotSuitableTargetError());

			static::notifyAboutJobError($result, false);

			return $result;
		}

		$context = $this->getAIEngineContext();
		$engine = $this->getAIEngine($context);
		if (!$engine)
		{
			AIManager::logger()->critical(
				'{date}: {class}: Cant start operation {operationType} on {target} because there is no relevant AI engine!'
				. ' Category {engineCategory}, context {engineContext}' . PHP_EOL,
				[
					'class' => static::class,
					'target' => $this->target,
					'operationType' => static::TYPE_ID,
					'engineCategory' => static::ENGINE_CATEGORY,
					'engineContext' => $context,
				],
			);

			$result->addError(ErrorCode::getAIEngineNotFoundError([
				'isAiMarketplaceAppsExist' => $this->isAiMarketplaceAppsExist(),
			]));

			static::notifyAboutJobError($result, false);

			return $result;
		}

		if (method_exists($engine, 'skipAgreement'))
		{
			$engine->skipAgreement();
		}

		$checkJobsResult = static::checkPreviousJobs($this->target, (int)$this->parentJobId);
		if (!$checkJobsResult->isSuccess())
		{
			AIManager::logger()->error(
				'{date}: {class}: Cant start operation {operationType} on {target}'
				. ' because of duplication or retry policy: {errors}' . PHP_EOL,
				['class' => static::class, 'target' => $this->target, 'operationType' => static::TYPE_ID, 'errors' => $checkJobsResult->getErrors()],
			);

			$result->addErrors($checkJobsResult->getErrors());

			static::notifyAboutJobError($result, false);

			return $result;
		}

		$previousJob = $checkJobsResult->getData()['previousJob'] ?? null;

		$aiPayloadResult = $this->getAIPayload();
		if (!$aiPayloadResult->isSuccess())
		{
			AIManager::logger()->error(
				'{date}: {class}: Error while trying to form AI payload for target {target}'
				. ' in operation {operationType}: {errors}' . PHP_EOL,
				[
					'class' => static::class,
					'target' => $this->target,
					'operationType' => static::TYPE_ID,
					'errors' => $aiPayloadResult->getErrors(),
				],
			);

			$result->addErrors($aiPayloadResult->getErrors());

			static::notifyAboutJobError($result, false);

			return $result;
		}

		$hash = null;
		$error = null;

		if (AIManager::isStubMode())
		{
			$hash = AIManager::registerStubJob($engine, $this->getStubPayload());

			AIManager::logger()->info(
				'{date}: {class}: Created stub job {hash} for target {target} in operation {operationType}' . PHP_EOL,
				[
					'class' => static::class,
					'hash' => $hash,
					'target' => $this->target,
					'operationType' => static::TYPE_ID,
				],
			);
		}
		else
		{
			$engine
				->setPayload($aiPayloadResult->getData()['payload'])
				->setHistoryState(false)
				->onSuccess(static function (\Bitrix\AI\Result $result, ?string $queueHash = null) use (&$hash) {
					$hash = $queueHash;
				})
				->onError(static function (Error $processingError) use (&$error) {
					$error = $processingError;
				})
			;

			if (static::ENGINE_CATEGORY === 'audio')
			{
				$engine->completions();
			}
			else
			{
				$engine->completionsInQueue();
			}

			AIManager::logger()->info(
				'{date}: {class}: Created AI job for target {target} in operation {operationType}' . PHP_EOL,
				[
					'class' => static::class,
					'target' => $this->target,
					'operationType' => static::TYPE_ID,
				],
			);
		}

		if ($error instanceof Error)
		{
			//first, process limit errors
			$limitError = AIManager::fetchLimitError($error);
			if ($limitError !== null)
			{
				$result->addError($limitError);

				$errorData = $limitError->getCustomData() ?? [];

				AIManager::logger()->error(
					'{date}: {class}: Cant start operation {operationType} on {target} because limit of requests to AI exceeded!'
					. ' Category {engineCategory}, context {engineContext}, slider code "{sliderCode}"' . PHP_EOL,
					[
						'class' => static::class,
						'target' => $this->target,
						'operationType' => static::TYPE_ID,
						'engineCategory' => static::ENGINE_CATEGORY,
						'engineContext' => $context,
						'sliderCode' => $errorData['sliderCode'] ?? null,
					],
				);

				if (!$this->isManualLaunch)
				{
					static::notifyAboutLimitExceededError($result);
				}

				static::notifyAboutJobError($result, false);

				return $result;
			}

			AIManager::logger()->critical(
				'{date}: {class}: Error while adding AI job for target {target} in operation {operationType} for activity{activity}: {error}' . PHP_EOL,
				[
					'class' => static::class,
					'target' => $this->target,
					'operationType' => static::TYPE_ID,
					'error' => $error,
					'activity' => self::getTargetRealId($this->target, $this->parentJobId),
				],
			);

			if (empty($error->getMessage()))
			{
				$error = new Error(ErrorCode::getAIEngineNotFoundError()->getMessage());
			}

			$result->addError($error);

			static::notifyAboutJobError($result);

			return $result;
		}
		else
		{
			self::logOperationProgress('operationLaunched', $this->target, (string)$hash, $this->parentJobId);
		}

		if ($previousJob instanceof EO_Queue)
		{
			AIManager::logger()->debug(
				'{date}: {class}: Updating existing job {id} in CRM DB for target {target} in operation {operationType}' . PHP_EOL,
				[
					'class' => static::class,
					'id' => $previousJob->getId(),
					'target' => $this->target,
					'operationType' => static::TYPE_ID,
				],
			);

			$dbSaveResult = QueueTable::update(
				$previousJob->getId(),
				['HASH' => $hash] + $this->getJobUpdateFields(),
			);
		}
		else
		{
			AIManager::logger()->debug(
				'{date}: {class}: Creating a new job row in CRM DB for target {target} in operation {operationType}' . PHP_EOL,
				[
					'class' => static::class,
					'target' => $this->target,
					'operationType' => static::TYPE_ID,
				],
			);

			$dbSaveResult = QueueTable::add(
				['HASH' => $hash] + $this->getJobAddFields(),
			);
		}

		if (!$dbSaveResult->isSuccess())
		{
			AIManager::logger()->critical(
				'{date}: {class}: Errors while saving job to CRM DB for target {target} in operation {operationType} for hash {hash} for activity{activity}: {errors}' . PHP_EOL,
				[
					'class' => static::class,
					'target' => $this->target,
					'operationType' => static::TYPE_ID,
					'errors' => $dbSaveResult->getErrors(),
					'hash' => $hash,
					'activity' => self::getTargetRealId($this->target, $this->parentJobId),
				],
			);

			$result->addErrors($dbSaveResult->getErrors());

			static::notifyAboutJobError($result, true, false);

			return $result;
		}

		$result->setJobId($dbSaveResult->getId());

		if ($result->isSuccess())
		{
			AIManager::logger()->debug(
				'{date}: {class}: Notifying timeline about operation launch for target {target} in operation {operationType}' . PHP_EOL,
				[
					'class' => static::class,
					'target' => $this->target,
					'operationType' => static::TYPE_ID,
				],
			);

			static::notifyTimelineAfterSuccessfulLaunch($result);
		}

		AIManager::logger()->debug(
			'{date}: {class}: Operation launch for target {target} finished with result {result}' . PHP_EOL,
			[
				'class' => static::class,
				'target' => $this->target,
				'result' => $result,
			],
		);

		if (static::TYPE_ID === 1)
		{
			static::notifyTimelinesAboutActivityUpdate($this->target->getEntityId());
			self::sendCallParsingAnalyticsEvent($result, self::extractActivityIdFromResult($result));
		}

		return $result;
	}

	abstract protected function getAIPayload(): \Bitrix\Main\Result;

	abstract protected function getStubPayload(): mixed;

	abstract protected static function notifyTimelineAfterSuccessfulLaunch(Result $result): void;

	protected function getJobUpdateFields(): array
	{
		return [
			'USER_ID' => $this->userId,
			'EXECUTION_STATUS' => QueueTable::EXECUTION_STATUS_PENDING,
			'ERROR_CODE' => null,
			'ERROR_MESSAGE' => null,
			'RETRY_COUNT' => new SqlExpression('?# + 1', 'RETRY_COUNT'),
			'IS_MANUAL_LAUNCH' => $this->isManualLaunch,
			'LANGUAGE_ID' => $this->contextLanguageId,
			'ENGINE_ID' => self::$engineId,
			'NEXT_TYPE_ID' => Scenario::getNextTypeIdByScenario($this->scenario),
		];
	}

	protected function getJobAddFields(): array
	{
		return [
			'ENTITY_TYPE_ID' => $this->target->getEntityTypeId(),
			'ENTITY_ID' => $this->target->getEntityId(),
			'TYPE_ID' => static::TYPE_ID,
			'USER_ID' => $this->userId,
			'PARENT_ID' => (int)$this->parentJobId,
			'IS_MANUAL_LAUNCH' => $this->isManualLaunch,
			'LANGUAGE_ID' => $this->contextLanguageId,
			'ENGINE_ID' => self::$engineId,
			'NEXT_TYPE_ID' => Scenario::getNextTypeIdByScenario($this->scenario),
		];
	}

	protected function getContextAdditionalInfo(): array
	{
		return [
			'myCompanyName' => Container::getInstance()->getCompanyBroker()->getTitle(EntityLink::getDefaultMyCompanyId()),
		];
	}

	protected function getContextLanguageId(): string
	{
		return Config::getDefaultLanguageId();
	}

	private function getAIEngineContext(): Context
	{
		$context = new Context('crm', static::CONTEXT_ID, $this->userId);
		$context->setParameters([
			'target' => $this->target->toArray(),
			'userId' => $this->userId,
			'parentJobId' => $this->parentJobId,
			'additionalInfo' => $this->getContextAdditionalInfo(),
		]);
		$context->setLanguage($this->contextLanguageId);

		return $context;
	}

	protected function getAIEngine(Context $context): ?Engine
	{
		$manager = new Manager();
		$item = $manager->getItem(static::ENGINE_CODE);
		if ($item === null)
		{
			return null;
		}

		$engine = Engine::getByCode(
			$item->getValue(),
			$context,
			static::ENGINE_CATEGORY,
		);

		if ($engine && $engine->getIEngine()->isThirdParty())
		{
			self::$engineId = $engine->getIEngine()->getRestItem()->getId();
		}

		return $engine;
	}

	private function isAiMarketplaceAppsExist(): bool
	{
		if (!Loader::includeModule('rest'))
		{
			return false;
		}

		$tagId = 'ai_provider_partner_crm';
		$cache = Application::getInstance()->getCache();
		$cacheId = $tagId . '_marketplace';
		$cacheTtl = 60 * 60 * 24; // 24 hour
		$cachePath = '/crm/ai/aiAppTag/';
		if ($cache->initCache($cacheTtl, $cacheId, $cachePath))
		{
			$result = $cache->getVars();
		}
		else
		{
			$marketplaceApps = Marketplace\Client::getByTag($tagId, 0, 1);
			$result = count($marketplaceApps['ITEMS'] ?? []) > 0;
			$cache->startDataCache();
			$cache->endDataCache($result);
		}

		return $result;
	}

	public static function onQueueJobExecute(Event $event, EO_Queue $job): Result
	{
		AIManager::logger()->debug(
			'{date}: {class}: Processing event {event} for job {job}' . PHP_EOL,
			[
				'event' => $event,
				'job' => $job->collectValues(fieldsMask: FieldTypeMask::FLAT),
				'class' => static::class,
			],
		);

		$dummyResult = static::constructResult($job);

		/** @var \Bitrix\AI\Result|null $aiResult */
		$aiResult = $event->getParameter('result');

		if (!($aiResult instanceof \Bitrix\AI\Result))
		{
			AIManager::logger()->critical(
				'{date}: {class}: There is no AI result in event {event} for job {job} for activity{activity}' . PHP_EOL,
				[
					'event' => $event,
					'job' => $job->collectValues(fieldsMask: FieldTypeMask::FLAT),
					'class' => static::class,
					'activity' => self::extractActivityIdFromJob($job),
				],
			);

			$dummyResult->addError(ErrorCode::getAIResultFoundError());

			static::notifyAboutJobError($dummyResult);

			return self::saveErrorToJobAndReturnResult($job, $dummyResult);
		}

		$payload = static::extractPayloadFromAIResult($aiResult, $job);

		if ($payload->hasValidationErrors())
		{
			AIManager::logger()->critical(
				'{date}: {class}: Error validating payload from AI result with hash {hash} for activity{activity}: errors {errors}, AI result {aiResult}' . PHP_EOL,
				[
					'class' => static::class,
					'errors' => $payload->getValidationErrors()->toArray(),
					'aiResult' => $aiResult,
					'hash' => $job->requireHash(),
					'activity' => self::extractActivityIdFromJob($job),
				],
			);

			$dummyResult->addErrors(
				$payload->getValidationErrors()->toArray()
			);

			$result = self::saveErrorToJobAndReturnResult($job, $dummyResult);

			static::notifyAboutJobError($dummyResult);

			return $result;
		}

		$job->setResult(Json::encode($payload, 0));
		$job->setExecutionStatus(QueueTable::EXECUTION_STATUS_SUCCESS);

		AIManager::logger()->debug(
			'{date}: {class}: Got valid response from AI, saving it to CRM DB: {job}' . PHP_EOL,
			[
				'class' => static::class,
				'job' => $job->collectValues(fieldsMask: FieldTypeMask::FLAT),
			],
		);

		$dbSaveResult = $job->save();
		if (!$dbSaveResult->isSuccess())
		{
			AIManager::logger()->critical(
				'{date}: {class}: Error while saving results to CRM DB with hash {hash} for activity{activity}: {errors}' . PHP_EOL,
				[
					'class' => static::class,
					'errors' => $dbSaveResult->getErrors(),
					'hash' => $job->requireHash(),
					'activity' => self::extractActivityIdFromJob($job),
				],
			);

			$job->resetResult();

			$dummyResult->addErrors($dbSaveResult->getErrors());

			static::notifyAboutJobError($dummyResult, true, false);

			return self::saveErrorToJobAndReturnResult($job, $dummyResult);
		}

		$result = static::constructResult($job);

		if ($result->isSuccess())
		{
			static::onAfterSuccessfulJobFinish($result, $event->getParameter('engine')?->getContext());
			self::logOperationProgress('operationComplete',$result->getTarget(), $job->requireHash(), $result->getParentJobId());

			AIManager::logger()->debug(
				'{date}: {class}: Notifying timeline about operation finish'
				. ' for target {target} in operation {operationType}' . PHP_EOL,
				[
					'class' => static::class,
					'target' => $result->getTarget(),
					'operationType' => static::TYPE_ID,
				],
			);
			static::notifyTimelineAfterSuccessfulJobFinish($result);
		}
		else
		{
			static::notifyAboutJobError($result, true, false);
		}

		$builder = self::constructJobFinishEventBuilder($job)
			?->setStatus($result->isSuccess() ? Dictionary::STATUS_SUCCESS : Dictionary::STATUS_ERROR)
		;
		if ($builder)
		{
			$builder->buildEvent()->send();
			// send the same analytics only with different TOOL and CATEGORY
			$builder
				->setTool(Dictionary::TOOL_CRM)
				->setCategory(Dictionary::CATEGORY_AI_OPERATIONS)
				->buildEvent()
				->send()
			;
		}

		AIManager::logger()->debug(
			'{date}: {class}: Job for target {hash} finished with result {result}' . PHP_EOL,
			[
				'class' => static::class,
				'hash' => $job->requireHash(),
				'result' => $result,
			],
		);

		return $result;
	}

	/**
	 * @template T of \Bitrix\Main\Result
	 *
	 * @param EO_Queue $job
	 * @param T $result
	 *
	 * @return T
	 */
	private static function saveErrorToJobAndReturnResult(EO_Queue $job, \Bitrix\Main\Result $result): \Bitrix\Main\Result
	{
		$errors = $result->getErrors();

		$errorToSave = end($errors);
		if (!($errorToSave instanceof Error))
		{
			return $result;
		}

		$job->setExecutionStatus(QueueTable::EXECUTION_STATUS_ERROR);
		$job->setErrorCode($errorToSave->getCode());
		$job->setErrorMessage($errorToSave->getMessage());

		$dbSaveResult = $job->save();
		if (!$dbSaveResult->isSuccess())
		{
			AIManager::logger()->critical(
				'{date}: {class}: Error while trying to save error info to CRM DB with hash {hash} for activity{activity}: {errors}',
				[
					'class' => static::class,
					'errors' => $dbSaveResult->getErrors(),
					'hash' => $job->requireHash(),
					'activity' => self::extractActivityIdFromJob($job),
				],
			);
		}

		$builder = self::constructJobFinishEventBuilder($job)
			?->setStatus(Dictionary::STATUS_ERROR);
		if ($builder)
		{
			$builder->buildEvent()->send();
			// send the same analytics only with different TOOL and CATEGORY
			$builder
				->setTool(Dictionary::TOOL_CRM)
				->setCategory(Dictionary::CATEGORY_AI_OPERATIONS)
				->buildEvent()
				->send()
			;
		}

		return $result;
	}

	protected static function onAfterSuccessfulJobFinish(Result $result, ?Context $context = null): void
	{
	}

	abstract protected static function notifyTimelineAfterSuccessfulJobFinish(Result $result): void;

	abstract protected static function notifyAboutJobError(
		Result $result,
		bool $withSyncBadges = true,
		bool $withSendAnalytics = true
	): void;

	protected static function notifyAboutLimitExceededError(Result $result): void
	{
		$activityId = $result->getTarget()?->getEntityId();
		if ($activityId === null)
		{
			return;
		}

		self::syncBadges($activityId, Badge\Type\AiCallFieldsFillingResult::ERROR_LIMIT_EXCEEDED);
		static::notifyTimelinesAboutAutomationLaunchError($result);
	}

	final protected static function notifyTimelinesAboutActivityUpdate(int $activityId, bool $forceUpdateHistoryItems = false): void
	{
		$activity = CCrmActivity::GetByID($activityId, false);
		if ($activity)
		{
			ActivityController::getInstance()
				->notifyTimelinesAboutActivityUpdate($activity, (int)$activity['RESPONSIBLE_ID'], $forceUpdateHistoryItems)
			;
		}
	}

	final protected static function notifyTimelinesAboutAutomationLaunchError(Result $result, int $activityId = null): void
	{
		$activityId = $activityId ?? $result->getTarget()?->getEntityId();
		$target = (new Orchestrator())->findPossibleFillFieldsTarget($activityId);
		if ($target)
		{
			$errorCodes = array_map(static fn($error) => $error->getCode(), $result->getErrors());
			Controller::getInstance()->onAutomationLaunchError(
				$target,
				$activityId,
				[ 'ERROR_CODES' => $errorCodes ],
				$result->getUserId(),
			);
		}
	}

	final protected static function syncBadges(int $activityId, string $badgeValue = ''): void
	{
		$itemIdentifier = (new Orchestrator())->findPossibleFillFieldsTarget($activityId);
		if (!$itemIdentifier)
		{
			return;
		}

		Badge\Badge::deleteByEntity($itemIdentifier, Badge\Badge::AI_CALL_FIELDS_FILLING_RESULT);
		if (!empty($badgeValue))
		{
			$badge = Container::getInstance()->getBadge(Badge\Badge::AI_CALL_FIELDS_FILLING_RESULT, $badgeValue);
			$sourceIdentifier = new Badge\SourceIdentifier(
				Badge\SourceIdentifier::CRM_OWNER_TYPE_PROVIDER,
				CCrmOwnerType::Activity,
				$activityId,
			);

			$badge->bind($itemIdentifier, $sourceIdentifier);
			Monitor::getInstance()->onBadgesSync($itemIdentifier);
		}
	}

	public static function onQueueJobFail(Event $event, EO_Queue $job): Result
	{
		AIManager::logger()->debug(
			'{date}: {class}: Processing event {event} for job {job}' . PHP_EOL,
			[
				'event' => $event,
				'job' => $job->collectValues(fieldsMask: FieldTypeMask::FLAT),
				'class' => static::class,
			],
		);

		$error = $event->getParameter('error');
		if (!($error instanceof Error))
		{
			$error = ErrorCode::getJobExecutionFailedError();
		}

		AIManager::logger()->critical(
			'{date}: {class}: Job {id} (hash {hash}) for activity{activity} failed because we have received error from AI queue: {error}' . PHP_EOL,
			[
				'class' => static::class,
				'id' => $job->getId(),
				'hash' => $job->requireHash(),
				'error' => $error,
				'activity' => self::extractActivityIdFromJob($job),
			],
		);

		self::saveErrorToJobAndReturnResult(
			$job,
			(new Result(static::TYPE_ID))->addError($error)
		);

		$result = static::constructResult($job);

		static::notifyAboutJobError($result);

		return $result;
	}

	/**
	 * @param EO_Queue $job
	 *
	 * @return Result
	 * @throws ArgumentException
	 */
	public static function constructResult(EO_Queue $job): Result
	{
		if ((int)$job->requireTypeId() !== static::TYPE_ID)
		{
			throw new ArgumentException(static::class . ' processes only results of type ' . static::TYPE_ID);
		}

		/** @var Dto|null $payload */
		$payload = null;

		if ($job->requireExecutionStatus() === QueueTable::EXECUTION_STATUS_SUCCESS)
		{
			$resultsJson = $job->requireResult();
			if (is_string($resultsJson) && $resultsJson !== '')
			{
				$payload = static::constructPayload($resultsJson);
			}
		}

		$result = new Result(
			static::TYPE_ID,
			new ItemIdentifier($job->requireEntityTypeId(), $job->requireEntityId()),
			$job->requireUserId(),
			$job->getId(),
			$job->requireExecutionStatus() === QueueTable::EXECUTION_STATUS_PENDING,
			$payload,
			$job->requireOperationStatus(),
			$job->requireParentId(),
			$job->requireRetryCount(),
			$job->requireIsManualLaunch(),
			$job->requireLanguageId(),
			$job->requireNextTypeId()
		);

		if ($job->requireExecutionStatus() === QueueTable::EXECUTION_STATUS_ERROR)
		{
			$result->addError(ErrorCode::getJobExecutionFailedError());
		}
		elseif ($result->getPayload() === null && !$result->isPending())
		{
			$result->addError(ErrorCode::getPayloadNotFoundError());
		}

		return $result;
	}

	public static function constructPayload(string $json): ?Dto
	{
		if (empty($json))
		{
			return null;
		}

		$resultsArray = null;
		try
		{
			$resultsArray = Json::decode($json);
		}
		catch (ArgumentException)
		{
		}

		if (!is_array($resultsArray))
		{
			return null;
		}

		$class = static::PAYLOAD_CLASS;
		$payload = new $class($resultsArray);
		if ($payload->hasValidationErrors())
		{
			return null;
		}

		return $payload;
	}

	abstract protected static function extractPayloadFromAIResult(\Bitrix\AI\Result $result, EO_Queue $job): Dto;

	protected static function logOperationProgress(string $operation, ItemIdentifier $target, string $hash, ?int $parentJobId): void
	{
		if (Loader::includeModule('bitrix24'))
		{
			$logHost = Application::getInstance()->getContext()->getServer()->getHttpHost();
			$logStep = static::class;
			$logDate = (new \Bitrix\Main\Type\DateTime())->toString();
			$logTarget = self::getTargetRealId($target, $parentJobId);
			AddMessage2Log("crm.integration.AI {$logHost} {$operation} {$logDate}: started step {$logStep} with hash {$hash} for activity{$logTarget}", 'crm');
		}
	}

	protected static function getTargetRealId(ItemIdentifier $target, ?int $parentJobId): int
	{
		if ($target->getEntityTypeId() !== CCrmOwnerType::Activity)
		{
			$parentJob = QueueTable::query()
				->setSelect(['ID', 'ENTITY_ID', 'ENTITY_TYPE_ID'])
				->where('ID', $parentJobId)
				->setLimit(1)
				->fetchObject()
			;
			if ($parentJob?->getEntityTypeId() === CCrmOwnerType::Activity)
			{
				return $parentJob->getEntityId();
			}

			return 0;
		}

		return $target->getEntityId();
	}

	private static function extractActivityIdFromJob(EO_Queue $job): int
	{
		return self::getTargetRealId(new ItemIdentifier($job->getEntityTypeId(), $job->getEntityId()), $job->getParentId());
	}

	private static function extractActivityIdFromResult(Result $result): int
	{
		return self::getTargetRealId($result->getTarget(), $result->getParentJobId());
	}

	final protected static function sendCallParsingAnalyticsEvent(
		Result $result,
		int $activityId,
		?int $totalScenarioDuration = null
	): void
	{
		$activity = Container::getInstance()->getActivityBroker()->getById($activityId);
		if (!$activity)
		{
			return;
		}

		$owner = Container::getInstance()->getActivityBroker()->getOwner($activityId);
		if (!$owner)
		{
			return;
		}

		$builder = (new CallParsingEvent())
			->setIsManualLaunch($result->isManualLaunch())
			->setActivityOwnerTypeId($owner->getEntityTypeId())
			->setActivityId($activityId)
			->setActivityDirection($activity['DIRECTION'])
			->setTotalScenarioDuration($totalScenarioDuration)
			->setElement(Dictionary::ELEMENT_COPILOT_BUTTON)
			->setStatus(CallParsingEvent::resolveStatusByJobResult($result))
		;
		$builder->buildEvent()->send();
		// send the same analytics only with different TOOL and CATEGORY
		$builder
			->setTool(Dictionary::TOOL_CRM)
			->setCategory(Dictionary::CATEGORY_AI_OPERATIONS)
			->buildEvent()
			->send()
		;
	}

	final protected static function extractPayloadPrettifiedData(\Bitrix\AI\Result $result): array
	{
		try
		{
			$prettifiedData = $result->getPrettifiedData() ?: '';
			$prettifiedData = trim($prettifiedData);

			$startPos = strpos($prettifiedData, '{');
			$endPos = strrpos($prettifiedData, '}');

			if ($startPos === false || $endPos === false)
			{
				return [];
			}

			$prettifiedData = substr($prettifiedData, $startPos, $endPos - $startPos + 1);
			$json = Json::decode($prettifiedData);
		}
		catch (ArgumentException)
		{
			$json = [];
		}

		if (!is_array($json))
		{
			$json = [];
		}

		return $json;
	}

	final protected static function extractPayloadString(mixed $input): ?string
	{
		$result = null;
		if (!empty($input))
		{
			$result = is_array($input)
				? implode(PHP_EOL, $input)
				: (string)$input
			;
		}

		return $result;
	}

	private static function constructJobFinishEventBuilder(EO_Queue $job): ?AIBaseEvent
	{
		$activityId = self::extractActivityIdFromJob($job);
		$owner = null;
		if ($activityId >= 0)
		{
			$owner = Container::getInstance()->getActivityBroker()->getOwner($activityId);
		}
		if ($activityId <= 0 || !$owner)
		{
			return null;
		}

		if (!$job->requireFinishedTime())
		{
			AIManager::logger()->info(
				'{date}: {class}: Skipping analytics because job doesnt have finishedTime set.'
				. ' May be this job was created before the update: {job}',
				[
					'job' => $job->collectValues(fieldsMask: FieldTypeMask::FLAT),
					'class' => static::class,
				]
			);

			return null;
		}

		return static::getJobFinishEventBuilder()
			->setOperationType(static::TYPE_ID)
			->setCreatedTime($job->requireCreatedTime())
			->setFinishedTime($job->requireFinishedTime())
			->setIsManualLaunch($job->requireIsManualLaunch())
			->setActivityOwnerTypeId($owner->getEntityTypeId())
			->setActivityId($activityId)
		;
	}

	abstract protected static function getJobFinishEventBuilder(): AIBaseEvent;
}
