<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\IO;
use Bitrix\Main\Web;
use Bitrix\Main\Error;
use Bitrix\Main\Config\Option;
use Bitrix\Sale\Registry;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Internals;
use Bitrix\Sale\BusinessValue;
use Bitrix\Sale\Helpers\Admin;
use Bitrix\SalesCenter\Integration\SaleManager;

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

		if(!SaleManager::getInstance()->isFullAccess())
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

		$id = $this->request->get('ID');
		$handler = $this->request->get('ACTION_FILE');
		$psMode = $this->request->get('PS_MODE');
		$name = trim($this->request->get('NAME'));
		$description = trim($this->request->get('DESCRIPTION'));
		$active = $this->request->get('ACTIVE');
		$isCanPrintCheck = $this->request->get('CAN_PRINT_CHECK');
		$sort = $this->request->get('SORT');
		$xmlId = $this->request->get('XML_ID');
		$isCash = $this->request->get('IS_CASH');

		$path = PaySystem\Manager::getPathToHandlerFolder($handler);
		if ($path === null)
		{
			$this->errorCollection->add([new Error(Loc::getMessage('SP_AJAX_SAVE_PAYSYSTEM_ERROR_HANDLER_TYPE'))]);
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

		$fields = array(
			"NAME" => $name,
			"PSA_NAME" => $name,
			"ACTIVE" => ($active == 'Y') ? 'Y' : 'N',
			"CAN_PRINT_CHECK" => ($isCanPrintCheck == 'Y') ? 'Y' : 'N',
			"CODE" => '',
			"NEW_WINDOW" => 'N',
			"ALLOW_EDIT_PAYMENT" => 'Y',
			"IS_CASH" => isset($isCash) ? $isCash : 'N',
			"ENTITY_REGISTRY_TYPE" => Registry::REGISTRY_TYPE_ORDER,
			"SORT" => $sort,
			"ENCODING" => '',
			"DESCRIPTION" => $description,
			"ACTION_FILE" => $handler,
			'PS_MODE' => $psMode,
			'XML_ID' => $xmlId,
			'AUTO_CHANGE_1C' => 'N',
		);

		$file = $this->request->getFile('LOGOTIP');
		if ($file !== null && $file["error"] == 0)
		{
			/** @noinspection PhpUndefinedClassInspection */
			$imageFileError = \CFile::CheckImageFile($file);
			if ($imageFileError === null)
			{
				$fields['LOGOTIP'] = $file;
				$fields['LOGOTIP']['del'] = trim($this->request->get("LOGOTIP_del"));
				$fields['LOGOTIP']['MODULE_ID'] = "sale";
				/** @noinspection PhpUndefinedClassInspection */
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
					/** @noinspection PhpUndefinedClassInspection */
					$fields['LOGOTIP'] = \CFile::MakeFileArray($image);
					$fields['LOGOTIP']['MODULE_ID'] = "sale";
					/** @noinspection PhpUndefinedClassInspection */
					\CFile::SaveForDB($fields, 'LOGOTIP', 'sale/paysystem/logotip');
				}
			}

			if (!isset($fields['LOGOTIP']))
			{
				$image = '/bitrix/images/sale/sale_payments/'.$handler.'.png';
				if (IO\File::isFileExists($documentRoot.$image))
				{
					/** @noinspection PhpUndefinedClassInspection */
					$fields['LOGOTIP'] = \CFile::MakeFileArray($image);
					$fields['LOGOTIP']['MODULE_ID'] = "sale";
					/** @noinspection PhpUndefinedClassInspection */
					\CFile::SaveForDB($fields, 'LOGOTIP', 'sale/paysystem/logotip');
				}
			}
		}

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
		else
		{
			if (IO\File::isFileExists($documentRoot.$handler."/pre_payment.php"))
			{
				$fields["HAVE_PREPAY"] = "Y";
			}
			if (IO\File::isFileExists($documentRoot.$handler."/result.php"))
			{
				$fields["HAVE_RESULT"] = "Y";
			}
			if (IO\File::isFileExists($documentRoot.$handler."/action.php"))
			{
				$fields["HAVE_ACTION"] = "Y";
			}
			if (IO\File::isFileExists($documentRoot.$handler."/payment.php"))
			{
				$fields["HAVE_PAYMENT"] = "Y";
			}
			if (IO\File::isFileExists($documentRoot.$handler."/result_rec.php"))
			{
				$fields["HAVE_RESULT_RECEIVE"] = "Y";
			}
		}

		if ($this->getErrors())
		{
			return [];
		}

		$isBusinessValue = true;
		if (isset($_POST['PAYSYSTEMBizVal']))
		{
			$_POST['PAYSYSTEMBizVal'] = Bitrix\Main\Text\Encoding::convertEncoding($_POST['PAYSYSTEMBizVal'], LANG_CHARSET, 'UTF-8');

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
			$result = Internals\PaySystemActionTable::update($id, $fields);

			if (!$result->isSuccess())
			{
				$this->errorCollection->add($result->getErrors());
			}
			else
			{
				if ($isBusinessValue)
				{
					$this->saveBusinessValue($handler, $id, false);
				}
			}
		}
		else
		{
			$result = Internals\PaySystemActionTable::add($fields);
			if (!$result->isSuccess())
			{
				$this->errorCollection->add($result->getErrors());
			}
			else
			{
				$id = $result->getId();
				if ($isBusinessValue)
				{
					$this->saveBusinessValue($handler, $id, true);
				}
			}
		}

		return [
			'ID' => $id
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

	/**
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function logoutProfileAction()
	{
		if (!Loader::includeModule('seo'))
		{
			$this->errorCollection->add([new Error(Loc::getMessage('SP_AJAX_SEO_MODULE_ERROR'))]);
			return [];
		}

		$webhookList = $this->getRegisteredWebhookList();
		if ($webhookList)
		{
			$this->removeWebhooks($webhookList);
		}

		Bitrix\Seo\Checkout\Services\AccountYandex::removeAuth();
		Option::set('sale', 'YANDEX_CHECKOUT_OAUTH', false);

		return [];
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getRegisteredWebhookList()
	{
		$yandex = new Bitrix\Seo\Checkout\Services\AccountYandex();
		$yandex->setService(Bitrix\Seo\Checkout\Service::getInstance());
		$webhookListResult = $yandex->getWebhookList();
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
	 * @param $webhookList
	 * @throws \Bitrix\Main\SystemException
	 */
	private function removeWebhooks($webhookList)
	{
		$yandex = new Bitrix\Seo\Checkout\Services\AccountYandex();
		$yandex->setService(Bitrix\Seo\Checkout\Service::getInstance());

		foreach ($webhookList['items'] as $webhookItem)
		{
			$yandex->removeWebhook($webhookItem['id']);
		}
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getProfileStatusAction()
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

		$authAdapter = Bitrix\Seo\Checkout\Service::getAuthAdapter('yandex');
		$hasAuth = $authAdapter->hasAuth();
		if ($hasAuth)
		{
			Option::set('sale', 'YANDEX_CHECKOUT_OAUTH', true);
			$yandex = new Bitrix\Seo\Checkout\Services\AccountYandex();
			$yandex->setService(Bitrix\Seo\Checkout\Service::getInstance());

			$this->registerWebhooks();
			if ($this->errorCollection->isEmpty())
			{
				$result = [
					'hasAuth' => true,
					'profile' => $yandex->getProfile(),
				];
			}
			else
			{
				$this->logoutProfileAction();
			}
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function registerWebhooks()
	{
		if (!Loader::includeModule('seo'))
		{
			$this->errorCollection->add([new Error(Loc::getMessage('SP_AJAX_SEO_MODULE_ERROR'))]);
			return [];
		}

		$yandex = new Bitrix\Seo\Checkout\Services\AccountYandex();
		$yandex->setService(Bitrix\Seo\Checkout\Service::getInstance());

		$registerPaymentSucceededResult = $yandex->registerPaymentSucceededWebhook();
		if ($registerPaymentSucceededResult->isSuccess())
		{
			Option::set('sale', 'YANDEX_CHECKOUT_OAUTH_WEBHOOK_REGISTER', true);
		}
		else
		{
			$this->errorCollection->add($registerPaymentSucceededResult->getErrors());
		}

		return [];
	}

	/**
	 * @param $paySystemId
	 * @throws Exception
	 */
	public function deletePaySystemAction($paySystemId)
	{
		Internals\PaySystemActionTable::delete($paySystemId);
	}
}