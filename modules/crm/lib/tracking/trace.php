<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking;

use Bitrix\Main\Event;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;

use Bitrix\Crm;
use Bitrix\Crm\UtmTable;

/**
 * Class Trace
 *
 * @package Bitrix\Crm\Tracking
 */
class Trace
{
	const DETECT_TIME_MINUTES = 10;
	const FIND_ENTITIES_TIME_DAYS = 7;

	protected $id;
	protected $url;
	protected $isMobile = false;
	protected $utm = [];
	protected $pages = [];
	protected $analyticsClient = [];

	/** @var Channel\Collection|null $channelCollection Channel collection. */
	protected $channelCollection;

	/** @var Crm\Entity\Identificator\ComplexCollection|null $entityCollection Entity collection. */
	protected $entityCollection;

	protected $sourceId;

	private $loaded = false;

	protected $ref;

	protected $gid;

	/** @var bool Use detecting of Date Create. */
	private $useDetectingOfDateCreate = false;

	/** @var bool Use trace detecting. */
	private $useTraceDetecting = true;

	/** @var DateTime|null Date create. */
	private $dateCreate;

	/** @var static[] Previous traces. */
	private $previousTraces = [];

	/**
	 * Create trace from string.
	 *
	 * @param string|null $string Trace string.
	 * @return static
	 */
	public static function create($string = null)
	{
		$instance = (new static());
		$string = trim((string) $string);
		if ($string)
		{
			try
			{
				$data = Json::decode($string);
				if (is_array($data))
				{
					$instance->loadByArray($data);
				}
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
		$this->entityCollection = new Crm\Entity\Identificator\ComplexCollection();

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

		$previousTraces = self::getValueByKey($data, 'previous');
		$previousTraces = self::getValueByKey($previousTraces, 'list');
		if (is_array($previousTraces))
		{
			$this->previousTraces = [];
			foreach (array_slice($previousTraces, -5) as $previousTrace)
			{
				if (empty($previousTrace) || !is_array($previousTrace))
				{
					continue;
				}

				$this->previousTraces[] = (new static())
					->useDetectingOfDateCreate()
					->loadByArray($previousTrace);
			}
		}

		$device = self::getValueByKey($data, 'device');
		$this->setMobile(self::getValueByKey($device, 'isMobile'));

		$pages = self::getValueByKey($data, 'pages');
		$pages = self::getValueByKey($pages, 'list');
		if (is_array($pages))
		{
			foreach ($pages as $page)
			{
				if (empty($page[0]))
				{
					continue;
				}

				$this->addPage($page[0], $page[1], $page[2]);
			}
		}

		$gid = self::getValueByKey($data, 'gid');
		if ($gid)
		{
			$this->setGid($gid);
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
		$tagTs = self::getValueByKey($tags, 'ts');
		if ($tagTs && is_numeric($tagTs) && $this->useDetectingOfDateCreate)
		{
			$this->dateCreate = DateTime::createFromTimestamp($tagTs);
		}

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
			foreach ($channels as $channelData)
			{
				if (empty($channelData['code']))
				{
					continue;
				}

				if (!Channel\Factory::isKnown($channelData['code']))
				{
					continue;
				}

				$channel = Channel\Factory::create(
					$channelData['code'],
					isset($channelData['value']) ? $channelData['value'] : null
				);

				if ($channel->isSupportChannelDetecting())
				{
					foreach ($channel->getChannels() as $detectedChannel)
					{
						$this->channelCollection->setChannel($detectedChannel);
					}
				}

				$this->channelCollection->setChannel($channel);
			}
		}

		$client = self::getValueByKey($data, 'client');
		if ($client)
		{
			$this->analyticsClient = $client;
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
	 * Get pages.
	 *
	 * @return array
	 */
	public function getPages()
	{
		return $this->pages;
	}

	/**
	 * Get guest id.
	 *
	 * @return string|null
	 */
	public function getGid()
	{
		return $this->gid;
	}

	/**
	 * Set guest id.
	 *
	 * @param string $gid Guest ID.
	 * @return $this
	 */
	public function setGid($gid)
	{
		$this->gid = $gid;
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
	 * Get url.
	 *
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
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
		$name = mb_strtoupper($name);
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
	 * Get utm.
	 *
	 * @return array
	 */
	public function getUtm()
	{
		return $this->utm;
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

	/*
	 * Get source ID.
	 *
	 * @return int|null
	 */
	public function getSourceId()
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
		if (!$ref)
		{
			return $this;
		}

		if (!$this->sourceId)
		{
			$this->sourceId = Internals\SourceTable::getSourceByReferrer($ref);
		}

		$refUri = new Uri($ref);
		$page = [
			'URL' => $ref,
			'DATE_INSERT' => (!empty($this->pages)
				? $this->pages[0]['DATE_INSERT']
				: new DateTime()
			),
			'TITLE' => $refUri->getHost(),
			'IS_REF' => true,
		];

		$this->pages = array_merge([$page], $this->pages);

		return $this;
	}

	/**
	 * Use detecting of date create.
	 *
	 * @param bool $mode Mode.
	 * @return $this
	 */
	public function useDetectingOfDateCreate($mode = true)
	{
		$this->useDetectingOfDateCreate = $mode;
		return $this;
	}

	/**
	 * Use trace detecting.
	 *
	 * @param bool $mode Mode.
	 * @return $this
	 */
	public function useTraceDetecting($mode = true)
	{
		$this->useTraceDetecting = $mode;
		return $this;
	}

	/**
	 * Get ID.
	 *
	 * @return int|null
	 */
	public function getId()
	{
		return $this->id;
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
		if ($this->loaded || !$this->useTraceDetecting)
		{
			return null;
		}

		foreach ($this->channelCollection as $channel)
		{
			/** @var Channel\Base $channel */
			if (!$channel->isSupportTraceDetecting())
			{
				continue;
			}

			return Internals\TraceTable::getSpareTraceIdByChannel(
				$channel->getCode(),
				$channel->getValue(),
				(new DateTime())->add('-' . self::DETECT_TIME_MINUTES . ' minutes')
			);
		}

		return null;
	}

	/**
	 * Detect entities.
	 *
	 * @return $this
	 */
	public function detectEntities()
	{
		foreach ($this->channelCollection as $channel)
		{
			/** @var Channel\Base $channel */
			if (!$channel->isSupportEntityDetecting())
			{
				continue;
			}

			$entities = $channel->getEntities();
			/** @var Crm\Entity\Identificator\ComplexCollection $entities */
			$this->entityCollection->add($entities->toArray());
			break;
		}

		return $this;
	}

	/**
	 * Save.
	 *
	 * @return int|null
	 */
	public function save()
	{
		if ($this->id)
		{
			return $this->id;
		}

		$traceId = $this->detect();
		if ($traceId)
		{
			return $traceId;
		}

		$this->detectEntities();

		foreach ($this->previousTraces as $previousTrace)
		{
			$previousTrace->save();
		}

		$trace = (new Internals\EO_Trace)
			->setSourceId($this->getSourceId())
			->setIsMobile($this->isMobile)
			->setTagsRaw($this->utm)
			->setHasChild(!empty($this->previousTraces))
			->setPagesRaw(array_map(
				function ($page)
				{
					$dateInsert = $page['DATE_INSERT'];
					// @var DateTime $dateInsert
					$page['DATE_INSERT'] = $dateInsert->getTimestamp();
					return $page;
				},
				$this->pages
			));

		if ($this->dateCreate)
		{
			$trace->setDateCreate($this->dateCreate);
		}

		$result = $trace->save();
		if ($result->isSuccess())
		{
			$this->id = $result->getId();
			foreach ($this->previousTraces as $previousTrace)
			{
				if (!$previousTrace->getId())
				{
					continue;
				}

				Internals\TraceTreeTable::add([
					'PARENT_ID' => $this->id,
					'CHILD_ID' => $previousTrace->getId()
				])->isSuccess();
			}
			foreach ($this->channelCollection as $channel)
			{
				self::appendChannel($this->id, $channel);
			}
			foreach ($this->entityCollection as $entity)
			{
				/** @var Crm\Entity\Identificator\Complex $entity */
				self::appendEntity($this->id, $entity->getTypeId(), $entity->getId());
			}

			Source\Level\TraceSplitter::instance()->split($this);
			$this->sendEventForAnalytics();
		}

		return $this->id;
	}

	public function getChannelCollection(): ?Channel\Collection
	{
		return $this->channelCollection;
	}

	public function getAnalyticsClient()
	{
		return $this->analyticsClient;
	}

	private function sendEventForAnalytics(): void
	{
		if (empty($this->analyticsClient))
		{
			return;
		}

		$event = new Event(
			'crm',
			'onGetAnalyticsAfterSaveTrace',
			['instance' => $this]
		);

		$event->send();
	}
}
