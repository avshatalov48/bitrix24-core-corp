<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponent $this */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Engine;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Socialnetwork\ComponentHelper;

if (!Loader::includeModule('socialnetwork'))
{
	ShowError(Loc::getMessage('MOBILE_LIVEFEED_SOCIALNETWORK_MODULE_NOT_INSTALLED'));
	return;
}

global $USER;

if (!$USER->isAuthorized())
{
	ShowError(Loc::getMessage('MOBILE_LIVEFEED_NOT_AUTHORIZED'));
	return;
}

final class MobileLivefeed extends \CBitrixComponent implements Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
	/** @var  ErrorCollection */
	protected $errorCollection;

	protected $crmActivityIdList = [];
	protected $useLogin = true;
	protected $ajaxCall = false;
	protected $reloadCall = false;
	protected $prevPageLogIdList = [];
	protected $bFirstPage = false;
	protected $bNeedSetLogPage = false;

	public function configureActions()
	{
		return [];
	}

	protected function listKeysSignedParameters()
	{
		return [
			'GROUP_ID',
			'PATH_TO_LOG_ENTRY', 'PATH_TO_LOG_ENTRY_EMPTY',
			'PATH_TO_USER', 'PATH_TO_GROUP',
			'PATH_TO_CRMCOMPANY', 'PATH_TO_CRMCONTACT', 'PATH_TO_CRMLEAD', 'PATH_TO_CRMDEAL',
			'PATH_TO_TASKS_SNM_ROUTER',
			'SET_LOG_CACHE',
			'IMAGE_MAX_WIDTH',
			'DATE_TIME_FORMAT',
			'CHECK_PERMISSIONS_DEST'
		];
	}

	public function onPrepareComponentParams($params)
	{
		$this->errorCollection = new ErrorCollection();

		$request = \Bitrix\Main\Context::getCurrent()->getRequest();

		$this->ajaxCall = (
			$request->get('AJAX_CALL') == 'Y'
			&& (

				$request->get('RELOAD') != 'Y'
				|| $request->get('ACTION') == 'EDIT_POST'
			)
		);
		$this->reloadCall = ($request->get('RELOAD') == 'Y');

		$params['IS_CRM'] = (isset($params['IS_CRM']) && $params['IS_CRM'] == 'Y' ? 'Y' : 'N');

		if (
			!array_key_exists('USE_FOLLOW', $params)
			|| $params['USE_FOLLOW'] == ''
		)
		{
			$params['USE_FOLLOW'] = 'Y';
		}

		$params["RATING_TYPE"] = "like";

		$params['PATH_TO_USER'] = trim($params['PATH_TO_USER']);
		$params['PATH_TO_GROUP'] = trim($params['PATH_TO_GROUP']);
		$params['PATH_TO_SMILE'] = trim($params['PATH_TO_SMILE']);
		if ($params['PATH_TO_SMILE'] == '')
		{
			$params['PATH_TO_SMILE'] = '/bitrix/images/socialnetwork/smile/';
		}

//		$params['PATH_TO_LOG_ENTRY_EMPTY'] .= (mb_strpos($params['PATH_TO_LOG_ENTRY_EMPTY'], '?') !== false ? '&' : '?').'version='.(defined('MOBILE_MODULE_VERSION') ? MOBILE_MODULE_VERSION : 'default');

		$params['GROUP_ID'] = intval($params['GROUP_ID']); // group page
		$params['USER_ID'] = intval($params['USER_ID']); // profile page
		$params['LOG_ID'] = intval($params['LOG_ID']); // log entity pag

		$params['FIND'] = ($request->get('FIND') ? trim($request->get('FIND')) : '');

		$params['NAME_TEMPLATE'] = $params['NAME_TEMPLATE'] ? $params['NAME_TEMPLATE'] : \CSite::getNameFormat();
		$params['SHOW_RATING'] = (isset($params['SHOW_RATING']) ? $params['SHOW_RATING'] : 'Y');

		$params['NAME_TEMPLATE_WO_NOBR'] = str_replace(
			[ '#NOBR#', '#/NOBR#' ],
			'',
			$params['NAME_TEMPLATE']
		);
		$params['NAME_TEMPLATE'] = $params['NAME_TEMPLATE_WO_NOBR'];
		if (!isset($params['SHOW_LOGIN']))
		{
			$params['SHOW_LOGIN'] = $params['SHOW_LOGIN'] != 'N' ? 'Y' : 'N';
		}

		$this->useLogin = ($params['SHOW_LOGIN'] != 'N');

		$params['AVATAR_SIZE'] = (isset($params['AVATAR_SIZE']) ? intval($params['AVATAR_SIZE']) : 100);
		$params['AVATAR_SIZE_COMMENT'] = (isset($params['AVATAR_SIZE_COMMENT']) ? intval($params['AVATAR_SIZE_COMMENT']) : 100);

		$params['EMPTY_PAGE'] = ($request->get('empty') == 'Y' ? 'Y' : 'N');

		$params['COMMENTS_IN_EVENT'] = (isset($params['COMMENTS_IN_EVENT']) && intval($params['COMMENTS_IN_EVENT']) > 0 ? $params['COMMENTS_IN_EVENT'] : 3);
		$params['DESTINATION_LIMIT'] = (isset($params['DESTINATION_LIMIT']) ? intval($params['DESTINATION_LIMIT']) : 100);
		$params['DESTINATION_LIMIT_SHOW'] = (isset($params['DESTINATION_LIMIT_SHOW']) ? intval($params['DESTINATION_LIMIT_SHOW']) : 3);

		if (Loader::includeModule('mobileapp'))
		{
			$minDimension = min(
				[
					intval(\CMobile::getInstance()->getDevicewidth()),
					intval(\CMobile::getInstance()->getDeviceheight())
				]
			);

			if ($minDimension < 650)
			{
				$minDimension = 650;
			}
			elseif ($minDimension < 1300)
			{
				$minDimension = 1300;
			}
			else
			{
				$minDimension = 2050;
			}

			$params['IMAGE_MAX_WIDTH'] = intval(($minDimension - 100) / 2);
		}

		if (
			$request->get('ACTION') == 'CONVERT'
			&& $params['LOG_ID'] <= 0
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

		$params['SET_LOG_CACHE'] = (
			isset($params['SET_LOG_CACHE'])
			&& $params['LOG_ID'] <= 0
			&& !$this->ajaxCall
				? $params['SET_LOG_CACHE']
				: 'N'
		);

		$params['SET_LOG_COUNTER'] = (
			$params['SET_LOG_CACHE'] == 'Y'
			&& (
				(
					!$this->ajaxCall
					&& \Bitrix\Main\Page\Frame::isAjaxRequest()
				)
				|| $this->reloadCall
			)
				? 'Y'
				: 'N'
		);

		$params['SET_LOG_PAGE_CACHE'] = ($params['LOG_ID'] <= 0 ? 'Y' : 'N');
		$params['PAGE_SIZE'] = (intval($params['PAGE_SIZE']) > 0 ? $params['PAGE_SIZE'] : 7);

		if($params['PATH_TO_USER_BLOG_POST'] <> '')
		{
			$params["PATH_TO_USER_MICROBLOG_POST"] = $params['PATH_TO_USER_BLOG_POST'];
		}

		if (intval($params['PHOTO_COUNT']) <= 0)
		{
			$params['PHOTO_COUNT'] = 5;
		}

		if (intval($params['PHOTO_THUMBNAIL_SIZE']) <= 0)
		{
			$params['PHOTO_THUMBNAIL_SIZE'] = 76;
		}

		if ($params['EMPTY_PAGE'] != 'Y')
		{
			if (
				$params['IS_CRM'] == "Y"
				&& ($params["CRM_ENTITY_TYPE"] <> '')
			)
			{
				$params['SET_LOG_COUNTER'] = $params['SET_LOG_PAGE_CACHE'] = 'N';
			}

			if (
				$params['LOG_ID'] <= 0
				&& intval($params['NEW_LOG_ID']) <= 0
				&& in_array($params['FILTER'], [ 'favorites', 'my', 'important', 'work', 'bizproc', 'blog' ])
			)
			{
				$params['SET_LOG_COUNTER'] = 'N';
				$params['SET_LOG_PAGE_CACHE'] = 'N';
				$params['USE_FOLLOW'] = 'N';
			}

			if (intval($params['GROUP_ID']) > 0)
			{
				$params['SET_LOG_PAGE_CACHE'] = 'Y';
				$params['USE_FOLLOW'] = 'N';
				$params['SET_LOG_COUNTER'] = 'N';
			}
			elseif (
				$params['IS_CRM'] == 'Y'
				&& $params['SET_LOG_COUNTER'] != 'N'
			)
			{
			}
			elseif ($params['FIND'] <> '')
			{
				$params['SET_LOG_COUNTER'] = 'N';
				$params['SET_LOG_PAGE_CACHE'] = 'N';
				$params['USE_FOLLOW'] = 'N';
			}

			if (intval($request->get('pagesize')) > 0)
			{
				$params['SET_LOG_PAGE_CACHE'] = "N";
			}
		}

		return $params;
	}

	public function getEntryLogIdAction($params)
	{
		if (!\Bitrix\Main\Loader::includeModule('socialnetwork'))
		{
			$this->errorCollection->add([new Error(Loc::getMessage('MOBILE_LIVEFEED_SOCIALNETWORK_MODULE_NOT_INSTALLED', 'MOBILE_LIVEFEED_SOCIALNETWORK_MODULE_NOT_INSTALLED'))]);
			return;
		}

		$entityType = (isset($params['entityType']) ? $params['entityType'] : '');
		$entityId = (isset($params['entityId']) ? intval($params['entityId']) : 0);

		if (
			$entityType == ''
			|| $entityId <= 0
		)
		{
			$this->errorCollection->add([new Error(Loc::getMessage('MOBILE_LIVEFEED_WRONG_ENTITY_DATA', 'MOBILE_LIVEFEED_WRONG_ENTITY_DATA'))]);
			return;
		}

		$provider = \Bitrix\Socialnetwork\Livefeed\Provider::init(array(
			'ENTITY_TYPE' => $entityType,
			'ENTITY_ID' => $entityId,
		));
		if ($provider)
		{
			$logId = $provider->getLogId();
		}

		return [
			'logId' => $logId
		];
	}

	public function getEntryContentAction($params)
	{
		if (!\Bitrix\Main\Loader::includeModule('socialnetwork'))
		{
			$this->errorCollection->add([new Error(Loc::getMessage('MOBILE_LIVEFEED_SOCIALNETWORK_MODULE_NOT_INSTALLED', 'MOBILE_LIVEFEED_SOCIALNETWORK_MODULE_NOT_INSTALLED'))]);
			return;
		}

		$logId = (isset($params['logId']) ? intval($params['logId']) : 0);
		$entityType = (isset($params['entityType']) ? $params['entityType'] : '');
		$entityId = (isset($params['entityId']) ? intval($params['entityId']) : 0);
		$siteTemplateId = (isset($params['siteTemplateId']) ? $params['siteTemplateId'] : 'mobile_app');

		if (
			$logId <= 0
			&& $entityType <> ''
			&& $entityId > 0
		)
		{
			$provider = \Bitrix\Socialnetwork\Livefeed\Provider::init(array(
				'ENTITY_TYPE' => $entityType,
				'ENTITY_ID' => $entityId,
			));
			if ($provider)
			{
				$logId = $provider->getLogId();
			}
		}

		if ($logId <= 0)
		{
			$this->errorCollection->add([new Error(Loc::getMessage('MOBILE_LIVEFEED_WRONG_LOG_ID'), 'MOBILE_LIVEFEED_WRONG_LOG_ID')]);
			return;
		}

		define('BX_MOBILE', true);

		$this->arParams['LOG_ID'] = $logId;
		$this->arParams['SITE_TEMPLATE_ID'] = $siteTemplateId;
		$this->arParams['TARGET'] = 'ENTRIES_ONLY';
		$this->arParams['IS_LIST'] = 'Y';

		return new Engine\Response\Component($this->getName(), '', $this->arParams);
	}

	public function executeComponent()
	{
		global $APPLICATION, $USER;

		\CPageOption::setOptionString('main', 'nav_page_in_session', 'N');

		$request = \Bitrix\Main\Context::getCurrent()->getRequest();

		$this->arResult['AJAX_CALL'] = $this->ajaxCall;
		$this->arResult['RELOAD'] = $this->reloadCall;
		$this->arResult['RELOAD_JSON'] = (
			$this->reloadCall
			&& $request->get('RELOAD_JSON') == 'Y'
		);

		if ($request->get('pplogid') <> '')
		{
			$this->prevPageLogIdList = explode('|', trim($request->get('pplogid')));
			if (is_array($this->prevPageLogIdList))
			{
				foreach($this->prevPageLogIdList as $key => $val)
				{
					preg_match('/^(\d+)$/', $val, $matches);
					if (count($matches) <= 0)
					{
						unset($this->prevPageLogIdList[$key]);
					}
				}
				$this->prevPageLogIdList = array_unique($this->prevPageLogIdList);
			}
		}

		$APPLICATION->setPageProperty('BodyClass', ($this->arParams['LOG_ID'] > 0 || $this->arParams['EMPTY_PAGE'] == 'Y' ? 'post-card' : 'lenta-page'));

		if(
			(
				$this->arParams['GROUP_ID'] <= 0
				&& \CSocNetFeatures::isActiveFeature(SONET_ENTITY_USER, $USER->getID(), 'blog')
			)
			|| (
				$this->arParams['GROUP_ID'] > 0
				&& \CSocNetFeatures::isActiveFeature(SONET_ENTITY_GROUP, $this->arParams['GROUP_ID'], 'blog')
			)
		)
		{
			$this->arResult['MICROBLOG_USER_ID'] = $USER->getID();
		}

		$this->arResult['TZ_OFFSET'] = \CTimeZone::getOffset();
		$this->arResult['bExtranetSite'] = (Loader::includeModule('extranet') && \CExtranet::isExtranetSite());

		if ($this->arParams['EMPTY_PAGE'] != 'Y')
		{
			\CSocNetTools::initGlobalExtranetArrays();

			$config = \Bitrix\Main\Application::getConnection()->getConfiguration();
			$this->arResult['ftMinTokenSize'] = (isset($config['ft_min_token_size']) ? $config['ft_min_token_size'] : \CSQLWhere::FT_MIN_TOKEN_SIZE);

			$this->arResult['Events'] = false;

			$arFilter = [];

			if ($this->arParams['LOG_ID'] > 0)
			{
				$arFilter['ID'] = $this->arParams['LOG_ID'];
			}
			elseif(
				$this->arResult['AJAX_CALL']
				&& intval($this->arParams['NEW_LOG_ID']) > 0
			)
			{
				$arFilter['ID'] = $this->arParams['NEW_LOG_ID'];
			}
			else
			{
				if ($this->arParams['DESTINATION'] > 0)
				{
					$arFilter['LOG_RIGHTS'] = $this->arParams['DESTINATION'];
				}
				elseif ($this->arParams['GROUP_ID'] > 0)
				{
					$arFilter['LOG_RIGHTS'] = 'SG'.intval($this->arParams['GROUP_ID']);
					$arFilter['LOG_RIGHTS_SG'] = 'OSG'.intval($this->arParams['GROUP_ID']).'_'.SONET_ROLES_AUTHORIZED;

					$res = \CSocNetGroup::getList(
						[],
						[
							'ID' => intval($this->arParams['GROUP_ID']),
							'CHECK_PERMISSIONS' => $USER->getId()
						],
						false,
						false,
						[ 'ID', 'NAME', 'OPENED' ]
					);
					if ($workgroupFields = $res->Fetch())
					{
						$this->arResult['GROUP_NAME'] = $workgroupFields['NAME'];
						if (
							$workgroupFields['OPENED'] == 'Y'
							&& !\CSocNetUser::isCurrentUserModuleAdmin()
							&& !in_array(\CSocNetUserToGroup::getUserRole($USER->getId(), $workgroupFields['ID']), [ SONET_ROLES_OWNER, SONET_ROLES_MODERATOR, SONET_ROLES_USER ])
						)
						{
							$this->arResult['GROUP_READ_ONLY'] = 'Y';
						}
					}
				}

				if ($this->arParams['FIND'] <> '')
				{
					$fullTextEnabled = \Bitrix\Socialnetwork\LogIndexTable::getEntity()->fullTextIndexEnabled('CONTENT');
					$operation = ($fullTextEnabled ? '*' : '*%');
					if (
						!$fullTextEnabled
						|| mb_strlen($this->arParams['FIND']) >= $this->arResult['ftMinTokenSize']
					)
					{
						$arFilter[$operation.'CONTENT'] = \Bitrix\Socialnetwork\Item\LogIndex::prepareToken($this->arParams['FIND']);
					}
				}

				if ($this->arParams['IS_CRM'] != 'Y')
				{
					$arFilter['!MODULE_ID'] = ( // can't use !@MODULE_ID because of null
						Option::get('crm', 'enable_livefeed_merge', 'N') == 'Y'
						|| (
							!empty($arFilter['LOG_RIGHTS'])
							&& !is_array($arFilter['LOG_RIGHTS'])
							&& preg_match('/^SG(\d+)$/', $arFilter['LOG_RIGHTS'], $matches)
						)
							? [ 'crm' ]
							: [ 'crm', 'crm_shared' ]
					);
				}
			}

			if (
				$this->arParams['LOG_ID'] <= 0
				&& intval($this->arParams['NEW_LOG_ID']) <= 0
			)
			{
				if (isset($this->arParams['EXACT_EVENT_ID']))
				{
					$arFilter['EVENT_ID'] = array($this->arParams['EXACT_EVENT_ID']);
				}
				elseif (is_array($this->arParams['EVENT_ID']))
				{
					$eventIdFullSet = [];
					foreach($this->arParams['EVENT_ID'] as $eventId)
					{
						$eventIdFullSet = array_merge($eventIdFullSet, \CSocNetLogTools::findFullSetByEventID($eventId));
					}
					$arFilter['EVENT_ID'] = array_unique($eventIdFullSet);
				}
				elseif ($this->arParams['EVENT_ID'])
				{
					$arFilter['EVENT_ID'] = \CSocNetLogTools::findFullSetByEventID($this->arParams['EVENT_ID']);
				}

				if (intval($this->arParams['CREATED_BY_ID']) > 0) // from preset
				{
					$arFilter['USER_ID'] = $this->arParams['CREATED_BY_ID'];
				}
			}

			if (
				(
					$this->arParams['GROUP_ID'] > 0
					|| $this->arParams['USER_ID'] > 0
				)
				&& !array_key_exists('EVENT_ID', $arFilter)
			)
			{
				$arFilter['EVENT_ID'] = [];
				$allowedEventIdList = \CSocNetAllowed::getAllowedLogEvents();
				foreach($allowedEventIdList as $eventId => $eventData)
				{
					if (
						array_key_exists('HIDDEN', $eventData)
						&& $eventData['HIDDEN']
					)
					{
						continue;
					}

					$arFilter['EVENT_ID'][] = $eventId;
				}

				$featuresList = CSocNetFeatures::GetActiveFeatures(
					($this->arParams['GROUP_ID'] > 0 ? SONET_ENTITY_GROUP : SONET_ENTITY_GROUP),
					($this->arParams["GROUP_ID"] > 0 ? $this->arParams['GROUP_ID'] : $this->arParams['USER_ID'])
				);
				foreach($featuresList as $featureId)
				{
					$allowedFeaturesList = \CSocNetAllowed::getAllowedFeatures();
					if (
						array_key_exists($featureId, $allowedFeaturesList)
						&& array_key_exists('subscribe_events', $allowedFeaturesList[$featureId])
					)
					{
						foreach ($allowedFeaturesList[$featureId]['subscribe_events'] as $eventId => $eventData)
						{
							$arFilter['EVENT_ID'][] = $eventId;
						}
					}
				}
			}

			if (
				!$arFilter['EVENT_ID']
				|| (is_array($arFilter['EVENT_ID']) && empty($arFilter['EVENT_ID']))
			)
			{
				unset($arFilter['EVENT_ID']);
			}

			$arFilter['SITE_ID'] = ($this->arResult['bExtranetSite'] ? SITE_ID : [ SITE_ID, false ]);
			$arFilter['<=LOG_DATE'] = 'NOW';

			if ($this->arParams['LOG_ID'] <= 0)
			{
				if (!$this->arResult['AJAX_CALL'])
				{
					$arNavStartParams = array(
						'nTopCount' => $this->arParams['PAGE_SIZE']
					);
					$this->arResult['PAGE_NUMBER'] = 1;
					$this->bFirstPage = true;
				}
				else
				{
					if (intval($request->get('PAGEN_'.($GLOBALS['NavNum'] + 1))) > 0)
					{
						$this->arResult['PAGE_NUMBER'] = intval($request->get('PAGEN_'.($GLOBALS['NavNum'] + 1)));
					}

					$arNavStartParams = [
						'nPageSize' => (intval($request->get('pagesize')) > 0 ? intval($request->get('pagesize')) : $this->arParams['PAGE_SIZE']),
						'bDescPageNumbering' => false,
						'bShowAll' => false,
						'iNavAddRecords' => 1,
						'bSkipPageReset' => true,
						'nRecordCount' => 1000000
					];
				}
			}

			if (
				$this->arParams['LOG_ID'] <= 0
				&& intval($this->arParams['NEW_LOG_ID']) <= 0
				&& in_array($this->arParams['FILTER'], [ 'favorites', 'my', 'important', 'work', 'bizproc', 'blog' ])
			)
			{
				if ($this->arParams['FILTER'] == 'favorites')
				{
					$arFilter['>FAVORITES_USER_ID'] = 0;
				}
				elseif ($this->arParams['FILTER'] == 'my')
				{
					$arFilter['USER_ID'] = $USER->getID();
				}
				elseif ($this->arParams['FILTER'] == 'important')
				{
					$arFilter['EVENT_ID'] = 'blog_post_important';
				}
				elseif ($this->arParams['FILTER'] == 'work')
				{
					$arFilter['EVENT_ID'] = [ 'tasks', 'timeman_entry', 'report' ];
				}
				elseif ($this->arParams['FILTER'] == 'bizproc')
				{
					$arFilter['EVENT_ID'] = 'lists_new_element';
				}
				elseif ($this->arParams['FILTER'] == 'blog')
				{
					$blogPostLivefeedProvider = new \Bitrix\Socialnetwork\Livefeed\BlogPost;
					$arFilter['EVENT_ID'] = $blogPostLivefeedProvider->getEventId();
				}
			}

			if (!ComponentHelper::checkLivefeedTasksAllowed())
			{
				$eventIdFilter = $arFilter['EVENT_ID'];
				$notEventIdFilter = $arFilter['!EVENT_ID'];

				if (empty($notEventIdFilter))
				{
					$notEventIdFilter = [];
				}
				elseif(!is_array($notEventIdFilter))
				{
					$notEventIdFilter = [ $notEventIdFilter ];
				}

				if (empty($eventIdFilter))
				{
					$eventIdFilter = [];
				}
				elseif(!is_array($eventIdFilter))
				{
					$eventIdFilter = [ $eventIdFilter ];
				}

				if (ModuleManager::isModuleInstalled('tasks'))
				{
					$notEventIdFilter = array_merge($notEventIdFilter, [ 'tasks' ]);
					$eventIdFilter = array_filter($eventIdFilter, function($eventId) { return ($eventId != 'tasks'); });
				}
				if (
					ModuleManager::isModuleInstalled('crm')
					&& Option::get('crm', 'enable_livefeed_merge', 'N') == 'Y'
				)
				{
					$notEventIdFilter = array_merge($notEventIdFilter, [ 'crm_activity_add' ]);
					$eventIdFilter = array_filter($eventIdFilter, function($eventId) { return ($eventId != 'crm_activity_add'); });
				}

				if (!empty($notEventIdFilter))
				{
					$arFilter['!EVENT_ID'] = $notEventIdFilter;
				}

				$arFilter['EVENT_ID'] = $eventIdFilter;
			}

			if (intval($this->arParams['GROUP_ID']) > 0)
			{
				$this->arResult['COUNTER_TYPE'] = 'SG'.intval($this->arParams['GROUP_ID']);
			}
			elseif(
				$this->arParams['IS_CRM'] == 'Y'
				&& $this->arParams['SET_LOG_COUNTER'] != 'N'
			)
			{
				$this->arResult['COUNTER_TYPE'] = 'CRM_**';
			}
			elseif ($this->arParams['FIND'] <> '')
			{
			}
			else
			{
				$this->arResult['COUNTER_TYPE'] = \CUserCounter::LIVEFEED_CODE;
			}

			if ($this->arParams['SET_LOG_COUNTER'] == 'Y')
			{
				$this->arResult['LAST_LOG_TS'] = \CUserCounter::getLastDate($USER->GetID(), $this->arResult['COUNTER_TYPE']);

				if ($this->arResult['LAST_LOG_TS'] == 0)
				{
					$this->arResult['LAST_LOG_TS'] = 1;
				}
				else
				{
					//We substruct TimeZone offset in order to get server time
					//because of template compatibility
					$this->arResult['LAST_LOG_TS'] -= $this->arResult['TZ_OFFSET'];
				}
			}
			elseif (
				($this->arResult['COUNTER_TYPE'] == \CUserCounter::LIVEFEED_CODE)
				&& (
					$this->arParams['LOG_ID'] > 0
					|| $this->arResult['AJAX_CALL']
				)
				&& intval($request->get('LAST_LOG_TS')) > 0
			)
			{
				$this->arResult['LAST_LOG_TS'] = intval($request->get('LAST_LOG_TS'));
			}

			$arListParams = [
				'CHECK_RIGHTS' => 'Y',
				'CHECK_VIEW' => ($this->arParams['LOG_ID'] <= 0 ? 'Y' : 'N'),
				'USE_SUBSCRIBE' => 'N'
			];

			if ($this->arParams['LOG_ID'] > 0)
			{
				$arListParams['CHECK_RIGHTS_OSG'] = 'Y';
			}

			if ($this->arResult['bExtranetSite'])
			{
				$arListParams['MY_GROUPS_ONLY'] = 'Y';
			}

			if ($this->arParams['SET_LOG_PAGE_CACHE'] == 'Y')
			{
				$groupCode = ($this->arResult['COUNTER_TYPE'] <> '' ? $this->arResult['COUNTER_TYPE'] : '**');
				$res = \Bitrix\Socialnetwork\LogPageTable::getList(array(
					'order' => array(),
					'filter' => array(
						'USER_ID' => $USER->getId(),
						'=SITE_ID' => SITE_ID,
						'=GROUP_CODE' => $groupCode,
						'PAGE_SIZE' => $this->arParams['PAGE_SIZE'],
						'PAGE_NUM' => $this->arResult['PAGE_NUMBER']
					),
					'select' => [ 'PAGE_LAST_DATE' ]
				));

				if ($logPageFields = $res->fetch())
				{
					$dateLastPageStart = $logPageFields['PAGE_LAST_DATE'];
					$arFilter['>=LOG_UPDATE'] = convertTimeStamp(makeTimeStamp($logPageFields['PAGE_LAST_DATE'], \CSite::getDateFormat('FULL')) - 60*60*24*4, 'FULL');
				}
				elseif (
					$groupCode != '**'
					|| $this->arResult['MY_GROUPS_ONLY'] != 'Y'
				)
				{
					$res = \Bitrix\Socialnetwork\LogPageTable::getList([
						'order' => [
							'PAGE_LAST_DATE' => 'DESC'
						],
						'filter' => [
							'=SITE_ID' => SITE_ID,
							'=GROUP_CODE' => $groupCode,
							'PAGE_SIZE' => $this->arParams['PAGE_SIZE'],
							'PAGE_NUM' => $this->arResult['PAGE_NUMBER']
						],
						'select' => [ 'PAGE_LAST_DATE' ]
					]);

					if ($logPageFields = $res->fetch())
					{
						$dateLastPageStart = $logPageFields['PAGE_LAST_DATE'];
						$arFilter['>=LOG_UPDATE'] = convertTimeStamp(makeTimeStamp($logPageFields['PAGE_LAST_DATE'], \CSite::getDateFormat('FULL')) - 60*60*24*4, 'FULL');
						$this->bNeedSetLogPage = true;
					}
				}
			}

			$arOrder = [ 'LOG_UPDATE' => 'DESC' ];
			if ($this->arParams['USE_FOLLOW'] == 'Y')
			{
				$arListParams['USE_FOLLOW'] = 'Y';
				$arOrder = [ 'DATE_FOLLOW' => 'DESC' ];
			}

			$arOrder['ID'] = 'DESC';

			$arSelectFields = [
				'ID',
				'LOG_DATE', 'LOG_UPDATE', 'DATE_FOLLOW',
				'ENTITY_TYPE', 'ENTITY_ID', 'EVENT_ID', 'SOURCE_ID', 'USER_ID', 'COMMENTS_COUNT',
				'FOLLOW', 'FAVORITES_USER_ID',
				'RATING_TYPE_ID', 'RATING_ENTITY_ID'
			];

			$this->arResult['arLogTmpID'] = [];

			$arTmpEventsNew = $this->getEntryIdList([
				'order' => $arOrder,
				'filter' => $arFilter,
				'select' => $arSelectFields,
				'navParams' => $arNavStartParams,
				'listParams' => $arListParams,
				'firstPage' => $this->bFirstPage,
			]);

			if (
				count($this->arResult['arLogTmpID']) <= 0
				&& $this->bNeedSetLogPage // no log pages for user
			)
			{
				unset($dateLastPageStart);
				unset($arFilter['>=LOG_UPDATE']);

				$arTmpEventsNew = $this->getEntryIdList([
					'order' => $arOrder,
					'filter' => $arFilter,
					'select' => $arSelectFields,
					'navParams' => $arNavStartParams,
					'listParams' => $arListParams,
					'firstPage' => $this->bFirstPage,
				]);
			}

			$cnt = count($this->arResult['arLogTmpID']);

			if (
				$cnt == 0
				&& isset($dateLastPageStart)
				&& $this->arParams['SET_LOG_PAGE_CACHE'] == 'Y'
			)
			{
				\CSocNetLogPages::deleteEx($USER->getID(), SITE_ID, $this->arParams['PAGE_SIZE'], ($this->arResult['COUNTER_TYPE'] <> '' ? $this->arResult['COUNTER_TYPE'] : '**'));
			}

			if (
				$cnt < $this->arParams['PAGE_SIZE']
				&& 	isset($arFilter['>=LOG_UPDATE'])
			)
			{
				$this->arResult['NEXT_PAGE_SIZE'] = $cnt;
			}
			elseif (intval($request->get('pagesize')) > 0)
			{
				$this->arResult['NEXT_PAGE_SIZE'] = intval($request->get('pagesize'));
			}

			$lastEventData = [];
			foreach ($arTmpEventsNew as $key => $eventFields)
			{
				if (
					!is_array($this->prevPageLogIdList)
					|| !in_array($eventFields['ID'], $this->prevPageLogIdList)
				)
				{
					$arTmpEventsNew[$key]['EVENT_ID_FULLSET'] = \CSocNetLogTools::findFullSetEventIDByEventID($eventFields['EVENT_ID']);
				}
				else
				{
					unset($arTmpEventsNew[$key]);
				}

				$lastEventData = $eventFields;
			}

			$this->arResult['Events'] = $arTmpEventsNew;

			foreach ($this->arResult["Events"] as $i => $eventFields)
			{
				$event = new \Bitrix\Main\Event(
					'mobile',
					'onGetContentId',
					[
						'logEventFields' => $eventFields
					]
				);
				$event->send();

				foreach($event->getResults() as $eventResult)
				{
					if($eventResult->getType() == \Bitrix\Main\EventResult::SUCCESS)
					{
						$eventParams = $eventResult->getParameters();

						if (
							is_array($eventParams)
							&& isset($eventParams['contentId'])
						)
						{
							$this->arResult['Events'][$i]['CONTENT_ID'] = $eventParams['contentId']['ENTITY_TYPE'].'-'.intval($eventParams['contentId']['ENTITY_ID']);
						}
					}
				}
			}

			$dateLastPage = false;

			if (!empty($lastEventData))
			{
				if (
					$this->arParams['USE_FOLLOW'] == 'N'
					&& $lastEventData['LOG_UPDATE']
				)
				{
					$this->arResult['dateLastPageTS'] = makeTimeStamp($lastEventData['LOG_UPDATE'], \CSite::getDateFormat('FULL'));
					$dateLastPage = convertTimeStamp($this->arResult['dateLastPageTS'], 'FULL');
				}
				elseif ($lastEventData['DATE_FOLLOW'])
				{
					$this->arResult['dateLastPageTS'] = MakeTimeStamp($lastEventData['DATE_FOLLOW'], \CSite::getDateFormat('FULL'));
					$dateLastPage = convertTimeStamp($this->arResult['dateLastPageTS'], 'FULL');
				}
			}

			$emptyCounter = false;
			if (
				$this->arParams['LOG_ID'] <= 0
				&& intval($this->arParams['NEW_LOG_ID']) <= 0
			)
			{
				$counters = \CUserCounter::getValues($USER->getID(), SITE_ID);
				if (isset($counters[$this->arResult["COUNTER_TYPE"]]))
				{
					$this->arResult['LOG_COUNTER'] = intval($counters[$this->arResult['COUNTER_TYPE']]);
				}
				else
				{
					$emptyCounter = true;
					$this->arResult['LOG_COUNTER'] = 0;
				}
			}

			$this->arResult['COUNTER_TO_CLEAR'] = false;

			if ($this->arParams['SET_LOG_COUNTER'] == 'Y')
			{
				if (
					intval($this->arResult['LOG_COUNTER']) > 0
					|| $emptyCounter
				)
				{
					\CUserCounter::clearByUser(
						$USER->getID(),
						[ SITE_ID, \CUserCounter::ALL_SITES ],
						$this->arResult['COUNTER_TYPE'],
						true,
						false
					);

					$this->arResult['COUNTER_TO_CLEAR'] = $this->arResult['COUNTER_TYPE'];

					$res = getModuleEvents('socialnetwork', 'OnSonetLogCounterClear');
					while ($event = $res->Fetch())
					{
						executeModuleEventEx($event, [ $this->arResult['COUNTER_TYPE'], intval($this->arResult['LAST_LOG_TS']) ]);
					}
				}
				elseif ($this->arResult['COUNTER_TYPE'] == \CUserCounter::LIVEFEED_CODE)
				{
					$this->arResult['COUNTER_TO_CLEAR'] = $this->arResult['COUNTER_TYPE'];
				}

				if (
					$this->arResult['COUNTER_TYPE'] == CUserCounter::LIVEFEED_CODE
					&& \Bitrix\Main\Loader::includeModule('pull')
				)
				{
					\Bitrix\Pull\Event::add($USER->getId(), [
						'module_id' => 'main',
						'command' => 'user_counter',
						'expiry' => 3600,
						'params' => [
							SITE_ID => [
								\CUserCounter::LIVEFEED_CODE => 0
							]
						],
					]);

					$this->arResult['COUNTER_TO_CLEAR'] = $this->arResult['COUNTER_TYPE'];
				}
			}

			if ($this->arResult['COUNTER_TO_CLEAR'])
			{
				$this->arResult['COUNTER_SERVER_TIME'] = date('c');
				$this->arResult['COUNTER_SERVER_TIME_UNIX'] = microtime(true);
			}

			if (
				$this->arParams['SET_LOG_PAGE_CACHE'] == 'Y'
				&& $dateLastPage
				&& (
					!$dateLastPageStart
					|| $dateLastPageStart != $dateLastPage
					|| $this->bNeedSetLogPage
				)
			)
			{
				\CSocNetLogPages::set(
					$USER->getId(),
					convertTimeStamp(makeTimeStamp($dateLastPage, \CSite::getDateFormat('FULL')) - $this->arResult['TZ_OFFSET'], 'FULL'),
					$this->arParams['PAGE_SIZE'],
					$this->arResult['PAGE_NUMBER'],
					SITE_ID,
					($this->arResult['COUNTER_TYPE'] <> '' ? $this->arResult['COUNTER_TYPE'] : \CUserCounter::LIVEFEED_CODE)
				);
			}
		}
		else
		{
			$res = \CUser::getById($USER->getId());
			if ($currentUserFields = $res->fetch())
			{
				$this->arResult['EmptyComment'] = [
					'AVATAR_SRC' => \CSocNetLogTools::formatEvent_CreateAvatar($currentUserFields, $this->arParams, ''),
					'AUTHOR_NAME' => \CUser::formatName($this->arParams['NAME_TEMPLATE'], $currentUserFields, $this->useLogin)
				];
			}
		}

		if ($this->arParams['USE_FOLLOW'] == 'Y')
		{
			$res = \CSocNetLogFollow::getList(
				[
					'USER_ID' => $USER->getId(),
					'CODE' => '**'
				],
				[ 'TYPE' ]
			);
			if ($followFields = $res->fetch())
			{
				$this->arResult['FOLLOW_DEFAULT'] = $followFields["TYPE"];
			}
			else
			{
				$this->arResult['FOLLOW_DEFAULT'] = Option::get('socialnetwork', 'follow_default_type', 'Y');
			}
		}

		$this->arResult['SHOW_EXPERT_MODE'] = (
			ComponentHelper::checkLivefeedTasksAllowed()
			&& ModuleManager::isModuleInstalled('tasks')
				? 'Y'
				: 'N'
		);

		if ($this->arResult['SHOW_EXPERT_MODE'] == 'Y')
		{
			$this->arResult['EXPERT_MODE'] = 'N';
			$res = \Bitrix\Socialnetwork\LogViewTable::getList([
				'order' => [],
				'filter' => [
					"USER_ID" => $USER->GetID(),
					"EVENT_ID" => 'tasks'
				],
				'select' => [ 'TYPE' ]
			]);
			if ($logViewFields = $res->Fetch())
			{
				$this->arResult['EXPERT_MODE'] = ($logViewFields['TYPE'] == 'N' ? 'Y' : 'N');
			}
		}

		$allowToAll = ComponentHelper::getAllowToAllDestination();

		$this->arResult['extranetSiteId'] = (
			ModuleManager::isModuleInstalled('extranet')
				? Option::get('extranet', 'extranet_site', false)
				: false
		);

		$this->arResult["extranetSiteDir"] = '';
		if ($this->arResult['extranetSiteId'])
		{
			$res = \Bitrix\Main\SiteTable::getList([
				'filter' => [ '=LID' => $this->arResult['extranetSiteId'] ],
				'select' => [ 'DIR' ]
			]);
			if ($siteFields = $res->fetch())
			{
				$this->arResult['extranetSiteDir'] = $siteFields['DIR'];
			}
		}

		$this->arResult['bDenyToAll'] = ($this->arResult['bExtranetSite'] || !$allowToAll);
		$this->arResult['bDefaultToAll'] = (
			$allowToAll
			? (Option::get('socialnetwork', 'default_livefeed_toall', 'Y') == 'Y')
			: false
		);

		if ($this->arResult['bExtranetSite'])
		{
			$this->arResult['arAvailableGroup'] = \CSocNetLogDestination::getSocnetGroup(
				[
					'features' => [
						"blog",
						[ 'premoderate_post', 'moderate_post', 'write_post', 'full_post' ]
					]
				]
			);
		}

		$this->arResult['bDiskInstalled'] = (
			Option::get('disk', 'successfully_converted', false)
			&& ModuleManager::isModuleInstalled('disk')
		);

		$this->arResult['bWebDavInstalled'] = ModuleManager::isModuleInstalled('webdav');

		$this->arResult['postFormUFCode'] = (
			$this->arResult['bDiskInstalled']
			|| ModuleManager::isModuleInstalled('webdav')
				? 'UF_BLOG_POST_FILE'
				: 'UF_BLOG_POST_DOC'
		);

		if (
			!empty($this->arCrmActivityId)
			&& Option::get('crm', 'enable_livefeed_merge', 'N') == 'Y'
			&& Loader::includeModule('crm')
		)
		{
			$this->arResult['CRM_ACTIVITY2TASK'] = [];

			$res = \CCrmActivity::getList(
				[],
				[
					'TYPE_ID' => \CCrmActivityType::Task,
					'ID' => $this->arCrmActivityId,
					'CHECK_PERMISSIONS' => 'N'
				],
				false,
				false,
				[ 'ID', 'ASSOCIATED_ENTITY_ID' ]
			);
			while ($crmActivityFields = $res->fetch())
			{
				$this->arResult['CRM_ACTIVITY2TASK'][$crmActivityFields['ID']] = $crmActivityFields['ASSOCIATED_ENTITY_ID'];
			}
		}

		$this->arResult['USE_FRAMECACHE'] = ($this->arParams['SET_LOG_COUNTER'] == 'Y');

		// knowledge for group
		$this->arResult['KNOWLEDGE_PATH'] = '';
		if (
			$this->arParams['GROUP_ID'] > 0
			&& Loader::includeModule('landing')
			&& \Bitrix\Landing\Connector\SocialNetwork::userInGroup($this->arParams['GROUP_ID'])
		)
		{
			$this->arResult['KNOWLEDGE_PATH'] = \Bitrix\Landing\Connector\SocialNetwork::getSocNetMenuUrl(
				$this->arParams['GROUP_ID'],
				false
			);
		}

		$this->includeComponentTemplate();
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Error
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	private function getEntryIdList(array $params = []): array
	{
		$result = [];

		$order = (isset($params['order']) ? $params['order'] : []);
		$filter = (isset($params['filter']) ? $params['filter'] : []);
		$select = (isset($params['select']) ? $params['select'] : []);
		$navParams = (isset($params['navParams']) ? $params['navParams'] : []);
		$listParams = (isset($params['listParams']) ? $params['listParams'] : []);
		$firstPage = !!$params['firstPage'];

		$res = \CSocNetLog::getList(
			$order,
			$filter,
			false,
			$navParams,
			$select,
			$listParams
		);

		if (
			$this->arParams['LOG_ID'] <= 0
			&& intval($this->arParams['NEW_LOG_ID']) <= 0
		)
		{
			if ($firstPage)
			{
				$this->arResult['PAGE_NAVNUM'] = $GLOBALS['NavNum'] + 1;
				$this->arResult['PAGE_NAVCOUNT'] = 1000000;
			}
			else
			{
				$this->arResult['PAGE_NUMBER'] = $res->NavPageNomer;
				$this->arResult['PAGE_NAVNUM'] = $res->NavNum;
				$this->arResult['PAGE_NAVCOUNT'] = $res->NavPageCount;
			}
		}

		$cnt = 0;

		$timemanInstalled = ModuleManager::isModuleInstalled('timeman');
		$tasksInstalled = ModuleManager::isModuleInstalled('tasks');
		$listsInstalled = ModuleManager::isModuleInstalled('lists');

		while ($eventFields = $res->getNext())
		{
			if (
				(
					in_array($eventFields['EVENT_ID'], [ 'timeman_entry', 'report' ])
					&& !$timemanInstalled
				)
				|| (
					in_array($eventFields['EVENT_ID'], [ 'tasks' ])
					&& !$tasksInstalled
				)
				|| (
					in_array($eventFields['EVENT_ID'], [ 'lists_new_element' ])
					&& !$listsInstalled
				)
			)
			{
				continue;
			}

			if (
				$eventFields['EVENT_ID'] == 'crm_activity_add'
				&& intval($eventFields['ENTITY_ID']) > 0
			)
			{
				$this->crmActivityIdList[] = intval($eventFields['ENTITY_ID']);
			}

			$cnt++;
			$result[] = $eventFields;
			$this->arResult['arLogTmpID'][] = $eventFields['ID'];
		}

		return $result;
	}
}