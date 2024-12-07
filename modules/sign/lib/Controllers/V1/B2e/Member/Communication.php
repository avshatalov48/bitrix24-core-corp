<?php

namespace Bitrix\Sign\Controllers\V1\B2e\Member;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Service;
use Bitrix\Sign\Type\Member\ChannelType;
use Bitrix\Main;

final class Communication extends \Bitrix\Sign\Engine\Controller
{
	#[Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT)]
	public function updateMembersChannelTypeAction(CurrentUser $user, array $members, string $channelType): array
	{
		$channelType = mb_strtoupper($channelType);
		if (!ChannelType::isValid($channelType))
		{
			$this->addErrorByMessage('Invalid channel type');

			return [];
		}

		$memberItems = Service\Container::instance()->getMemberRepository()->listByUids($members);
		$communicationService = new Service\Sign\Member\CommunicationService($user->getId());

		$result = $communicationService->modifyMembersChannelType($memberItems, $channelType);
		$this->addErrorsFromResult($result);

		return [];
	}

	public function setAgreementDecisionAction(): array
	{
		if (
			!Storage::instance()->isB2eAvailable()
			|| empty(Storage::instance()->getClientToken())
		)
		{
			$this->addError((new Main\Error('B2e is not active')));
			return [];
		}

		$timestamp = (new Main\Type\DateTime())->getTimestamp();
		$decision = 'Y';

		\CUserOptions::SetOption(
			'sign',
			'sign-agreement',
			compact('decision', 'timestamp'),
			false,
			Main\Engine\CurrentUser::get()->getId()
		);

		return compact('decision');
	}
}
