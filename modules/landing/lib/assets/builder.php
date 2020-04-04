<?php

namespace Bitrix\Landing\Assets;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main;


abstract class Builder
{
	const TYPE_STANDART = 'STANDART';
	const TYPE_WEBPACK = 'WEBPACK';
	
	const MODULE_ID = 'landing';
	const FOLDER_NAME = 'assets';
	const PACKAGE_NAME = 'landing_assets';
	
	/**
	 * @var ResourceCollection
	 */
	protected $resources;
	/**
	 * @var array
	 */
	protected $normalizedResources = [];
	
	public function __construct($resources)
	{
		if ($resources instanceof ResourceCollection)
		{
			$this->resources = $resources;
		}
		else
		{
			throw new ArgumentTypeException($resources, 'ResourceCollection');
		}
	}
	
	/**
	 * @param ResourceCollection
	 * @param string $type
	 * @return StandartBuilder|WebpackBuilder
	 * @throws ArgumentException
	 */
	public static function createByType($resources, $type)
	{
		switch ($type)
		{
			case self::TYPE_STANDART:
				return new StandartBuilder($resources);
			case self::TYPE_WEBPACK:
				return new WebpackBuilder($resources);
			default:
				throw new ArgumentException("Unknown landing asset builder type `$type`.");
		}
	}
	
	abstract public function setOutput();
	
	abstract protected function normalizeResources();
	
	protected function initResourcesAsJsExtension($resources, $extName = null)
	{
		if(!$extName)
		{
			$extName = self::PACKAGE_NAME;
		}
		$extFullName = $extName . '_' . md5(serialize($resources));
		
		$resources = array_merge($resources, [
			'bundle_js' => $extFullName,
			'bundle_css' => $extFullName,
		]);
		\CJSCore::registerExt($extName, $resources);
		\CJSCore::Init($extName);
	}
	
	protected function setStrings()
	{
		foreach($this->resources->getStrings() as $string)
		{
			// Main\Page\Asset::getInstance()->addString($string);
			Main\Page\Asset::getInstance()->addString($string, false, Main\Page\AssetLocation::AFTER_JS);
		}
	}
}