<?php
namespace Bitrix\Imconnector\Update;

use Bitrix\ImConnector\Model\StatusConnectorsTable;

use Bitrix\ImOpenLines\Model\ConfigTable;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImBot\Bot\Support24;

use Bitrix\UI\Util;

Loc::loadMessages(__FILE__);

class Update2112000
{
	protected const ID_ARTICLE_HELP_DESK = 14927782;

	/**
	 * Agent which sends notification.
	 *
	 * @return string
	 */
	public static function sendNotifications(): string
	{
		if (
			Loader::includeModule('imopenlines')
			&& Loader::includeModule('imconnector')
			&& Loader::includeModule('ui')
		)
		{
			$lineIds = [];
			$activeConnections = StatusConnectorsTable::getList([
				'select' => ['LINE'],
				'filter' => [
					'=CONNECTOR' => [
						'facebook',
						'fbinstagramdirect'
					],
					'=ACTIVE' => 'Y',
					'=CONNECTION' => 'Y',
					'=REGISTER' => 'Y',
				],
			]);

			while ($activeConnection = $activeConnections->fetch())
			{
				$lineIds[] = $activeConnection['LINE'];
			}

			if (!empty($lineIds))
			{
				if (
					Loader::includeModule('bitrix24')
					&& Loader::includeModule('imbot')
				)
				{
					$userIds = [];

					$lineIds = array_unique($lineIds);

					$queueLine = ConfigTable::getList([
						'select' => ['MODIFY_USER_ID'],
						'filter' => [
							'=ID' => $lineIds,
						],
					]);

					while ($row = $queueLine->fetch())
					{
						$userIds[] = $row['MODIFY_USER_ID'];
					}

					if (empty($userIds))
					{
						$userIds = ['ADMIN'];
					}
					else
					{
						$admins = Support24::getAdministrators();
						$userIds = array_merge($userIds, $admins);
						$userIds = array_unique($userIds);
					}

					foreach ($userIds as $userId)
					{
						Support24::sendMessage([
							'DIALOG_ID' => $userId,
							'MESSAGE' => Loc::getMessage('IMCONNECTOR_UPDATER_2112000_CHAT', [
								'#HREF#' => Util::getArticleUrlByCode(self::ID_ARTICLE_HELP_DESK),
							]),
							'SYSTEM' => 'N',
							'URL_PREVIEW' => 'N'
						]);
					}
				}
				else
				{
					\CAdminNotify::Add([
						'MODULE_ID' => 'imconnector',
						'ENABLE_CLOSE' => 'Y',
						'NOTIFY_TYPE' => \CAdminNotify::TYPE_NORMAL,
						'MESSAGE' => Loc::getMessage('IMCONNECTOR_UPDATER_2112000_ADMIN_NOTIFY', [
							'#HREF#' => Util::getArticleUrlByCode(self::ID_ARTICLE_HELP_DESK),
						]),
					]);
				}
			}
		}

		return '';
	}
}