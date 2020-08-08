<?php
namespace Bitrix\Crm\Format;
use Bitrix\Main;
class PersonNameFormatter
{
	const Undefined = 0;
	const Dflt = 1;
	const FirstLast = 2;
	const FirstSecondLast = 3;
	const LastFirst = 4;
	const LastFirstSecond = 5;
	const HonorificLast = 6;
	//const Custom = 100;

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

		$formatID = intval(\COption::GetOptionString('crm', 'prsn_nm_frmt_id', 0));
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
			return \COption::SetOptionString('crm', 'prsn_nm_frmt_id', $formatID);
		}
		// Do not store default format ID
		\COption::RemoveOption('crm', 'prsn_nm_frmt_id');
		return true;
	}
	public static function getAllDescriptions()
	{
		if(!self::$allDescriptions)
		{
			IncludeModuleLangFile(__FILE__);

			self::$allDescriptions = array(
				self::Dflt => GetMessage('CRM_PRSN_NM_FRMT_DEFAULT'),
				self::HonorificLast => GetMessage('CRM_PRSN_NM_FRMT_HONORIFIC_LAST'),
				self::FirstLast => GetMessage('CRM_PRSN_NM_FRMT_FIRST_LAST'),
				self::FirstSecondLast => GetMessage('CRM_PRSN_NM_FRMT_FIRST_SECOND_LAST'),
				self::LastFirst => GetMessage('CRM_PRSN_NM_FRMT_LAST_FIRST'),
				self::LastFirstSecond => GetMessage('CRM_PRSN_NM_FRMT_LAST_FIRST_SECOND')
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
	public static function hasFirstName($format)
	{
		return mb_stripos($format, '#NAME#') !== false;
	}
	public static function hasSecondName($format)
	{
		return mb_stripos($format, '#SECOND_NAME#') !== false;
	}
	public static function hasLastName($format)
	{
		return mb_stripos($format, '#LAST_NAME#') !== false;
	}
	public static function tryParseFormatID($format, $defaultFormatID = '')
	{
		$format = str_replace(
			array(',',  '#NAME_SHORT#', '#SECOND_NAME_SHORT#'),
			array('',   '#NAME#',       '#SECOND_NAME#'),
			$format
		);

		switch($format)
		{
			case self::FirstLastFormat:
				return self::FirstLast;
			case self::FirstSecondLastFormat:
				return self::FirstSecondLast;
			case self::LastFirstFormat:
				return self::LastFirst;
			case self::LastFirstSecondFormat:
				return self::LastFirstSecond;
			case self::HonorificLastFormat:
				return self::HonorificLast;
		}
		return $defaultFormatID !== '' ? $defaultFormatID : self::FirstLastFormat;
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

		if($formatID === self::Dflt)
		{
			$formatID = self::tryParseFormatID(\CSite::GetNameFormat(false));
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

		$parts = preg_split('/[\s]+/', $name);
		if(!empty($parts))
		{
			if($formatID === self::FirstLast || $formatID === self::FirstSecondLast)
			{
				$nameParts['NAME'] = array_shift($parts);
				$nameParts['LAST_NAME'] = implode(' ', $parts);
			}
			else//if($formatID === self::LastFirst || $formatID === self::LastFirstSecond
			{
				$nameParts['NAME'] = array_pop($parts);
				$nameParts['LAST_NAME'] = implode(' ', $parts);
			}

			return true;
		}

		if($formatID === self::FirstLast || $formatID === self::FirstSecondLast)
		{
			$nameParts['NAME'] = $name;
			$nameParts['LAST_NAME'] = '';
		}
		else//if($formatID === self::LastFirst || $formatID === self::LastFirstSecond
		{
			$nameParts['NAME'] = '';
			$nameParts['LAST_NAME'] = $name;
		}

		return true;
	}
}