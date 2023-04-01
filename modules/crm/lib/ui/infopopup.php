<?php

namespace Bitrix\Crm\UI;

use Bitrix\Main\Type\Contract\Arrayable;

class InfoPopup implements Arrayable
{
	protected ?string $title = null;
	protected ?string $subtitle = null;
	protected ?string $icon = null;
	protected array $tableFields = [];

	public function getIcon(): ?string
	{
		return $this->icon;
	}

	public function setIcon(string $icon): self
	{
		$this->icon = $icon;

		return $this;
	}

	public function getTitle(): ?string
	{
		return $this->title;
	}

	public function setTitle(string $title): self
	{
		$this->title = $title;

		return $this;
	}

	public function getSubtitle(): ?string
	{
		return $this->subtitle;
	}

	public function setSubtitle(string $subtitle): self
	{
		$this->subtitle = $subtitle;

		return $this;
	}

	public function addLinkField(string $title, string $text, $href): self
	{
		$linkBlock = $this->createLinkBlock($text, $href);

		return $this->addMixedField($title, $linkBlock);
	}

	public function addTextField(string $title, string $text): self
	{
		$textBlock = $this->createTextBlock($text);

		return $this->addMixedField($title, $textBlock);
	}

	public function addMoneyField(string $title, float $opportunity, string $currencyId)
	{
		$moneyBlock = $this->createMoneyBlock($opportunity, $currencyId);

		return $this->addMixedField($title, $moneyBlock);
	}

	public function addPhoneField(string $title, string $text, string $phone, bool $canPerformCalls)
	{
		$phoneBlock = $this->createPhoneBlock($text, $phone, $canPerformCalls);

		return $this->addMixedField($title, $phoneBlock);
	}

	public function createLinkBlock($text, $href): array
	{
		return [
			'type' => 'link',
			'content' => $text,
			'attributes' => [
				'href' => $href,
			],
		];
	}

	public function createTextBlock($text): array
	{
		return [
			'type' => 'text',
			'content' => $text,
		];
	}

	public function createMoneyBlock(float $opportunity, string $currencyId): array
	{
		return [
			'type' => 'money',
			'attributes' => [
				'opportunity' => $opportunity,
				'currencyId' => $currencyId,
			],
		];
	}

	public function createPhoneBlock(string $text, string $phone, bool $canPerformCalls): array
	{
		return [
			'type' => 'phone',
			'content' => $text,
			'attributes' => [
				'phone' => $phone,
				'canPerformCalls' => $canPerformCalls,
			],
		];
	}

	public function addMixedField(string $title, array $blocks): self
	{
		$this->tableFields[] = [
			'title' => $title,
			'contentBlock' => $blocks,
		];

		return $this;
	}

	public function toArray(): array
	{
		return [
			'header' => [
				'title' => $this->title,
				'subtitle' => $this->subtitle,
				'icon' => $this->icon,
			],
			'tableFields' => $this->tableFields,
		];
	}
}
