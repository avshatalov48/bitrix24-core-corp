<?php

namespace Bitrix\Intranet\Update;

use Bitrix\Intranet\Integration\Templates\Bitrix24\ThemePicker;
use Bitrix\Intranet\Internals\ThemeTable;
use Bitrix\Main\Application;
use Bitrix\Main\Update\Stepper;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

final class ThemePickerConvert extends Stepper
{
	protected static $moduleId = 'intranet';
	protected $limit = 50;

	private function getCount()
	{
		$result = 0;
		$connection = Application::getInstance()->getConnection();

		$queryObject = $connection->query("SELECT COUNT(`ID`) AS CNT FROM `b_user_option` WHERE `CATEGORY` = 'intranet' AND `NAME` LIKE 'bitrix24\_theme\_%' ORDER BY ID ASC");
		if ($fields = $queryObject->fetch())
		{
			$result = (int)$fields['CNT'];
		}

		return $result;
	}

	public function execute(array &$result)
	{
		if (!(
			Loader::includeModule('intranet')
			&& Option::get('intranet', 'needConvertThemePicker', 'Y') === 'Y'
		))
		{
			return false;
		}

		$return = false;

		$params = Option::get('intranet', 'themepickerconvert', '');
		$params = ($params !== '' ? @unserialize($params, [ 'allowed_classes' => false ]) : []);
		$params = (is_array($params) ? $params : []);

		if (empty($params))
		{
			$params = [
				'lastId' => 0,
				'number' => 0,
				'count' => $this->getCount(),
			];
		}

		if ($params['count'] > 0)
		{
			$result['title'] = '';
			$result['progress'] = 1;
			$result['steps'] = '';
			$result['count'] = $params['count'];

			$connection = Application::getInstance()->getConnection();

			$queryObject = $connection->query("SELECT `ID`, `USER_ID`, `NAME`, `VALUE` FROM `b_user_option` WHERE `CATEGORY` = 'intranet' AND `NAME` LIKE 'bitrix24\_theme\_%' AND `ID` > " . (int)$params['lastId'] . " ORDER BY ID ASC LIMIT 0, " . (int)$this->limit);

			$found = false;
			while ($record = $queryObject->fetch())
			{
				$themeId = @unserialize($record['VALUE'], [ 'allowed_classes' => false ]);
				$userId = 0;

				if (is_array($themeId) && isset($themeId['userId'], $themeId['themeId']))
				{
					$themeId = $themeId['themeId'];
					$userId = (int)$themeId['userId'];
				}

				if (preg_match('/^bitrix24\_theme\_(.+)/is' . BX_UTF_PCRE_MODIFIER, $record['NAME'], $matches))
				{
					ThemeTable::set([
						'THEME_ID' => $themeId,
						'USER_ID' => $userId,
						'ENTITY_TYPE' => ThemePicker::ENTITY_TYPE_USER,
						'ENTITY_ID' => $record['USER_ID'],
						'CONTEXT' => $matches[1],
					]);
				}

				$params['lastId'] = $record['ID'];
				$params['number']++;
				$found = true;
			}

			if ($found)
			{
				Option::set('intranet', 'themepickerconvert', serialize($params));
				$return = true;
			}

			$result['progress'] = (int)($params['number'] * (int)$this->limit / $params['count']);
			$result['steps'] = $params['number'];

			if ($found === false)
			{
				Option::delete('intranet', [ 'name' => 'themepickerconvert' ]);
				Option::set('intranet', 'needConvertThemePicker', 'N');
			}
		}
		else
		{
			Option::set('intranet', 'needConvertThemePicker', 'N');
		}

		return $return;
	}
}
