<?php

namespace Bitrix\BIConnector\ExternalSource\Internal;

class ExternalSourceSettingsCollection extends EO_ExternalSourceSettings_Collection
{
	public function getEntityByCode(string $code): ?EO_ExternalSourceSettings
	{
		$settings = $this->getAll();
		foreach ($settings as $setting)
		{
			if ($setting->getCode() === $code)
			{
				return $setting;
			}
		}

		return null;
	}

	public function getValueByCode(string $code)
	{
		return $this->getEntityByCode($code)?->getValue();
	}
}
