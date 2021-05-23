<?php

namespace Bitrix\Rest\Marketplace;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;
use Bitrix\Main;
use Bitrix\Rest\PlacementTable;
use Bitrix\Rest\Engine\Access;
use Bitrix\Rest\AppLangTable;
use Bitrix\Rest\Event\Sender;
use Bitrix\Rest\OAuthService;
use Bitrix\Rest\AppLogTable;
use Bitrix\Rest\EventTable;
use Bitrix\Rest\Analytic;
use Bitrix\Rest\AppTable;
use CRestUtil;

Loc::loadMessages(__FILE__);

class Application
{
	public static function install($code, $version = false, $checkHash = false, $installHash = false, $from = null) : array
	{
		$result = [];
		if (CRestUtil::canInstallApplication())
		{
			if (!OAuthService::getEngine()->isRegistered())
			{
				try
				{
					OAuthService::register();
				}
				catch(SystemException $e)
				{
					$result = [
						'error' => $e->getCode(),
						'error_description' => $e->getMessage()
					];
				}
			}

			if (OAuthService::getEngine()->isRegistered())
			{
				$version = !empty($version) ? $version : false;

				$result = [
					'error' => Loc::getMessage('RMP_INSTALL_ERROR')
				];

				$appDetailInfo = false;
				if ($code <> '')
				{
					if (!empty($checkHash) && !empty($installHash))
					{
						$appDetailInfo = Client::getInstall($code, $version, $checkHash, $installHash);
					}
					else
					{
						$appDetailInfo = Client::getInstall($code, $version);
					}

					if ($appDetailInfo)
					{
						$appDetailInfo = $appDetailInfo['ITEMS'];
					}
				}

				if (
					$appDetailInfo
					&& (
						!Access::isAvailable($code)
						|| !Access::isAvailableCount(Access::ENTITY_TYPE_APP, $code)
					)
				)
				{
					$result = [
						'error' => Loc::getMessage('RMP_ERROR_ACCESS_DENIED'),
						'helperCode' => Access::getHelperCode(Access::ACTION_INSTALL, Access::ENTITY_TYPE_APP, $appDetailInfo)
					];
				}
				elseif ($appDetailInfo)
				{
					if (CRestUtil::canInstallApplication($appDetailInfo))
					{
						$queryFields = [
							'CLIENT_ID' => $appDetailInfo['APP_CODE'],
							'VERSION' => $appDetailInfo['VER'],
							'BY_SUBSCRIPTION' => $appDetailInfo['BY_SUBSCRIPTION'] === 'Y' ? 'Y' : 'N',
						];

						if (isset($checkHash, $installHash))
						{
							$queryFields['CHECK_HASH'] = $checkHash;
							$queryFields['INSTALL_HASH'] = $installHash;
						}

						$installResult = OAuthService::getEngine()->getClient()->installApplication($queryFields);

						if ($installResult['error'])
						{
							$result['error_description'] = $installResult['error'] . ': ' . $installResult['error_description'];
						}
						elseif ($installResult['result'])
						{
							$appFields = [
								'CLIENT_ID' => $installResult['result']['client_id'],
								'CODE' => $appDetailInfo['CODE'],
								'ACTIVE' => AppTable::ACTIVE,
								'INSTALLED' => ($appDetailInfo['OPEN_API'] === 'Y' || empty($appDetailInfo['INSTALL_URL']))
									? AppTable::INSTALLED
									: AppTable::NOT_INSTALLED,
								'URL' => $appDetailInfo['URL'],
								'URL_DEMO' => $appDetailInfo['DEMO_URL'],
								'URL_INSTALL' => $appDetailInfo['INSTALL_URL'],
								'VERSION' => $installResult['result']['version'],
								'SCOPE' => implode(',', $installResult['result']['scope']),
								'STATUS' => $installResult['result']['status'],
								'SHARED_KEY' => $appDetailInfo['SHARED_KEY'],
								'CLIENT_SECRET' => '',
								'APP_NAME' => $appDetailInfo['NAME'],
								'MOBILE' => $appDetailInfo['BXMOBILE'] === 'Y' ? AppTable::ACTIVE : AppTable::INACTIVE,
								'USER_INSTALL' => CRestUtil::appCanBeInstalledByUser($appDetailInfo) ? AppTable::ACTIVE : AppTable::INACTIVE,
							];

							if (
								$appFields['STATUS'] === AppTable::STATUS_TRIAL
								|| $appFields['STATUS'] === AppTable::STATUS_PAID
							)
							{
								$appFields['DATE_FINISH'] = DateTime::createFromTimestamp($installResult['result']['date_finish']);
							}
							else
							{
								$appFields['DATE_FINISH'] = '';
							}

							//Configuration app
							if ($appDetailInfo['TYPE'] === AppTable::TYPE_CONFIGURATION)
							{
								$appFields['INSTALLED'] = AppTable::NOT_INSTALLED;
							}

							$existingApp = AppTable::getByClientId($appFields['CLIENT_ID']);
							if ($existingApp)
							{
								$addResult = AppTable::update($existingApp['ID'], $appFields);
							}
							else
							{
								$addResult = AppTable::add($appFields);
							}

							if ($addResult->isSuccess())
							{
								$appId = $addResult->getId();

								if ($existingApp)
								{
									AppLogTable::log($appId, AppLogTable::ACTION_TYPE_UPDATE);
								}
								else
								{
									AppLogTable::log($appId, AppLogTable::ACTION_TYPE_ADD);
								}

								if ($appFields['INSTALLED'] === AppTable::INSTALLED)
								{
									AppLogTable::log($appId, AppLogTable::ACTION_TYPE_INSTALL);
								}

								if (!CRestUtil::isAdmin())
								{
									CRestUtil::notifyInstall($appFields);
								}

								if (is_array($appDetailInfo['MENU_TITLE']))
								{
									foreach ($appDetailInfo['MENU_TITLE'] as $lang => $langName)
									{
										$appLangFields = array(
											'APP_ID' => $appId,
											'LANGUAGE_ID' => $lang,
											'MENU_NAME' => $langName
										);

										$appLangUpdateFields = array(
											'MENU_NAME' => $langName
										);

										$connection = Main\Application::getConnection();
										$queries = $connection->getSqlHelper()->prepareMerge(
											AppLangTable::getTableName(),
											[
												'APP_ID',
												'LANGUAGE_ID'
											],
											$appLangFields,
											$appLangUpdateFields
										);

										foreach($queries as $query)
										{
											$connection->queryExecute($query);
										}
									}
								}

								if ($appDetailInfo['OPEN_API'] === 'Y' && !empty($appFields['URL_INSTALL']))
								{
									// checkCallback is already called inside checkFields
									$result = EventTable::add(
										[
											'APP_ID' => $appId,
											'EVENT_NAME' => 'ONAPPINSTALL',
											'EVENT_HANDLER' => $appFields['URL_INSTALL'],
										]
									);
									if ($result->isSuccess())
									{
										Sender::bind('rest', 'OnRestAppInstall');
									}
								}

								AppTable::install($appId);

								$uriString = CRestUtil::getApplicationPage($appId);
								$uri = new Uri($uriString);
								$ver = (int) $version;
								$uri->addParams(
									[
										'ver' => $ver,
										'check_hash' => $checkHash,
										'install_hash' => $installHash
									]
								);
								$redirect = $uri->getUri();

								$result = [
									'success' => 1,
									'id' => $appId,
									'open' => $appDetailInfo['OPEN_API'] !== 'Y',
									'installed' => $appFields['INSTALLED'] === 'Y',
									'redirect' => $redirect,
								];

								Analytic::logToFile(
									'finishInstall',
									$code,
									$from ?? 'index'
								);
							}
							else
							{
								$result['error_description'] = implode('<br />', $addResult->getErrorMessages());
							}
						}
					}
					else
					{
						$result = ['error' => Loc::getMessage('RMP_ACCESS_DENIED')];
					}
				}
				else
				{
					$result = ['error' => Loc::getMessage('RMP_NOT_FOUND')];
				}
			}
			elseif (!$result['error'])
			{
				$result = ['error' => Loc::getMessage('RMP_INSTALL_ERROR')];
			}
		}
		else
		{
			$result = ['error' => Loc::getMessage('RMP_ACCESS_DENIED')];
		}

		return $result;
	}

	public static function uninstall($code, bool $clean = false, $from = null) : array
	{
		if (CRestUtil::isAdmin())
		{
			$res = AppTable::getList(
				[
					'filter' => [
						'=CODE' => $code,
						'!=STATUS' => AppTable::STATUS_LOCAL,
					],
				]
			);

			$appInfo = $res->fetch();
			if ($appInfo)
			{
				$checkResult = AppTable::checkUninstallAvailability($appInfo['ID'], $clean);
				if (
					$checkResult->isEmpty()
					&& AppTable::canUninstallByType($appInfo['CODE'], $appInfo['VERSION'])
				)
				{
					AppTable::uninstall($appInfo['ID'], $clean);

					$appFields = [
						'ACTIVE' => 'N',
						'INSTALLED' => 'N',
					];

					AppTable::update($appInfo['ID'], $appFields);

					AppLogTable::log($appInfo['ID'], AppLogTable::ACTION_TYPE_UNINSTALL);

					Analytic::logToFile(
						'finishUninstall',
						$appInfo['CODE'],
						$from ?? 'index'
					);

					$result = ['success' => 1];
				}
				else
				{
					$errorMessage = '';
					foreach ($checkResult as $error)
					{
						$errorMessage .= $error->getMessage() . "\n";
					}

					$result = ['error' => $errorMessage];
					if (
						$checkResult->isEmpty()
						&& AppTable::getAppType($appInfo['CODE']) == AppTable::TYPE_CONFIGURATION
					)
					{
						$result = [
							'sliderUrl' => \Bitrix\Rest\Marketplace\Url::getConfigurationImportRollbackUrl(
								$appInfo['CODE']
							),
						];
					}
				}
			}
			else
			{
				$result = ['error' => Loc::getMessage('RMP_NOT_FOUND')];
			}
		}
		else
		{
			$result = ['error' => Loc::getMessage('RMP_ACCESS_DENIED')];
		}

		return $result;
	}

	public static function reinstall($id) : array
	{
		$result = [];
		if (CRestUtil::isAdmin())
		{
			$appInfo = AppTable::getByClientId($id);
			if (
				!Access::isAvailable($id)
				|| !Access::isAvailableCount(Access::ENTITY_TYPE_APP, $id)
			)
			{
				$result = [
					'error' => Loc::getMessage('RMP_ERROR_ACCESS_DENIED'),
					'helperCode' => Access::getHelperCode(Access::ACTION_INSTALL, Access::ENTITY_TYPE_APP, $appInfo)
				];
			}
			elseif ($appInfo && $appInfo['STATUS'] === AppTable::STATUS_LOCAL)
			{
				if (empty($appInfo['MENU_NAME']) && empty($appInfo['MENU_NAME_DEFAULT']))
				{
					AppTable::install($appInfo['ID']);
					$result = ['success' => 1];
				}
				elseif (!empty($appInfo['URL_INSTALL']))
				{
					$appFields = [
						'INSTALLED' => 'N',
					];

					AppTable::update($appInfo['ID'], $appFields);

					$result = [
						'success' => 1,
						'redirect' => CRestUtil::getApplicationPage($appInfo['ID']),
					];
				}
			}
			else
			{
				$result = ['error' => Loc::getMessage('RMP_NOT_FOUND')];
			}
		}
		else
		{
			$result = ['error' => Loc::getMessage('RMP_ACCESS_DENIED')];
		}

		return $result;
	}

	public static function setRights($appId, $rights) : array
	{
		$result = [];
		if (CRestUtil::isAdmin())
		{
			if ($appId > 0)
			{
				$appInfo = AppTable::getByClientId($appId);
				if ($appInfo['CODE'])
				{
					Analytic::logToFile(
						'setAppRight',
						$appInfo['CODE'],
						$appInfo['CODE']
					);
				}
				AppTable::setAccess($appId, $rights);
				PlacementTable::clearHandlerCache();
				$result = ['success' => 1];
			}
		}
		else
		{
			$result = ['error' => Loc::getMessage('RMP_ACCESS_DENIED')];
		}

		return $result;
	}

	public static function getRights($appId)
	{
		if (CRestUtil::isAdmin())
		{
			if ($appId > 0)
			{
				$result = AppTable::getAccess($appId);
			}
			else
			{
				$result = 0;
			}
		}
		else
		{
			$result = ['error' => Loc::getMessage('RMP_ACCESS_DENIED')];
		}

		return $result;
	}
}