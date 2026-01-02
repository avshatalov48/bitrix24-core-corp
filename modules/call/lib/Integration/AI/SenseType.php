<?php

namespace Bitrix\Call\Integration\AI;

use Bitrix\Call\Integration\AI\Task;

enum SenseType: string
{
	case TRANSCRIBE = 'transcribe';
	case OVERVIEW = 'overview';
	case SUMMARY = 'summary';
	case INSIGHTS = 'insights';

	/**
	 * @return string|Task\AITask
	 */
	public function getTaskClass(): string
	{
		return match($this)
		{
			self::TRANSCRIBE => Task\TranscribeCallRecord::class,
			self::OVERVIEW => Task\TranscriptionOverview::class,
			self::SUMMARY => Task\TranscriptionSummary::class,
			self::INSIGHTS => Task\TranscriptionInsights::class,
		};
	}
}