<?php

namespace Bitrix\Sale\Delivery\ExtraServices;

use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Enum extends Base
{
	public function __construct($id, array $structure, $currency, $value = null, array $additionalParams = array())
	{
		$prices = !empty($structure["PARAMS"]["PRICES"]) && is_array($structure["PARAMS"]["PRICES"]) ? $structure["PARAMS"]["PRICES"] : array();
		$structure["PARAMS"]["ONCHANGE"] = $this->createJSOnchange($id, $prices);
		parent::__construct($id, $structure, $currency,  $value);
		$this->params["TYPE"] = "ENUM";
		$this->params["OPTIONS"] = array();
	}

	public static function getClassTitle()
	{
		return Loc::getMessage("DELIVERY_EXTRA_SERVICE_ENUM_TITLE");
	}

	public function getCost()
	{
		if (
			!isset($this->params["PRICES"])
			|| !is_array($this->params["PRICES"])
		)
		{
			throw new SystemException("Service id: " . $this->id . " doesn't have field array PRICES");
		}

		if (isset($this->params["PRICES"][$this->value]["PRICE"]))
		{
			$result = $this->params["PRICES"][$this->value]["PRICE"];
		}
		else
		{
			$row = reset($this->params["PRICES"]);
			$result =$row["PRICE"] ?? 0;
		}

		return $this->convertToOperatingCurrency($result);
	}

	public static function prepareParamsToSave(array $params)
	{
		if(!isset($params["PARAMS"]["PRICES"]) || !is_array($params["PARAMS"]["PRICES"]))
			return $params;

		foreach($params["PARAMS"]["PRICES"] as $id => $price)
			if($price["TITLE"] == '')
				unset($params["PARAMS"]["PRICES"][$id]);

		return $params;
	}

	public static function getAdminParamsName()
	{
		return Loc::getMessage("DELIVERY_EXTRA_SERVICE_ENUM_LIST");
	}

	public static function getAdminParamsControl($name, array $params, $currency = "")
	{
		$result = '<div style="border: 1px solid #e0e8ea; padding: 10px; width: 500px;">';

		if(isset($params["PARAMS"]["PRICES"]) && is_array($params["PARAMS"]["PRICES"]))
		{
			foreach($params["PARAMS"]["PRICES"] as $id => $price)
			{
				if(!isset($params["PARAMS"]["PRICES"][$id]))
					$params["PARAMS"]["PRICES"][$id] = 0;

				$result .= self::getValueHtml($name, $id, $price["TITLE"], $price["PRICE"] ?? 0, $currency)."<br><br>";
			}
		}

		$i = strval(time());
		$result .= self::getValueHtml($name, $i, "", "", $currency)."<br><br>".
			'<input type="button" value="'.Loc::getMessage("DELIVERY_EXTRA_SERVICE_ENUM_ADD").
				'" onclick=\'var d=new Date(); '.
				'this.parentNode.insertBefore(BX.create("span",{html: this.nextElementSibling.innerHTML.replace(/\#ID\#/g, d.getTime())}), this);\'>'.
			'<span style="display:none;">'.self::getValueHtml($name, '#ID#')."<br><br>".'</span><br><br></div>';

		return $result;
	}

	protected static function getValueHtml($name, $id, $title = "", $price = "", $currency = "")
	{
		$price = roundEx((float)$price, SALE_VALUE_PRECISION);
		$currency = htmlspecialcharsbx((string)$currency);

		return Loc::getMessage("DELIVERY_EXTRA_SERVICE_ENUM_NAME").
			':&nbsp;<input name="'.$name.'[PARAMS][PRICES]['.$id.'][TITLE]" value="'.htmlspecialcharsbx($title).'">&nbsp;&nbsp;'.
			Loc::getMessage("DELIVERY_EXTRA_SERVICE_ENUM_PRICE").
			':&nbsp;<input name="'.$name.'[PARAMS][PRICES]['.$id.'][PRICE]" value="'.$price.'">'.($currency <> '' ? " (".$currency.")" : "");
	}

	protected static function getJSPrice(array $prices)
	{
		if(empty($prices))
			return "";

		foreach($prices as $id => $price)
			$prices[$id] = roundEx(floatval($price), SALE_VALUE_PRECISION);

		return "(function(value){var prices=".\CUtil::PhpToJSObject($prices)."; return prices[value]['PRICE'];})(this.value)";
	}

	public function setOperatingCurrency($currency)
	{
		if(!empty($this->params["PRICES"]) && is_array($this->params["PRICES"]))
		{
			$prices = array();

			foreach($this->params["PRICES"] as $id => $price)
				$prices[$id] = $this->convertToOperatingCurrency($price);

			$this->params["ONCHANGE"] = $this->createJSOnchange($this->id, $prices);
		}

		$this->createOptions();
		parent::setOperatingCurrency($currency);
	}

	protected function createOptions()
	{
		$this->params["OPTIONS"] = [];

		if (empty($this->params["PRICES"]) || !is_array($this->params["PRICES"]))
		{
			return;
		}

		foreach ($this->params["PRICES"] as $key => $price)
		{
			if (!is_array($price))
			{
				continue;
			}
			$priceTitle = trim((string)($price['TITLE'] ?? ''));
			if ($priceTitle === '')
			{
				continue;
			}

			$priceVal = (float)($price['PRICE'] ?? 0);
			$this->params['OPTIONS'][$key] =
				htmlspecialcharsbx($price['TITLE'])
				. ' ('
				. strip_tags(
					SaleFormatCurrency(
						$this->convertToOperatingCurrency($priceVal),
						$this->operatingCurrency,
						false
					)
				)
				. ')'
			;
		}
	}

	public function getEditControl($prefix = "", $value = false)
	{
		$this->createOptions();
		return parent::getEditControl($prefix, $value);
	}

	public function getViewControl()
	{
		$this->createOptions();
		return parent::getViewControl();
	}

	protected function createJSOnchange($id, array $prices)
	{
		return "BX.onCustomEvent('onDeliveryExtraServiceValueChange', [{'id' : '".$id."', 'value': this.value, 'price': ".$this->getJSPrice($prices)."}]);";
	}

	/**
	 * @inheritDoc
	 */
	public function getDisplayValue(): ?string
	{
		return isset($this->params['PRICES'][$this->value])
			? (string)$this->params['PRICES'][$this->value]['TITLE']
			: null;
	}
}
