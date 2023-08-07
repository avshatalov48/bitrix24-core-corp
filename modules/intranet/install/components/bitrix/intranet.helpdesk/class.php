<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\UI\Util;

class IntranetHelpdeskComponent extends CBitrixComponent
{
	public const OPTION_CONFIG_NAME = 'new_helper_notify';

	public function executeComponent()
	{
		$this->arResult['NEED_CHECK_HELP_NOTIFICATION'] = 'N';
		$this->arResult['HELP_NOTIFY_NUM'] = '';
		$this->arResult['CAN_HAVE_HELP_NOTIFICATIONS'] = 'N';
		$this->arResult['CURRENT_HELP_NOTIFICATIONS'] = '';
		$this->arResult['LAST_CHECK_NOTIFICATIONS_TIME'] = '';
		$this->arResult['IM_BAR_EXISTS'] = Loader::includeModule('im')
			&& CBXFeatures::IsFeatureEnabled('WebMessenger')
			&& !defined('BX_IM_FULLSCREEN')
		;

		if (
			Loader::includeModule('bitrix24')
			&& !(Loader::includeModule('extranet')
				&& SITE_ID === CExtranet::GetExtranetSiteID())
		)
		{
			$helpNotify = CUserOptions::GetOption('bitrix24', self::OPTION_CONFIG_NAME);

			if (!isset($helpNotify['counter_update_date']))
			{
				$helpNotify['counter_update_date'] = time();
				CUserOptions::SetOption('bitrix24', self::OPTION_CONFIG_NAME, $helpNotify);
			}

			$this->arResult['COUNTER_UPDATE_DATE'] = $helpNotify['counter_update_date']; //time when user read notifications last time

			if (!isset($helpNotify['time']) || $helpNotify['time'] < time())
			{
				$this->arResult['NEED_CHECK_HELP_NOTIFICATION'] = 'Y';
			}

			if (isset($helpNotify['num']))
			{
				$this->arResult['HELP_NOTIFY_NUM'] = (int)$helpNotify['num'];
			}

			if (isset($helpNotify['notifications']))
			{
				$this->arResult['CURRENT_HELP_NOTIFICATIONS'] = $helpNotify['notifications'];
			}

			if (isset($helpNotify['lastCheckNotificationsTime']))
			{
				$this->arResult['LAST_CHECK_NOTIFICATIONS_TIME'] = $helpNotify['lastCheckNotificationsTime'];
			}

			$this->arResult['CAN_HAVE_HELP_NOTIFICATIONS'] = 'Y';
		}

		$this->arResult['HELPDESK_URL'] = '';

		if (Loader::includeModule('ui'))
		{
			$this->arResult['HELPDESK_URL'] = Util::getHelpdeskUrl(true);
		}

		$this->arResult['OPEN_HELPER_AFTER_PAGE_LOADING'] = false;
		$helper = Context::getCurrent()->getRequest()->getQuery('helper');

		if (isset($helper) && $helper === 'Y')
		{
			$this->arResult['OPEN_HELPER_AFTER_PAGE_LOADING'] = true;
		}

		$this->includeComponentTemplate();
	}
}