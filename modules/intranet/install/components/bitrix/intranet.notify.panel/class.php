<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Main\Type;

class IntranetNotifyPanelComponent extends \CBitrixComponent implements Main\Engine\Contract\Controllerable
{
	private const CONFIG_OPTION_NAME = 'notify-configuration';
	private const CONFIG_OPTION_MODULE = 'intranet';
	private const DEFAULT_NOTIFICATION_CONFIG = [
		'isAvailable' => false,
	];
	private const TYPES_LICENSE_NOTIFICATION = [
		'almost-expired-60' => 'almost-expired-60',
		'expired' => 'expired',
	];
	private const URL_PARTNER_BUY = '/bitrix/admin/buy_support.php?lang=' . LANGUAGE_ID;
	private static int $queueCounter = 0; // counter of notifications that are ready to be shown
	private Main\License $license;
	private bool $isAdmin;

	public function __construct($component = null)
	{
		$this->license = Main\Application::getInstance()->getLicense();
		$this->isAdmin = Main\Engine\CurrentUser::get()->isAdmin();

		parent::__construct($component);
	}

	public function executeComponent()
	{
		if (Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			return;
		}

		$this->includeComponentTemplate();
	}

	public function onPrepareComponentParams($arParams): array
	{
		$this->arResult['notifications'] = $this->getNotifications();
		$this->arResult['notifyManager'] = [
			'isAvailable' => self::$queueCounter > 0,
			'isAdmin' => $this->isAdmin,
		];

		return parent::onPrepareComponentParams($arParams);
	}

	public function getNotifications(): array
	{
		return [
			'license-popup' => $this->getLicenseNotificationData(),
			'panel' => $this->getPanelData(),
		];
	}

	private function getLicenseNotificationData(): array
	{
		if (self::$queueCounter > 0)
		{
			return self::DEFAULT_NOTIFICATION_CONFIG;
		}

		if ($this->license->isTimeBound() && !$this->license->isDemo())
		{
			$dateNow = new Type\Date();
			$expireDate = $this->license->getExpireDate();

			if (!$expireDate)
			{
				return self::DEFAULT_NOTIFICATION_CONFIG;
			}

			$remainder = $expireDate < $dateNow ? 0 : $dateNow->getDiff($expireDate)->days;

			if ($remainder > 60)
			{
				return self::DEFAULT_NOTIFICATION_CONFIG;
			}

			if ($remainder > 0 && !$this->isAdmin)
			{
				return self::DEFAULT_NOTIFICATION_CONFIG;
			}

			$notifyConfig = $this->getItemNotifyConfiguration('license-notification');

			if ($this->checkLicenseNotifyConfig($notifyConfig, $expireDate, $remainder))
			{
				return self::DEFAULT_NOTIFICATION_CONFIG;
			}

			self::$queueCounter++;
			$data['isAvailable'] = true;
			$data['isExpired'] = $remainder === 0;
			$data['type'] = $data['isExpired'] ? self::TYPES_LICENSE_NOTIFICATION['expired']
				: self::TYPES_LICENSE_NOTIFICATION['almost-expired-60'];
			$data['expireDate'] = $expireDate->getTimestamp();
			$data['blockDate'] = $expireDate->add('+15 days')->getTimestamp();
			$data['isPortalWithPartner'] = $this->license->getPartnerId() > 0;
			$data['urlBuyWithPartner'] = self::URL_PARTNER_BUY;
			$data['urlDefaultBuy'] = $this->license->getBuyLink();
			$data['urlArticle'] = $this->license->getDocumentationLink();

			return $data;
		}

		return self::DEFAULT_NOTIFICATION_CONFIG;
	}

	private function getPanelData(): array
	{
		if (self::$queueCounter > 0)
		{
			return self::DEFAULT_NOTIFICATION_CONFIG;
		}

		if ($this->license->isTimeBound())
		{
			$now = new Type\Date();
			$expireDate = $this->license->getExpireDate();

			if ($expireDate && $expireDate < $now)
			{
				self::$queueCounter++;

				return [
					'isAvailable' => true,
					'type' => 'license-expired',
					'params' => [
						'blockDate' => $expireDate->add('+15 days')->getTimestamp(),
						'urlBuy' => $this->license->getBuyLink(),
						'urlArticle' => $this->license->getDocumentationLink(),
					],
				];
			}

			return self::DEFAULT_NOTIFICATION_CONFIG;
		}

		return self::DEFAULT_NOTIFICATION_CONFIG;
	}

	private function getNotifyConfig(): array
	{
		return CUserOptions::GetOption(self::CONFIG_OPTION_MODULE, self::CONFIG_OPTION_NAME, []);
	}

	private function setNotifyConfig(array $value): void
	{
		CUserOptions::SetOption(self::CONFIG_OPTION_MODULE, self::CONFIG_OPTION_NAME, $value);
	}

	private function getItemNotifyConfiguration(string $name): ?array
	{
		return $this->getNotifyConfig()[$name] ?? null;
	}

	public function setLicenseNotifyConfigAction(string $type): void
	{
		if (!in_array($type, self::TYPES_LICENSE_NOTIFICATION))
		{
			return;
		}

		$expireDate = Main\Application::getInstance()->getLicense()->getExpireDate();

		if ($expireDate)
		{
			$this->setItemNotifyConfiguration('license-notification', [
				'expireDate' => $expireDate->getTimestamp(),
				'type' => $type,
				'next-show' => $this->isAdmin && $type === self::TYPES_LICENSE_NOTIFICATION['expired']
					? (new Type\DateTime())->add('+1 day')->getTimestamp() : null,
			]);
		}
	}

	// checking that the current notification has already been shown
	private function checkLicenseNotifyConfig(?array $config, Type\Date $expireDate, int $remainder): bool
	{
		if (!isset($config['expireDate'], $config['type']))
		{
			return false;
		}

		if ($config['expireDate'] === $expireDate->getTimestamp())
		{
			if ($config['type'] === self::TYPES_LICENSE_NOTIFICATION['almost-expired-60'] && $remainder > 0)
			{
				return true;
			}

			if ($config['type'] === self::TYPES_LICENSE_NOTIFICATION['expired'] && $remainder <= 0)
			{
				if ($this->isAdmin)
				{
					if (!isset($config['next-show']))
					{
						return false;
					}

					return ((new Type\DateTime())->getTimestamp() < (int)$config['next-show']);
				}

				return true;
			}
		}

		return false;
	}

	private function setItemNotifyConfiguration(string $name, array $value): void
	{
		$config = $this->getNotifyConfig();

		if (empty($config))
		{
			$config = [$name => $value];
		}
		else
		{
			$config = array_merge($config, [$name => $value]);
		}

		$this->setNotifyConfig($config);
	}

	public function configureActions(): array
	{
		return [];
	}
}
