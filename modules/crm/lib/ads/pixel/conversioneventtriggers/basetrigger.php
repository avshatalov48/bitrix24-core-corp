<?php

namespace Bitrix\Crm\Ads\Pixel\ConversionEventTriggers;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Crm\Ads\Pixel\ConversionWrapper;
use Bitrix\Crm\Ads\Pixel\Configuration\Configuration;
use Bitrix\Crm\Ads\Pixel\EventBuilders\CrmConversionEventBuilderInterface;

/**
 * Class BaseTrigger
 * @package Bitrix\Crm\Ads\Pixel\ConversionEventTriggers
 */
abstract class BaseTrigger
{
	/** @var Configuration|null */
	protected $configuration;

	/** @var ConversionWrapper|null $conversion*/
	protected $conversion;

	/**
	 * @return bool
	 */
	protected abstract function checkConfiguration() : bool;

	/**
	 * @return string
	 */
	protected abstract function getCode() : string;

	/**
	 * @return string
	 */
	protected abstract function getType() : string;

	/**
	 * @return CrmConversionEventBuilderInterface
	 */
	protected abstract function getConversionEventBuilder() : CrmConversionEventBuilderInterface;

	/**
	 * BaseTrigger constructor.
	 */
	public function __construct()
	{
		$serviceLocator = ServiceLocator::getInstance();
		if ($serviceLocator->has('crm.service.ads.conversion.configurator'))
		{
			$this->configuration = $serviceLocator->get('crm.service.ads.conversion.configurator')->load($this->getCode());
		}
		if ($serviceLocator->has('crm.service.ads.conversion.facebook'))
		{
			$this->conversion = $serviceLocator->get('crm.service.ads.conversion.facebook');
		}
	}

	/**
	 * @return ConversionWrapper|null
	 */
	public function getWrapper() : ?ConversionWrapper
	{
		return $this->conversion;
	}

	/**
	 * @return Configuration|null
	 */
	public function getConfiguration() : ?Configuration
	{
		return $this->configuration;
	}

	/**
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function execute() : void
	{
		if ($this->checkConfiguration() && $this->getWrapper() && $this->getWrapper()->isAvailable())
		{
			$this->getWrapper()->addEvents($this->getConversionEventBuilder());
		}
	}
}