<?php

namespace Bitrix\Crm\UI\Webpack\Form;

use Bitrix\Crm\UI\Webpack;

/**
 * Class App
 *
 * @package Bitrix\Crm\UI\Webpack\Form
 */
class App extends Webpack\Base
{
	/** @var static $instance */
	protected static $instance;

	protected static $type = 'form.app';

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
			return '\\Bitrix\\Crm\\UI\\Webpack\\Form\\App::rebuildAgent();';
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
		$this->fileName = 'app.js';
		$this->addExtension('crm.site.form.embed');
		$this->embeddedModuleName = 'crm.site.form.loader';
	}

	protected function configureFile()
	{
		$this->fileDir = 'form';
		$this->fileName = 'app.js';
	}
}