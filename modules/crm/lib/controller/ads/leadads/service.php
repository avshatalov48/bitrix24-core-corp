<?php

namespace Bitrix\Crm\Controller\Ads\LeadAds;

use Bitrix\Crm\Ads\Internals\AdsFormLinkTable;
use Bitrix\Main\Error;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Localization\Loc;

class Service extends AbstractController
{
	public const WRITE_ACTIONS = ["logout", "logoutGroup", "registerGroup"];

	/**
	 * Logout from group
	 * @param string $type
	 * @param string $groupId
	 * @return AjaxJson
	 */
	public function logoutGroupAction(string $type, string $groupId) : AjaxJson
	{
		if (!$service = $this->getService())
		{
			$this->addError(
				new Error("Service is not available.")
			);

			return $this->errorResponse();
		}

		if (!$service::unRegisterGroup($type,$groupId))
		{
			$this->addError(
				new Error("Can't unregister group")
			);

			return $this->errorResponse();
		}

		return $this->successResponse();
	}

	/**
	 * Register group action
	 * @param string $type
	 * @param string $group
	 * @return AjaxJson
	 */
	public function registerGroupAction(string $type, string $group) : AjaxJson
	{

		if (!$service = $this->getService())
		{
			$this->addError(
				new Error("Service is not available.")
			);

			return $this->errorResponse();
		}

		if (!$service::registerGroup($type,$group))
		{
			$this->addError(
				new Error("Can't register group")
			);

			return $this->errorResponse();
		}

		if (!$authAdapter = $service->getForm($type)->getGroupAuthAdapter())
		{
			$this->addError(
				new Error("Can't get auth adapter")
			);

			return $this->errorResponse();
		}

		return $this->successResponse([
			"authUrl" => $authAdapter->getAuthUrl(),
		]);

	}

	/**
	 * Logout from leadads service
	 * @param string $type
	 * @return AjaxJson
	 * @throws \Bitrix\Main\SystemException
	 */
	public function logoutAction(string $type): AjaxJson
	{
		if (!$service = $this->getService())
		{
			$this->addError(
				new Error("Service not available.")
			);

			return $this->errorResponse();
		}

		$account = $service->getAccount($type);
		$logoutResult = $account->logout();
		if ($logoutResult->isSuccess())
		{
			$this->unlinkWebForm($logoutResult->getData());
		}

		$groupAuthAdapter = $service->getGroupAuth($type);
		if ($groupAuthAdapter)
		{
			$groupAuthAdapter->removeAuth();
		}

		$service::getAuthAdapter($type)->removeAuth();

		return $this->successResponse();
	}

	public function unlinkWebForm(array $data): void
	{
		if (!empty($data))
		{
			$linksDb = AdsFormLinkTable::getList([
				'select' => ['ID'],
				'filter' => [
					'=ADS_FORM_ID' => $data['formIds']
				]
			]);

			while ($link = $linksDb->fetch())
			{
				AdsFormLinkTable::delete($link['ID']);
			}
		}
	}

	public function checkProfileAction($type)
	{
		if (!$service = $this->getService())
		{
			$this->addError(
				new Error("Service not available.")
			);

			return $this->errorResponse();
		}

		$account = $service->getAccount($type);
		if ($account->checkNewAuthInfo())
		{
			return $this->successResponse();
		}

		$this->addError(
			new Error("Profile not available")
		);

		return $this->errorResponse();
	}

	public function getAuthUrlAction($type): AjaxJson
	{
		if (!$service = $this->getService())
		{
			$this->addError(
				new Error("Service not available.")
			);

			return $this->errorResponse();
		}

		$authUrl = $service->getAuthUrl($type);
		return $this->successResponse(['authUrl' => $authUrl]);
	}
}
