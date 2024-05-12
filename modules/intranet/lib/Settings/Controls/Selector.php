<?php

namespace Bitrix\Intranet\Settings\Controls;

use Bitrix\Intranet\Settings\SettingsPermission;

class Selector extends Field
{
	const TYPE = 'selector';
	private array $items;

	public function __construct(
		string $id,
		string $name,
		string $label,
		array $items,
		?string $value = null,
		private ?array $multiValue = null,
		?array $hints = null,
		?SettingsPermission $permission = null,
		bool $isEnable = true,
		?string $helpDesk = null
	)
	{
		parent::__construct($id, $name, $label, self::TYPE, $permission, $isEnable, $value, $hints, $helpDesk);
		$this->items = $items;
	}

	public function getItems(): array
	{
		return $this->items;
	}

	public function setItems(array $items): void
	{
		$this->items = $items;
	}

	public function jsonSerialize(): array
	{
		$result = [
			'inputName' => $this->getName(),
			'items' => $this->items,
			'values' => $this->items,
			'multiValue' => $this->multiValue
		];
		if (isset($this->getHints()['on']) && is_string($this->getHints()['on']))
		{
			$result['hintOn'] = $this->getHints()['on'];
		}
		if (isset($this->getHints()['off']) && is_string($this->getHints()['off']))
		{
			$result['hintOff'] = $this->getHints()['off'];
		}
		if (isset($this->getHints()['hintTitle']) && is_string($this->getHints()['hintTitle']))
		{
			$result['hintTitle'] = $this->getHints()['hintTitle'];
		}

		return array_merge(parent::jsonSerialize(), $result);
	}
}