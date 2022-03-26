<?php

namespace Bitrix\Crm\Controller\Ads\LeadAds;

use Bitrix\Main;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Seo\LeadAds;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Seo\Retargeting\IMultiClientService;

abstract class AbstractController extends Main\Engine\Controller
{
	public const WRITE_ACTIONS = [];

	protected function isWriteAction(Main\Engine\Action $action): bool
	{
		return in_array($action->getName(), static::WRITE_ACTIONS, true);
	}

	/**
	 * @param bool $writeMode
	 *
	 * @return bool
	 * @throws Main\AccessDeniedException
	 */
	protected function checkPermissions(bool $writeMode): bool
	{
		$auth = new \CCrmPerms($this->getCurrentUser()->getId());

		if($auth->havePerm('WEBFORM', BX_CRM_PERM_NONE, $writeMode ? 'WRITE' : 'READ'))
		{
			throw new Main\AccessDeniedException();
		}

		return true;
	}

	protected function getService($proxyId = null): ?LeadAds\Service
	{
		$serviceLocator = ServiceLocator::getInstance();
		if (!Main\Loader::includeModule('seo') || !$serviceLocator->has("seo.leadads.service"))
		{
			return null;
		}

		/**@var LeadAds\Service $service*/
		$service = $serviceLocator->get("seo.leadads.service");

		if ($proxyId && $service instanceof IMultiClientService && $service::canUseMultipleClients())
		{
			$service->setClientId($proxyId);
		}

		return $service;
	}

	/**
	 * @throws Main\AccessDeniedException|Main\LoaderException
	 */
	public function processBeforeAction(Main\Engine\Action $action): bool
	{
		return parent::processBeforeAction($action) && $this->checkPermissions($this->isWriteAction($action));
	}

	/**
	 * Create error response
	 * @return AjaxJson
	 */
	public function errorResponse() : AjaxJson
	{
		return AjaxJson::createError($this->errorCollection);
	}

	/**
	 * Create success response
	 * @param mixed|null $data
	 * @return AjaxJson
	 */
	public function successResponse($data = null) : AjaxJson
	{
		return AjaxJson::createSuccess($data);
	}
}