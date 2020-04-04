<?php
namespace Bitrix\Timeman\Component;

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class BaseComponent extends \CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->arResult['isSlider'] = $this->getRequest()->get('IFRAME') === 'Y';
	}

	/**
	 * @return \Bitrix\Main\HttpRequest
	 */
	protected function getRequest()
	{
		return Application::getInstance()->getContext()->getRequest();
	}

	protected function getApplication()
	{
		global $APPLICATION;
		return $APPLICATION;
	}

	/**
	 * @return array
	 */
	public function configureActions()
	{
		return [];
	}

	protected function initFromParams($paramName, $defaultValue = null, $type = null)
	{
		if (!array_key_exists($paramName, $this->arParams) ||
			(array_key_exists($paramName, $this->arParams) && is_null($this->arParams[$paramName])))
		{
			$this->arResult[$paramName] = $defaultValue;
			return;
		}
		switch ($type)
		{
			case 'array':
				$this->arResult[$paramName] = (array)($this->arParams[$paramName]);
				break;
			case 'text':
				$this->arResult[$paramName] = htmlspecialcharsbx($this->arParams[$paramName]);
				break;
			case 'int':
				$this->arResult[$paramName] = intval($this->arParams[$paramName]);
				break;
			case 'bool':
				$this->arResult[$paramName] = (bool)($this->arParams[$paramName]);
				break;
			default:
				$this->arResult[$paramName] = $this->arParams[$paramName];
				break;
		}
	}

	protected function getFromParamsOrRequest($arParams, $name, $type)
	{
		$value = array_key_exists($name, $arParams) ? $arParams[$name] : $this->getRequest()->get($name);
		if ($type === 'int')
		{
			if (!is_null($value) && $value >= 0)
			{
				$value = (int)$value;
			}
			else
			{
				$value = null;
			}
		}
		return $value;
	}
}