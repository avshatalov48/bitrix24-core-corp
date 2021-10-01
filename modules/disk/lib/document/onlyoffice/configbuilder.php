<?php

namespace Bitrix\Disk\Document\OnlyOffice;

use Bitrix\Disk\Document\Language;
use Bitrix\Disk\Document\OnlyOffice\Models\DocumentSession;
use Bitrix\Disk\File;
use Bitrix\Disk\User;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Context;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\JWT;
use Bitrix\Main\Web\Uri;

final class ConfigBuilder
{
	public const DOCUMENT_TYPE_WORD = 'word';
	public const DOCUMENT_TYPE_CELL = 'cell';
	public const DOCUMENT_TYPE_SLIDE = 'slide';

	public const REVIEW_DISPLAY_MARKUP = 'markup';
	public const REVIEW_DISPLAY_FINAL = 'final';
	public const REVIEW_DISPLAY_ORIGINAL = 'original';

	public const MODE_EDIT = 'edit';
	public const MODE_VIEW = 'view';

	/** @var File */
	protected $file;
	/** @var string */
	protected $fileExtension;
	/** @var DocumentSession */
	protected $documentSession;
	/** @var User */
	protected $user;
	/** @var string */
	protected $mode = self::MODE_VIEW;
	/** @var Uri */
	protected $callbackUrl;
	/** @var Uri */
	protected $documentUrl;
	/** @var Uri */
	protected $baseUrlToLogo;
	/** @var int */
	protected $height = 600;
	/** @var array */
	protected $permissions = [];

	/**
	 * ConfigBuilder constructor.
	 * @param DocumentSession $documentSession
	 * @throws ArgumentOutOfRangeException
	 */
	public function __construct(DocumentSession $documentSession)
	{
		$this
			->setDocumentSession($documentSession)
		;

		if (((int)$this->documentSession->getType()) === DocumentSession::TYPE_EDIT)
		{
			$this->setMode(self::MODE_EDIT);
		}
	}

	public function setMode(string $mode): self
	{
		if (!in_array($mode, [self::MODE_EDIT, self::MODE_VIEW], true))
		{
			throw new ArgumentOutOfRangeException('mode');
		}

		$this->mode = $mode;

		return $this;
	}

	public function getMode(): string
	{
		return $this->mode;
	}

	public function isViewMode(): bool
	{
		return $this->getMode() === self::MODE_VIEW;
	}

	public function allowEdit(bool $allowed): self
	{
		$this->permissions['edit'] = $allowed;

		return $this;
	}

	public function allowRename(bool $allowed): self
	{
		$this->permissions['rename'] = $allowed;

		return $this;
	}

	public function allowDownload(bool $allowed): self
	{
		$this->permissions['download'] = $allowed;

		return $this;
	}

	public function setDocumentSession(DocumentSession $documentSession): self
	{
		$this->documentSession = $documentSession;
		$this->fileExtension = mb_strtolower(getFileExtension($documentSession->getFilename()));

		return $this;
	}

	public function setUser(User $user): self
	{
		$this->user = $user;

		return $this;
	}

	public function setCallbackUrl(Uri $callbackUrl): self
	{
		$this->callbackUrl = $callbackUrl;

		return $this;
	}

	public function setDocumentUrl(Uri $documentUrl): self
	{
		$this->documentUrl = $documentUrl;

		return $this;
	}

	public function setBaseUrlToLogo(Uri $url): self
	{
		$this->baseUrlToLogo = $url;

		return $this;
	}

	public function setHeight(int $height): self
	{
		$this->height = $height;

		return $this;
	}

	public function getDocumentType(): string
	{
		//https://api.onlyoffice.com/editors/config

		$text = [
			'doc',
			'docm',
			'docx',
			'dot',
			'dotm',
			'dotx',
			'epub',
			'fodt',
			'htm',
			'html',
			'mht',
			'odt',
			'ott',
			'pdf',
			'rtf',
			'txt',
			'djvu',
			'xps',
		];
		$spreadsheet = [
			'csv',
			'fods',
			'ods',
			'ots',
			'xls',
			'xlsm',
			'xlsx',
			'xlt',
			'xltm',
			'xltx',
		];
		$presentation = [
			'fodp',
			'odp',
			'otp',
			'pot',
			'potm',
			'potx',
			'pps',
			'ppsm',
			'ppsx',
			'ppt',
			'pptm',
			'pptx',
		];

		if (in_array($this->fileExtension, $text, true))
		{
			return self::DOCUMENT_TYPE_WORD;
		}
		if (in_array($this->fileExtension, $spreadsheet, true))
		{
			return self::DOCUMENT_TYPE_CELL;
		}
		if (in_array($this->fileExtension, $presentation, true))
		{
			return self::DOCUMENT_TYPE_SLIDE;
		}

		return self::DOCUMENT_TYPE_WORD;
	}

	private function supportPrint(): bool
	{
		return true;
	}

	public function build()
	{
		$editorOptions = [
			'documentType' => $this->getDocumentType(),
			'height' => $this->height . 'px',
			'document' => [
				'fileType' => $this->fileExtension,
				'key' => $this->documentSession->getExternalHash(),
				'title' => $this->documentSession->getFilename(),
				'url' => (string)$this->documentUrl,
				'owner' => '',
				'uploaded' => '',
				'permissions' => [
					'print' => $this->supportPrint(),
					'download' => $this->permissions['download'] ?? true,
					'copy' => true,
					'edit' => $this->permissions['edit'] ?? false,
					'rename' => $this->permissions['rename'] ?? false,
					'review' => true,
					'comment' => true,
				],
			],
			'editorConfig' => [
				'user' => [
					'id' => (string)$this->user->getId(),
					'name' => $this->user->getFormattedName(),
				],
				'lang' => $this->getLangCode(),
				'region' => $this->getRegion(),
				'mode' => $this->mode,
				'callbackUrl' => (string)$this->callbackUrl,
				'customization' => [
					'forcesave' => true,
					// 'loaderName' => 'Bitrix24',
					'customer' => [
						'address' => '',
						'info' => Loc::getMessage('DISK_ONLYOFFICE_CONFIGBUILDER_CUSTOMER_INFO'),
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
					'reviewDisplay' => self::REVIEW_DISPLAY_MARKUP,
					'chat' => false,
					'hideRightMenu' => true,
					'compactHeader' => true,
					'goback' => false,
					'plugins' => false,
				],
			],
			'events' => [],
		];

		if (!Application::getInstance()->isUtfMode())
		{
			$editorOptions = Encoding::convertEncoding($editorOptions, SITE_CHARSET, 'UTF-8');
		}

		$editorOptions['token'] = JWT::encode($editorOptions, $this->getSecretKey());

		return $editorOptions;
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

	protected function getLangCode(): string
	{
		//https://helpcenter.onlyoffice.com/ru/installation/docs-available-languages.aspx
		$countryCode = Context::getCurrent()->getLanguage();

		return Language::getIso639Code($countryCode) ?: $countryCode;
	}

	protected function getRegion(): string
	{
		$acceptedLanguages = Context::getCurrent()->getRequest()->getAcceptedLanguages();

		return array_shift($acceptedLanguages) ?: 'en-US';
	}

	protected function getLogoForCustomerSection(): string
	{
		$countryCode = Context::getCurrent()->getLanguage();
		if ($countryCode === 'ru')
		{
			return $this->buildUrlToImage('disk_doceditor_logo_customer_ru.png');
		}

		return $this->buildUrlToImage('disk_doceditor_logo_customer_en.png');
	}

	protected function buildUrlToImage(string $name): string
	{
		$baseUrl = $this->baseUrlToLogo;
		if (!$baseUrl)
		{
			$baseUrl = new Uri(UrlManager::getInstance()->getHostUrl());
		}

		return "{$baseUrl}/bitrix/images/disk/{$name}";
	}

	protected function getSecretKey(): string
	{
		return ServiceLocator::getInstance()->get('disk.onlyofficeConfiguration')->getSecretKey();
	}
}
