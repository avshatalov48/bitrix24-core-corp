<?php

namespace Bitrix\Location\Source\Osm;

use Bitrix\Location\Repository\Location\Capability\IFindByExternalId;
use Bitrix\Location\Repository\Location\IRepository;
use Bitrix\Location\Repository\Location\ISource;
use Bitrix\Location\Source\BaseRepository;
use Bitrix\Location\Source\Osm\Api\Api;
use Bitrix\Location\Source\Osm\Converters\Factory;

/**
 * Class Repository
 * @package Bitrix\Location\Source\Osm
 * @internal
 */
final class Repository extends BaseRepository implements IRepository, IFindByExternalId, ISource
{
	/** @var string  */
	protected static $sourceCode = 'OSM';

	/** @var Api */
	protected $api;

	/** @var OsmSource */
	protected $osmSource;

	/**
	 * Repository constructor.
	 * @param Api $api
	 * @param OsmSource $osmSource
	 */
	public function __construct(Api $api, OsmSource $osmSource)
	{
		$this->api = $api;
		$this->osmSource = $osmSource;
	}

	/**
	 * @inheritDoc
	 */
	public function findByExternalId(string $externalId, string $sourceCode, string $languageId)
	{
		$osmType = ExternalIdBuilder::getOsmTypeByExternalId($externalId);
		$osmId = ExternalIdBuilder::getOsmIdByExternalId($externalId);

		if ($sourceCode !== self::$sourceCode || is_null($osmType) || is_null($osmId))
		{
			return null;
		}

		$details = $this->api->details(
			[
				'osm_type' => $osmType,
				'osm_id' => $osmId,
				'addressdetails' => 1,
				'accept-language' => $this->osmSource->convertLang($languageId),
			]
		);

		return Factory::make($details)->convert(
			$languageId, $details
		);
	}

	/**
	 * @inheritDoc
	 */
	public static function getSourceCode(): string
	{
		return self::$sourceCode;
	}
}
