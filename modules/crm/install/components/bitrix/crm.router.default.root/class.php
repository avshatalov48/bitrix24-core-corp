<?php

use Bitrix\Crm\Component\Base;
use Bitrix\Main\Application;
use Bitrix\Main\Composite\Engine;
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
		if (!defined('ERROR_404'))
		{
			define('ERROR_404', 'Y');
		}

		CHTTP::setStatus('404 Not Found');
		if ($this->getApplication()->RestartWorkarea())
		{
			if (!defined('BX_URLREWRITE'))
			{
				define('BX_URLREWRITE', true);
			}

			Engine::setEnable(false);

			global $APPLICATION;
			require Application::getDocumentRoot() . '/404.php';
		}

		die();
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
