<?php

namespace Bitrix\Intranet\Infrastructure\Controller\ActionFilter;

use Bitrix\Bitrix24\Feature;
use Bitrix\Intranet\Public\Type\Collection\InvitationCollection;
use Bitrix\Intranet\Internals\InvitationTable;
use Bitrix\Intranet\Invitation\Register;
use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

class EmailDailyLimit extends Engine\ActionFilter\Base
{
	public function __construct(
		private int $maxInvitationsAtTime = 0,
		private int $dailyEmailLimit = 0,
	)
	{
		parent::__construct();
	}

	public static function createByDefault()
	{
		$dailyEmailLimit = 0;
		if (
			Loader::includeModule('bitrix24')
			&& ((int)Feature::getVariable('intranet_emails_invitation_limit') > 0)
		)
		{
			$dailyEmailLimit = (int)Feature::getVariable('intranet_emails_invitation_limit');
		}
		$maxInvitationsAtTime = static::getMaxEmailCount();

		return new self($maxInvitationsAtTime, $dailyEmailLimit);
	}

	final public function onBeforeAction(Event $event)
	{
		Loc::loadLanguageFile($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/intranet/lib/invitation/register.php');

		/** @var \Bitrix\Main\Engine\Action $action */
		$action = $event->getParameter('action');
		$arguments = $action->getArguments();
		$invitationCollection = $this->extractInvitationCollection($arguments) ?? new InvitationCollection();
		$date = new DateTime();
		$date->add('-1 days');

		$pastEmailInvitationNumber = InvitationTable::query()
			->where('INVITATION_TYPE', \Bitrix\Intranet\Invitation::TYPE_EMAIL)
			->where('DATE_CREATE', '>', $date)
			->queryCountTotal();

		if (
			$this->dailyEmailLimit > 0
			&& ($pastEmailInvitationNumber + $invitationCollection->countEmailInvitation()) >= $this->dailyEmailLimit
		)
		{
			$this->addError(new Error(
				Loc::getMessage("INTRANET_INVITATION_DAILY_EMAIL_LIMIT_EXCEEDED")
			));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		if ($invitationCollection->countEmailInvitation() > $this->maxInvitationsAtTime)
		{
			$this->addError(new Error(
				Loc::getMessage("INTRANET_INVITATION_EMAIL_LIMIT_EXCEEDED")
			));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}
	}

	private function extractInvitationCollection(array $arguments): ?InvitationCollection
	{
		foreach ($arguments as $argument)
		{
			if ($argument instanceof InvitationCollection)
			{
				return $argument;
			}
		}

		return null;
	}

	private static function getMaxEmailCount()
	{
		if (Loader::includeModule('bitrix24'))
		{
			$licensePrefix = \CBitrix24::getLicensePrefix();
			$licenseType = \CBitrix24::getLicenseType();

			if (
				$licenseType === 'project'
				&& in_array($licensePrefix, ['cn', 'en', 'vn', 'jp'])
			)
			{
				return 10;
			}
		}

		return 100;
	}
}