<?php

namespace Bitrix\Crm\Controller\Ads\LeadAds;

use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Seo\Retargeting;
use Bitrix\Seo\LeadAds\Service;

class Account extends AbstractController
{
	/**
	 * @param string $type
	 * @param string|int|null $proxyId
	 *
	 * @return AjaxJson
	 */
	public function getProxyClientListAction(string $type, $proxyId = null): AjaxJson
	{
		$errorCollection = new ErrorCollection;
		if (!$service = $this->getService($proxyId))
		{
			$errorCollection[] = new Error("Service not available.");
			return AjaxJson::createError($errorCollection);
		}
		$multipleSupport = $service instanceof Retargeting\IMultiClientService && $service::canUseMultipleClients();

		return $multipleSupport
			? AjaxJson::createSuccess(
					array("clients" => $service::getAuthAdapter($type)->getClientList())
			)
			: AjaxJson::createSuccess(
					array("client" => $service::getAuthAdapter($type)->getClientId())
			)
		;
	}

	/**
	 * @param string $type
	 * @param string|int|null $proxyId
	 *
	 * @return AjaxJson
	 */
	public function getProfileAction(string $type, $proxyId = null): AjaxJson
	{
		$errorCollection = new ErrorCollection;

		if (!$service = $this->getService($proxyId))
		{
			$errorCollection[] = new Error("Service not available.");

			return AjaxJson::createError($errorCollection);
		}

		if (!$account = $service->getAccount($type))
		{
			$errorCollection[] = new Error("Unknown type: {$type} .");

			return AjaxJson::createError($errorCollection);
		}

		if (empty($response = $account->getProfileCached()))
		{
			$errorCollection[] = new Error("External server error.");

			return AjaxJson::createError($errorCollection);
		}

		return AjaxJson::createSuccess(
			[
				"profile" => [
					'id' => $response['ID'],
					'name' => $response['NAME'],
					'link' => $response['LINK'],
					'picture' => $response['PICTURE'],
				]
			]

		);
	}

	/**
	 * @param string $type
	 *
	 * @return AjaxJson
	 */
	public function getProfileListAction(string $type): AjaxJson
	{
		$errorCollection = new ErrorCollection;

		if (!$service = $this->getService())
		{
			$errorCollection[] = new Error("Service not available.");

			return AjaxJson::createError($errorCollection);
		}

		if (!$service instanceof Retargeting\IMultiClientService || $service::canUseMultipleClients())
		{
			return $this->getProfileAction($type);
		}

		$service = $this->getService();
		$closure = static function(array $item) use ($type,$service) {
			/**@var $service Retargeting\IMultiClientService|Service*/
			if (!$service = clone $service)
			{
				return null;
			}

			$service->setClientId($item['proxy_client_id']);

			if (!$account = $service->getAccount($type))
			{
				return null;
			}

			$account->getRequest()->setAuthAdapter(
				$authAdapter = Retargeting\AuthAdapter::create($type,$service)
			);

			($profile = $account->getProfileCached()->getData())
				? $profile['CLIENT_ID'] = $item['proxy_client_id']
				: $authAdapter->removeAuth();

			return $profile?: null;
		};

		$profiles = array_filter(
			array_map($closure, $service::getAuthAdapter($type)->getClientList())
		);

		return AjaxJson::createSuccess(
			array(
				"profiles" => array_values($profiles)
			)
		);


	}

	/**
	 * @param string $type
	 * @param string|int|null $proxyId
	 *
	 * @return AjaxJson
	 */
	public function getAccountsAction(string $type, $proxyId = null): AjaxJson
	{
		$errorCollection = new ErrorCollection;
		if (!$service = $this->getService($proxyId))
		{
			$errorCollection[] = new Error("Service not available.");
			return AjaxJson::createError($errorCollection);
		}

		if (!$account = $service->getAccount($type))
		{
			$errorCollection[] = new Error("Unknown type: {$type}.");
			return AjaxJson::createError($errorCollection);
		}

		if (!($list = $account->getList()) || !$list->isSuccess())
		{
			$errorCollection[] = new Error("External server error.");
			return AjaxJson::createError($errorCollection);
		}

		return AjaxJson::createSuccess(
			array(
				"accounts" => $list->getData()
			)
		);
	}

	public function loginCompletionAction(string $type, $proxyId = null): AjaxJson
	{
		$errorCollection = new ErrorCollection();
		if (!$service = $this->getService($proxyId))
		{
			$errorCollection[] = new Error("Service not available.");
			return AjaxJson::createError($errorCollection);
		}

		if (!$account = $service->getAccount($type))
		{
			$errorCollection[] = new Error("Unknown type: {$type}.");
			return AjaxJson::createError($errorCollection);
		}

		if (!($loginCompletionResult = $account->loginCompletion()) || !$loginCompletionResult->isSuccess())
		{
			$errorCollection[] = new Error("External server error.");
			return AjaxJson::createError($errorCollection);
		}

		return AjaxJson::createSuccess(
			[
				"data" => $loginCompletionResult->getData()
			]
		);
		
	}
}
