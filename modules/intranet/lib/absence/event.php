<?php
namespace Bitrix\Intranet\Absence;

use \Bitrix\Iblock;

use \Bitrix\Main\Loader,
	\Bitrix\Main\Type\DateTime;

use \Bitrix\Intranet\UserAbsence;

/**
 * Class Event
 * @package Bitrix\Intranet\Absence
 */
class Event
{
	/**
	 * @param $fields
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function onAfterIblockElementAdd($fields)
	{
		if ($fields['RESULT'] === false)
		{
			return;
		}

		$iblockId = UserAbsence::getIblockId();
		if ($iblockId > 0 && intval($fields['IBLOCK_ID']) == $iblockId)
		{
			UserAbsence::cleanCache();

			self::addAgent($fields['ID']);
		}

		return true;
	}

	/**
	 * @param $fields
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function onAfterIblockElementUpdate($fields)
	{
		$iblockId = UserAbsence::getIblockId();
		if ($iblockId > 0 && intval($fields['IBLOCK_ID']) == $iblockId)
		{
			UserAbsence::cleanCache();

			self::deleteAgent($fields['ID']);

			self::addAgent($fields['ID']);
		}

		return true;
	}

	/**
	 * @param $fields
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function onAfterIblockElementDelete($fields)
	{
		$iblockId = UserAbsence::getIblockId();
		if ($iblockId > 0 && intval($fields['IBLOCK_ID']) == $iblockId)
		{
			UserAbsence::cleanCache();

			self::deleteAgent($fields['ID']);
		}

		return true;
	}

	/**
	 * @param $elementId
	 */
	protected static function deleteAgent($elementId)
	{
		\CAgent::RemoveAgent(self::getNameAgentStart($elementId), "intranet");
		\CAgent::RemoveAgent(self::getNameAgentEnd($elementId), "intranet");
	}

	/**
	 * @param $elementId
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected static function addAgent($elementId)
	{
		if(Loader::includeModule('iblock'))
		{
			$timezone = null;

			$absence = Iblock\ElementTable::getList(
				[
					'select' => [
						'ACTIVE_FROM',
						'ACTIVE_TO'
					],
					'filter' => [
						'ID' => $elementId
					]
				]
			)->Fetch();

			if(!empty($absence['ACTIVE_FROM']) && $absence['ACTIVE_FROM'] instanceof DateTime)
			{
				\CAgent::AddAgent(
					self::getNameAgentStart($elementId),
					"intranet",
					"N",
					86400,
					"",
					"Y",
					$absence['ACTIVE_FROM']->toString());
			}

			if(!empty($absence['ACTIVE_TO']) && $absence['ACTIVE_TO'] instanceof DateTime)
			{
				\CAgent::AddAgent(
					self::getNameAgentEnd($elementId),
					"intranet",
					"N",
					86400,
					"",
					"Y",
					$absence['ACTIVE_TO']->toString());
			}
		}
	}

	/**
	 * @param $elementId
	 * @return string
	 */
	protected static function getNameAgentStart($elementId)
	{
		return '\Bitrix\Intranet\Absence\Agent::start(' . $elementId . ');';
	}

	/**
	 * @param $elementId
	 * @return string
	 */
	protected static function getNameAgentEnd($elementId)
	{
		return '\Bitrix\Intranet\Absence\Agent::end(' . $elementId . ');';
	}
}
