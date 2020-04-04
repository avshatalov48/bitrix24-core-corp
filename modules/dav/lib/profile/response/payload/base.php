<?php

namespace Bitrix\Dav\Profile\Response\Payload;

use Bitrix\Dav\Profile\Response\Payload\Dictionaries\CalDav;
use Bitrix\Dav\Profile\Response\Payload\Dictionaries\CardDav;
use Bitrix\Dav\Profile\Response\Payload\Dictionaries\Decorator;
use Bitrix\Dav\TokensTable;
use Bitrix\Main\HttpRequest;
use Bitrix\Dav\Profile\Response\Base as ResponseBase;
use Bitrix\Main\IO;
use Bitrix\Main\Context;

/**
 * Class Base
 * @package Bitrix\Dav\Profile\Response\Payload
 */
class Base extends ResponseBase
{
	protected static $allowedRequestParams = array('resources', 'access_token');
	protected static $allowedResourceTypes = array('carddav', 'caldav', 'all');
	private $resources = array();
	private $accessToken = '';

	/**
	 * Base constructor.
	 * @param HttpRequest $request Request Object.
	 */
	public function __construct(HttpRequest $request)
	{
		$params = $request->get('params');
		if (!empty($params['access_token']))
		{
			$this->setAccessToken($params['access_token']);
			if ($this->isAccess())
			{
				$this->collectResourcesByParams($params);

				if ($this->errors)
				{
					$this->setErrorHeaderContent();
					$this->setErrorBodyContent();
				}
				else
				{
					$this->setPayloadHeaderContent();
					$this->setPayloadBodyContent();
				}
			}
			else
			{
				$this->errors[] = 'Has not access with this access token';
				$this->setAccessDeniedHeaderContent();
				$this->setErrorBodyContent();
			}
		}
		else
		{
			$this->errors[] = 'params[access_token] is required';;
			$this->setErrorHeaderContent();
			$this->setErrorBodyContent();
		}
	}

	/**
	 * @return bool Is user with access token has access.
	 */
	public function isAccess()
	{
		return TokensTable::isTokenValid($this->getAccessToken());
	}

	/**
	 * @return string Access token of current object.
	 */
	public function getAccessToken()
	{
		return $this->accessToken;
	}

	/**
	 * @param string $accessToken Set property accessToken of this object.
	 * @return void
	 */
	public function setAccessToken($accessToken)
	{
		$this->accessToken = $accessToken;
	}


	/**
	 *  Set body property, construct with template.
	 *  @return void
	 */
	private function setPayloadBodyContent()
	{
		$dictionaries = new Decorator($this->resources, $this->accessToken);
		$params['dicts'] = $dictionaries->prepareBodyContent();
		$params['profileIdentifier'] = $dictionaries->getProfileIdentifier();
		$params['host'] = Context::getCurrent()->getServer()->getHttpHost();;
		$templatePath = IO\Path::getDirectory(__DIR__ . '/templates/') . '/base.mobileconfig';
		$this->setBody(static::render($templatePath, $params));
	}

	/**
	 *  Set header property, for downloadable xml resource.
	 *  @return void
	 */
	private function setPayloadHeaderContent()
	{
		$this->setHeader('Content-Disposition: attachment; filename=profile.mobileconfig');
		$this->setHeader('Content-Type: application/xml');
	}

	/**
	 *  Set header property, for not found resource(404 Not Found).
	 *  @return void
	 */
	private function setErrorHeaderContent()
	{
		$this->setHeader('HTTP/1.0 404 Not Found');
		$this->setHeader('Content-Type: application/json');
	}

	/**
	 *  Set header property, for not access (403 Forbidden).
	 *  @return void
	 */
	private function setAccessDeniedHeaderContent()
	{
		$this->setHeader('HTTP/1.0 403 Forbidden');
		$this->setHeader('Content-Type: application/json');
	}

	/**
	 * @param array $params Parameters from request.
	 * @return void
	 */
	private function collectResourcesByParams($params)
	{
		$errors = $this->collectParamsErrors($params);
		if (!$errors)
		{
			$resourceKeyList = explode(',', $params['resources']);
			$this->resources += $this->collectResources($resourceKeyList);

		}
		else
		{
			$this->errors += $errors;
		}
	}

	/**
	 * @param array $params Parameters from request.
	 * @return array
	 */
	private function collectParamsErrors($params)
	{
		$errors = array();
		foreach (static::$allowedRequestParams as $requiredKey)
		{
			if (!isset($params[$requiredKey]))
			{
				$errors[] = 'params[' . $requiredKey . '] is required';
			}
		}

		return $errors;
	}

	/**
	 * @param array $resourceKeyList Resource names, by this resource will form payload.
	 * @return array
	 */
	private function collectResources($resourceKeyList)
	{
		$resources = array();
		$errors = $this->getResourceNameErrors($resourceKeyList);
		if (!$errors)
		{
			foreach ($resourceKeyList as $key)
			{
				switch ($key)
				{
					case 'carddav':
						$resources[] = new CardDav();
						break;
					case 'caldav':
						$resources[] = new CalDav();
						break;
					default:
						$resources[] = new CardDav();
						$resources[] = new CalDav();
				}
			}
		}
		else
		{
			$this->errors += $errors;
		}

		return $resources;
	}


	/**
	 * @param array $resourceNamesList Resource names, by this resource will form payload.
	 * @return array
	 */
	private function getResourceNameErrors($resourceNamesList)
	{
		$errors = array();
		foreach ($resourceNamesList as $resourceName)
		{
			if (!in_array($resourceName, static::$allowedResourceTypes))
			{
				$errors[] = !empty($resourceName) ? $resourceName . ' is not allowed' : 'Require resource type for generate payload';
			}
		}
		return $errors;
	}
}