<?php

namespace Bitrix\Disk\Document\OnlyOffice\Editor;


use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;

final class CustomizationBuilder
{
	public const REVIEW_DISPLAY_MARKUP = 'markup';
	public const REVIEW_DISPLAY_SIMPLE = 'simple';
	public const REVIEW_DISPLAY_FINAL = 'final';
	public const REVIEW_DISPLAY_ORIGINAL = 'original';
	public const LOGO_CUSTOMER_RU_PNG = 'disk_doceditor_logo_customer_ru.png';
	public const LOGO_CUSTOMER_EN_PNG = 'disk_doceditor_logo_customer_en.png';

	/** @var Uri */
	private $baseUrl;
	/** @var Uri */
	protected $baseUrlToLogo;
	/** @var string */
	protected $infoText;
	/** @var array */
	private $customization;
	private ?string $portalZone;

	public function __construct(Uri $baseUrl, array $customization = [], string $portalZone = null)
	{
		$this->baseUrl = $baseUrl;
		$this->customization = $customization;
		$this->portalZone = $portalZone;
	}

	public function setBaseUrlToLogo(Uri $url): self
	{
		$this->baseUrlToLogo = $url;

		return $this;
	}

	public function setInfoText(string $infoText): self
	{
		$this->infoText = $infoText;

		return $this;
	}

	public function build(): array
	{
		$customization = [
			'forcesave' => true,
			'customer' => [
				'address' => '',
				'info' => $this->infoText ?? '',
				'logo' => $this->getLogoForCustomerSection(),
				'mail' => '',
				'name' => 'Bitrix24',
				'www' => $this->getUrlForCustomerSection(),
			],
			'logo' => [
				'image' => $this->buildUrlToImage('disk_doceditor_logo.png?1'),
				'imageEmbedded' => $this->buildUrlToImage('disk_doceditor_logo_embed.png?1'),
				'url' => 'https://bitrix24.com',
			],
			'review' => [
				'reviewDisplay' => self::REVIEW_DISPLAY_MARKUP,
			],
			'goback' => false,
			'plugins' => false,
			// 'loaderName' => 'Bitrix24',
			'hideRightMenu' => $this->customization['hideRightMenu'] ?? true,
			'hideRulers' => $this->customization['hideRulers'] ?? false,
			'compactHeader' => $this->customization['compactHeader'] ?? true,
			'compactToolbar' => $this->customization['compactToolbar'] ?? false,
		];

		return $customization;
	}

	protected function getUrlForCustomerSection(): string
	{
		$mapPortalZoneToLink = [
			'com' => 'bitrix24.com/~3JSIK',
			'in'=> 'bitrix24.in/~Xnje8',
			'eu' => 'bitrix24.eu/~XCN6F',
			'de'=> 'bitrix24.de/~SHScb',
			'es'=> 'bitrix24.es/~DGazh',
			'br'=> 'bitrix24.com.br/~5cSlR',
			'pl' => 'bitrix24.pl/~bYGVk',
			'it' => 'bitrix24.it/~2jnT9',
			'fr'=> 'bitrix24.fr/~GheAt',
			'cn' => 'bitrix24.cn/~pZamF',
			'tc' => 'bitrix24.cn/~lO6mN',
			'jp' => 'bitrix24.jp/~WNv10',
			'vn' => 'bitrix24.vn/~b23Cr',
			'tr' => 'bitrix24.com.tr/~QIxVz',
			'id' => 'bitrix24.id/~lZJR5',
			'my' => 'bitrix24.com/~GGKdw',
			'th' => 'bitrix24.com/~PCUAr',
			'hi' => 'bitrix24.in/~PJIOO',
			'ru' => 'bitrix24.ru/~PzxbH',
			'ua' => 'bitrix24.ua/~OrCqM',
			'by' => 'bitrix24.by/~wuftW',
			'kz' => 'bitrix24.kz/~3GxS9',
		];

		if (!Loader::includeModule('bitrix24'))
		{
			return $mapPortalZoneToLink['com'];
		}

		$portalZone = \CBitrix24::getPortalZone();

		return $mapPortalZoneToLink[$portalZone] ?? $mapPortalZoneToLink['com'];
	}

	protected function getLogoForCustomerSection(): string
	{
		if ($this->portalZone === 'ru')
		{
			return $this->buildUrlToImage(self::LOGO_CUSTOMER_RU_PNG);
		}

		return $this->buildUrlToImage(self::LOGO_CUSTOMER_EN_PNG);
	}

	protected function buildUrlToImage(string $name): string
	{
		$baseUrl = $this->baseUrlToLogo ?? $this->baseUrl;

		return "{$baseUrl}/bitrix/images/disk/{$name}";
	}
}