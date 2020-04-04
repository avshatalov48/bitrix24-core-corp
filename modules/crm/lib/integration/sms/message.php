<?php

namespace Bitrix\Crm\Integration\Sms;

use Bitrix\Main\Error;

class Message
{
	/** @var Provider\Base $provider */
	protected $provider;

	protected $from;
	protected $to;
	protected $text;

	/**
	 * Message constructor.
	 * @param Provider\Base|null $provider
	 */
	public function __construct(Provider\Base $provider = null)
	{
		if ($provider)
		{
			$this->setProvider($provider);
		}
	}

	/**
	 * @param string|null $to
	 * @return SendMessageResult Send operation result.
	 */
	public function send($to = null)
	{
		if ($to)
		{
			$this->setTo($to);
		}
		$provider = $this->getProvider();
		if (!$provider)
		{
			$result = new SendMessageResult();
			$result->addError(new Error('Provider is not set'));
			return $result;
		}

		return $provider->sendMessage($this);
	}

	/**
	 * @return mixed
	 */
	public function getFrom()
	{
		return $this->from;
	}

	/**
	 * @param mixed $from
	 * @return $this
	 */
	public function setFrom($from)
	{
		$this->from = (string)$from;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getText()
	{
		return $this->text;
	}

	/**
	 * @param mixed $text
	 * @return $this
	 */
	public function setText($text)
	{
		$this->text = (string)$text;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getTo()
	{
		return $this->to;
	}

	/**
	 * @param mixed $to
	 * @return $this
	 */
	public function setTo($to)
	{
		$to = \NormalizePhone($to);
		if ($to && strlen($to) >= 10)
		{
			$this->to = '+'.$to;
		}
		return $this;
	}

	/**
	 * @return Provider\Base|null
	 */
	public function getProvider()
	{
		return $this->provider;
	}

	/**
	 * @param Provider\Base $provider
	 * @return $this
	 */
	public function setProvider(Provider\Base $provider)
	{
		$this->provider = $provider;
		return $this;
	}
}