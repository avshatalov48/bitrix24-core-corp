<?php
namespace Bitrix\Sale\Helpers\Admin\Blocks;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class OrderBasketSettings
{
	protected $idPrefix = "";
	protected $allColumns = array();
	protected $visibleColumns = array();
	protected $isShowPropsRawVisible = false;

	protected static $jsInited = false;

	public function __construct(array $params)
	{
		$this->idPrefix = $params["ID_PREFIX"];
		$this->settingsDlgObjectName = $params["SETTINGS_DLG_OBJECT_NAME"];
		$this->allColumns = $params["ALL_COLUMNS"];
		$this->visibleColumns = $params["VISIBLE_COLUMNS"];
	}

	public function getScripts()
	{
		$result = '';

		if(!static::$jsInited)
		{
			\Bitrix\Main\Page\Asset::getInstance()->addJs("/bitrix/js/sale/admin/order_basket_settings.js");

			$result .= '
					BX.message({
						SALE_ORDER_BASKET_JS_SETTINGS_TITLE: "'.\CUtil::JSEscape(Loc::getMessage("SALE_ORDER_BASKET_JS_SETTINGS_TITLE")).'",
						SALE_ORDER_BASKET_JS_SETTINGS_APPLY: "'.\CUtil::JSEscape(Loc::getMessage("SALE_ORDER_BASKET_JS_SETTINGS_APPLY")).'"
					});
			';

			static::$jsInited = true;
		}

		return $result;
	}

	public function getHtml()
	{
		$availableColumns = array_diff_key($this->allColumns, $this->visibleColumns);
		$arAvailableColumnsHTML = "";

		foreach ($availableColumns as $key => $value)
			$arAvailableColumnsHTML .= "<option value=".$key.">".htmlspecialcharsbx($value)."</option>";

		$arUserColumnsHTML = "";

		foreach ($this->visibleColumns as $key => $value)
			$arUserColumnsHTML .= "<option value=".$key.">".htmlspecialcharsbx($value)."</option>";

		$settingsTemplate = '
			<div id="'.$this->idPrefix.'columns_form">
				<table width="100%">
					<tr>
						<td colspan="2" align="center">
							<table>
								<tr>
									<td style="background-image:none" nowrap>
										<div style="margin-bottom:5px">'.Loc::getMessage("SALE_ORDER_BASKET_SETTINGS_AVAILABLE_COLUMNS").'</div>
										<div class="scrollable">
											<select
												id="adm-sale-basket-sett-all-cols"
												name="allColumns"
												class="settings_select"
												multiple
												size="'.(count($this->allColumns) - count($this->visibleColumns)).'"
												ondblclick="this.form.add_btn.onclick()"
												onchange="'.$this->settingsDlgObjectName.'.onAvailableChange(this);"
											>
											'.$arAvailableColumnsHTML.'
											</select>
										</div>
									</td>
									<td style="background-image:none">
										<div style="margin-bottom:5px">
											<input type="button" name="add_btn" value="&gt;" title="'.Loc::getMessage("SALE_ORDER_BASKET_SETTINGS_ADD_COLUMN").'" style="width:30px;" disabled onclick="jsSelectUtils.addSelectedOptions(this.form.allColumns, this.form.columns, false); jsSelectUtils.deleteSelectedOptions(this.form.allColumns); ">
										</div>
										<div style="margin-bottom:5px">
											<input type="button" name="del_btn" value="&lt;" title="'.Loc::getMessage("SALE_ORDER_BASKET_SETTINGS_DELETE_COLUMN").'" style="width:30px;" disabled onclick="jsSelectUtils.addSelectedOptions(this.form.columns, this.form.allColumns, false, true); jsSelectUtils.deleteSelectedOptions(this.form.columns);">
										</div>
									</td>
									<td style="background-image:none" nowrap>
										<div style="margin-bottom:5px">'.Loc::getMessage("SALE_ORDER_BASKET_SETTINGS_SELECTED_COLUMNS").'</div>
										<div class="scrollable">
											<select
												class="settings_select"
												name="columns"
												multiple
												size="'.count($this->visibleColumns).'"
												ondblclick="this.form.del_btn.onclick()"
												onchange="'.$this->settingsDlgObjectName.'.onSelectedChange(this);"
												>
											'.$arUserColumnsHTML.'
											</select>
										</div>
									</td>
									<td style="background-image:none">
										<div style="margin-bottom:5px"><input type="button" name="up_btn" value="'.Loc::getMessage("SALE_ORDER_BASKET_SETTINGS_UP").'" title="'.Loc::getMessage("SALE_ORDER_BASKET_SETTINGS_MOVE_UP").'" class="bx-grid-btn" style="width:60px;" disabled onclick="jsSelectUtils.moveOptionsUp(this.form.columns)"></div>
										<div style="margin-bottom:5px"><input type="button" name="down_btn" value="'.Loc::getMessage("SALE_ORDER_BASKET_SETTINGS_DOWN").'" title="'.Loc::getMessage("SALE_ORDER_BASKET_SETTINGS_MOVE_DOWN").'" class="bx-grid-btn" style="width:60px;" disabled onclick="jsSelectUtils.moveOptionsDown(this.form.columns)"></div>
									</td>
								</tr>
								' . ($this->isShowPropsRawVisible ? $this->getShowPropsRowHtml() : '') . '								
							</table>
						</td>
					</tr>
				</table>
			</div>';

		return $settingsTemplate;
	}

	public function setShowPropsVisible(bool $isVisible): void
	{
		$this->isShowPropsRawVisible = $isVisible;
	}

	protected function getShowPropsRowHtml(): string
	{
		$isShowPropVisible = static::loadIsShowPropsVisible();

		return '
			<tr id="adm-sale-basket-sett-show-props">
				<td colspan="3">
					<div style="margin-top: 15px;">
						' . Loc::getMessage("SALE_ORDER_BASKET_SETTINGS_SHOW_PROPERTIES")
						. ': <input type="checkbox" name="show_properties"'
						. ($isShowPropVisible ? ' checked' : '').'>
					</div>
				</td>
			</tr>';
	}

	public static function loadIsShowPropsVisible(): bool
	{
		return (Option::get('sale', 'order_basket_settings_show_props_visible', 'Y') === 'Y');
	}

	public static function saveIsShowPropsVisible(bool $isVisible): void
	{
		Option::set('sale', 'order_basket_settings_show_props_visible', ($isVisible ? 'Y' : 'N'));
	}
}