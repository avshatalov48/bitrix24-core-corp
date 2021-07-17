<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Channel;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Entity;

Loc::loadMessages(__FILE__);

/**
 * Class Base
 *
 * @package Bitrix\Crm\Tracking\Channel
 */
class Base
{
	const Mail = 'mail';
	const Call = 'call';
	const Imol = 'imol';
	const Site = 'site';
	const Site24 = 'site24';
	const Shop24 = 'shop24';
	const CrmShop = 'crm-shop';
	const SiteDomain = 'site-domain';
	const Button = 'button';
	const Form = 'form';
	const Callback = 'callback';
	const Rest = 'rest';
	const Order = 'order';
	const SalesCenter = 'sales-center';
	const FbLeadAds = 'fb-lead-ads';
	const VkLeadAds = 'vk-lead-ads';

	/** @var string $code Code. */
	protected $code;

	/**	@var string $value Value. */
	protected $value;

	/**	@var Entity\Identificator\ComplexCollection|null $entities Entity collection. */
	private $entities;

	/**	@var Collection $channels Channel collection. */
	private $channels;

	/**
	 * Return true if it is configured.
	 *
	 * @return bool
	 */
	public static function isConfigured()
	{
		return true;
	}

	/**
	 * Get name by code.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	public static function getNameByCode($code)
	{
		return Loc::getMessage('CRM_TRACKING_CHANNEL_BASE_NAME_'.mb_strtoupper($code)) ?: $code;
	}

	/**
	 * Get grid name by code.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	public static function getGridNameByCode($code)
	{
		return Loc::getMessage('CRM_TRACKING_CHANNEL_BASE_GRID_NAME_'.mb_strtoupper($code)) ?: self::getNameByCode($code);
	}

	/**
	 * Get source ID.
	 *
	 * @return int|null
	 */
	public function getSourceId()
	{
		return null;
	}

	/**
	 * Get entities.
	 *
	 * @return Entity\Identificator\ComplexCollection
	 */
	public function getEntities()
	{
		if (!$this->entities)
		{
			$this->entities = new Entity\Identificator\ComplexCollection();
		}

		return $this->entities;
	}

	/**
	 * Get channels.
	 *
	 * @return Collection
	 */
	public function getChannels()
	{
		if (!$this->channels)
		{
			$this->channels = new Collection();
		}

		return $this->channels;
	}

	/**
	 * Return true if supports detecting trace.
	 *
	 * @return bool
	 */
	public function isSupportTraceDetecting()
	{
		return $this instanceof Features\TraceDetectable;
	}

	/**
	 * Return true if supports detecting entities.
	 *
	 * @return bool
	 */
	public function isSupportEntityDetecting()
	{
		return $this instanceof Features\EntityDetectable;
	}

	/**
	 * Return true if supports detecting channels.
	 *
	 * @return bool
	 */
	public function isSupportChannelDetecting()
	{
		return $this instanceof Features\EntityDetectable;
	}

	/**
	 * Return true if it implements site interface.
	 *
	 * @return bool
	 */
	public function isSite()
	{
		return $this instanceof Features\Site;
	}

	/**
	 * Return true if source directly required single channel.
	 *
	 * @return bool
	 */
	public function isSourceDirectlyRequiredSingleChannel()
	{
		return $this instanceof Features\SingleChannelSourceDetectable;
	}

	/**
	 * Get code.
	 *
	 * @return string|null
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * Get value.
	 *
	 * @return string|null
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Get name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return self::getNameByCode($this->getCode());
	}

	/**
	 * Get grid name.
	 *
	 * @return string
	 */
	public function getGridName()
	{
		return self::getGridNameByCode($this->getCode());
	}

	/**
	 * Get description.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->getValue();
	}

	/**
	 * Return true if can use.
	 *
	 * @return bool
	 */
	public function canUse()
	{
		return true;
	}

	/**
	 * Get items.
	 *
	 * @return array
	 */
	public function getItems()
	{
		return [];
	}

	/**
	 * To array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return [
			'CODE' => $this->getCode(),
			'VALUE' => $this->getValue(),
		];
	}
}