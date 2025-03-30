<?php

namespace Bitrix\SignMobile\Controller;

use Bitrix\Intranet\ActionFilter\IntranetUser;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Mobile\Provider\UserRepository;
use Bitrix\Sign;
use Bitrix\Sign\Type\MyDocumentsGrid\ActorRole;
use Bitrix\SignMobile\Config\Feature;
use Bitrix\SignMobile\Response\Document\MemberDocumentResourceCollection;
use Bitrix\Sign\Type\MyDocumentsGrid\FilterStatus;
use Bitrix\Sign\Item\MyDocumentsGrid\MyDocumentsFilter;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Serializer\ItemPropertyJsonSerializer;

class Document extends Controller
{
	private const FILTER_PRESET_IN_WORK = 'preset_in_progress';
	private const FILTER_PRESET_SEND = 'preset_send';
	private const FILTER_PRESET_SIGNED = 'preset_signed';
	private const FILTER_PRESET_PROCESSED_BY_ME = 'preset_processed_by_me';

	private function includeRequiredModules(): bool
	{
		$result = (Loader::includeModule('intranet'));

		if (!$result)
		{
			$this->addError(new Error(
				'Modules must be installed: mobile, sign, intranet',
				'REQUIRED_MODULES_NOT_INSTALLED'
			));
		}

		return $result;
	}

	public function configureActions(): array
	{
		if (!$this->includeRequiredModules())
		{
			return [];
		}

		return [
			'getDocumentList' => [
				'+prefilters' => [
					new CloseSession(),
					new IntranetUser(),
				],
			],
			'list' => [
				'+prefilters' => [
					new IntranetUser(),
				],
			],
		];
	}

	private function getRequestFilters($filterParams): MyDocumentsFilter
	{
		$searchString = (string)($filterParams['searchString'] ?? '');

		return match ($filterParams['tabId'] ?? null)
		{
			self::FILTER_PRESET_IN_WORK => new MyDocumentsFilter(
				statuses: [FilterStatus::IN_PROGRESS],
				text: $searchString,
			),
			self::FILTER_PRESET_SEND => new MyDocumentsFilter(
				role: ActorRole::INITIATOR,
				text: $searchString,
			),
			self::FILTER_PRESET_SIGNED => new MyDocumentsFilter(
				statuses: [FilterStatus::SIGNED],
				text: $searchString,
			),
			self::FILTER_PRESET_PROCESSED_BY_ME => new MyDocumentsFilter(
				statuses: [FilterStatus::MY_ACTION_DONE],
				text: $searchString,
			),
			default => new MyDocumentsFilter(text: $searchString),
		};
	}

	public function isE2bAvailableAction(): array
	{
		return [
			'isE2bAvailable' =>
				class_exists(Feature::class)
				&& Feature::instance()->isSendDocumentByEmployeeEnabled()
		];
	}

	public function getNeedCountAction(CurrentUser $user): int
	{
		$userId = (int)$user->getId();

		return Container::instance()
			->getMyDocumentService()
			->getTotalCountNeedAction($userId)
		;
	}

	public function getNeedCountForSendPresetAction(CurrentUser $user): int
		{
		$userId = (int)$user->getId();

		return Container::instance()
			->getMyDocumentService()
			->getCountNeedActionForSentDocumentsByEmployee($userId)
			;
		}

	public function getDocumentListAction(PageNavigation $nav, CurrentUser $user, array $filterParams): array
	{
		if (!$this->includeRequiredModules())
		{
			return [];
		}

		$currentUserId = CurrentUser::get()->getId();
		if ($currentUserId === null)
		{
			$this->addError(new Error('Access denied', 'ACCESS_DENIED'));

			return [];
		}

		$dataService = Container::instance()->getMyDocumentService();
		$userId = (int)$user->getId();
		$limit = (int)$nav->getLimit();
		$offset = (int)$nav->getOffset();
		$gridFilter = $this->getRequestFilters($filterParams);
		$data = $dataService->getGridData($limit, $offset, $userId, $gridFilter);
		$items = (new ItemPropertyJsonSerializer())->serialize($data->rows);

		return [
			'items' => $items,
			'users' => UserRepository::getByIds($data->userIds),
			'needActionCount' => $this->getNeedCountAction($user),
			'needCountForSendPreset' => $this->getNeedCountForSendPresetAction($user),
		];
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
				'initiatedByType' => $link->getInitiatedByType(),
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

	public function reviewAcceptAction(int $memberId): void
	{
		if (!$this->includeRequiredModules())
		{
			return;
		}

		$currentUserId = (int)CurrentUser::get()->getId();
		if ($currentUserId <= 0)
		{
			$this->addError(new Error(
				Loc::getMessage('SIGN_MOBILE_CONTROLLER_DOCUMENT_USER_WAS_NOT_FOUND'),
				'USER_WAS_NOT_FOUND'
			));

			return;
		}

		$mobileService = Sign\Service\Container::instance()
			->getMobileService()
		;

		$checkAccessResult = $mobileService
			->checkAccessToSigning($memberId, $currentUserId)
		;

		if (!$checkAccessResult->isSuccess())
		{
			$this->addErrors($checkAccessResult->getErrors());

			return;
		}

		$result = $mobileService
			->acceptReview($memberId)
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
			'reviewList' =>MemberDocumentResourceCollection::fromItemCollection($reviewMemberDocumentList)->toArray(),
		];
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
