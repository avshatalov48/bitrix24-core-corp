<?

namespace Bitrix\DocumentGenerator\Integration;

use Bitrix\Bitrix24\Feature;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\Main\Loader;

class Bitrix24Manager
{
	const LIMIT_ERROR_CODE = 'DOCGEN_LIMIT_ERROR';
	const DEFAULT_TEMPLATE_SIZE = 2097152;

	/**
	 * Tells if module bitrix24 is installed.
	 *
	 * @return bool
	 */
	public static function isEnabled()
	{
		return Loader::includeModule('bitrix24');
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
			return (static::getDocumentsLimit() > 0);
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
	 */
	public static function getDocumentsLimit()
	{
		if(static::isEnabled())
		{
			return \Bitrix\Bitrix24\Feature::getVariable('documentgenerator_create_documents');
		}

		return 0;
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

    /**
     * @return int
     */
    public static function getDocumentsCount()
	{
		return \CUserOptions::GetOption(Driver::MODULE_ID, 'documents_count', 0);
	}

    /**
     * @param int $count
     */
    public static function setDocumentsCount($count)
	{
		\CUserOptions::SetOption(Driver::MODULE_ID, 'documents_count', $count);
	}

	public static function getPortalZone(): string
	{
		return (string)\CBitrix24::getPortalZone();
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

	/**
	 * @return bool
	 */
	public static function isPermissionsFeatureEnabled()
	{
		if(static::isEnabled())
		{
			return \Bitrix\Bitrix24\Feature::isFeatureEnabled("documentgenerator_permissions");
		}

		return true;
	}

	public static function getMaximumTemplateFileSize()
	{
		$size = null;
		if(static::isEnabled())
		{
			$size = Feature::getVariable('documentgenerator_template_size');
		}

		if(!$size)
		{
			if(defined('DOCUMENTGENERATOR_MAXIMUM_TEMPLATE_SIZE'))
			{
				$size = DOCUMENTGENERATOR_MAXIMUM_TEMPLATE_SIZE;
			}
			else
			{
				$size = static::DEFAULT_TEMPLATE_SIZE;
			}
		}

		return $size;
	}
}