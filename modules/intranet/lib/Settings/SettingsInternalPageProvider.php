<?php

namespace Bitrix\Intranet\Settings;

use \Bitrix\Main;

final class SettingsInternalPageProvider implements SettingsPageProviderInterface
{
	private string $type;
	private int $sort = 100;
	private ?string $title;

	protected function __construct(string $type, ?string $title = null)
	{
		$this->type = $type;
		if (!empty($title))
		{
			$this->title = $title;
		}
	}

	public function setSort(int $sort): static
	{
		$this->sort = $sort;

		return $this;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getSort(): int
	{
		return $this->sort;
	}

	public function getTitle(): string
	{
		return $this->title ?? $this->type;
	}

	public function getDataManager(array $data = []): SettingsInterface
	{
		$settingsClassName = __NAMESPACE__ . '\\'. ucwords($this->type) . 'Settings';

		if (class_exists($settingsClassName))
		{
			//@var SettingsInterface $internalSettings
			$internalSettings = new $settingsClassName();
			if ($internalSettings->getType() === $this->type)
			{
				return $internalSettings;
			}
		}

		throw new Main\ArgumentOutOfRangeException($settingsClassName);
	}

	static public function createFromType(string $type, ?string $title = null, int $sort = 100): static
	{
		// TODO check inherit origin
		return (new self($type, $title))->setSort($sort);
	}
}