<?php
namespace Bitrix\Imopenlines\Update;

use \Bitrix\Main\Loader;

final class Agent
{
	public static function update1720(): string
	{
		if(Loader::IncludeModule("im") && class_exists('\Bitrix\Im\Model\AppTable') && class_exists('\Bitrix\Im\App'))
		{
			$result = \Bitrix\Im\Model\AppTable::getList(Array(
				'filter' => Array('=MODULE_ID' => 'imopenlines', '=CODE' => 'quick')
			))->fetch();

			if (!$result)
			{
				$imagePath = BX_ROOT.'/modules/imopenlines/install/icon/icon_quick.png';

				$iconId = \CFile::SaveFile(
					\CFile::MakeFileArray($imagePath),
					'imopenlines'
				);

				\Bitrix\Im\App::register(Array(
					'MODULE_ID' => 'imopenlines',
					'BOT_ID' => 0,
					'CODE' => 'quick',
					'REGISTERED' => 'Y',
					'ICON_ID' => $iconId,
					'IFRAME' => '/desktop_app/iframe/imopenlines_quick.php',
					'IFRAME_WIDTH' => '512',
					'IFRAME_HEIGHT' => '234',
					'CONTEXT' => 'lines',
					'CLASS' => '\Bitrix\ImOpenLines\Chat',
					'METHOD_LANG_GET' => 'onAppLang',
				));
			}

			return '';
		}
		else
		{
			return __METHOD__. '();';
		}
	}

	/**
	 * @return string
	 */
	public static function update222000(): string
	{
		if (
			Loader::IncludeModule('im')
			&& Loader::IncludeModule('imopenlines')
		)
		{
			$connection = \Bitrix\Main\Application::getInstance()->getConnection();
			$configs = [];
			$confList = $connection->query("SELECT ID FROM b_imopenlines_config");
			while ($row = $confList->fetch())
			{
				$configs[] = (int)$row['ID'];
			}

			$type = \Bitrix\Im\Chat::TYPE_OPEN_LINE;
			$chatType = \Bitrix\ImOpenLines\Chat::CHAT_TYPE_OPERATOR;

			$res = $connection->query("
				SELECT 
					c.ID, c.ENTITY_ID 
				FROM 
					b_im_chat c 
					inner join b_im_recent r
						on c.ID = r.ITEM_ID 
						and r.ITEM_TYPE = '{$type}'
						and c.ENTITY_TYPE = '{$chatType}'
					left join b_imopenlines_session s
						on c.id = s.CHAT_ID
					left join b_imopenlines_config g
						on g.ID = cast(substring_index(substring_index(c.ENTITY_ID, '|', 2), '|', -1) as unsigned)
				WHERE 
					s.ID is null
					AND g.ID is null
			");
			while ($chat = $res->fetch())
			{
				$userCode = \Bitrix\ImOpenLines\Session\Common::parseUserCode($chat['ENTITY_ID']);
				if ($userCode && (int)$userCode['CONFIG_ID'] > 0 && !in_array((int)$userCode['CONFIG_ID'], $configs))
				{
					\Bitrix\ImOpenLines\Im::chatHide($chat['ID']);
				}
			}

			if (Loader::includeModule('pull'))
			{
				\Bitrix\Pull\Event::send();
			}
		}

		return '';
	}
}