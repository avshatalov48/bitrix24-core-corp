<?php
namespace Bitrix\Imopenlines\Update;

use \Bitrix\Main\Loader;

final class Agent
{
	public function update1720()
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
			return '\Bitrix\Imopenlines\Update\Agent::update1720();';
		}
	}
}