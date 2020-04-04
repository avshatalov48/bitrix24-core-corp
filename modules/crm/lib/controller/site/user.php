<?php
namespace Bitrix\Crm\Controller\Site;

use Bitrix\Main;
use Bitrix\Crm\WebForm;

class User extends Main\Engine\Controller
{
	public function configureActions()
	{
		return [
			'get' => [
				'prefilters' => [
					new Main\Engine\ActionFilter\HttpMethod(
						[
							Main\Engine\ActionFilter\HttpMethod::METHOD_GET,
							Main\Engine\ActionFilter\HttpMethod::METHOD_POST,
						]
					),
					new Main\Engine\ActionFilter\Csrf(false),
					new Main\Engine\ActionFilter\CloseSession(),
				]
			],
		];
	}

	public function getAction()
	{
		try
		{
			$result = static::getJsonResponse([
				'result' => WebForm\Embed\Rest::getUser(Main\Context::getCurrent()->getRequest()->toArray())
			]);
		}
		catch (\Exception $exception)
		{
			$result = static::getJsonResponse([
				'error' => true,
				'error_description' => $exception->getMessage(),
				'result' => []
			]);
		}

		return $result;
	}

	public static function getJsonResponse(array $data = [])
	{
		$response = new Main\Engine\Response\Json($data);

		$origin = Main\Context::getCurrent()->getServer()->get('HTTP_ORIGIN');
		if ($origin)
		{
			$response->addHeader('Access-Control-Allow-Origin', $origin);
			$response->addHeader('Access-Control-Allow-Credentials', 'true');
		}

		return $response;
	}
}
