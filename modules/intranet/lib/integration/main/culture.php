<?

namespace Bitrix\Intranet\Integration\Main;

use Bitrix\Main;
use Bitrix\Main\Localization\CultureTable;
use Bitrix\Main\Localization\Loc;

final class Culture
{
	public static function getCultures(): array
	{
		$langCultures = [];
		$data = CultureTable::getList();

		global $b24Languages;
		$fileName = \Bitrix\Main\Application::getDocumentRoot() . getLocalPath('templates/bitrix24', BX_PERSONAL_ROOT) . "/languages.php";
		if (\Bitrix\Main\IO\File::isFileExists($fileName))
		{
			include_once $fileName;
		}

		while($culture = $data->fetch())
		{
			$langCultures[$culture['ID']] = [
				'ID' => $culture['ID'],
				'CODE' => $culture['CODE'],
				'NAME' =>  $b24Languages[trim($culture['NAME'])]['NAME']?? $culture['NAME'],
				'SHORT_DATE_FORMAT' => $culture["SHORT_DATE_FORMAT"] !== ''
					? htmlspecialcharsbx(FormatDate($culture["SHORT_DATE_FORMAT"]))
					: '',

				'LONG_DATE_FORMAT' => $culture["LONG_DATE_FORMAT"] !== ''
					? htmlspecialcharsbx(FormatDate($culture["LONG_DATE_FORMAT"]))
					: '',
			];
		}

		return $langCultures;
	}

	public static function getCurrentSite()
	{
		$data = \CSite::GetByID(SITE_ID);

		if ($site = $data->fetch())
		{
			return $site;
		}

		return false;
	}

	public static function updateCurrentSiteCulture($cultureId)
	{
		$site = new \CSite;

		$site->Update(SITE_ID, [
			'CULTURE_ID' => (int) $cultureId,
		]);
	}

	private static function getDefaultCulture($languageId): array
	{
		static $cultures;
		if (!$cultures)
		{
			if (method_exists('Bitrix\Main\Localization\Culture', 'getDefaultByLanguage'))
			{
				$cultures = Main\Localization\Culture::getDefaultByLanguage();
			}
			else
			{
				$file = new Main\IO\File(
					Main\IO\Path::convertRelativeToAbsolute(BX_ROOT.'/modules/main/install/index.php')
				);
				$dir = new Main\IO\Directory(
					Main\IO\Path::convertRelativeToAbsolute(BX_ROOT.'/modules/main/lang')
				);
				$cultures = [];
				foreach ($dir->getChildren() as $langDir)
				{
					$messages = Loc::loadLanguageFile($file->getPath(), $langDir->getName());
					$cultures[$langDir->getName()] = [
						"FORMAT_DATE" => $messages["MAIN_DEFAULT_LANGUAGE_FORMAT_DATE"],
						"FORMAT_DATETIME" => $messages["MAIN_DEFAULT_LANGUAGE_FORMAT_DATETIME"],
						"FORMAT_NAME" => $messages["MAIN_DEFAULT_LANGUAGE_FORMAT_NAME"],
						"SHORT_TIME_FORMAT" => $messages["MAIN_DEFAULT_LANGUAGE_SHORT_TIME_FORMAT"],
						"LONG_TIME_FORMAT" => $messages["MAIN_DEFAULT_LANGUAGE_LONG_TIME_FORMAT"],
					];
				}
			}
		}
		if (array_key_exists($languageId, $cultures))
		{
			return $cultures[$languageId];
		}
		return $cultures['en'];
	}

	public static function updateCulture($fields)
	{
		$newFields = [];
		$currentCulture = Main\Context::getCurrent()->getCulture();
		if (isset($fields['TIME_FORMAT_TYPE']))
		{
			$type = (int) $fields['TIME_FORMAT_TYPE'];

			$defaultCulture = self::getDefaultCulture(Main\Context::getCurrent()->getLanguage());

			$formatDate = $currentCulture->getFormatDate();
			if ($type === 12)
			{
				if (strpos($currentCulture->getFormatDatetime(), ' H:MI:SS T') === false)
				{
					$newFields['FORMAT_DATETIME'] = $formatDate . ' H:MI:SS T';
				}
				if ($currentCulture->getShortTimeFormat() !== 'g:i a')
				{
					$newFields['SHORT_TIME_FORMAT'] = 'g:i a';
				}
				if ($currentCulture->getLongTimeFormat() !== 'g:i:s a')
				{
					$newFields['LONG_TIME_FORMAT'] = 'g:i:s a';
				}
			}
			else
			{
				if (strpos($currentCulture->getFormatDatetime(), ' HH:MI:SS') === false)
				{
					$newFields['FORMAT_DATETIME'] = $formatDate . ' HH:MI:SS';
				}

				if ($currentCulture->getShortTimeFormat() === 'g:i a')
				{
					if (mb_strpos($defaultCulture['SHORT_TIME_FORMAT'], 'H') === false
						&& mb_strpos($defaultCulture['SHORT_TIME_FORMAT'], 'G') === false)
					{
						$newFields['SHORT_TIME_FORMAT'] = 'G:i';
					}
					else
					{
						$newFields['SHORT_TIME_FORMAT'] = $defaultCulture['SHORT_TIME_FORMAT'];
					}
				}
				if ($currentCulture->getLongTimeFormat() === 'g:i:s a')
				{
					if (mb_strpos($defaultCulture['LONG_TIME_FORMAT'], 'H') === false
						&& mb_strpos($defaultCulture['LONG_TIME_FORMAT'], 'G') === false)
					{
						$newFields['LONG_TIME_FORMAT'] = 'G:i:s';
					}
					else
					{
						$newFields['LONG_TIME_FORMAT'] = $defaultCulture['LONG_TIME_FORMAT'];
					}
				}
			}
		}

		if (isset($fields['WEEK_START']) && (int) $currentCulture->getWeekStart() !== (int) $fields['WEEK_START'])
		{
			$newFields['WEEK_START'] = $fields['WEEK_START'];
		}

		if (isset($fields['FORMAT_NAME']) && $currentCulture->getFormatName() !== $fields['FORMAT_NAME'])
		{
			$newFields['FORMAT_NAME'] = $fields['FORMAT_NAME'];
		}

		if (sizeof($newFields) > 0)
		{
			CultureTable::update($currentCulture->getId(), $newFields);
		}
	}
}