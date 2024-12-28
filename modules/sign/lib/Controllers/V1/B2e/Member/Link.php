<?php

namespace Bitrix\Sign\Controllers\V1\B2e\Member;

use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;
use Bitrix\Main\Web\Uri;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Service;
use Bitrix\Sign\Type\DocumentScenario;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;

final class Link extends \Bitrix\Sign\Engine\Controller
{
	const SHOW_MEMBER_INFO = false;


	public function getLinkForSigningAction(int $memberId): ?array
	{
		if (!Storage::instance()->isB2eAvailable())
		{
			$this->addError(new Error('Access denied'));
			return [];
		}

		$memberRepository = $this->container->getMemberRepository();
		$member = $memberRepository->getById($memberId);

		if (!$member)
		{
			$this->addError(new Error('Member not found'));
			return [];
		}

		$currentUserId = (int)\Bitrix\Main\Engine\CurrentUser::get()->getId();
		if (
			!$this->container
				->getSignMemberUserService()
				->checkAccessToMember($member, $currentUserId)
		)
		{
			$this->addError(new Error(
				Loc::getMessage('SIGN_CONTROLLERS_V1_B2E_MEMBER_LINK_ERROR_ACCESS_DENIED'),
				'ACCESS_DENIED'
			));
			return [];
		}

		$result = $this->container
			->getMemberService()
			->getLinkForSigning($member)
		;

		if (!$result->isSuccess())
		{
			$this->handleResultErrors($result);
			return [];
		}

		$linkForSigning = $result->getData()['uri'] ?? null;
		$lang = Context::getCurrent()->getLanguage();
		if ($linkForSigning && $lang)
		{
			$linkForSigning = (new Uri($linkForSigning))->addParams(['lang' => $lang])->getUri();
		}

		return [
			'uri' => $linkForSigning,
			'requireBrowser' => self::isSigningInBrowserRequired($member),
			'mobileAllowed' => self::isMobileSigningAllowed($member),
			'employeeData' => $this->getEmployeeDataForResponse($member),
		];
	}

	private function getEmployeeDataForResponse(Member $member): array
	{
		$employeeSigned = $member->role === Role::SIGNER && $member->status === MemberStatus::DONE;

		if (!$employeeSigned)
		{
			return [];
		}

		$documentService = Service\Container::instance()->getDocumentService();
		$document = $documentService->getById($member->documentId);

		if (!$document)
		{
			$this->addError(new Error('Document not found'));
			return [];
		}

		$userIdForMember = Service\Container::instance()
			->getMemberService()
			->getUserIdForMember($member);

		if (self::SHOW_MEMBER_INFO)
		{
			$avatar = Service\Container::instance()
				->getSignMemberUserService()
				->getAvatarByMemberUid($member->uid);

			$userModel = UserTable::getById($userIdForMember)->fetchObject();

			if (!$userModel)
			{
				$this->addError(new Error('User not found'));
				return [];
			}
		}

		return [
			'signed' => true,
			'dateSignedTs' => $member->dateSigned?->getTimestamp(),
			'uri' => [
				'signedDocument' => $this->getSignedFileDownloadUrl($member),
				'allDocuments' => Storage::instance()->getProfileSafeUrl($userIdForMember)
			],
			'document' => [
				'title' => $documentService->getComposedTitleByDocument($document),
				'dateTs' => $document->dateCreate?->getTimestamp(),
			],
			'member' => self::SHOW_MEMBER_INFO ? [
				'name' => \CUser::FormatName(
					\Bitrix\Main\Context::getCurrent()->getCulture()->getNameFormat(),
					[
						'LOGIN' => '',
						'NAME' => $userModel->getName(),
						'LAST_NAME' => $userModel->getLastName(),
						'SECOND_NAME' => $userModel->getSecondName(),
					],
					false, false
				),
				'position' => $userModel->getWorkPosition(),
				'photo' => $avatar?->getUriPath(),
			] : [],
		];
	}

	private static function isSigningInBrowserRequired(Member $member): bool
	{
		return $member->role === Role::ASSIGNEE;
	}

	private static function isMobileSigningAllowed(Member $member): bool
	{
		return $member->role === Role::SIGNER || $member->role === Role::REVIEWER;
	}

	private function handleResultErrors(\Bitrix\Main\Result $result): void
	{
		if ($result->getErrorCollection()->getErrorByCode('ACCESS_DENIED') !== null)
		{
			$this->addError(new \Bitrix\Main\Error(
				Loc::getMessage('SIGN_CONTROLLERS_V1_B2E_MEMBER_LINK_ERROR_ACCESS_DENIED'),
				'ACCESS_DENIED'
			));
		}
		else
		{
			$this->addErrors($result->getErrors());
		}
	}

	private function getSignedFileDownloadUrl(Member $member): ?string
	{
		$result = Service\Container::instance()
			->getMemberService()
			->getLinkForSignedFile($member)
		;

		return $result->isSuccess()
			? ($result->getData()['url'] ?? null)
			: null
		;
	}
}
