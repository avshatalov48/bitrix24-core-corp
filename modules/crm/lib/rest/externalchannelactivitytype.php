<?php

namespace Bitrix\Crm\Rest;

class CCrmExternalChannelActivityType
{
	const Undefined = 0;
	const Activity = 1;
	const ImportAgent = 2;
	const ActivityFaceCard = 3;

	const First = 1;
	const Last = 3;

	const ActivityName = 'ACTIVITY';
	const ImportAgentName = 'IMPORT_AGENT';
	const ActivityFaceCardName = 'ACTIVITY_FACE_CARD';

	private static $ALL_DESCRIPTIONS = array();

	public static function isDefined($typeID)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}
		return $typeID >= self::First && $typeID <= self::Last;
	}

	public static function resolveID($name)
	{
		$name = strtoupper(trim(strval($name)));
		if($name == '')
		{
			return self::Undefined;
		}

		switch($name)
		{
			case self::ActivityName:
				return self::Activity;
			case self::ImportAgentName:
				return self::ImportAgent;
			case self::ActivityFaceCardName:
				return self::ActivityFaceCard;

			default:
				return self::Undefined;
		}
	}

	public static function resolveName($typeID)
	{
		if(!is_numeric($typeID))
		{
			return '';
		}

		$typeID = intval($typeID);
		if($typeID <= 0)
		{
			return '';
		}

		switch($typeID)
		{
			case self::Activity:
				return self::ActivityName;
			case self::ImportAgent:
				return self::ImportAgentName;
			case self::ActivityFaceCard:
				return self::ActivityFaceCardName;

			case self::Undefined:
			default:
				return '';
		}
	}

	public static function getAllDescriptions()
	{
		if(!self::$ALL_DESCRIPTIONS[LANGUAGE_ID])
		{
			IncludeModuleLangFile(__FILE__);

			self::$ALL_DESCRIPTIONS[LANGUAGE_ID] = array(
					self::Activity => GetMessage('CRM_EXTERNAL_CHANNEL_ACTIVITY_TYPE_ACTIVITY'),
					self::ImportAgent => GetMessage('CRM_EXTERNAL_CHANNEL_ACTIVITY_TYPE_AGENT'),
					self::ActivityFaceCard => GetMessage('CRM_EXTERNAL_CHANNEL_ACTIVITY_TYPE_ACTIVITY_FACE_CARD'),
			);
		}

		return self::$ALL_DESCRIPTIONS[LANGUAGE_ID];
	}
}