<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Command;

use Bitrix\Intranet\Exception\UpdateFailedException;
use Bitrix\Intranet\Internal\Entity\UserProfile;
use Bitrix\Intranet\Internal\Service\UserProfileService;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Command\AbstractCommand;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class UpdateUserProfileCommand extends AbstractCommand
{
	public function __construct(
		public readonly UserProfile $userProfile,
	)
	{
	}

	protected function execute(): Result
	{
		$result = new Result();

		try
		{
			$userProfileService = UserProfileService::createByDefault();
			$handler = new UpdateUserProfileHandler($userProfileService);
			$handler($this);
		}
		catch (UpdateFailedException $e)
		{
			$result->addErrors($e->getErrors()->toArray());
		}
		catch (ArgumentException $e)
		{
			$result->addError(new Error('Wrong arguments'));
		}

		return $result;
	}

	public function toArray(): array
	{
		return [];
	}
}
