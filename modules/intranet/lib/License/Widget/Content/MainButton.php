<?php

namespace Bitrix\Intranet\License\Widget\Content;

use Bitrix\Intranet\License\ExpirationNotifier;
use Bitrix\Main\Application;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;

class MainButton extends BaseContent
{
	private Main\License $license;
	private ExpirationNotifier $expirationNotifier;

	public function __construct()
	{
		$this->license = Application::getInstance()->getLicense();
		$this->expirationNotifier = new ExpirationNotifier();
	}

	public function getName(): string
	{
		return 'main-button';
	}

	public function getText(): string
	{
		if ($this->license->isDemo())
		{
			return Loc::getMessage('INTRANET_LICENSE_WIDGET_MAIN_BUTTON_TEXT_DEMO') ?? '';
		}

		return Loc::getMessage('INTRANET_LICENSE_WIDGET_MAIN_BUTTON_TEXT') ?? '';
	}

	public function getClassName(): string
	{
		$baseClasses = 'ui-btn ui-btn-round ui-btn-themes license-btn';
		$additionalClasses = 'ui-btn-icon-tariff --pro license-btn-blue-border';

		if ($this->isExpired())
		{
			$additionalClasses = 'license-btn-alert-border license-btn-animate license-btn-animate-forward';
		}
		elseif ($this->isAlmostExpired())
		{
			$additionalClasses = 'license-btn-alert-border ui-btn-icon-low-battery';
		}
		elseif ($this->license->isDemo())
		{
			$additionalClasses = 'ui-btn-icon-demo license-btn-blue-border';
		}

		return $baseClasses . ' ' . $additionalClasses;
	}

	public function getConfiguration(): array
	{
		return [
			'text' => $this->getText(),
			'className' => $this->getClassName(),
		];
	}

	private function isExpired(): bool
	{
		return $this->license->isTimeBound() && $this->license->getExpireDate() < new Date();
	}

	private function isAlmostExpired(): bool
	{
		return $this->license->isTimeBound() && $this->expirationNotifier->shouldNotifyAboutAlmostExpiration();
	}
}
