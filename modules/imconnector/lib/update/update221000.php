<?php
namespace Bitrix\Imconnector\Update;

use Bitrix\Main\Loader,
	Bitrix\Main\Config\Option;

class Update221000
{
	private const MODULE_ID = 'imconnector';
	private const OPTION_ID = 'uri_client';

	/**
	 * @return string
	 */
	public static function checkPublicUrl(): string
	{
		if (
			!Loader::includeModule('bitrix24')
			&& Loader::includeModule(self::MODULE_ID)
		)
		{
			$publicUrl = Option::getRealValue(self::MODULE_ID, self::OPTION_ID, '');
			if (!empty($publicUrl))
			{
				$checkResult = \Bitrix\ImConnector\Connector::checkPublicUrl($publicUrl, false);
				if (!$checkResult->isSuccess())
				{
					if (Loader::includeModule('imbot'))
					{
						$publicUrl = Option::get('imbot', 'portal_url', '');
						if (!empty($publicUrl))
						{
							$checkResult = \Bitrix\ImConnector\Connector::checkPublicUrl($publicUrl, false);
							if ($checkResult->isSuccess())
							{
								Option::set(self::MODULE_ID, self::OPTION_ID, $publicUrl);
							}
						}
					}
					if (!$checkResult->isSuccess() && Loader::includeModule('imopenlines'))
					{
						$publicUrl = Option::get('imopenlines', 'portal_url', '');
						if (!empty($publicUrl))
						{
							$checkResult = \Bitrix\ImConnector\Connector::checkPublicUrl($publicUrl, false);
							if ($checkResult->isSuccess())
							{
								Option::set(self::MODULE_ID, self::OPTION_ID, $publicUrl);
							}
						}
					}
				}
			}
		}

		return '';
	}
}