<?php

namespace Bitrix\Intranet\License\Widget\Content;

use Bitrix\Intranet\License\ExpirationNotifier;
use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main;
use Bitrix\Main\Type\Date;

class License extends BaseContent
{
	private Main\License $license;
	private ExpirationNotifier $expirationNotifier;
	private bool $isCIS;

	public function __construct()
	{
		$this->license = Application::getInstance()->getLicense();
		$this->isCIS = in_array($this->license->getRegion(), ['ru', 'by', 'kz']);
		$this->expirationNotifier = new ExpirationNotifier();
	}

	public function getName(): string
	{
		return 'license';
	}

	public function getConfiguration(): array
	{
		if (!$this->license->isTimeBound() && !$this->license->isDemo())
		{
			return [
				'isAvailable' => false,
			];
		}

		return [
			'isAvailable' => true,
			'button' => $this->getButtonConfiguration(),
			'more' => $this->getMoreInformationConfiguration(),
			'messages' => [
				'expired' => $this->getExpiredMessage(),
				'block' => $this->getBlockMessage(),
				'remainder' => $this->getRemainderMessage(),
			],
			'isExpired' => $this->isExpired(),
			'isDemo' => $this->license->isDemo(),
			'isAlmostExpired' => $this->isAlmostExpired(),
			'isAlmostBlocked' => $this->isAlmostBlocked(),
			'name' => $this->getLicenseName(),
		];
	}

	public function isExpired(): bool
	{
		return $this->license->isTimeBound() && $this->license->getExpireDate() < new Date();
	}

	private function isAlmostExpired(): bool
	{
		return $this->license->isTimeBound() && $this->expirationNotifier->shouldNotifyAboutAlmostExpiration();
	}

	private function isAlmostBlocked(): bool
	{
		$currentDate = (new Date());
		$licenseBlockedDate = $this->license->getExpireDate()?->add('+15 days');
		$days = $licenseBlockedDate?->getDiff($currentDate)->days;

		return $this->isExpired() && ($days >= 0 && $days <= 15);
	}

	private function getButtonConfiguration(): array
	{
		$expireDate = $this->license->getExpireDate();
		$isAvailable = $this->license->isDemo() || ($expireDate && ((new Date())->getDiff($expireDate)->days < 60));

		if ($this->license->isDemo())
		{
			return [
				'text' => Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_LICENSE_BUTTON_TEXT_DEMO'),
				'link' => (new Main\License\UrlProvider())->getPriceTableUrl(),
				'isAvailable' => $isAvailable,
			];
		}

		return [
			'text' => Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_LICENSE_BUTTON_TEXT_RENEW'),
			'link' => $this->isCIS
				? SITE_DIR . 'bitrix/admin/buy_support.php'
				: (new Main\License\UrlProvider())->getPurchaseHistoryUrl(),
			'type' => $this->isCIS ? 'GET' : 'POST',
			'hashKey' => $this->isCIS ? null : $this->license->getHashLicenseKey(),
			'isAvailable' => $isAvailable,
		];
	}

	private function getExpiredMessage(): ?string
	{
		$tillShortFormat = FormatDate(
			Context::getCurrent()?->getCulture()?->getDayMonthFormat(),
			$this->license->getExpireDate()?->getTimestamp(),
		);

		return Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_LICENSE_FINISH_DATE', [
			'#LICENSETILL#' => $tillShortFormat,
		]);
	}

	private function getBlockMessage(): ?string
	{
		$currentDate = (new Date())->getTimestamp();
		$licenseTill = $this->license->getExpireDate()?->add('+15 days')->getTimestamp();
		$blockDays = FormatDate(
			'ddiff',
			$currentDate,
			max($licenseTill, $currentDate));

		return Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_LICENSE_REMAINDER_BLOCK_DAYS', [
			'#NUM_DAYS#' => $blockDays,
		]);
	}

	private function getRemainderMessage(): ?string
	{
		$currentDate = (new Date())->getTimestamp();
		$expireDate = $this->license->getExpireDate();

		if (!$expireDate)
		{
			return null;
		}

		$expiredLicenseDate = $expireDate->getTimestamp();

		if ((new Date())->getDiff($expireDate)->days < 30)
		{
			$remainder = FormatDate(
				"ddiff",
				$currentDate,
				max($expiredLicenseDate, $currentDate));

			return Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_LICENSE_REMAINDER_DAYS', [
				'#NUM_DAYS#' => $remainder,
			]);
		}

		$licenseTillShortFormat = FormatDate(
			Context::getCurrent()?->getCulture()?->getLongDateFormat(),
			$expiredLicenseDate,
		);

		return Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_LICENSE_REMAINDER_DATE', [
			'#LICENSETILL#' => $licenseTillShortFormat,
		]);
	}

	private function getLicenseName(): ?string
	{
		if ($this->license->isDemo())
		{
			return Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_LICENSE_NAME_DEMO');
		}

		if ($this->isExpired())
		{
			return Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_LICENSE_NAME_DISABLED');
		}

		return Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_LICENSE_NAME_ACTIVE');
	}

	private function getMoreInformationConfiguration(): array
	{
		return [
			'text' => Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_LICENSE_MORE_INFORMATION'),
			'link' => SITE_DIR . 'bitrix/admin/update_system.php',
		];
	}
}
