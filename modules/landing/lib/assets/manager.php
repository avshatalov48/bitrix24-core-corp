<?php

namespace Bitrix\Landing\Assets;

use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main;

Loc::loadMessages(__FILE__);

/**
 * Class Manager
 * Collect assets, sort by locations, set output in different modes (webpack or default)
 *
 * @package Bitrix\Landing
 */
class Manager
{
	const MODE_STANDART = 'STANDART';
	const MODE_WEBPACK = 'WEBPACK';
	
	const REGISTERED_KEY_CODE = 'code';
	const REGISTERED_KEY_LOCATION = 'location';
	
	private static $instance = null;
	
	/**
	 * webpack or standart
	 * @var string
	 */
	protected $mode;
	/**
	 * Collection of already added assets
	 * @var array
	 */
	protected $registered = [];
	/**
	 * @var ResourceCollection
	 */
	protected $resources;
	/**
	 * @deprecated since version 19.0.0
	 */
	protected $externalLinks = [];
	/**
	 * @var Builder
	 */
	protected $builder;
	
	/**
	 * Singleton instance.
	 * @return Manager
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new static;
		}
		
		return self::$instance;
	}
	
	/**
	 * Manager constructor.
	 */
	public function __construct()
	{
		$this->mode = self::MODE_STANDART;
		$this->resources = new ResourceCollection();
	}
	
	/**
	 * Set webpack mode of builder
	 */
	public function setWebpackMode()
	{
		$this->mode = self::MODE_WEBPACK;
	}
	
	/**
	 * Set standart mode of builder
	 */
	public function setStandartMode()
	{
		$this->mode = self::MODE_STANDART;
	}
	
	/**
	 * Get current mode
	 * @return string
	 */
	public function getMode()
	{
		return $this->mode;
	}
	
	/**
	 * @param string $code - name of asset or CJSCore extension
	 * @return mixed
	 */
	public function isAssetRegistered($code)
	{
		return array_key_exists($code, $this->registered);
	}
	
	/**
	 * Return location of later added asset
	 * @param $code Code of added asset
	 * @return bool|mixed asset location or false, if asset not added
	 */
	public function getRegisteredAssetLocation($code)
	{
		if ($this->isAssetRegistered($code))
		{
			return $this->registered[$code][self::REGISTERED_KEY_LOCATION];
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * @param string $code - name of asset or CJSCore extension
	 */
	public function markAssetRegistered($code, $location)
	{
		$this->registered[$code] = [
			self::REGISTERED_KEY_CODE => $code,
			self::REGISTERED_KEY_LOCATION => $location,
		];
		\CJSCore::markExtensionLoaded($code);
	}
	
	
	/**
	 * Recursive (by 'rel' key) adding assets in WP packege
	 *
	 * @param array[string]|string $code
	 * @param string $location - where will be placed asset.
	 */
	public function addAsset($code, $location = null)
	{
//		recursive for arrays
		if (is_array($code))
		{
			foreach ($code as $item)
			{
				$this->addAsset(strval($item), $location);
			}
		}
		else
		{
			$this->addAssetRecursive($code, $location);
		}
	}
	
	/**
	 * @param $code
	 * @param string $location
	 */
	protected function addAssetRecursive($code, $location)
	{
		$location = Location::verifyLocation($location);

//		just once, but if new location more critical - readd
		if (!$this->isNeedAddAsset($code, $location))
		{
			return;
		}

//		get data from CJSCore
		if ($ext = \CJSCore::getExtInfo($code))
		{
			$asset = $ext;
		}
		else if($ext = Extension::getConfig($code))
		{
			$asset = $ext;
		}
//		if name - it path
		else if ($type = self::detectType($code))
		{
			$asset = [$type => [$code]];
		}
		else
		{
			return;
		}
		
		$this->processAsset($asset, $location);
		$this->markAssetRegistered($code, $location);
	}
	
	protected function isNeedAddAsset($code, $location)
	{
		if ($this->isAssetRegistered($code))
		{
			return $location < $this->getRegisteredAssetLocation($code);
		}
		elseif (\CJSCore::isExtensionLoaded($code))
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	
	/**
	 * Get parts of asset and add them in pack
	 *
	 * @param array $asset - array of asset data
	 * @param string $location - where will be placed asset.
	 */
	protected function processAsset(array $asset, $location)
	{
		foreach (Types::getAssetTypes() as $type)
		{
			if (!isset($asset[$type]) || empty($asset[$type]))
			{
				continue;
			}
			
			if (!is_array($asset[$type]))
			{
				$asset[$type] = [$asset[$type]];
			}
			
			switch ($type)
			{
				case Types::KEY_RELATIVE:
				{
					foreach ($asset[$type] as $rel)
					{
						$this->addAsset($rel, $location);
					}
					break;
				}
				
				case Types::TYPE_JS:
				case Types::TYPE_CSS:
				case Types::TYPE_LANG:
				{
					foreach ($asset[$type] as $path)
					{
						if (\CMain::IsExternalLink($path))
						{
							$this->resources->addString($this->createStringFromPath($path, $type));
						}
						else if (self::detectType($path))
						{
							$this->resources->add($path, $type, $location);
						}
					}
					break;
				}
				
				case Types::TYPE_FONT:
				{
					// preload fonts add immediately
					foreach ($asset[$type] as $fontFile)
					{
						$this->resources->addString($this->createStringFromPath($fontFile, $type));
					}
					break;
				}
				
				default:
					break;
			}
		}
	}
	
	protected function createStringFromPath($path, $type)
	{
		$externalLink = '';
		
		switch ($type)
		{
			case Types::TYPE_CSS:
			{
				$externalLink = "<link href=\"$path\" type=\"text/css\" rel=\"stylesheet\">";
				break;
			}
			
			case Types::TYPE_JS:
			{
				$externalLink = "<script type=\"text/javascript\" src=\"$path\"></script>";
				break;
			}
			
			case Types::TYPE_FONT:
			{
				$fontType = self::checkFontLinkType($path);
				$externalLink = '<link rel="preload" href="' . $path
					. '" as="font" crossorigin="anonymous" type="' . $fontType . '" crossorigin>';
				break;
			}
			
			default:
				break;
		}
		
		return $externalLink;
	}
	
	
	/**
	 * Detect type by path.
	 *
	 * @param string $path Relative path to asset.
	 * @return null|string
	 */
	public static function detectType($path)
	{
		$path = parse_url($path)['path'];
		$type = strtolower(substr(strrchr($path, '.'), 1));
		switch ($type)
		{
			case 'js':
				return Types::TYPE_JS;
			case 'css':
				return Types::TYPE_CSS;
			case 'php':
				return Types::TYPE_LANG;
			default:
				return null;
		}
	}
	
	protected function checkFontLinkType($path)
	{
		//woff2 must be before woff, because strpos find woff in woff2 ;)
		$available = [
			'woff2' => 'font/woff2',
			'woff' => 'font/woff',
			'ttf' => 'font/ttf',
			'eot' => 'application/vnd.ms-fontobject',
			'svg' => 'image/svg+xml',
		];
		
		$linkType = '';
		foreach ($available as $type => $value)
		{
			if (strpos($path, $type) !== false)
			{
				$linkType = $value;
				break;
			}
		}
		
		return $linkType;
	}
	
	/**
	 * Add extensions on page
	 */
	public function setOutput()
	{
		$this->createBuilder();
		$this->builder->setOutput();
	}
	
	/**
	 * Create builder object by currently set mode
	 * @throws Main\ArgumentException
	 */
	protected function createBuilder()
	{
		$this->builder = Builder::createByType($this->resources, $this->mode);
	}
}