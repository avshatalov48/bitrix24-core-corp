<?php

namespace Bitrix\MobileApp\Janative\Entity;

use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\FileEntry;
use Bitrix\Main\IO\FileNotFoundException;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization;
use Bitrix\MobileApp\Janative\Manager;
use Bitrix\MobileApp\Janative\Utils;

abstract class Base
{
	protected static $modificationDates = [];
	protected static $dependencies = [];
	protected static $expandedDependencies = [];
	protected $path;
	protected $namespace;
	protected $baseFileName;
	public $name;
	private $config;


	private function getConfig(): ?array {
		if ($this->config == null) {
			$this->config = [];
			$file = new File("$this->path/deps.php");
			$result = [];
			if ($file->isExists())
			{
				$this->config = include($file->getPath());
				if (!is_array($this->config)) {
					$this->config = [];
				}
			}
		}

		return $this->config;
	}

	public function getModificationTime()
	{
		if(static::$modificationDates[$this->name])
		{
			return static::$modificationDates[$this->name];
		}

		$file = new File("{$this->path}/{$this->baseFileName}.js");
		$dates = [$file->getModificationTime()];
		$langDirectory = new Directory("{$this->path}/lang/");
		if ($langDirectory->isExists())
		{
			$langs = $langDirectory->getChildren();
			foreach ($langs as $lang)
			{
				if ($lang->isDirectory())
				{
					$langFile = new File($lang->getPath()."/{$this->baseFileName}.php");
					if($langFile->isExists())
						$dates[] = $langFile->getModificationTime();
				}
			}
		}

		$value = max($dates);
		$this->onBeforeModificationDateSave($value);
		static::$modificationDates[$this->name] = $value;

		return $value;
	}

	public function getDependencies()
	{
		if (!array_key_exists($this->name, static::$dependencies))
		{
			static::$dependencies[$this->name] = $this->resolveDependencies();
		}

		return static::$dependencies[$this->name];
	}

	public function getPath()
	{
		$relativePath = str_replace(Application::getDocumentRoot(), "", "{$this->path}/");
		return Path::normalize($relativePath);
	}

	public function getRelativePathToFile()
	{
		$relativePath = $this->getPath() . "/{$this->baseFileName}.js";
		return Path::normalize($relativePath);
	}

	public function getLangMessages()
	{
		$langPhrases = Localization\Loc::loadLanguageFile("{$this->path}/{$this->baseFileName}.php");
		return $langPhrases ?: [];
	}

	public function getDependencyList()
	{
		$config = $this->getConfig();
		$list = [];
		if (is_array($config))
		{
			if (array_keys($config) !== range(0, count($config) - 1)) {
				if(array_key_exists('extensions', $config)) {
					$list = $config['extensions'];
				}
			}
			else
			{
				$list = $config;
			}
		}

		return array_reduce(
			$list,
			function ($result, $ext) {
				return array_merge($result,  Base::expandDependency($ext));
			}, []);
	}

	protected function getBundleFiles(): array {
		$config = $this->getConfig();
		$list = [];
		if (array_key_exists("bundle", $config)) {
			$list = array_map(function ($file) {
				$path = Path::normalize($this->path."/$file");
				if (Path::getExtension($path) !== "js") {
					$path .= ".js";
				}
				return $path;
			}, $config["bundle"]);
		}

		return $list;
	}

	public function getComponentDependencies(): ?array
	{
		$config = $this->getConfig();
		$result = [];
		if (is_array($config))
		{
			if (array_keys($config) !== range(0, count($config) - 1)) {
				if (array_key_exists('components', $config)) {
					if (is_array($config['components'])) {
						return $config['components'];
					}
				}
			}
			else
			{
				$result = null;
			}

		}

		return $result;
	}

	/**
	 * @param $ext
	 * @return array
	 * @throws FileNotFoundException
	 */
	private static function expandDependency($ext): array
	{

		$result = [];

		if (!is_string($ext))
		{
			return [];
		}

		if (array_key_exists($ext, self::$expandedDependencies))
		{
			return self::$expandedDependencies[$ext];
		}

		$findChildren = false;
		$relativeExtDir = $ext;


		if(mb_strpos($ext, "*") == (mb_strlen($ext) - 1))
		{
			$relativeExtDir = str_replace(["/*", "*"], "", $ext);
			$findChildren = true;
		}

		$absolutePath = Manager::getExtensionPath($relativeExtDir);
		if($findChildren && $absolutePath != null)
		{
			$dir = new Directory($absolutePath);
			$items = $dir->getChildren();
			for ($i = 0; $i < count($items); $i++)
			{
				/** @var Directory $entry **/
				$entry = $items[$i];
				if ($entry->isDirectory())
				{
					$toAdd = $entry->getChildren();
					$extensionFile = new File($entry->getPath(). '/extension.js');
					if($extensionFile->isExists())
					{
						$result[] = $extensionFile->getPath();
					}

					$items = array_merge($items, $toAdd);
				}
			}

			$result = array_map(function($path) use ($absolutePath, $relativeExtDir) {
				return str_replace([$absolutePath, "/extension.js"],[$relativeExtDir, ""], $path);
			}, $result);
		}

		$rootExtension = new File($absolutePath . '/extension.js');
		if($rootExtension->isExists())
		{
			$result[] = $relativeExtDir;
		}

		self::$expandedDependencies[$ext] = $result;
		return $result;
	}

	public function getLangDefinitionExpression()
	{
		$langPhrases = $this->getLangMessages();
		if (count($langPhrases) > 0)
		{
			$jsonLangMessages = Utils::jsonEncode($langPhrases, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
			return <<<JS
BX.message($jsonLangMessages);
JS;
		}

		return "";
	}

	abstract protected function onBeforeModificationDateSave(&$value);
	abstract protected function resolveDependencies();


}