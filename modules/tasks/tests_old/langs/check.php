<?
CModule::IncludeModule('tasks');

use Bitrix\Main\IO\Path;
include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/tasks/dev/util/testcase.php");
$beforeClasses = get_declared_classes();
$beforeClassesCount = count($beforeClasses);

class checkTests extends \Bitrix\Tasks\Dev\Util\TestCase
{
	private static $list = array();

	public function testRu()
	{
		$files = self::findAllGetMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/tasks/install/components/bitrix/tasks.task.list');
		foreach($files as $filePath)
		{
			self::parse($filePath, 'ru');
		}

		dd(self::$list);
	}

	private static function parse($filePath, $lang)
	{
		$file = file_get_contents($filePath);
		preg_match_all('#getmessage\([\'"](.*?)[\'"]\)#i', $file, $m);
		if(empty($m[1]))
		{
			return false;
		}

		$langFile = self::includeLangFiles($filePath, $lang);

		if(!file_exists($langFile))
		{
			self::$list[] = array(
				'file'=>$filePath,
				'langFile' => $langFile,
				'error'=>'Не найден файл перевода'
			);
			return false;
		}

		$MESS = array();
		include($langFile);

		$getMessages = array_flip($m[1]);

		$diff1 = array_diff_assoc($getMessages, $MESS);
		$diff2 = array_diff_assoc($MESS, $getMessages);

		if(!empty($diff1))
		{
			self::$list[] = array(
				'file' => $filePath,
				'langFile' => $langFile,
				'error' => 'Не хватает перевода',
				'mess'=>$diff1
			);
		}
		if(!empty($diff2))
		{
			self::$list[] = array(
				'file' => $filePath,
				'langFile' => $langFile,
				'error' => 'Лишние переводы',
				'mess'=>$diff2
			);
		}
	}

	private static function findAllGetMessages($rootpath)
	{
		static $files = array();

		if(!$files || empty($files))
		{

			$fileinfos = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($rootpath)
			);
			foreach ($fileinfos as $pathname => $fileinfo)
			{
				if (!$fileinfo->isFile() && !mb_strpos($pathname, '/lang/'))
				{
					continue;
				}

				$ext = pathinfo($pathname, PATHINFO_EXTENSION);

				if (!in_array($ext, array('js', 'php')))
				{
					continue;
				}

				$files[] = $pathname;
			}
		}

		return $files;
	}

	// copy past from Bitrix\Main\Localization;
	private static function includeLangFiles($file, $language)
	{
		static $langDirCache = array();

		$path = Path::getDirectory($file);

		if(isset($langDirCache[$path]))
		{
			$langDir = $langDirCache[$path];
			$fileName = mb_substr($file, (mb_strlen($langDir) - 5));
		}
		else
		{
			//let's find language folder
			$langDir = $fileName = "";
			$filePath = $file;
			while(($slashPos = mb_strrpos($filePath, "/")) !== false)
			{
				$filePath = mb_substr($filePath, 0, $slashPos);
				$langPath = $filePath."/lang";
				if(is_dir($langPath))
				{
					$langDir = $langPath;
					$fileName = mb_substr($file, $slashPos);
					$langDirCache[$path] = $langDir;
					break;
				}
			}
		}

		$mess = array();
		if($langDir <> "")
		{
			//load messages for default lang first
			$defaultLang = \Bitrix\Main\Localization\Loc::getDefaultLang($language);
			if($defaultLang <> $language)
			{
				$langFile = $langDir."/".$defaultLang.$fileName;
				if(file_exists($langFile))
				{
					return $langFile;
				}
			}

			//then load messages for specified lang
			$langFile = $langDir."/".$language.$fileName;
			if(file_exists($langFile))
			{
				return $langFile;
			}
		}

		return $langFile;
	}
}



