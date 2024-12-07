<?php

namespace Bitrix\SignMobile\Controller;

use Bitrix\Intranet\ActionFilter\IntranetUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Sign;
use Bitrix\SignMobile\Response\Document\MemberDocumentResourceCollection;

class Document extends Controller
{
	private function includeRequiredModules(): bool
	{
		$result = (
			Loader::includeModule('mobile') &&
			Loader::includeModule('sign') &&
			Loader::includeModule('intranet')
		);

		if (!$result)
		{
			$this->addError(new Error(
				'Modules must be installed: mobile, sign, intranet',
				'REQUIRED_MODULES_NOT_INSTALLED'
			));
		}

		return $result;
	}

	public function getSigningLinkAction(int $memberId): ?array
	{
		if (!$this->includeRequiredModules())
		{
			return null;
		}

		$currentUserId = CurrentUser::get()->getId();

		if (!$currentUserId)
		{
			$this->addError(new Error(
				Loc::getMessage('SIGN_MOBILE_CONTROLLER_DOCUMENT_USER_WAS_NOT_FOUND'),
				'USER_WAS_NOT_FOUND'
			));
			return null;
		}

		$mobileService = Sign\Service\Container::instance()->getMobileService();
		$checkAccessResult = $mobileService->checkAccessToSigning($memberId, (int)$currentUserId);

		if (!$checkAccessResult->isSuccess())
		{
			if ($checkAccessResult->getErrorCollection()->getErrorByCode('ACCESS_DENIED') !== null)
			{
				$this->addError(new Error(
					Loc::getMessage('SIGN_MOBILE_CONTROLLER_DOCUMENT_LINK_IS_NOT_VALID'),
					'ACCESS_DENIED'
				));
				return null;
			}

			$this->addErrors($checkAccessResult->getErrors());
			return null;
		}

		$result = Sign\Service\Container::instance()
			->getMobileService()
			->setDarkMode(false) // TODO
			->getLinkForSigning($memberId)
		;

		if ($result->isSuccess() && $link = $result->getLink())
		{
			return [
				'documentTitle' => $link->documentTitle,
				'isReadyForSigning' => $link->isReadyForSigningOnMobile(),
				'isGoskey' => $link->isGoskey(),
				'role' => $link->getRole(),
				'state' => $link->getDocumentSigningState(),
				'url' => $link->url,
				'isExternal' => $link->isExternal(),
			];
		}

		$this->addError(new Error(
			Loc::getMessage('SIGN_MOBILE_CONTROLLER_DOCUMENT_LINK_IS_NOT_VALID'),
			'ACCESS_DENIED'
		));

		return null;
	}

	public function confirmationAcceptAction(int $memberId): void
	{
		if (!$this->includeRequiredModules())
		{
			return;
		}

		$currentUserId = CurrentUser::get()->getId();

		if (!$currentUserId)
		{
			$this->addError(new Error(
				Loc::getMessage('SIGN_MOBILE_CONTROLLER_DOCUMENT_USER_WAS_NOT_FOUND'),
				'USER_WAS_NOT_FOUND'
			));
			return;
		}

		$checkAccessResult = Sign\Service\Container::instance()
			->getMobileService()
			->checkAccessToSigning($memberId, (int)$currentUserId)
		;

		if (!$checkAccessResult->isSuccess())
		{
			$this->addErrors($checkAccessResult->getErrors());
			return;
		}

		$result = Sign\Service\Container::instance()
			->getMobileService()
			->acceptConfirmation($memberId)
		;

		$this->addErrors($result->getErrors());
	}

	public function confirmationPostponeAction(int $memberId): void
	{
		if (!$this->includeRequiredModules())
		{
			return;
		}

		$currentUserId = CurrentUser::get()->getId();

		if (!$currentUserId)
		{
			$this->addError(new Error(
				Loc::getMessage('SIGN_MOBILE_CONTROLLER_DOCUMENT_USER_WAS_NOT_FOUND'),
				'USER_WAS_NOT_FOUND'
			));
			return;
		}

		$checkAccessResult = Sign\Service\Container::instance()
			->getMobileService()
			->checkAccessToSigning($memberId, (int)$currentUserId)
		;

		if (!$checkAccessResult->isSuccess())
		{
			$this->addErrors($checkAccessResult->getErrors());
			return;
		}

		$result = Sign\Service\Container::instance()
			->getMobileService()
			->postponeConfirmation($memberId)
		;

		$this->addErrors($result->getErrors());
	}

	public function signingRejectAction(int $memberId): void
	{
		if (!$this->includeRequiredModules())
		{
			return;
		}

		$currentUserId = CurrentUser::get()->getId();

		if (!$currentUserId)
		{
			$this->addError(new Error(
				Loc::getMessage('SIGN_MOBILE_CONTROLLER_DOCUMENT_USER_WAS_NOT_FOUND'),
				'USER_WAS_NOT_FOUND'
			));
			return;
		}

		$checkAccessResult = Sign\Service\Container::instance()
			->getMobileService()
			->checkAccessToSigning($memberId, (int)$currentUserId)
		;

		if (!$checkAccessResult->isSuccess())
		{
			$this->addErrors($checkAccessResult->getErrors());
			return;
		}

		$result = Sign\Service\Container::instance()
			->getMobileService()
			->rejectSigning($memberId)
		;

		$this->addErrors($result->getErrors());
	}

	public function signingAcceptAction(int $memberId): void
	{
		if (!$this->includeRequiredModules())
		{
			return;
		}

		$currentUserId = CurrentUser::get()->getId();

		if (!$currentUserId)
		{
			$this->addError(new Error(
				Loc::getMessage('SIGN_MOBILE_CONTROLLER_DOCUMENT_USER_WAS_NOT_FOUND'),
				'USER_WAS_NOT_FOUND'
			));
			return;
		}

		$checkAccessResult = Sign\Service\Container::instance()
			->getMobileService()
			->checkAccessToSigning($memberId, (int)$currentUserId)
		;

		if (!$checkAccessResult->isSuccess())
		{
			$this->addErrors($checkAccessResult->getErrors());
			return;
		}

		$result = Sign\Service\Container::instance()
			->getMobileService()
			->acceptSigning($memberId)
		;

		$this->addErrors($result->getErrors());
	}

	public function listAction(): ?array
	{
		if (!$this->includeRequiredModules())
		{
			return null;
		}

		$currentUserId = CurrentUser::get()->getId();
		if ($currentUserId === null)
		{
			$this->addError(new Error('Access denied', 'ACCESS_DENIED'));

			return null;
		}

		$signingMemberDocumentList = Sign\Service\Container::instance()
			->getSignMobileMemberService()
			->getB2eSigningMemberDocumentList($currentUserId)
		;

		$signedMemberDocumentList = Sign\Service\Container::instance()
			->getSignMobileMemberService()
			->getB2eSignedMemberDocumentList($currentUserId)
		;

		$reviewMemberDocumentList = Sign\Service\Container::instance()
			->getSignMobileMemberService()
			->getB2eReviewMemberDocumentList($currentUserId)
		;

		return [
			'signingList' => MemberDocumentResourceCollection::fromItemCollection($signingMemberDocumentList)->toArray(),
			'signedList' => MemberDocumentResourceCollection::fromItemCollection($signedMemberDocumentList)->toArray(),
			'reviewList' =>MemberDocumentResourceCollection::fromItemCollection($reviewMemberDocumentList)->toArray()
		];
	}

	public function configureActions(): array
	{
		if (!$this->includeRequiredModules())
		{
			return [];
		}

		$actionsConfiguration = parent::configureActions();
		$actionsConfiguration['list']['+prefilters'] = [
			IntranetUser::class
		];

		return $actionsConfiguration;
	}

	public function getExternalUrlAction(int $memberId): array
	{
		if (!$this->includeRequiredModules())
		{
			return [];
		}

		$currentUserId = CurrentUser::get()->getId();

		if (!$currentUserId)
		{
			$this->addError(new Error('Access denied', 'ACCESS_DENIED'));

			return [];
		}

		$checkAccessResult = Sign\Service\Container::instance()
		   ->getMobileService()
		   ->checkAccessToSigning($memberId, (int)$currentUserId)
		;

		if (!$checkAccessResult->isSuccess())
		{
			$this->addErrors($checkAccessResult->getErrors());

			return [];
		}

		$result = Sign\Service\Container::instance()
					->getMobileService()
					->getExternalSigningUrl($memberId)
		;

		if ($result instanceof Sign\Result\Service\ExternalSigningUrlResult)
		{
			return [
				'url' => $result->url,
			];
		}

		$this->addErrors($result->getErrors());

		return [];
	}
}
