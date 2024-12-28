<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Common;

use Bitrix\Crm\Service\Timeline\Layout\Body;
use Bitrix\Crm\Timeline\Entity\CustomLogoTable;
use Bitrix\Crm\Timeline\Entity\Object\CustomLogo;
use ReflectionClass;

class Logo
{
	protected ?CustomLogoTable $customLogoTable;
	protected string $code = '';

	//region LogoCodes
	public const CALL_PLAY_RECORD = 'call-play-record';
	public const CALL_DEFAULT = 'call-default';
	public const CALL_INCOMING = 'call-incoming';
	public const CALL_OUTGOING = 'call-outgoing';
	public const DOCUMENT = 'document';
	public const DOCUMENT_PRINT = 'document-print';
	public const DOCUMENT_SIGNED = 'document-signed';
	public const DOCUMENT_DRAFT = 'document-draft';
	public const CHANNEL_CHAT = 'channel-chat';
	public const CHANNEL_AVITO = 'channel-avito';
	public const CHANNEL_APPLE = 'channel-apple';
	public const CHANNEL_FACEBOOK = 'channel-fb';
	public const CHANNEL_FACEBOOK_CHAT = 'channel-fb-chat';
	public const CHANNEL_INSTAGRAM_DIRECT = 'channel-instagram-direct';
	public const CHANNEL_BITRIX = 'channel-bitrix';
	public const CHANNEL_ODNOKLASSNIKI = 'channel-ok';
	public const CHANNEL_TELEGRAM = 'channel-telegram';
	public const CHANNEL_VIBER = 'channel-viber';
	public const CHANNEL_VK = 'channel-vk';
	public const CHANNEL_VK_ORDER = 'channel-vk-order';
	public const CHANNEL_WHATSAPP_BITRIX = 'channel-whatsapp-bitrix';
	public const CHANNEL_EDNA = 'channel-edna';
	public const CHANNEL_WHATSAPP = 'channel-whatsapp';
	public const MAIL_OUTCOME = 'mail-outcome';
	public const LIST_CHECK = 'list-check';
	public const SHOP = 'shop';
	public const NOTIFICATION = 'notification';
	public const COMMENT = 'comment';
	public const SMS = 'sms';
	public const SHOP_EYE = 'shop-eye';
	public const BANK_CARD = 'bank-card';
	public const CALENDAR_SHARE = 'calendar-share';
	public const UNREAD_COMMENT = 'unread-comment';
	public const TASK_ACTIVITY = 'task-activity';
	public const AI_COPILOT = 'ai-copilot';
	public const ZOOM = 'zoom';

	public const BIZPROC = 'bizproc';
	public const BIZPROC_TASK = 'bizproc-task';
	//endregion

	public static function getInstance(string $code): self
	{
		return new self($code);
	}

	public function __construct(string $code)
	{
		$this->code = $code;
		$this->customLogoTable = new CustomLogoTable();
	}

	public function createLogo(): ?Body\Logo
	{
		if ($this->isSystem())
		{
			return $this->createLogoInstanceForSystemCode();
		}

		return $this->createLogoInstanceForCustomCode();
	}

	public function isSystem(): bool
	{
		return in_array($this->getCode(), self::getSystemLogoCodes(), true);
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public static function getSystemLogoCodes(): array
	{
		// @todo starting with PHP 8.0 can use ReflectionClassConstant::IS_PUBLIC to filter constants
		return (new ReflectionClass(__CLASS__))->getConstants();
	}

	protected function createLogoInstanceForSystemCode(): ?Body\Logo
	{
		switch ($this->getCode())
		{
			case self::CALL_PLAY_RECORD:
			case self::DOCUMENT:
			case self::DOCUMENT_PRINT:
			case self::DOCUMENT_SIGNED:
			case self::DOCUMENT_DRAFT:
			case self::MAIL_OUTCOME:
				return (new Body\Logo($this->getCode()));

			case self::CALL_INCOMING:
			case self::CALL_DEFAULT:
			case self::CALL_OUTGOING:
			case self::CHANNEL_CHAT:
			case self::CHANNEL_AVITO:
			case self::CHANNEL_APPLE:
			case self::CHANNEL_FACEBOOK:
			case self::CHANNEL_FACEBOOK_CHAT:
			case self::CHANNEL_INSTAGRAM_DIRECT:
			case self::CHANNEL_BITRIX:
			case self::CHANNEL_ODNOKLASSNIKI:
			case self::CHANNEL_TELEGRAM:
			case self::CHANNEL_VIBER:
			case self::CHANNEL_VK:
			case self::CHANNEL_VK_ORDER:
			case self::CHANNEL_WHATSAPP:
			case self::CHANNEL_WHATSAPP_BITRIX:
			case self::CHANNEL_EDNA:
			case self::LIST_CHECK:
			case self::SHOP:
			case self::NOTIFICATION:
			case self::COMMENT:
			case self::SMS:
			case self::SHOP_EYE:
			case self::BANK_CARD:
			case self::CALENDAR_SHARE:
			case self::UNREAD_COMMENT:
			case self::TASK_ACTIVITY:
			case self::AI_COPILOT:
			case self::ZOOM:
			case self::BIZPROC:
			case self::BIZPROC_TASK:
				return (new Body\Logo($this->getCode()))
					->setInCircle(true)
				;
		}

		return null;
	}

	protected function createLogoInstanceForCustomCode(): ?Body\Logo
	{
		$customLogo = $this->getCustomLogo();
		if (!$customLogo)
		{
			return null;
		}

		return (new Body\Logo($this->getCode()))
			->setBackgroundUrl($customLogo->getFileUri())
			->setBackgroundSize()
		;
	}

	protected function getCustomLogo(): ?CustomLogo
	{
		return $this->customLogoTable::getByCode($this->getCode());
	}
}
