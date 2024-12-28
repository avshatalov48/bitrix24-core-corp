<?php

use Bitrix\Crm\Component\Base;
use Bitrix\Main\Web\Uri;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CrmRouterDefaultRoot extends Base
{
	public function executeComponent(): never
	{
		$this->init();
		$this->tryRedirectToConsistentUrlFromPartlyDefined();

		if ($this->isRequestUriEqualsCrm())
		{
			$this->redirectToFirstAvailableEntity();
		}

		$this->processPageNotFound();
	}

	private function redirectToFirstAvailableEntity(): never
	{
		\Bitrix\Intranet\Integration\Crm::getInstance()->redirectToFirstAvailableEntity();
	}

	private function processPageNotFound(): never
	{
		\Bitrix\Crm\Router\ResponseHelper::showPageNotFound();
	}

	private function isRequestUriEqualsCrm(): bool
	{
		$siteDir = defined('SITE_DIR') ? SITE_DIR : '';
		$crmPath =  (new Uri('/crm/'))->getPath();
		$crmPathWithSiteDir = (new Uri($siteDir . 'crm/'))->getPath();

		$requestUriPath = (new Uri($this->request->getRequestUri()))->getPath();

		return in_array($requestUriPath, [$crmPath, $crmPathWithSiteDir], true);
	}

	private function tryRedirectToConsistentUrlFromPartlyDefined(): void
	{
		$requestUri = $this->request->getRequestUri();
		$consistentUrl = $this->router->getConsistentUrlFromPartlyDefined($requestUri);
		if ($consistentUrl !== null)
		{
			LocalRedirect($consistentUrl->getUri());
		}
	}
}
