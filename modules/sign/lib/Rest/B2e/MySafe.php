<?php

namespace Bitrix\Sign\Rest\B2e;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\Response\BFile;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\AuthTypeException;
use Bitrix\Rest\Oauth\Auth as OauthAuth;
use Bitrix\Rest\RestException;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Access\Service\RolePermissionService;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Controllers\V1\Document\B2eSignedFile;
use Bitrix\Sign\Operation\GetSignedB2eFileUrl;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\EntityFileCode;
use Bitrix\Sign\Type\EntityType;
use CCrmPerms;
use CRestServer;
use CRestUtil;
use IRestService;

Loader::includeModule('rest');

final class MySafe extends IRestService
{

	private const LIMIT_DEFAULT = 20;
	private const LIMIT_MAX = 1000;

	public static function onRestServiceBuildDescription(): array
	{
		return [
			'sign.b2e' => [
				'sign.b2e.mysafe.tail' => ['callback' => [self::class, 'getMySafe'], 'options' => []],
				'sign.b2e.personal.tail' => ['callback' => [self::class, 'getPersonal'], 'options' => []],
				CRestUtil::METHOD_DOWNLOAD => ['callback' => [self::class, 'downloadDocument'], 'options' => []],
			],

		];
	}

	/**
	 * @param array $query Input parameters ($_GET, $_POST).
	 * @param int $start (int)$query['start']
	 * @param CRestServer $restServer REST server.
	 *
	 * @return BFile
	 * @throws RestException
	 */
	public static function downloadDocument(array $query, $start, CRestServer $restServer): BFile
	{
		$controller = new B2eSignedFile();
		$controller->configureActions();

		try
		{
			$result = $controller->downloadAction(
				EntityType::MEMBER,
				$id = (int)($query['id'] ?? 0),
				(new Signer())->sign(EntityType::MEMBER . '' . $id, GetSignedB2eFileUrl::B2eFileSalt),
				(int)($query['fileCode'] ?? EntityFileCode::SIGNED),
			);
		}
		catch (\Exception $error)
		{
			throw new RestException($error->getMessage(), $error->getCode());
		}
		if (empty($result))
		{
			[$error] = $controller->getErrors();
			throw new RestException($error?->getMessage(), $error?->getCode());
		}
		else
		{
			return $result;
		}

	}

	/**
	 * @param array $query Input parameters ($_GET, $_POST).
	 * @param int $start (int)$query['start']
	 * @param CRestServer $restServer REST server.
	 *
	 * @return array
	 * @throws AccessException
	 * @throws RestException
	 */
	public static function getMySafe(array $query, $start, CRestServer $restServer): array
	{
		self::checkAuth($restServer);

		//check access to MySafe action
		$accessController = (new AccessController(CurrentUser::get()->getId()));
		if ($accessController->check(ActionDictionary::ACTION_B2E_MY_SAFE) !== true)
		{
			throw new AccessException('Access denied');
		}

		$memberRepository = Container::instance()->getMemberRepository();
		$documentRepository = Container::instance()->getDocumentRepository();
		$documentService = Container::instance()->getDocumentService();
		$memberService = Container::instance()->getMemberService();

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

		$memberCollection = $memberRepository->listB2eMembersWithResultFilesForMySafe(
			self::preparePermissionFilterForMySafe(),
			$limit,
			$offset
		);

		if ($memberCollection->isEmpty())
		{
			return [];
		}

		$documentCollection = $documentRepository->listByIds(
			array_unique(
				array_map(fn($m) => $m->documentId, $memberCollection->toArray())
			)
		);
		$documents = $documentCollection->getArrayByIds();

		$result = [];
		foreach ($memberCollection as $member)
		{
			$document = $documents[$member->documentId];
			$result[] = [
				'id' => $member->id,
				'title' => $documentService->getTitleWithAutoNumber($document),
				'create_date' => $document->dateCreate?->format(\DateTimeInterface::ATOM),
				'signed_date' => $member->dateSigned?->format(\DateTimeInterface::ATOM),
				'creator_id' => $document->createdById,
				'member_id' => $memberService->getUserIdForMember($member),
				'role' => $member->role,
				'file_url' => CRestUtil::getDownloadUrl(['id' => $member->id], $restServer),
			];
		}

		return $result;
	}

	/**
	 * @param array $query Input parameters ($_GET, $_POST).
	 * @param int $start (int)$query['start']
	 * @param CRestServer $restServer REST server.
	 *
	 * @return array
	 * @throws AccessException
	 */
	public static function getPersonal(array $query, $start, CRestServer $restServer): array
	{
		self::checkAuth($restServer);

		$memberRepository = Container::instance()->getMemberRepository();
		$documentRepository = Container::instance()->getDocumentRepository();
		$documentService = Container::instance()->getDocumentService();

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

		$memberCollection = $memberRepository->listSignersByUserIdIsDone(
			CurrentUser::get()->getId(),
			Query::filter(),
			$limit,
			$offset
		);

		if ($memberCollection->isEmpty())
		{
			return [];
		}

		$documentCollection = $documentRepository->listByIds(
			array_unique(
				array_map(fn($m) => $m->documentId, $memberCollection->toArray())
			)
		);
		$documents = $documentCollection->getArrayByIds();

		$result = [];
		foreach ($memberCollection as $member)
		{
			$document = $documents[$member->documentId];
			$result[] = [
				'id' => $member->id,
				'title' => $documentService->getTitleWithAutoNumber($document),
				'signed_date' => $member->dateSigned?->format(\DateTimeInterface::ATOM),
				'file_url' => CRestUtil::getDownloadUrl(['id' => $member->id], $restServer),
			];
		}

		return $result;
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

		if (!Storage::instance()->isB2eAvailable())
		{
			throw new AccessException('Access denied');
		}
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws RestException
	 */
	private static function preparePermissionFilterForMySafe(): ConditionTree
	{
		$filter = Query::filter();

		if (is_numeric(CurrentUser::get()->getId()) === false)
		{
			throw new AccessException('Access denied for user with malformed id');
		}

		$accessController = (new AccessController(CurrentUser::get()->getId()));
		$user = $accessController->getUser();

		if (CurrentUser::get()->isAdmin())
		{
			return $filter;
		}

		$permission = (new RolePermissionService())->getValueForPermission(
			$user->getRoles(),
			SignPermissionDictionary::SIGN_B2E_MY_SAFE_DOCUMENTS
		);

		switch ($permission)
		{
			case CCrmPerms::PERM_ALL:
			{
				break;
			}
			case CCrmPerms::PERM_SUBDEPARTMENT:
			case CCrmPerms::PERM_DEPARTMENT:
			{
				$filter->whereIn(
					'CREATED_BY_ID',
					$user->getUserDepartmentMembers(
						$permission === CCrmPerms::PERM_SUBDEPARTMENT
					)
				);
				break;
			}
			case CCrmPerms::PERM_SELF:
			{
				$filter->where('CREATED_BY_ID', '=', $user->getUserId());
				break;
			}
			case null:
			{
				$filter->where('CREATED_BY_ID', '=', null);
				break;
			}
		}

		return $filter;
	}
}
