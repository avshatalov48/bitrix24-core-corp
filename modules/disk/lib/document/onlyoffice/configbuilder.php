<?php

namespace Bitrix\Disk\Document\OnlyOffice;

use Bitrix\Disk\Document\OnlyOffice\Models\DocumentSession;
use Bitrix\Disk\File;
use Bitrix\Disk\User;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\JWT;
use Bitrix\Main\Web\Uri;

final class ConfigBuilder
{
	public const DOCUMENT_TYPE_WORD = 'word';
	public const DOCUMENT_TYPE_CELL = 'cell';
	public const DOCUMENT_TYPE_SLIDE = 'slide';

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

	public function allowEdit(bool $allowed): self
	{
		$this->permissions['edit'] = $allowed;

		return $this;
	}

	public function setDocumentSession(DocumentSession $documentSession): self
	{
		$this->documentSession = $documentSession;
		$this->fileExtension = getFileExtension($documentSession->getFilename());

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
					'print' => true,
					'download' => true,
					'copy' => true,
					'edit' => $this->permissions['edit'] ?? false,
					'rename' => false,
					'review' => true,
					'comment' => true,
				],
			],
			'editorConfig' => [
				'user' => [
					'id' => $this->user->getId(),
					'name' => $this->user->getFormattedName(),
				],
				'lang' => Context::getCurrent()->getLanguage(),
				'region' => 'en-Us',
				'mode' => $this->mode,
				'callbackUrl' => (string)$this->callbackUrl,
				'customization' => [
					'logo' => [
						'image' => $this->buildUrlToImage('disk_doceditor_logo.png?1'),
						'imageEmbedded' => $this->buildUrlToImage('disk_doceditor_logo_embed.png?1'),
						'url' => 'https://bitrix24.com',
					],
					'chat' => false,
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

	protected function buildUrlToImage(string $name): string
	{
		$hostUrl = UrlManager::getInstance()->getHostUrl();

		return $hostUrl . '/bitrix/images/disk/' . $name;
	}

	protected function getSecretKey(): string
	{
		return OnlyOfficeHandler::getSecretKey();
	}
}