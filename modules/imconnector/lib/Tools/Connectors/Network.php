<?php
namespace Bitrix\ImConnector\Tools\Connectors;

use Bitrix\Main\Loader;

class Network
{
	/**
	 * @param $code
	 * @return string
	 */
	public function getPublicLink($code): string
	{
		$result = '';

		if (Loader::includeModule('socialservices'))
		{
			$result = \CSocServBitrix24Net::NETWORK_URL . '/oauth/select/?preset=im&IM_DIALOG=networkLines' . $code;
		}

		return $result;
	}
}