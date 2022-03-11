<?php
namespace Bitrix\ImConnector\Connectors;

use Bitrix\Main\Localization\Loc;

/**
 * Class Avito
 * @package Bitrix\ImConnector\Connectors
 */
class Avito extends Base
{
	//Input
	/**
	 * @param array $chat
	 * @return array
	 */
	protected function processingChat(array $chat): array
	{
		if (!empty($chat['url']))
		{
			$chat['description'] = Loc::getMessage(
				'IMCONNECTOR_LINK_TO_AVITO_AD',
				[
					'#LINK#' => $chat['url']
				]
			);

			unset($chat['url']);
		}

		return $chat;
	}
	//END Input
}