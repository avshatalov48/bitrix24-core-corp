<?php

namespace Bitrix\Crm\Integration\Analytics\Builder\AI;

use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Dto\FillItemFieldsFromCallTranscriptionPayload;
use Bitrix\Crm\Integration\AI\ErrorCode;
use Bitrix\Crm\Integration\Analytics\Builder\AbstractBuilder;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Main\Result;
use CCrmActivityDirection;
use CCrmOwnerType;

/**
 * It has a bit of copy-paste from
 * @see AIBaseEvent
 *
 * If you add more copy-paste here, consider refactoring it
 */
final class CallParsingEvent extends AbstractBuilder
{
	private string $tool = Dictionary::TOOL_AI;
	private string $category = Dictionary::CATEGORY_CRM_OPERATIONS;
	private string $type = Dictionary::TYPE_MANUAL;

	private ?int $activityOwnerTypeId = null;
	private ?int $activityId = null;
	private ?int $activityDirection = null;
	private ?int $totalScenarioDuration = null;

	public function setTool(string $tool): self
	{
		$this->tool = $tool;

		return $this;
	}

	public function setCategory(string $category): self
	{
		$this->category = $category;

		return $this;
	}

	protected function getTool(): string
	{
		return $this->tool;
	}

	protected function customValidate(): Result
	{
		$result = new Result();

		if (!CCrmOwnerType::IsDefined($this->activityOwnerTypeId))
		{
			$result->addError(
				\Bitrix\Crm\Controller\ErrorCode::getRequiredArgumentMissingError('activityOwnerTypeId'),
			);
		}

		if ($this->activityId <= 0)
		{
			$result->addError(
				\Bitrix\Crm\Controller\ErrorCode::getRequiredArgumentMissingError('activityId'),
			);
		}

		if (!CCrmActivityDirection::IsDefined($this->activityDirection))
		{
			$result->addError(
				\Bitrix\Crm\Controller\ErrorCode::getRequiredArgumentMissingError('activityDirection'),
			);
		}

		return $result;
	}

	protected function buildCustomData(): array
	{
		$this->setSection(Dictionary::SECTION_CRM);
		$this->setSubSection(Dictionary::getAnalyticsEntityType($this->activityOwnerTypeId));
		$this->setP2('callDirection', mb_strtolower(CCrmActivityDirection::ResolveName($this->activityDirection)));
		$this->setP5('idCall', (string)$this->activityId);

		if ($this->totalScenarioDuration !== null)
		{
			$this->setP4('duration', $this->totalScenarioDuration);
		}

		return [
			'type' => $this->type,
			'category' => $this->category,
			'event' => Dictionary::EVENT_CALL_PARSING,
		];
	}

	public function setIsManualLaunch(bool $isManualLaunch): self
	{
		$this->type = $isManualLaunch ? Dictionary::TYPE_MANUAL : Dictionary::TYPE_AUTO;

		return $this;
	}

	public function setActivityOwnerTypeId(int $entityTypeId): self
	{
		$this->activityOwnerTypeId = $entityTypeId;

		return $this;
	}

	public function setActivityId(int $activityId): self
	{
		$this->activityId = $activityId;

		return $this;
	}

	public function setActivityDirection(int $directionTypeId): self
	{
		$this->activityDirection = $directionTypeId;

		return $this;
	}

	public function setTotalScenarioDuration(?int $seconds): self
	{
		$this->totalScenarioDuration = $seconds;

		return $this;
	}

	public static function resolveStatusByJobResult(\Bitrix\Crm\Integration\AI\Result $result): string
	{
		if ($result->isSuccess())
		{
			return self::resolveSuccessStatusByJobResult($result);
		}

		return self::resolveErrorStatusByJobResult($result);
	}

	private static function resolveSuccessStatusByJobResult(\Bitrix\Crm\Integration\AI\Result $result): string
	{
		$payload = $result->getPayload();

		if (!($payload instanceof FillItemFieldsFromCallTranscriptionPayload))
		{
			return Dictionary::STATUS_SUCCESS;
		}

		$atListOneFieldIsApplied = false;
		foreach (array_merge($payload->singleFields, $payload->multipleFields) as $field)
		{
			if ($field->isApplied)
			{
				$atListOneFieldIsApplied = true;
				break;
			}
		}

		if ($atListOneFieldIsApplied)
		{
			return Dictionary::STATUS_SUCCESS_FIELDS;
		}

		if (!empty($payload->unallocatedData))
		{
			return Dictionary::STATUS_SUCCESS_COMMENT;
		}

		return Dictionary::STATUS_SUCCESS;
	}

	private static function resolveErrorStatusByJobResult(\Bitrix\Crm\Integration\AI\Result $result): string
	{
		$limitError = $result->getErrorCollection()->getErrorByCode(ErrorCode::AI_ENGINE_LIMIT_EXCEEDED);
		if ($limitError)
		{
			return match ($limitError->getCustomData()['limitCode']) {
				AIManager::AI_LIMIT_CODE_DAILY => Dictionary::STATUS_ERROR_LIMIT_DAILY,
				AIManager::AI_LIMIT_CODE_MONTHLY => Dictionary::STATUS_ERROR_LIMIT_MONTHLY,
				AIManager::AI_LIMIT_BAAS => Dictionary::STATUS_ERROR_LIMIT_BAAS,
				default => Dictionary::STATUS_ERROR_NO_LIMITS,
			};
		}

		$licenseError = $result->getErrorCollection()->getErrorByCode(ErrorCode::LICENSE_NOT_ACCEPTED);
		if ($licenseError)
		{
			return Dictionary::STATUS_ERROR_AGREEMENT;
		}

		static $b24ErrorCodes = [
			ErrorCode::AI_NOT_AVAILABLE,
			ErrorCode::AI_DISABLED,
			ErrorCode::LICENSE_NOT_ACCEPTED,
			ErrorCode::FILE_NOT_FOUND,
			ErrorCode::FILE_NOT_SUPPORTED,
			ErrorCode::AI_ENGINE_NOT_FOUND,
			ErrorCode::JOB_ALREADY_EXISTS,
			ErrorCode::JOB_MAX_RETRIES_EXCEEDED,
			ErrorCode::NOT_SUITABLE_TARGET,
		];

		foreach ($b24ErrorCodes as $errorCode)
		{
			if ($result->getErrorCollection()->getErrorByCode($errorCode))
			{
				return Dictionary::STATUS_ERROR_B24;
			}
		}

		return Dictionary::STATUS_ERROR_PROVIDER;
	}
}
