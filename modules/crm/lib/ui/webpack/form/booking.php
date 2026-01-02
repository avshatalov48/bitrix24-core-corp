<?php

namespace Bitrix\Crm\UI\Webpack\Form;

use Bitrix\Crm\UI\Webpack;

/**
 * Class Booking
 *
 * @package Bitrix\Crm\UI\Webpack\Form
 */
class Booking extends Webpack\Base
{
	protected static $instance;

	protected static $type = 'form.booking_v2';

	public static function instance(): static
	{
		if (!static::$instance)
		{
			static::$instance = new static(1);
		}

		return static::$instance;
	}

	public static function rebuildAgent(): string
	{
		if ((new static(1))->build())
		{
			return '';
		}
		else
		{
			return static::class . '::rebuildAgent();';
		}
	}

	/**
	 * Configure. Set extensions and modules to controller.
	 */
	public function configure(): void
	{
		$this->fileDir = 'form';
		$this->fileName = 'booking.js';

		$this->addExtension('crm.site.form.booking');

		$this->embeddedModuleName = 'crm.form.booking';
	}
}
