<?php

namespace Bitrix\Crm\UI\Webpack\Form;

use Bitrix\Crm\UI\Webpack;

/**
 * Class ResourceBooking
 *
 * @package Bitrix\Crm\UI\Webpack\Form
 */
class ResourceBooking extends Webpack\Base
{
	/** @var static $instance */
	protected static $instance;

	protected static $type = 'form.booking';

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
			return '\\Bitrix\\Crm\\UI\\Webpack\\Form\\ResourceBooking::rebuildAgent();';
		}
	}

	/**
	 * Configure. Set extensions and modules to controller.
	 *
	 * @return void
	 */
	public function configure()
	{
		$this->fileDir = 'form';
		$this->fileName = 'resourcebooking.js';

		$this->addExtension('crm.site.form.resourcebooking');
		//$this->addExtension('ui.vue.components.datepick');
		//$this->addExtension('calendar.resourcebooking');

		$this->embeddedModuleName = 'crm.form.resourcebooking';
	}
}