<?php

namespace Bitrix\Intranet\License\Notification;

use Bitrix\Intranet\CurrentUser;
use Bitrix\Main\Application;
use Bitrix\Main\License;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

class Popup implements NotificationProvider
{
	private const CONFIG_OPTION_MODULE = 'intranet';
	private const CONFIG_OPTION_NAME = 'notify-configuration';
	private License $license;
	private CurrentUser $currentUser;
	private ?Date $expireDate;
	private const URL_PARTNER_BUY = '/bitrix/admin/buy_support.php?lang=' . LANGUAGE_ID;
	private bool $isCIS;
	private int $numberRemainingDaysLicense;

	public function __construct()
	{
		$this->license = Application::getInstance()->getLicense();
		$this->currentUser = CurrentUser::get();
		$this->expireDate = $this->license->getExpireDate();
		$this->isCIS = in_array($this->license->getRegion(), ['ru', 'by', 'kz']);
	}

	public function isAvailable(): bool
	{
		return $this->currentUser->isAdmin() && $this->license->isTimeBound() && !$this->license->isDemo();
	}

	public function checkNeedToShow(): bool
	{
		if (!$this->expireDate)
		{
			return false;
		}

		if (!$this->isAvailable())
		{
			return false;
		}

		if ($this->getNumberRemainingDaysLicense() > $this->getStartingDayNotification())
		{
			return false;
		}

		return $this->checkNeedToShowBySavedShowConfiguration();
	}

	public function getConfiguration(): array
	{
		return [
			'isAdmin' => $this->currentUser->isAdmin(),
			'isExpired' => $this->getNumberRemainingDaysLicense() === -1,
			'type' => 'popup',
			'popupType' => $this->getCurrentType(),
			'expireDate' => $this->expireDate?->getTimestamp(),
			'blockDate' => Date::createFromTimestamp($this->expireDate?->getTimestamp())->add('+15 days')->getTimestamp(),
			'isPortalWithPartner' => $this->license->getPartnerId() > 0,
			'urlBuyWithPartner' => self::URL_PARTNER_BUY,
			'urlDefaultBuy' => $this->license->getBuyLink(),
			'urlArticle' => $this->license->getDocumentationLink(),
			'isCIS' => $this->isCIS,
		];
	}

	public function saveShowConfiguration(string $type): void
	{
		if (!$this->isAvailable())
		{
			return;
		}

		$config = $this->getSavedShowConfiguration();
		$value = [
			'expireDate' => $this->expireDate?->getTimestamp(),
			'type' => $type,
			'next-show' => (new DateTime())->add('+1 day')->getTimestamp(),
		];

		if (empty($config))
		{
			$config = $value;
		}
		else
		{
			$config = array_merge($config, $value);
		}

		$this->setSavedShowConfiguration($config);
	}

	public function getNumberRemainingDaysLicense(): int
	{
		if (!isset($this->numberRemainingDaysLicense))
		{
			$dateNow = new Date();
			$this->numberRemainingDaysLicense = $this->expireDate < $dateNow ? -1
				: $dateNow->getDiff($this->expireDate)->days;
		}

		return $this->numberRemainingDaysLicense;
	}

	public function getCurrentType(): string
	{
		if ($this->getNumberRemainingDaysLicense() === -1)
		{
			return 'expired';
		}

		$schedule = $this->getSchedule();

		foreach ($schedule as $day)
		{
			if ($day >= $this->getNumberRemainingDaysLicense())
			{
				return (string)$day;
			}
		}

		return 'no-expired';
	}

	private function isEnterpriseLicense(): bool
	{
		return ModuleManager::isModuleInstalled('cluster');
	}

	public function getSchedule(): array
	{
		if ($this->isEnterpriseLicense())
		{
			if ($this->isCIS)
			{
				return [1, 15, 30, 60];
			}

			return [1, 15, 30];
		}

		if ($this->isCIS)
		{
			return [1, 15, 30];
		}

		return [1, 15];
	}

	public function getSavedShowConfiguration(): array
	{
		return \CUserOptions::GetOption(self::CONFIG_OPTION_MODULE, self::CONFIG_OPTION_NAME, []);
	}

	public function getStartingDayNotification(): int
	{
		return max($this->getSchedule());
	}

	private function setSavedShowConfiguration(array $value): void
	{
		\CUserOptions::SetOption(self::CONFIG_OPTION_MODULE, self::CONFIG_OPTION_NAME, $value);
	}

	private function checkNeedToShowBySavedShowConfiguration(): bool
	{
		$savedConfiguration = $this->getSavedShowConfiguration();

		if (!isset($savedConfiguration['expireDate'], $savedConfiguration['type']))
		{
			return true;
		}

		if ($savedConfiguration['expireDate'] === $this->expireDate?->getTimestamp())
		{
			if ($savedConfiguration['type'] === $this->getCurrentType() && $this->getNumberRemainingDaysLicense() > -1)
			{
				return false;
			}

			if ($savedConfiguration['type'] === 'expired' && $this->getNumberRemainingDaysLicense() <= -1)
			{
				if (!isset($savedConfiguration['next-show']))
				{
					return true;
				}

				return ((new DateTime())->getTimestamp() > (int)$savedConfiguration['next-show']);
			}
		}

		return true;
	}
}