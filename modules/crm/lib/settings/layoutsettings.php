<?php
namespace Bitrix\Crm\Settings;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Main\Loader;

class LayoutSettings
{
	/** @var LayoutSettings */
	private static $current = null;

	/** @var BooleanSetting */
	private $enableSlider = null;
	/** @var BooleanSetting */
	private $enableCatalogPriceEdit = null;
	/** @var BooleanSetting */
	private $enableEntityCommodityItemCreation = null;
	/** @var BooleanSetting */
	private $enableCatalogPriceSave = null;
	/** @var BooleanSetting */
	private $enableSimpleTimeFormat = null;
	/** @var BooleanSetting */
	private $enableUserNameSorting = null;
	/** @var IntegerSetting */
	private $clientLayoutType = null;
	/** @var BooleanSetting */
	private $enableDedupeWizard = null;
	/** @var BooleanSetting */
	private $enableFullCatalog;

	function __construct()
	{
		$this->enableSlider = new BooleanSetting('enable_slider', true);
		$this->enableFullCatalog = new BooleanSetting('enable_full_catalog', true);
		$this->enableCatalogPriceEdit = new BooleanSetting('enable_product_price_edit', false);
		$this->enableEntityCommodityItemCreation = new BooleanSetting('enable_entity_commodity_item_creation', false);
		$this->enableCatalogPriceSave = new BooleanSetting('enable_catalog_price_save', false);
		$this->enableSimpleTimeFormat = new BooleanSetting('enable_simple_time_format', true);
		$this->enableUserNameSorting = new BooleanSetting('enable_user_name_sorting', false);
		$this->clientLayoutType = new IntegerSetting(
			'client_layout_type',
			Crm\Layout\ClientLayoutType::CONTACT_COMPANY
		);
		$this->enableDedupeWizard = new BooleanSetting('enable_dedupe_wizard', true);
	}
	/**
	 * Get current instance
	 * @return LayoutSettings
	 */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new LayoutSettings();
		}
		return self::$current;
	}
	/**
	 * Check if slider enabled for edit and view actions
	 * @return bool
	 */
	public function isSliderEnabled()
	{
		return $this->enableSlider->get();
	}
	/**
	 * Enabled slider for edit and view actions
	 * @param bool $enabled Enabled Flag.
	 * @return void
	 */
	public function enableSlider($enabled)
	{
		$this->enableSlider->set($enabled);
	}

	public function isFullCatalogEnabled(): bool
	{
		return Loader::includeModule('bitrix24') ? true : $this->enableFullCatalog->get();
	}

	public function enableFullCatalog(bool $enabled): void
	{
		$this->enableFullCatalog->set($enabled);
	}

	public function isCommonProductProcessingEnabled(): bool
	{
		return Loader::includeModule('catalog') && \Bitrix\Catalog\Config\Feature::isCommonProductProcessingEnabled();
	}

	/**
	 * @deprecated
	 *
	 * Check if user is allowed to change product prices from entity card
	 * @return bool
	 */
	public function isCatalogPriceEditEnabled(): bool
	{
		return !$this->isCommonProductProcessingEnabled() || $this->enableCatalogPriceEdit->get();
	}
	/**
	 * Check if user is allowed to create commodity item into entities without creation into catalog
	 * @return bool
	 */
	public function isCreationEntityCommodityItemAllowed(): bool
	{
		return $this->enableEntityCommodityItemCreation->get();
	}
	/**
	 * Enabled changing product prices from entity card
	 * @param bool $enabled Enabled Flag.
	 * @return void
	 */
	public function enableCatalogPriceEdit($enabled): void
	{
		$this->enableCatalogPriceEdit->set($enabled);
	}
	/**
	 * Enabled creation commodity item into entities without creation into catalog
	 * @param bool $enabled Enabled Flag.
	 * @return void
	 */
	public function enableEntityCommodityItemCreation($enabled): void
	{
		$this->enableEntityCommodityItemCreation->set($enabled);
	}
	/**
	 * @deprecated
	 *
	 * Check if user is allowed to change product prices from entity card
	 * @return bool
	 */
	public function isCatalogPriceSaveEnabled(): bool
	{
		return $this->isCommonProductProcessingEnabled() && $this->enableCatalogPriceSave->get();
	}
	/**
	 * Enabled saving product prices from entity card
	 * @param bool $enabled Enabled Flag.
	 * @return void
	 */
	public function enableCatalogPriceSave($enabled): void
	{
		$this->enableCatalogPriceSave->set($enabled);
	}
	/**
	 * Check if dedupe wizard enabled for duplicate control
	 * @return bool
	 */
	public function isDedupeWizardEnabled()
	{
		return $this->enableDedupeWizard->get();
	}
	/**
	 * Enable dedupe wizard for duplicate control
	 * @param bool $enabled Enabled Flag.
	 * @return void
	 */
	public function enableDedupeWizard($enabled)
	{
		$this->enableDedupeWizard->set($enabled);
	}
	/**
	 * Check if simple time format enabled for display system fields (CREATED, LAST_MODIFIED and etc)
	 * @return bool
	 */
	public function isSimpleTimeFormatEnabled()
	{
		return $this->enableSimpleTimeFormat->get();
	}
	/**
	 * Enable simple time format
	 * @param bool $enabled Enabled Flag.
	 * @return void
	 */
	public function enableSimpleTimeFormat($enabled)
	{
		$this->enableSimpleTimeFormat->set($enabled);
	}
	/**
	 * Check if user name sorting enabled
	 * @return bool
	 */
	public function isUserNameSortingEnabled()
	{
		return $this->enableUserNameSorting->get();
	}
	/**
	 * Enable user name sorting
	 * @param bool $enabled Enabled Flag.
	 * @return void
	 */
	public function enableUserNameSorting($enabled)
	{
		$this->enableUserNameSorting->set($enabled);
	}
	/**
	 * Get client layout type
	 * @return int
	 */
	public function getClientLayoutType()
	{
		return $this->clientLayoutType->get();
	}
	/**
	 * Set client layout type
	 * @param int $layoutType Layout type (see \Bitrix\Crm\Layout\ClientLayoutType).
	 * @return void
	 */
	public function setClientLayoutType($layoutType)
	{
		$this->clientLayoutType->set($layoutType);
	}
}