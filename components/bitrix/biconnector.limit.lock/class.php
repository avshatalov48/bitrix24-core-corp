<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\LimitManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\UI;

class LimitLockComponent extends CBitrixComponent
{
	private LimitManager $limitManager;
	private bool $fullLock = false;
	private bool $supersetLimit = false;

	private const LAST_TIME_SHOW_POPUP_OPTION_NAME = 'last_time_show_limit_popup';

	public function executeComponent()
	{
		if (!$this->includeModules())
		{
			return null;
		}
		$this->init();

		if (!$this->needShowPopup())
		{
			return null;
		}

		if ($this->limitManager->checkLimitWarning())
		{
			return null;
		}

		$this->prepareResult();
		$this->arResult['TITLE'] = $this->getTitle();
		$this->arResult['LICENSE_BUTTON_TEXT'] = $this->getLicenseButtonText();
		$this->arResult['CONTENT'] = $this->getPopupContent();
		$this->arResult['LICENSE_URL'] = $this->getLicenseUrl();
		$this->arResult['FULL_LOCK'] = $this->fullLock ? 'Y' : 'N';

		$this->includeComponentTemplate();
	}

	private function init(): void
	{
		$this->limitManager = LimitManager::getInstance();
		$this->supersetLimit = ($this->arParams['SUPERSET_LIMIT'] ?? 'N') === 'Y';
		if ($this->supersetLimit)
		{
			$this->limitManager->setIsSuperset();
		}
		$this->fullLock = !$this->limitManager->checkLimit();
	}

	private function prepareResult(): void
	{
		$this->arResult = [
			'TITLE' => '',
			'LICENSE_BUTTON_TEXT' => '',
			'LATER_BUTTON_TEXT' => Loc::getMessage('CC_BLL_LATER_BUTTON_MSGVER_1'),
			'CONTENT' => '',
			'LICENSE_PATH' => '',
		];
	}

	private function needShowPopup(): bool
	{
		if (!$this->supersetLimit)
		{
			return true;
		}

		if ($this->fullLock)
		{
			return true;
		}

		$lastTimestampShow = (int)Option::get('biconnector', self::LAST_TIME_SHOW_POPUP_OPTION_NAME, 0);
		if ($lastTimestampShow <= 0)
		{
			Option::set('biconnector', self::LAST_TIME_SHOW_POPUP_OPTION_NAME, time());

			return true;
		}

		$lastTimeShow = DateTime::createFromTimestamp($lastTimestampShow);
		if ($lastTimeShow->add('+60 seconds') < new DateTime())
		{
			Option::set('biconnector', self::LAST_TIME_SHOW_POPUP_OPTION_NAME, time());

			return true;
		}

		return false;
	}

	private function getTitle(): string
	{
		if ($this->supersetLimit && !$this->limitManager->isLimitByLicence())
		{
			if ($this->fullLock)
			{
				return Loc::getMessage('CC_BLL_CONSTRUCTOR_TITLE_BLOCKED', [
					'#LIMIT_END_DATE#' => $this->limitManager->getSupersetUnlockDate(),
				]);
			}

			return Loc::getMessage('CC_BLL_CONSTRUCTOR_TITLE_WARNING');
		}

		if (Loader::includeModule('bitrix24'))
		{
			if ($this->fullLock)
			{
				return Loc::getMessage('CC_BLL_TITLE_CLOUD_BLOCKED', [
					'#SHORT_DATE#' => $this->limitManager->getLimitDate(),
				]);
			}

			return Loc::getMessage('CC_BLL_TITLE_CLOUD_WARNING');
		}

		if ($this->fullLock)
		{
			return Loc::getMessage('CC_BLL_TITLE_BOX_BLOCKED', [
				'#SHORT_DATE#' => $this->limitManager->getLimitDate()->format(Date::getFormat()),
			]);
		}

		return Loc::getMessage('CC_BLL_TITLE_BOX_WARNING', [
			'#SHORT_DATE#' => $this->limitManager->getLimitDate()->format(Date::getFormat()),
		]);
	}

	private function getLicenseButtonText(): string
	{
		if (Loader::includeModule('bitrix24'))
		{
			return Loc::getMessage('CC_BLL_LICENSE_BUTTON_CLOUD');
		}

		if ($this->supersetLimit && !$this->limitManager->isLimitByLicence())
		{
			return '';
		}

		return Loc::getMessage('CC_BLL_LICENSE_BUTTON_BOX');
	}

	private function getPopupContent(): string
	{
		if ($this->supersetLimit && !$this->limitManager->isLimitByLicence())
		{
			if ($this->fullLock)
			{
				return Loc::getMessage('CC_BLL_CONSTRUCTOR_CONTENT_BLOCKED', [
					'#LIMIT#' => number_format($this->limitManager->getLimit(), thousands_separator: ' '),
					'#SHORT_DATE#' => $this->limitManager->getLimitDate(),
					'#ABOUT_LIMITS_HREF#' => UI\Util::getArticleUrlByCode('14888370'),
				]);
			}

			return Loc::getMessage('CC_BLL_CONSTRUCTOR_CONTENT_WARNING', [
				'#LIMIT#' => number_format($this->limitManager->getLimit(), thousands_separator: ' '),
				'#LOCK_DATE#' => $this->limitManager->getLimitDate(),
				'#ABOUT_LIMITS_HREF#' => UI\Util::getArticleUrlByCode('14888370'),
			]);
		}

		if (Loader::includeModule('bitrix24'))
		{
			if ($this->fullLock)
			{
				return Loc::getMessage('CC_BLL_CONTENT_BLOCKED_MSGVER_1', [
					'#LIMIT#' => number_format($this->limitManager->getLimit(), thousands_separator: ' '),
					'#SHORT_DATE#' => $this->limitManager->getLimitDate(),
					'#ABOUT_LIMITS_HREF#' => UI\Util::getArticleUrlByCode('14888370'),
				]);
			}

			return Loc::getMessage('CC_BLL_CONTENT_WARNING_MSGVER_1', [
				'#LIMIT#' => number_format($this->limitManager->getLimit(), thousands_separator: ' '),
				'#SHORT_DATE#' => $this->limitManager->getLimitDate(),
				'#ABOUT_LIMITS_HREF#' => UI\Util::getArticleUrlByCode('14888370'),
			]);
		}

		if ($this->fullLock)
		{
			return Loc::getMessage('CC_BLL_CONTENT_BLOCKED_BOX_MSGVER_1', [
				'#SHORT_DATE#' => $this->limitManager->getLimitDate()->format(Date::getFormat()),
				'#ABOUT_LIMITS_HREF#' => \Bitrix\UI\Util::getArticleUrlByCode('15702822'),
			]);
		}

		return Loc::getMessage('CC_BLL_CONTENT_WARNING_BOX_MSGVER_1', [
			'#SHORT_DATE#' => $this->limitManager->getLimitDate()->format(Date::getFormat()),
			'#ABOUT_LIMITS_HREF#' => \Bitrix\UI\Util::getArticleUrlByCode('15702822'),
		]);
	}

	private function getLicenseUrl(): string
	{
		if (Loader::includeModule('bitrix24'))
		{
			$licenseUrl = \CBitrix24::PATH_LICENSE_ALL;
		}
		else
		{
			$region = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();
			$licenseUrl = match ($region)
			{
				'ru' => 'https://www.1c-bitrix.ru/buy/products/b24.php',
				'ua' => 'https://www.bitrix.ua/buy/products/b24.php',
				'kz' => 'https://www.1c-bitrix.kz/buy/products/b24.php',
				'by' => 'https://www.1c-bitrix.by/buy/products/b24.php',
				'de' => 'https://store.bitrix24.de/profile/license-keys.php',
				default => 'https://store.bitrix24.com/profile/license-keys.php',
			};
		}

		return $licenseUrl;
	}

	private function includeModules(): bool
	{
		if (!Loader::includeModule('biconnector'))
		{
			return false;
		}

		if (!Loader::includeModule('ui'))
		{
			return false;
		}

		return true;
	}
}
