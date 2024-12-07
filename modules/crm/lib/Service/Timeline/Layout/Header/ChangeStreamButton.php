<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Header;

use Bitrix\Crm\Service\Timeline\Layout\Base;
use Bitrix\Crm\Service\Timeline\Layout\Mixin\Actionable;
use Bitrix\Main\Localization\Loc;

class ChangeStreamButton extends Base
{
	use Actionable;

	public const TYPE_COMPLETE = 'complete';
	public const TYPE_PIN = 'pin';
	public const TYPE_UNPIN = 'unpin';

	protected bool $disableIfReadonly = false;
	protected string $type;
	protected ?string $title = null;

	public function getType(): string
	{
		return $this->type;
	}

	public function setType(string $type): self
	{
		$this->type = $type;

		return $this;
	}

	public function setTypeComplete(): self
	{
		$this->setType(self::TYPE_COMPLETE);

		return $this;
	}

	public function setTypePin(): self
	{
		$this->setType(self::TYPE_PIN);
		$this->setTitle(self::getPinTitle());

		return $this;
	}

	public function setTypeUnpin(): self
	{
		$this->setType(self::TYPE_UNPIN);
		$this->setTitle(self::getUnpinTitle());

		return $this;
	}

	public function getDisableIfReadonly(): ?bool
	{
		return $this->disableIfReadonly;
	}

	public function setDisableIfReadonly(?bool $disableIfReadonly = true): self
	{
		$this->disableIfReadonly = $disableIfReadonly;

		return $this;
	}

	public function getTitle(): ?string
	{
		return $this->title;
	}

	public function setTitle(?string $title): self
	{
		$this->title = $title;

		return $this;
	}

	public static function getPinTitle(): string
	{
		return Loc::getMessage('CRM_TIMELINE_MENU_FASTEN');
	}

	public static function getUnpinTitle(): string
	{
		return Loc::getMessage('CRM_TIMELINE_MENU_UNFASTEN');
	}

	public function toArray(): array
	{
		return [
			'type' => $this->getType(),
			'title' => $this->getTitle(),
			'action' => $this->getAction(),
			'disableIfReadonly' => $this->getDisableIfReadonly(),
		];
	}
}
