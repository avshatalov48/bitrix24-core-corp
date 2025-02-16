<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Common;

use Bitrix\Crm\Timeline\Entity\CustomIconTable;
use Bitrix\Crm\Timeline\Entity\Object\CustomIcon;
use ReflectionClass;

class Icon
{
	protected ?CustomIconTable $customIconTable;
	protected ?string $code = null;
	protected ?CustomIcon $customIcon = null;

	//region IconCodes
	public const CALL = 'call';
	public const CALL_INCOMING_MISSED = 'call-incoming-missed';
	public const CALL_INCOMING = 'call-incoming';
	public const CALL_COMPLETED = 'call-completed';
	public const CALL_OUTCOMING = 'call-outcoming';
	public const DOCUMENT = 'document';
	public const IM = 'IM';
	public const CIRCLE_CHECK = 'circle-check';
	public const INFO = 'info';
	public const RELATION = 'relation';
	public const LINK = 'link';
	public const UNLINK = 'unlink';
	public const STORE = 'store';
	public const VIEW = 'view';
	public const STAGE_CHANGE = 'stage-change';
	public const SUM = 'sum';
	public const OPENLINE_INCOMING_MESSAGE = 'open-line-incoming-message';
	public const CLOCK = 'clock';
	public const MAIL_OUTCOME = 'mail-outcome';
	public const COMPLETE = 'complete';
	public const CHECK = 'check';
	public const COMMENT = 'comment';
	public const SMS = 'sms';
	public const WHATSAPP = 'whatsapp';
	public const TASK = 'task';
	public const PIPELINE = 'pipeline';
	public const WALLET = 'wallet';
	public const ATTENTION = 'attention';
	public const BANK_CARD = 'bank-card';
	public const CONVERSION = 'conversion';
	public const CALENDAR = 'calendar';
	public const CIRCLE_CROSSED = 'circle-crossed';
	public const TASK_PING = 'task-ping';
	public const TASK_NEW_COMMENT = 'task-new-comment';
	public const TASK_VIEWED_COMMENT = 'task-viewed-comment';
	public const TASK_ACTIVITY = 'task-activity';
	public const AI_COPILOT = 'ai-copilot';
	public const AI_PROCESS = 'ai-process';
	public const RESTORATION = 'restoration';
	public const ARROW_UP = 'arrow-up';
	public const ARROW_DOWN = 'arrow-down';
	public const VISIT = 'visit';
	public const CAMERA = 'camera';
	public const COPY = 'copy';
	public const ROBOT = 'robot';

	public const CYCLE_EQUAL = 'cycle-equal';
	public const BIZPROC = 'bizproc';
	public const MESSAGE_WITH_POINT = 'message-with-point';

	public const BOOKING = 'booking';
	//endregion

	public static function getSystemIcons(): array
	{
		// @todo starting with PHP 8.0 can use ReflectionClassConstant::IS_PUBLIC to filter constants
		return (new ReflectionClass(__CLASS__))->getConstants();
	}

	public static function initFromCode(string $code): self
	{
		return (new self())
			->setCode($code);
	}

	public static function initFromObject(CustomIcon $customIcon): self
	{
		return (new self())
			->setCustomIcon($customIcon)
		;
	}

	public function __construct()
	{
		$this->customIconTable = new CustomIconTable();
	}

	public function getData(): ?array
	{
		if ($this->isSystem())
		{
			$fileUri = '';
		}
		else
		{
			$customIcon = $this->getCustomIcon();
			if (!$customIcon)
			{
				return null;
			}

			$fileUri = ($this->getCustomIconFileUri($customIcon) ?? '');
		}

		return [
			'code' => $this->getCode(),
			'isSystem' => $this->isSystem(),
			'fileUri' => $fileUri,
		];
	}

	protected function isSystem(): bool
	{
		return in_array($this->getCode(), self::getSystemIcons(), true);
	}

	protected function getCustomIcon(): ?CustomIcon
	{
		return $this->customIconTable::getByCode($this->getCode());
	}

	protected function getCustomIconFileUri(CustomIcon $customIcon): ?string
	{
		return \CFile::GetPath($customIcon->getFileId());
	}

	protected function getCode(): string
	{
		if ($this->customIcon)
		{
			return $this->customIcon->getCode();
		}

		return $this->code;
	}

	protected function setCode(string $code): self
	{
		$this->code = $code;

		return $this;
	}

	protected function setCustomIcon(CustomIcon $customIcon): self
	{
		$this->customIcon = $customIcon;

		return $this;
	}
}
