<?php

namespace Bitrix\Rpa\Controller;

use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Model\TimelineTable;
use Bitrix\UI\Timeline\CommentParser;

class Comment extends Base
{
	public const USER_FIELD_ENTITY_ID = 'RPA_COMMENT';
	public const USER_FIELD_FILES = 'UF_RPA_COMMENT_FILES';

	protected const MAXIMUM_NOTIFICATION_COMMENT_TEXT_LENGTH = 255;

	protected static $uiComment;

	public static function getUiComment(): \Bitrix\UI\Timeline\Comment
	{
		Loader::includeModule('ui');
		if(static::$uiComment === null)
		{
			static::$uiComment = new \Bitrix\UI\Timeline\Comment(static::USER_FIELD_ENTITY_ID, static::USER_FIELD_FILES);
		}
		return static::$uiComment;
	}

	public static function getCommentParser(int $id = 0): CommentParser
	{
		Loader::includeModule('ui');
		return new CommentParser(static::getUiComment()->getFileUserFields($id));
	}

	public function getVisualEditorAction(string $name, int $commentId = 0): ?Component
	{
		$text = '';
		if($commentId > 0)
		{
			$timeline = TimelineTable::getById($commentId)->fetchObject();
			if(!$timeline)
			{
				$this->addError(new Error('Comment ',$commentId.' is not found'));
				return null;
			}
			$item = $timeline->getItem();
			if (!$item)
			{
				$this->addError(new Error('Comment ',$commentId.' is not found'));
				return null;
			}
			$userPermissions = Driver::getInstance()->getUserPermissions();
			if (!$userPermissions->canViewComment($item, $timeline))
			{
				$this->addError(new Error(Loc::getMessage('RPA_MODIFY_COMMENT_ACCESS_DENIED')));
				return null;
			}
			$text = $timeline->getDescription();
		}

		return static::getUiComment()->getVisualEditorResponse($name, $commentId, $text);
	}

	public function addAction(\Bitrix\Rpa\Model\Type $type, int $itemId, array $fields, string $eventId = ''): ?array
	{
		$item = $type->getItem($itemId);
		if(!$item)
		{
			$this->addError(new Error(Loc::getMessage('RPA_ITEM_NOT_FOUND_ERROR')));
			return null;
		}

		$userPermissions = Driver::getInstance()->getUserPermissions();
		if(!$userPermissions->canAddComment($item))
		{
			$this->addError(new Error(Loc::getMessage('RPA_ADD_COMMENT_ACCESS_DENIED')));
			return null;
		}

		$emptyFields = $this->getEmptyRequiredParameterNames($fields, ['description']);
		if(!empty($emptyFields))
		{
			$this->addError(new Error('Empty required fields: '.implode(', ', $emptyFields)));
			return null;
		}

		$timeline = \Bitrix\Rpa\Model\Timeline::createForItem($item);
		$timeline->setAction($timeline::ACTION_COMMENT);
		$timeline->setDescription($fields['description']);
		$timeline->setUserId($userPermissions->getUserId());
		$files = $this->processFiles($fields);

		$uiComment = static::getUiComment();
		$result = $timeline->save();
		if($result->isSuccess())
		{
			$this->sendMentions($timeline);
			Driver::getInstance()->getPullManager()->sendTimelineAddEvent($timeline, $eventId);
			$uiComment->saveFiles($timeline->getId(), $files);
			return [
				'comment' => $timeline->preparePublicData(),
			];
		}

		$this->addErrors($result->getErrors());
		return null;
	}

	public function deleteAction(int $id, string $eventId = ''): void
	{
		$timeline = TimelineTable::getById($id)->fetchObject();
		if(!$timeline)
		{
			return;
		}

		$userPermissions = Driver::getInstance()->getUserPermissions();
		if(!$userPermissions->canDeleteComment($timeline))
		{
			$this->addError(new Error(Loc::getMessage('RPA_DELETE_COMMENT_ACCESS_DENIED')));
			return;
		}

		$result = $timeline->delete();
		if(!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}
		else
		{
			$pullManager = Driver::getInstance()->getPullManager();
			$pullManager->sendTimelineDeleteEvent($timeline->getTypeId(), $timeline->getItemId(), $id, $eventId);
		}
	}

	public function updateAction(int $id, array $fields, string $eventId = ''): ?array
	{
		$timeline = TimelineTable::getById($id)->fetchObject();
		if(!$timeline)
		{
			$this->addError(new Error(Loc::getMessage('RPA_COMMENT_NOT_FOUND_ERROR')));
			return null;
		}

		$userPermissions = Driver::getInstance()->getUserPermissions();
		if(!$userPermissions->canUpdateComment($timeline))
		{
			$this->addError(new Error(Loc::getMessage('RPA_MODIFY_COMMENT_ACCESS_DENIED')));
			return null;
		}

		$emptyFields = $this->getEmptyRequiredParameterNames($fields, ['description']);
		if(!empty($emptyFields))
		{
			$this->addError(new Error('Empty required fields: '.implode(', ', $emptyFields)));
			return null;
		}

		$uiComment = static::getUiComment();
		$previouslyMentionedUserIds = static::getCommentParser()->getMentionedUserIds($timeline->getDescription());

		$timeline->setDescription($fields['description']);
		$files = $this->processFiles($fields);
		$result = $timeline->save();
		if($result->isSuccess())
		{
			$uiComment->saveFiles($timeline->getId(), $files);
			$this->sendMentions($timeline, $previouslyMentionedUserIds);
			Driver::getInstance()->getPullManager()->sendTimelineUpdateEvent($timeline, $eventId);
			return [
				'comment' => $timeline->preparePublicData(),
			];
		}

		$this->addErrors($result->getErrors());
		return null;
	}

	protected function processFiles(array $fields): array
	{
		$files = is_array($fields['files']) ? $fields['files'] : [];
		$uiComment = static::getUiComment();
		$userField = $uiComment->getFileUserFields();
		if (!$userField)
		{
			return [];
		}
		$userId = Driver::getInstance()->getUserId();
		$storage = \Bitrix\Disk\Driver::getInstance()->addUserStorage($userId);
		if (!$storage)
		{
			return [];
		}
		$securityContext = $storage->getSecurityContext($userId);
		foreach ($files as &$file)
		{
			if ($this->getScope() === self::SCOPE_REST)
			{
				if (
					isset($file['id'])
					&& $file['id'] > 0
					&& $securityContext->canRead((int)$file['id'])
				)
				{
					$file = (int)$file['id'];
				}
				else
				{
					$file = $this->uploadFile($file);
					if($file > 0)
					{
						$file = 'n' . $file;
					}
				}
			}
			else
			{
				$fileId = $file;
				if (mb_substr($fileId, 0, 1) === 'n')
				{
					$fileId = (int)mb_substr($fileId, 1);
				}
				if (!$securityContext->canRead($fileId))
				{
					$file = null;
				}
			}
		}

		return $files;
	}

	protected function uploadFile($fileContent): ?int
	{
		if(empty($fileContent))
		{
			return null;
		}

		$fileArray = \CRestUtil::saveFile($fileContent);
		if(!$fileArray)
		{
			$this->addError(new Error(Loc::getMessage('RPA_CONTROLLER_COULD_NOT_UPLOAD_FILE_ERROR')));
			return null;
		}

		$userId = Driver::getInstance()->getUserId();
		$storage = \Bitrix\Disk\Driver::getInstance()->addUserStorage($userId);
		if($storage)
		{
			$folder = $storage->getFolderForUploadedFiles();
			if($folder)
			{
				$file = $folder->uploadFile($fileArray, [
					'CREATED_BY' => $userId,
					'MODULE_ID' => Driver::MODULE_ID,
				], [], true);
				if($file)
				{
					return $file->getId();
				}

				$this->addErrors($folder->getErrors());
			}
		}

		return null;
	}

	public function getFilesContentAction(int $id): ?Component
	{
		$timeline = TimelineTable::getById($id)->fetchObject();
		if(!$timeline)
		{
			$this->addError(new Error(Loc::getMessage('RPA_COMMENT_NOT_FOUND_ERROR')));
			return null;
		}

		$item = $timeline->getItem();
		$userPermissions = Driver::getInstance()->getUserPermissions();
		if($item && !$userPermissions->canViewComment($item, $timeline))
		{
			$this->addError(new Error(Loc::getMessage('RPA_MODIFY_COMMENT_ACCESS_DENIED')));
			return null;
		}

		return static::getUiComment()->getFilesContentResponse($id);
	}

	public function getAction(int $id, array $options = []): ?array
	{
		$timeline = TimelineTable::getById($id)->fetchObject();
		if(!$timeline)
		{
			$this->addError(new Error(Loc::getMessage('RPA_COMMENT_NOT_FOUND_ERROR')));
			return null;
		}

		$item = $timeline->getItem();
		$userPermissions = Driver::getInstance()->getUserPermissions();
		if(!$userPermissions->canViewComment($item, $timeline))
		{
			$this->addError(new Error(Loc::getMessage('RPA_MODIFY_COMMENT_ACCESS_DENIED')));
			return null;
		}

		return [
			'comment' => $timeline->preparePublicData($options),
		];
	}

	protected function sendMentions(\Bitrix\Rpa\Model\Timeline $timeline, array $previouslyMentionedUserIds = []): void
	{
		$item = $timeline->getItem();
		if(!$item)
		{
			return;
		}

		$uiComment = static::getUiComment();

		$userSuffix = $uiComment->getUserGenderSuffix($timeline->getUserId());
		$text = TruncateText(
			trim(static::getCommentParser()->getText($timeline->getDescription())),
			static::MAXIMUM_NOTIFICATION_COMMENT_TEXT_LENGTH
		);
		$message = Loc::getMessage('RPA_COMMENT_MENTION_NOTIFY'.$userSuffix, [
			'#ITEM#' => '<a href="'.Driver::getInstance()->getUrlManager()->getItemDetailUrl(
				$item->getType()->getId(),
				$item->getId()
			).'" class="bx-notifier-item-action">'.$item->getName().'</a>',
			'#TYPE#' => $item->getType()->getTitle(),
			'#COMMENT#' => $text,
		]);

		$uiComment->sendMentions(
			$timeline->getId(),
			$timeline->getUserId(),
			$timeline->getDescription(),
			$message,
			$previouslyMentionedUserIds
		);
	}
}
