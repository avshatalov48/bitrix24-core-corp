<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\IO;
use Bitrix\Main\Web;
use Bitrix\Main\Error;
use Bitrix\Main\Config\Option;
use Bitrix\Sale\Registry;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Cashbox;
use Bitrix\Sale\BusinessValue;
use Bitrix\Sale\Helpers\Admin;
use Bitrix\Sale\Services\PaySystem\Restrictions;
use Bitrix\SalesCenter\Integration\SaleManager;
use Bitrix\Seo;

/**
 * Class SalesCenterPaySystemAjaxController
 */
class SalesCenterPaySystemAjaxController extends \Bitrix\Main\Engine\Controller
{
	/**
	 * @param \Bitrix\Main\Engine\Action $action
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function processBeforeAction(\Bitrix\Main\Engine\Action $action)
	{
		Loader::includeModule('salescenter');

		if(!SaleManager::getInstance()->isFullAccess(true))
		{
			Loc::loadMessages(__DIR__.'/class.php');
			$this->addError(new Error(Loc::getMessage("SP_SALESCENTER_SALE_ACCESS_DENIED")));
			return false;
		}

		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/sale/lib/helpers/admin/businessvalue.php');
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/sale/lib/cashbox/inputs/file.php');

		return parent::processBeforeAction($action);
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function savePaySystemAction()
	{
		$documentRoot = Application::getDocumentRoot();

		$id = (int)$this->request->get('ID');
		$handler = $this->request->get('ACTION_FILE');
		$psMode = $this->request->get('PS_MODE');
		$name = trim($this->request->get('NAME'));
		$description = trim($this->request->get('DESCRIPTION'));
		$active = $this->request->get('ACTIVE') ?? 'N';
		$isCanPrintCheck = $this->request->get('CAN_PRINT_CHECK');
		$sort = $this->request->get('SORT');
		$xmlId = $this->request->get('XML_ID');
		$isCash = $this->request->get('IS_CASH');
		$encoding = $this->request->get('ENCODING');
		$newWindow = $this->request->get('NEW_WINDOW');
		$allowEditPayment = $this->request->get('ALLOW_EDIT_PAYMENT');
		$code = $this->request->get('CODE');
		$isCanPrintCheckSelf = $this->request->get('CAN_PRINT_CHECK_SELF');

		if ($id > 0)
		{
			$service = PaySystem\Manager::getObjectById($id);
			if (!$service)
			{
				$this->errorCollection->setError(new Error(Loc::getMessage('SP_AJAX_SAVE_PAYSYSTEM_ERROR_UPDATE')));
				return [];
			}
		}

		// always active for new pay system
		if ((int)$id <= 0)
		{
			$active = 'Y';
		}

		if (empty($handler))
		{
			$this->errorCollection->add([new Error(Loc::getMessage('SP_AJAX_SAVE_PAYSYSTEM_ERROR_HANDLER'))]);
		}

		if (empty($name))
		{
			$this->errorCollection->add([new Error(Loc::getMessage('SP_AJAX_SAVE_PAYSYSTEM_ERROR_NAME'))]);
		}

		if ($this->getErrors())
		{
			return [];
		}

		$fields = [
			"NAME" => $name,
			"PSA_NAME" => $name,
			"ACTIVE" => $active ?? 'Y',
			"CAN_PRINT_CHECK" => ($isCanPrintCheck === 'Y' || $isCanPrintCheckSelf === 'Y') ? 'Y' : 'N',
			"CODE" => $code ?: '',
			"NEW_WINDOW" => ($newWindow === 'Y') ? 'Y' : 'N',
			"ALLOW_EDIT_PAYMENT" => ($allowEditPayment === 'Y') ? 'Y' : 'N',
			"IS_CASH" => $isCash ?? 'N',
			"ENTITY_REGISTRY_TYPE" => Registry::REGISTRY_TYPE_ORDER,
			"SORT" => $sort,
			"ENCODING" => $encoding,
			"DESCRIPTION" => $description,
			"ACTION_FILE" => $handler,
			'PS_MODE' => $psMode,
			'XML_ID' => $xmlId,
			'AUTO_CHANGE_1C' => 'N',
		];

		$file = $this->request->getFile('LOGOTIP');
		if ($file !== null && $file["error"] == 0)
		{
			$imageFileError = \CFile::CheckImageFile($file);
			if ($imageFileError === null)
			{
				$fields['LOGOTIP'] = $file;
				$fields['LOGOTIP']['del'] = trim($this->request->get("LOGOTIP_del"));
				$fields['LOGOTIP']['MODULE_ID'] = "sale";
				\CFile::SaveForDB($fields, 'LOGOTIP', 'sale/paysystem/logotip');
			}
			else
			{
				$this->errorCollection->add([new Error($imageFileError)]);
			}
		}
		elseif ($this->request->get("LOGOTIP_del") !== null && $this->request->get("LOGOTIP_del") == 'Y')
		{
			$fields['LOGOTIP'] = 0;
		}
		elseif ($id <= 0)
		{
			if ($psMode)
			{
				$image = '/bitrix/images/sale/sale_payments/'.$handler.'/'.$psMode.'.png';
				if (IO\File::isFileExists($documentRoot.$image))
				{
					$fields['LOGOTIP'] = \CFile::MakeFileArray($image);
					$fields['LOGOTIP']['MODULE_ID'] = "sale";
					\CFile::SaveForDB($fields, 'LOGOTIP', 'sale/paysystem/logotip');
				}
			}

			if (!isset($fields['LOGOTIP']))
			{
				$image = '/bitrix/images/sale/sale_payments/'.$handler.'.png';
				if (IO\File::isFileExists($documentRoot.$image))
				{
					$fields['LOGOTIP'] = \CFile::MakeFileArray($image);
					$fields['LOGOTIP']['MODULE_ID'] = "sale";
					\CFile::SaveForDB($fields, 'LOGOTIP', 'sale/paysystem/logotip');
				}
			}
		}

		$path = PaySystem\Manager::getPathToHandlerFolder($handler);
		if (IO\File::isFileExists($documentRoot.$path.'/handler.php'))
		{
			require_once $documentRoot.$path.'/handler.php';

			$className = PaySystem\Manager::getClassNameFromPath($path);
			$fields['HAVE_PAYMENT'] = 'Y';

			if (is_subclass_of($className, '\Bitrix\Sale\PaySystem\IPrePayable'))
			{
				$fields['HAVE_PREPAY'] = 'Y';
			}
			if (is_subclass_of($className, '\Bitrix\Sale\PaySystem\ServiceHandler'))
			{
				$fields['HAVE_RESULT_RECEIVE'] = 'Y';
			}
			if (is_subclass_of($className, '\Bitrix\Sale\PaySystem\IPayable'))
			{
				$fields['HAVE_PRICE'] = 'Y';
			}
			if (is_subclass_of($className, '\Bitrix\Sale\PaySystem\ICheckable'))
			{
				$fields['HAVE_RESULT'] = 'Y';
			}
		}

		if (PaySystem\Manager::isRestHandler($handler))
		{
			$fields['HAVE_PREPAY'] = 'N';
			$fields['HAVE_RESULT'] = 'N';
			$fields['HAVE_ACTION'] = 'N';
			$fields['HAVE_PAYMENT'] = 'N';
			$fields['HAVE_RESULT_RECEIVE'] = 'Y';
		}

		if ($this->getErrors())
		{
			return [];
		}

		$isBusinessValue = true;
		if (isset($_POST['PAYSYSTEMBizVal']))
		{
			try
			{
				$_POST['PAYSYSTEMBizVal'] = Web\Json::decode($_POST['PAYSYSTEMBizVal']);
				$_REQUEST['PAYSYSTEMBizVal'] = $_POST['PAYSYSTEMBizVal'];
			}
			catch (\Exception $ex)
			{
				$isBusinessValue = false;
			}
		}

		if ($id > 0)
		{
			$result = PaySystem\Manager::update($id, $fields);
			if ($result->isSuccess())
			{
				if ($isBusinessValue)
				{
					$this->saveBusinessValue($handler, $id, false);
				}
			}
			else
			{
				$this->errorCollection->add($result->getErrors());
			}
		}
		else
		{
			$result = PaySystem\Manager::add($fields);
			if ($result->isSuccess())
			{
				$id = $result->getId();
				if ($id > 0)
				{
					$fields = [
						'PARAMS' => serialize(
							[
								'BX_PAY_SYSTEM_ID' => $id,
							]
						),
						'PAY_SYSTEM_ID' => $id,
					];

					$result = PaySystem\Manager::update($id, $fields);
					if ($result->isSuccess())
					{
						$service = PaySystem\Manager::getObjectById($id);
						$applyRestrictionsResult = Restrictions\Manager::setupDefaultRestrictions($service);
						if (!$applyRestrictionsResult->isSuccess())
						{
							$this->errorCollection->add([new Error(Loc::getMessage('SP_AJAX_SAVE_PAYSYSTEM_ERROR_DEFAULT_RSRT_SETUP_MSGVER_1'))]);

							$this->errorCollection->add($applyRestrictionsResult->getErrors());
						}
					}
					else
					{
						$this->errorCollection->add($result->getErrors());
					}

					if ($isBusinessValue)
					{
						$this->saveBusinessValue($handler, $id, true);
					}
				}
			}
			else
			{
				$this->errorCollection->add($result->getErrors());
			}
		}

		if ($id > 0 && $this->errorCollection->isEmpty())
		{
			$service = PaySystem\Manager::getObjectById($id);
			if ($service && $service->isSupportPrintCheck())
			{
				$this->setCashbox($service);
			}
		}

		return [
			'ID' => $id,
		];
	}

	/**
	 * @param $handler
	 * @param $paySystemId
	 * @param $isNewSystem
	 * @throws \Bitrix\Main\SystemException
	 */
	private function saveBusinessValue($handler, $paySystemId, $isNewSystem)
	{
		$data = PaySystem\Manager::getHandlerDescription($handler);
		if ($isNewSystem)
		{
			BusinessValue::addConsumer('PAYSYSTEM_NEW', $data);
		}
		else
		{
			BusinessValue::changeConsumer('PAYSYSTEM_'.$paySystemId, $data);
		}

		$businessValueControl = new Admin\BusinessValueControl('PAYSYSTEM');
		if ($businessValueControl->setMapFromPost())
		{
			if ($isNewSystem)
			{
				$businessValueControl->changeConsumerKey('PAYSYSTEM_NEW', 'PAYSYSTEM_'.$paySystemId);
			}

			if (!$businessValueControl->saveMap())
			{
				$this->errorCollection->add([new Error('')]);
			}
		}
		else
		{
			$this->errorCollection->add([new Error('')]);
		}
	}

	private function setCashbox(PaySystem\Service $service): void
	{
		$cashboxSettings = $this->getCashboxSettingsFromRequest();
		if (!$this->errorCollection->isEmpty())
		{
			return;
		}

		$kkmId = $cashboxSettings['CASHBOX_DATA']['KKM_ID'] ?? '';

		/** @var Cashbox\CashboxPaySystem $cashboxClass */
		$cashboxClass = $service->getCashboxClass();

		if (!$kkmId)
		{
			$supportedKkmModels = $cashboxClass::getKkmValue($service);
			if ($supportedKkmModels)
			{
				$kkmId = current($supportedKkmModels);
			}
		}

		if ($this->request->get('CAN_PRINT_CHECK_SELF') === 'Y')
		{
			if (!$kkmId)
			{
				$handlerDescription = $service->getHandlerDescription();
				$paySystemCodeName = $handlerDescription['CODES'][$cashboxClass::getPaySystemCodeForKkm()]['NAME'] ?? '';
				if ($paySystemCodeName)
				{
					$this->errorCollection->add(
						[
							new Error(Loc::getMessage('SP_AJAX_SAVE_PAYSYSTEM_CASHBOX_ERROR_CREATE_CASHBOX')),
							new Error(Loc::getMessage(
								'SP_AJAX_SAVE_PAYSYSTEM_CASHBOX_ERROR_KEY_IS_EMPTY',
								[
									'#CODE_NAME#' => $paySystemCodeName,
								]
							)),
						]
					);
				}
				else
				{
					$this->errorCollection->add([new Error(Loc::getMessage('SP_AJAX_SAVE_PAYSYSTEM_CASHBOX_ERROR_CREATE_CASHBOX'))]);
				}

				return;
			}

			$fields = [
				'NAME' => $cashboxClass::getName(),
				'HANDLER' => $cashboxClass,
				'EMAIL' => $cashboxSettings['CASHBOX_DATA']['EMAIL'] ?? '',
				'NUMBER_KKM' => '',
				'KKM_ID' => $kkmId,
				'USE_OFFLINE' => 'N',
				'ENABLED' => 'Y',
				'ACTIVE' => 'Y',
				'SORT' => 100,
				'SETTINGS' => $cashboxSettings['CASHBOX_SETTINGS'],
			];

			$handlerList = Cashbox\Cashbox::getHandlerList();
			if (isset($handlerList[$fields['HANDLER']]))
			{
				$cashboxObject = Cashbox\Cashbox::create($fields);
				if ($cashboxObject)
				{
					$result = $cashboxObject->validate();
					if ($result->isSuccess())
					{
						$cashbox = Cashbox\Manager::getList([
							'filter' => [
								'=HANDLER' => $cashboxClass,
								'=KKM_ID' => $kkmId,
							],
						])->fetch();
						$cashboxId = $cashbox['ID'] ?? null;
						if ($cashboxId)
						{
							$result = Cashbox\Manager::update($cashboxId, $fields);
							if ($result->isSuccess())
							{
								$cashboxObject = Cashbox\Manager::getObjectById($cashboxId);
								AddEventToStatFile(
									'salescenter',
									'updateCashbox',
									$cashboxId,
									$cashboxObject::getCode()
								);
							}
						}
						else
						{
							$result = Cashbox\Manager::add($fields);
							if ($result->isSuccess())
							{
								$cashboxId = $result->getId();
								$cashboxObject = Cashbox\Manager::getObjectById($cashboxId);
								AddEventToStatFile(
									'salescenter',
									'addCashbox',
									$cashboxId,
									$cashboxObject::getCode()
								);
							}
						}
					}
					else
					{
						$this->errorCollection->add($result->getErrors());
					}
				}
				else
				{
					$this->errorCollection->add([
						new Error(Loc::getMessage('SP_AJAX_SAVE_PAYSYSTEM_CASHBOX_ERROR_CREATE_CASHBOX')),
					]);
				}
			}
			else
			{
				$this->errorCollection->add([
					new Error(Loc::getMessage('SP_AJAX_SAVE_PAYSYSTEM_CASHBOX_ERROR_NO_HANDLER_EXIST')),
				]);
			}
		}
		else
		{
			$onDisabledFiscalizationResult = PaySystem\Cashbox\EventHandler::onDisabledFiscalization($service, $kkmId);
			if (!$onDisabledFiscalizationResult->isSuccess())
			{
				$this->errorCollection->add($onDisabledFiscalizationResult->getErrors());
			}
		}
	}

	private function getCashboxSettingsFromRequest(): array
	{
		$result = [
			'CASHBOX_DATA' => [],
			'CASHBOX_SETTINGS' => [],
		];

		$cashboxData = $this->request->get('CASHBOX');
		$cashboxSettings = $this->request->get('SETTINGS');

		try
		{
			if ($cashboxData)
			{
				$result['CASHBOX_DATA'] = Web\Json::decode($cashboxData);
			}

			if ($cashboxSettings)
			{
				$result['CASHBOX_SETTINGS'] = Web\Json::decode($cashboxSettings);
			}
		}
		catch (Main\ArgumentException $ex)
		{
			$this->errorCollection->add([new Error(Loc::getMessage('SP_AJAX_SAVE_PAYSYSTEM_CASHBOX_ERROR_PARAMS'))]);
		}

		return $result;
	}

	/**
	 * @param string $type
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function logoutProfileAction(string $type)
	{
		if (!Loader::includeModule('seo'))
		{
			$this->errorCollection->add([new Error(Loc::getMessage('SP_AJAX_SEO_MODULE_ERROR'))]);
			return [];
		}

		$oauthService = Seo\Checkout\Services\Factory::createService($type);

		$webhookList = $this->getRegisteredWebhookList($oauthService);
		if ($webhookList)
		{
			$this->removeWebhooks($oauthService, $webhookList);
		}

		$this->removeAuth($type);

		return [];
	}

	/**
	 * @param string $type
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function removeAuth(string $type): void
	{
		$authAdapter = Seo\Checkout\Service::getAuthAdapter($type);
		$authAdapter->removeAuth();
		Option::set('sale', 'YANDEX_CHECKOUT_OAUTH', false);
	}

	/**
	 * @param Seo\Checkout\Services\AccountYandex|Seo\Checkout\Services\AccountYookassa $oauthService
	 * @return array
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getRegisteredWebhookList($oauthService): array
	{
		$webhookListResult = $oauthService->getWebhookList();
		if ($webhookListResult->isSuccess())
		{
			$webhookList = $webhookListResult->getData();
		}

		if (isset($webhookList['items']) && !empty($webhookList['items']) && is_array($webhookList['items']))
		{
			return $webhookList;
		}

		return [];
	}

	/**
	 * @param Seo\Checkout\Services\AccountYandex|Seo\Checkout\Services\AccountYookassa $oauthService
	 * @param $webhookList
	 * @throws \Bitrix\Main\SystemException
	 */
	private function removeWebhooks($oauthService, $webhookList): void
	{
		foreach ($webhookList['items'] as $webhookItem)
		{
			$oauthService->removeWebhook($webhookItem['id']);
		}

		Option::delete('sale', ['name' => 'YANDEX_CHECKOUT_OAUTH_WEBHOOK_REGISTER']);
	}

	/**
	 * @param string $type
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getProfileStatusAction(string $type)
	{
		if (!Loader::includeModule('seo'))
		{
			$this->errorCollection->add([new Error(Loc::getMessage('SP_AJAX_SEO_MODULE_ERROR'))]);
			return [];
		}

		$result = [
			'hasAuth' => false,
			'profile' => [],
		];

		$authAdapter = Seo\Checkout\Service::getAuthAdapter($type);
		$hasAuth = $authAdapter->hasAuth();
		if ($hasAuth)
		{
			Option::set('sale', 'YANDEX_CHECKOUT_OAUTH', true);

			$oauthService = Seo\Checkout\Services\Factory::createService($authAdapter->getType());

			$this->registerWebhooks($oauthService);
			if ($this->errorCollection->isEmpty())
			{
				$result = [
					'hasAuth' => true,
					'profile' => $oauthService->getProfile(),
				];
			}
			else
			{
				$this->logoutProfileAction($authAdapter->getType());
			}
		}

		return $result;
	}

	/**
	 * @param Seo\Checkout\Services\AccountYandex|Seo\Checkout\Services\AccountYookassa $oauthService
	 * @return array
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function registerWebhooks($oauthService): array
	{
		if (!Loader::includeModule('seo'))
		{
			$this->errorCollection->add([new Error(Loc::getMessage('SP_AJAX_SEO_MODULE_ERROR'))]);
			return [];
		}

		$registerPaymentSucceededResult = $oauthService->registerPaymentSucceededWebhook();
		$registerPaymentCanceledWebhookResult = $oauthService->registerPaymentCanceledWebhook();
		if ($registerPaymentSucceededResult->isSuccess() && $registerPaymentCanceledWebhookResult->isSuccess())
		{
			Option::set('sale', 'YANDEX_CHECKOUT_OAUTH_WEBHOOK_REGISTER', true);
		}

		if (!$registerPaymentSucceededResult->isSuccess())
		{
			$this->errorCollection->add($registerPaymentSucceededResult->getErrors());
		}
		elseif (!$registerPaymentCanceledWebhookResult->isSuccess())
		{
			$this->errorCollection->add($registerPaymentCanceledWebhookResult->getErrors());
		}

		return [];
	}

	/**
	 * @param $paySystemId
	 * @throws Exception
	 */
	public function deletePaySystemAction($paySystemId)
	{
		PaySystem\Manager::delete($paySystemId);
	}
}