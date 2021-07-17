<?php

namespace Bitrix\Intranet\Integration\Rest\Configuration\Entity;

use Bitrix\Intranet\Integration\Templates\Bitrix24\ThemePicker;
use Bitrix\Intranet\Composite\CacheProvider;
use Bitrix\Intranet\Internals\ThemeTable;
use Bitrix\Rest\Configuration\Helper;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\IO\File;
use CFile;

Loc::loadMessages(__FILE__);

class Theme
{
	const ENTITY_INTRANET_THEME = 'theme';

	protected static $accessManifest = [
		'intranet_theme',
		'intranet_setting'
	];

	/**
	 * @param $params array standard export
	 *
	 * @return array export result
	 * @return null for skip no access step
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function export($params)
	{
		$result = null;
		if (Helper::checkAccessManifest($params, static::$accessManifest))
		{
			$themePicker = ThemePicker::getInstance(ThemePicker::ENTITY_TYPE_USER);
			$theme = $themePicker->getDefaultTheme();
			if ($theme['id'])
			{
				[$textColor, $subThemeId] = explode(':', $theme['id']);
				$result = [
					'FILE_NAME' => static::ENTITY_INTRANET_THEME,
					'CONTENT' => [
						'TYPE' => static::ENTITY_INTRANET_THEME,
						'ID' => $theme['id'],
						'TEXT_COLOR' => $textColor,
					],
					'NEXT' => false
				];

				if (mb_strpos($subThemeId, $themePicker->getCustomThemePrefix()) === false)
				{
					$result['CONTENT']['CODE'] = $subThemeId;
				}
				else
				{
					$themeData = null;

					$res = ThemeTable::getList([
						'filter' => [
							'=ENTITY_TYPE' => $themePicker->getEntityType(),
							'ENTITY_ID' => 0,
							'=CONTEXT' => $themePicker->getContext(),
						],
						'select' => [ 'ID', 'USER_ID' ]
					]);
					while($themeFields = $res->fetch())
					{
						$themeList = \CUserOptions::getOption(
							'intranet',
							$themePicker->getCustomThemesOptionName(),
							[],
							$themeFields['USER_ID']
						);

						if (!empty($themeList[$theme['id']]))
						{
							$themeData = $themeList[$theme['id']];
						}
					}

					if (is_array($themeData))
					{
						if ($themeData['bgImage'] > 0)
						{
							$result['CONTENT']['BACKGROUND_IMAGE'] = $themeData['bgImage'];
							$result['FILES'] = [
								[
									'ID' => $themeData['bgImage']
								]
							];
						}
						if (isset($themeData['bgColor']))
						{
							$result['CONTENT']['BACKGROUND_COLOR'] = $themeData['bgColor'];
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param $params
	 *
	 * @return array|null
	 */
	public static function import($params)
	{
		$result = null;
		if (
			Helper::checkAccessManifest($params, static::$accessManifest)
			&& !empty($params['CONTENT']['DATA'])
		)
		{
			$result = [];
			$themePicker = ThemePicker::getInstance();

			$data = $params['CONTENT']['DATA'];
			if (!empty($data['CODE']))
			{
				$theme = $themePicker->getStandardTheme($data['ID']);
				if (is_array($theme))
				{
					$success = $themePicker->setCurrentThemeId($theme['id']);
					if ($success && ThemePicker::isAdmin())
					{
						$themePicker->setDefaultTheme($theme['id']);
						CacheProvider::deleteUserCache();
					}
					else
					{
						$result['ERROR_MESSAGES'] = Loc::getMessage('INTRANET_CONFIGURATION_THEME_ERROR_SET_DEFAULT');
					}
				}
				else
				{
					$result['ERROR_MESSAGES'] = Loc::getMessage('INTRANET_CONFIGURATION_THEME_ERROR_NOT_FOUND');
				}
			}
			else
			{
				try
				{
					$themeId = false;
					$saveData = [
						'textColor' => $data['TEXT_COLOR'],
					];

					if ($data['BACKGROUND_COLOR'] <> '')
					{
						$saveData['bgColor'] = $data['BACKGROUND_COLOR'];
					}

					if ($data['BACKGROUND_IMAGE'] > 0)
					{
						$structure = new \Bitrix\Rest\Configuration\Structure($params['CONTEXT_USER']);
						$file = $structure->getUnpackFile($data['BACKGROUND_IMAGE']);
						if ($file && $file['PATH'] && $file['NAME'])
						{
							//on create check file extensions, copy file with real name to tmp folder
							$tmpFolder = $structure->getFolder();
							if ($tmpFolder)
							{
								$folder = $tmpFolder.'tmp_files/';
								if (CheckDirPath($folder))
								{
									$image = File::getFileContents($file['PATH']);
									File::putFileContents($folder.$file['NAME'], $image);
									$saveData['bgImage'] = CFile::MakeFileArray($folder.$file['NAME']);
								}
							}
						}
					}

					if (!$saveData['bgImage'] && !$saveData['bgColor'] && $data['ID'])
					{
						$themeList = $themePicker->getList();
						$key = array_search($data['ID'], array_column($themeList, 'id'));
						if ($key !== false)
						{
							$themeId = $themeList[$key]['id'];
						}
					}

					if (!$themeId)
					{
						$themeId = $themePicker->create($saveData);
					}

					if ($themeId)
					{
						$success = $themePicker->setCurrentThemeId($themeId);
						if ($success && ThemePicker::isAdmin())
						{
							$themePicker->setDefaultTheme($themeId);
							CacheProvider::deleteUserCache();
						}
						else
						{
							$result['ERROR_MESSAGES'] = Loc::getMessage('INTRANET_CONFIGURATION_THEME_ERROR_SET_DEFAULT');
						}
					}
				}
				catch (SystemException $e)
				{
					$result['ERROR_MESSAGES'] = $e->getMessage();
				}
			}
		}

		return $result;
	}
}