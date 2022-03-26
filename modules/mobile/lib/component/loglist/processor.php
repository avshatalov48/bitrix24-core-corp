<?php

namespace Bitrix\Mobile\Component\LogList;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Socialnetwork\ComponentHelper;
use Bitrix\Socialnetwork\LogRightTable;
use Bitrix\Socialnetwork\UserToGroupTable;

Loader::requireModule('socialnetwork');

class Processor extends \Bitrix\Socialnetwork\Component\LogListCommon\Processor
{
	protected $select = [];

	protected $eventsList = [];
	protected $showPinnedPanel = true;

	public function setSelect($value = []): void
	{
		$this->select = $value;
	}

	public function getSelect(): array
	{
		return $this->select;
	}

	public function setEventsList(array $value = [], $type = 'main')
	{
		$this->eventsList[$type] = $value;
	}

	public function setEventsListKey($key = '', array $value = [], $type = 'main')
	{
		if ($key == '')
		{
			return;
		}

		if (!isset($this->eventsList[$type]))
		{
			$this->eventsList[$type] = [];
		}

		$this->eventsList[$type][$key] = $value;
	}

	public function appendEventsList(array $value = [], $type = 'main')
	{
		if (!isset($this->eventsList[$type]))
		{
			$this->eventsList[$type] = [];
		}

		$this->eventsList[$type][] = $value;
	}

	public function unsetEventsListKey($key = '', $type = 'main'): void
	{
		if ($key === '')
		{
			return;
		}

		if (!isset($this->eventsList[$type]))
		{
			return;
		}

		unset($this->eventsList[$type][$key]);
	}

	public function getEventsList($type = 'main')
	{
		$result = [];

		if (!isset($this->eventsList[$type]))
		{
			return $result;
		}

		return $this->eventsList[$type];
	}

	public function processNavData(&$result)
	{
		$params = $this->getComponent()->arParams;
		$request = $this->getRequest();

		if ($params['LOG_ID'] <= 0)
		{
			if (!$result['AJAX_CALL'])
			{
				$this->setNavParams([
					'nTopCount' => $params['PAGE_SIZE']
				]);
				$result['PAGE_NUMBER'] = 1;
				$this->setFirstPage(true);
			}
			else
			{
				if ((int)$request->get('PAGEN_'.($GLOBALS['NavNum'] + 1)) > 0)
				{
					$result['PAGE_NUMBER'] = (int)$request->get('PAGEN_'.($GLOBALS['NavNum'] + 1));
				}

				$this->setNavParams([
					'nPageSize' => ((int)$request->get('pagesize') > 0 ? (int)$request->get('pagesize') : $params['PAGE_SIZE']),
					'bDescPageNumbering' => false,
					'bShowAll' => false,
					'iNavAddRecords' => 1,
					'bSkipPageReset' => true,
					'nRecordCount' => 1000000
				]);
			}
		}
	}

	public function processWorkgroupData(&$result)
	{
		$params = $this->getComponent()->arParams;

		if (
			$params['LOG_ID'] <= 0
			&& $params['NEW_LOG_ID'] <= 0
			&& !$result['AJAX_CALL']
			&& $params['GROUP_ID'] > 0
		)
		{
			$res = \CSocNetGroup::getList(
				[],
				[
					'ID' => (int)($params['GROUP_ID']),
					'CHECK_PERMISSIONS' => $result['currentUserId']
				],
				false,
				false,
				['ID', 'NAME', 'OPENED', 'IMAGE_ID']
			);
			if ($workgroupFields = $res->fetch())
			{
				$result['GROUP_NAME'] = $workgroupFields['NAME'];
				$result['GROUP_IMAGE'] = (is_array($file = \CFile::GetFileArray($workgroupFields['IMAGE_ID'])) ? $file['SRC'] : '');
				if (
					$workgroupFields['OPENED'] === 'Y'
					&& !\CSocNetUser::isCurrentUserModuleAdmin()
					&& !in_array(\CSocNetUserToGroup::getUserRole($result['currentUserId'], $workgroupFields['ID']), UserToGroupTable::getRolesMember(), true)
				)
				{
					$result['GROUP_READ_ONLY'] = 'Y';
				}
			}
		}
	}

	public function processFilterData(&$result)
	{
		$params = $this->getComponent()->arParams;

		if ($params['LOG_ID'] > 0)
		{
			$this->setFilterKey('ID', $params['LOG_ID']);
			$this->showPinnedPanel = false;
		}
		elseif (
			$result['AJAX_CALL']
			&& $params['NEW_LOG_ID'] > 0
		)
		{
			$this->setFilterKey('ID', $params['NEW_LOG_ID']);
			$this->showPinnedPanel = false;
		}
		else
		{
			if ($params['DESTINATION'] > 0)
			{
				$this->setFilterKey('LOG_RIGHTS', $params['DESTINATION']);
				$this->showPinnedPanel = false;
			}
			elseif ($params['GROUP_ID'] > 0)
			{
				$this->setFilterKey('LOG_RIGHTS', 'SG'.(int)$params['GROUP_ID']);

				if ($result['GROUP_READ_ONLY'] === 'Y')
				{
					$this->setFilterKey('LOG_RIGHTS_SG', 'OSG' . (int)$params['GROUP_ID'] . '_' . SONET_ROLES_AUTHORIZED);
				}

				$this->showPinnedPanel = false;
			}

			if ($params['FIND'] <> '')
			{
				$fullTextEnabled = \Bitrix\Socialnetwork\LogIndexTable::getEntity()->fullTextIndexEnabled('CONTENT');
				$operation = ($fullTextEnabled ? '*' : '*%');
				if (
					!$fullTextEnabled
					|| mb_strlen($params['FIND']) >= $result['ftMinTokenSize']
				)
				{
					$this->setFilterKey($operation.'CONTENT', \Bitrix\Socialnetwork\Item\LogIndex::prepareToken($params['FIND']));
				}
				$this->showPinnedPanel = false;
			}

			if ($params['IS_CRM'] !== 'Y')
			{
				$logRightsFilterValue = $this->getFilterKey('LOG_RIGHTS');

				$this->setFilterKey('!MODULE_ID', ( // can't use !@MODULE_ID because of null
					Option::get('crm', 'enable_livefeed_merge', 'N') === 'Y'
					|| (
						!empty($logRightsFilterValue)
						&& !is_array($logRightsFilterValue)
						&& preg_match('/^SG(\d+)$/', $logRightsFilterValue, $matches)
					)
						? ['crm']
						: ['crm', 'crm_shared']
				));
			}
		}

		if (
			$params['LOG_ID'] <= 0
			&& $params['NEW_LOG_ID'] <= 0
		)
		{
			if (isset($params['EXACT_EVENT_ID']))
			{
				$this->setFilterKey('EVENT_ID', [$params['EXACT_EVENT_ID']]);
			}
			elseif (is_array($params['EVENT_ID']))
			{
				$arraysToMerge = [];
				foreach ($params['EVENT_ID'] as $eventId)
				{
					$arraysToMerge[] = \CSocNetLogTools::findFullSetByEventID($eventId);
				}
				$eventIdFullSet = array_merge([], ...$arraysToMerge);

				$this->setFilterKey('EVENT_ID', array_unique($eventIdFullSet));
			}
			elseif ($params['EVENT_ID'])
			{
				$this->setFilterKey('EVENT_ID', \CSocNetLogTools::findFullSetByEventID($params['EVENT_ID']));
			}

			if ((int)$params['CREATED_BY_ID'] > 0) // from preset
			{
				$this->setFilterKey('USER_ID', $params['CREATED_BY_ID']);
			}
		}

		if (
			(
				$params['GROUP_ID'] > 0
				|| $params['USER_ID'] > 0
			)
			&& $this->getFilterKey('EVENT_ID') === false
		)
		{
			$eventIdFilterValue = [];

			$allowedEventIdList = \CSocNetAllowed::getAllowedLogEvents();
			foreach ($allowedEventIdList as $eventId => $eventData)
			{
				if (
					array_key_exists('HIDDEN', $eventData)
					&& $eventData['HIDDEN']
				)
				{
					continue;
				}

				$eventIdFilterValue[] = $eventId;
			}

			$featuresList = \CSocNetFeatures::GetActiveFeatures(
				($params['GROUP_ID'] > 0 ? SONET_ENTITY_GROUP : SONET_ENTITY_USER),
				($params["GROUP_ID"] > 0 ? $params['GROUP_ID'] : $params['USER_ID'])
			);
			$allowedFeaturesList = \CSocNetAllowed::getAllowedFeatures();
			foreach ($featuresList as $featureId)
			{
				if (
					array_key_exists($featureId, $allowedFeaturesList)
					&& array_key_exists('subscribe_events', $allowedFeaturesList[$featureId])
				)
				{
					foreach ($allowedFeaturesList[$featureId]['subscribe_events'] as $eventId => $eventData)
					{
						$eventIdFilterValue[] = $eventId;
					}
				}
			}
			$this->setFilterKey('EVENT_ID', $eventIdFilterValue);
		}

		if (
			$this->getFilterKey('EVENT_ID') === false
			|| (is_array($this->getFilterKey('EVENT_ID')) && empty($this->getFilterKey('EVENT_ID')))
		)
		{
			$this->unsetFilterKey('EVENT_ID');
		}

		$this->setFilterKey('SITE_ID', ($result['bExtranetSite'] ? SITE_ID : [SITE_ID, false]));
		$this->setFilterKey('<=LOG_DATE', 'NOW');

		if (
			$params['LOG_ID'] <= 0
			&& $params['NEW_LOG_ID'] <= 0
			&& in_array($params['FILTER'], ['favorites', 'my', 'important', 'work', 'bizproc', 'blog'])
		)
		{
			if ($params['FILTER'] === 'favorites')
			{
				$this->setFilterKey('>FAVORITES_USER_ID', 0);
			}
			elseif ($params['FILTER'] === 'my')
			{
				$this->setFilterKey('USER_ID', $result['currentUserId']);
			}
			elseif ($params['FILTER'] === 'important')
			{
				$this->setFilterKey('EVENT_ID', 'blog_post_important');
			}
			elseif ($params['FILTER'] === 'work')
			{
				$this->setFilterKey('EVENT_ID', ['tasks', 'timeman_entry', 'report']);
			}
			elseif ($params['FILTER'] === 'bizproc')
			{
				$this->setFilterKey('EVENT_ID', 'lists_new_element');
			}
			elseif ($params['FILTER'] === 'blog')
			{
				$blogPostLivefeedProvider = new \Bitrix\Socialnetwork\Livefeed\BlogPost;
				$this->setFilterKey('EVENT_ID', $blogPostLivefeedProvider->getEventId());
			}
		}

		if (!ComponentHelper::checkLivefeedTasksAllowed())
		{
			$eventIdFilter = $this->getFilterKey('EVENT_ID');
			$notEventIdFilter = $this->getFilterKey('!EVENT_ID');

			if (empty($notEventIdFilter))
			{
				$notEventIdFilter = [];
			}
			elseif (!is_array($notEventIdFilter))
			{
				$notEventIdFilter = [$notEventIdFilter];
			}

			if (empty($eventIdFilter))
			{
				$eventIdFilter = [];
			}
			elseif (!is_array($eventIdFilter))
			{
				$eventIdFilter = [$eventIdFilter];
			}

			if (ModuleManager::isModuleInstalled('tasks'))
			{
				$notEventIdFilter = array_merge($notEventIdFilter, ['tasks']);
				$eventIdFilter = array_filter($eventIdFilter, function ($eventId)
				{
					return ($eventId !== 'tasks');
				});
			}
			if (
				ModuleManager::isModuleInstalled('crm')
				&& Option::get('crm', 'enable_livefeed_merge', 'N') === 'Y'
			)
			{
				$notEventIdFilter = array_merge($notEventIdFilter, ['crm_activity_add']);
				$eventIdFilter = array_filter($eventIdFilter, static function ($eventId) {
					return ($eventId !== 'crm_activity_add');
				});
			}

			if (!empty($notEventIdFilter))
			{
				$this->setFilterKey('!EVENT_ID', $notEventIdFilter);
			}

			$this->setFilterKey('EVENT_ID', $eventIdFilter);
		}

		if ($result['currentUserId'] > 0)
		{
			$result['USE_PINNED'] = 'Y';

			if ($this->showPinnedPanel)
			{
				$this->setFilterKey('PINNED_USER_ID', 0);
				$result['SHOW_PINNED_PANEL'] = 'Y';
			}
		}
	}

	public function processLastTimestamp(&$result)
	{
		$params = $this->getComponent()->arParams;
		$request = $this->getRequest();

		if ($params['SET_LOG_COUNTER'] === 'Y')
		{
			$result['LAST_LOG_TS'] = \CUserCounter::getLastDate($result['currentUserId'], $result['COUNTER_TYPE']);

			if ($result['LAST_LOG_TS'] == 0)
			{
				$result['LAST_LOG_TS'] = 1;
			}
			else
			{
				//We substruct TimeZone offset in order to get server time
				//because of template compatibility
				$result['LAST_LOG_TS'] -= $result['TZ_OFFSET'];
			}
		}
		elseif (
			($result['COUNTER_TYPE'] == \CUserCounter::LIVEFEED_CODE)
			&& (
				$params['LOG_ID'] > 0
				|| $result['AJAX_CALL']
			)
			&& (int)$request->get('LAST_LOG_TS') > 0
		)
		{
			$result['LAST_LOG_TS'] = (int)$request->get('LAST_LOG_TS');
		}
	}

	public function processListParams(&$result)
	{
		$params = $this->getComponent()->arParams;

		$listParams = [
			'CHECK_RIGHTS' => 'Y',
			'CHECK_VIEW' => ($params['LOG_ID'] <= 0 ? 'Y' : 'N'),
			'USE_SUBSCRIBE' => 'N'
		];

		if ($params['LOG_ID'] > 0)
		{
			$listParams['CHECK_RIGHTS_OSG'] = ($this->checkAnyOpenedWorkgroupByLogId($params['LOG_ID']) ? 'Y' : 'N');
		}

		if ($result['bExtranetSite'])
		{
			$listParams['MY_GROUPS_ONLY'] = 'Y';
		}

		if ($params['USE_FOLLOW'] === 'Y')
		{
			$listParams['USE_FOLLOW'] = 'Y';
		}


		if ($result['USE_PINNED'] === 'Y')
		{
			$listParams['USE_PINNED'] = 'Y';
		}

		$this->setListParams($listParams);
	}

	private function checkAnyOpenedWorkgroupByLogId($logId = 0): bool
	{
		$result = false;

		$logId = (int)$logId;
		if ($logId <= 0)
		{
			return $result;
		}

		$workgroupIdList = [];

		$res = LogRightTable::getList([
			'filter' => [
				'LOG_ID' => $logId,
			],
			'select' => [ 'GROUP_CODE' ]
		]);
		while ($logRightFields = $res->fetch())
		{
			if (preg_match('/^SG(\d+)$/', $logRightFields['GROUP_CODE'], $matches))
			{
				$workgroupIdList[] = $matches[1];
			}
		}

		$workgroupIdList = array_unique($workgroupIdList);
		if (empty($workgroupIdList))
		{
			return $result;
		}

		return \Bitrix\Socialnetwork\Helper\Workgroup::checkAnyOpened($workgroupIdList);
	}

	public function processOrderData()
	{
		$params = $this->getComponent()->arParams;

		$order = [ 'LOG_UPDATE' => 'DESC' ];
		if ($params['USE_FOLLOW'] === 'Y')
		{
			$order = [ 'DATE_FOLLOW' => 'DESC' ];
		}

		$order['ID'] = 'DESC';

		$this->setOrder($order);
	}

	public function processSelectData(&$result)
	{
		$select = [
			'ID',
			'LOG_DATE', 'LOG_UPDATE', 'DATE_FOLLOW',
			'ENTITY_TYPE', 'ENTITY_ID', 'EVENT_ID', 'SOURCE_ID', 'USER_ID', 'COMMENTS_COUNT',
			'FOLLOW', 'FAVORITES_USER_ID',
			'RATING_TYPE_ID', 'RATING_ENTITY_ID'
		];

		if ($result['currentUserId'] > 0)
		{
			$select[] = 'PINNED_USER_ID';
		}

		$this->setSelect($select);
	}

	public function processNextPageSize(&$result)
	{
		$request = $this->getRequest();
		$params = $this->getComponent()->arParams;
		$filter = $this->getFilter();

		$result['NEXT_PAGE_SIZE'] = 0;

		if (
			count($result['arLogTmpID']) < $params['PAGE_SIZE']
			&& 	isset($filter['>=LOG_UPDATE'])
		)
		{
			$result['NEXT_PAGE_SIZE'] = count($result['arLogTmpID']);
		}
		elseif ((int)$request->get('pagesize') > 0)
		{
			$result['NEXT_PAGE_SIZE'] = (int)$request->get('pagesize');
		}
	}

	public function processEventsList(&$result, $type = 'main')
	{
		$params = $this->getComponent()->arParams;
		$logPageProcessorInstance = $this->getComponent()->getLogPageProcessorInstance();

		if (!$logPageProcessorInstance)
		{
			return;
		}

		$prevPageLogIdList = $logPageProcessorInstance->getPrevPageLogIdList();

		$eventsList = $this->getEventsList($type);

		foreach ($eventsList as $key => $eventFields)
		{
			if (
				!is_array($prevPageLogIdList)
				|| !in_array($eventFields['ID'], $prevPageLogIdList)
			)
			{
				$eventsList[$key]['EVENT_ID_FULLSET'] = \CSocNetLogTools::findFullSetEventIDByEventID($eventFields['EVENT_ID']);
			}
			else
			{
				unset($eventsList[$key]);
			}
		}

		if ($type === 'main')
		{
			$result['Events'] = $eventsList;
		}
		elseif ($type === 'pinned')
		{
			$result['pinnedEvents'] = $eventsList;
		}
	}

	public function processContentList(&$result)
	{
		foreach ($result["Events"] as $i => $eventFields)
		{
			$event = new \Bitrix\Main\Event(
				'mobile',
				'onGetContentId',
				[
					'logEventFields' => $eventFields
				]
			);
			$event->send();

			foreach ($event->getResults() as $eventResult)
			{
				if ($eventResult->getType() == \Bitrix\Main\EventResult::SUCCESS)
				{
					$eventParams = $eventResult->getParameters();

					if (
						is_array($eventParams)
						&& isset($eventParams['contentId'])
					)
					{
						$result['Events'][$i]['CONTENT_ID'] = $eventParams['contentId']['ENTITY_TYPE'].'-'.(int)$eventParams['contentId']['ENTITY_ID'];
					}
				}
			}
		}
	}

	public function processCrmActivities(&$result)
	{
		if (
			!empty($this->crmActivityIdList)
			&& Option::get('crm', 'enable_livefeed_merge', 'N') === 'Y'
			&& Loader::includeModule('crm')
		)
		{
			$result['CRM_ACTIVITY2TASK'] = [];

			$res = \CCrmActivity::getList(
				[],
				[
					'TYPE_ID' => \CCrmActivityType::Task,
					'ID' => $this->crmActivityIdList,
					'CHECK_PERMISSIONS' => 'N'
				],
				false,
				false,
				[ 'ID', 'ASSOCIATED_ENTITY_ID' ]
			);
			while ($crmActivityFields = $res->fetch())
			{
				$result['CRM_ACTIVITY2TASK'][$crmActivityFields['ID']] = $crmActivityFields['ASSOCIATED_ENTITY_ID'];
			}
		}
	}

	public function warmUpStaticCache($result)
	{
		$logEventsData = [];

		if (is_array($result['Events']))
		{
			foreach ($result['Events'] as $eventFields)
			{
				$logEventsData[(int)$eventFields['ID']] = $eventFields['EVENT_ID'];
			}
		}
		if (is_array($result['pinnedEvents']))
		{
			foreach ($result['pinnedEvents'] as $eventFields)
			{
				$logEventsData[(int)$eventFields['ID']] = $eventFields['EVENT_ID'];
			}
		}

		$forumPostLivefeedProvider = new \Bitrix\Socialnetwork\Livefeed\ForumPost();
		$forumPostLivefeedProvider->warmUpAuxCommentsStaticCache([
			'logEventsData' => $logEventsData
		]);
	}
}
