<?php
/**
 * Created by PhpStorm.
 * User: evgenik
 * Date: 16.05.2016
 * Time: 15:19
 */

namespace Bitrix\Crm;


class EntityAddressType
{
	const Undefined = 0;
	const Primary = 1;
	const Secondary = 2;
	const Third = 3;
	const Home = 4;
	const Work = 5;
	const Registered = 6;
	const Custom = 7;
	const Post = 8;
	const Beneficiary = 9;
	const Bank = 10;

	const First = 1;
	const Last = 10;

	const PrimaryName = 'PRIMARY';
	const SecondaryName = 'SECONDARY';
	const ThirdName = 'THIRD';
	const HomeName = 'HOME';
	const WorkName = 'WORK';
	const RegisteredName = 'REGISTERED';
	const CustomName = 'CUSTOM';
	const PostName = 'POST';
	const BeneficiaryName = 'BENEFICIARY';
	const BankName = 'BANK';

	private static $ALL_DESCRIPTIONS = array();

	public static function isDefined($typeID)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}
		return $typeID >= self::First && $typeID <= self::Last;
	}

	public static function getAllIDs()
	{
		return array(
			self::Primary,
			self::Secondary,
			self::Third,
			self::Home,
			self::Work,
			self::Registered,
			self::Custom,
			self::Post,
			self::Beneficiary,
			self::Bank
		);
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
			case self::PrimaryName:
				return self::Primary;

			case self::SecondaryName:
				return self::Secondary;

			case self::ThirdName:
				return self::Third;

			case self::HomeName:
				return self::Home;

			case self::WorkName:
				return self::Work;

			case self::RegisteredName:
				return self::Registered;

			case self::CustomName:
				return self::Custom;

			case self::PostName:
				return self::Post;

			case self::BeneficiaryName:
				return self::Beneficiary;

			case self::BankName:
				return self::Bank;

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
			case self::Primary:
				return self::PrimaryName;

			case self::Secondary:
				return self::SecondaryName;

			case self::Third:
				return self::ThirdName;

			case self::Home:
				return self::HomeName;

			case self::Work:
				return self::WorkName;

			case self::Registered:
				return self::RegisteredName;

			case self::Custom:
				return self::CustomName;

			case self::Post:
				return self::PostName;

			case self::Beneficiary:
				return self::BeneficiaryName;

			case self::Bank:
				return self::BankName;

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
				self::Primary => GetMessage('CRM_ADDRESS_TYPE_PRIMARY'),
				self::Secondary => GetMessage('CRM_ADDRESS_TYPE_SECONDARY'),
				self::Third => GetMessage('CRM_ADDRESS_TYPE_THIRD'),
				self::Home => GetMessage('CRM_ADDRESS_TYPE_HOME'),
				self::Work => GetMessage('CRM_ADDRESS_TYPE_WORK'),
				self::Registered => GetMessage('CRM_ADDRESS_TYPE_REGISTERED'),
				self::Custom => GetMessage('CRM_ADDRESS_TYPE_CUSTOM'),
				self::Post => GetMessage('CRM_ADDRESS_TYPE_POST'),
				self::Beneficiary => GetMessage('CRM_ADDRESS_TYPE_BENEFICIARY'),
				self::Bank => GetMessage('CRM_ADDRESS_TYPE_BANK')
			);
		}

		return self::$ALL_DESCRIPTIONS[LANGUAGE_ID];
	}

	public static function getDescription($typeID)
	{
		$typeID = intval($typeID);
		$all = self::getAllDescriptions();
		return isset($all[$typeID]) ? $all[$typeID] : '';
	}

	public static function getDescriptions($types)
	{
		$result = array();
		if(is_array($types))
		{
			foreach($types as $typeID)
			{
				$typeID = intval($typeID);
				$descr = self::getDescription($typeID);
				if($descr !== '')
				{
					$result[$typeID] = $descr;
				}
			}
		}
		return $result;
	}
}