<?php

namespace Bitrix\Market\Tag;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\EventManager;
use Bitrix\Main\Type\Date;
use Bitrix\Market\Integration\TagManager;

/**
 * class Manager
 *
 * @package Bitrix\Market\Tag
 */
class Manager
{
	public const EVENT_LOAD_TAGS = 'onLoadRestMarketTags';

	private const DELIMITER = '|';
	private const MODULE_ID = 'rest';

	private static $saveData = [];
	private static $flagEvent = false;

	/**
	 * Add a handler to the save changes.
	 */
	public static function registerEventHandlerSave()
	{
		if (!self::$flagEvent)
		{
			Application::getInstance()->addBackgroundJob(
				[
					__CLASS__,
					'finalize',
				],
				[],
				Application::JOB_PRIORITY_LOW
			);

			self::$flagEvent = true;
		}
	}

	/**
	 * Save changed tags. Call from static::registerEventHandlerSave().
	 * @return null
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws SqlQueryException
	 */
	public static function finalize()
	{
		$data = array_filter(static::$saveData);
		static::$saveData = [];
		if (!empty($data))
		{
			$filter = [
				'LOGIC' => 'OR',
			];
			foreach ($data as $type => $itemList)
			{
				foreach ($itemList as $item)
				{
					$filter[] = [
						'=TYPE' => $type,
						'=MODULE_ID' => $item['MODULE_ID'],
						'=CODE' => $item['CODE'],
					];
				}
			}

			$existData = [];
			$res = TagTable::getList(
				[
					'filter' => $filter,
				]
			);
			while ($item = $res->fetch())
			{
				$key = static::getKeyUnic(
					$item['TYPE'],
					$item['MODULE_ID'],
					$item['CODE'],
					$item['DATE_VALUE'],
				);

				$existData[$key] = $item;
			}

			TagTable::unblockSave();
			$today = Date::createFromTimestamp(time());
			foreach ($data as $type => $itemList)
			{
				foreach ($itemList as $item)
				{
					$id = 0;
					$save = [
						'TYPE' => $type,
						'MODULE_ID' => $item['MODULE_ID'],
						'CODE' => $item['CODE'],
						'DATE_VALUE' => $item['DATE_VALUE'] instanceof Date ? $item['DATE_VALUE'] : $today,
						'VALUE' => $item['VALUE'],
					];
					$key = static::getKeyUnic(
						$save['TYPE'],
						$save['MODULE_ID'],
						$save['CODE'],
						$save['DATE_VALUE'],
					);
					if (!empty($existData[$key]))
					{
						if (
							$save['TYPE'] === TagTable::TYPE_INCREMENT_DAILY
							&& $save['DATE_VALUE']->getTimestamp() === $existData[$key]['DATE_VALUE']->getTimestamp()
						)
						{
							continue;
						}
						$id = (int)$existData[$key]['ID'];
						$save['VALUE'] = static::getValueByType($type, $save['VALUE'], $existData[$key]['VALUE']);
					}

					if ($id > 0)
					{
						TagTable::update($id, $save);
					}
					else
					{
						try
						{
							TagTable::add($save);
						}
						catch (SqlQueryException $e)
						{
							if (mb_strpos($e->getMessage(), 'Duplicate entry') !== false)
							{
								if (
									in_array(
										$save['TYPE'],
										[
											TagTable::TYPE_DEFAULT,
											TagTable::TYPE_INCREMENT,
										],
										true
									)
								)
								{
									$resDuplicate = TagTable::getList(
										[
											'filter' => [
												'=TYPE' => $save['TYPE'],
												'=MODULE_ID' => $save['MODULE_ID'],
												'=CODE' => $save['CODE'],
												'=DATE_VALUE' => $save['DATE_VALUE'],
											],
										]
									);

									if ($element = $resDuplicate->fetch())
									{
										$save['VALUE'] = static::getValueByType($item['TYPE'], $item['VALUE'], $element['VALUE']);

										TagTable::update($element['ID'], $save);
									}
								}
							}
							else
							{
								throw $e;
							}
						}
					}
				}
			}
			TagTable::blockSave();
		}

		return null;
	}

	private static function getValueByType($type, $newValue, $oldValue = null)
	{
		if ($oldValue)
		{
			if ($type === TagTable::TYPE_INCREMENT)
			{
				$newValue += (int)$oldValue;
			}

			if ($type === TagTable::TYPE_INCREMENT_DAILY && (int)$oldValue > 0)
			{
				$newValue = (int)$oldValue + 1;
			}
		}

		return $newValue;
	}

	private static function getKeyUnic(string $type, string $moduleId, string $code, Date $dateValue)
	{
		return implode(
			static::DELIMITER,
			[
				$type,
				$moduleId,
				$code,
			]
		);
	}

	/**
	 * Returns key from params
	 * @param $moduleId
	 * @param $code
	 * @return string
	 */
	public static function getKey($moduleId, $code): string
	{
		return $moduleId . static::DELIMITER . $code;
	}

	/**
	 * Explode key.
	 *
	 * @param string $key
	 * @return false|string[]
	 */
	public static function explodeKey(string $key)
	{
		return explode(static::DELIMITER, $key, 1);
	}

	/**
	 * Adds or updates tags value.
	 *
	 * @param string $moduleId
	 * @param string $code
	 * @param string $value
	 * @param null|Date $dateValue
	 * @return bool
	 */
	public static function save(string $moduleId, string $code, string $value, ?Date $dateValue = null, string $type = TagTable::TYPE_DEFAULT): bool
	{
		if (in_array($type, [TagTable::TYPE_DEFAULT, TagTable::TYPE_INCREMENT, TagTable::TYPE_INCREMENT_DAILY], true))
		{
			static::$saveData[$type][] = [
				'VALUE' => $value,
				'DATE_VALUE' => $dateValue,
				'MODULE_ID' => $moduleId,
				'CODE' => $code,
			];
		}

		static::registerEventHandlerSave();

		return true;
	}

	/**
	 * @param $moduleId
	 * @param $code
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function increment($moduleId, $code, $plus = true): bool
	{
		$key = static::getKey($moduleId, $code);

		if (!isset(static::$saveData[TagTable::TYPE_INCREMENT][$key]))
		{
			static::$saveData[TagTable::TYPE_INCREMENT][$key] = [
				'VALUE' => 0,
				'MODULE_ID' => $moduleId,
				'CODE' => $code,
			];
		}

		static::$saveData[TagTable::TYPE_INCREMENT][$key]['VALUE'] += $plus ? 1 : -1;

		static::registerEventHandlerSave();

		return true;
	}

	/**
	 * @param string $moduleId
	 * @param string $code
	 * @return bool
	 */
	public static function incrementDaily(string $moduleId, string $code): bool
	{
		$key = static::getKey($moduleId, $code);
		static::$saveData[TagTable::TYPE_INCREMENT_DAILY][$key] = [
			'MODULE_ID' => $moduleId,
			'CODE' => $code,
			'VALUE' => 1,
		];

		static::registerEventHandlerSave();

		return true;
	}

	public static function onAfterRegisterModule(Main\Event $event): void
	{
		$fields = $event->getParameters();
		if (!empty($fields[0]))
		{
			static::loadByModule($fields[0]);
		}
	}

	public static function onAfterUnRegisterModule(Main\Event $event): void
	{
		$fields = $event->getParameters();
		if (!empty($fields[0]))
		{
			static::deleteByModule($fields[0]);
		}
	}

	public static function deleteByModule($moduleId): bool
	{
		if (!empty($moduleId))
		{
			$res = TagTable::getList(
				[
					'filter' => [
						'=MODULE_ID' => $moduleId,
					],
					'select' => [
						'ID',
					]
				]
			);
			while ($item = $res->fetch())
			{
				TagTable::delete($item['ID']);
			}
		}

		return true;
	}

	/**
	 * Sends event to load tags by module id.
	 *
	 * @param string $moduleId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function loadByModule(string $moduleId)
	{
		if (Main\ModuleManager::isModuleInstalled($moduleId))
		{
			if (TagManager::isExist($moduleId))
			{
				$tagHandler = TagManager::getClass($moduleId);
				$result = call_user_func([$tagHandler, 'list']);

				static::saveList($result);
			}

			return static::loadTags(
				[
					'MODULE_ID' => $moduleId,
				]
			);
		}

		return false;
	}

	/**
	 * Save list of tag.
	 *
	 * @param array $list
	 * @return bool
	 */
	public static function saveList(array $list): bool
	{
		foreach ($list as $tag)
		{
			if (
				!empty($tag['MODULE_ID'])
				&& !empty($tag['CODE'])
				&& array_key_exists('VALUE', $tag)
			)
			{
				static::save(
					(string)$tag['MODULE_ID'],
					(string)$tag['CODE'],
					(string)$tag['VALUE'],
					$tag['DATE_VALUE'] ?? null,
					$tag['TYPE'] ?? TagTable::TYPE_DEFAULT,
				);
			}
		}

		return true;
	}
	/**
	 * Sends event to load tags.
	 *
	 * @param array $eventParams
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function loadTags(array $eventParams = []): bool
	{
		$event = new Main\Event(
			'rest',
			static::EVENT_LOAD_TAGS,
			$eventParams,
			$eventParams['MODULE_ID'] ?? null
		);

		EventManager::getInstance()->send($event);
		foreach ($event->getResults() as $eventResult)
		{
			$result = $eventResult->getParameters();
			if (is_array($result))
			{
				static::saveList($result);
			}
		}

		return true;
	}

	/**
	 * Load tags by module.
	 * @param string $moduleId
	 * @return string
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function doAgentLoad(string $moduleId): string
	{
		static::loadByModule($moduleId);

		return static::class . '::doAgentLoad(\'' . $moduleId . '\');';
	}

	/**
	 * Load tags by module.
	 * @param string $moduleId
	 * @return string
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function doAgentOnceLoad(string $moduleId): string
	{
		static::loadByModule($moduleId);

		$res = \CAgent::getList(
			[],
			[
				'MODULE_ID' => static::MODULE_ID,
				'NAME' => '%' . static::class . '::doAgentOnceLoad(\'' . $moduleId . '\')%',
			]
		);
		while ($agent = $res->fetch())
		{
			\CAgent::Delete($agent['ID']);
		}

		return '';
	}
}