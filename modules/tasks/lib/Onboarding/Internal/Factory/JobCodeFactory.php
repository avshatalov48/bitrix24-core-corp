<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Internal\Factory;

use Bitrix\Tasks\Onboarding\Command\CommandInterface;
use Bitrix\Tasks\Onboarding\Internal\Type;
use Bitrix\Tasks\Onboarding\Transfer\JobCode;
use Bitrix\Tasks\Onboarding\Transfer\JobCodes;
use Bitrix\Tasks\Onboarding\Transfer\CommandModel;

final class JobCodeFactory
{
	public static function createCodes(array $codes): JobCodes
	{
		return new JobCodes($codes);
	}

	public static function createCodeByCommandModel(CommandModel $commandModel): JobCode
	{
		return self::createCode($commandModel->type, $commandModel->userId);
	}

	public static function createCodeByCommand(CommandInterface $command): JobCode
	{
		return new JobCode($command->getCode());
	}

	public static function createCode(Type $type, int $userId): JobCode
	{
		return new JobCode($type->value . '_' . $userId);
	}
}