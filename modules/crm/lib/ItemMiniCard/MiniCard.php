<?php

namespace Bitrix\Crm\ItemMiniCard;

use Bitrix\Crm\ItemMiniCard\Builder\MiniCardBuilder;
use Bitrix\Crm\ItemMiniCard\Layout\Avatar\AbstractAvatar;
use Bitrix\Crm\ItemMiniCard\Layout\Control\AbstractControl;
use Bitrix\Crm\ItemMiniCard\Layout\Field\AbstractField;
use Bitrix\Crm\ItemMiniCard\Layout\FooterNote\AbstractFooterNote;
use Bitrix\Main\Type\Contract\Arrayable;
use JsonSerializable;

final class MiniCard implements Arrayable, JsonSerializable
{
	public function __construct(
		public ?string $id,
		public string $title,
		public AbstractAvatar $avatar,
		/** @var AbstractControl[] $controls */
		public array $controls = [],
		/** @var AbstractField[] $fields */
		public array $fields = [],
		/** @var AbstractFooterNote[] $footerNotes */
		public array $footerNotes = [],
	)
	{
	}

	public static function builder(): MiniCardBuilder
	{
		return new MiniCardBuilder();
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'title' => $this->title,
			'avatar' => $this->avatar->toArray(),
			'controls' => array_map(static fn (AbstractControl $control) => $control->toArray(), $this->controls),
			'fields' => array_map(static fn (AbstractField $field) => $field->toArray(), $this->fields),
			'footerNotes' => array_map(static fn (AbstractFooterNote $note) => $note->toArray(), $this->footerNotes),
		];
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
