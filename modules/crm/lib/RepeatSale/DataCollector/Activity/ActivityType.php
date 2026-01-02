<?php

namespace Bitrix\Crm\RepeatSale\DataCollector\Activity;

enum ActivityType: string
{
	case CALL_RECORDING_TRANSCRIPTS = 'call_recording_transcripts';
	case COMMENTS = 'comments';
	case TODOS = 'todos';
	case EMAILS = 'emails';
	case OPEN_LINES = 'open_lines';

	public static function getAllTypes(): array
	{
		return array_map(static fn(self $case) => $case->value, self::cases());
	}

	public static function isCommunicationChannel(string $type): bool
	{
		return in_array(
			$type,
			[
				self::CALL_RECORDING_TRANSCRIPTS->value,
				self::EMAILS->value,
				self::OPEN_LINES->value,
			],
			true
		);
	}

	public static function mapCommunicationChannel(string $type): string
	{
		return match ($type)
		{
			self::CALL_RECORDING_TRANSCRIPTS->value => 'call',
			self::EMAILS->value => 'email',
			self::OPEN_LINES->value => 'chat',
			default => 'unknown_contact_method',
		};
	}

	/**
	 * No localization - used for tests
	 *
	 * @return string
	 */
	public function getDisplayName(): string
	{
		return match ($this)
		{
			self::CALL_RECORDING_TRANSCRIPTS => 'Call Recording Transcripts',
			self::COMMENTS => 'Comments',
			self::TODOS => 'To-Do Items',
			self::EMAILS => 'Emails',
			self::OPEN_LINES => 'Open Lines',
		};
	}
}
