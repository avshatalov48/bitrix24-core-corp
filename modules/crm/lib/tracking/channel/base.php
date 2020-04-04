<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Channel;

use Bitrix\Main\Localization\Loc;

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
	const SiteDomain = 'site-domain';
	const Button = 'button';
	const Form = 'form';
	const Callback = 'callback';

	/** @var string $code Code. */
	protected $code;

	/**	@var string $value Value. */
	protected $value;

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
	 * Return true if supports detecting trace.
	 *
	 * @return bool
	 */
	public function isSupportDetecting()
	{
		return false;
	}

	/**
	 * Return true if it implements site interface.
	 *
	 * @return bool
	 */
	public function isSite()
	{
		return $this instanceof iSite;
	}

	/**
	 * Return true if source directly required single channel.
	 *
	 * @return bool
	 */
	public function isSourceDirectlyRequiredSingleChannel()
	{
		return false;
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
		return Loc::getMessage('CRM_TRACKING_CHANNEL_BASE_NAME_' . strtoupper($this->getCode())) ?: $this->getCode();
	}

	/**
	 * Get grid name.
	 *
	 * @return string
	 */
	public function getGridName()
	{
		return Loc::getMessage('CRM_TRACKING_CHANNEL_BASE_GRID_NAME_' . strtoupper($this->getCode())) ?: $this->getName();
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