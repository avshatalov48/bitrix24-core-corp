<?php

namespace Bitrix\Voximplant\Agent;


use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

class Fixer
{
	/**
	 * Recreates crm activities for calls, lost due to the bug in version 18.5.0 (see http://jabber.bx/view.php?id=106795)
	 */
	public static function restoreLostCrmActivities()
	{
		if(!Loader::includeModule('crm'))
		{
			return '';
		}

		$lastId = (int)Option::get('voximplant', '~activity_fixer_last_processed_id', 0);
		$cursor = \Bitrix\Voximplant\StatisticTable::query()
			->addSelect('*')
			->whereLike('CALL_ID', 'external%')
			->where('CALL_START_DATE', '>', new \Bitrix\Main\Type\DateTime('2018-11-15', 'Y-m-d'))
			->where('INCOMING', '=', '1')
			->where(\Bitrix\Main\Entity\Query::filter()
				->logic('or')
				->where('CRM_ACTIVITY_ID', 0)
				->whereNull('CRM_ACTIVITY_ID')
			)
			->where('ID', '>', $lastId)
			->setLimit(100)
			->exec();

		$found = false;
		while ($row = $cursor->fetch())
		{
			$found = true;

			$activityId = \CVoxImplantCrmHelper::AddCall($row);
			if ($activityId > 0)
			{
				\Bitrix\Voximplant\StatisticTable::update($row['ID'], [
					'CRM_ACTIVITY_ID' => $activityId
				]);
				\CVoxImplantCrmHelper::AttachRecordToCall($row);

				$activity = \CCrmActivity::GetByID($activityId, false);

				$correctDate = new \Bitrix\Main\Type\DateTime($activity['END_TIME'], \Bitrix\Main\Type\Date::convertFormatToPhp(FORMAT_DATETIME));
				\Bitrix\Crm\ActivityTable::update($activityId, [
					'CREATED' => $correctDate,
					'LAST_UPDATED' => $correctDate
				]);

				$timelineCursor = \Bitrix\Crm\Timeline\Entity\TimelineTable::getList([
					'select' => ['ID'],
					'filter' => [
						'=ASSOCIATED_ENTITY_ID' => $activityId,
						'=ASSOCIATED_ENTITY_TYPE_ID' => 6
					]
				]);

				while ($timelineRow = $timelineCursor->fetch())
				{
					\Bitrix\Crm\Timeline\Entity\TimelineTable::update($timelineRow['ID'], [
						'CREATED' => $correctDate
					]);
				}
			}
			$lastId = $row['ID'];
		}

		if ($found)
		{
			Option::set('voximplant', '~activity_fixer_last_processed_id', $lastId);
			return '\Bitrix\Voximplant\Agent\Fixer::restoreLostCrmActivities();';
		}
		else
		{
			Option::delete('voximplant', [
				'name' => '~activity_fixer_last_processed_id'
			]);
			return '';
		}
	}

}