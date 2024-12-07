<?php

namespace Bitrix\Sign\Rest\B2e;

use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\AuthTypeException;
use Bitrix\Rest\Oauth\Auth as OauthAuth;
use Bitrix\Rest\RestException;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Item\Api\ExternalSignProvider\FieldsRequest;
use Bitrix\Sign\Service\Container;
use CRestServer;
use IRestService;

Loader::includeModule('rest');

final class Provider extends IRestService
{

	private const LIMIT_DEFAULT = 20;
	private const LIMIT_MAX = 1000;

	public static function onRestServiceBuildDescription(): array
	{
		return [
			'sign.b2e' => [
				'sign.b2e.provider.add' => ['callback' => [self::class, 'add'], 'options' => []],
				'sign.b2e.provider.update' => ['callback' => [self::class, 'update'], 'options' => []],
				'sign.b2e.provider.delete' => ['callback' => [self::class, 'delete'], 'options' => []],
				'sign.b2e.provider.tail' => ['callback' => [self::class, 'list'], 'options' => []],
			],
		];
	}

	/**
	 * @param array $query Input parameters ($_GET, $_POST).
	 * @param int $start (int)$query['start']
	 * @param CRestServer $restServer REST server.
	 *
	 * @throws RestException
	 */
	public static function add(array $query, $start, CRestServer $restServer): int|array
	{
		self::checkAuth($restServer);

		$service = Container::instance()->getExternalSignProviderService();

		return self::formatResult($service->add(
			self::getFieldRequest($query)
		));
	}

	/**
	 * @param array $query Input parameters ($_GET, $_POST).
	 * @param int $start (int)$query['start']
	 * @param CRestServer $restServer REST server.
	 *
	 * @throws RestException
	 */
	public static function update(array $query, $start, CRestServer $restServer): bool|array
	{
		self::checkAuth($restServer);

		$service = Container::instance()->getExternalSignProviderService();

		$id = filter_var($query['id'] ?? 0, FILTER_VALIDATE_INT, [
			'options' => [
				'min_range' => 0,
				'default' => 0,
			],
		]);


		return self::formatResult($service->edit(
			$id,
			self::getFieldRequest($query)
		));
	}

	/**
	 * @param array $query Input parameters ($_GET, $_POST).
	 * @param int $start (int)$query['start']
	 * @param CRestServer $restServer REST server.
	 *
	 * @throws RestException
	 */
	public static function delete(array $query, $start, CRestServer $restServer): bool|array
	{
		self::checkAuth($restServer);

		$service = Container::instance()->getExternalSignProviderService();

		$id = filter_var($query['id'] ?? 0, FILTER_VALIDATE_INT, [
			'options' => [
				'min_range' => 0,
				'default' => 0,
			],
		]);

		return self::formatResult($service->delete($id));
	}

	/**
	 * @param array $query Input parameters ($_GET, $_POST).
	 * @param int $start (int)$query['start']
	 * @param CRestServer $restServer REST server.
	 *
	 * @throws AccessException
	 * @throws RestException
	 */
	public static function list(array $query, $start, CRestServer $restServer): array
	{
		self::checkAuth($restServer);

		$service = Container::instance()->getExternalSignProviderService();

		$limit = filter_var($query['limit'] ?? self::LIMIT_DEFAULT, FILTER_VALIDATE_INT, [
			'options' => [
				'min_range' => 1,
				'max_range' => self::LIMIT_MAX,
				'default' => self::LIMIT_DEFAULT,
			],
		]);
		$offset = filter_var($query['offset'] ?? 0, FILTER_VALIDATE_INT, [
			'options' => [
				'min_range' => 0,
				'default' => 0,
			],
		]);

		return self::formatResult($service->list($limit, $offset));
	}

	private static function formatResult(Result $result): bool|int|array {
		if($result->isSuccess())
		{
			return $result->getData()['result'];
		}
		[$error] = $result->getErrors();
		return ['error' => $error->getCustomData()['code'] ?? 0, 'error_description'=>$error->getMessage()];
	}

	private static function getFieldRequest(array $query): FieldsRequest {
		$fields = $query['fields'] ?? [];
		$title = $fields['title'] ?? null;
		$description = $fields['description'] ?? null;
		$iconUri = $fields['iconUri'] ?? null;
		$companyRegUri = $fields['companyRegUri'] ?? null;
		$documentSignUri = $fields['documentSignUri'] ?? null;
		$publicKeyUri = $fields['publicKeyUri'] ?? null;

		return new FieldsRequest(
				title:           $title,
				description:     $description,
				iconUri:         $iconUri,
				companyRegUri:   $companyRegUri,
				documentSignUri: $documentSignUri,
				publicKeyUri:    $publicKeyUri
		);
	}

	/**
	 * @throws AccessException
	 */
	private static function checkAuth(CRestServer $restServer): void
	{
		global $USER;

		if (!$USER->isAuthorized())
		{
			throw new AccessException("User authorization required");
		}

		if ($restServer->getAuthType() !== OauthAuth::AUTH_TYPE)
		{
			throw new AuthTypeException("Application context required");
		}

		if (!Storage::instance()->isB2eAvailable())
		{
			throw new AccessException('Access denied');
		}
	}
}
