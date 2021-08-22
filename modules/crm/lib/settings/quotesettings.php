<?php
namespace Bitrix\Crm\Settings;

use Bitrix\Crm\Service\Container;

class QuoteSettings
{
	const VIEW_LIST = EntityViewSettings::LIST_VIEW;
	const VIEW_KANBAN = EntityViewSettings::KANBAN_VIEW;

	private static $current;
	private $enableViewEvent;
	private $isOpened;
	private $isFactoryEnabled;

	public function __construct()
	{
		$this->isOpened = new BooleanSetting('quote_opened_flag', true);
		$this->enableViewEvent = new BooleanSetting('quote_enable_view_event', true);
		$this->isFactoryEnabled = new BooleanSetting('quote_enable_factory', true);
	}
	/**
	 * Get current instance
	 * @return QuoteSettings
	 */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new QuoteSettings();
		}
		return self::$current;
	}
	/**
	 * Get value of flag 'OPENED'
	 * @return bool
	 */
	public function getOpenedFlag()
	{
		return $this->isOpened->get();
	}
	/**
	 * Set value of flag 'OPENED'
	 * @param bool $opened Opened Flag.
	 * @return void
	 */
	public function setOpenedFlag($opened)
	{
		$this->isOpened->set($opened);
	}

	/**
	 * Return true if new interface and api through Service\Factory is used to process quotes.
	 *
	 * @return bool
	 */
	public function isFactoryEnabled(): bool
	{
		return $this->isFactoryEnabled->get();
	}

	/**
	 * Set state of isFactoryEnabled setting.
	 *
	 * @param bool $isEnabled
	 */
	public function setFactoryEnabled(bool $isEnabled): void
	{
		$this->isFactoryEnabled->set($isEnabled);
	}

	/**
	 * Get current list view ID
	 * @return int
	 * @deprecated Use \Bitrix\Crm\Service\Router::getCurrentListView instead
	 */
	public function getCurrentListViewID()
	{
		$view = Container::getInstance()->getRouter()->getCurrentListView(\CCrmOwnerType::Quote);
		return EntityViewSettings::resolveID($view);
	}
	/**
	 * Get default list view ID
	 * @return int
	 * @deprecated Use \Bitrix\Crm\Service\Factory::getDefaultListView instead
	 */
	public function getDefaultListViewID()
	{
		return Container::getInstance()->getRouter()->getDefaultListView(\CCrmOwnerType::Quote);
	}
	/**
	 * Set default list view ID
	 * @param int $viewID View ID.
	 * @return void
	 * @deprecated Use \Bitrix\Crm\Service\Factory::setDefaultListView instead
	 */
	public function setDefaultListViewID($viewID)
	{
		Container::getInstance()->getRouter()->setDefaultListView(\CCrmOwnerType::Quote, $viewID);
	}
}