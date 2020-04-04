<?php

namespace Bitrix\Crm\UI\Webpack;

/**
 * Class Guest
 *
 * @package Bitrix\Crm\UI\Webpack
 */
class Guest extends Base
{
	/** @var static $instance */
	protected static $instance;

	protected static $type = self::TYPE_GUEST;

	/**
	 * Get instance.
	 *
	 * @return static
	 */
	public static function instance()
	{
		if (!static::$instance)
		{
			static::$instance = new static(1);
		}

		return static::$instance;
	}

	/**
	 * Rebuild agent.
	 *
	 * @return string
	 */
	public static function rebuildAgent()
	{
		if ((new static(1))->build())
		{
			return '';
		}
		else
		{
			return '\\Bitrix\\Crm\\UI\\Webpack\\Guest::rebuildAgent();';
		}
	}

	/**
	 * Configure. Set extensions and modules to controller.
	 *
	 * @return void
	 */
	public function configure()
	{
		$this->addExtension('crm.tracking.guest');
		$this->embeddedModuleName = 'crm.tracking.guest.loader';
	}
}