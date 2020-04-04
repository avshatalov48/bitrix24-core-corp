<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking;

use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;

use Bitrix\Crm\UtmTable;

/**
 * Class Trace
 *
 * @package Bitrix\Crm\Tracking
 */
class Trace
{
	const DETECT_TIME_MINUTES = 5;

	protected $url;
	protected $isMobile = false;
	protected $utm = [];
	protected $pages = [];

	/** @var Channel\Collection|null $channelCollection Channel collection. */
	protected $channelCollection;

	protected $sourceId;

	private $loaded = false;

	protected $ref;

	/**
	 * Create trace from string.
	 *
	 * @param string|null $string Trace string.
	 * @return static
	 */
	public static function create($string = null)
	{
		$instance = (new static());
		if ($string)
		{
			$string = trim($string);
			try
			{
				$string = Encoding::convertEncoding($string, SITE_CHARSET, 'UTF-8');
				$data = Json::decode($string);
				$instance->loadByArray($data);
			}
			catch (\Exception $exception)
			{
			}
		}

		return $instance;
	}

	/**
	 * Append channel to trace.
	 *
	 * @param int $traceId Trace ID.
	 * @param Channel\Base $channel Channel.
	 * @return void
	 */
	public static function appendChannel($traceId, Channel\Base $channel)
	{
		Internals\TraceChannelTable::addChannel(
			$traceId,
			$channel->getCode(),
			$channel->getValue()
		);
	}

	/**
	 * Append channel to trace.
	 *
	 * @param int $traceId Trace ID.
	 * @param int $entityTypeId Entity type ID.
	 * @param int $entityId Entity ID.
	 * @return void
	 */
	public static function appendEntity($traceId, $entityTypeId, $entityId)
	{
		Internals\TraceEntityTable::addEntity(
			$traceId,
			$entityTypeId,
			$entityId
		);
	}

	/**
	 * Trace constructor.
	 *
	 * @param array $data Data.
	 */
	public function __construct(array $data = null)
	{
		$this->channelCollection = new Channel\Collection();

		if ($data)
		{
			$this->loadByArray($data);
		}
	}

	/**
	 * Load by array.
	 *
	 * @param array $data Data.
	 * @return $this
	 */
	public function loadByArray(array $data)
	{
		$this->loaded = true;

		$device = self::getValueByKey($data, 'device');
		$this->setMobile(self::getValueByKey($device, 'isMobile'));

		$pages = self::getValueByKey($data, 'pages');
		$pages = self::getValueByKey($pages, 'list');
		if (is_array($pages))
		{
			foreach ($pages as $page)
			{
				if (empty($page[0]) || empty($page[0]))
				{
					continue;
				}

				$this->addPage($page[0], $page[1], $page[2]);
			}
		}

		$url = self::getValueByKey($data, 'url');
		if (!$url && $this->pages)
		{
			$url = $this->pages[0]['URL'];
		}
		if ($url)
		{
			$this->setUrl($url);
		}

		$tags = self::getValueByKey($data, 'tags');
		$tags = self::getValueByKey($tags, 'list');
		if (is_array($tags))
		{
			foreach ($tags as $tagName => $tagValue)
			{
				$this->addUtm($tagName, $tagValue);
			}
		}

		$channels = self::getValueByKey($data, 'channels');
		if (is_array($channels))
		{
			foreach ($channels as $channel)
			{
				if (empty($channel['code']))
				{
					continue;
				}

				$this->channelCollection->addChannel(
					$channel['code'],
					isset($channel['value']) ? $channel['value'] : null
				);
			}
		}

		$this->setReferrer(self::getValueByKey($data, 'ref'));

		return $this;
	}

	/**
	 * Add channel.
	 *
	 * @param Channel\Base $channel Channel instance.
	 * @return $this
	 */
	public function addChannel(Channel\Base $channel)
	{
		$this->channelCollection->setChannel($channel);
		return $this;
	}

	/**
	 * Add page.
	 *
	 * @param string $url Url.
	 * @param int $timestamp Timestamp.
	 * @param string|null $title Title.
	 * @return $this
	 */
	public function addPage($url, $timestamp, $title = null)
	{
		$this->pages[] = [
			'URL' => $url,
			'DATE_INSERT' => DateTime::createFromTimestamp($timestamp),
			'TITLE' => $title,
		];

		return $this;
	}

	/**
	 * Set url.
	 *
	 * @param string $url Url.
	 * @return $this
	 */
	public function setUrl($url)
	{
		$this->url = $url;

		$channel = Channel\Factory::createSiteChannelByHost((new Uri($url))->getHost());
		$this->channelCollection->setChannel($channel, 0);

		return $this;
	}

	/**
	 * Add utm.
	 *
	 * @param string $name Name.
	 * @param string $value Value.
	 * @return $this
	 */
	public function addUtm($name, $value)
	{
		$name = strtoupper($name);
		if (in_array($name, UtmTable::getCodeList()) && $value)
		{
			$this->utm[$name] = $value;
		}

		if ($name === 'UTM_SOURCE' && !$this->sourceId)
		{
			$this->sourceId = Internals\SourceTable::getSourceByUtmSource($value);
		}

		return $this;
	}

	/**
	 * Set mobile.
	 *
	 * @param bool $isMobile Is mobile.
	 * @return $this
	 */
	public function setMobile($isMobile)
	{
		$this->isMobile = $isMobile == true;
		return $this;
	}

	protected function getSourceId()
	{
		return $this->sourceId ?: $this->channelCollection->getSourceId();
	}

	/**
	 * Set source.
	 *
	 * @param int|string $source Source code or ID.
	 * @return $this
	 */
	public function setSource($source)
	{
		if (is_numeric($source))
		{
			$this->sourceId = (int) $source;
		}
		elseif (is_string($source))
		{
			$sources = Provider::getReadySources();
			$sources = array_combine(
				array_column($sources, 'CODE'),
				array_values($sources)
			);
			$source = isset($sources[$source]) ? $sources[$source] : null;
			if ($source)
			{
				$this->sourceId = $source['ID'];
			}
		}

		return $this;
	}

	/**
	 * Set referrer.
	 *
	 * @param string $ref Referrer page.
	 * @return $this
	 */
	public function setReferrer($ref)
	{
		$ref = trim($ref);
		if ($ref && !$this->sourceId)
		{
			$this->sourceId = Internals\SourceTable::getSourceByReferrer($ref);
		}

		return $this;
	}

	protected static function getValueByKey($array, $key)
	{
		if (!is_array($array))
		{
			return null;
		}

		return (isset($array[$key]) && $array[$key]) ? $array[$key] : null;
	}

	protected function detect()
	{
		if ($this->loaded)
		{
			return null;
		}

		foreach ($this->channelCollection as $channel)
		{
			/** @var Channel\Base $channel */
			if (!$channel->isSupportDetecting())
			{
				continue;
			}

			$row = Internals\TraceTable::getList([
				'select' => ['ID'],
				'filter' => [
					'>DATE_CREATE' => (new DateTime())->add('-' . self::DETECT_TIME_MINUTES . ' minutes'),
					'=ENTITY.ID' => null,
					'=CHANNEL.CODE' => $channel->getCode(),
					'=CHANNEL.VALUE' => $channel->getValue(),
				],
				'order' => ['ID' => 'ASC']
			])->fetch();

			return $row ? $row['ID'] : null;
		}

		return null;
	}

	/**
	 * Save.
	 *
	 * @return int|null
	 */
	public function save()
	{
		$traceId = $this->detect();
		if ($traceId)
		{
			return $traceId;
		}

		$result = Internals\TraceTable::add([
			'SOURCE_ID' => $this->getSourceId(),
			'IS_MOBILE' => $this->isMobile ? 'Y' : 'N',
			'TAGS_RAW' => $this->utm,
			'PAGES_RAW' => array_map(
				function ($page)
				{
					$dateInsert = $page['DATE_INSERT'];
					/** @var DateTime $dateInsert */
					$page['DATE_INSERT'] = $dateInsert->getTimestamp();
					return $page;
				},
				$this->pages
			)
		]);
		if ($result->isSuccess())
		{
			$traceId = $result->getId();
			foreach ($this->channelCollection as $channel)
			{
				self::appendChannel($traceId, $channel);
			}
		}

		return $traceId;
	}
}