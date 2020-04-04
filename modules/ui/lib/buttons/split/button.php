<?php

namespace Bitrix\UI\Buttons\Split;

use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\UI\Extension;
use Bitrix\UI\Buttons;
use Bitrix\UI\Buttons\ButtonAttributes;

class Button extends Buttons\Button
{
	/** @var SubButton */
	protected $mainButton;
	/** @var SubButton */
	protected $menuButton;
	protected $menuTarget = Type::MAIN;
	protected $baseClass = 'ui-btn-split';

	/**
	 * @return string
	 */
	public static function getJsClass()
	{
		return 'BX.UI.SplitButton';
	}

	/**
	 * @param $params
	 */
	protected function buildFromArray($params)
	{
		$mainOptions = $menuOptions = [];

		if (!empty($params['mainOptions']))
		{
			$mainOptions = $params['mainOptions'];
		}
		if (!empty($params['menuOptions']))
		{
			$menuOptions = $params['menuOptions'];
		}
		unset($params['tag']);

		$mainOptions['buttonType'] = Type::MAIN;
		$menuOptions['buttonType'] = Type::MENU;

		$this->mainButton = new SubButton($mainOptions);
		$this->menuButton = new SubButton($menuOptions);
		$this->mainButton->setSplitButton($this);
		$this->menuButton->setSplitButton($this);

		if (isset($params['menuTarget']) && $params['menuTarget'] === Type::MENU)
		{
			$this->menuTarget = Type::MENU;
		}

		parent::buildFromArray($params);
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function render()
	{
		Extension::load($this->listExtensions());

		$attributes = clone $this->getAttributeCollection();
		$this->appendDefaultJsonOption($attributes);

		$output = "<div {$attributes}>{$this->renderInner()}</div>";

		$js = $this->renderJavascript();
		if ($js)
		{
			$output .= "<script>BX.ready(function(){ {$js} });</script>";
		}

		return $output;
	}

	protected function renderInner()
	{
		$mainButtonOutput = $this->getMainButton()->render();
		$menuButtonOutput = $this->getMenuButton()->render();

		return "{$mainButtonOutput} {$menuButtonOutput}";
	}

	protected function getQuerySelector()
	{
		$tag = 'div';
		$uniqId = $this->getUniqId();
		$uniqIdName = "data-" . self::UNIQ_ID_DATA_ATTR;

		return "{$tag}[{$uniqIdName}=\"{$uniqId}\"]";
	}

	protected function appendDefaultJsonOption(ButtonAttributes $attributes)
	{
		$attributes = parent::appendDefaultJsonOption($attributes);

		$mainButton = $this->getMainButton();
		$menuButton = $this->getMenuButton();

		$mainButtonAttributes = clone $mainButton->getAttributeCollection();
		$mainButton->appendDefaultJsonOption($mainButtonAttributes);

		$menuButtonAttributes = clone $menuButton->getAttributeCollection();
		$menuButton->appendDefaultJsonOption($menuButtonAttributes);

		$mainButtonQuerySelector = $mainButton->getQuerySelector();
		$menuButtonQuerySelector = $menuButton->getQuerySelector();

		$attributes
			->addJsonOption('mainButton', $mainButtonAttributes->getJsonOptions())
			->addJsonOption('menuButton', $menuButtonAttributes->getJsonOptions())
			->addJsonOption('querySelectors', [
				'mainButton' => $mainButtonQuerySelector,
				'menuButton' => $menuButtonQuerySelector,
			])
		;

	}

	/**
	 * @return SubButton
	 */
	public function getMainButton()
	{
		return $this->mainButton;
	}

	/**
	 * @return SubButton
	 */
	public function getMenuButton()
	{
		return $this->menuButton;
	}

	/**
	 * @return string
	 */
	public function getMenuTarget()
	{
		return $this->menuTarget;
	}

	/**
	 * @param string $text
	 *
	 * @return $this
	 */
	public function setText($text)
	{
		$this->getMainButton()->setText($text);

		return $this;
	}

	/**
	 * @param string $state
	 *
	 * @return $this
	 */
	public function setState($state)
	{
		return $this->setProperty('state', $state, State::class);
	}

	/**
	 * @param bool $flag
	 *
	 * @return Buttons\Button|void
	 * @throws InvalidOperationException
	 */
	public function setRound($flag = true)
	{
		throw new InvalidOperationException("BX.UI.SplitButton can't be round.");
	}

	/**
	 * @param bool $flag
	 * @return static
	 */
	public function setDropdown($flag = true)
	{
		return $this;
	}

	/**
	 * @param bool $flag
	 *
	 * @return $this
	 */
	public function setDisabled($flag = true)
	{
		if ($flag)
		{
			$this->setState(State::DISABLED);
		}

		$this->getMainButton()->setDisabled($flag);
		$this->getMenuButton()->setDisabled($flag);

		return $this;
	}
}