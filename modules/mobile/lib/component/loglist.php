<?php

namespace Bitrix\Mobile\Component;

use Bitrix\Main\Config\Option;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Socialnetwork\Component\LogList\Util;
use Bitrix\Mobile\Component\LogList\Param;
use Bitrix\Mobile\Component\LogList\Path;
use Bitrix\Mobile\Component\LogList\ParamPhotogallery;
use Bitrix\Mobile\Component\LogList\Processor;
use Bitrix\Mobile\Component\LogList\Page;
use Bitrix\Mobile\Component\LogList\Counter;
use Bitrix\Socialnetwork\ComponentHelper;
use Bitrix\Main\Application;
use Bitrix\Mobile\Livefeed;

Loader::requireModule('socialnetwork');

class LogList  extends \Bitrix\Socialnetwork\Component\LogListCommon
{
	protected $ajaxCall = false;
	protected $reloadCall = false;
	protected $crmActivityIdList = [];

	protected $paramsInstance = null;
	protected $pathInstance = null;
	protected $paramsPhotogalleryInstance = null;
	protected $processorInstance = null;
	protected $logPageProcessorInstance = null;
	protected $counterProcessorInstance = null;

	public $useLogin = false;

	protected function getParamsInstance(): Param
	{
		if ($this->paramsInstance === null)
		{
			$this->paramsInstance = new Param([
				'component' => $this,
				'ajaxCall' => $this->ajaxCall,
				'reloadCall' => $this->reloadCall,
				'request' => $this->getRequest()
			]);
		}

		return $this->paramsInstance;
	}

	public function getPathInstance(): Path
	{
		if ($this->pathInstance === null)
		{
			$this->pathInstance = new Path([
				'component' => $this,
				'request' => $this->getRequest()
			]);
		}

		return $this->pathInstance;
	}

	public function getParamsPhotogalleryInstance(): ParamPhotogallery
	{
		if ($this->paramsPhotogalleryInstance === null)
		{
			$this->paramsPhotogalleryInstance = new ParamPhotogallery([
				'component' => $this
			]);
		}

		return $this->paramsPhotogalleryInstance;
	}

	protected function getProcessorInstance(): Processor
	{
		if ($this->processorInstance === null)
		{
			$this->processorInstance = new Processor([
				'component' => $this,
				'request' => $this->getRequest()
			]);
		}

		return $this->processorInstance;
	}

	public function getLogPageProcessorInstance(): Page
	{
		if ($this->logPageProcessorInstance === null)
		{
			$this->logPageProcessorInstance = new Page([
				'component' => $this,
				'request' => $this->getRequest(),
				'processorInstance' => $this->getProcessorInstance()
			]);
		}

		return $this->logPageProcessorInstance;
	}

	public function getCounterProcessorInstance(): Counter
	{
		if ($this->counterProcessorInstance === null)
		{
			$this->counterProcessorInstance = new Counter([
				'component' => $this,
				'request' => $this->getRequest(),
//				'processorInstance' => $this->getProcessorInstance()
			]);
		}

		return $this->counterProcessorInstance;
	}

	public function onPrepareComponentParams($params)
	{
		$this->errorCollection = new ErrorCollection();

		$request = $this->getRequest();
		$paramsInstance = $this->getParamsInstance();
		$pathInstance = $this->getPathInstance();
		$paramsPhotogalleryInstance = $this->getParamsPhotogalleryInstance();

		if (
			!$request
			|| !$pathInstance
			|| !$paramsInstance
			|| !$paramsPhotogalleryInstance
		)
		{
			return $params;
		}

		$this->ajaxCall = (
			$request->get('AJAX_CALL') === 'Y'
			&& (

				$request->get('RELOAD') !== 'Y'
				|| $request->get('ACTION') === 'EDIT_POST'
			)
		);
		$this->reloadCall = ($request->get('RELOAD') === 'Y');

		$params['IS_CRM'] = (isset($params['IS_CRM']) && $params['IS_CRM'] === 'Y' ? 'Y' : 'N');

		if (
			!array_key_exists('USE_FOLLOW', $params)
			|| $params['USE_FOLLOW'] == ''
		)
		{
			$params['USE_FOLLOW'] = 'Y';
		}

		$params["RATING_TYPE"] = "like";

		$pathInstance->setPaths($params);

		$params['GROUP_ID'] = (int)$params['GROUP_ID']; // group page
		if ($params['GROUP_ID'] > 0)
		{
			$params['ENTITY_TYPE'] = SONET_ENTITY_GROUP;
		}
		$params['USER_ID'] = (int)($params['USER_ID'] ?? 0); // profile page
		$params['LOG_ID'] = (int)($params['LOG_ID'] ?? 0); // log entity page
		$params['NEW_LOG_ID'] = (int)($params['NEW_LOG_ID'] ?? 0);

		$params['FIND'] = ($request->get('FIND') ? trim($request->get('FIND')) : '');

		$params['SHOW_RATING'] = ($params['SHOW_RATING'] ?? 'Y');

		$paramsInstance->prepareNameTemplateParams($params);

		$params['SHOW_LOGIN'] = ($params['SHOW_LOGIN'] ?? 'Y');

		$this->useLogin = ($params['SHOW_LOGIN'] !== 'N');

		$paramsInstance->prepareAvatarParams($params);

		$paramsInstance->prepareCommentsParams($params);
		$paramsInstance->prepareDestinationParams($params);
		$paramsInstance->prepareDimensionsParams($params);

		if (
			$params['LOG_ID'] <= 0
			&& $request->get('ACTION') === 'CONVERT'
		)
		{
			$convertResult = \CSocNetLogTools::getDataFromRatingEntity($request->get('ENTITY_TYPE_ID'), $request->get('ENTITY_ID'), false);
			if (
				is_array($convertResult)
				&& $convertResult['LOG_ID'] > 0
			)
			{
				$params['LOG_ID'] = $convertResult['LOG_ID'];
			}
		}

		$paramsInstance->prepareCounterParams($params);
		$paramsInstance->preparePageParams($params);

		Util::checkEmptyParamInteger($params, 'PAGE_SIZE', 7);

		$paramsPhotogalleryInstance->preparePhotogalleryParams($params);
		$paramsInstance->prepareBehaviourParams($params);

		return $params;
	}

	protected function getBackgroundData(): array
	{
		return Livefeed\Helper::getBackgroundData();
	}

	protected function getBackgroundCommonData(): array
	{
		return [
			'url' => Application::getInstance()->getPersonalRoot().'/templates/mobile_app/images/lenta/background_common.png'
		];
	}

	protected function getMedalsData(): array
	{
		return Livefeed\Helper::getMedalsData();
	}

	protected function getImportantData(): array
	{
		$mobileSourceDir = Application::getInstance()->getPersonalRoot().'/templates/mobile_app/images/lenta/important';
		$params = $this->arParams;

		return [
			'nameTemplate' => $params['NAME_TEMPLATE'],
			'backgroundUrl' => $mobileSourceDir.'/background_mobile.svg'
		];
	}

	protected function getPostFormData(): array
	{
		$sourceDir = Application::getInstance()->getPersonalRoot().'/js/mobile/images/postform';

		return [
			'attachmentCloseIcon' => $sourceDir.'/icon_close.png',
			'attachmentArrowRightIcon' => $sourceDir.'/icon_arrow_right.svg',
			'attachmentFileIconFolder' => $sourceDir.'/file/',
			'menuMedalIcon' => $sourceDir.'/icon_menu_medal.png',
			'menuDeleteIcon' => $sourceDir.'/icon_menu_delete.png',
			'menuUpIcon' => $sourceDir.'/icon_menu_up.png',
			'menuUpIconDisabled' => $sourceDir.'/icon_menu_up_disabled.png',
			'menuDownIcon' => $sourceDir.'/icon_menu_down.png',
			'menuDownIconDisabled' => $sourceDir.'/icon_menu_down_disabled.png',
			'menuMultiCheckIcon' => $sourceDir.'/icon_menu_multicheck.png',
			'menuPlusIcon' => $sourceDir.'/icon_menu_plus.png',
			'keyboardEllipsisIcon' => $sourceDir.'/icon_keyboard_ellipsis.svg',
			'backgroundIcon' => $sourceDir.'/icon_background.png',
			'titleIcon' => $sourceDir.'/icon_title.png',
			'userAvatar' => $sourceDir.'/avatar/user.png',
		];
	}

	protected function prepareData(): array
	{
		global $USER;

		$request = $this->getRequest();
		$params = $this->arParams;
		$processorInstance = $this->getProcessorInstance();
		$logPageProcessorInstance = $this->getLogPageProcessorInstance();
		$counterProcessorInstance = $this->getCounterProcessorInstance();

		$result = [];

		if (
			!$request
			|| !$processorInstance
			|| !$logPageProcessorInstance
			|| !$counterProcessorInstance
		)
		{
			return $result;
		}

		$result['AJAX_CALL'] = $this->ajaxCall;
		$result['RELOAD'] = $this->reloadCall;
		$result['RELOAD_JSON'] = (
			$this->reloadCall
			&& $request->get('RELOAD_JSON') === 'Y'
		);
		$result['currentUserId'] = (int)$USER->getId();
		$result['serverTimestamp'] = time();

		$this->setExtranetData($result);

		$logPageProcessorInstance->preparePrevPageLogId();
		$processorInstance->getMicroblogUserId($result);

		$result['TZ_OFFSET'] = \CTimeZone::getOffset();

		if ($params['EMPTY_PAGE'] !== 'Y')
		{
			\CSocNetTools::initGlobalExtranetArrays();

			$config = Application::getConnection()->getConfiguration();
			$result['ftMinTokenSize'] = ($config['ft_min_token_size'] ?? \CSQLWhere::FT_MIN_TOKEN_SIZE);

			$result['Events'] = false;

			$processorInstance->processWorkgroupData($result);
			$processorInstance->processFilterData($result);
			$processorInstance->processNavData($result);
			$counterProcessorInstance->processCounterTypeData($result);
			$processorInstance->processLastTimestamp($result);
			$processorInstance->processListParams($result);
			$logPageProcessorInstance->getLogPageData($result);
			$processorInstance->processOrderData();
			$processorInstance->processSelectData($result);
			$this->getEntriesData($result);
			$processorInstance->processEventsList($result, 'main');
			$processorInstance->processEventsList($result, 'pinned');
			$processorInstance->warmUpStaticCache($result);
			$logPageProcessorInstance->deleteLogPageData($result);
			$processorInstance->processNextPageSize($result);
			$processorInstance->processContentList($result);
			$logPageProcessorInstance->setLogPageData($result);

			$processorInstance->getUnreadTaskCommentsIdList($result);

			$counterProcessorInstance->clearLogCounter($result);
			$this->setFollowData($result);
			$this->setExpertModeData($result);
		}
		else
		{
			$res = \CUser::getById($USER->getId());
			if ($currentUserFields = $res->fetch())
			{
				$result['EmptyComment'] = [
					'AVATAR_SRC' => \CSocNetLogTools::formatEvent_CreateAvatar($currentUserFields, $params, ''),
					'AUTHOR_NAME' => \CUser::formatName($params['NAME_TEMPLATE'], $currentUserFields, $this->useLogin)
				];
			}
		}

		$allowToAll = ComponentHelper::getAllowToAllDestination();

		$result['bDenyToAll'] = ($result['bExtranetSite'] || !$allowToAll);
		$result['bDefaultToAll'] = ($allowToAll && Option::get('socialnetwork', 'default_livefeed_toall', 'Y') === 'Y');

		if ($result['bExtranetSite'])
		{
			$result['arAvailableGroup'] = \CSocNetLogDestination::getSocnetGroup(
				[
					'features' => [
						"blog",
						[ 'premoderate_post', 'moderate_post', 'write_post', 'full_post' ]
					]
				]
			);
		}

		$result['bDiskInstalled'] = (
			Option::get('disk', 'successfully_converted', false)
			&& ModuleManager::isModuleInstalled('disk')
		);

		$result['bWebDavInstalled'] = ModuleManager::isModuleInstalled('webdav');

		$result['postFormUFCode'] = (
		$result['bDiskInstalled']
			|| ModuleManager::isModuleInstalled('webdav')
				? 'UF_BLOG_POST_FILE'
				: 'UF_BLOG_POST_DOC'
		);

		$processorInstance->processCrmActivities($result);

		$result['USE_FRAMECACHE'] = ($params['SET_LOG_COUNTER'] === 'Y');

		// knowledge for group
		$result['KNOWLEDGE_PATH'] = '';
		if (
			$params['GROUP_ID'] > 0
			&& Loader::includeModule('landing')
			&& \Bitrix\Landing\Connector\SocialNetwork::userInGroup($params['GROUP_ID'])
		)
		{
			$result['KNOWLEDGE_PATH'] = \Bitrix\Landing\Connector\SocialNetwork::getSocNetMenuUrl(
				$params['GROUP_ID'],
				false
			);
		}

		$result['BACKGROUND_IMAGES_DATA'] = $this->getBackgroundData();
		$result['BACKGROUND_COMMON'] = $this->getBackgroundCommonData();
		$result['MEDALS_LIST'] = $this->getMedalsData();
		$result['IMPORTANT_DATA'] = $this->getImportantData();
		$result['POST_FORM_DATA'] = $this->getPostFormData();

		return $result;
	}

	protected function getEntriesData(&$result): void
	{
		$params = $this->arParams;

		$result['arLogTmpID'] = [];

		$processorInstance = $this->getProcessorInstance();
		$logPageProcessorInstance = $this->getLogPageProcessorInstance();
		if (
			!$processorInstance
			|| !$logPageProcessorInstance
		)
		{
			return;
		}

		$queryResultData = $this->getEntryIdList($result);

		if (
			$queryResultData['countAll'] < (int)$params['PAGE_SIZE']
			&& !empty($processorInstance->getFilterKey('>=LOG_UPDATE'))
		)
		{
			$processorInstance->setEventsList([]);
			$logPageProcessorInstance->setDateLastPageStart(null);
			$processorInstance->unsetFilterKey('>=LOG_UPDATE');

			$this->getEntryIdList($result);
		}

		$this->getPinnedIdList($result);
	}

	protected function processEvent(&$result, &$cnt, array $eventFields = [], array $options = []): void
	{
		static $timemanInstalled = null;
		static $tasksInstalled = null;
		static $listsInstalled = null;

		if ($timemanInstalled === null)
		{
			$timemanInstalled = ModuleManager::isModuleInstalled('timeman');
		}
		if ($tasksInstalled === null)
		{
			$tasksInstalled = ModuleManager::isModuleInstalled('tasks');
		}
		if ($listsInstalled === null)
		{
			$listsInstalled = ModuleManager::isModuleInstalled('lists');
		}

		if (
			(
				!$tasksInstalled
				&& $eventFields['EVENT_ID'] === 'tasks'
			)
			|| (
				$eventFields['EVENT_ID'] === 'lists_new_element'
				&& !$listsInstalled
			)
			|| (
				in_array($eventFields['EVENT_ID'], [ 'timeman_entry', 'report' ], true)
				&& !$timemanInstalled
			)
		)
		{
			return;
		}

		$processorInstance = $this->getProcessorInstance();

		if (!$processorInstance)
		{
			return;
		}

		if (
			$eventFields['EVENT_ID'] === 'crm_activity_add'
			&& (int)$eventFields['ENTITY_ID'] > 0
		)
		{
			$this->crmActivityIdList[] = (int)$eventFields['ENTITY_ID'];
		}
		elseif ($eventFields['EVENT_ID'] === 'tasks')
		{
			$task2LogList = $this->getTask2LogListValue();
			$task2LogList[(int)$eventFields['SOURCE_ID']] = (int)$eventFields['ID'];
			$this->setTask2LogListValue($task2LogList);
			unset($task2LogList);
		}

		$cnt++;
		if (isset($options['type']))
		{
			if ($options['type'] === 'main')
			{
				$result['arLogTmpID'][] = $eventFields['ID'];
				$processorInstance->appendEventsList($eventFields);
			}
			elseif ($options['type'] === 'pinned')
			{
				$contentId = \Bitrix\Socialnetwork\Livefeed\Provider::getContentId($eventFields);

				if (!empty($contentId['ENTITY_TYPE']))
				{
					$postProvider = \Bitrix\Socialnetwork\Livefeed\Provider::init([
						'ENTITY_TYPE' => $contentId['ENTITY_TYPE'],
						'ENTITY_ID' => $contentId['ENTITY_ID'],
						'LOG_ID' => $eventFields['ID']
					]);
					if ($postProvider)
					{
						$result['pinnedIdList'][] = $eventFields['ID'];
						$eventFields['PINNED_PANEL_DATA'] = [
							'TITLE' => $postProvider->getPinnedTitle(),
							'DESCRIPTION' => $postProvider->getPinnedDescription()
						];
						$processorInstance->appendEventsList($eventFields, 'pinned');
					}
				}
			}
		}
	}

	protected function getEntryIdList(&$result): array
	{
		$params = $this->arParams;

		$returnResult = [
			'countAll' => 0
		];

		$processorInstance = $this->getProcessorInstance();
		if (!$processorInstance)
		{
			return $returnResult;
		}

		$res = \CSocNetLog::getList(
			$processorInstance->getOrder(),
			$processorInstance->getFilter(),
			false,
			$processorInstance->getNavParams(),
			$processorInstance->getSelect(),
			$processorInstance->getListParams()
		);

		if (
			$params['LOG_ID'] <= 0
			&& $params['NEW_LOG_ID'] <= 0
		)
		{
			if ($processorInstance->getFirstPage())
			{
				$result['PAGE_NAVNUM'] = $GLOBALS['NavNum'] + 1;
				$result['PAGE_NAVCOUNT'] = 1000000;
			}
			else
			{
				$result['PAGE_NUMBER'] = $res->NavPageNomer;
				$result['PAGE_NAVNUM'] = $res->NavNum;
				$result['PAGE_NAVCOUNT'] = $res->NavPageCount;
			}
		}

		$cnt = 0;
		while ($eventFields = $res->getNext())
		{
			$this->processEvent($result, $cnt, $eventFields, [
				'type' => 'main',
			]);
		}

		$returnResult['countAll'] = $res->selectedRowsCount();

		return $returnResult;
	}

	protected function getPinnedIdList(&$result): void
	{
		$result['pinnedEvents'] = [];
		$result['pinnedIdList'] = [];

		if ($result['USE_PINNED'] !== 'Y')
		{
			return;
		}

		$processorInstance = $this->getProcessorInstance();
		if (!$processorInstance)
		{
			return;
		}

		$logUpdateFilterValue = $processorInstance->getFilterKey('>=LOG_UPDATE');
		$processorInstance->unsetFilterKey('>=LOG_UPDATE');

		/* filter without >=LOG_UPDATE field */
		$filter = $processorInstance->getFilter();

		$processorInstance->setFilterKey('>=LOG_UPDATE', $logUpdateFilterValue);

		$filter['PINNED_USER_ID'] = $result['currentUserId'];

		$select = $processorInstance->getSelect();
		unset($select['TMP_ID'], $select['PINNED_USER_ID']);

		$res = \CSocNetLog::getList(
			[
				'PINNED_DATE' => 'DESC'
			],
			$filter,
			false,
			[
				'nTopCount' => 50
			],
			$select,
			[
				'CHECK_RIGHTS' => 'Y',
				'USE_PINNED' => 'Y',
				'USE_FOLLOW' => 'N'
			]
		);
		$cnt = 0;
		while ($eventFields = $res->getNext())
		{
			$this->processEvent($result, $cnt, $eventFields, [
				'type' => 'pinned'
			]);
		}
	}

	public function setFollowData(&$result): void
	{
		if ($result['currentUserId'] <= 0)
		{
			return;
		}

		$params = $this->arParams;

		if ($params['USE_FOLLOW'] === 'Y')
		{
			$result['FOLLOW_DEFAULT'] = Option::get('socialnetwork', 'follow_default_type', 'Y');

			$res = \CSocNetLogFollow::getList(
				[
					'USER_ID' => $result['currentUserId'],
					'CODE' => '**'
				],
				[ 'TYPE' ]
			);
			if ($followFields = $res->fetch())
			{
				$result['FOLLOW_DEFAULT'] = $followFields["TYPE"];
			}
		}
	}

	public function setExpertModeData(&$result): void
	{
		if ($result['currentUserId'] <= 0)
		{
			return;
		}

		$result['SHOW_EXPERT_MODE'] = (
			ComponentHelper::checkLivefeedTasksAllowed()
			&& ModuleManager::isModuleInstalled('tasks')
				? 'Y'
				: 'N'
		);

		if ($result['SHOW_EXPERT_MODE'] === 'Y')
		{
			$result['EXPERT_MODE'] = 'N';
			$res = \Bitrix\Socialnetwork\LogViewTable::getList([
				'order' => [],
				'filter' => [
					"USER_ID" => $result['currentUserId'],
					"=EVENT_ID" => 'tasks'
				],
				'select' => [ 'TYPE' ]
			]);
			if ($logViewFields = $res->fetch())
			{
				$result['EXPERT_MODE'] = ($logViewFields['TYPE'] === 'N' ? 'Y' : 'N');
			}
		}
	}

	public function setExtranetData(&$result): void
	{
		$result['bExtranetSite'] = (
			Loader::includeModule('extranet')
			&& \CExtranet::isExtranetSite()
		);

		$result['extranetSiteId'] = (
			ModuleManager::isModuleInstalled('extranet')
				? Option::get('extranet', 'extranet_site', false)
				: false
		);

		$result['extranetSiteDir'] = '';
		if ($result['extranetSiteId'])
		{
			$res = \Bitrix\Main\SiteTable::getList([
				'filter' => [ '=LID' => $result['extranetSiteId'] ],
				'select' => [ 'DIR' ]
			]);
			if ($siteFields = $res->fetch())
			{
				$result['extranetSiteDir'] = $siteFields['DIR'];
			}
		}
	}
}
