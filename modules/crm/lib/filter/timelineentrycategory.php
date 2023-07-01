<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;

Loc::loadMessages(__FILE__);

class TimelineEntryCategory
{
	public const
		ACTIVITY_REQUEST = 'activity-request',
		ACTIVITY_TASK = 'activity-task',
		ACTIVITY_CALL = 'activity-call',
		ACTIVITY_VISIT = 'activity-visit',
		ACTIVITY_MEETING = 'activity-meeting',
		ACTIVITY_EMAIL = 'activity-email',
		ACTIVITY_ZOOM = 'activity-zoom',
		ACTIVITY_CALL_TRACKER = 'activity-call-tracker';

	public const
		COMMENT = 'comment',
		DOCUMENT = 'document',
		SMS = 'sms',
		NOTIFICATION = 'notification',
		BIZ_PROCESS = 'biz-process',
		WEB_FORM = 'web-form',
		CHAT = 'chat',
		CREATION = 'creation',
		MODIFICATION = 'modification',
		CONVERSION = 'conversion',
		LINK = 'link',
		UNLINK = 'unlink',
		WAITING = 'waiting',
		APPLICATION = 'application',
		ORDER = 'order',
		ORDER_CHECK = 'check',
		LOG_MESSAGE = 'log-message';

	/**
	 * Get Category descriptions
	 * @return array
	 */
	public static function getDescriptions()
	{
		$result = [
			self::COMMENT => Loc::getMessage('CRM_TIMELINE_CATEGORY_COMMENT'),
			self::DOCUMENT => Loc::getMessage('CRM_TIMELINE_CATEGORY_DOCUMENT'),
			self::SMS => Loc::getMessage('CRM_TIMELINE_CATEGORY_SMS'),
			self::NOTIFICATION => Loc::getMessage('CRM_TIMELINE_CATEGORY_NOTIFICATION_2'),
			self::ACTIVITY_ZOOM => Loc::getMessage('CRM_TIMELINE_CATEGORY_ZOOM'),
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
			self::LINK => Loc::getMessage('CRM_TIMELINE_CATEGORY_LINK'),
			self::UNLINK => Loc::getMessage('CRM_TIMELINE_CATEGORY_UNLINK'),
			self::WAITING => Loc::getMessage('CRM_TIMELINE_CATEGORY_WAITING'),
			self::APPLICATION => Loc::getMessage('CRM_TIMELINE_CATEGORY_APPLICATION'),
			self::ORDER => Loc::getMessage('CRM_TIMELINE_CATEGORY_ORDER'),
			self::ORDER_CHECK => Loc::getMessage('CRM_TIMELINE_CATEGORY_ORDER_CHECK'),
			self::LOG_MESSAGE => Loc::getMessage('CRM_TIMELINE_CATEGORY_LOG_MESSAGE'),
		];

		if (Main\Config\Option::get('mobile', 'crm_call_tracker_enabled', 'N') === 'Y')
		{
			$result[self::ACTIVITY_CALL_TRACKER] = Loc::getMessage('CRM_TIMELINE_CATEGORY_CALL_TRACKER');
		}

		return $result;
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
							->logic(Main\ORM\Query\Filter\ConditionTree::LOGIC_OR)
							->where('ASSOCIATED_ENTITY_CLASS_NAME', Crm\Activity\Provider\Task::getId())
							->where('ASSOCIATED_ENTITY_CLASS_NAME', Crm\Activity\Provider\Tasks\Task::getId())
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
				elseif($entryCategoryID === self::ACTIVITY_ZOOM)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()
							->where('ASSOCIATED_ENTITY_CLASS_NAME', Crm\Activity\Provider\Zoom::getId())
					);
				}
				elseif($entryCategoryID === self::ACTIVITY_CALL_TRACKER)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()
							->where('ASSOCIATED_ENTITY_CLASS_NAME', Crm\Activity\Provider\CallTracker::getId())
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
				elseif($entryCategoryID === self::LINK)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()->where('TYPE_ID', Crm\Timeline\TimelineType::LINK)
					);
				}
				elseif($entryCategoryID === self::UNLINK)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()->where('TYPE_ID', Crm\Timeline\TimelineType::UNLINK)
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
				elseif($entryCategoryID === self::NOTIFICATION)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()
							->where('ASSOCIATED_ENTITY_CLASS_NAME', Crm\Activity\Provider\Notification::getId())
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
							->whereIn('ASSOCIATED_ENTITY_CLASS_NAME', [
								Crm\Activity\Provider\RestApp::getId(),
								Crm\Activity\Provider\ConfigurableRestApp::getId(),
							])
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
				elseif($entryCategoryID === self::LOG_MESSAGE)
				{
					$categoryFilter->where(
						Main\Entity\Query::filter()
							->where('TYPE_ID', Crm\Timeline\TimelineType::LOG_MESSAGE)
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