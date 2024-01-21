<?php
namespace Bitrix\Imopenlines\Update;

use \Bitrix\Main\Loader;

final class Agent
{
	public static function updateQuick(): string
	{
		if (
			Loader::IncludeModule("im")
			&& class_exists('\Bitrix\Im\Model\AppTable')
			&& class_exists('\Bitrix\Im\App')
		)
		{
			$imagePath = BX_ROOT.'/modules/imopenlines/install/icon/icon_quick.png';

			$result = \Bitrix\Im\Model\AppTable::getList([
				'filter' => ['=MODULE_ID' => 'imopenlines', '=CODE' => 'quick']
			])->fetch();

			if (!$result)
			{
				$iconId = \CFile::SaveFile(\CFile::MakeFileArray($imagePath), 'imopenlines');

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
			else
			{
				$iconFileOk = false;
				$appId = (int)$result['ID'];
				$iconId = (int)$result['ICON_FILE_ID'];
				if ($iconId)
				{
					$file = \CFile::getByID($iconId)->fetch();

					$iconFileOk = \Bitrix\Main\IO\File::isFileExists(
						\Bitrix\Main\Application::getDocumentRoot()
						.$file['SRC']
					);
				}
				if (
					$iconFileOk !== true
					&& ($iconId = \CFile::SaveFile(\CFile::MakeFileArray($imagePath), 'imopenlines'))
				)
				{
					\Bitrix\Im\Model\AppTable::update($appId, ['ICON_FILE_ID' => $iconId])->isSuccess();
				}
			}

			return '';
		}
		else
		{
			return __METHOD__. '();';
		}
	}

	public static function update1720(): string
	{
		return self::updateQuick();
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
			$configs = [];
			$confList = \Bitrix\ImOpenLines\Model\ConfigTable::getList(['select' => ['ID']]);
			while ($row = $confList->fetch())
			{
				$configs[] = (int)$row['ID'];
			}

			$type = \Bitrix\Im\Chat::TYPE_OPEN_LINE;
			$chatType = \Bitrix\ImOpenLines\Chat::CHAT_TYPE_OPERATOR;

			$connection = \Bitrix\Main\Application::getInstance()->getConnection();
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
						on g.ID = cast(substring_index(substring_index(c.ENTITY_ID, '|', 2), '|', -1) as decimal)
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

	public static function updateRightsQuickAnswersAgent(): string
	{
		if (!Loader::includeModule('iblock'))
		{
			return '';
		}

		$iblocks = \CIBlock::getList(
			[],
			[
				'ACTIVE' => 'Y',
				'TYPE' => \Bitrix\ImOpenlines\QuickAnswers\ListsDataManager::TYPE,
				'CODE' => \Bitrix\ImOpenlines\QuickAnswers\ListsDataManager::IBLOCK_CODE,
				'CHECK_PERMISSIONS' => 'N'
			]
		);

		while ($iblock = $iblocks->Fetch())
		{
			$configsCount = \Bitrix\ImOpenLines\Model\ConfigTable::getCount([
				'=QUICK_ANSWERS_IBLOCK_ID' => (int)$iblock['ID']
			]);

			if ($configsCount)
			{
				\Bitrix\ImOpenlines\QuickAnswers\ListsDataManager::updateIblockRights($iblock['ID']);
			}
			else
			{
				$sectionCount = \CIBlockElement::GetList([], ['IBLOCK_ID' => $iblock['ID']]);
				if (!$sectionCount->SelectedRowsCount())
				{
					\CIBlock::Delete($iblock['ID']);
				}
			}
		}

		return '';
	}
}
