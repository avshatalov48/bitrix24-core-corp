<?php

namespace Bitrix\StaffTrack\Integration\Im;

use Bitrix\Im\Common;
use Bitrix\Im\Dialog;
use Bitrix\Im\User;
use Bitrix\Im\V2\Message;
use Bitrix\Im\V2\Message\Delete\DeleteService;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\StaffTrack\Dictionary\Location;
use Bitrix\StaffTrack\Model\ShiftMessageTable;
use Bitrix\StaffTrack\Shift\ShiftDto;

class MessageService
{
	private int $userId;
	private ?ShiftDto $shiftDto = null;

	private ?int $chatId = null;
	private ?DeleteService $deleteService = null;

	public const COMPONENT_MESSAGE_ID = 'CheckInMessage';

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	/**
	 * @param ShiftDto $shiftDto
	 *
	 * @return Result
	 * @throws LoaderException
	 * @throws \Exception
	 */
	public function sendShiftStart(ShiftDto $shiftDto): Result
	{
		$result = new Result();
		$this->shiftDto = $shiftDto;

		if (!$this->isAvailable())
		{
			return $result->addError(new Error('Module not found'));
		}

		if (empty($this->shiftDto->dialogId))
		{
			return $result->addError(new Error('Empty dialog id'));
		}

		if (empty($this->shiftDto->message))
		{
			return $result->addError(new Error('Empty message'));
		}

		if (empty($this->getChatId($this->shiftDto->dialogId)))
		{
			return $result->addError(new Error('Chat id not found'));
		}

		$this->handleBaseStartMessage();
		$this->handleCheckInMessage();

		return $result;
	}

	/**
	 * @param ShiftDto $shiftDto
	 * @return Result
	 * @throws LoaderException
	 */
	public function sendShiftCancel(ShiftDto $shiftDto): Result
	{
		$result = new Result();
		$this->shiftDto = $shiftDto;

		if (!$this->isAvailable())
		{
			return $result->addError(new Error('Module not found'));
		}

		if (empty($this->shiftDto->dialogId) || empty($this->shiftDto->cancelReason))
		{
			return $result->addError(new Error('Empty dialog id'));
		}

		if (empty($this->getChatId($this->shiftDto->dialogId)))
		{
			return $result->addError(new Error('Chat id not found'));
		}

		\CIMChat::AddMessage([
			'DIALOG_ID' => $this->shiftDto->dialogId,
			'FROM_USER_ID' => $this->userId,
			'MESSAGE' => $this->shiftDto->cancelReason,
		]);

		return $result;
	}

	/**
	 * @param string $dialogId
	 * @param string $link
	 * @return Result
	 * @throws LoaderException
	 *
	 */
	public function sendUserStatisticsLink(string $dialogId, string $link): Result
	{
		$result = new Result();

		if (!$this->isAvailable())
		{
			return $result->addError(new Error('Module not found'));
		}

		if (empty($this->getChatId($dialogId)))
		{
			return $result->addError(new Error('Chat id not found'));
		}

		\CIMChat::AddMessage([
			'DIALOG_ID' => $dialogId,
			'FROM_USER_ID' => $this->userId,
			'MESSAGE' => Loc::getMessage('STAFFTRACK_INTEGRATION_IM_USER_LINK_STATISTICS_V2', [
				'#LINK#' => $link,
			]),
		]);

		return $result;
	}


	/**
	 * @param string|null $dialogId
	 * @return array|null[]
	 * @throws LoaderException
	 */
	public function getDialogInfo(?string $dialogId): array
	{
		$defaultResult = [
			'dialogId' => null,
			'dialogName' => null,
		];

		if (!$this->isAvailable())
		{
			return $defaultResult;
		}

		if (empty($dialogId))
		{
			return $defaultResult;
		}

		if (Common::isChatId($dialogId))
		{
			$dialogName = Dialog::getTitle($dialogId, $this->userId);
		}
		else
		{
			$chatId = \CIMMessage::GetChatId($dialogId, $this->userId);
			if (!$chatId)
			{
				return $defaultResult;
			}

			$dialogName = User::getInstance($dialogId)->getFullName(false);
		}

		return [
			'dialogId' => $dialogId,
			'dialogName' => $dialogName,
		];
	}

	/**
	 * @param int $messageId
	 *
	 * @return void
	 * @throws LoaderException
	 */
	public function deleteMessage(int $messageId): void
	{
		if (!$this->isAvailable())
		{
			return;
		}

		$message = new Message($messageId);

		$this->getDeleteMessageService($message)->delete();
	}

	/**
	 * @param \Bitrix\Im\V2\Message $message
	 * @return DeleteService
	 */
	private function getDeleteMessageService(\Bitrix\Im\V2\Message $message): DeleteService
	{
		if ($this->deleteService === null)
		{
			$this->deleteService = (new DeleteService($message))->setMode(DeleteService::DELETE_COMPLETE);
		}
		else
		{
			$this->deleteService->setMessage($message);
		}

		return $this->deleteService;
	}


	/**
	 * @return void
	 */
	private function handleBaseStartMessage(): void
	{
		if (!empty($this->shiftDto->imageFileId))
		{
			$fileId = 'disk' . $this->shiftDto->imageFileId;
			$params = [
				'USER_ID' => $this->shiftDto->userId,
			];

			\CIMDisk::UploadFileFromDisk(
				$this->chatId,
				[$fileId],
				$this->shiftDto->message,
				$params
			);
		}
		else
		{
			\CIMChat::AddMessage([
				'DIALOG_ID' => $this->shiftDto->dialogId,
				'FROM_USER_ID' => $this->userId,
				'MESSAGE' => $this->shiftDto->message,
			]);
		}
	}

	/**
	 * @return void
	 * @throws \Exception
	 */
	private function handleCheckInMessage(): void
	{
		if (empty($this->shiftDto->geoImageUrl))
		{
			return;
		}

		$componentParams = [
			'URL' => $this->shiftDto->geoImageUrl,
		];

		if (!empty($this->shiftDto->address))
		{
			$componentParams['LOCATION'] = Loc::getMessage('STAFFTRACK_INTEGRATION_IM_ADDRESS', [
				'#ADDRESS#' => $this->shiftDto->address,
			]);
		}
		else if (!empty($this->shiftDto->location))
		{
			$componentParams['STATUS'] = Location::tryFrom($this->shiftDto->location)
				? Location::getFullName($this->shiftDto->location)
				: $this->shiftDto->location
			;
		}

		$messageId = \CIMChat::AddMessage([
			'DIALOG_ID' => $this->shiftDto->dialogId,
			'FROM_USER_ID' => $this->userId,
			'MESSAGE' => $this->shiftDto->message,
			'PUSH' => 'N',
			'SKIP_COUNTER_INCREMENTS' => 'Y',
			'PARAMS' => [
				'NOTIFY' => 'N',
				'COMPONENT_ID' => self::COMPONENT_MESSAGE_ID,
				'COMPONENT_PARAMS' => $componentParams,
			],
		]);

		if (!empty($messageId))
		{
			$this->createShiftMessageConnection($messageId);
		}
	}

	/**
	 * @param string $dialogId
	 * @return bool
	 */
	private function getChatId(string $dialogId): bool
	{
		$this->chatId = \Bitrix\Im\Dialog::getChatId($dialogId, $this->userId);

		return $this->chatId;
	}


	/**
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function isAvailable(): bool
	{
		return Loader::includeModule('im');
	}

	/**
	 * @param int $messageId
	 * @return void
	 * @throws \Exception
	 */
	private function createShiftMessageConnection(int $messageId): void
	{
		ShiftMessageTable::add([
			'SHIFT_ID' => $this->shiftDto->id,
			'MESSAGE_ID' => $messageId,
		]);
	}
}
