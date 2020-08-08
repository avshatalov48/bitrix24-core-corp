<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Rest\Configuration\OwnerEntityTable;
use Bitrix\Crm\Integration\Rest\Configuration\Entity\Status;

class CrmDealCheckerComponent extends \CBitrixComponent
{
	private const SLIDER_SHOW_TIME_OPTION_NAME = 'last_popup_show_time';

	/**
	 * @var $dateTime DateTime
	 */
	private
		$categoryId = null,
		$dateTime;

	public function executeComponent()
	{
		$this->categoryId = ($this->arParams['CATEGORY_ID'] ?? null);
		$this->dateTime = $this->getLastDayDateTime();

		$this->arResult['showPopup'] = $this->canShowPopup();
		$this->includeComponentTemplate();
	}

	private function getLastDayDateTime(): DateTime
	{
		$dateTime = new DateTime();
		$dateTime->modify('-1 day');
		return $dateTime;
	}

	private function canShowPopup(): bool
	{
		$canShowInfoHelper = false;

		if (
			$this->categoryId
			&& Loader::includeModule('rest')
			&& is_callable('\Bitrix\Rest\Configuration\OwnerEntityTable::checkApp')
		)
		{
			$timestamp = \CUserOptions::GetOption(
				'crm',
				self::SLIDER_SHOW_TIME_OPTION_NAME
			);

			if ($timestamp < $this->dateTime->getTimestamp())
			{
				$canShowInfoHelper = true;
			}

			/*
			 * uncomment this code and change days_left count after slider create
			if (
				$canShowInfoHelper
				&& ($appInfo = OwnerEntityTable::checkApp(Status::OWNER_ENTITY_TYPE_CRM_DEAL_CATEGORY, $this->categoryId))
				&& isset($appInfo['DAYS_LEFT'])
				&& $appInfo['DAYS_LEFT'] < 30
			)
			{
				$this->dateTime->modify('+1 day');
				\CUserOptions::SetOption(
					'crm',
					self::SLIDER_SHOW_TIME_OPTION_NAME,
					$this->dateTime->getTimestamp()
				);
				return true;
			}
			*/
		}

		return false;
	}
}

