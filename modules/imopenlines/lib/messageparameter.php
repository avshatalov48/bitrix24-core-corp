<?php

namespace Bitrix\Imopenlines;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Im\V2\Message\Param;


class MessageParameter
{
	public const
		CONNECTOR_MID = 'CONNECTOR_MID',
		IMOL_QUOTE_MSG = 'IMOL_QUOTE_MSG',
		IMOL_VOTE = 'IMOL_VOTE',
		IMOL_VOTE_TEXT = 'IMOL_VOTE_TEXT',
		IMOL_VOTE_LIKE = 'IMOL_VOTE_LIKE',
		IMOL_VOTE_DISLIKE = 'IMOL_VOTE_DISLIKE',
		IMOL_VOTE_HEAD = 'IMOL_VOTE_HEAD',
		IMOL_COMMENT_HEAD = 'IMOL_COMMENT_HEAD',
		IMOL_FORM = 'IMOL_FORM',
		IMOL_DATE_CLOSE_VOTE = 'IMOL_DATE_CLOSE_VOTE',
		IMOL_TIME_LIMIT_VOTE = 'IMOL_TIME_LIMIT_VOTE',
		IMOL_VOTE_SID = 'IMOL_VOTE_SID',
		IMOL_VOTE_USER = 'IMOL_VOTE_USER',
		IMOL_SID = 'IMOL_SID'
	;

	public static function onInitTypes(Event $event): EventResult
	{
		$settings = [
			self::CONNECTOR_MID => [
				'type' => Param::TYPE_STRING_ARRAY,
			],
			// Allow|Disallow to quote message
			self::IMOL_QUOTE_MSG => [
				'type' => Param::TYPE_BOOL,
				'default' => false,
			],
			// Vote value: dislike|like
			self::IMOL_VOTE => [
				'type' => Param::TYPE_STRING,
			],
			self::IMOL_VOTE_TEXT => [
				'type' => Param::TYPE_STRING,
				'default' => '',
				'saveValueFilter' => [\Bitrix\Im\Text::class, 'encodeEmoji'],
				'loadValueFilter' => [\Bitrix\Im\Text::class, 'decodeEmoji'],
			],
			// Vote like button text
			self::IMOL_VOTE_LIKE => [
				'type' => Param::TYPE_STRING,
				'default' => '',
				'saveValueFilter' => [\Bitrix\Im\Text::class, 'encodeEmoji'],
				'loadValueFilter' => [\Bitrix\Im\Text::class, 'decodeEmoji'],
			],
			// Vote dislike button text
			self::IMOL_VOTE_DISLIKE => [
				'type' => Param::TYPE_STRING,
				'default' => '',
				'saveValueFilter' => [\Bitrix\Im\Text::class, 'encodeEmoji'],
				'loadValueFilter' => [\Bitrix\Im\Text::class, 'decodeEmoji'],
			],
			// Vote value by manager
			self::IMOL_VOTE_HEAD => [
				'type' => Param::TYPE_INT,
			],
			// Manager comment
			self::IMOL_COMMENT_HEAD => [
				'type' => Param::TYPE_STRING,
			],
			// Form type: like|welcome
			self::IMOL_FORM => [
				'type' => Param::TYPE_STRING,
			],
			self::IMOL_DATE_CLOSE_VOTE => [
				'type' => Param::TYPE_DATE_TIME,
			],
			// Vote time limit
			self::IMOL_TIME_LIMIT_VOTE => [
				'type' => Param::TYPE_INT,
			],
			// OL session Id
			self::IMOL_VOTE_SID => [
				'type' => Param::TYPE_INT,
			],
			self::IMOL_VOTE_USER => [
				'type' => Param::TYPE_INT,
			],
			// OL session Id
			self::IMOL_SID => [
				'type' => Param::TYPE_INT,
			],
		];

		return new EventResult(EventResult::SUCCESS, $settings, 'imopenlines');
	}
}