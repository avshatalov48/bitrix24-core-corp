<?php

namespace Bitrix\Intranet\License\Widget\Content;

use Bitrix\Intranet\Integration\Market\Label;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Web\Uri;
use Bitrix\Rest\Engine\Access;
use Bitrix\Rest\Marketplace;

class Market extends BaseContent
{
	private bool $isDemo;
	private bool $isSubscriptionAccess;
	private bool $isSubscriptionAvailable;
	private bool $isDemoAvailable;
	private ?Date $subscriptionFinalDate;
	private bool $isAlmostExpired;
	private bool $isExpired;
	private bool $isPaid;

	public function __construct()
	{
		$this->isSubscriptionAccess = Loader::includeModule('rest')
			&& Marketplace\Client::isSubscriptionAccess();
		$this->isDemo = Marketplace\Client::isSubscriptionDemo();
		$this->isSubscriptionAvailable = Marketplace\Client::isSubscriptionAvailable();
		$this->isDemoAvailable = Marketplace\Client::isSubscriptionDemoAvailable();
		$this->subscriptionFinalDate = Marketplace\Client::getSubscriptionFinalDate();
		$daysLeft = $this->subscriptionFinalDate?->getDiff(new Date())->days;
		$this->isAlmostExpired = $this->isSubscriptionAvailable && $daysLeft !== null && $daysLeft < 14;
		$this->isExpired = $this->isSubscriptionAvailable && $daysLeft !== null && $daysLeft <= 0;
		$this->isPaid = $this->isSubscriptionAvailable && !$this->isDemo;
	}

	public function getName(): string
	{
		return 'market';
	}

	public function getConfiguration(): array
	{
		if (!$this->isSubscriptionAccess)
		{
			return [
				'isAvailable' => $this->isSubscriptionAccess,
			];
		}

		return [
			'isAvailable' => $this->isSubscriptionAccess,
			'title' => $this->getTitle(),
			'description' => $this->getDescriptionConfiguration(),
			'button' => $this->getButtonConfiguration(),
			'messages' => [
				'remainder' => $this->getRemainingDaysMessage(),
			],
			'isAlmostExpired' => $this->isAlmostExpired,
			'isExpired' => $this->isExpired,
			'isPaid' => $this->isPaid,
			'isDemo' => $this->isDemo,
		];
	}

	private function getRemainingDaysMessage(): string
	{
		if (!$this->isSubscriptionAvailable)
		{
			return '';
		}

		$finalDate = $this->subscriptionFinalDate?->getTimestamp();
		$currentDate = new Date();

		if ($finalDate > 0)
		{
			$paidDateConverted = Date::createFromTimestamp($finalDate)->toString();
			$daysLeftForMessage = FormatDate("ddiff", $currentDate, max($finalDate, $currentDate));

			if ($this->isAlmostExpired)
			{
				return Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_MARKET_REMAINDER', [
					'#NUM_DAYS#' => $daysLeftForMessage,
				]);
			}

			if ($this->isDemo)
			{
				return Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_MARKET_REMAINDER_DEMO_DATE', [
					'#MARKET_TILL#' => $paidDateConverted,
				]);
			}

			return Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_MARKET_REMAINDER_DATE', [
				'#MARKET_TILL#' => $paidDateConverted,
			]);
		}

		return '';
	}

	private function getTitle(): string
	{
		if (Label::isRenamedMarket())
		{
			return Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_MARKET_TEXT_MSGVER_1');
		}

		return Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_MARKET_TEXT');
	}

	private function getDescriptionConfiguration(): array
	{
		if ($this->isDemo)
		{
			$landingCode = 'limit_benefit_market_trial_active';
		}
		elseif ($this->isSubscriptionAvailable)
		{
			$landingCode = 'limit_benefit_market_active';
		}
		else
		{
			$landingCode = 'limit_benefit_market';
		}

		return [
			'text' => Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_MARKET_DESCRIPTION'),
			'landingCode' => $landingCode,
		];
	}

	private function getButtonConfiguration(): array
	{
		$link = \Bitrix\Intranet\Binding\Marketplace::getMainDirectory();

		if ($this->isDemoAvailable && !$this->isDemo && !$this->isSubscriptionAvailable)
		{
			$text = Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_MARKET_BUTTON_ON_DEMO');
			$link = (new Uri(SITE_DIR))
				->addParams(['FEATURE_PROMOTER' => Access::getHelperCode(Access::ACTION_BUY)])
				->getUri();
		}
		elseif ($this->isSubscriptionAvailable)
		{
			if ($this->isAlmostExpired)
			{
				$text = Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_MARKET_BUTTON_RENEW');
			}
			else
			{
				$text = Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_MARKET_BUTTON_CATALOG');
			}
		}
		else
		{
			$region = Application::getInstance()->getLicense()->getRegion();

			if ($region === 'ru')
			{
				$link = 'https://www.1c-bitrix.ru/buy/products/b24.php#tab-section-5';
			}
			elseif ($region === 'by')
			{
				$link = 'https://www.1c-bitrix.by/buy/products/b24.php#tab-section-4';
			}

			$text = Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_MARKET_BUTTON_BUY');
		}

		return [
			'text' => $text,
			'link' => $link,
		];
	}
}
