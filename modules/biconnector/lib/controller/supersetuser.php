<?php

namespace Bitrix\BIConnector\Controller;

use Bitrix\BIConnector\Integration\Superset\Integrator\ProxyIntegrator;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;

class SupersetUser extends Controller
{
	public function getAction()
	{
		$integrator = ProxyIntegrator::getInstance();
		$superset = new SupersetController($integrator);

		$credentials = $superset->getUserCredentials();
		if ($credentials !== null)
		{
			return [
				'user' => [
					'login' => $credentials->login,
					'password' => $credentials->password,
				]
			];
		}

		return [];
	}


	/**
	 * @inheritDoc
	 */
	public function configureActions()
	{
		$get = [
			'+prefilters' => [],
		];

		if (Loader::includeModule('intranet'))
		{
			$get['+prefilters'][] = new \Bitrix\Intranet\ActionFilter\IntranetUser();
		}

		return [
			'get' => $get,
		];
	}
}
