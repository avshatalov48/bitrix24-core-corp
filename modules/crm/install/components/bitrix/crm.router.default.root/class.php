<?php

use Bitrix\Crm\Component\Base;
use Bitrix\Crm\Router\ResponseHelper;
use Bitrix\Main\IO\Path;
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
		ResponseHelper::showPageNotFound();
	}

	private function isRequestUriEqualsCrm(): bool
	{
		$siteDir = defined('SITE_DIR') ? SITE_DIR : '/';
		$crmPath = Path::combine($siteDir, $this->router->getDefaultRoot()) . '/';

		$requestUriPath = (new Uri($this->request->getRequestUri()))->getPath();

		return $crmPath === $requestUriPath;
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
