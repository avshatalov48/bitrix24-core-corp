<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Disk\Document\OnlyOffice\Templates\CreateDocumentByCallTemplateScenario;
use Bitrix\Disk\FileLink;
use Bitrix\Disk\Sharing;
use Bitrix\Disk\User;
use Bitrix\Im;
use Bitrix\Im\Call\Call;
use Bitrix\Im\Dialog;
use Bitrix\Main\Localization\Loc;

final class MessengerCallConnector extends StubConnector
{
	public const SECONDS_BETWEEN_SAME_FILE = 1800;

	private $canRead;
	/** @var int */
	private $callId;
	/** @var Call */
	private $call;

	public function __construct($entityId)
	{
		parent::__construct($entityId);

		$this->callId = $entityId;
	}

	private function getCall(): ?Call
	{
		if (!$this->call)
		{
			$this->call = Call::loadWithId($this->callId);
		}

		return $this->call;
	}

	private function getChatId(): ?int
	{
		$call = $this->getCall();
		if (!$call)
		{
			return null;
		}

		return $call->getChatId();
	}

	public function canRead($userId): bool
	{
		if ($this->canRead !== null)
		{
			return $this->canRead;
		}

		$this->canRead = false;

		$call = $this->getCall();
		if ($call)
		{
			$associatedEntity = $call->getAssociatedEntity();
			if ($associatedEntity)
			{
				$this->canRead = $associatedEntity->checkAccess((int)$userId);
			}
		}

		return $this->canRead;
	}

	public function canUpdate($userId): bool
	{
		return $this->canRead($userId);
	}

	protected function shouldSendResume(FileLink $resume): bool
	{
		$endDate = $this->getCall()->getEndDate();
		if (!$endDate)
		{
			return false;
		}

		if (time() - $endDate->getTimestamp() > self::SECONDS_BETWEEN_SAME_FILE)
		{
			return true;
		}

		return Im\Disk\Sender::hasFileInLastMessages($resume, $this->getChatId());
	}

	public function addComment($authorId, array $data): void
	{
		if (!$this->getChatId())
		{
			return;
		}

		if ($this->getCall()->hasActiveUsers())
		{
			return;
		}

		if (!$this->canRead($authorId))
		{
			return;
		}

		$fileLink = $this->getLinkFileInChat();
		if (!$fileLink)
		{
			return;
		}

		$isResume = $this->isResume();
		if ($isResume && !$this->shouldSendResume($fileLink))
		{
			return;
		}

		$text = Loc::getMessage("DISK_UF_IM_CALL_CONNECTOR_CALL_DOCUMENT_UPDATED");
		if ($isResume)
		{
			$text = Loc::getMessage("DISK_UF_IM_CALL_CONNECTOR_CALL_RESUME_UPDATED");
		}

		Im\Disk\Sender::sendExistingFileToChat(
			$fileLink,
			$this->getChatId(),
			$text,
			['CALL_ID' => $this->getCall()->getId()],
			$authorId
		);
	}

	protected function getLinkFileInChat(): ?FileLink
	{
		$file = $this->getAttachedObject()->getFile();
		$sharings = $file->getSharingsAsReal(['TO_ENTITY' => Sharing::CODE_CHAT . $this->getChatId()]);
		if ($sharings)
		{
			return $sharings[0]->getLinkObject();
		}

		return null;
	}

	public function getDataToShow()
	{
		return $this->getDataToShowByUser($this->getUser()->getId());
	}

	public function getDataToShowByUser(int $userId): array
	{
		$call = $this->getCall();
		if (!$call)
		{
			return [];
		}

		$dialogId = $call->getAssociatedEntity()->getEntityId($userId);

		return [
			'TITLE' => Loc::getMessage('DISK_UF_IM_CALL_CONNECTOR_TITLE', ['#NAME#' => $call->getAssociatedEntity()->getName($userId)]),
			'DETAIL_URL' => $dialogId ? Dialog::getLink($dialogId, $userId) : '',
			'DESCRIPTION' => '',
			'MEMBERS' => $this->getMembers($call),
		];
	}

	protected function getMembers(Call $call): array
	{
		$members = [];
		$userIds = $call->getAssociatedEntity()->getUsers();
		if (!$userIds)
		{
			return [];
		}

		$users = User::getModelList([
			'filter' => [
				'@ID' => $userIds,
			]
		]);
		foreach ($users as $user)
		{
			$members[] = [
				"NAME" => $user->getFormattedName(),
				"LINK" => \CComponentEngine::makePathFromTemplate($this->getPathToUser(), ["user_id" => $user->getId()]),
				"AVATAR_SRC" => $user->getAvatarSrc(),
			];
		}

		return $members;
	}

	protected function isResume(): bool
	{
		return $this->getAttachedObject()->getFile()->getCode() === CreateDocumentByCallTemplateScenario::CODE_RESUME;
	}
}
