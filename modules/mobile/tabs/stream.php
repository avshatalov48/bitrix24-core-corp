<?php

namespace Bitrix\Mobile\AppTabs;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\MobileApp\Janative\Manager;
use Bitrix\Socialnetwork\Helper\Path;

class Stream implements Tabable
{
	private $context;

	public function isAvailable()
	{
		return true;
	}

	public function getData()
	{
		return $this->getDataInternal();
	}

	/**
	 * @return boolean
	 */
	public function shouldShowInMenu()
	{
		return true;
	}

	/**
	 * @return null|array
	 */
	public function getMenuData()
	{
		$data = $this->getDataInternal();
		$result = [
			'title' => $this->getTitle(),
			'useLetterImage' => true,
			'color' => '#40465A',
			'imageUrl' => 'favorite/stream.png',
		];;

		if ($data['component'])
		{
			$result['params'] = [
				'onclick' => \Bitrix\Mobile\Tab\Utils::getComponentJSCode($data['component']),
			];
		}
		elseif ($data['page'])
		{
			$result['params'] = $data['page'];
		}

		return $result;
	}

	private function getDataInternal(): array
	{
		$newsWebPath = $this->context->siteDir . 'mobile/index.php?version=' . $this->context->version;
		$bpWebPath = $this->context->siteDir . 'mobile/bp/?USER_STATUS=0';

		if (\Bitrix\MobileApp\Mobile::getApiVersion() < 41)
		{
			return [
				'sort' => 200,
				'imageName' => 'stream',
				'badgeCode' => 'stream',
				'page' => [
					'url' => $newsWebPath,
					'titleParams' => [
						'useLargeTitleMode' => true,
						'text' => $this->getTitle()
					],
					'useSearchBar' => true,
				],
			];
		}

		$calendarWebPathTemplate = Path::get('user_calendar_path_template');
		if ($calendarWebPathTemplate === '')
		{
			$calendarWebPathTemplate = SITE_DIR . '/company/personal/user/#ID#/calendar/';
		}

		$calendarWebPath = str_replace([ '#ID#', '#USER_ID#', '#user_id#' ], $this->context->userId, $calendarWebPathTemplate);

		$isExtranetUser = (Loader::includeModule('extranet') && !\CExtranet::isIntranetUser());

		$allowedFeatures = $this->getAllowedFeatures();

		$tabs = [
			[
				'id' => 'stream',
				'title' => Loc::getMessage('TAB_STREAM_NAVIGATION_TAB_STREAM'),
				'component' => [
					'name' => 'JSStackComponent',
					'componentCode' => 'web: ' . $newsWebPath,
					'rootWidget' => [
						'name' => 'web',
						'settings' => [
							'titleParams' => [
								'useLargeTitleMode' => true,
								'text' => Loc::getMessage('TAB_STREAM_NAVIGATION_TAB_STREAM'),
							],
							'page' => [
								'preload' => false,
								'url' => $newsWebPath,
								'useSearchBar' => true,
							],
						],
					],
				],
			],
		];

		if (
			in_array('files', $allowedFeatures, true)
			&& Option::get('disk', 'successfully_converted', false)
			&& ModuleManager::isModuleInstalled('disk')
		)
		{
			$tabs[] = [
				'id' => 'disk',
				'title' => Loc::getMessage('TAB_STREAM_NAVIGATION_TAB_DISK'),
				'component' => [
					'name' => 'JSStackComponent',
					'title' => Loc::getMessage('TAB_STREAM_NAVIGATION_TAB_DISK'),
					'componentCode' => 'user.disk',
					'scriptPath' => Manager::getComponentPath('user.disk'),
					'rootWidget' => [
						'name' => 'list',
						'settings' => [
							'objectName' => 'list',
							'titleParams' => [
								'useLargeTitleMode' => true,
							],
							'useSearch' => true,
							'doNotHideSearchResult' => true,
						],
					],
					'params' => [
						'COMPONENT_CODE' => 'user.disk',
						'destroyOnRemove'=> false
					],
				],
			];
		}

		if (
			!$isExtranetUser
			&& ModuleManager::isModuleInstalled('bizproc')
		)
		{
			$tabs[] = [
				'id' => 'bp',
				'title' => Loc::getMessage('TAB_STREAM_NAVIGATION_TAB_BP'),
				'component' => [
					'name' => 'JSStackComponent',
					'componentCode' => 'web: ' . $bpWebPath,
					'rootWidget' => [
						'name' => 'web',
						'settings' => [
							'titleParams' => [
								'useLargeTitleMode' => true,
								'text' => Loc::getMessage('TAB_STREAM_NAVIGATION_TAB_BP'),
							],
							'page' => [
								'preload' => false,
								'url' => $bpWebPath,
								'useSearchBar' => false,
							],
						],
					],
				],
			];
		}

/*
		if (
			in_array('calendar', $allowedFeatures, true)
			&& ModuleManager::isModuleInstalled('calendar')
			&& !$isExtranetUser
		)
		{
			$tabs[] = [
				'id' => 'calendar',
				'title' => Loc::getMessage('TAB_STREAM_NAVIGATION_TAB_CALENDAR'),
				'selectable' => false,
			];
		}
*/
/*
		if (!$isExtranetUser)
		{
			$tabs[] = [
				'id' => 'video',
				'title' => Loc::getMessage('TAB_STREAM_NAVIGATION_TAB_VIDEO'),
				'selectable' => false,
			];
		}
*/
		if (
			!$isExtranetUser
			&& ModuleManager::isModuleInstalled('mail')
		)
		{
			$tabs[] = [
				'id' => 'mail',
				'title' => Loc::getMessage('TAB_STREAM_NAVIGATION_TAB_MAIL'),
				'selectable' => false,
			];
		}

		return [
			'sort' => 200,
			'imageName' => 'stream',
			'badgeCode' => 'stream',
			'id' => 'stream',
			'component' => [
				'name' => 'JSStackComponent',
				'title' => Loc::getMessage('TAB_STREAM_NAVIGATION_HEADER'),
				'componentCode' => 'stream.tabs',
				'scriptPath' => Manager::getComponentPath('stream.tabs'),
				'rootWidget' => [
					'name' => 'tabs',
					'settings' => [
						'objectName' => 'tabs',
						'titleParams' => [
							'useLargeTitleMode' => true,
							'text' => Loc::getMessage('TAB_STREAM_NAVIGATION_HEADER2'),
						],
						'grabTitle' => false,
						'grabButtons' => true,
						'grabSearch' => true,
						'tabs' => [
							'items' => $tabs,
						],
					],
				],
				'params' => [
					'COMPONENT_CODE' => 'stream.tabs',
					'USER_ID' => $this->context->userId,
					'SITE_ID' => $this->context->siteId,
					'CALENDAR_WEB_PATH' => $calendarWebPath,
					'VIDEO_WEB_PATH' => SITE_DIR . 'conference/',
					'MAIL_WEB_PATH' => SITE_DIR . 'mail/',
				],
			],
		];
	}

	private function getAllowedFeatures(): array
	{
		$result = [];

		if (!Loader::includeModule('socialnetwork'))
		{
			return $result;
		}

		$activeUserFeaturesList = \CSocNetFeatures::getActiveFeatures(SONET_ENTITY_USER, $this->context->userId);
		$allowedFeaturesList = \CSocNetAllowed::getAllowedFeatures();

		foreach ([ 'files', 'calendar' ] as $feature)
		{
			if ($feature === 'calendar')
			{
				$allowed = (
					isset($allowedFeaturesList[$feature]['allowed'])
					&& (
						(
							in_array(SONET_ENTITY_USER, $allowedFeaturesList[$feature]['allowed'], true)
							&& is_array($activeUserFeaturesList)
							&& in_array($feature, $activeUserFeaturesList, true)
						)
						|| in_array(SONET_ENTITY_GROUP, $allowedFeaturesList[$feature]['allowed'], true)
					)
				);
			}
			else
			{
				$allowed = (
					isset($allowedFeaturesList[$feature]['allowed'])
					&& in_array(SONET_ENTITY_USER, $allowedFeaturesList[$feature]['allowed'], true)
					&& is_array($activeUserFeaturesList)
					&& in_array($feature, $activeUserFeaturesList)
				);
			}

			if ($allowed)
			{
				$result[] = $feature;
			}
		}

		return $result;
	}

	public function canBeRemoved()
	{
		return true;
	}

	/**
	 * @return integer
	 */
	public function defaultSortValue()
	{
		return 200;
	}

	public function canChangeSort()
	{
		return true;
	}

	public function getTitle()
	{
		return Loc::getMessage("TAB_NAME_NEWS");
	}

	public function setContext($context)
	{
		$this->context = $context;
	}

	public function getShortTitle()
	{
		return Loc::getMessage("TAB_NAME_NEWS_SHORT");
	}

	public function getId()
	{
		return "news";
	}
}

