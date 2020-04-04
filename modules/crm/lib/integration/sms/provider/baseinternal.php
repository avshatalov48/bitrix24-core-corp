<?php

namespace Bitrix\Crm\Integration\Sms\Provider;

use Bitrix\Crm\Integration\Sms\MessageStatusResult;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Result;

abstract class BaseInternal extends Base
{
	protected $options;

	public function getType()
	{
		return static::PROVIDER_TYPE_INTERNAL;
	}

	/**
	 * Check demo status.
	 * @return bool
	 */
	public function isDemo()
	{
		return ($this->canUse() && $this->getOption('is_demo', true));
	}

	/**
	 * Check registration state.
	 * @return bool
	 */
	abstract public function isRegistered();

	/**
	 * Check is registration confirmed.
	 * @return bool
	 */
	public function isConfirmed()
	{
		return $this->isRegistered();
	}

	/**
	 * Get senders list.
	 * @return array
	 */
	abstract public function getSenderList();

	/**
	 * Get default sender alias.
	 * @return string
	 */
	abstract public function getDefaultSender();

	/**
	 * Set default sms sender alias.
	 * @param string $sender Sender alias.
	 * @return $this
	 */
	abstract public function setDefaultSender($sender);

	/**
	 * Check can use state of provider.
	 * @return bool
	 */
	public function canUse()
	{
		return ($this->isRegistered() && $this->isConfirmed());
	}

	/**
	 * @param array $fields
	 * @return Result
	 */
	abstract public function register(array $fields);

	/**
	 * @return array
	 */
	abstract public function getOwnerInfo();

	/**
	 * @param array $fields
	 * @return Result
	 */
	public function confirmRegistration(array $fields)
	{
		return new Result();
	}

	/**
	 * @return Result
	 */
	public function sendConfirmationCode()
	{
		return new Result();
	}

	/**
	 * @return string
	 */
	public function getManageUrl()
	{
		return '/crm/configs/sms/?provider='.$this->getId();
	}

	/**
	 * @return string
	 */
	abstract public function getExternalManageUrl();

	/**
	 * @param string $messageId Message Id.
	 * @return MessageStatusResult Message status result.
	 */
	abstract public function getMessageStatus($messageId);

	/**
	 * Enable demo mode.
	 * @return $this
	 */
	public function enableDemo()
	{
		$this->setOption('is_demo', true);
		return $this;
	}

	/**
	 * Disable demo mode.
	 * @return $this
	 */
	public function disableDemo()
	{
		$this->setOption('is_demo', false);
		return $this;
	}

	/**
	 * @param array $options
	 * @return $this
	 */
	protected function setOptions(array $options)
	{
		$this->options = $options;
		$providerId = $this->getId();
		Option::set('crm','integration.sms.'.$providerId, serialize($options));
		return $this;
	}

	/**
	 * @return array
	 */
	protected function getOptions()
	{
		if ($this->options === null)
		{
			$providerId = $this->getId();
			$optionsString = Option::get('crm', 'integration.sms.'.$providerId);
			if (CheckSerializedData($optionsString))
			{
				$this->options = unserialize($optionsString);
			}

			if (!is_array($this->options))
			{
				$this->options = array();
			}
		}
		return $this->options;
	}

	/**
	 * @param $optionName
	 * @param $optionValue
	 * @return $this
	 * @internal param array $options
	 */
	protected function setOption($optionName, $optionValue)
	{
		$options = $this->getOptions();
		if (!isset($options[$optionName]) || $options[$optionName] !== $optionValue)
		{
			$options[$optionName] = $optionValue;
			$this->setOptions($options);
		}
		return $this;
	}

	/**
	 * @param $optionName
	 * @param mixed $defaultValue
	 * @return mixed|null
	 */
	protected function getOption($optionName, $defaultValue = null)
	{
		$options = $this->getOptions();
		return isset($options[$optionName]) ? $options[$optionName] : $defaultValue;
	}

	/**
	 * @return bool
	 */
	public function clearOptions()
	{
		$this->options = array();
		$providerId = $this->getId();
		Option::delete('crm', array('name' => 'integration.sms.'.$providerId));
		return true;
	}
}