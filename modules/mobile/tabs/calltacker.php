<?php

namespace Bitrix\Mobile\AppTabs;

use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Tab\Tabable;

class CallTracker implements Tabable
{
	private $context;

	public function isAvailable()
	{
		return (
			!$this->context->extranet
			&& \Bitrix\Mobile\Integration\Crm\CallTracker::isAvailable()
		);
	}

	public function getData()
	{
		if (!$this->isAvailable())
		{
			return null;
		}

		return [
			'id' => \Bitrix\Mobile\Integration\Crm\CallTracker::getMenuId(),
			'imageName' => 'tracker',
			'badgeCode' => 'crm_activity_current_calltracker',
			'sort' => $this->defaultSortValue(),
			'component' => $this->getComponentParams(),
		];
	}

	/**
	 * @return boolean
	 */
	public function shouldShowInMenu()
	{
		return $this->isAvailable();
	}

	/**
	 * @return null|array
	 */
	public function getMenuData()
	{
		$componentParams = $this->getComponentParams();

		$result = [
			'title' => Loc::getMessage('TAB_NAME_CALLTRACKER'),
			'min_api_version' => \Bitrix\Mobile\Integration\Crm\CallTracker::getMinApiVersion(),
			'id' => \Bitrix\Mobile\Integration\Crm\CallTracker::getMenuId(),
			'imageUrl' => 'crm/icon-phone-tracker.png',
			'color' => '#0069B5',
			'params' => [
				'onclick' => \Bitrix\Mobile\Tab\Utils::getComponentJSCode($componentParams),
				'counter' => 'crm_activity_current_calltracker',
			]
		];

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
		return 500;
	}

	public function canChangeSort()
	{
		return true;
	}

	public function getTitle()
	{
		return Loc::getMessage('TAB_NAME_CALLTRACKER');
	}

	public function setContext($context)
	{
		$this->context = $context;
	}

	public function getShortTitle()
	{
		return Loc::getMessage('TAB_NAME_CALLTRACKER_SHORT');
	}

	public function getId()
	{
		return 'calltracker';
	}

	private function getComponentParams(): array
	{
		return [
			'name' => 'JSStackComponent',
			'scriptPath' => \Bitrix\MobileApp\Janative\Manager::getComponentPath('crm.calltracker.list'),
			'componentCode' => 'crm.calltracker.list',
			'rootWidget' => [
				'name' => 'layout',
				'settings' => [
					'objectName' => 'layoutWidget',
					'title' => Loc::getMessage('TAB_NAME_CALLTRACKER'),
				],
			],
			'params' => [
				'detailUrl' => SITE_DIR . 'mobile/crm/deal/?page=calltracker_details&deal_id=#deal_id#',
				'isSimpleCrm' => !\Bitrix\Crm\Settings\LeadSettings::isEnabled(),
				'hasFeatureRestrictions' => !\Bitrix\Crm\Restriction\RestrictionManager::isCallTrackerPermitted(),
				'hasActiveTab' => \Bitrix\Mobile\Integration\Crm\CallTracker::hasActiveTab(),
				'canAddToIgnored' => \Bitrix\Crm\Exclusion\Manager::checkCreatePermission()
			],
		];
	}
}
