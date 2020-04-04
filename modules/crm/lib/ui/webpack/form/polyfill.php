<?php

namespace Bitrix\Crm\UI\Webpack\Form;

use Bitrix\Crm\UI\Webpack;

/**
 * Class Polyfill
 *
 * @package Bitrix\Crm\UI\Webpack\Form
 */
class Polyfill extends Webpack\Base
{
	/** @var static $instance */
	protected static $instance;

	protected static $type = 'form.polyfill';

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
			return '\\Bitrix\\Crm\\UI\\Webpack\\Form\\Polyfill::rebuildAgent();';
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
		$this->fileName = 'polyfill.js';
		$this->addExtension('crm.site.form.polyfill');
		$this->embeddedModuleName = 'crm.site.form.polyfill.loader';
	}
}