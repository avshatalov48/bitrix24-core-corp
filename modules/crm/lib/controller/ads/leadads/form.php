<?php

namespace Bitrix\Crm\Controller\Ads\LeadAds;

use Bitrix\Main\Error;
use Bitrix\Main\Engine\Response\AjaxJson;

class Form extends AbstractController
{
	/**
	 * @param string $type
	 * @param string|int $accountId
	 * @param string|int|null $proxyId
	 *
	 * @return AjaxJson
	 */
	public function listAction(string $type, $accountId, $proxyId = null): AjaxJson
	{
		if (!$service = $this->getService($proxyId))
		{
			$this->addError(
				new Error("Service not available.")
			);

			return $this->errorResponse();
		}

		if (!$form = $service->getForm($type))
		{
			$this->addError(
				new Error("Wrong type {$type}.")
			);

			return $this->errorResponse();
		}

		if ((int)$accountId !== 0)
		{
			$form->setAccountId($accountId);
		}
		$formListResponse = $form->getList();

		if (!$formListResponse->isSuccess())
		{
			$this->addErrors(
				$formListResponse->getErrors()
			);

			return $this->errorResponse();
		}

		/**@var Form[] $result*/
		for($result = [];$form = $formListResponse->fetch();)
		{
			if ($form->isActive())
			{
				$result[] = $form;
			}
		}


		return $this->successResponse([
			"forms" => $result
		]);

	}

	/**
	 * @param string $type
	 * @param string|int $accountId
	 * @param string|int $formId
	 * @param string|int|null $proxyId
	 *
	 * @return AjaxJson
	 */
	public function getAction(string $type, $accountId, $formId, $proxyId = null): AjaxJson
	{
		if (!$service = $this->getService($proxyId))
		{
			$this->addError(
				new Error("Service not available.")
			);

			return $this->errorResponse();
		}

		if (!$form = $service->getForm($type))
		{
			$this->addError(
				new Error("Wrong type {$type}.")
			);

			return $this->errorResponse();
		}

		if ((int)$accountId !== 0)
		{
			$form->setAccountId($accountId);
		}
		$formResponse = $form->getForm($formId);

		if (!$formResponse->isSuccess())
		{
			$this->addErrors(
				$formResponse->getErrors()
			);

			return $this->errorResponse();
		}


		return $this->successResponse([
			"form" => $formResponse->fetch()
		]);
	}
}
