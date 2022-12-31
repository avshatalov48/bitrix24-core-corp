<?php

namespace Bitrix\Disk\Document\OnlyOffice\Editor;

use Bitrix\Disk\Document\Language;
use Bitrix\Disk\Document\OnlyOffice\Models\DocumentSession;
use Bitrix\Disk\File;
use Bitrix\Disk\User;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Context;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\JWT;
use Bitrix\Main\Web\Uri;

final class ConfigBuilder
{
	public const VERSION = 1;

	public const DOCUMENT_TYPE_WORD = 'word';
	public const DOCUMENT_TYPE_CELL = 'cell';
	public const DOCUMENT_TYPE_SLIDE = 'slide';

	public const REVIEW_DISPLAY_MARKUP = CustomizationBuilder::REVIEW_DISPLAY_MARKUP;
	public const REVIEW_DISPLAY_FINAL = CustomizationBuilder::REVIEW_DISPLAY_FINAL;
	public const REVIEW_DISPLAY_ORIGINAL = CustomizationBuilder::REVIEW_DISPLAY_ORIGINAL;

	public const MODE_EDIT = 'edit';
	public const MODE_VIEW = 'view';

	public const VISUAL_MODE_USUAL = 2;
	public const VISUAL_MODE_COMPACT = 3;

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
	/** @var array */
	protected $permissions = [];
	/** @var array{hideRulers: bool, hideRightMenu: bool, compactHeader: bool, compactToolbar: bool} */
	protected $customization = [];

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

		if ($this->documentSession->getType() === DocumentSession::TYPE_EDIT)
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

	public function isEditMode(): bool
	{
		return $this->getMode() === self::MODE_EDIT;
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

	public function hideRulers(): self
	{
		$this->customization['hideRulers'] = true;

		return $this;
	}

	public function hideRightMenu(): self
	{
		$this->customization['hideRightMenu'] = true;

		return $this;
	}

	public function setCompactHeader(): self
	{
		$this->customization['compactHeader'] = true;

		return $this;
	}

	public function setCompactToolbar(): self
	{
		$this->customization['compactToolbar'] = true;

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

	protected function getCustomizationSection(Uri $baseUrl): array
	{
		$customizationBuilder = new CustomizationBuilder($baseUrl, $this->customization);
		$customizationBuilder->setInfoText(Loc::getMessage('DISK_ONLYOFFICE_CONFIGBUILDER_CUSTOMER_INFO'));

		if ($this->baseUrlToLogo)
		{
			$customizationBuilder->setBaseUrlToLogo($this->baseUrlToLogo);
		}

		return $customizationBuilder->build();
	}

	public function build(): array
	{
		$baseUrl = new Uri(UrlManager::getInstance()->getHostUrl());

		$editorOptions = [
			'_version' => self::VERSION,
			'documentType' => $this->getDocumentType(),
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
				'customization' => $this->getCustomizationSection($baseUrl),
			],
			'events' => [],
		];

		if (!Application::getInstance()->isUtfMode())
		{
			$editorOptions['token'] = JWT::encode(
				Encoding::convertEncoding($editorOptions, SITE_CHARSET, 'UTF-8'),
				$this->getSecretKey()
			);
		}
		else
		{
			$editorOptions['token'] = JWT::encode($editorOptions, $this->getSecretKey());
		}

		return $editorOptions;
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

	protected function getSecretKey(): string
	{
		return ServiceLocator::getInstance()->get('disk.onlyofficeConfiguration')->getSecretKey();
	}
}