<?php

namespace Bitrix\Sign\Service;

use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;
use Bitrix\Sign\Item\Api\Mobile\Confirmation\AcceptRequest;
use Bitrix\Sign\Item\Api\Mobile\Confirmation\PostponeRequest;
use Bitrix\Sign\Item\Api\Mobile\Signing\ExternalUrlRequest;
use Bitrix\Sign\Item\Api\Mobile\Signing\RefuseRequest;
use Bitrix\Sign\Item\Api\Mobile\Signing\ReviewRequest;
use Bitrix\Sign\Item\Api\Mobile\Signing\SignRequest;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Item\Mobile\Link;
use Bitrix\Sign\Main\Application;
use Bitrix\Sign\Operation\ChangeMemberStatus;
use Bitrix\Sign\Operation\SigningStop;
use Bitrix\Sign\Operation\SyncMemberStatus;
use Bitrix\Sign\Result\Service\ExternalSigningUrlResult;
use Bitrix\Sign\Service\Result\Mobile\LinkResult;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;
use Bitrix\Sign\Type\ProviderCode;
use Bitrix\SignMobile;
use Bitrix\Sign\Type;

class MobileService
{
	private bool $darkMode = false;
	private readonly Sign\DocumentService $documentService;
	private readonly Sign\MemberService $memberService;
	private readonly Sign\Document\ProviderCodeService $providerCodeService;
	private readonly Api\MobileService $mobileService;

	public function __construct()
	{
		$container = Container::instance();
		$this->documentService = $container->getDocumentService();
		$this->memberService = $container->getMemberService();
		$this->providerCodeService = $container->getProviderCodeService();
		$this->mobileService = $container->getApiMobileService();
	}

	public function setDarkMode(bool $isDark): self
	{
		$this->darkMode = $isDark;
		return $this;
	}

	public function getLinkForSigning(int $memberId): LinkResult
	{
		$member = $this->memberService->getById($memberId);

		if (!$member)
		{
			return (new LinkResult())->addError(new Error('not ready for signing'));
		}

		if ($member->status === MemberStatus::DONE)
		{
			return $this->getLinkForFinishedSigning($member);
		}

		$document = $this->documentService->getById($member->documentId);

		if (!$document)
		{
			return (new LinkResult())->addError(new Error('not ready for signing'));
		}

		$result = $this->providerCodeService->loadByDocument($document);
		if (!$result->isSuccess())
		{
			return (new LinkResult())->addErrors($result->getErrors());
		}

		if (
			$document->providerCode === ProviderCode::GOS_KEY
			&& !MemberStatus::isFinishForSigning($member->status)
		)
		{
			(new SyncMemberStatus($member, $document))->launch();
		}

		$result = $this->memberService->getLinkForSigning($member);

		if (!$result->isSuccess())
		{
			return (new LinkResult())->addErrors($result->getErrors());
		}

		$url = $this->getUrlForSigning($result);

		if (!$url)
		{
			return (new LinkResult())->addError(new Error('not ready for signing'));
		}

		$url = $this->addMobileUrlParams($url);

		$link = new Link(
			url: $url,
			documentTitle: $document->title,
			memberId: $member->id,
			role: $member->role,
			status: $member->status,
			documentStatus: $document->status,
			providerCode: $document->providerCode,
			readyForDownload: false,
			initiatedByType: $document->initiatedByType,
		);

		if (
			$document->providerCode === ProviderCode::GOS_KEY
			&& $member->status === MemberStatus::READY
			&& $member->role === Role::ASSIGNEE
			&& $this->memberService->countWaitingSigners($document->id) === 0
		)
		{
			$link->setGoskeyAssigneeAlmostDone();
		}

		return (new LinkResult())->setLink($link);
	}

	private function getLinkForFinishedSigning(Member $member): LinkResult
	{
		$memberService = $this->memberService;
		$result = $memberService->getLinkForSignedFile($member);

		// may be signed, but not ready for download
		$url = null;
		if ($result->isSuccess())
		{
			$url = $this->getUrlForSignedFile($result);
			$url = $this->addMobileUrlParams($url);
		}

		$document = $this->documentService->getById($member->documentId);

		if (!$document)
		{
			return (new LinkResult())->addError(new Error('not ready for signing'));
		}
		$result = $this->providerCodeService->loadByDocument($document);
		if (!$result->isSuccess())
		{
			return (new LinkResult())->addErrors($result->getErrors());
		}

		return (new LinkResult())->setLink(
			new Link(
				url: $url,
				documentTitle: $document->title,
				memberId: $member->id,
				role: $member->role,
				status: $member->status,
				documentStatus: $document->status,
				providerCode: $document->providerCode,
				readyForDownload: $url !== null,
				initiatedByType: $document->initiatedByType,
			),
		);
	}

	private function addMobileUrlParams(string $url): string
	{
		$uri = new Uri($url);
		$uri->addParams(['mobile' => 1]);
		$uri->addParams(['mobileDark' => $this->darkMode ? 1 : 0]);
		return $uri->getUri();
	}

	public function getNextSigningIfExists(int $userId): LinkResult
	{
		$member = Container::instance()
			->getSignMemberUserService()
			->getMemberForSigning($userId, [Role::SIGNER, Role::REVIEWER])
		;

		if ($member)
		{
			return $this->getLinkForSigning($member->id);
		}

		return new LinkResult();
	}

	public function sendSignConfirmationEvent(Member $member): Result
	{
		if (!\Bitrix\Main\Loader::includeModule('signmobile'))
		{
			return (new Result())->addError(new Error('signmobile load error'));
		}

		$result = Container::instance()
			->getMobileService()
			->getLinkForSigning($member->id)
		;

		if ($result->isSuccess() && $link = $result->getLink())
		{
			$userId = $this->memberService->getUserIdForMember($member);

			$service = SignMobile\Service\Container::instance()->getEventService();
			return $service->sendSignConfirmation($userId, $link);
		}

		return (new Result())->addError(new Error('signing error'));
	}

	public function acceptSigning(int $memberId): Result
	{
		$member = Container::instance()->getMemberService()->getById($memberId);
		$resultWithError = (new Result())->addError(new Error('sign error'));

		if (!$member)
		{
			return $resultWithError;
		}

		// only signers can sign on mobile
		if ($member->role !== Role::SIGNER)
		{
			return $resultWithError;
		}

		$document = Container::instance()->getDocumentService()->getById($member->documentId);

		if (!$document)
		{
			return $resultWithError;
		}

		$response = Container::instance()->getApiMobileService()
			->acceptSigning(
				new SignRequest(documentUid: $document->uid, memberUid: $member->uid),
			)
		;

		if ($response->isSuccess())
		{
			$updateResult = (new ChangeMemberStatus($member, $document, MemberStatus::DONE))->launch();

			if (!$updateResult->isSuccess())
			{
				return $updateResult;
			}
		}

		return $response->createResult();
	}

	public function acceptReview(int $memberId): Result
	{
		$member = $this->memberService->getById($memberId);

		if (!$member)
		{
			return (new Result())->addError(new Error('unknown member id'));
		}

		if ($member->role !== Role::REVIEWER)
		{
			return (new Result())->addError(new Error('wrong member role'));
		}

		$document = $this->documentService->getById($member->documentId);
		if (!$document)
		{
			return (new Result())->addError(new Error('unknown document id'));
		}

		if ($document->status !== Type\DocumentStatus::SIGNING || !MemberStatus::isReadyForSigning($member->status))
		{
			return (new Result())->addError(new Error('wrong document or member status'));
		}

		$response = $this->mobileService
			->acceptReview(
				new ReviewRequest($document->uid, $member->uid),
			)
		;

		if ($response->isSuccess())
		{
			$updateResult = (new ChangeMemberStatus($member, $document, MemberStatus::DONE))->launch();

			if (!$updateResult->isSuccess())
			{
				return $updateResult;
			}
		}

		return $response->createResult();
	}

	public function rejectSigning(int $memberId): Result
	{
		$member = Container::instance()->getMemberService()->getById($memberId);
		$resultWithRejectError = (new Result())->addError(new Error('reject error'));

		if (!$member)
		{
			return $resultWithRejectError;
		}

		if ($member->role !== Role::SIGNER && $member->role !== Role::REVIEWER)
		{
			return $resultWithRejectError;
		}

		$document = Container::instance()->getDocumentService()->getById($member->documentId);

		if (!$document)
		{
			return $resultWithRejectError;
		}

		if ($document->initiatedByType === Type\Document\InitiatedByType::EMPLOYEE || $member->role === Role::REVIEWER)
		{
			return (new SigningStop($document->uid, $member->entityId))->launch();
		}

		$response = Container::instance()->getApiMobileService()
			->refuseSigning(
				new RefuseRequest(documentUid: $document->uid, memberUid: $member->uid),
			)
		;

		if ($response->isSuccess())
		{
			$result = (new ChangeMemberStatus($member, $document, MemberStatus::REFUSED))->launch();

			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		return $response->createResult();
	}

	public function acceptConfirmation(int $memberId): Result
	{
		$member = $this->memberService->getById($memberId);

		if (!$member)
		{
			return (new Result())->addError(new Error('confirm error'));
		}

		$document = $this->documentService->getById($member->documentId);

		if (!$document)
		{
			return (new Result())->addError(new Error('confirm error'));
		}

		$response = Container::instance()->getApiMobileService()->acceptConfirmation(
			new AcceptRequest(documentUid: $document->uid, memberUid: $member->uid),
		);

		if (!$response->isSuccess())
		{
			return $response->createResult();
		}

		return (new ChangeMemberStatus($member, $document, MemberStatus::DONE))->launch();
	}

	public function postponeConfirmation(int $memberId): Result
	{
		$member = $this->memberService->getById($memberId);

		if ($member)
		{
			$document = $this->documentService->getById($member->documentId);

			if ($document)
			{
				$response = Container::instance()->getApiMobileService()->postponeConfirmation(
					new PostponeRequest(documentUid: $document->uid, memberUid: $member->uid),
				);

				return (new Result())->addErrors(
					$response->getErrors(),
				);
			}
		}

		return (new Result())->addError(new Error('reject error'));
	}

	public function checkAccessToSigning(int $memberId, int $userId): Result
	{
		$member = $this->memberService->getById($memberId);

		if (
			!$member
			|| !Container::instance()->getSignMemberUserService()->checkAccessToMember($member, $userId))
		{
			return (new Result())->addError(new Error(
				'access denied',
				'ACCESS_DENIED',
			));
		}

		return new Result();
	}

	public function getExternalSigningUrl(int $memberId): Result|ExternalSigningUrlResult
	{
		$member = $this->memberService->getById($memberId);

		if (!$member)
		{
			return (new Result())->addError(new Error('member not found'));
		}

		$document = $this->documentService->getById($member->documentId);

		if (!$document)
		{
			return (new Result())->addError(new Error('document not found'));
		}

		$response = Container::instance()
			->getApiMobileService()
			->getExternalSigningUrl(
				new ExternalUrlRequest($document->uid, $member->uid)
			)
		;

		if (!$response->isSuccess())
		{
			return $response->createResult();
		}

		return new ExternalSigningUrlResult($response->url);
	}

	private function getUrlForSignedFile(Result $result): ?string
	{
		$path = $result->getData()['url'] ?? null;

		if ($path)
		{
			$scheme = Context::getCurrent()?->getRequest()->isHttps() === false
				? 'http://'
				: 'https://'
			;
			return
				$scheme
				. Application::getServer()->getHttpHost()
				. $path
			;
		}

		return null;
	}

	private function getUrlForSigning(Result $result): ?string
	{
		return $result->getData()['uri'] ?? null;
	}
}
