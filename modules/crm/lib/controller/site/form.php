<?php
namespace Bitrix\Crm\Controller\Site;

use Bitrix\Main;
use Bitrix\Crm\WebForm;

class Form extends Main\Engine\Controller
{
	public function configureActions()
	{
		return [
			'fill' => [
				'prefilters' => [
					new Main\Engine\ActionFilter\HttpMethod(
						[
							Main\Engine\ActionFilter\HttpMethod::METHOD_POST,
						]
					),
					new Main\Engine\ActionFilter\Csrf(false),
					new Main\Engine\ActionFilter\CloseSession(),
				]
			],
		];
	}

	public function fillAction()
	{
		try
		{
			$result = User::getJsonResponse([
				'result' => WebForm\Embed\Rest::fillForm(Main\Context::getCurrent()->getRequest()->toArray())
			]);
		}
		catch (\Exception $exception)
		{
			$result = User::getJsonResponse([
				'error' => true,
				'error_description' => $exception->getMessage(),
				'result' => []
			]);
		}

		return $result;
	}
}
