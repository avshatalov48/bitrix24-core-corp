<?php

namespace Bitrix\Crm\Integration;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

/**
 * Class Bitrix24Manager
 *
 * Required in Biitrix24 context. Provodes information about the license and supported features.
 * @package Bitrix\Crm\Integration
 */
class Bitrix24Manager
{
	//region Members
	/** @var bool|null */
	private static $hasPurchasedLicense = null;
	/** @var bool|null */
	private static $hasDemoLicense = null;
	/** @var bool|null */
	private static $hasNfrLicense = null;
	/** @var bool|null */
	private static $hasPurchasedUsers = null;
	/** @var bool|null */
	private static $hasPurchasedDiskSpace = null;
	/** @var bool|null */
	private static $isPaidAccount = null;
	/** @var bool|null */
	private static $enableRestBizProc = null;
	/** @var array|null */
	private static $entityAccessFlags = null;
	/** @var array|null */
	private static $unlimitedAccessFlags = null;
	//endregion
	//region Methods
	/**
	 * Check if current manager enabled.
	 * @return bool
	 */
	public static function isEnabled()
	{
		return ModuleManager::isModuleInstalled('bitrix24');
	}
	/**
	 * Check if portal has paid license, paid for extra users, paid for disk space or SIP features.
	 * @return bool
	 */
	public static function isPaidAccount()
	{
		if(self::$isPaidAccount !== null)
		{
			return self::$isPaidAccount;
		}

		self::$isPaidAccount = self::hasPurchasedLicense()
			|| self::hasPurchasedUsers()
			|| self::hasPurchasedDiskSpace();

		if(!self::$isPaidAccount)
		{
			//Phone number check: voximplant::account_payed
			//SIP connector check: main::~PARAM_PHONE_SIP
			self::$isPaidAccount = \COption::GetOptionString('voximplant', 'account_payed', 'N') === 'Y'
				|| \COption::GetOptionString('main', '~PARAM_PHONE_SIP', 'N') === 'Y';
		}

		return self::$isPaidAccount;
	}
	/**
	 * Check if portal has paid license.
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function hasPurchasedLicense()
	{
		if(self::$hasPurchasedLicense !== null)
		{
			return self::$hasPurchasedLicense;
		}

		if(!(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24'))
			&& method_exists('CBitrix24', 'IsLicensePaid'))
		{
			return (self::$hasPurchasedLicense = false);
		}

		return (self::$hasPurchasedLicense = \CBitrix24::IsLicensePaid());
	}
	/**
	 *  Check if portal has trial license.
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function hasDemoLicense()
	{
		if(self::$hasDemoLicense !== null)
		{
			return self::$hasDemoLicense;
		}

		if(!(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24'))
			&& method_exists('CBitrix24', 'IsDemoLicense'))
		{
			return (self::$hasDemoLicense = false);
		}

		return (self::$hasDemoLicense = \CBitrix24::IsDemoLicense());
	}
	/**
	 * Check if portal has NFR license.
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function hasNfrLicense()
	{
		if(self::$hasNfrLicense !== null)
		{
			return self::$hasNfrLicense;
		}

		if(!(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24'))
			&& method_exists('CBitrix24', 'IsNfrLicense'))
		{
			return (self::$hasNfrLicense = false);
		}

		return (self::$hasNfrLicense = \CBitrix24::IsNfrLicense());
	}
	/**
	 * Check if portal has paid for extra users.
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function hasPurchasedUsers()
	{
		if(self::$hasPurchasedUsers !== null)
		{
			return self::$hasPurchasedUsers;
		}

		if(!(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24'))
			&& method_exists('CBitrix24', 'IsExtraUsers'))
		{
			return (self::$hasPurchasedUsers = false);
		}

		return (self::$hasPurchasedUsers = \CBitrix24::IsExtraUsers());
	}
	/**
	 * Check if portal has paid for extra disk space.
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function hasPurchasedDiskSpace()
	{
		if(self::$hasPurchasedDiskSpace !== null)
		{
			return self::$hasPurchasedDiskSpace;
		}

		if(!(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24'))
			&& method_exists('CBitrix24', 'IsExtraDiskSpace'))
		{
			return (self::$hasPurchasedDiskSpace = false);
		}

		return (self::$hasPurchasedDiskSpace = \CBitrix24::IsExtraDiskSpace());
	}
	/**
	 * Check if Business Processes are enabled for REST API.
	 * @return bool
	 */
	public static function isRestBizProcEnabled()
	{
		if(self::$enableRestBizProc !== null)
		{
			return self::$enableRestBizProc;
		}

		return (self::$enableRestBizProc = (self::hasPurchasedLicense() || self::hasNfrLicense() || self::hasDemoLicense()));
	}

	/**
	 * @param array $params
	 * @return array|null
	 */
	public static function prepareStubInfo(array $params)
	{
		if(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24')
			&& method_exists('CBitrix24', 'prepareStubInfo'))
		{
			$title = isset($params['TITLE']) ? $params['TITLE'] : '';
			$content = isset($params['CONTENT']) ? $params['CONTENT'] : '';

			$replacements = isset($params['REPLACEMENTS']) && is_array($params['REPLACEMENTS'])
				? $params['REPLACEMENTS'] : array();

			if(!empty($replacements))
			{
				$search = array_keys($replacements);
				$replace = array_values($replacements);

				$title = str_replace($search, $replace, $title);
				$content = str_replace($search, $replace, $content);
			}

			$licenseAllButtonClass = ($params['GLOBAL_SEARCH']? 'ui-btn ui-btn-xs ui-btn-light-border' : 'success');
			$licenseDemoButtonClass = ($params['GLOBAL_SEARCH']? 'ui-btn ui-btn-xs ui-btn-light' : '');

			$options = [];
			if (isset($params['ANALYTICS_LABEL']) && $params['ANALYTICS_LABEL'] != '')
			{
				$options['ANALYTICS_LABEL'] = $params['ANALYTICS_LABEL'];
			}

			return \CBitrix24::prepareStubInfo(
				$title,
				$content,
				array(
					array('ID' => \CBitrix24::BUTTON_LICENSE_ALL, 'CLASS_NAME' => $licenseAllButtonClass),
					array('ID' => \CBitrix24::BUTTON_LICENSE_DEMO, 'CLASS_NAME' => $licenseDemoButtonClass),
				),
				$options
			);
		}

		return null;
	}

	/**
	 * Prepare JavaScript for license purchase information.
	 * @param array $params Popup params.
	 * @return string
	 * @throws Main\LoaderException
	 */
	public static function prepareLicenseInfoPopupScript(array $params)
	{
		if(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24')
			&& method_exists('CBitrix24', 'initLicenseInfoPopupJS')
		)
		{
			\CBitrix24::initLicenseInfoPopupJS();

			$popupID = isset($params['ID']) ? \CUtil::JSEscape($params['ID']) : '';
			$title = isset($params['TITLE']) ? \CUtil::JSEscape($params['TITLE']) : '';
			$content = '';
			if(isset($params['CONTENT']))
			{
				$content = \CUtil::JSEscape(
					str_replace(
						'#TF_PRICE#',
						\CBitrix24::getLicensePrice('tf'),
						$params['CONTENT']
					)
				);
			}
			return "if(typeof(B24.licenseInfoPopup) !== 'undefined'){ B24.licenseInfoPopup.show('{$popupID}', '{$title}', '{$content}'); }";
		}

		return '';
	}
	/**
	 * Prepare JavaScript for opening purchaise information by info-helper slider
	 * @param array $params Info-helper params.
	 * @return string
	 * @throws Main\LoaderException
	 */
	public static function prepareLicenseInfoHelperScript(array $params)
	{
		$script = '';

		if(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24')
			&& ModuleManager::isModuleInstalled('ui')
			&& Loader::includeModule('ui')
		)
		{
			if ((is_string($params['ID']) && $params['ID'] !== ''))
			{
				$script = 'if (top.hasOwnProperty("BX") && top.BX !== null && typeof(top.BX) === "function"'.
					' && top.BX.hasOwnProperty("UI") && top.BX.UI !== null && typeof(top.BX.UI) === "object"'.
					' && top.BX.UI.hasOwnProperty("InfoHelper") && top.BX.UI.InfoHelper !== null'.
					' && typeof(top.BX.UI.InfoHelper) === "object" && top.BX.UI.InfoHelper.hasOwnProperty("show")'.
					' && typeof(top.BX.UI.InfoHelper.show) === "function"){top.BX.UI.InfoHelper.show("'.
					\CUtil::JSEscape($params['ID']).'");}';
			}
		}

		return $script;
	}
	/**
	 * Prepare HTML for license purchase information.
	 * @param array $params Popup params.
	 * @return string
	 * @throws Main\LoaderException
	 */
	public static function prepareLicenseInfoHtml(array $params)
	{
		if(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24'))
		{
			$popupID = isset($params['ID']) ? \CUtil::JSEscape($params['ID']) : '';
			$content = '';
			if(isset($params['CONTENT']))
			{
				$licenseListUrl = \CUtil::JSEscape(\CBitrix24::PATH_LICENSE_ALL);
				$demoLicenseUrl = \CUtil::JSEscape(\CBitrix24::PATH_LICENSE_DEMO);

				$content = str_replace(
					array(
						'#LICENSE_LIST_SCRIPT#',
						'#DEMO_LICENSE_SCRIPT#',
						'#TF_PRICE#'
					),
					array(
						"BX.CrmRemoteAction.items['{$popupID}'].execute('{$licenseListUrl}');",
						"BX.CrmRemoteAction.items['{$popupID}'].execute('{$demoLicenseUrl}');",
						\CBitrix24::getLicensePrice('tf')
					),
					$params['CONTENT']
				);
			}

			$serviceUrl = \CUtil::JSEscape(\CBitrix24::PATH_COUNTER);
			$hostName = \CUtil::JSEscape(BX24_HOST_NAME);
			return "{$content}
				<script type='text/javascript'>
					BX.ready(
						function()
						{
							BX.CrmRemoteAction.create(
								'{$popupID}',
								{
									serviceUrl: '{$serviceUrl}',
									data: { host: '{$hostName}', action: 'tariff', popupId: '{$popupID}' }
								}
							);
						}
					);
				</script>";
		}

		return '';
	}
	/**
	 * Get URL for "Choose a Bitrix24 plan" page.
	 * @return string
	 * @throws Main\LoaderException
	 */
	public static function getLicenseListPageUrl()
	{
		if(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24'))
		{
			return \CBitrix24::PATH_LICENSE_ALL;
		}

		return '';
	}
	/**
	 * Get URL for "Free 30-day trial" page.
	 * @return string
	 * @throws Main\LoaderException
	 */
	public static function getDemoLicensePageUrl()
	{
		if(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24'))
		{
			return \CBitrix24::PATH_LICENSE_DEMO;
		}

		return '';
	}
	/**
	 * Check accessability of entity type according to Bitrix24 restrictions.
	 * @param int $entityTypeID Entity type ID.
	 * @param int $userID User ID (if not specified then current user ID will be taken).
	 * @return bool
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\LoaderException
	 */
	public static function isAccessEnabled($entityTypeID, $userID = 0)
	{
		if(!is_integer($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if(!is_integer($userID))
		{
			$userID = (int)$userID;
		}

		if($userID <= 0)
		{
			$userID = \CCrmSecurityHelper::GetCurrentUserID();
		}

		if(self::$entityAccessFlags === null)
		{
			self::$entityAccessFlags = array();
		}

		if(!isset(self::$entityAccessFlags[$userID]))
		{
			self::$entityAccessFlags[$userID] = array();
		}

		$code = $entityTypeID === \CCrmOwnerType::Lead ? 'crm_lead' : 'crm';
		if(isset(self::$entityAccessFlags[$userID][$code]))
		{
			return self::$entityAccessFlags[$userID][$code];
		}

		if(!(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24')
			&& method_exists('CBitrix24BusinessTools', 'isToolAvailable')))
		{
			return (self::$entityAccessFlags[$userID][$code] = true);
		}

		return (self::$entityAccessFlags[$userID][$code] = \CBitrix24BusinessTools::isToolAvailable($userID, $code));
	}
	/**
	 * Check if user has unlimited access
	 * @param int $userID User ID (if not specified then current user ID will be taken).
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function isUnlimitedAccess($userID = 0)
	{
		if(!is_integer($userID))
		{
			$userID = (int)$userID;
		}

		if($userID <= 0)
		{
			$userID = \CCrmSecurityHelper::GetCurrentUserID();
		}

		if(self::$unlimitedAccessFlags === null)
		{
			self::$unlimitedAccessFlags = array();
		}

		if(isset(self::$unlimitedAccessFlags[$userID]))
		{
			return self::$unlimitedAccessFlags[$userID];
		}

		if(!(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24')
			&& method_exists('CBitrix24BusinessTools', 'isUserUnlimited')))
		{
			return (self::$unlimitedAccessFlags[$userID] = true);
		}

		return (self::$unlimitedAccessFlags[$userID] = \CBitrix24BusinessTools::isUserUnlimited($userID));
	}
	/**
	 * Get maximum allowed deal category quantity.
	 * @return int
	 * @throws Main\LoaderException
	 */
	public static function getDealCategoryCount()
	{
		if(!(ModuleManager::isModuleInstalled('bitrix24') && Loader::includeModule('bitrix24')))
		{
			return 0;
		}

		return self::getVariable('crm_deal_category_limit');
	}
	//endregion

	/**
	 * Check if specified feature is enabled
	 * @param string $releaseName Name of release.
	 * @return bool
	 */
	public static function isFeatureEnabled($releaseName)
	{
		if(!(ModuleManager::isModuleInstalled('bitrix24') && Loader::includeModule('bitrix24')))
		{
			return true;
		}

		return \Bitrix\Bitrix24\Feature::isFeatureEnabled($releaseName);
	}

	/**
	 * Get variable value.
	 * @param string $name Name of variable
	 * @return mixed|null
	 */
	public static function getVariable($name)
	{
		if(!(ModuleManager::isModuleInstalled('bitrix24') && Loader::includeModule('bitrix24')))
		{
			return null;
		}

		return \Bitrix\Bitrix24\Feature::getVariable($name);
	}

	/**
	 * Fetch variable with MAX value (for maximal editions)
	 *
	 * @param string $name Variable name
	 *
	 * @return int
	 */
	public static function getMaxVariable(string $name): int
	{
		if (!(ModuleManager::isModuleInstalled('bitrix24') && Loader::includeModule('bitrix24')))
		{
			return 0;
		}

		$allVariables = array_map(
			fn ($edition) => \Bitrix\Bitrix24\Feature::getVariable($name, $edition),
			\CBitrix24::MAXIMAL_EDITIONS
		);

		if (empty($allVariables))
		{
			return 0;
		}

		return max($allVariables);
	}

	/**
	 * Method determines if the installed license is an ENTERPRISE
	 *
	 * @return bool
	 */
	public static function isEnterprise(): bool
	{
		if (!(ModuleManager::isModuleInstalled('bitrix24') && Loader::includeModule('bitrix24')))
		{
			return false;
		}

		$licenseFamily = \CBitrix24::getLicenseFamily();

		return $licenseFamily === 'ent';
	}
}
