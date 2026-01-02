<?php

namespace Bitrix\Intranet\License;

use Bitrix\Main\Application;
use Bitrix\Main\License;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Type\Date;

class ExpirationNotifier
{
	private bool $isCIS;
	private License $license;

	public function __construct()
	{
		$this->license = Application::getInstance()->getLicense();
		$this->isCIS = in_array($this->license->getRegion(), ['ru', 'by', 'kz']);
	}

	public function shouldNotifyAboutAlmostExpiration(): bool
	{
		return $this->license->getExpireDate()?->getDiff(new Date())->days < max($this->getNotifySchedule());
	}

	public function getNotifySchedule(): array
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

	private function isEnterpriseLicense(): bool
	{
		return ModuleManager::isModuleInstalled('cluster');
	}
}