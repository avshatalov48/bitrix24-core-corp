<?php
namespace Bitrix\Crm\Settings;

use Bitrix\Crm\Service\Container;

class QuoteSettings
{
	use Traits\EnableFactory;
	use Traits\UseNumberInTitlePlaceholder;

	const VIEW_LIST = EntityViewSettings::LIST_VIEW_NAME;
	const VIEW_KANBAN = EntityViewSettings::KANBAN_VIEW_NAME;
	const VIEW_DEADLINES = EntityViewSettings::DEADLINES_VIEW_NAME;

	private static $current;
	private $enableViewEvent;
	private $isOpened;
	private $isUseNumberInTitlePlaceholder;

	public function __construct()
	{
		$this->isOpened = new BooleanSetting('quote_opened_flag', true);
		$this->enableViewEvent = new BooleanSetting('quote_enable_view_event', true);
		$this->initIsFactoryEnabledSetting(\CCrmOwnerType::Quote);
		$this->initIsUseNumberInTitlePlaceholderSettings(\CCrmOwnerType::Quote);
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

	public function isFactoryEnabled(): bool
	{
		return true;
	}
}
