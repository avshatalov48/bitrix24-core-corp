<?php
namespace Bitrix\Socialnetwork\Livefeed;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\LogTable;

final class Wiki extends Provider
{
	public const PROVIDER_ID = 'WIKI';
	public const CONTENT_TYPE_ID = 'WIKI';

	public static function getId(): string
	{
		return static::PROVIDER_ID;
	}

	public function getEventId()
	{
		return [ 'wiki' ];
	}

	public function getType()
	{
		return Provider::TYPE_POST;
	}

	public function getCommentProvider()
	{
		return new ForumPost();
	}

	public function initSourceFields()
	{
		static $wikiParser = false;
		static $cache = [];

		$elementId = (int)$this->entityId;

		if ($elementId <= 0)
		{
			return;
		}

		$sourceFields = [];

		if (isset($cache[$elementId]))
		{
			$sourceFields = $cache[$elementId];
		}
		elseif (Loader::includeModule('wiki'))
		{
			$element = \CWiki::getElementById($elementId, [
				'CHECK_PERMISSIONS' => 'N',
				'ACTIVE' => 'Y'
			]);

			if ($element)
			{
				$sourceFields = $element;

				$res = LogTable::getList([
					'filter' => [
						'SOURCE_ID' => $elementId,
						'@EVENT_ID' => $this->getEventId(),
					],
					'select' => [ 'ID', 'URL' ]
				]);
				if ($logEntryFields = $res->fetch())
				{
					$sourceFields = array_merge($element, [
						'LOG_ID' => $logEntryFields['ID'],
						'URL' => $logEntryFields['URL']
					]);
				}

				$cache[$elementId] = $sourceFields;
			}
		}

		if (empty($sourceFields))
		{
			return;
		}

		$this->setLogId($sourceFields['LOG_ID']);
		$this->setSourceFields($sourceFields);

		$this->setSourceTitle($sourceFields['NAME']);
		if (!$wikiParser)
		{
			$wikiParser = new \CWikiParser();
		}
		$this->setSourceDescription(\CTextParser::clearAllTags(\CWikiParser::clear($wikiParser->parse($sourceFields['DETAIL_TEXT'], $sourceFields['DETAIL_TEXT_TYPE'], []))));
	}

	public function getPinnedTitle(): string
	{
		$result = '';

		if (empty($this->sourceFields))
		{
			$this->initSourceFields();
		}

		$sourceFields = $this->getSourceFields();
		if (empty($sourceFields))
		{
			return $result;
		}

		$result = Loc::getMessage('SONET_LIVEFEED_WIKI_PINNED_TITLE', [
			'#TITLE#' => $sourceFields['NAME']
		]);

		return $result;
	}

	public static function canRead($params): bool
	{
		return true;
	}

	protected function getPermissions(array $post): string
	{
		return self::PERMISSION_READ;
	}

	public function getLiveFeedUrl(): string
	{
		$pathToWikiArticle = '';

		if (
			($message = $this->getSourceFields())
			&& !empty($message)
		)
		{
			$pathToWikiArticle = str_replace(
				"#GROUPS_PATH#",
				Option::get('socialnetwork', 'workgroups_page', '/workgroups/', $this->getSiteId()),
				$message['URL']
			);
		}

		return $pathToWikiArticle;
	}
}