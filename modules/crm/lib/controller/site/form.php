<?php
namespace Bitrix\Crm\Controller\Site;

use Bitrix\Main;
use Bitrix\Crm\WebForm;

class Form extends Main\Engine\Controller
{
	/**
	 * Configure actions.
	 *
	 * @return array
	 */
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
				]
			],
			'handleAnalytics' => [
				'prefilters' => [
					new Main\Engine\ActionFilter\HttpMethod(
						[
							Main\Engine\ActionFilter\HttpMethod::METHOD_POST,
						]
					),
					new Main\Engine\ActionFilter\Csrf(false),
				],
				'postfilters' => [
					new Main\Engine\ActionFilter\Cors(),
				]
			],
			'get' => [
				'prefilters' => [
					new Main\Engine\ActionFilter\HttpMethod(
						[
							Main\Engine\ActionFilter\HttpMethod::METHOD_GET,
							Main\Engine\ActionFilter\HttpMethod::METHOD_POST,
						]
					),
					new Main\Engine\ActionFilter\Csrf(false),
				]
			],
		];
	}

	/**
	 * Fill form action.
	 *
	 * @return Main\Engine\Response\Json
	 */
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

	/**
	 * Get form details action.
	 *
	 * @return Main\Engine\Response\Json
	 */
	public function getAction()
	{
		try
		{
			$result = User::getJsonResponse([
				'result' => WebForm\Embed\Rest::getForm(Main\Context::getCurrent()->getRequest()->toArray())
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

	/**
	 * Handle form analytics event.
	 *
	 * @param string $counter Counter Type.
	 * @param string $formId Form ID.
	 * @return Main\Engine\Response\Json
	 */
	public function handleAnalyticsAction(string $counter, string $formId): Main\Engine\Response\Json
	{
		try
		{
			$result = new Main\Engine\Response\Json([
				'error' => false
			]);

			switch($counter)
			{
				case 'view':
					WebForm\Form::incCounterView($formId);
					break;

				case 'start':
					WebForm\Form::incCounterStartFill($formId);
					break;
			}
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
