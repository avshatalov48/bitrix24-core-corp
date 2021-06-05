<?php
namespace Bitrix\Crm\Settings;

/**
 * This class contains some common settings specific for dynamic types.
 */
class DynamicSettings
{
	private static $current;
	private $isEnabled;

	/**
	 * Constructor.
	 */
	protected function __construct()
	{
		$this->isEnabled = new BooleanSetting('dynamic_enable_factory', false);
	}

	/**
	 * Get actual instance of this class.
	 *
	 * @return static
	 */
	public static function getCurrent(): self
	{
		if(self::$current === null)
		{
			self::$current = new self();
		}
		return self::$current;
	}

	/**
	 * Return true if dynamic types enabled.
	 *
	 * @return bool
	 */
	public function isEnabled(): bool
	{
		return $this->isEnabled->get();
	}

	/**
	 * Set new state of enabled setting.
	 *
	 * @param bool $isEnabled
	 */
	public function setEnabled(bool $isEnabled): void
	{
		$this->isEnabled->set($isEnabled);
	}
}