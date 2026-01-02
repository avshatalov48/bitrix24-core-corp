<?php
namespace Bitrix\ImConnector\Connectors;

use Bitrix\Main\Localization\Loc;


class Avito extends Base
{
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
}