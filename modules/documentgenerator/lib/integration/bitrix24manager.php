<?

namespace Bitrix\DocumentGenerator\Integration;

use Bitrix\DocumentGenerator\Driver;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

class Bitrix24Manager
{
	const LIMIT_ERROR_CODE = 'DOCGEN_LIMIT_ERROR';

	/**
	 * Tells if module bitrix24 is installed.
	 *
	 * @return bool
	 */
	public static function isEnabled()
	{
		return ModuleManager::isModuleInstalled('bitrix24');
	}

	/**
	 * Returns true if tariff for this portal is not free.
	 *
	 * @return bool
	 */
	public static function isLicensePaid()
	{
		if(Loader::includeModule('bitrix24'))
		{
			return \CBitrix24::IsLicensePaid();
		}

		return false;
	}

	/**
	 * Returns true if demo is active.
	 *
	 * @return bool
	 */
	public static function isDemoLicense()
	{
		if(Loader::includeModule('bitrix24'))
		{
			return \CBitrix24::IsDemoLicense();
		}

		return false;
	}

	/**
	 * Returns true if it is an nfr portal.
	 *
	 * @return bool
	 */
	public static function isNfrLicense()
	{
		if(Loader::includeModule('bitrix24'))
		{
			return \CBitrix24::IsNfrLicense();
		}

		return false;
	}

	/**
	 * Returns true if restrictions are active.
	 *
	 * @return bool
	 */
	public static function isRestrictionsActive()
	{
		if(static::isEnabled())
		{
			if(static::isDemoLicense() || static::isLicensePaid() || static::isNfrLicense())
			{
				return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * Returns true
	 *
	 * @return bool
	 */
	public static function isDocumentsLimitReached()
	{
		static $result = null;
		if($result === null)
		{
			if(static::isRestrictionsActive())
			{
				$result = (static::getDocumentsCount() >= static::getDocumentsLimit());
			}
			else
			{
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * @return int
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function getDocumentsLimit()
	{
		return Option::get(Driver::MODULE_ID, 'maximum_documents_on_free_tariff', 100);
	}

	/**
	 * Init javascript license popup.
	 *
	 * @param string $featureGroupName
	 */
	public static function initLicenseInfoPopupJS($featureGroupName = "")
	{
		if(Loader::includeModule('bitrix24'))
		{
			\CBitrix24::initLicenseInfoPopupJS($featureGroupName);
		}
	}

	public static function increaseDocumentsCount()
	{
		$count = static::getDocumentsCount();
		$count++;
		static::setDocumentsCount($count);
	}

	protected static function getDocumentsCount()
	{
		return \CUserOptions::GetOption(Driver::MODULE_ID, 'documents_count', 0);
	}

	protected static function setDocumentsCount($count)
	{
		\CUserOptions::SetOption(Driver::MODULE_ID, 'documents_count', $count);
	}

	/**
	 * @param string $region
	 * @return array
	 */
	public static function getFeedbackFormInfo($region)
	{
		if($region == 'ru')
		{
			return ['id' => 40, 'lang' => 'ru', 'sec' => 'b2bdce'];
		}
		elseif($region == 'br')
		{
			return ['id' => 30, 'lang' => 'br', 'sec' => '0j7lwo'];
		}
		elseif($region == 'la')
		{
			return ['id' => 32, 'lang' => 'la', 'sec' => '5vb40n'];
		}
		elseif($region == 'de')
		{
			return ['id' => 36, 'lang' => 'de', 'sec' => 'yrqoue'];
		}
		elseif($region == 'ua')
		{
			return ['id' => 42, 'lang' => 'ua', 'sec' => 'fyzjb2'];
		}
		else // en
		{
			return ['id' => 38, 'lang' => 'en', 'sec' => 's2thdq'];
		}
	}

	public static function showTariffRestrictionButtons()
	{
		if(Loader::includeModule('bitrix24'))
		{
			\CBitrix24::showTariffRestrictionButtons('documentgenerator_create');
		}
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getDefaultLanguage()
	{
		if(Loader::includeModule('bitrix24'))
		{
			return \CBitrix24::GetDefaultLanguage();
		}
	}
}