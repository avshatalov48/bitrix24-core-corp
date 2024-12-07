<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\FileDeleteException;
use Bitrix\Main\Localization;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

/**
 * Class MobileJSComponent
 */
class MobileWebComponent extends \CBitrixComponent
{
	const LOCK_TAG_PREFIX = "WEB_COMPONENT_ARCHIVE_IS_BEING_GENERATED_";
	const LOCK_TTL = 5;
	const FILE_ARCHIVE_MODULE_NAME = "mobile/webcomponent";
	const VERSION = "1";

	public $componentPath;
	public $componentName;

	protected $availableComponents;
	protected $componentsPath;
	protected $version;
	protected $debugMode;
	protected $devMode;
	protected $emulateError;
	protected $errors;

	private static $componentSubdirectory = "/webcomponents/";
	private static $archiveTempDirectory = "/mobile/webcomponents/";
	private $systemTemporaryDirectory;


	public function __construct($component = null)
	{
		parent::__construct($component);
		$componentsPath = Application::getDocumentRoot() . $this->getPath() . self::$componentSubdirectory;
		$this->availableComponents = [];
		$this->debugMode = array_key_exists("debug", $_REQUEST);
		$this->devMode = array_key_exists("dev", $_REQUEST);
		$this->emulateError = array_key_exists("error", $_REQUEST);
		$this->errors = [];
		$this->systemTemporaryDirectory = sys_get_temp_dir();
		$componentDir = new Directory($componentsPath);
		$jsComponentsDirs = $componentDir->getChildren();
		foreach ($jsComponentsDirs as $jsComponentDir)
		{
			if ($jsComponentDir->isDirectory())
			{
				$this->availableComponents[] = $jsComponentDir->getName();
			}
		}
	}

	public function onPrepareComponentParams($arParams)
	{
		if ($arParams["componentName"])
		{
			$this->componentPath = $this->getPath() . self::$componentSubdirectory . $arParams["componentName"];
			$this->componentName = $arParams["componentName"];
			$this->version = $this->getComponentVersion($this->componentName);
		}

		return $arParams;
	}

	public function executeComponent()
	{
		if (!in_array($this->componentName, $this->availableComponents))
		{
			header("BX-Component-Not-Found: true");
		}
		else
		{
			$this->waitForUnlocking();

			$componentPath = Application::getDocumentRoot() . $this->componentPath;
			$componentFolder = new Directory($componentPath);

			if ($componentFolder->isExists())
			{

				$fileData = null;
				$archiveData = $this->getArchiveData();
				$shouldGenerate = (
					$archiveData["version"] != $this->version
					|| intval($archiveData["fileId"] <= 0)
					|| !is_array($fileData = $this->getFileData($archiveData["fileId"]))
				);

				if ($shouldGenerate || $this->devMode)
				{
					//generate a new archive
					$archiveFileId = $this->generateArchive();
					$fileData = $this->getFileData($archiveFileId);
					$this->saveArchiveData($archiveFileId, $this->version);
					if($archiveData["fileId"])
					{
						\CFile::delete($archiveData["fileId"]);
					}
				}

				if ($this->debugMode)
				{
					if($this->errors)
						print_r($this->errors);
				}
				else
				{
					if ($fileData)
					{
						$this->downloadArchive($fileData);
					}
					else
					{
						$GLOBALS['APPLICATION']->RestartBuffer();
						header('Pragma: public');
						header("BX-Web-Component-error: file not found");
						header('Cache-control: private');
						header("Expires: 0");
					}

				}
			}
		}
	}

	public function generateArchive()
	{
		require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/tar_gz.php");

		$this->lockConcurrentExecution();
		$fileId = null;
		$rndGenerator = new \Bitrix\Main\Type\RandomSequence($this->componentName.LANGUAGE_ID);
		$randomTmpDir = new Directory($this->systemTemporaryDirectory . self::$archiveTempDirectory . $rndGenerator->randString(20));
		$randomTmpDir->create();
		$randomTmpPath = $randomTmpDir->getPath() . "/".md5($this->componentName)."/";
		$archivePath =  $randomTmpPath.$this->version.".tar.gz";
		$tmpBundlePath = $randomTmpPath. "/bundle/";
		$config = include(Application::getDocumentRoot() . $this->componentPath . "/config.php");

		//Copy base bundle to temporary directory
		$copied = CopyDirFiles(Application::getDocumentRoot() . $this->componentPath . "/bundle/", $tmpBundlePath, true, true, false);
		if ($copied)
		{
			$map = [
				"js" => [],
				"css" => [],
				"images" => [],
				"langs" => [],
				"jscoreLang" => [],
				"messages" => [],
				"exclude" => [
					'/bitrix/js/main/polyfill/core/dist/polyfill.bundle.js',
					// TODO remove following scripts after release main 19.0.0
					'/bitrix/js/main/polyfill/find/js/find.js',
					'/bitrix/js/main/polyfill/includes/js/includes.js',
					'/bitrix/js/main/polyfill/fill/main.polyfill.fill.js',
					'/bitrix/js/main/polyfill/customevent/main.polyfill.customevent.js',
					'/bitrix/js/main/polyfill/complex/base-polyfill.js',
				]
			];

			// Get resource mapping from CoreJS extensions
			$jsCore = ['ajax', 'mobile.pull.client'];
			if (isset($config["rel"]) && !empty($config["rel"]))
			{
				$jsCore = array_merge($jsCore, $config["rel"]);
			}

			$resources = \Bitrix\Main\UI\Extension::getResourceList($jsCore, [
				'skip_extensions' => ['pull.client', 'rest.client']
			]);

			if (
				in_array('/bitrix/js/main/polyfill/complex/base-polyfill.js', $resources['js'])
				|| in_array('/bitrix/js/main/polyfill/core/dist/polyfill.bundle.js', $resources['js'])
			)
			{
				$map["js"] = [
					'/bitrix/js/main/polyfill/core/lib/',
				];
			}

			$map["js"] = array_merge($map["js"], $resources["js"]);
			$map["css"] = $resources["css"];
			$map["langs"] =$resources["lang"];

			// Get resource mapping from JN extensions
			if (isset($config["deps"]) && !empty($config["deps"]) && \Bitrix\Main\Loader::includeModule('mobileapp'))
			{
				$resources = \Bitrix\MobileApp\Janative\Manager::getExtensionResourceList($config["deps"]);
				$map["js"] = array_merge($map["js"], $resources["js"]);
				$map["messages"] = $resources["messages"];
			}

			// Get resource mapping from webcomponent config
			foreach (["js", "css", "images", "langs", "exclude"] as $key)
			{
				if (isset($config[$key]))
				{
					$map[$key] = array_merge($map[$key], $config[$key]);
				}
			}

			//Copy additional resources to bundle in temporary directory
			$resourcesForHeaders = $this->copyResourceMapFiles($tmpBundlePath, $map);
			$this->injectResourcesIntoIndexFile($tmpBundlePath, $resourcesForHeaders);

			//Create tar.gz archive with bundle in temporary directory
			$arc = new \CArchiver($archivePath, false);
			$arc->add([$tmpBundlePath], "", $tmpBundlePath);

			//Try to extract files in temporary directory to check if the archive is corrupted
			//FIXME to find better solution in future
			$arc->extractFiles($randomTmpPath . "/extractTest/");

			//Remove bundle in temporary directory
			$dirEntry = new Directory($tmpBundlePath);
			try
			{
				$dirEntry->delete();
			}
			catch (FileDeleteException $exception)
			{
				//it's ok, let it be
			}

			if(empty($errors = $arc->getErrors()) && !$this->emulateError)
			{
				//6. Save the archive
				$fileArray = \CFile::MakeFileArray($archivePath);
				$fileId = CFile::SaveFile([
					"name" => $fileArray["name"],
					"size" => $fileArray["size"],
					"tmp_name" => $fileArray["tmp_name"],
					"type" => $fileArray["type"],
					"MODULE_ID" => self::FILE_ARCHIVE_MODULE_NAME,
				], "mobile_web_component"
				);
			}
			else
			{
				array_walk($errors, function($error)
				{
					$this->addError($error[0]);
				});
			}

			//7. Delete tmp dir
			try
			{
				$randomTmpDir->delete();
			}
			catch (FileDeleteException $exception)
			{
				//it's ok, let it be
			}

		}
		else
		{
			$this->addError("Error while coping "
				. $this->componentPath
				. "/bundle/" . " to "
				. $tmpBundlePath);
		}

		$this->unlockConcurrentExecution();

		return $fileId;
	}

	private function addError($text)
	{
		$this->errors[] = $text;
	}

	private function injectResourcesIntoIndexFile($path, $map)
	{
		$indexFileEntry = new File($path . "/index.html");
		$content = $indexFileEntry->getContents();
		$injectionRules = [
			"css" => "<link type=\"text/css\" href=\"bundle://#PATH#\" rel=\"stylesheet\" />",
			"js" => "<script src=\"bundle://#PATH#\"></script>",
			"langs" => "<script src=\"bundle://#PATH#\"></script>"
		];
		$injectionContent = [];
		$injectionContent["timestamp"] = time();


		foreach ($injectionRules as $type => $rule)
		{
			$injectionContent[$type] = "";
			if (!$map[$type])
			{
				continue;
			}

			$count = count($map[$type]);

			$index = 0;
			foreach ($map[$type] as $localPath)
			{
				$index++;

				if (mb_substr($localPath, 0, 1) === '/')
				{
					$localPath = mb_substr($localPath, 1);
				}
				if (mb_substr($localPath, -1) === '/')
				{
					foreach ($this->getResourceMapForFolder($path, $localPath) as $folderFilePath)
					{
						$injectionContent[$type] .= str_replace("#PATH#", $folderFilePath.'?'.$injectionContent["timestamp"], $rule).($count == $index? "": "\n\t");
					}
				}
				else
				{
					$injectionContent[$type] .= str_replace("#PATH#", $localPath.'?'.$injectionContent["timestamp"], $rule).($count == $index? "": "\n\t");
				}
			}
		}

		// resource injection
		$contentWithInjection = str_replace(
			array_map(function ($rule)
			{
				return "#".mb_strtoupper($rule) . "#";
			},
			array_keys($injectionContent)),
			array_values($injectionContent),
			$content
		);

		// messages injection
		$componentMessages = Localization\Loc::loadLanguageFile(Application::getDocumentRoot() . $this->componentPath . "/component.php");
		if (!empty($componentMessages))
		{
			$contentWithInjection = str_replace
			(
				array_map(function ($message)
				{
					return '#MESS_'.$message.'#';
				},
				array_keys($componentMessages)),
				array_values($componentMessages),
				$contentWithInjection
			);
		}

		$indexFileEntry->putContents($contentWithInjection);
	}

	private function getResourceMapForFolder($path, $localPath)
	{
		$map = [];

		$directory = new Directory($path . "/" .$localPath);
		foreach ($directory->getChildren() as $file)
		{
			if ($file->isDirectory())
			{
				$map = array_merge($this->getResourceMapForFolder($path, $localPath . $file->getName() . '/'));
			}
			else
			{
				$map[] = $localPath . $file->getName();
			}
		}

		return $map;
	}

	private function copyResourceMapFiles($path, $map)
	{
		$types = ["js", "css", "images"];
		$headerFileMap = [];
		foreach ($types as $type)
		{
			if (!$map[$type])
			{
				continue;
			}

			$headerFileMap[$type] = [];
			foreach ($map[$type] as $localPath => $originalPath)
			{
				$replaceMap = [];
				if (empty($originalPath))
				{
					continue;
				}
				else if (is_array($originalPath))
				{
					if ($type == "css" && !empty($originalPath["path"]))
					{
						$replaceMap = $originalPath["replace"];
						$originalPath = $originalPath["path"];
					}
					else
					{
						continue;
					}
				}
				else if (array_search($originalPath, $map['exclude']) !== false)
				{
					continue;
				}

				if (is_integer($localPath))
				{
					$localPath = $originalPath;
				}

				if (CopyDirFiles(Application::getDocumentRoot() . $originalPath, $path . $localPath, true, true))
				{
					if (!empty($replaceMap))
					{
						$file = new File($path.$localPath);
						$content = $file->getContents();
						foreach ($replaceMap as $find => $replace)
						{
							$content = str_replace($find, $replace, $content);
						}
						$file->putContents($content);
					}
					else if ($type === "css")
					{
						$file = new File($path.$localPath);
						$content = $file->getContents();

						$content = str_replace(
							['url(/', "url('/", 'url("/'],
							['url(bundle://', "url('bundle://", 'url("bundle://'],
						$content);

						$file->putContents($content);
					}

					$headerFileMap[$type][] = $localPath;
				}
				else
				{
					$this->addError("Error while coping " .  $originalPath . " to " . $path . $localPath);
				}

				if (defined('MOBILE_WEBCOMPONENT_INCLUDE_MAP_FILES') && $type === 'js')
				{
					$file = new File(Application::getDocumentRoot() . $originalPath . '.map');
					if ($file->isExists() && !$file->isDirectory())
					{
						CopyDirFiles(Application::getDocumentRoot() . $originalPath . '.map', $path . $localPath . '.map', true);
					}
				}
			}
		}

		$MESS = Localization\Loc::loadLanguageFile(Application::getDocumentRoot() . $this->componentPath . "/component.php");
		foreach ($map["langs"] as $file)
		{
			$messages = \Bitrix\Main\Localization\Loc::loadLanguageFile(Application::getDocumentRoot().$file, LANGUAGE_ID);
			if(is_array($messages))
			{
				$MESS = array_merge($MESS, $messages);
			}
		}

		$MESS = array_merge($MESS, $map["messages"]);

		if (count($MESS) > 0)
		{
			$headerFileMap["langs"] = ["langs/lang.js"];
			$jsonLangMessages = $this->jsonEncode($MESS);
			$localizationPhrases = <<<JS
BX.message($jsonLangMessages);

JS;
			$localizationFile = new File($path . "/langs/lang.js");
			if (!$localizationFile->putContents($localizationPhrases))
			{
				$this->addError("Can't to create localization file " . $localizationFile->getPath());
			}
		}

		return $headerFileMap;
	}

	public function jsonEncode($string)
	{
		$options = JSON_HEX_TAG | JSON_HEX_AMP | JSON_PRETTY_PRINT | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;

		return Json::encode($string, $options);
	}

	private function isExecutionLocked()
	{
		$app = \Bitrix\Main\Application::getInstance();
		$managedCache = $app->getManagedCache();

		return ($managedCache->getImmediate(self::LOCK_TTL, $this->getLockTag()) === true);
	}

	public function getComponentVersion($componentName)
	{
		$componentFolder = new Directory($this->getPath() . self::$componentSubdirectory . $componentName);
		$versionFile = new File(Application::getDocumentRoot() . $componentFolder->getPath() . "/version.php");
		if ($versionFile->isExists())
		{
			$versionDesc = include($versionFile->getPath());
			return $versionDesc["version"].".".self::VERSION;
		}

		return "0.".self::VERSION;
	}

	/**
	 * Locks concurrent execution of component to avoid parallel creation of archive
	 * @throws \Bitrix\Main\SystemException
	 */
	private function lockConcurrentExecution()
	{
		$tag = $this->getLockTag();
		$app = \Bitrix\Main\Application::getInstance();
		$managedCache = $app->getManagedCache();
		$managedCache->clean($tag);
		$managedCache->read(self::LOCK_TTL, $tag);
		$managedCache->setImmediate($tag, true);
	}

	/**
	 * Unlock concurrent execution of component
	 * @throws \Bitrix\Main\SystemException
	 */
	private function unlockConcurrentExecution()
	{
		$app = \Bitrix\Main\Application::getInstance();
		$managedCache = $app->getManagedCache();
		$managedCache->clean($this->getLockTag());
	}

	/**
	 * Locks the script until the archive that is being built in the parallel hit will not be created.
	 */
	private function waitForUnlocking()
	{
		$times = self::LOCK_TTL;
		$i = 0;
		while ($this->isExecutionLocked() && $i < $times)
		{
			sleep(1);
			$i++;
		}
	}

	private function getLockTag()
	{
		return self::LOCK_TAG_PREFIX . $this->componentName;
	}
	private function getOptionName()
	{
		return $this->componentName.LANGUAGE_ID;
	}

	private function saveArchiveData($fileId, $version)
	{
		try
		{
			\Bitrix\Main\Config\Option::set(
				"mobile",
				$this->getOptionName(),
				$fileId . "/" . $version,
				SITE_ID
			);
		} catch (\Bitrix\Main\ArgumentOutOfRangeException $e)
		{
			//do nothing
		}
	}

	private function getArchiveData()
	{
		$defaultValue = ["fileId" => "", "version" => 0];
		try
		{
			$stringData = \Bitrix\Main\Config\Option::get("mobile", $this->getOptionName(), "", SITE_ID);
			$data = explode("/", $stringData);

			return (count($data) == 2)
				? array_combine(["fileId", "version"], $data)
				: $defaultValue;
		}
		catch (\Bitrix\Main\ArgumentNullException $e)
		{
			return $defaultValue;
		}
		catch (\Bitrix\Main\ArgumentOutOfRangeException $e)
		{
			return $defaultValue;
		}
	}

	private function getFileData($fileId)
	{
		$files = \CFile::GetList([],
			[
				"ID" => $fileId,
				"MODULE_ID" => self::FILE_ARCHIVE_MODULE_NAME
			]
		);

		if($file = $files->fetch())
			return $file;

		return null;
	}

	private function downloadArchive($fileData)
	{
		header('Pragma: public');
		header("BX-Web-Component: true");
		header("BX-Component-Name:" . $this->componentName . "");
		header("BX-Component-Version:" . $this->version . "");
		header('Cache-control: private');
		header("Expires: 0");

		\CFile::ViewByUser($fileData, [
			"force_download" => true,
			"attachment_name" => $fileData["name"],
			"fast_download" => false
		]);
	}

}
