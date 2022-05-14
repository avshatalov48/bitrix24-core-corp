<?php

namespace Bitrix\Disk\Document\OnlyOffice;

use Bitrix\Disk\Driver;
use Bitrix\Disk\Integration\Bitrix24Manager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;

final class Bitrix24Scenario
{
	/** @var DateTime|null */
	protected $dateInstallation;
	/** @var bool */
	protected $loadedDateInstallation = false;
	protected $isEnabled = false;

	public function __construct()
	{
		$this->isEnabled = ModuleManager::isModuleInstalled('bitrix24');
	}

	protected function getDateInstallationOnlyOffice(): ?DateTime
	{
		if (!$this->loadedDateInstallation)
		{
			$value = Option::get(Driver::INTERNAL_MODULE_ID, 'disk_onlyoffice_installation_date', null);
			$this->loadedDateInstallation = true;
			if ($value)
			{
				$this->dateInstallation = DateTime::createFromTimestamp($value);
			}
		}

		return $this->dateInstallation;
	}

	public function isCurrentUserJoinedAfterInstallationOnlyOffice(): bool
	{
		if (!$this->isEnabled)
		{
			return true;
		}

		if (!($GLOBALS['USER'] instanceof \CUser) || !($GLOBALS['USER']->getId()))
		{
			return true;
		}

		$userSettings = \CUserOptions::getOption(
			Driver::INTERNAL_MODULE_ID,
			'joined_after_install_onlyoffice',
			[
				'v' => null,
			]
		);

		if ($userSettings['v'] === null)
		{
			$dateInstallationOnlyOffice = $this->getDateInstallationOnlyOffice();
			if (!$dateInstallationOnlyOffice)
			{
				return true;
			}

			$user = UserTable::getById($GLOBALS['USER']->getId())->fetchObject();
			if (!$user)
			{
				return true;
			}

			$result = true;
			if ($user->getDateRegister())
			{
				$result = $user->getDateRegister()->getTimestamp() > $dateInstallationOnlyOffice->getTimestamp();
			}

			\CUserOptions::setOption(
				Driver::INTERNAL_MODULE_ID,
				'joined_after_install_onlyoffice',
				[
					'v' => $result,
				]
			);
		}
		else
		{
			$result = (bool)($userSettings['v'] ?? false);
		}

		return $result;
	}

	public function isUserAlreadyGotPromoAboutOnlyOffice(): bool
	{
		if (!$this->isEnabled)
		{
			return true;
		}

		$userSettings = \CUserOptions::getOption(
			Driver::INTERNAL_MODULE_ID,
			'got_promo_onlyoffice',
			[
				'v' => null,
			]
		);

		return ($userSettings['v'] ?? false);
	}

	public function isUserAlreadyGotEndDemoOnlyOffice(): bool
	{
		if (!$this->isEnabled)
		{
			return true;
		}

		$userSettings = \CUserOptions::getOption(
			Driver::INTERNAL_MODULE_ID,
			'got_end_demo_onlyoffice',
			[
				'v' => null,
			]
		);

		return ($userSettings['v'] ?? false);
	}

	public function canUseEdit(): bool
	{
		if (!$this->isEnabled)
		{
			return true;
		}

		return Bitrix24Manager::isFeatureEnabled('disk_onlyoffice_edit');
	}

	public function canUseView(): bool
	{
		if (!$this->isEnabled)
		{
			return true;
		}

		if ($this->canUseEdit())
		{
			return true;
		}

		return Bitrix24Manager::isFeatureEnabled('disk_onlyoffice_view');
	}

	public function isTrialEnded(): bool
	{
		if (!$this->isEnabled || $this->canUseEdit())
		{
			return false;
		}

		$trialFeatureInfo = Bitrix24Manager::getTrialFeatureInfo('disk_onlyoffice_edit');
		if ($trialFeatureInfo)
		{
			return true;
		}

		$isFeatureTrialable = Bitrix24Manager::isFeatureTrialable('disk_onlyoffice_edit');
		if ($isFeatureTrialable)
		{
			return false;
		}

		$trialEditionInfo = Bitrix24Manager::getTrialEditionInfo();

		return (bool)$trialEditionInfo;
	}
}