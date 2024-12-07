<?php

namespace Bitrix\Crm\Activity\ToDo\ColorSettings;

final class ColorSettingsProvider
{
	private const DEFAULT_COLOR_ID = 'default';

	private bool $readOnlyMode = false;


	public function __construct(private readonly ?string $colorId = null)
	{

	}

	public static function getDefaultColorId(): string
	{
		return self::DEFAULT_COLOR_ID;
	}

	public function setReadOnlyMode(bool $readOnlyMode): ColorSettingsProvider
	{
		$this->readOnlyMode = $readOnlyMode;

		return $this;
	}

	public function fetchForJsComponent(): array
	{
		return [
			'valuesList' =>  $this->getDefaultColorsList(),
			'selectedValueId' => $this->colorId ?? self::getDefaultColorId(),
			'readOnlyMode' => $this->readOnlyMode,
		];
	}

	public function isAvailableColorId(string $colorId): bool
	{
		foreach ($this->getDefaultColorsList() as $color)
		{
			if ($color['id'] === $colorId)
			{
				return true;
			}
		}

		return false;
	}

	public function getByColorId(string $colorId): ?array
	{
		foreach ($this->getDefaultColorsList() as $color)
		{
			if ($color['id'] === $colorId)
			{
				return $color;
			}
		}

		return null;
	}

	private function getDefaultColorsList(): array
	{
		return [
			[
				'id' => self::getDefaultColorId(),
				'color' => '#FFC34D',
				'iconBackground' => '#FFC34D',
				'itemBackground' => '#FEFCEE',
				'logoBackground' => '#FFF1D6',
			],
			[
				'id' => '1',
				'color' => '#4090C7',
				'iconBackground' => '#4090C7',
				'itemBackground' => '#F4FCFE',
				'logoBackground' => '#E5F9FF',
			],
			[
				'id' => '2',
				'color' => '#29D9D0',
				'iconBackground' => '#29D9D0',
				'itemBackground' => '#F2FFFE',
				'logoBackground' => '#DAFFFC',
			],
			[
				'id' => '3',
				'color' => '#F57E02',
				'iconBackground' => '#F57E02',
				'itemBackground' => '#FFF7EF',
				'logoBackground' => '#FFEEDD',
			],
			[
				'id' => '4',
				'color' => '#8FB035',
				'iconBackground' => '#8FB035',
				'itemBackground' => '#FAFDED',
				'logoBackground' => '#F1FBD0',
			],
			[
				'id' => '5',
				'color' => '#B37DC4',
				'iconBackground' => '#B37DC4',
				'itemBackground' => '#FCFAFF',
				'logoBackground' => '#F7EFFF',
			],
			[
				'id' => '6',
				'color' => '#858F9E',
				'iconBackground' => '#858F9E',
				'itemBackground' => '#FCFCFD',
				'logoBackground' => '#ECEDEE',
			],
			[
				'id' => '7',
				'color' => '#DA7790',
				'iconBackground' => '#DA7790',
				'itemBackground' => '#FFF8FB',
				'logoBackground' => '#FFE9F3',
			],
		];
	}
}
