<?php

namespace Bitrix\Sign\Controllers\V1\B2e;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Engine\Controller;

final class Onboarding extends Controller
{
	public function hideOnboardingSigningBannerAction(): array
	{
		$currentUserId = (int)CurrentUser::get()->getId();
		if ($currentUserId < 1)
		{
			$this->addError(new Error('User not found'));

			return [];
		}

		Container::instance()->getOnboardingService()->setBannerHidden($currentUserId);
		
		return [];
	}

	public function hasSignedDocumentsAction(): array
	{
		$currentUserId = (int)CurrentUser::get()->getId();
		if ($currentUserId < 1)
		{
			$this->addError(new Error('User not found'));

			return [];
		}

		$memberService = Container::instance()->getMemberService();
		$userHasRoleOrInitiatorInDocument = $memberService->isUserMemberOrInitiatorWithDoneStatus($currentUserId);

		return [
			'hasSignedDocuments' => $userHasRoleOrInitiatorInDocument,
		];
	}
}
