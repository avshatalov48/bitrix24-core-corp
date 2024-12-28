<?php

namespace Bitrix\Sign\Rest\B2e\HcmLink;

use Bitrix\Main\Loader;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\AuthTypeException;
use Bitrix\Rest\Oauth\Auth as OauthAuth;
use Bitrix\Rest\RestException;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Result\Service\Integration\HumanResources\HcmLinkSignedFileInfo;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Integration\HumanResources\EventType;
use CRestUtil;
use IRestService;
use \CRestServer;

use Bitrix\Main;

Loader::includeModule('rest');

final class SignedFile extends IRestService
{
	public const MODULE_ID = 'sign';
	public const SCOPE = 'sign.b2e';

	public static function onRestServiceBuildDescription(): array
	{
		if (!Container::instance()->getHcmLinkService()->isAvailable())
		{
			return [];
		}

		return [
			self::SCOPE => [
				self::SCOPE . '.hcmlink.document.get' => [
					'callback' => [self::class, 'getDocument'],
					'options' => [],
				],
				CRestUtil::EVENTS => [
					'OnSignHcmLinkB2eDocumentSigned' => [
						self::MODULE_ID,
						EventType::DOCUMENT_SIGNED->value,
						[self::class, 'onEvent'],
					],
				],
			],
		];
	}

	public static function onEvent(array $eventData): array
	{
		/* @var ?Main\Event $eventData */
		$eventData = ($eventData[0] ?? null)?->getParameters();

		return [
			'id' => $eventData['memberId'] ?? null,
			'company' => $eventData['company'] ?? null,
		];
	}

	public static function getDocument(array $query, $start, CRestServer $restServer): array
	{
		self::checkAuth($restServer);

		$memberId = (int)$query['id'] ?? 0;

		$result = Container::instance()->getHcmLinkSignedFileService()
			->getInfoByMemberId($memberId)
		;

		if (!$result instanceof HcmLinkSignedFileInfo)
		{
			$error = $result->getError();
			throw new RestException($error?->getMessage(), $error?->getCode());
		}

		return [
			'company' => $result->company,
			'employee' => $result->employee,
			'document' => [
				'date' => $result->documentDate->format(\DateTimeInterface::ATOM),
				'name' => $result->documentName,
				'fileUrl' => CRestUtil::getDownloadUrl(['id' => $memberId], $restServer),
				'fileName' => $result->fileName,
			],
		];
	}

	/**
	 * @param CRestServer $restServer
	 *
	 * @return void
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

		if (!Storage::instance()->isB2eAvailable() || !CRestUtil::isAdmin())
		{
			throw new AccessException('Access denied');
		}
	}
}
