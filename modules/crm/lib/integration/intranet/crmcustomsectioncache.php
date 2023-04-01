<?php

namespace Bitrix\Crm\Integration\Intranet;


final class CrmCustomSectionCache
{
	private array $data;

	public function __construct(array $data)
	{
		$this->data = $data;
	}

	public function hasBySettingsName(string $settingsName): bool
	{
		return in_array($settingsName, array_column($this->data, 'SETTINGS'));
	}

	public function getAllSettingsByCustomSectionId(int $customSectionId): array
	{
		return array_column(
			array_filter($this->data, fn ($item) => (int) $item['CUSTOM_SECTION_ID'] === $customSectionId),
			'SETTINGS'
		);
	}

	/**
	 * @param string $settingName
	 * @return int[]
	 */
	public function getAllCustomSectionIdsBySettings(string $settingName): array
	{
		return array_column(
			array_filter($this->data, fn ($item) => $item['SETTINGS'] === $settingName),
			'CUSTOM_SECTION_ID'
		);
	}
}