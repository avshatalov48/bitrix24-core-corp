<?php

namespace Bitrix\Crm\ItemMiniCard\Builder;

use Bitrix\Crm\ItemMiniCard\Contract\Provider;
use Bitrix\Crm\ItemMiniCard\Layout\Avatar\AbstractAvatar;
use Bitrix\Crm\ItemMiniCard\Layout\Control\AbstractControl;
use Bitrix\Crm\ItemMiniCard\Layout\Field\AbstractField;
use Bitrix\Crm\ItemMiniCard\Layout\FooterNote\AbstractFooterNote;
use Bitrix\Crm\ItemMiniCard\MiniCard;

final class MiniCardBuilder
{
	private ?string $id = null;
	private ?string $title = null;
	private ?AbstractAvatar $avatar = null;

	/** @var AbstractControl[] $controls */
	private array $controls = [];

	/** @var AbstractField[] */
	private array $fields = [];

	/** @var AbstractFooterNote[] */
	private array $footerNotes = [];

	public function setId(?string $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function setTitle(string $title): self
	{
		$this->title = $title;

		return $this;
	}

	public function setAvatar(AbstractAvatar $avatar): self
	{
		$this->avatar = $avatar;

		return $this;
	}

	/**
	 * @param AbstractControl[] $controls
	 * @return $this
	 */
	public function setControls(array $controls): self
	{
		$this->controls = $this->normalizeLayoutElements($controls, AbstractControl::class);

		return $this;
	}

	/**
	 * @param AbstractField[] $fields
	 * @return $this
	 */
	public function setFields(array $fields): self
	{
		$this->fields = $this->normalizeLayoutElements($fields, AbstractField::class);

		return $this;
	}

	/**
	 * @param AbstractFooterNote[] $footerNotes
	 * @return $this
	 */
	public function setFooterNotes(array $footerNotes): self
	{
		$this->footerNotes = $this->normalizeLayoutElements($footerNotes, AbstractFooterNote::class);

		return $this;
	}

	private function normalizeLayoutElements(array $elements, string $parentClassName): array
	{
		$elements = array_filter($elements, static fn (mixed $field) => $field instanceof $parentClassName);

		return array_values($elements);
	}

	public function build(): ?MiniCard
	{
		if ($this->title === null || $this->avatar === null)
		{
			return null;
		}

		return new MiniCard(
			$this->id,
			$this->title,
			$this->avatar,
			$this->controls,
			$this->fields,
			$this->footerNotes,
		);
	}

	public function useProvider(Provider $provider): self
	{
		return $this
			->setId($provider->provideId())
			->setTitle($provider->provideTitle())
			->setAvatar($provider->provideAvatar())
			->setControls($provider->provideControls())
			->setFields($provider->provideFields())
			->setFooterNotes($provider->provideFooterNotes());
	}
}
