<?php

namespace Bitrix\Crm\Ads\Pixel;

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Seo\Conversion\ConversionEventInterface;
use Bitrix\Seo\Conversion\ConversionObjectInterface;
use Bitrix\Crm\Ads\Pixel\EventBuilders\CrmConversionEventBuilderInterface;

/**
 * Class ConversionWrapper
 * @package Bitrix\Crm\Ads\Pixel
 */
class ConversionWrapper
{
	/**@var ConversionObjectInterface|null $conversion*/
	protected $conversion;

	/**
	 * ConversionWrapper constructor.
	 *
	 * @param ConversionObjectInterface|null $conversion
	 *
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function __construct(?ConversionObjectInterface $conversion)
	{
		$this->conversion = $conversion;
		if ($this->isAvailable())
		{
			Application::getInstance()->addBackgroundJob(
				function() {
					try
					{
						if ($this->isAvailable() && !empty($this->conversion->getEvents()))
						{
							$this->conversion->fireEvents();
						}
					}
					catch (\Throwable $throwable)
					{
					}
				}
			);
		}
	}

	/**
	 * @param CrmConversionEventBuilderInterface $eventBuilder
	 *
	 * @return $this
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function addEvents(CrmConversionEventBuilderInterface $eventBuilder)
	{
		if ($this->isAvailable())
		{
			foreach ($eventBuilder->buildEvents() as $event)
			{
				if ($event instanceof ConversionEventInterface)
				{
					$this->conversion->addEvent($event);
				}
			}
		}

		return $this;
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function isAvailable() : bool
	{
		return
			$this->conversion &&
			Loader::includeModule('seo') &&
			Loader::includeModule('socialservices') &&
			$this->conversion->isAvailable()
			;
	}

	/**
	 * @return string
	 */
	public function getType() : string
	{
		return $this->conversion->getType();
	}


}