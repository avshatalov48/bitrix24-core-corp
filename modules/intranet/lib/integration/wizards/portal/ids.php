<?php
namespace Bitrix\Intranet\Integration\Wizards\Portal;

use \Bitrix\Main;

class Ids
{
	private static string $cacheId = 'id_to_code_';
	private static string $cacheDir = '/bx/code';
	private static int $cacheTtl = 2592000;
	private static \CPHPCache $cache;

	private static ?array $voteChannelSId = [];
	private static ?array $blogs = [];
	private static ?array $iblockIds = [];
	private static ?array $diskIds = [];
	private static ?array $forumIds = [];


	private static function getCacheObject(): \CPHPCache
	{
		if (!isset(self::$cache))
		{
			self::$cache = new \CPHPCache;
		}
		return self::$cache;
	}

	private static function getFormCache(string $id)
	{
		$cacheId = implode('_', [self::$cacheId, $id]);

		if (self::getCacheObject()->InitCache(self::$cacheTtl, $cacheId, self::$cacheDir)
			&& ($tmpVal = self::getCacheObject()->GetVars())
		)
		{
			return $tmpVal;
		}
		return null;
	}

	private static function putIntoCache(string $id, $data): void
	{
		$cacheId = implode('_', [self::$cacheId, $id]);
		if (
			self::getCacheObject()->InitCache(self::$cacheTtl, $cacheId, self::$cacheDir)
			&& self::getCacheObject()->StartDataCache()
		)
		{
			self::getCacheObject()->EndDataCache($data);
		}
	}

	public static function getDiskStorageId(string $code, ?string $siteId = null): ?string
	{
		$siteId = $siteId ?: SITE_ID;

		if (empty(self::$diskIds))
		{
			$cacheId = 'diskIds';
			$val = self::getFormCache($cacheId);

			if (!is_array($val) && Main\Loader::IncludeModule('disk'))
			{
				if ($code === 'MANAGE_STORAGE_ID')
				{
					$storage = \Bitrix\Disk\Storage::getList([
						'select' => ['ID'],
						'filter' => ['ENTITY_ID' => "directors_files_" . $siteId, 'SITE_ID' => $siteId],
					])->fetch();

					$val['MANAGE_STORAGE_ID'] = $storage['ID'] ?? '0';
				}
				elseif ($code === 'SHARED_STORAGE_ID')
				{
					$commonStorage = \Bitrix\Disk\Driver::getInstance()->getStorageByCommonId('shared_files_'
							. $siteId);
					$val['SHARED_STORAGE_ID'] = $commonStorage ? $commonStorage->getId() : '0';
				}
				elseif ($code === 'SALE_STORAGE_ID')
				{
					$storage = \Bitrix\Disk\Storage::getList([
						'select' => ['ID'],
						'filter' => ['ENTITY_ID' => "sales_files_" . $siteId, 'SITE_ID' => $siteId],
					])->fetch();

					$val['SALE_STORAGE_ID'] = $storage['ID'] ?? '0';
				}

				self::putIntoCache($cacheId, $val);
			}
			self::$diskIds = $val;
		}
		return isset(self::$diskIds[$code]) ? (string)self::$diskIds[$code] : null;
	}

	public static function getForumId(string $xmlForumId): ?string
	{
		if (empty(self::$forumIds))
		{
			$cacheId = 'forumId';
			$val = self::getFormCache($cacheId);

			if (!is_array($val) && Main\Loader::IncludeModule('forum'))
			{
				$val = [];
				$dbRes = \Bitrix\Forum\ForumTable::getList([
					'select' => ['ID', 'XML_ID'],
					'filter' => [
						'=XML_ID' => [
							"PHOTOGALLERY_COMMENTS", // #PHOTOGALLERY_COMMENTS#
							"DOCS_SHARED_COMMENTS",// #SHARED_FILES_FORUM_ID#
							"GENERAL", // #GENERAL_FORUM_ID# - deprecated
							"DOCS_SALES_COMMENTS", // #SALE_FILES_FORUM_ID#
							"DOCS_DIRECTORS_COMMENTS", // #DIRECTORS_FILES_FORUM_ID#
							"GROUPS_AND_USERS_FILES_COMMENTS", // #GROUPS_AND_USERS_FILES_COMMENTS# #FILES_FORUM_ID#
							"NEWS_COMMENTS", // #NEWS_COMMENTS_FORUM_ID#
							"USERS_AND_GROUPS", // #FORUM_ID#

							'intranet_tasks', // #FORUM_ID#,
							'GROUPS_AND_USERS_TASKS_COMMENTS_EXTRANET',
							'bizproc_workflow',
							'WIKI',
							'WIKI_GROUP_COMMENTS',

							'car_forum_demo',
						]
					]
				]);
				while ($res = $dbRes->Fetch())
				{
					$val[$res['XML_ID']] = $res['ID'];
				}

				self::putIntoCache($cacheId, $val);
			}
			self::$forumIds = $val;
		}
		return isset(self::$forumIds[$xmlForumId]) ? (string) self::$forumIds[$xmlForumId] : null;
	}

	public static function getBlogId(?string $siteId = null): ?string
	{
		$siteId = $siteId ?: SITE_ID;

		if (empty(self::$blogs))
		{
			$cacheId = 'blogId';
			$val = self::getFormCache($cacheId);

			if (!is_array($val) && Main\Loader::IncludeModule('blog'))
			{
				$val = [];
				$dbGroup = \CBlogGroup::GetList(["ID" => "ASC"], ["~NAME" => "[__]%"], false, false, ['ID', 'SITE_ID']);
				while ($res = $dbGroup->Fetch())
				{
					$val[$res['SITE_ID']] = $res['ID'];
				}
				self::putIntoCache($cacheId, $val);
			}
			self::$blogs = $val;
		}
		return isset(self::$blogs[$siteId]) ? (string) self::$blogs[$siteId] : null;
	}

	public static function getIblockId(string $iBlockCode, string $siteId = null): ?string
	{
		$siteId = $siteId ?: SITE_ID;

		if (empty(self::$iblockIds))
		{
			$cacheId = 'iblockIds';
			$val = self::getFormCache($cacheId);

			if (!is_array($val) && Main\Loader::IncludeModule('iblock'))
			{
				$val = [];

				$dbRes = \CIBlock::GetList([], ['CHECK_PERMISSIONS' => 'N']);
				while ($res = $dbRes->Fetch())
				{
					$val[$res['CODE']] = $res['ID'];
					$val[$res['XML_ID']] = $res['ID'];
				}
				self::putIntoCache($cacheId, $val);
			}
			self::$iblockIds = $val;
		}
		$iBlockCodeOldVariant = $iBlockCode.'_'.$siteId;
		return isset(self::$iblockIds[$iBlockCodeOldVariant])
			? (string) self::$iblockIds[$iBlockCodeOldVariant]
			: (isset(self::$iblockIds[$iBlockCode])
				? (string) self::$iblockIds[$iBlockCode]
				: null
			)
		;
	}

	public static function getVoteChannelSid(?string $siteId = null): ?string
	{
		$siteId = $siteId ?: SITE_ID;

		if (!isset(self::$voteChannelSId[$siteId]))
		{
			$cacheId = implode('_', ['voteChannelSId', $siteId]);
			$val = self::getFormCache($cacheId);

			if (!is_string($val) && Main\Loader::IncludeModule('vote'))
			{
				$symbolicName = 'COMPANY_' . $siteId;
				if (!(\CVoteChannel::GetList('', '', [
					"SYMBOLIC_NAME" => $symbolicName,
					'SYMBOLIC_NAME_EXACT_MATCH' => 'Y'
				])->fetch()))
				{
					$symbolicName = 'COMPANY';
				}
				self::putIntoCache($cacheId, $symbolicName);
				$val = $symbolicName;
			}

			self::$voteChannelSId[$siteId] = is_string($val) ? $val : null;
		}
		return self::$voteChannelSId[$siteId];
	}
}



