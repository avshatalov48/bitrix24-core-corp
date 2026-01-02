<?php

namespace Bitrix\Intranet\License\Widget\Content;

use Bitrix\Intranet\Integration\Baas\BaasProvider;
use Bitrix\Main\Localization\Loc;

class Baas extends BaseContent
{
	private BaasProvider $provider;

	public function __construct()
	{
		$this->provider = new BaasProvider();
	}

	public function getName(): string
	{
		return 'baas';
	}

	public function getConfiguration(): array
	{
		return [
			'isAvailable' => $this->provider->isAvailable(),
			'title' => $this->getTitle(),
			'isActive' => $this->provider->isActive(),
			'messages' => [
				'remainder' => $this->getRemainderMessage(),
			],
			'description' => $this->getDescriptionConfiguration(),
			'button' => $this->getButtonConfiguration(),
		];
	}

	private function getTitle(): string
	{
		return Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_BASS_TEXT') ?? '';
	}

	private function getDescriptionConfiguration(): array
	{
		return [
			'text' => Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_BASS_DESCRIPTION'),
			'landingCode' => 'limit_boost_box',
		];
	}

	private function getRemainderMessage(): string
	{
		return Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_BASS_REMAINDER', [
			'#PURCHASE_COUNT#' => $this->provider->getPurchaseCount(),
		]) ?? '';
	}

	private function getButtonConfiguration(): array
	{
		return [
			'text' => $this->provider->isActive()
				? Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_BASS_BUTTON_BUY_MORE')
				: Loc::getMessage('INTRANET_LICENSE_WIDGET_CONTENT_BASS_BUTTON_BUY'),
		];
	}
}
