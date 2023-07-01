<?php

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline\CommentController;
use Bitrix\Crm\Timeline\CommentEntry;
use Bitrix\Crm\Timeline\Entity\Object\Timeline;
use Bitrix\Crm\Timeline\Entity\TimelineBindingTable;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Main;
use Bitrix\Main\Error;
use CCrmOwnerType;
use CRestUtil;

class Comment extends Base
{
	public const LOAD_FILES_BLOCK = 1;
	public const LOAD_TEXT_CONTENT = 2;

	private const CACHE_TTL = 3600;

	/**
	 * @var TimelineTable
	 */
	protected TimelineTable $timelineEntity;

	/**
	 * @var TimelineBindingTable
	 */
	protected TimelineBindingTable $timelineBindingEntity;

	final protected function init(): void
	{
		parent::init();

		$this->timelineEntity = new TimelineTable();
		$this->timelineBindingEntity = new TimelineBindingTable();
    }

	/**
	 * 'crm.timeline.comment.load' method handler.
	 *
	 * @param int $commentId
	 * @param int $ownerTypeId
	 * @param int $ownerId
	 * @param int $options
	 *
	 * @return array|null
	 */
	public function loadAction(int $commentId, int $ownerTypeId, int $ownerId, int $options = 0): ?array
	{
		if ($this->getScope() !== static::SCOPE_AJAX)
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		if (!$this->assertValidCommentRecord($commentId))
		{
			return null;
		}

		if (!$this->assertValidOwner($ownerId, $ownerTypeId, false))
		{
			return null;
		}

		$html = '';

		if ($options & self::LOAD_FILES_BLOCK)
		{
			$html = CommentController::getFileBlock($commentId);
		}

		if ($options & self::LOAD_TEXT_CONTENT)
		{
			$commentData = $this->load($commentId);
			$result = CommentController::convertToHtml(
				[
					'ID' => $commentData->getId(),
					'CREATED' => $commentData->getCreated(),
					'AUTHOR_ID' => $commentData->getAuthorId(),
					'COMMENT' => $commentData->getComment(),
					'SETTINGS' => $commentData->getSettings(),
				],
				['INCLUDE_FILES' => 'Y']
			);
			$html = $result['COMMENT'];
		}

		return [
			'commentId' => $commentId,
			'html' => $html,
		];
	}

	/**
	 * 'crm.timeline.comment.add' method handler. Compatible with rest-api.
	 *
	 * @param array $fields Comment fields to add, array type used for compatibility with rest api
	 *
	 * @return int|null
	 */
	public function addAction(array $fields): ?int
	{
		[$ownerId, $ownerTypeId, $content, $authorId, $filesList] = $this->fetchFieldsToAdd($fields);
		if (!$this->assertValidOwner($ownerId, $ownerTypeId))
		{
			return null;
		}

		if (!$this->assertValidCommentContent($content))
		{
			return null;
		}

		return $this->add($ownerTypeId, $ownerId, $content, $filesList, $authorId);
	}

	/**
	 * 'crm.timeline.comment.update' method handler. Compatible with rest-api.
	 *
	 * @param mixed $id				Comment ID
	 * @param array $fields			Comment fields to update
	 * @param int|null $ownerTypeId
	 * @param int|null $ownerId
	 *
	 * @return int|null
	 */
	public function updateAction($id, array $fields, int $ownerTypeId = null, int $ownerId = null): ?int
	{
		$id = is_numeric($id) ? (int)$id : null;
		if (!$this->assertValidCommentRecord($id))
		{
			return null;
		}

		// compatibility layer to previous version rest-api
		if (!isset($ownerId, $ownerTypeId))
		{
			[$ownerId, $ownerTypeId] = $this->detectOwnerIds($id);
		}
		
		if (!$this->assertValidOwner($ownerId, $ownerTypeId))
		{
			return null;
		}

		[$content, $filesList] = $this->fetchFieldsToUpdate($fields);

		if (!$this->assertValidCommentContent($content))
		{
			return null;
		}

		[$isBindingsExist, $bindings] = $this->detectBindings($id, $ownerId, $ownerTypeId);
		if (!$isBindingsExist)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		return $this->update($id, $ownerTypeId, $ownerId, $content, $filesList, $bindings);
	}

	/**
	 * 'crm.timeline.comment.delete' method handler. Compatible with rest-api.
 	 *
	 * @param mixed $id 			Comment ID
	 * @param int|null $ownerTypeId
	 * @param int|null $ownerId
	 *
	 * @return void
	 */
	public function deleteAction($id, int $ownerTypeId = null, int $ownerId = null): void
	{
		$id = is_numeric($id) ? (int)$id : null;
		if (!$this->assertValidCommentRecord($id))
		{
			return;
		}

		// compatibility layer to previous version rest-api
		$loadedBindings = $this->loadBindings($id);
		if (!isset($ownerId, $ownerTypeId))
		{
			if (count($loadedBindings) > 1)
			{
				$this->addError(ErrorCode::getMultipleBindingsError());

				return;
			}

			$ownerId = (int)($loadedBindings[0]['ENTITY_ID'] ?? 0);
			$ownerTypeId = (int)($loadedBindings[0]['ENTITY_TYPE_ID'] ?? 0);
		}

		if (!$this->assertValidOwner($ownerId, $ownerTypeId))
		{
			return;
		}

		[$isBindingsExist, $bindings] = $this->detectBindings($id, $ownerId, $ownerTypeId);
		if (!$isBindingsExist)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return;
		}

		$this->delete($id, $ownerTypeId, $ownerId, $bindings);
	}

	//region CRUD methods
	protected function add(int $ownerTypeId, int $ownerId, string $content, array $filesList, ?int $authorId): ?int
	{
		$commentId = CommentEntry::create([
			'TEXT' => $content,
			'FILES' => $filesList,
			'SETTINGS' => ['HAS_FILES' => empty($filesList) ? 'N' : 'Y'],
			'BINDINGS' => [['ENTITY_TYPE_ID' => $ownerTypeId, 'ENTITY_ID' => $ownerId]],
			'AUTHOR_ID' => $authorId,
		]);
		if ($commentId <= 0)
		{
			$this->addError(new Error('Could not create comment', ErrorCode::ADDING_DISABLED));

			return null;
		}

		CommentController::getInstance()->onCreate(
			$commentId,
			[
				'COMMENT' => $content,
				'ENTITY_TYPE_ID' => $ownerTypeId,
				'ENTITY_ID' => $ownerId,
			]
		);

		return $commentId;
	}

	protected function copy(int $commentId, int $ownerTypeId, int $ownerId, array $filesList): ?int
	{
		$commentData = $this->load($commentId);

		$newCommentId = CommentEntry::create([
			'CREATED' => $commentData->getCreated(),
			'AUTHOR_ID' => $commentData->getAuthorId(),
			'SETTINGS' => $commentData->getSettings(),
			'TEXT' => $commentData->getComment(),
			'FILES' => $filesList,
			'BINDINGS' => [
				[
					'ENTITY_TYPE_ID' => $ownerTypeId,
					'ENTITY_ID' => $ownerId
				]
			]
		]);

		$bindingDelete = $this->timelineBindingEntity::delete(array(
			'OWNER_ID' => $commentId,
			'ENTITY_ID' => $ownerId,
			'ENTITY_TYPE_ID' => $ownerTypeId,
		));

		if ($bindingDelete->isSuccess())
		{
			CommentController::getInstance()->sendPullEventOnDelete(
				new ItemIdentifier($ownerTypeId, $ownerId), $commentId
			);

			CommentController::getInstance()->sendPullEventOnAdd(
				new ItemIdentifier($ownerTypeId, $ownerId), $newCommentId
			);

			$commentId = $newCommentId;
		}

		return $commentId;
	}

	protected function update(int $commentId, int $ownerTypeId, int $ownerId, string $content, array $filesList, array $bindings): ?int
	{
		$oldContent = $this->load($commentId)->getComment();

		if (count($bindings) > 1)
		{
			$commentId = $this->copy($commentId, $ownerTypeId, $ownerId, $filesList);
		}

		$updateResult = CommentEntry::update($commentId, [
			'COMMENT' => $content,
			'SETTINGS' => ['HAS_FILES' => empty($filesList) ? 'N' : 'Y'],
			'FILES' => $filesList,
		]);

		if ($updateResult->isSuccess())
		{
			CommentController::getInstance()->onModify($commentId, [
				'COMMENT' => $content,
				'ENTITY_TYPE_ID' => $ownerTypeId,
				'ENTITY_ID' => $ownerId,
				'OLD_MENTION_LIST' => CommentController::getMentionIds($oldContent)
			]);

			return $updateResult->getId();
		}

		$this->addErrors($updateResult->getErrors());

		return null;
	}

	protected function delete(int $commentId, int $ownerTypeId, int $ownerId, array $bindings): void
	{
		if (count($bindings) > 1)
		{
			$deleteResult = $this->timelineBindingEntity::delete([
				'OWNER_ID' => $commentId,
				'ENTITY_ID' => $ownerId,
				'ENTITY_TYPE_ID' => $ownerTypeId,
			]);
		}
		else
		{
			$deleteResult = CommentEntry::delete($commentId);
		}

		if ($deleteResult->isSuccess())
		{
			CommentController::getInstance()->onDelete(
				$commentId,
				[
					'ENTITY_TYPE_ID' => $ownerTypeId,
					'ENTITY_ID' => $ownerId,
				]
			);
		}
		else
		{
			$this->addErrors($deleteResult->getErrors());
		}
	}

	protected function load(int $commentId): ?Timeline
	{
		return TimelineTable::query()
			->setSelect(['*'])
			->where('ID', $commentId)
			->where('TYPE_ID', TimelineType::COMMENT)
			->setCacheTtl(self::CACHE_TTL)
			->fetchObject();
	}

	protected function loadBindings(int $commentId): array
	{
		return $this->timelineBindingEntity::getList(
			[
				'filter' => ['=OWNER_ID' => $commentId],
				'cache' => ['ttl' => self::CACHE_TTL],
			]
		)->fetchAll();
	}
	//endregion

	//region validation methods
	protected function isAllowedAction(int $ownerTypeId, int $ownerId, bool $checkUpdatePermission = true): bool
	{
		$userPermissions = Container::getInstance()->getUserPermissions();

		if ($checkUpdatePermission)
		{
			$isActionAllowed = $userPermissions->checkUpdatePermissions($ownerTypeId, $ownerId);
		}
		else
		{
			$isActionAllowed = $userPermissions->checkReadPermissions($ownerTypeId, $ownerId);
		}

		if ($isActionAllowed)
		{
			return true;
		}

		$this->addError(ErrorCode::getAccessDeniedError());

		return false;
	}

	private function assertValidOwner(int $ownerId, int $ownerTypeId, bool $checkUpdatePermission = true): bool
	{
		if ($ownerId <= 0 || !CCrmOwnerType::IsDefined($ownerTypeId))
		{
			$this->addError(ErrorCode::getOwnerNotFoundError());

			return false;
		}

		if (!$this->isAllowedAction($ownerTypeId, $ownerId, $checkUpdatePermission))
		{
			return false;
		}

		return true;
	}

	private function assertValidCommentRecord(?int $commentId): bool
	{
		if (!isset($commentId))
		{
			$this->addError(ErrorCode::getNotFoundError());

			return false;
		}

		$comment = $this->load($commentId);
		if (!isset($comment))
		{
			$this->addError(ErrorCode::getNotFoundError());

			return false;
		}

		$loadedBindings = $this->loadBindings($commentId);
		if (count($loadedBindings) === 0)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return false;
		}

		return true;
    }

	private function assertValidCommentContent(string $content): bool
	{
		if ($content === '')
		{
			$this->addError(new Error('Empty comment message', ErrorCode::INVALID_ARG_VALUE));

			return false;
		}

		return true;
	}
	//endregion

	//region fetch methods
	private function fetchFieldsToAdd(array $fields): array
	{
		$ownerId = (int)($fields['ENTITY_ID'] ?? 0);
		$ownerType = (string)($fields['ENTITY_TYPE'] ?? '');
		$ownerTypeId = (int)($fields['ENTITY_TYPE_ID'] ?? 0);
		if ($ownerTypeId === 0 && !empty($ownerType))
		{
			$ownerTypeId = CCrmOwnerType::ResolveID($ownerType);
		}

		$content = trim((string)($fields['COMMENT'] ?? ''));

		$authorId = (int)($fields['AUTHOR_ID']?? 0);
		if ($authorId <= 0)
		{
			$authorId = Container::getInstance()->getContext()->getUserId();
		}

		$loadedFiles = isset($fields['FILES']) && is_array($fields['FILES'])
			? $fields['FILES']
			: [];
		$filesList = $this->fetchFileIds($loadedFiles, $authorId);
		if (empty($filesList))
		{
			$filesList = isset($fields['ATTACHMENTS']) && is_array($fields['ATTACHMENTS'])
				? $fields['ATTACHMENTS']
				: [];
		}

		$filesList = array_values(array_filter($filesList));

		return [$ownerId, $ownerTypeId, $content, $authorId, $filesList];
	}

	private function fetchFieldsToUpdate(array $fields): array
	{
		$content = trim((string)($fields['COMMENT'] ?? ''));

		$loadedFiles = isset($fields['FILES']) && is_array($fields['FILES'])
			? $fields['FILES']
			: [];
		$filesList = $this->fetchFileIds(
			$loadedFiles,
			Container::getInstance()->getContext()->getUserId()
		);
		if (empty($filesList))
		{
			$filesList = isset($fields['ATTACHMENTS']) && is_array($fields['ATTACHMENTS'])
				? $fields['ATTACHMENTS']
				: [];
		}

		$filesList = array_values(array_filter($filesList));

		return [$content, $filesList];

	}

	private function detectOwnerIds(int $commentId): array
	{
		$loadedBindings = $this->loadBindings($commentId);
		if (count($loadedBindings) === 0)
        {
			return [];
		}

		$ownerId = null;
		$ownerTypeId = null;
		foreach ($loadedBindings as $bindingData)
		{
			$ownerId = (int)$bindingData['ENTITY_ID'];
			$ownerTypeId = (int)$bindingData['ENTITY_TYPE_ID'];
			if ($this->isAllowedAction($ownerTypeId, $ownerId))
			{
				break;
			}
		}

		return [$ownerId, $ownerTypeId];
	}

	private function detectBindings(int $commentId, int $ownerId, int $ownerTypeId): array
	{
		$loadedBindings = $this->loadBindings($commentId);
		$bindings = [];
		$isBindingsExist = false;
		foreach ($loadedBindings as $bindingData)
		{
			if (
				(int)$bindingData['ENTITY_TYPE_ID'] === $ownerTypeId
				&& (int)$bindingData['ENTITY_ID'] === $ownerId
			)
			{
				$isBindingsExist = true;
			}

			$bindings[] = $bindingData;
		}

		return [$isBindingsExist, $bindings];
	}

	private function fetchFileIds(array $loadedFiles, ?int $authorId): array
	{
		$filesList = [];

		if (
			count($loadedFiles) > 0
			&& Main\Config\Option::get('disk', 'successfully_converted', false)
			&& Main\Loader::includeModule('disk')
			&& ($storage = Driver::getInstance()->getStorageByUserId($authorId))
			&& ($folder = $storage->getFolderForUploadedFiles())
		)
		{
			foreach($loadedFiles as $tmp)
			{
				$fileFields = CRestUtil::saveFile($tmp);
				if (is_array($fileFields))
				{
					$file = $folder->uploadFile(
						$fileFields,
						[
							'NAME' => $fileFields['name'],
							'CREATED_BY' => $authorId
						],
						[],
						true
					);

					if ($file)
					{
						$filesList[] = FileUserType::NEW_FILE_PREFIX . $file->getId();
					}
				}
			}
		}

		return $filesList;
	}
	//endregion
}
