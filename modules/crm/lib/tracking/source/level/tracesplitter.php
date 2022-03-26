<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Source\Level;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main;

use Bitrix\Crm\Tracking;

Loc::loadMessages(__FILE__);

/**
 * Class TraceSplitter.
 *
 * @package Bitrix\Crm\Tracking\Source\Level
 */
final class TraceSplitter
{
	/** @var Tracking\Source\Base[] $sources Sources. */
	protected $sources;
	protected $sourceCodes = [];
	protected $checkDoubling = false;

	/** @var static $instance Instance. */
	protected static $instance;

	/**
	 * Get instance.
	 *
	 * @return static
	 */
	public static function instance()
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct()
	{
		$sources = Tracking\Provider::getReadySources();
		$this->sourceCodes = array_combine(
			array_column($sources, 'ID'),
			array_column($sources, 'CODE')
		);
	}

	/**
	 * Split trace.
	 *
	 * @param Tracking\Trace $trace
	 * @return void
	 * @throws \Exception
	 */
	public function split(Tracking\Trace $trace)
	{
		$decomposition = self::parseUtm($trace->getUtm());
		$this->save($trace->getId(), $trace->getSourceId(), $decomposition);
	}

	/**
	 * Save.
	 *
	 * @param int $traceId Trace ID.
	 * @param int $sourceId Source ID.
	 * @param array $decomposition Decomposition.
	 * @return void
	 * @throws \Exception
	 */
	protected function save($traceId, $sourceId, array $decomposition)
	{
		if (!$traceId || !$sourceId || empty($decomposition))
		{
			return;
		}

		$sourceCode = $this->sourceCodes[$sourceId] ?? null;
		if (!$sourceCode || !Tracking\Source\Factory::isKnown($sourceCode))
		{
			return;
		}

		$children = self::getSourceChildren($sourceId, $decomposition);
		foreach ($decomposition as $levelId => $code)
		{
			$traceSourceId = 0;
			if ($this->checkDoubling)
			{
				$row = Tracking\Internals\TraceSourceTable::getRow([
					'select' => ['ID'],
					'filter' => [
						'=TRACE_ID' => $traceId,
						'=LEVEL' => $levelId,
					],
				]);
				if ($row)
				{
					$traceSourceId = $row['ID'];
				}
			}

			if (!$code)
			{
				continue;
			}

			$childId = $children[$levelId][$code] ?? 0;
			if (!$traceSourceId)
			{
				Tracking\Internals\TraceSourceTable::add([
					'TRACE_ID' => $traceId,
					'LEVEL' => $levelId,
					'CODE' => $code,
					'SOURCE_CHILD_ID' => $childId,
					'PROCESSED' => $childId ? 1 : 0,
				]);
			}
			elseif($childId)
			{
				Tracking\Internals\TraceSourceTable::update(
					$traceSourceId,
					[
						'TRACE_ID' => $traceId,
						'LEVEL' => $levelId,
						'CODE' => $code,
						'SOURCE_CHILD_ID' => $childId,
						'PROCESSED' => 1,
					]
				);
			}
		}
	}

	private static function getSourceChildren($sourceId, array $decomposition)
	{
		$childrenRows = Tracking\Internals\SourceChildTable::getList([
			'select' => ['ID', 'LEVEL', 'CODE'],
			'filter' => [
				'=SOURCE_ID' => $sourceId,
				'=LEVEL' => array_keys($decomposition),
				'=CODE' => array_values($decomposition),
			],
		]);
		$children = [];
		foreach ($childrenRows as $child)
		{
			if (empty($children[$child['LEVEL']]))
			{
				$children[$child['LEVEL']] = [];
			}
			$children[$child['LEVEL']][$child['CODE']] = $child['ID'];
		}

		return $children;
	}

	private static function getUtmMap()
	{
		return [
			'tar' => Type::Keyword,
			'adid' => Type::Keyword,
			'ad_id' => Type::Keyword,
			'kwid' => Type::Keyword,
			'kwd' => Type::Keyword,
			'aid' => Type::Keyword,
			'gid' => Type::Adgroup,
			'cid' => Type::Campaign,

			'creative' => Type::Keyword,
			'adgroupid' => Type::Adgroup,
			'campaignid' => Type::Campaign,

			'creative_id' => Type::Keyword,
			'gbid' => Type::Adgroup,
			'campaign_id' => Type::Campaign,
		];
	}

	private static function parseUtm(array $tags = [])
	{
		$levels = [];

		if (empty($tags))
		{
			return $levels;
		}

		if (!empty($tags['UTM_CAMPAIGN']))
		{
			$campaignId = preg_replace('/[^\d]/', '', $tags['UTM_CAMPAIGN']);
			if (mb_strlen($campaignId) >= 6)
			{
				$levels[Type::Campaign] = $campaignId;
			}
		}

		static $map = null;
		if ($map === null)
		{
			$map = self::getUtmMap();
		}

		$mapKeys = implode("|", array_keys($map));
		foreach($tags as $tagValue)
		{
			if (!$tagValue || !trim($tagValue))
			{
				continue;
			}

			$matches = [];
			$matchResult = preg_match_all("/($mapKeys)[|_-]?([a-z\-\d]{6,})/", $tagValue, $matches);
			if (!$matchResult || empty($matches[1]))
			{
				continue;
			}

			foreach ($matches[1] as $matchIndex => $key)
			{
				$value = $matches[2][$matchIndex];
				$value = preg_replace("/[^\d]/", "", $value);
				if (!$value)
				{
					continue;
				}
				$levels[$map[$key]] = $value;
			}
		}

		return $levels;
	}
}