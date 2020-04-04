<?php
namespace Bitrix\Tasks\Import;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class PersonNameFormatter
{
	const Undefined = 0;
	const Dflt = 1;
	const FirstLast = 2;
	const FirstSecondLast = 3;
	const LastFirst = 4;
	const LastFirstSecond = 5;
	const HonorificLast = 6;

	const FirstLastFormat = '#NAME# #LAST_NAME#';
	const FirstSecondLastFormat = '#NAME# #SECOND_NAME# #LAST_NAME#';
	const LastFirstFormat = '#LAST_NAME# #NAME#';
	const LastFirstSecondFormat = '#LAST_NAME# #NAME# #SECOND_NAME#';
	const HonorificLastFormat = '#TITLE# #LAST_NAME#';

	private static $formatID = null;
	private static $formatString = null;
	private static $allDescriptions = null;

	public static function isDefined($formatID)
	{
		if(!is_int($formatID))
		{
			$formatID = intval($formatID);
		}
		return $formatID > self::Undefined && $formatID <= self::HonorificLast;
	}

	public static function getFormatID()
	{
		if(self::$formatID !== null)
		{
			return self::$formatID;
		}

		$formatID = intval(\COption::GetOptionString('tasks', 'person_name_format_id', 0));
		if(!self::isDefined($formatID))
		{
			$formatID = self::Dflt;
		}
		self::$formatID = $formatID;
		return self::$formatID;
	}

	public static function setFormatID($formatID)
	{
		if(!is_int($formatID))
		{
			throw new Main\ArgumentTypeException('formatID', 'integer');
		}

		if(!self::isDefined($formatID))
		{
			return false;
		}

		self::$formatID = $formatID;
		self::$formatString = null;
		if($formatID !== self::Dflt)
		{
			return \COption::SetOptionString('tasks', 'person_name_format_id', $formatID);
		}
		// Do not store default format ID
		\COption::RemoveOption('tasks', 'person_name_format_id');
		return true;
	}

	public static function getAllDescriptions()
	{
		if(!self::$allDescriptions)
		{
			IncludeModuleLangFile(__FILE__);

			self::$allDescriptions = array(
				self::Dflt => Loc::getMessage('TASKS_PERSON_NAME_FORMAT_DEFAULT'),
				self::HonorificLast => Loc::getMessage('TASKS_PERSON_NAME_FORMAT_HONORIFIC_LAST'),
				self::FirstLast => Loc::getMessage('TASKS_PERSON_NAME_FORMAT_FIRST_LAST'),
				self::FirstSecondLast => Loc::getMessage('TASKS_PERSON_NAME_FORMAT_FIRST_SECOND_LAST'),
				self::LastFirst => Loc::getMessage('TASKS_PERSON_NAME_FORMAT_LAST_FIRST'),
				self::LastFirstSecond => Loc::getMessage('TASKS_PERSON_NAME_FORMAT_LAST_FIRST_SECOND')
			);
		}
		return self::$allDescriptions;
	}

	public static function getFormatByID($formatID)
	{
		$formatID = intval($formatID);
		switch($formatID)
		{
			case self::FirstLast:
				return self::FirstLastFormat;
			case self::FirstSecondLast:
				return self::FirstSecondLastFormat;
			case self::LastFirst:
				return self::LastFirstFormat;
			case self::LastFirstSecond:
				return self::LastFirstSecondFormat;
			case self::HonorificLast:
				return self::HonorificLastFormat;
		}
		return \CSite::GetNameFormat(false);
	}

	public static function getFormat()
	{
		if(self::$formatString !== null)
		{
			return self::$formatString;
		}

		$formatID = self::getFormatID();
		switch($formatID)
		{
			case self::FirstLast:
				self::$formatString = self::FirstLastFormat;
				break;
			case self::FirstSecondLast:
				self::$formatString = self::FirstSecondLastFormat;
				break;
			case self::LastFirst:
				self::$formatString = self::LastFirstFormat;
				break;
			case self::LastFirstSecond:
				self::$formatString = self::LastFirstSecondFormat;
				break;
			case self::HonorificLast:
				self::$formatString = self::HonorificLastFormat;
				break;
			default:
				self::$formatString = \CSite::GetNameFormat(false);
		}
		return self::$formatString;
	}

	public static function tryParseName($name, $formatID, &$nameParts)
	{
		if(!is_string($name) || $name === '')
		{
			return false;
		}

		$formatID = intval($formatID);
		if(!self::isDefined($formatID))
		{
			throw new Main\NotSupportedException("Format: '{$formatID}' is not supported in current context");
		}

		if($formatID === self::FirstSecondLast || $formatID === self::LastFirstSecond)
		{
			if(preg_match('/^\s*(\S+)\s+(\S+)\s+(\S+)\s*$/', $name, $m) === 1)
			{
				if(!is_array($nameParts))
				{
					$nameParts = array();
				}

				if($formatID === self::FirstSecondLast)
				{
					$nameParts['NAME'] = $m[1];
					$nameParts['SECOND_NAME'] = $m[2];
					$nameParts['LAST_NAME'] = $m[3];
				}
				else //$formatID === self::LastFirstSecond
				{
					$nameParts['LAST_NAME'] = $m[1];
					$nameParts['NAME'] = $m[2];
					$nameParts['SECOND_NAME'] = $m[3];
				}

				return true;
			}
		}

		if(preg_match('/^\s*(\S+)\s+(\S+)\s*$/', $name, $m) === 1)
		{
			if(!is_array($nameParts))
			{
				$nameParts = array();
			}

			if($formatID === self::HonorificLast)
			{
				$nameParts['TITLE'] = $m[1];
				$nameParts['NAME'] = '';
				$nameParts['SECOND_NAME'] = '';
				$nameParts['LAST_NAME'] = $m[2];
			}
			elseif($formatID === self::FirstLast || $formatID === self::FirstSecondLast)
			{
				$nameParts['NAME'] = $m[1];
				$nameParts['SECOND_NAME'] = '';
				$nameParts['LAST_NAME'] = $m[2];
			}
			else //$formatID === self::LastFirst || $formatID === self::LastFirstSecond
			{
				$nameParts['LAST_NAME'] = $m[1];
				$nameParts['NAME'] = $m[2];
				$nameParts['SECOND_NAME'] = '';
			}

			return true;
		}

		return false;
	}
}