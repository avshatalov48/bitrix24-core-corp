<?php
namespace Bitrix\Intranet\Absence;

use \Bitrix\Intranet\UserAbsence;

use \Bitrix\Main\Event,
	\Bitrix\Main\Loader,
	\Bitrix\Main\Type\DateTime;

use \Bitrix\Iblock;

/**
 * Class Agent
 * @package Bitrix\Intranet\Absence
 */
class Agent
{
	const EVENT_START_ABSENCE = 'OnStartAbsence';
	const EVENT_END_ABSENCE = 'OnEndAbsence';

	/**
	 * @param $iblockElementId
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function start($iblockElementId)
	{
		return self::general($iblockElementId, self::EVENT_START_ABSENCE);
	}

	/**
	 * @param $iblockElementId
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function end($iblockElementId)
	{
		return self::general($iblockElementId, self::EVENT_END_ABSENCE);
	}

	/**
	 * @param $iblockElementId
	 * @param $type
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected static function general($iblockElementId, $type)
	{
		if(Loader::includeModule('iblock') && is_numeric($iblockElementId) && $iblockElementId>0)
		{
			$iblockId = UserAbsence::getIblockId();

			if($iblockId > 0)
			{
				$userId = \CIBlockElement::GetProperty($iblockId, $iblockElementId, ["sort" => "asc"], ["CODE"=>"USER"])->Fetch()['VALUE'];
				$absenceType = \CIBlockElement::GetProperty($iblockId, $iblockElementId, ["sort" => "asc"], ["CODE"=>"ABSENCE_TYPE"])->Fetch()['VALUE_XML_ID'];

				$absence = Iblock\ElementTable::getList(
					[
						'select' => [
							'ACTIVE_FROM',
							'ACTIVE_TO'
						],
						'filter' => [
							'ID' => $iblockElementId
						]
					]
				)->Fetch();

				if(!empty($userId) && !empty($absenceType) && !empty($absence))
				{
					$duration = 0;

					if($absence['ACTIVE_TO'] instanceof DateTime && $absence['ACTIVE_FROM'] instanceof DateTime)
					{
						$duration = $absence['ACTIVE_TO']->getTimestamp() - $absence['ACTIVE_FROM']->getTimestamp();
					}

					$data = [
						'USER_ID' => $userId,
						'ABSENCE_TYPE' => $absenceType,
						'START' => $absence['ACTIVE_FROM'],
						'END' => $absence['ACTIVE_TO'],
						'DURATION' => $duration,
					];

					$event = new Event('intranet', $type, $data);
					$event->send();
				}
			}
		}

		return '';
	}
}