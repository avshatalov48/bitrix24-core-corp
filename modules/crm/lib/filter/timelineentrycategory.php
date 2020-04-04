<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;

Loc::loadMessages(__FILE__);

class TimelineEntryCategory
{
	const COMMENT = 'comment';
	const DOCUMENT = 'document';
	const SMS = 'sms';
	const BIZ_PROCESS = 'biz-process';
	const ACTIVITY_REQUEST = 'activity-request';
	const ACTIVITY_TASK = 'activity-task';
	const ACTIVITY_CALL = 'activity-call';
	const ACTIVITY_VISIT = 'activity-visit';
	const ACTIVITY_MEETING = 'activity-meeting';
	const ACTIVITY_EMAIL = 'activity-email';
	const WEB_FORM = 'web-form';
	const CHAT = 'chat';
	const CREATION = 'creation';
	const MODIFICATION = 'modification';
	const CONVERSION = 'conversion';
	const WAITING = 'waiting';
	const APPLICATION = 'application';
	const ORDER = 'order';
	const ORDER_CHECK = 'check';

	/**
	 * Get Category descriptions
	 * @return array
	 */
	public static function getDescriptions()
	{
		return [
			self::COMMENT => Loc::getMessage('CRM_TIMELINE_CATEGORY_COMMENT'),
			self::DOCUMENT => Loc::getMessage('CRM_TIMELINE_CATEGORY_DOCUMENT'),
			self::SMS => Loc::getMessage('CRM_TIMELINE_CATEGORY_SMS'),
			self::BIZ_PROCESS => Loc::getMessage('CRM_TIMELINE_CATEGORY_BIZ_PROCESS'),
			self::ACTIVITY_REQUEST => Loc::getMessage('CRM_TIMELINE_CATEGORY_ACTIVITY_REQUEST'),
			self::ACTIVITY_TASK => Loc::getMessage('CRM_TIMELINE_CATEGORY_ACTIVITY_TASK'),
			self::ACTIVITY_CALL => Loc::getMessage('CRM_TIMELINE_CATEGORY_ACTIVITY_CALL'),
			self::ACTIVITY_VISIT => Loc::getMessage('CRM_TIMELINE_CATEGORY_ACTIVITY_VISIT'),
			self::ACTIVITY_MEETING => Loc::getMessage('CRM_TIMELINE_CATEGORY_ACTIVITY_MEETING'),
			self::ACTIVITY_EMAIL => Loc::getMessage('CRM_TIMELINE_CATEGORY_ACTIVITY_EMAIL'),
			self::WEB_FORM => Loc::getMessage('CRM_TIMELINE_CATEGORY_WEB_FORM'),
			self::CHAT => Loc::getMessage('CRM_TIMELINE_CATEGORY_CHAT'),
			self::CREATION => Loc::getMessage('CRM_TIMELINE_CATEGORY_CREATION'),
			self::MODIFICATION => Loc::getMessage('CRM_TIMELINE_CATEGORY_MODIFICATION'),
			self::CONVERSION => Loc::getMessage('CRM_TIMELINE_CATEGORY_CONVERSION'),
			self::WAITING => Loc::getMessage('CRM_TIMELINE_CATEGORY_WAITING'),
			self::APPLICATION => Loc::getMessage('CRM_TIMELINE_CATEGORY_APPLICATION'),
			self::ORDER => Loc::getMessage('CRM_TIMELINE_CATEGORY_ORDER'),
			self::ORDER_CHECK => Loc::getMessage('CRM_TIMELINE_CATEGORY_ORDER_CHECK'),
		];
	}

	/**
	 * @param Main\Entity\Query $query
	 * @param array $filter
	 */
	public static function prepareQuery($query, $filter)
	{
		if(isset($filter['ENTRY_CATEGORY_ID']) && is_array($filter['ENTRY_CATEGORY_ID']))
		{
			$categoryFilter = Main\Entity\Query::filter();
			$categoryFilter->logic('or');

			foreach($filter['ENTRY_CATEGORY_ID'] as $entryCategoryID)
			{
				if($entryCategoryID === self::COMMENT)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()->where('TYPE_ID', Crm\Timeline\TimelineType::COMMENT)
					);
				}
				elseif($entryCategoryID === self::DOCUMENT)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()->where('TYPE_ID', Crm\Timeline\TimelineType::DOCUMENT)
					);
				}
				elseif($entryCategoryID === self::BIZ_PROCESS)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()
							->where('TYPE_ID', Crm\Timeline\TimelineType::BIZPROC)
					);
				}
				elseif($entryCategoryID === self::ACTIVITY_REQUEST)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()
							->where('ASSOCIATED_ENTITY_CLASS_NAME', Crm\Activity\Provider\Request::getId())
					);
				}
				elseif($entryCategoryID === self::ACTIVITY_TASK)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()
							->where('ASSOCIATED_ENTITY_CLASS_NAME', Crm\Activity\Provider\Task::getId())
					);
				}
				elseif($entryCategoryID === self::ACTIVITY_CALL)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()
							->where('ASSOCIATED_ENTITY_CLASS_NAME', Crm\Activity\Provider\Call::getId())
					);
				}
				elseif($entryCategoryID === self::ACTIVITY_VISIT)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()
							->where('ASSOCIATED_ENTITY_CLASS_NAME', Crm\Activity\Provider\Visit::getId())
					);
				}
				elseif($entryCategoryID === self::ACTIVITY_MEETING)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()
							->where('ASSOCIATED_ENTITY_CLASS_NAME', Crm\Activity\Provider\Meeting::getId())
					);
				}
				elseif($entryCategoryID === self::ACTIVITY_EMAIL)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()
							->where('ASSOCIATED_ENTITY_CLASS_NAME', Crm\Activity\Provider\Email::getId())
					);
				}
				elseif($entryCategoryID === self::CREATION)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()->where('TYPE_ID', Crm\Timeline\TimelineType::CREATION)
					);
				}
				elseif($entryCategoryID === self::MODIFICATION)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()->where('TYPE_ID', Crm\Timeline\TimelineType::MODIFICATION)
					);
				}
				elseif($entryCategoryID === self::CONVERSION)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()->where('TYPE_ID', Crm\Timeline\TimelineType::CONVERSION)
					);
				}
				elseif($entryCategoryID === self::WAITING)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()->where('TYPE_ID', Crm\Timeline\TimelineType::WAIT)
					);
				}
				elseif($entryCategoryID === self::SMS)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()
							->where('ASSOCIATED_ENTITY_CLASS_NAME', Crm\Activity\Provider\Sms::getId())
					);
				}
				elseif($entryCategoryID === self::WEB_FORM)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()
							->where('ASSOCIATED_ENTITY_CLASS_NAME', Crm\Activity\Provider\WebForm::getId())
					);
				}
				elseif($entryCategoryID === self::CHAT)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()
							->where('ASSOCIATED_ENTITY_CLASS_NAME', Crm\Activity\Provider\OpenLine::getId())
					);
				}
				elseif($entryCategoryID === self::APPLICATION)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()
							->where('ASSOCIATED_ENTITY_CLASS_NAME', Crm\Activity\Provider\RestApp::getId())
					);
				}
				elseif($entryCategoryID === self::ORDER)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()
							->where('TYPE_ID', Crm\Timeline\TimelineType::ORDER)
					);
				}
				elseif($entryCategoryID === self::ORDER_CHECK)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()
							->where('TYPE_ID', Crm\Timeline\TimelineType::ORDER_CHECK)
					);
				}
			}

			if($categoryFilter->hasConditions())
			{
				$query->where($categoryFilter);
			}
		}
	}
}