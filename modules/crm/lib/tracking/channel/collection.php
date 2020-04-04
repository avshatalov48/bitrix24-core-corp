<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2015 Bitrix
 */

namespace Bitrix\Crm\Tracking\Channel;

use Bitrix\Main\Type\Dictionary;

/**
 * Class Collection
 *
 * @package Bitrix\Crm\Tracking\Channel
 */
class Collection extends Dictionary
{
	/**
	 * Constructor Collection.
	 * @param Base[] $values Initial channels in the collection.
	 */
	public function __construct(array $values = null)
	{
		if($values)
		{
			$this->add($values);
		}
	}

	/**
	 * Add an array of channel instances to the collection.
	 *
	 * @param Base[] $items Channel instance.
	 * @return void
	 */
	public function add(array $items)
	{
		foreach($items as $channel)
		{
			$this->setChannel($channel);
		}
	}

	/**
	 * Add channel to the collection.
	 *
	 * @param string $code Code.
	 * @param string $value Value.
	 * @return void
	 */
	public function addChannel($code, $value)
	{
		if (!Factory::isKnown($code))
		{
			return;
		}

		$this->setChannel(Factory::create($code, $value));
	}

	/**
	 * Add an complex identificator to the collection.
	 *
	 * @param Base $channel Channel object.
	 * @param int $offset Offset in the array.
	 * @return void
	 */
	public function setChannel(Base $channel, $offset = null)
	{
		parent::offsetSet($offset, $channel);
	}


	/**
	 * \ArrayAccess thing.
	 *
	 * @param mixed $offset Offset.
	 * @param mixed $value Value.
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		$this->setChannel($value, $offset);
	}

	/**
	 * Get source ID.
	 *
	 * @return int|null
	 */
	public function getSourceId()
	{
		$this->rewind();
		$firstChannel = $this->current();
		if (!$firstChannel)
		{
			return null;
		}

		/** @var Base $firstChannel */
		if ($firstChannel->isSourceDirectlyRequiredSingleChannel())
		{
			return $firstChannel->getSourceId();
		}

		foreach ($this->values as $channel)
		{
			/** @var Base $channel */
			if ($channel->isSourceDirectlyRequiredSingleChannel())
			{
				continue;
			}

			$sourceId = $channel->getSourceId();
			if ($sourceId)
			{
				return $sourceId;
			}
		}

		return null;
	}

	/**
	 * Convert to array of identificators.
	 *
	 * @return array
	 */
	public function toSimpleArray()
	{
		return array_map(
			function ($item)
			{
				/** @var Base $item */
				return $item->toArray();
			},
			$this->toArray()
		);
	}

	/**
	 * Convert to array.
	 *
	 * @return Base[]
	 */
	public function toArray()
	{
		return parent::toArray();
	}
}
