<?php

namespace Bitrix\Crm\UI\Webpack;

use Bitrix\Main\Web\WebPacker;

/**
 * Class CallTrackerEditor
 *
 * @package Bitrix\Crm\UI\Webpack
 */
class CallTrackerEditor extends Base
{
	/** @var static $instance */
	protected static $instance;

	protected static $type = self::TYPE_CALL_TRACKER_EDITOR;

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
			return '\\Bitrix\\Crm\\UI\\Webpack\\CallTrackerEditor::rebuildAgent();';
		}
	}

	/**
	 * Configure. Set extensions and modules to controller.
	 *
	 * @return void
	 */
	public function configure()
	{
		$this->addExtension('crm.tracking.editor');
		$this->embeddedModuleName = 'crm.tracking.editor.loader';
	}
}