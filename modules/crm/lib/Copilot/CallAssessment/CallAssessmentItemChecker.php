<?php

namespace Bitrix\Crm\Copilot\CallAssessment;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integration\AI\Model\QueueTable;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class CallAssessmentItemChecker
{
	use Singleton;

	private ?CallAssessmentItem $item;

	public function setItem(?CallAssessmentItem $item): self
	{
		$this->item = $item;

		return $this;
	}

	public function run(): Result
	{
		$result = new Result();

		if (!$this->item)
		{
			return $result->addError(new Error(
				Loc::getMessage('CALL_ASSESSMENT_ITEM_NOT_FOUND'),
				ErrorCode::NOT_FOUND
			));
		}

		if (!$this->item->isEnabled())
		{
			return $result->addError(new Error(
				Loc::getMessage('CALL_ASSESSMENT_ITEM_DISABLED'),
				ErrorCode::ACCESS_DENIED
			));
		}

		if (
			$this->isPromptEmpty()
			|| $this->isGistEmpty()
			|| $this->isJobIdEmpty()
			|| $this->isStatusNotSuccess()
		)
		{
			return $result->addError(new Error(
				Loc::getMessage('CALL_ASSESSMENT_ITEM_INVALID'),
				ErrorCode::INVALID_ARG_VALUE
			));
		}

		return $result; // success
	}

	private function isPromptEmpty(): bool
	{
		return empty(trim($this->item->getPrompt()));
	}

	private function isGistEmpty(): bool
	{
		return empty(trim($this->item->getGist()));
	}

	private function isJobIdEmpty(): bool
	{
		return $this->item->getJobId() <= 0
			&& empty($this->item->getCode()) // default scripts has 0 JOB_ID and filled CODE
		;
	}

	private function isStatusNotSuccess(): bool
	{
		return $this->item->getStatus() !== QueueTable::EXECUTION_STATUS_SUCCESS;
	}
}
