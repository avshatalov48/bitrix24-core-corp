<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Deadline\Controllers;

use Bitrix\Main\Engine\AutoWire\BinderArgumentException;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Deadline\Command\SetDefaultDeadlineCommand;
use Bitrix\Tasks\Deadline\Controllers\Dto\DeadlineDto;
use Bitrix\Tasks\Deadline\Entity\DeadlineUserOption;
use Bitrix\Tasks\Flow\Control\Exception\InvalidCommandException;
use Bitrix\Tasks\Rest\Controllers\Trait\ErrorResponseTrait;
use InvalidArgumentException;

class Deadline extends Controller
{
	use ErrorResponseTrait;

	/**
	 * @throws BinderArgumentException
	 */
	public function getAutoWiredParameters(): array
	{
		return [
			new ExactParameter(
				DeadlineDto::class,
				'deadlineData',
				function($className, $deadlineData): ?DeadlineDto
				{
					if (isset($deadlineData['isExactTime']))
					{
						$deadlineData['isExactTime'] = $deadlineData['isExactTime'] === 'Y';
					}

					if (isset($deadlineData['canChangeDeadline']))
					{
						$deadlineData['canChangeDeadline'] =
							($deadlineData['canChangeDeadline'] === 'Y'
								|| $deadlineData['canChangeDeadline'] === 'true');
					}

					if (isset($deadlineData['requireDeadlineChangeReason']))
					{
						$deadlineData['requireDeadlineChangeReason'] =
							($deadlineData['requireDeadlineChangeReason'] === 'Y'
								|| $deadlineData['requireDeadlineChangeReason'] === 'true');
					}

					if (isset($deadlineData['maxDeadlineChangeDate']))
					{
						try
						{
							// Attempt to create DateTime from standard formats, including ISO 8601
							$dateTime = new DateTime($deadlineData['maxDeadlineChangeDate']);
							// Check if the created date is valid, e.g., not '0000-00-00' or similar invalid dates
							// DateTime constructor might not throw an error for all invalid string inputs but res
							// A more robust check might be needed depending on expected input formats.
							// For now,we assume valid date strings if not empty.
							$deadlineData['maxDeadlineChangeDate'] = $dateTime;
						}
						catch (\Exception)
						{
							$deadlineData[ 'maxDeadlineChangeDate'] = null;
						}
					}
					else
					{
						$deadlineData['maxDeadlineChangeDate'] = null;
					}

					if (isset($deadlineData['maxDeadlineChanges']))
					{
						$deadlineData['maxDeadlineChanges'] = !empty($deadlineData['maxDeadlineChanges'])
							? (int)$deadlineData['maxDeadlineChanges']
							: null
						;
					}

					return DeadlineDto::createFromArray($deadlineData);
				}
			),
		];
	}

	/**
	 * @restMethod tasks.deadline.Deadline.setDefault
	 */
	public function setDefaultAction(DeadlineDto $deadlineData): bool
	{
		try
		{
			$deadlineData->validate();

			$userId = (int)CurrentUser::get()->getId();

			$deadlineUserOption = new DeadlineUserOption(
				userId: $userId,
				defaultDeadlineInSeconds: $deadlineData->default,
				isExactDeadlineTime: $deadlineData->isExactTime,
				canChangeDeadline: $deadlineData->canChangeDeadline,
				maxDeadlineChangeDate: $deadlineData->maxDeadlineChangeDate,
				maxDeadlineChanges: $deadlineData->maxDeadlineChanges,
				requireDeadlineChangeReason: $deadlineData->requireDeadlineChangeReason,
			);

			$setDefaultDeadlineCommand = new SetDefaultDeadlineCommand($deadlineUserOption);
			$setDefaultDeadlineCommand->run();

			return true;
		}
		catch (InvalidCommandException|InvalidArgumentException $e)
		{
			$this->buildErrorResponse($e->getMessage());

			return false;
		}
	}
}
