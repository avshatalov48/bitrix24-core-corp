<?php
class CCrmExternalSaleHelper
{
	public static function PrepareListItems(): array
	{
		static $result;

		if ($result === null)
		{
			$result = [];

			$rsSaleSettings = CCrmExternalSale::GetList(
				[
					'NAME' => 'ASC',
					'SERVER' => 'ASC',
				],
				[
					'ACTIVE' => 'Y',
				]
			);
			while ($arSaleSetting = $rsSaleSettings->Fetch())
			{
				$saleSettingsID = $arSaleSetting['ID'];
				$saleSettingName = isset($arSaleSetting['NAME']) ? (string)$arSaleSetting['NAME'] : '';
				if (!isset($saleSettingName[0]) && isset($arSaleSetting['SERVER']))
				{
					$saleSettingName = $arSaleSetting['SERVER'];
				}

				if (!isset($saleSettingName[0]))
				{
					$saleSettingName = $saleSettingsID;
				}
				$result[$saleSettingsID] = $saleSettingName;
			}
		}

		return $result;
	}
}
