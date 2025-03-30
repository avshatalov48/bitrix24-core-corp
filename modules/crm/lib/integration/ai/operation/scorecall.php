<?php

namespace Bitrix\Crm\Integration\AI\Operation;

use Bitrix\AI;
use Bitrix\AI\Context;
use Bitrix\Crm\Activity\Provider\Call;
use Bitrix\Crm\Badge;
use Bitrix\Crm\Copilot\AiQualityAssessment\Controller\AiQualityAssessmentController;
use Bitrix\Crm\Copilot\AiQualityAssessment\Entity\AiQualityAssessmentItem;
use Bitrix\Crm\Copilot\AiQualityAssessment\Entity\AiQualityAssessmentTable;
use Bitrix\Crm\Copilot\CallAssessment\AssessmentClientTypeResolver;
use Bitrix\Crm\Copilot\CallAssessment\CallAssessmentItem;
use Bitrix\Crm\Copilot\CallAssessment\CallAssessmentItemChecker;
use Bitrix\Crm\Copilot\CallAssessment\Controller\CopilotCallAssessmentController;
use Bitrix\Crm\Copilot\CallAssessment\ItemFactory;
use Bitrix\Crm\Copilot\PullManager;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Config;
use Bitrix\Crm\Integration\AI\Dto\ScoreCallPayload;
use Bitrix\Crm\Integration\AI\ErrorCode;
use Bitrix\Crm\Integration\AI\EventHandler;
use Bitrix\Crm\Integration\AI\Model\EO_Queue;
use Bitrix\Crm\Integration\AI\Model\QueueTable;
use Bitrix\Crm\Integration\AI\Result;
use Bitrix\Crm\Integration\Analytics\Builder\AI\AIBaseEvent;
use Bitrix\Crm\Integration\Analytics\Builder\AI\CallScoring;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\MultiValueStoreService;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Monitor;
use Bitrix\Crm\Timeline\AI\Call\Controller;
use Bitrix\Main;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Web\Json;
use CCrmOwnerType;

final class ScoreCall extends AbstractOperation
{
	public const TYPE_ID = 4;
	public const CONTEXT_ID = 'score_call';

	protected const PAYLOAD_CLASS = ScoreCallPayload::class;
	protected const ENGINE_CODE = EventHandler::SETTINGS_CALL_ASSESSMENT_ENGINE_CODE;

	private ?CallAssessmentItem $assessmentSettings;

	public function __construct(
		ItemIdentifier $target,
		private readonly string $transcription,
		?int $userId = null,
		?int $parentJobId = null,
		?int $assessmentSettingsId = null
	)
	{
		$this->assessmentSettings = self::getAssessmentSettings(
			$target->getEntityId(),
			$assessmentSettingsId,
			$parentJobId,
		);

		parent::__construct($target, $userId, $parentJobId);
	}

	public static function isSuitableTarget(ItemIdentifier $target): bool
	{
		if ($target->getEntityTypeId() === CCrmOwnerType::Activity)
		{
			$activity = Container::getInstance()->getActivityBroker()->getById($target->getEntityId());
			$providerId = $activity['PROVIDER_ID'] ?? null;
			if ($providerId === Call::ACTIVITY_PROVIDER_ID)
			{
				return true;
			}
		}

		return false;
	}

	protected static function checkPreviousJobs(ItemIdentifier $target, int $parentId): Main\Result
	{
		$result = new Main\Result();

		$previousJob = self::findDuplicateJob($target, $parentId);
		if (!$previousJob)
		{
			return $result; // new job
		}

		if ($previousJob->requireExecutionStatus() === QueueTable::EXECUTION_STATUS_SUCCESS)
		{
			return $result; // success previous job
		}

		if ($previousJob->requireExecutionStatus() === QueueTable::EXECUTION_STATUS_PENDING)
		{
			return $result->addError(ErrorCode::getJobAlreadyExistsError()); // previous job in progress
		}

		if (
			$previousJob->requireExecutionStatus() === QueueTable::EXECUTION_STATUS_ERROR
			&& $previousJob->requireRetryCount() >= Result::MAX_RETRY_COUNT
		)
		{
			return $result->addError(ErrorCode::getJobMaxRetriesExceededError());
		}

		$result->setData(['previousJob' => $previousJob]); // update only error jobs

		return $result;
	}

	protected function getAIPayload(): Main\Result
	{
		$checkerResult = CallAssessmentItemChecker::getInstance()
			->setItem($this->assessmentSettings)
			->run()
		;
		if (!$checkerResult->isSuccess())
		{
			return (new Main\Result())->addError($checkerResult->getError());
		}

		$clientType = (new AssessmentClientTypeResolver())->resolveByActivityId($this->target->getEntityId());

		return (new Main\Result())->setData([
			'payload' => (new \Bitrix\AI\Payload\Prompt('call_scoring'))
				->setMarkers([
					'transcript' => $this->transcription,
					'criteria' => $this->assessmentSettings->getGist(),
					'client_type' => $clientType?->name ?? '',
				])
			,
		]);
	}

	protected function getStubPayload(): mixed
	{
		$criteriaList = array_map(
			static fn(int $index) => [
					'criterion' => "name of criterion {$index}",
					'status' => (bool)Random::getInt(0, 1),
					'explanation' => "explanation of criterion {$index}",
			], [1, 2, 3, 4, 5, 6, 7]
		);

		$fields = [
			'call_review' => [
				'criteria' => $criteriaList,
			],
			'overall_summary' => 'The manager successfully met almost all criteria, providing quality service and attention to detail.',
			'recommendations' => "It is recommended to offer customers a 'gift wrap' promotion for a review on WhatsApp or Instagram to increase customer engagement and loyalty."
		];

		return Json::encode($fields);
	}

	protected function getContextLanguageId(): string
	{
		$itemIdentifier = (new Orchestrator())->findPossibleFillFieldsTarget($this->target->getEntityId());
		if ($itemIdentifier)
		{
			return Config::getLanguageId(
				$this->userId,
				$itemIdentifier->getEntityTypeId(),
				$itemIdentifier->getCategoryId()
			);
		}

		return parent::getContextLanguageId();
	}

	protected function getContextAdditionalInfo(): array
	{
		$result = parent::getContextAdditionalInfo();

		$result['assessment_settings_id'] = $this->assessmentSettings?->getId();

		return $result;
	}

	// region notify
	protected static function notifyTimelineAfterSuccessfulLaunch(Result $result): void
	{
		$nextTarget = (new Orchestrator())->findPossibleFillFieldsTarget($result->getTarget()?->getEntityId());
		if ($nextTarget)
		{
			Controller::getInstance()->onStartCallScoring(
				$nextTarget,
				$result->getTarget()?->getEntityId(),
				$result->getUserId(),
			);
		}
	}

	protected static function notifyTimelineAfterSuccessfulJobFinish(Result $result): void
	{
		if (self::isCriteriaListEmpty((array)$result->getPayload()?->criteria))
		{
			return;
		}

		$nextTarget = (new Orchestrator())->findPossibleFillFieldsTarget($result->getTarget()?->getEntityId());
		if ($nextTarget)
		{
			$activityId = $result->getTarget()?->getEntityId();
			if ($activityId)
			{
				Controller::getInstance()->onFinishCallScoring(
					$nextTarget,
					$activityId,
					[
						'JOB_ID' => $result->getJobId(),
					],
					$result->getUserId(),
				);

				self::notifyTimelinesAboutActivityUpdate($activityId, true);
			}
		}
	}

	protected static function notifyAboutJobError(Result $result, bool $withSyncBadges = true, bool $withSendAnalytics = true): void
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
						'ENGINE_ID' => self::$engineId,
						'ERRORS' => $result->getErrorMessages(),
					],
					$result->getUserId(),
				);

				self::syncBadges($activityId, Badge\Type\AiCallFieldsFillingResult::ERROR_PROCESS_VALUE);
			}

			self::notifyTimelinesAboutActivityUpdate($activityId);

			if ($withSendAnalytics)
			{
				self::sendCallParsingAnalyticsEvent(
					$result,
					$activityId
				);
			}
		}

		self::notifyCallQualityUpdate($activityId, 'error');
	}

	protected static function notifyCallQualityUpdate(int $activityId, string $status, array $params = []): void
	{
		$params['status'] = $status;

		(new PullManager())->sendAddScoringPullEvent($activityId, $params);
	}
	// endregion

	protected static function extractPayloadFromAIResult(AI\Result $result, EO_Queue $job): Dto
	{
		$json = self::extractPayloadPrettifiedData($result);
		$callReview = $json['call_review'] ?? null;
		if (empty($callReview))
		{
			return new ScoreCallPayload([]);
		}

		return new ScoreCallPayload([
			'criteria' => $callReview['criteria'],
			'overallSummary' => self::extractPayloadString($json['overall_summary'] ?? ''),
			'recommendations' =>self::extractPayloadString($json['recommendations'] ?? ''),
		]);
	}

	protected static function getJobFinishEventBuilder(): AIBaseEvent
	{
		return new CallScoring();
	}

	protected static function onAfterSuccessfulJobFinish(Result $result, ?Context $context = null): void
	{
		$activityId = $result->getTarget()?->getEntityId();
		/** @var ScoreCallPayload $payload */
		$payload = $result->getPayload();
		if (!$payload || !$result->isSuccess())
		{
			AIManager::logger()->error(
				'{date}: {class}: Error while trying to save CoPilot marks because of job error: {target}' . PHP_EOL,
				[
					'class' => self::class,
					'target' => $result->getTarget(),
				],
			);

			self::notifyCallQualityUpdate($activityId, 'error');

			return;
		}

		if (self::isCriteriaListEmpty($payload->criteria))
		{
			$nextTarget = (new Orchestrator())->findPossibleFillFieldsTarget($activityId);
			if ($nextTarget)
			{
				Controller::getInstance()->onCallScoringEmptyResult(
					$nextTarget,
					$activityId,
					[
						'RECOMMENDATIONS' => $payload->recommendations
					],
					$result->getUserId(),
				);
			}

			self::notifyTimelinesAboutActivityUpdate($activityId);

			self::notifyCallQualityUpdate($activityId, 'error');

			return;
		}

		/* @todo: add message via IM service & get RATED_USER_CHAT_ID | MANAGER_USER_CHAT_ID
		$sender = \Bitrix\Crm\Service\Container::getInstance()->getImService();
		$message = new Bitrix\Crm\Integration\Im\Message\Type\Assessment\ToEmployee(
			fromUser: $userIdFrom,
			toUser: $userIdTo,
		);
		$result = $sender->sendMessage($message);
		*/

		$activity = Container::getInstance()->getActivityBroker()->getById($activityId);
		$userId = $activity['RESPONSIBLE_ID'] ?? $result->getUserId();

		$assessmentSettingsId = isset($context)
			? $context->getParameters()['additionalInfo']['assessment_settings_id'] ?? null
			: null;
		$assessmentSettings = self::getAssessmentSettings($activityId, $assessmentSettingsId);
		$assessment = self::getAssessmentsValue($payload);
		$controller = AiQualityAssessmentController::getInstance();
		$prevRatedItemIdList = $controller->getList([
			'select' => ['ID'],
			'filter' => [
				'=ACTIVITY_ID' => $activityId,
				'=ACTIVITY_TYPE' => AiQualityAssessmentTable::ACTIVITY_TYPE_CALL,
				'=RATED_USER_ID' => $userId,
			],
		])->getAll();
		$prevRatedItemIdList = array_map(static fn(object $item) => $item->getId(), $prevRatedItemIdList);

		$saveResult = $controller
			->add(
				AiQualityAssessmentItem::createFromEntityFields([
					'ACTIVITY_ID' => $activityId,
					'ASSESSMENT_SETTING_ID' => $assessmentSettings?->getId(),
					'JOB_ID' => $result->getJobId(),
					'PROMPT' => $assessmentSettings?->getPrompt(),
					'ASSESSMENT' => $assessment,
					'ASSESSMENT_AVG' => $controller->getNewAvgAssessmentValue($userId, $assessment),
					'USE_IN_RATING' => true,
					'RATED_USER_ID' => $userId,
				])
			)
		;

		if ($saveResult->isSuccess())
		{
			self::notifyCallQualityUpdate(
				$activityId,
				'success',
				[
					'jobId' => $result->getJobId(),
					'assessmentSettingsId' => $assessmentSettings?->getId(),
					'ratedUserId' => $userId,
				]
			);

			self::trySyncScoreStatusBadge(
				$activityId,
				$assessment,
				$assessmentSettings?->getLowBorder()
			);
		}
		else
		{
			AIManager::logger()->critical(
				'{date}: {class}: Error while trying to save CoPilot marks because of error: {errors}',
				[
					'class' => self::class,
					'errors' => $saveResult->getErrors(),
				],
			);

			self::notifyCallQualityUpdate($activityId, 'error');

			return;
		}

		if ($prevRatedItemIdList)
		{
			AiQualityAssessmentTable::updateMulti(
				$prevRatedItemIdList,
				[
					'USE_IN_RATING' => 'N',
				],
				true
			);
		}

		MultiValueStoreService::getInstance()->deleteAll(self::generateJobCallAssessmentBindKey($result->getParentJobId(), $activityId));
	}

	private static function getAssessmentSettings(int $activityId, ?int $assessmentSettingsId = null, ?int $parentJobId = null): ?CallAssessmentItem
	{
		static $result;

		if (!is_null($result))
		{
			return $result;
		}

		if ($assessmentSettingsId === null && $parentJobId !== null)
		{
			$key = self::generateJobCallAssessmentBindKey($parentJobId, $activityId);
			$assessmentSettingsIdValue = MultiValueStoreService::getInstance()->getFirstValue($key);
			if (is_numeric($assessmentSettingsIdValue))
			{
				$assessmentSettingsId = (int)$assessmentSettingsIdValue;
			}
		}

		if (isset($assessmentSettingsId))
		{
			$assessmentSettingsItem = CopilotCallAssessmentController::getInstance()->getById($assessmentSettingsId);
			if ($assessmentSettingsItem)
			{
				$result = CallAssessmentItem::createFromEntity($assessmentSettingsItem);
			}
		}

		if (!$result)
		{
			$result = ItemFactory::getByActivityId($activityId);
		}

		return $result;
	}

	private static function getAssessmentsValue(ScoreCallPayload $payload): int
	{
		$criteriaList = $payload->criteria;
		if (empty($criteriaList))
		{
			return 0;
		}

		// filter out unrated criteria
		$criteriaList = array_values(
			array_filter(
				$criteriaList,
				static fn(object $item) => isset($item->status) && is_bool($item->status)
			)
		);

		$countTrue = 0;
		$totalCount = count($criteriaList);
		foreach ($criteriaList as $item)
		{
			if ($item->status)
			{
				++$countTrue;
			}
		}

		return round($countTrue / $totalCount * 100);
	}

	private static function isCriteriaListEmpty(array $list): bool
	{
		$criteriaLis = array_filter(
			$list,
			static fn(object $item) => isset($item->status) && is_bool($item->status))
		;

		return empty($criteriaLis);
	}

	private static function trySyncScoreStatusBadge(int $activityId, int $assessment, int $assessmentLowBorder): void
	{
		$itemIdentifier = (new Orchestrator())->findPossibleFillFieldsTarget($activityId);
		if (!$itemIdentifier)
		{
			return;
		}

		Badge\Badge::deleteByEntity($itemIdentifier, Badge\Badge::AI_CALL_SCORING_STATUS);

		if ($assessment > $assessmentLowBorder)
		{
			return;
		}

		$badge = Container::getInstance()->getBadge(Badge\Badge::AI_CALL_SCORING_STATUS, Badge\Type\AiCallScoringStatus::FAILED_VALUE);
		$sourceIdentifier = new Badge\SourceIdentifier(
			Badge\SourceIdentifier::CRM_OWNER_TYPE_PROVIDER,
			CCrmOwnerType::Activity,
			$activityId,
		);

		$badge->bind($itemIdentifier, $sourceIdentifier);
		Monitor::getInstance()->onBadgesSync($itemIdentifier);
	}

	public static function generateJobCallAssessmentBindKey(int $jobId, int $activityId): string
	{
		return "job_{$jobId}_activity_{$activityId}_bind_call_assessment";
	}
}
