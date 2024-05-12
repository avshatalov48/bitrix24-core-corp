<?php

namespace Bitrix\Intranet\License\Notification;

use Bitrix\Main\Application;
use Bitrix\Main\License;
use Bitrix\Main\Type\Date;

class Panel implements NotificationProvider
{
	private License $license;
	private ?Date $expireDate;

	public function __construct()
	{
		$this->license = Application::getInstance()->getLicense();
		$this->expireDate = $this->license->getExpireDate();
	}

	public function isAvailable(): bool
	{
		return $this->license->isTimeBound() && $this->expireDate;
	}

	public function checkNeedToShow(): bool
	{
		$currentDate = new Date();

		return $this->isAvailable() && $currentDate > $this->expireDate;
	}

	public function getConfiguration(): array
	{
		return [
			'type' => 'panel',
			'blockDate' => $this->expireDate?->add('+15 days')->getTimestamp(),
			'urlBuy' => $this->license->getBuyLink(),
			'urlArticle' => $this->license->getDocumentationLink(),
		];
	}

}