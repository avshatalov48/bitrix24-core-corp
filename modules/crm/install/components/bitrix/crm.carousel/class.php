<?php
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if(!(defined('B_PROLOG_INCLUDED') && B_PROLOG_INCLUDED === true)) die();

Loc::loadMessages(__FILE__);

class CCrmCarouselComponent extends CBitrixComponent
{
	//region Fields
	/** @var string  */
	protected $guid = '';
	/** @var bool */
	protected $autorewind = true;
	/** @var string  */
	protected $defaultButtonText = '';
	/** @var bool */
	protected $enableCloseButton = false;
	/** @var string  */
	protected $closeTitle = '';
	/** @var string  */
	protected $closeConfirm = '';
	/** @var array|null  */
	protected $items = null;
	//endregion
	//region Methods
	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->items = array();
	}
	public function executeComponent()
	{
		$this->initialize();
		$this->includeComponentTemplate();
	}
	protected function initialize()
	{
		if(isset($this->arParams['GUID']))
		{
			$this->guid = $this->arParams['GUID'];
		}
		if($this->guid === '')
		{
			$this->guid = 'carousel';
		}
		if(isset($this->arParams['AUTO_REWIND']))
		{
			$this->autorewind = ($this->arParams['AUTO_REWIND'] == 'Y' || $this->arParams['AUTO_REWIND'] == true);
		}
		if(isset($this->arParams['DEFAULT_BUTTON_TEXT']))
		{
			$this->defaultButtonText = $this->arParams['DEFAULT_BUTTON_TEXT'];
		}
		if(isset($this->arParams['ENABLE_CLOSE_BUTTON']))
		{
			$this->enableCloseButton = ($this->arParams['ENABLE_CLOSE_BUTTON'] == 'Y' || $this->arParams['ENABLE_CLOSE_BUTTON'] == true);
		}
		if(isset($this->arParams['CLOSE_TITLE']))
		{
			$this->closeTitle = $this->arParams['CLOSE_TITLE'];
		}
		if(isset($this->arParams['CLOSE_CONFIRM']))
		{
			$this->closeConfirm = $this->arParams['CLOSE_CONFIRM'];
		}
		if(isset($this->arParams['ITEMS']) && is_array($this->arParams['ITEMS']))
		{
			foreach($this->arParams['ITEMS'] as $itemParams)
			{
				if(!is_array($itemParams))
				{
					continue;
				}

				$item = new CCrmCarouselItem();
				$item->internalize($itemParams);
				$this->items[] = $item;
			}
		}
	}
	public function getGuid()
	{
		return $this->guid;
	}
	public function isAutoRewindEnabled()
	{
		return $this->autorewind;
	}
	public function getDefaultButtonText()
	{
		return $this->defaultButtonText;
	}
	public function isCloseButtonEnabled()
	{
		return $this->enableCloseButton;
	}
	public function getCloseTitle()
	{
		return $this->closeTitle;
	}
	public function getCloseConfirm()
	{
		return $this->closeConfirm;
	}
	public function getItems()
	{
		return $this->items;
	}
	//endregion
}

class CCrmCarouselItem
{
	//region Fields
	/** @var string */
	protected $caption = '';
	/** @var string */
	protected $captionClassName = '';
	/** @var string */
	protected $legend = '';
	/** @var string */
	protected $url = '';
	/** @var string */
	protected $buttonText = '';
	//endregion
	//region Methods
	/**
	 * @return string
	 */
	public function getCaption()
	{
		return $this->caption;
	}
	/**
	 * @param string $text Text.
	 * @return void
	 */
	public function setCaption($text)
	{
		if(!is_string($text))
		{
			$text = (string)$text;
		}
		$this->caption = $text;
	}
	/**
	 * @return string
	 */
	public function getCaptionClassName()
	{
		return $this->captionClassName;
	}
	/**
	 * @param string $caption Caption text.
	 * @return void
	 */
	public function setCaptionClassName($caption)
	{
		if(!is_string($caption))
		{
			$caption = (string)$caption;
		}
		$this->captionClassName = $caption;
	}
	/**
	 * @return string
	 */
	public function getLegend()
	{
		return $this->legend;
	}
	/**
	 * @param string $legend Legend text.
	 * @return void
	 */
	public function setLegend($legend)
	{
		if(!is_string($legend))
		{
			$legend = (string)$legend;
		}
		$this->legend = $legend;
	}
	/**
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}
	/**
	 * @param string $url URL.
	 * @return void
	 */
	public function setUrl($url)
	{
		if(!is_string($url))
		{
			$url = (string)$url;
		}
		$this->url = $url;
	}
	/**
	 * @return string
	 */
	public function getButtonText()
	{
		return $this->buttonText;
	}
	/**
	 * @param string $buttonText Button text.
	 * @return void
	 */
	public function setButtonText($buttonText)
	{
		if(!is_string($buttonText))
		{
			$buttonText = (string)$buttonText;
		}
		$this->buttonText = $buttonText;
	}
	/**
	 * @param array $params Parameter array
	 * @retirn void
	 */
	public function internalize(array $params)
	{
		$this->setCaption(isset($params['CAPTION']) ? $params['CAPTION'] : '');
		$this->setCaptionClassName(isset($params['CAPTION_CLASS_NAME']) ? $params['CAPTION_CLASS_NAME'] : '');
		$this->setLegend(isset($params['LEGEND']) ? $params['LEGEND'] : '');
		$this->setUrl(isset($params['URL']) ? $params['URL'] : '');
		$this->setButtonText(isset($params['BUTTON_TEXT']) ? $params['BUTTON_TEXT'] : '');
	}
	/**
	 * @return array
	 */
	public function externalize()
	{
		return array(
			'CAPTION' => $this->getCaption(),
			'CAPTION_CLASS_NAME' => $this->getCaptionClassName(),
			'LEGEND' => $this->getLegend(),
			'URL' => $this->getUrl(),
			'BUTTON_TEXT' => $this->getButtonText()
		);
	}
	//endregion
}