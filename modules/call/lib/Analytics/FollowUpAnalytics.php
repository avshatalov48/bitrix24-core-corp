<?php

namespace Bitrix\Call\Analytics;

use Bitrix\Call\Analytics\Event\FollowUpEvent;
use Bitrix\Call\Analytics\Event\FollowUpTaskEvent;
use Bitrix\Call\Integration\AI\SenseType;
use Bitrix\Call\Integration\AI\Task\AITask;

class FollowUpAnalytics extends AbstractAnalytics
{
	public function addFollowUpResultMessage(): self
	{
		$this->async(function () {
			(new FollowUpEvent('send_message', $this->call))
				->setType('follow_up')//st[type]
				->setStatus('summary')
				->send()
			;
		});

		return $this;
	}

	public function addFollowUpErrorMessage(string $errorCode): self
	{
		$this->async(function () use ($errorCode) {
			(new FollowUpEvent('send_message', $this->call))
				->setType('processing_error')//st[type]
				->setStatus('processing_error_' . $errorCode)
				->send()
			;
		});

		return $this;
	}

	public function addStopRecording(): self
	{
		$this->async(function () {
			(new FollowUpTaskEvent('ai_record_stop', $this->call))
				->setStatus('success')
				->send()
			;
		});

		return $this;
	}

	public function addErrorRecording(string $errorCode): self
	{
		$this->async(function () use ($errorCode) {
			(new FollowUpTaskEvent('ai_record_stop', $this->call))
				->setStatus('error_'. $errorCode)
				->send()
			;
		});

		return $this;
	}

	public function addGotEmptyRecord(): self
	{
		$this->async(function () {
			(new FollowUpTaskEvent('blank_record', $this->call))
				->setSection('call_followup')
				->setP4('recordDuration_0')
				->send()
			;
		});

		return $this;
	}

	public function addAITaskLunch(AITask $task): self
	{
		$eventType = match ($task->getAISenseType())
		{
			SenseType::TRANSCRIBE->value => 'audio_to_text',
			SenseType::SUMMARY->value => 'meeting_summarization',
			SenseType::OVERVIEW->value => 'meeting_overview',
			SenseType::INSIGHTS->value => 'meeting_insights',
		};
		$this->async(function () use ($eventType) {
			(new FollowUpTaskEvent($eventType, $this->call))
				->setSection('success')
				->send()
			;
		});

		return $this;
	}

	public function addAITaskFailed(AITask $task, string $errorCode): self
	{
		$eventType = match ($task->getAISenseType())
		{
			SenseType::TRANSCRIBE->value => 'audio_to_text',
			SenseType::SUMMARY->value => 'meeting_summarization',
			SenseType::OVERVIEW->value => 'meeting_overview',
			SenseType::INSIGHTS->value => 'meeting_insights',
		};
		$this->async(function () use ($eventType, $errorCode) {
			(new FollowUpTaskEvent($eventType, $this->call))
				->setSection('error_'. $errorCode)
				->send()
			;
		});

		return $this;
	}
}
