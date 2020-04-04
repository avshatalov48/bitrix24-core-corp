<?php

namespace Bitrix\Crm\Integration\Sms\Provider;

use Bitrix\Crm\Integration\Sms\Message;
use Bitrix\Crm\Integration\Sms\SendMessageResult;

abstract class Base
{
	const PROVIDER_TYPE_INTERNAL = 'internal';
	const PROVIDER_TYPE_REST = 'rest';

	protected $options;

	/**
	 * Get type of provider.
	 * @return string PROVIDER_TYPE_INTERNAL or PROVIDER_TYPE_REST
	 */
	abstract public function getType();

	public function isInternal()
	{
		return (static::getType() === static::PROVIDER_TYPE_INTERNAL);
	}

	public function isRest()
	{
		return (static::getType() === static::PROVIDER_TYPE_REST);
	}

	/**
	 * @return string Unique id.
	 */
	abstract public function getId();

	public function getExternalId()
	{
		return $this->getType().':'.$this->getId();
	}

	/**
	 * @return string
	 */
	abstract public function getName();

	/**
	 * @return string
	 */
	abstract public function getShortName();

	/**
	 * Check can use state of provider.
	 * @return bool
	 */
	abstract public function canUse();

	/**
	 * @param Message $message
	 * @return SendMessageResult Send operation result.
	 */
	abstract public function sendMessage(Message $message);

	/**
	 * @return string Manage url.
	 */
	abstract public function getManageUrl();
}