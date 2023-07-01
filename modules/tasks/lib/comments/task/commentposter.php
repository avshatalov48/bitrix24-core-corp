<?php
namespace Bitrix\Tasks\Comments\Task;

use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\StringHelper;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Access;
use Bitrix\Tasks\Comments\Internals\Comment;
use Bitrix\Tasks\Integration\CRM;
use Bitrix\Tasks\Integration\Disk;
use Bitrix\Tasks\Integration\Forum;
use Bitrix\Tasks\Integration\Mail;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Util\Collection;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Util\UserField;

Loc::loadMessages(__FILE__);

/**
 * Class CommentPoster
 *
 * @package Bitrix\Tasks\Comments\Task
 */
class CommentPoster
{
	private static $instances = [];
	private static $taskNames = [];
	private static $userNames = [];
	private static $groupNames = [];

	private $taskId;
	private $authorId;
	private $comments;
	private $deferredPostMode = false;

	/**
	 * CommentPoster constructor.
	 *
	 * @param int $taskId
	 * @param int $authorId
	 */
	protected function __construct(int $taskId, int $authorId)
	{
		$this->taskId = $taskId;
		$this->authorId = $authorId;
		$this->comments = new Collection();

		$this->disableDeferredPostMode();
	}

	/**
	 * Returns current post mode state.
	 *
	 * @return bool
	 */
	public function getDeferredPostMode(): bool
	{
		return $this->deferredPostMode;
	}

	/**
	 * Enables deferred post mode.
	 */
	public function enableDeferredPostMode(): void
	{
		$this->deferredPostMode = true;
	}

	/**
	 * Disables deferred post mode.
	 */
	public function disableDeferredPostMode(): void
	{
		$this->deferredPostMode = false;
	}

	/**
	 * Returns existing class instance or creates new.
	 *
	 * @param int $taskId
	 * @param int $authorId
	 * @return CommentPoster|null
	 */
	public static function getInstance(int $taskId, int $authorId): ?CommentPoster
	{
		if (!$taskId)
		{
			return null;
		}

		if (!isset(static::$instances[$taskId][$authorId]))
		{
			static::$instances[$taskId][$authorId] = new static($taskId, $authorId);
		}

		return static::$instances[$taskId][$authorId];
	}

	/**
	 * Pushes new comments to collection.
	 *
	 * @param array $comments
	 */
	public function addComments(array $comments): void
	{
		foreach ($comments as $comment)
		{
			/** @var Comment $comment */
			$this->comments->push($comment);
		}
	}

	/**
	 * Returns first comment of given type from collection or null if there is no comments of such type.
	 *
	 * @param int $type
	 * @return Comment|null
	 */
	public function getCommentByType($type = Comment::TYPE_DEFAULT): ?Comment
	{
		foreach ($this->comments as $comment)
		{
			/** @var Comment $comment */
			if ($comment->getType() === $type)
			{
				return $comment;
			}
		}

		return null;
	}

	/**
	 * Clears comment collection.
	 */
	public function clearComments(): void
	{
		$this->comments->clear();
	}

	/**
	 * @return Comment
	 */
	private function getNewChangeComment(): Comment
	{
		// $authorName = $this->getUserNames([$this->authorId])[$this->authorId];
		//
		// $changeMessageKey = 'COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES';
		// $changeMessage = Loc::getMessage($changeMessageKey, ['#AUTHOR#' => $authorName]);

		return new Comment('', $this->authorId, Comment::TYPE_UPDATE, []);
	}

	/**
	 * Appends a message about checklist changes.
	 */
	public function appendChecklistChangesMessage(): void
	{
		if (!($changeComment = $this->getCommentByType(Comment::TYPE_UPDATE)))
		{
			$changeComment = $this->getNewChangeComment();
			$this->addComments([$changeComment]);
		}

		$partName = 'checklist';
		if (!$changeComment->isPartExist($partName))
		{
			$code = 'COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_FIELD_CHECKLIST';
			$changeComment->addPart(
				$partName,
				Loc::getMessage($code),
				[ $code, []]
			);
		}
	}

	/**
	 * @param Comment $changeComment
	 * @param array $changes
	 */
	private function appendCrmElementChangesMessage(Comment $changeComment, array $changes): void
	{
		$partName = 'crm';
		$fieldKey = 'COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_FIELD_CRM';

		if (!$changeComment->isPartExist($partName))
		{
			$changeComment->addPart(
				$partName,
				Loc::getMessage($fieldKey)."\n",
				[ $fieldKey, [] ]
			);
		}

		$crmElementChanges = $this->prepareCrmElementChanges($changes['UF_CRM_TASK']);
		foreach ($crmElementChanges as $type => $change)
		{
			$replace = [
				'#OLD_VALUE#' => $change['OLD'],
				'#NEW_VALUE#' => $change['NEW'],
			];
			$changeComment->appendPartData($partName, [ "{$fieldKey}_{$type}", $replace ]);
			$changeComment->appendPartText(
				$partName,
				Loc::getMessage("{$fieldKey}_{$type}", $replace)."\n"
			);
		}
	}

	/**
	 * @param array $values
	 * @return array
	 */
	private function prepareCrmElementChanges(array $values): array
	{
		$collection = [];

		$oldElements = (explode(',', $values['FROM_VALUE']) ?: []);
		$newElements = (explode(',', $values['TO_VALUE']) ?: []);

		$uniqueElements = array_unique(array_merge($oldElements, $newElements));
		sort($uniqueElements);

		foreach ($uniqueElements as $element)
		{
			[$type, $id] = explode('_', $element);
			$typeId = \CCrmOwnerType::ResolveID(\CCrmOwnerTypeAbbr::ResolveName($type));
			$title = \CCrmOwnerType::GetCaption($typeId, $id);
			$url = \CCrmOwnerType::GetEntityShowPath($typeId, $id);

			if (!isset($collection[$type]))
			{
				$collection[$type] = [];
			}
			$title = ($title ?: $element);
			if ($title)
			{
				$title = htmlspecialcharsbx($title);
				$collection[$type][$element] = "<a href='{$url}'>{$title}</a>";
			}
		}

		$result = [];
		$noValue = Loc::getMessage('COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_FIELD_VALUE_NO');

		foreach ($collection as $type => $typeElements)
		{
			$old = array_intersect_key($typeElements, array_flip($oldElements));
			$new = array_intersect_key($typeElements, array_flip($newElements));

			if (!array_diff_key($old, $new) && !array_diff_key($new, $old))
			{
				continue;
			}

			$result[$type] = [
				'OLD' => (empty($old) ? $noValue : implode(', ', $old)),
				'NEW' => (empty($new) ? $noValue : implode(', ', $new)),
			];
		}

		return $result;
	}

	/**
	 * @param Comment $changeComment
	 * @param array $changes
	 */
	private function appendUserFieldChangesMessage(Comment $changeComment, array $changes): void
	{
		$partName = 'userField';
		$fieldKey = 'COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_FIELD_USER_FIELD';

		if (!$changeComment->isPartExist($partName))
		{
			$changeComment->addPart(
				$partName,
				Loc::getMessage($fieldKey)."\n",
				[ $fieldKey, []]
			);
		}

		$userFieldChanges = $this->prepareUserFieldChanges($changes);
		foreach ($userFieldChanges as $name => $change)
		{
			$replace = [
				'#NAME#' => $name,
				'#OLD_VALUE#' => $change['OLD'],
				'#NEW_VALUE#' => $change['NEW'],
			];
			$changeComment->appendPartData(
				$partName,
				[ "{$fieldKey}_TEMPLATE", $replace ]
			);
			$changeComment->appendPartText(
				$partName,
				Loc::getMessage("{$fieldKey}_TEMPLATE", $replace)."\n"
			);
		}
	}

	/**
	 * @param array $changes
	 * @return array
	 */
	private function prepareUserFieldChanges(array $changes): array
	{
		$systemUserFields = $this->getSystemFieldCodes();
		$fn = static function ($field) use ($systemUserFields) {
			return mb_strpos($field, 'UF_') === 0 && !in_array($field, $systemUserFields, true);
		};
		$ufScheme = UserField::getScheme(UserField\Task::getEntityCode(), $this->authorId);
		$userFields = array_intersect_key($ufScheme, array_filter($changes, $fn, ARRAY_FILTER_USE_KEY));

		$result = [];
		foreach ($userFields as $name => $data)
		{
			$result[$data['EDIT_FORM_LABEL']] = [
				'OLD' => $changes[$name]['FROM_VALUE'],
				'NEW' => $changes[$name]['TO_VALUE'],
			];
		}

		return $result;
	}

	/**
	 * @param array $taskData
	 * @return Comment[]
	 */
	private function prepareCommentsOnTaskAdd(array $taskData): array
	{
		$creatorId = (int)$taskData['CREATED_BY'];
		$responsibleId = (int)$taskData['RESPONSIBLE_ID'];
		$accomplices = (array)$taskData['ACCOMPLICES'];
		$auditors = (array)$taskData['AUDITORS'];

		if (in_array($this->authorId, $accomplices, true))
		{
			unset($accomplices[array_search($this->authorId, $accomplices, true)]);
		}
		if (in_array($this->authorId, $auditors, true))
		{
			unset($auditors[array_search($this->authorId, $auditors, true)]);
		}

		$addComments = [];

		if (
			$this->authorId === $creatorId
			&& $creatorId === $responsibleId
			&& empty($accomplices)
			&& empty($auditors)
		)
		{
			return $addComments;
		}

		if (!($addComment = $this->getCommentByType(Comment::TYPE_ADD)))
		{
			$addComment = new Comment('', $this->authorId, Comment::TYPE_ADD);
			$addComment->deletePart('main');
			$addComments[] = $addComment;
		}

		$userToLinkFunction = function (int $userId) {
			return $this->parseUserToLinked($userId);
		};

		if (
			$creatorId !== $responsibleId
			&&
			(
				$taskData['TASK_CONTROL'] === 'Y'
				|| $taskData['TASK_CONTROL'] === true
			)
		)
		{
			$messageKey = 'COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_CONTROL';
			$messageKey = $this->getLastVersionedMessageKey($messageKey);
			$addComment->addPart('control', Loc::getMessage($messageKey), [[$messageKey, []]]);
		}
		if (!$taskData['DEADLINE'])
		{
			$messageKey = 'COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_DEADLINE';
			$messageKey = $this->getLastVersionedMessageKey($messageKey);
			$addComment->addPart('deadline', Loc::getMessage($messageKey), [[$messageKey, []]]);
		}
		if ($this->authorId !== $creatorId)
		{
			$partName = 'creator';
			$messageKey = 'COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_FIELD_CREATED_BY';
			$messageKey = $this->getLastVersionedMessageKey($messageKey);
			$replace = ['#NEW_VALUE#' => $this->parseUserToLinked($creatorId)];
			$addComment->addPart($partName, Loc::getMessage($messageKey, $replace), [[$messageKey, $replace]]);
		}
		if ($this->authorId !== $responsibleId)
		{
			$partName = 'responsible';
			$messageKey = 'COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_FIELD_RESPONSIBLE_ID';
			$messageKey = $this->getLastVersionedMessageKey($messageKey);
			$replace = ['#NEW_VALUE#' => $this->parseUserToLinked($responsibleId)];
			$addComment->addPart($partName, Loc::getMessage($messageKey, $replace), [[$messageKey, $replace]]);
		}
		if (!empty($accomplices))
		{
			$partName = 'accomplices';
			$messageKey = 'COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_FIELD_ACCOMPLICES';
			$messageKey = $this->getLastVersionedMessageKey($messageKey);
			$replace = ['#NEW_VALUE#' => implode(', ', array_map($userToLinkFunction, $accomplices))];
			$addComment->addPart($partName, Loc::getMessage($messageKey, $replace), [[$messageKey, $replace]]);
		}
		if (!empty($auditors))
		{
			$partName = 'auditors';
			$messageKey = 'COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_FIELD_AUDITORS';
			$messageKey = $this->getLastVersionedMessageKey($messageKey);
			$replace = ['#NEW_VALUE#' => implode(', ', array_map($userToLinkFunction, $auditors))];
			$addComment->addPart($partName, Loc::getMessage($messageKey, $replace), [[$messageKey, $replace]]);
		}

		return $addComments;
	}

	/**
	 * @param array $oldFields
	 * @param array $newFields
	 * @param array $changes
	 * @return array|Comment[]
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function prepareCommentsOnTaskUpdate(array $oldFields, array $newFields, array $changes): array
	{
		if (empty($changes))
		{
			return [];
		}

		return array_merge(
			$this->prepareChangeComments($oldFields, $newFields, $changes),
			$this->prepareStatusComments($oldFields, $newFields, $changes)
		);
	}

	/**
	 * @param array $oldFields
	 * @param array $newFields
	 * @param array $changes
	 * @return array|Comment[]
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function prepareChangeComments(array $oldFields, array $newFields, array $changes): array
	{
		unset($changes['STATUS']);

		if (empty($changes))
		{
			return [];
		}

		$changeComments = [];

		if (!($changeComment = $this->getCommentByType(Comment::TYPE_UPDATE)))
		{
			$changeComment = $this->getNewChangeComment();
			$changeComments[] = $changeComment;
		}

		$appendCrmFields = false;
		$appendUserFields = false;

		foreach ($changes as $field => $values)
		{
			$liveParams = [];

			switch ($field)
			{
				case 'UF_TASK_WEBDAV_FILES':
					$field = 'FILES';
					break;

				case 'UF_CRM_TASK':
					$appendCrmFields = true;
					continue 2;

				case 'DEADLINE':
					$liveParams = $this->prepareChangeCommentLiveParams(array_merge($oldFields, $newFields));
					break;

				default:
					if (mb_strpos($field, 'UF_') === 0)
					{
						$appendUserFields = true;
						continue 2;
					}
					break;
			}

			$values = $this->getFieldValues($field, $values);
			if ($values['NEW'] === false)
			{
				continue;
			}

			$fieldKey = "COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_FIELD_{$field}";
			$fieldKey = $this->getLastVersionedMessageKey($fieldKey);
			$fieldReplaces = [
				'#OLD_VALUE#' => $values['OLD'],
				'#NEW_VALUE#' => $values['NEW'],
			];
			$changeComment->appendPartData('changes', [$fieldKey, array_merge($fieldReplaces, $liveParams)]);

			$field = (Loc::getMessage($fieldKey, $fieldReplaces) ?: $field);
			$changeComment->appendPartText('changes', $field."\n");
		}

		if ($appendCrmFields)
		{
			$this->appendCrmElementChangesMessage($changeComment, $changes);
		}
		if ($appendUserFields)
		{
			$this->appendUserFieldChangesMessage($changeComment, $changes);
		}

		$deadlineChanged = array_key_exists('DEADLINE', $changes);
		$responsibleChanged = array_key_exists('RESPONSIBLE_ID', $changes);

		if (
			($deadlineChanged && !$newFields['DEADLINE'])
			|| ($responsibleChanged && !$deadlineChanged && !$oldFields['DEADLINE'])
		)
		{
			$partName = 'deadline';
			$deadlineMessageKey = 'COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_DEADLINE';
			$deadlineMessageKey = $this->getLastVersionedMessageKey($deadlineMessageKey);
			$liveParams = $this->prepareChangeCommentLiveParams(array_merge($oldFields, $newFields));
			$changeComment->addPart($partName, Loc::getMessage($deadlineMessageKey), [[$deadlineMessageKey, $liveParams]]);
		}

		return $changeComments;
	}

	/**
	 * Returns array of old and new field value.
	 *
	 * @param string $field
	 * @param array $values
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getFieldValues(string $field, array $values): array
	{
		$old = $values['FROM_VALUE'];
		$new = $values['TO_VALUE'];

		$noValue = Loc::getMessage('COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_FIELD_VALUE_NOT_PRESENT_SINGLE_M');

		switch ($field)
		{
			case 'MARK':
			case 'PRIORITY':
			case 'TASK_CONTROL':
			case 'ALLOW_TIME_TRACKING':
			case 'ALLOW_CHANGE_DEADLINE':
				$new = Loc::getMessage("COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_FIELD_{$field}_{$new}");
				break;

			case 'PARENT_ID':
				$tasks = array_filter(array_unique($values), static function ($taskId) {
					return (int)$taskId > 0;
				});
				$taskNames = $this->getTaskNames($tasks);
				$old = ($taskNames[$old] ?? $noValue);
				$new = ($taskNames[$new] ?? $noValue);
				break;

			case 'GROUP_ID':
				$groups = array_filter(array_unique($values), static function ($groupId) {
					return (int)$groupId > 0;
				});
				$groupNames = $this->getGroupNames($groups);
				$old = ($old ?: 0);
				$new = ($new ?: 0);
				$old = "[GROUP_ID={$old}]" . ($groupNames[$old] ?? $noValue) . "[/GROUP_ID]";
				$new = "[GROUP_ID={$new}]" . ($groupNames[$new] ?? $noValue) . "[/GROUP_ID]";
				break;

			case 'CREATED_BY':
			case 'RESPONSIBLE_ID':
				$new = ($this->parseUserToLinked($new) ?: $noValue);
				break;

			case 'ACCOMPLICES':
			case 'AUDITORS':
				$userToLinkFunction = function (int $userId) {
					return $this->parseUserToLinked($userId);
				};
				$oldUsers = array_filter((explode(',', (string)$old) ?: []));
				$newUsers = array_filter((explode(',', (string)$new) ?: []));
				$pureNewUsers = array_unique(array_map('intval', array_diff($newUsers, $oldUsers)));
				if (empty($pureNewUsers))
				{
					$new = false;
					break;
				}
				$new = (implode(', ', array_map($userToLinkFunction, $pureNewUsers)) ?: $noValue);
				break;

			case 'DEADLINE':
				$old = ($old ? "#DEADLINE_START#{$old}#DEADLINE_END#" : $noValue);
				$new = ($new ? "#DEADLINE_START#{$new}#DEADLINE_END#" : $noValue);
				break;

			case 'TAGS':
				$new = ($new && $new !== '' ? str_replace(',', ', ', $new) : $noValue);
				break;

			case 'DEPENDS_ON':
				$oldTasks = (explode(',', $old) ?: []);
				$newTasks = (explode(',', $new) ?: []);
				$tasks = array_filter(array_unique(array_merge($oldTasks, $newTasks)), static function ($taskId) {
					return (int)$taskId > 0;
				});
				$taskNames = $this->getTaskNames($tasks);
				$old = (implode(', ', array_intersect_key($taskNames, array_flip($oldTasks))) ?: $noValue);
				$new = (implode(', ', array_intersect_key($taskNames, array_flip($newTasks))) ?: $noValue);
				break;

			case 'TIME_ESTIMATE':
				$old = $this->prepareTimeEstimate(($old ?: 0));
				$new = $this->prepareTimeEstimate(($new ?: 0));
				break;

			case 'START_DATE_PLAN':
			case 'END_DATE_PLAN':
				$old = ($old ?: $noValue);
				$new = ($new ?: $noValue);
			break;

			case 'FILES':
			case 'UF_TASK_WEBDAV_FILES':
			default:
				break;
		}

		return [
			'OLD' => $old,
			'NEW' => $new,
		];
	}

	/**
	 * @param int $seconds
	 * @return string
	 */
	private function prepareTimeEstimate(int $seconds): string
	{
		if (!$seconds)
		{
			return '';
		}

		$minutes = (int)($seconds / 60);
		$hours = (int)($minutes / 60);

		if ($minutes < 60)
		{
			$minutesMessage = Loc::getMessagePlural('TASKS_TASK_DURATION_MINUTES', $minutes);
			$duration = "{$minutes} {$minutesMessage}";
		}
		elseif ($minutesInRemainder = $minutes % 60)
		{
			$hoursMessage = Loc::getMessagePlural('TASKS_TASK_DURATION_HOURS', $hours);
			$minutesMessage = Loc::getMessagePlural('TASKS_TASK_DURATION_MINUTES', $minutesInRemainder);

			$duration = "{$hours} {$hoursMessage} {$minutesInRemainder} {$minutesMessage}";
		}
		else
		{
			$hoursMessage = Loc::getMessagePlural('TASKS_TASK_DURATION_HOURS', $hours);
			$duration = "{$hours} {$hoursMessage}";
		}

		if ($seconds < 3600 && ($secondsInRemainder = $seconds % 60))
		{
			$secondsMessage = Loc::getMessagePlural('TASKS_TASK_DURATION_SECONDS', $secondsInRemainder);
			$duration .= " {$secondsInRemainder} {$secondsMessage}";
		}

		return $duration;
	}

	private function prepareChangeCommentLiveParams(array $taskData): array
	{
		$taskId = (int)$taskData['ID'];
		$culture = Context::getCurrent()->getCulture();
		$deadline = (TaskRegistry::getInstance())->get($taskId)['DEADLINE'];
		$users = array_unique(
			array_merge(
				$this->getTaskMembers($taskData),
				$this->getTaskWatchers($taskId)
			)
		);

		return [
			'LIVE_DATA' => [
				'TASK_ID' => $taskId,
				'DATE_FORMAT' => "{$culture->getDayMonthFormat()}, {$culture->getShortTimeFormat()}",
				'DEADLINE' => ($deadline ? $deadline->getTimestamp() : null),
				'RIGHTS' => [
					'DEADLINE_CHANGE' => $this->getTaskUsersRight(
						$taskId,
						$users,
						Access\ActionDictionary::ACTION_TASK_DEADLINE
					),
				],
			],
		];
	}

	/**
	 * @param array $oldFields
	 * @param array $newFields
	 * @param array $changes
	 * @return array|Comment[]
	 */
	private function prepareStatusComments(array $oldFields, array $newFields, array $changes): array
	{
		$statusComments = [];

		if (!array_key_exists('STATUS', $changes))
		{
			return $statusComments;
		}

		$creatorId = (isset($newFields['CREATED_BY']) ? (int)$newFields['CREATED_BY'] : (int)$oldFields['CREATED_BY']);
		$newStatus = (int)$newFields['STATUS'];
		$oldStatus = (int)$oldFields['REAL_STATUS'];
		$newStatus = ($newStatus === \CTasks::STATE_NEW ? \CTasks::STATE_PENDING : $newStatus);
		$oldStatus = ($oldStatus === \CTasks::STATE_NEW ? \CTasks::STATE_PENDING : $oldStatus);

		$validNewStates = [\CTasks::STATE_PENDING, \CTasks::STATE_SUPPOSEDLY_COMPLETED, \CTasks::STATE_COMPLETED];
		if (!in_array($newStatus, $validNewStates, true))
		{
			return $statusComments;
		}

		$userToLinkFunction = function (int $userId) {
			return $this->parseUserToLinked($userId);
		};
		$messageKey = "COMMENT_POSTER_COMMENT_TASK_UPDATE_STATUS_{$newStatus}";
		$replace = ['#CREATOR#' => $userToLinkFunction($creatorId)];
		$liveParams = $this->prepareStatusCommentLiveParams(array_merge($oldFields, $newFields));

		if ($newStatus === \CTasks::STATE_PENDING)
		{
			$validOldStates = [\CTasks::STATE_SUPPOSEDLY_COMPLETED, \CTasks::STATE_COMPLETED];
			if (!in_array($oldStatus, $validOldStates, true))
			{
				return $statusComments;
			}

			$messageKey = "{$messageKey}_RENEW";
			$taskData = [
				'CREATED_BY' => $creatorId,
				'RESPONSIBLE_ID' => (
					isset($newFields['RESPONSIBLE_ID'])
						? (int)$newFields['RESPONSIBLE_ID']
						: (int)$oldFields['RESPONSIBLE_ID']
				),
				'ACCOMPLICES' => (
					isset($newFields['ACCOMPLICES'])
						? (array)$newFields['ACCOMPLICES']
						: (array)$oldFields['ACCOMPLICES']
				),
			];
			$members = $this->getMembersForExpiredMessages($taskData);
			if (empty($members))
			{
				$messageKey = "{$messageKey}_NO_MEMBERS";
			}
			else
			{
				$replace['#MEMBERS#'] = implode(', ', array_map($userToLinkFunction, $members));
			}
		}
		else if ($newStatus === \CTasks::STATE_COMPLETED && $oldStatus === \CTasks::STATE_SUPPOSEDLY_COMPLETED)
		{
			$messageKey = "{$messageKey}_APPROVE";
		}
		$messageKey = $this->getLastVersionedMessageKey($messageKey);

		$statusComments[] = new Comment(
			Loc::getMessage($messageKey, $replace),
			$this->authorId,
			Comment::TYPE_STATUS,
			[[$messageKey, array_merge($replace, $liveParams)]]
		);

		return $statusComments;
	}

	private function prepareStatusCommentLiveParams(array $taskData): array
	{
		$liveParams = [];

		$newStatus = (int)$taskData['STATUS'];
		$newStatus = ($newStatus === \CTasks::STATE_NEW ? \CTasks::STATE_PENDING : $newStatus);

		if ($newStatus === \CTasks::STATE_SUPPOSEDLY_COMPLETED)
		{
			$taskId = (int)$taskData['ID'];
			$users = array_unique(
				array_merge(
					$this->getTaskMembers($taskData),
					$this->getTaskWatchers($taskId)
				)
			);

			$liveParams = [
				'LIVE_DATA' => [
					'TASK_ID' => $taskId,
					'RIGHTS' => [
						'TASK_APPROVE' => $this->getTaskUsersRight(
							$taskId,
							$users,
							Access\ActionDictionary::ACTION_TASK_APPROVE
						),
						'TASK_DISAPPROVE' => $this->getTaskUsersRight(
							$taskId,
							$users,
							Access\ActionDictionary::ACTION_TASK_DISAPPROVE
						),
					],
				],
			];
		}

		return $liveParams;
	}

	/**
	 * @param array $taskData
	 * @return array|Comment[]
	 */
	private function prepareCommentsOnTaskExpiredSoon(array $taskData): array
	{
		$expiredSoonComments = [];

		if ($this->getCommentByType(Comment::TYPE_EXPIRED_SOON))
		{
			return $expiredSoonComments;
		}

		$members = $this->getMembersForExpiredMessages($taskData);
		if (empty($members))
		{
			$messageKey = 'COMMENT_POSTER_COMMENT_TASK_EXPIRED_SOON_NO_MEMBERS';
			$messageKey = $this->getLastVersionedMessageKey($messageKey);
			$replace = [];
		}
		else
		{
			$userToLinkFunction = function (int $userId) {
				return $this->parseUserToLinked($userId);
			};
			$messageKey = 'COMMENT_POSTER_COMMENT_TASK_EXPIRED_SOON';
			$messageKey = $this->getLastVersionedMessageKey($messageKey);
			$replace = ['#MEMBERS#' => implode(', ', array_map($userToLinkFunction, $members))];
		}

		$liveParams = $this->prepareTaskExpiredSoonCommentLiveParams($taskData);
		$expiredSoonComments[] = new Comment(
			Loc::getMessage($messageKey, $replace),
			$this->authorId,
			Comment::TYPE_EXPIRED_SOON,
			[[$messageKey, array_merge($replace, $liveParams)]]
		);

		return $expiredSoonComments;
	}

	private function prepareTaskExpiredSoonCommentLiveParams(array $taskData): array
	{
		return $this->prepareTaskExpiredCommentLiveParams($taskData);
	}

	/**
	 * @param array $taskData
	 * @return Comment[]
	 */
	private function prepareCommentsOnTaskExpired(array $taskData): array
	{
		$expiredComments = [];

		if ($this->getCommentByType(Comment::TYPE_EXPIRED))
		{
			return $expiredComments;
		}

		$members = $this->getMembersForExpiredMessages($taskData);
		if (empty($members))
		{
			$messageKey = 'COMMENT_POSTER_COMMENT_TASK_EXPIRED_NO_MEMBERS';
			$messageKey = $this->getLastVersionedMessageKey($messageKey);
			$replace = [];
		}
		else
		{
			$userToLinkFunction = function (int $userId) {
				return $this->parseUserToLinked($userId);
			};
			$messageKey = 'COMMENT_POSTER_COMMENT_TASK_EXPIRED';
			$messageKey = $this->getLastVersionedMessageKey($messageKey);
			$replace = [
				'#MEMBERS#' => implode(', ', array_map($userToLinkFunction, $members)),
			];
		}

		$liveParams = $this->prepareTaskExpiredCommentLiveParams($taskData);
		$expiredComments[] = new Comment(
			Loc::getMessage($messageKey, $replace),
			$this->authorId,
			Comment::TYPE_EXPIRED,
			[[$messageKey, array_merge($replace, $liveParams)]]
		);

		return $expiredComments;
	}

	private function prepareTaskExpiredCommentLiveParams(array $taskData): array
	{
		$taskId = (int)$taskData['ID'];
		$deadline = (TaskRegistry::getInstance())->get($taskId)['DEADLINE'];
		$users = array_unique(
			array_merge(
				$this->getTaskMembers($taskData),
				$this->getTaskWatchers($taskId)
			)
		);

		return [
			'LIVE_DATA' => [
				'TASK_ID' => $taskId,
				'EFFICIENCY_MEMBERS' => $this->getMembersForExpiredMessages($taskData),
				'DEADLINE' => ($deadline ? $deadline->getTimestamp() : null),
				'RIGHTS' => [
					'TASK_COMPLETE' => $this->getTaskUsersRight(
						$taskId,
						$users,
						Access\ActionDictionary::ACTION_TASK_COMPLETE
					),
					'DEADLINE_CHANGE' => $this->getTaskUsersRight(
						$taskId,
						$users,
						Access\ActionDictionary::ACTION_TASK_DEADLINE
					),
				],
			],
		];
	}

	/**
	 * @param array $taskData
	 * @return array
	 */
	private function getMembersForExpiredMessages(array $taskData): array
	{
		$creatorId = (int)$taskData['CREATED_BY'];
		$responsibleId = (int)$taskData['RESPONSIBLE_ID'];
		$accomplices = $taskData['ACCOMPLICES'];
		$accomplices = (is_array($accomplices) ? $accomplices : $accomplices->export());
		$accomplices = array_map('intval', $accomplices);

		if (in_array($creatorId, $accomplices, true))
		{
			unset($accomplices[array_search($creatorId, $accomplices, true)]);
		}

		$members = ($responsibleId !== $creatorId ? [$responsibleId] : []);
		$members = array_unique(array_merge($members, $accomplices));

		return $members;
	}

	/**
	 * @param array $taskData
	 * @return array
	 */
	private function prepareCommentsOnTaskStatusPinged(array $taskData): array
	{
		$pingedStatusComments = [];

		if ($this->getCommentByType(Comment::TYPE_PING_STATUS))
		{
			return $pingedStatusComments;
		}

		$members = $this->getMembersForStatusPingedMessages($taskData);
		$userToLinkFunction = function (int $userId) {
			return $this->parseUserToLinked($userId);
		};
		$messageKey = 'COMMENT_POSTER_COMMENT_TASK_PINGED_STATUS';
		$messageKey = $this->getLastVersionedMessageKey($messageKey);
		$replace = ['#MEMBERS#' => implode(', ', array_map($userToLinkFunction, $members))];
		$message = Loc::getMessage($messageKey, $replace);
		$commentType = Comment::TYPE_PING_STATUS;
		$pingedStatusComments[] = new Comment($message, $this->authorId, $commentType, [[$messageKey, $replace]]);

		return $pingedStatusComments;
	}

	/**
	 * @param array $taskData
	 * @return array
	 */
	private function getMembersForStatusPingedMessages(array $taskData): array
	{
		$responsibleId = (int)$taskData['RESPONSIBLE_ID'];
		$accomplices = $taskData['ACCOMPLICES'];
		$accomplices = (is_array($accomplices) ? $accomplices : $accomplices->export());
		$accomplices = array_map('intval', $accomplices);

		return array_unique(array_merge([$responsibleId], $accomplices));
	}

	/**
	 * Builds and posts comments on task add if deferred post mode is off.
	 *
	 * @param array $taskData
	 */
	public function postCommentsOnTaskAdd(array $taskData): void
	{
		$addComments = $this->prepareCommentsOnTaskAdd($taskData);
		$this->addComments($addComments);

		if ($this->getDeferredPostMode())
		{
			return;
		}

		$this->postComments();
		$this->clearComments();
	}

	/**
	 * Builds and posts comments on task update if deferred post mode is off.
	 *
	 * @param array $oldFields
	 * @param array $newFields
	 * @param array $changes
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function postCommentsOnTaskUpdate(array $oldFields, array $newFields, array $changes): void
	{
		$updateComments = $this->prepareCommentsOnTaskUpdate($oldFields, $newFields, $changes);
		$this->addComments($updateComments);

		if ($this->getDeferredPostMode())
		{
			return;
		}

		$this->postComments();
		$this->clearComments();
	}

	/**
	 * Builds and posts comments on task expired soon if deferred post mode is off.
	 *
	 * @param array $taskData
	 */
	public function postCommentsOnTaskExpiredSoon(array $taskData): void
	{
		$expiredSoonComments = $this->prepareCommentsOnTaskExpiredSoon($taskData);
		$this->addComments($expiredSoonComments);

		if ($this->getDeferredPostMode())
		{
			return;
		}

		$this->postComments();
		$this->clearComments();
	}

	/**
	 * Builds and posts comments on task expired if deferred post mode is off.
	 *
	 * @param array $taskData
	 */
	public function postCommentsOnTaskExpired(array $taskData): void
	{
		$expiredComments = $this->prepareCommentsOnTaskExpired($taskData);
		$this->addComments($expiredComments);

		if ($this->getDeferredPostMode())
		{
			return;
		}

		$this->postComments();
		$this->clearComments();
	}

	public function postCommentsOnTaskStatusPinged(array $taskData): void
	{
		$pingedComments = $this->prepareCommentsOnTaskStatusPinged($taskData);
		$this->addComments($pingedComments);

		if ($this->getDeferredPostMode())
		{
			return;
		}

		$this->postComments();
		$this->clearComments();
	}

	/**
	 * Builds comment text by parts data.
	 *
	 * @param array $partsData
	 * @param array $params
	 * @return string
	 */
	public static function getCommentText(array $partsData, array $params = []): string
	{
		$result = '';
		$textList = [];

		// old comments type compatibility (without AUX_DATA)
		if (isset($partsData['auxData'], $partsData['text']) && $partsData['text'] !== '')
		{
			return $partsData['text'];
		}

		foreach ($partsData as $partsItems)
		{
			if (!is_array($partsItems))
			{
				continue;
			}

			foreach ($partsItems as [$messageCode, $replace])
			{
				if (
					!empty($messageCode)
					&& ($message = Loc::getMessage($messageCode, static::prepareReplaces($replace ?? [])))
				)
				{
					$textList[] = static::parseReplaces($message, $params);
				}
			}
		}

		if (!empty($textList))
		{
			$result = implode("\n", $textList);
		}

		return $result;
	}

	private static function prepareReplaces(array $replaces = []): array
	{
		foreach ($replaces as $key => $replace)
		{
			if (is_array($replace))
			{
				unset($replaces[$key]);
			}
		}

		return $replaces;
	}

	private static function parseReplaces(string $message, array $params): string
	{
		$userId = User::getId();
		$entityTypes = ['TK', 'TASK'];
		if (isset($params['entityType']) && in_array($params['entityType'], $entityTypes, true))
		{
			$taskId = ($params['entityId'] ?? 0);
		}

		$replaces = [
			'EFFICIENCY',
			'DEADLINE',
			'DEADLINE_CHANGE',
			'TASK_APPROVE',
			'TASK_DISAPPROVE',
			'TASK_COMPLETE',
		];
		foreach ($replaces as $key)
		{
			$start = "#{$key}_START#";
			$end = "#{$key}_END#";

			if (
				mb_strpos($message, $start) === false
				&& mb_strpos($message, $end) === false
			)
			{
				continue;
			}

			switch ($key)
			{
				case 'EFFICIENCY':
					preg_match_all('/(?<=\[USER=)\d+(?=])/', $message, $userIds);
					$userIds = array_map('intval', $userIds[0]);
					$replaces =
						in_array($userId, $userIds, true)
							? ["[URL=/company/personal/user/{$userId}/tasks/effective/]", "[/URL]"]
							: ""
					;
					$message = str_replace([$start, $end], $replaces, $message);
					break;

				case 'DEADLINE':
					preg_match_all("/(?<={$start})\d+(?={$end})/", $message, $timestamp);
					if (!($timestamp = (int)$timestamp[0][0]))
					{
						break;
					}
					$culture = Context::getCurrent()->getCulture();
					$format = "{$culture->getDayMonthFormat()}, {$culture->getShortTimeFormat()}";
					$deadline = FormatDate($format, MakeTimeStamp(DateTime::createFromTimestamp($timestamp)));
					$message = str_replace([$timestamp, $start, $end], [$deadline, '', ''], $message);
					break;

				case 'DEADLINE_CHANGE':
				case 'TASK_APPROVE':
				case 'TASK_DISAPPROVE':
				case 'TASK_COMPLETE':
					$actionMap = [
						'DEADLINE_CHANGE' => Access\ActionDictionary::ACTION_TASK_DEADLINE,
						'TASK_APPROVE' => Access\ActionDictionary::ACTION_TASK_APPROVE,
						'TASK_DISAPPROVE' => Access\ActionDictionary::ACTION_TASK_DISAPPROVE,
						'TASK_COMPLETE' => Access\ActionDictionary::ACTION_TASK_COMPLETE,
					];
					$replace = '';
					if (isset($taskId) && Access\TaskAccessController::can($userId, $actionMap[$key], $taskId))
					{
						$actionUrl = static::getCommentActionUrl($userId, $taskId, $key);
						$replace = ["[URL={$actionUrl}]", "[/URL]"];
					}
					$message = str_replace([$start, $end], $replace, $message);
					break;

				default:
					$message = str_replace([$start, $end], '', $message);
					break;
			}
		}

		preg_match_all('/(?<=\[GROUP_ID=)\d+(?=])/', $message, $groupIds);
		foreach ($groupIds[0] as $groupId)
		{
			$message = str_replace(
				["[GROUP_ID={$groupId}]", "[/GROUP_ID]"],
				($groupId > 0 ? ["[URL=/workgroups/group/{$groupId}/]", "[/URL]"] : ['', '']),
				$message
			);
		}

		return $message;
	}

	private static function getCommentActionUrl(int $userId, int $taskId, string $action): string
	{
		$url = new Uri("/company/personal/user/{$userId}/tasks/task/view/{$taskId}/");
		$url->addParams([
			'commentAction' => lcfirst(StringHelper::snake2camel($action)),
		]);

		if (
			$action === 'DEADLINE_CHANGE'
			&& ($deadline = (TaskRegistry::getInstance())->get($taskId)['DEADLINE'])
		)
		{
			$url->addParams([
				'deadline' => $deadline->getTimestamp(),
			]);
		}

		return $url->getUri();
	}

	/**
	 * Posts comments from collection.
	 */
	public function postComments(): void
	{
		foreach ($this->comments as $comment)
		{
			$auxData = $comment->getData();

			$hasLiveData = false;
			if (is_array($auxData))
			{
				foreach ($auxData as $value)
				{
					if (!is_array($value))
					{
						continue;
					}

					foreach ($value as $commentData)
					{
						if (
							!is_array($commentData)
							|| !is_array($commentData[1])
							|| empty($commentData[1]['LIVE_DATA'])
							|| !is_array($commentData[1]['LIVE_DATA'])
						)
						{
							continue;
						}

						$hasLiveData = true;
						break;
					}

					if ($hasLiveData)
					{
						break;
					}
				}
			}

			/** @var Comment $comment */
			Forum\Task\Comment::add($this->taskId, [
				'AUTHOR_ID' => $comment->getAuthorId(),
				'POST_MESSAGE' => $comment->getText(),
				'UF_TASK_COMMENT_TYPE' => $comment->getType(),
				'AUX' => 'Y',
				'AUX_DATA' => $auxData,
				'AUX_LIVE_PARAMS' => ($hasLiveData ? [ 'JSON' => Main\Web\Json::encode($auxData) ] : []),
			]);
		}
	}

	/**
	 * @param int $userId
	 * @return string
	 */
	private function parseUserToLinked(int $userId): string
	{
		$userName = $this->getUserNames([$userId])[$userId];
		return "[USER={$userId}]{$userName}[/USER]";
	}

	/**
	 * @param array $tasks
	 * @return array
	 * @throws \TasksException
	 */
	private function getTaskNames(array $tasks): array
	{
		if (empty($tasks))
		{
			return [];
		}

		$tasksToFind = array_flip(array_diff_key(array_flip($tasks), static::$taskNames));

		if (!empty($tasksToFind))
		{
			[$foundedTasks] = \CTaskItem::fetchList($this->authorId, [], ['ID' => $tasksToFind], [], ['ID', 'TITLE']);
			foreach ($foundedTasks as $task)
			{
				$taskData = $task->getData(false);
				static::$taskNames[$taskData['ID']] = $taskData['TITLE'];
			}
		}

		return array_intersect_key(static::$taskNames, array_flip($tasks));
	}

	/**
	 * @param array $users
	 * @return array
	 */
	private function getUserNames(array $users): array
	{
		if (empty($users))
		{
			return [];
		}

		$usersToFind = array_flip(array_diff_key(array_flip($users), static::$userNames));

		if (!empty($usersToFind))
		{
			$userNames = User::getUserName($usersToFind);
			foreach ($userNames as $userId => $userName)
			{
				static::$userNames[$userId] = $userName;
			}
		}

		return array_intersect_key(static::$userNames, array_flip($users));
	}

	/**
	 * @param array $groups
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getGroupNames(array $groups): array
	{
		if (empty($groups))
		{
			return [];
		}

		$groupsToFind = array_flip(array_diff_key(array_flip($groups), static::$groupNames));

		if (!empty($groupsToFind))
		{
			$foundedGroups = SocialNetwork\Group::getData($groupsToFind);
			foreach ($foundedGroups as $groupId => $groupData)
			{
				static::$groupNames[$groupId] = $groupData['NAME'];
			}
		}

		return array_intersect_key(static::$groupNames, array_flip($groups));
	}

	/**
	 * @return array
	 */
	private function getSystemFieldCodes(): array
	{
		return [
			CRM\UserField::getMainSysUFCode(),
			Disk\UserField::getMainSysUFCode(),
			Mail\UserField::getMainSysUFCode(),
		];
	}

	/**
	 * @param string $baseKey
	 * @return string
	 */
	private function getLastVersionedMessageKey(string $baseKey): string
	{
		$resultKey = $baseKey;

		$version = 2;
		$proceed = true;
		while ($proceed)
		{
			$nextResultKey = "{$baseKey}_V{$version}";
			$message = Loc::getMessage($nextResultKey);
			if ($message !== null)
			{
				$resultKey = $nextResultKey;
			}
			else
			{
				$proceed = false;
			}
			++$version;
		}

		return $resultKey;
	}

	private function getTaskUsersRight(int $taskId, array $userIds, string $right): array
	{
		$rights = [];

		foreach ($userIds as $userId)
		{
			$rights[$userId] = Access\TaskAccessController::can($userId, $right, $taskId);
		}

		return $rights;
	}

	private function getTaskMembers(array $taskData): array
	{
		$creator = (int)$taskData['CREATED_BY'];
		$responsible = (int)$taskData['RESPONSIBLE_ID'];

		$accomplices = ($taskData['ACCOMPLICES'] ?? []);
		$accomplices = (is_array($accomplices) ? $accomplices : $accomplices->export());
		$accomplices = array_map('intval', $accomplices);

		$auditors = ($taskData['AUDITORS'] ?? []);
		$auditors = (is_array($auditors) ? $auditors : $auditors->export());
		$auditors = array_map('intval', $auditors);

		return array_unique(array_merge([$creator, $responsible], $accomplices, $auditors));
	}

	private function getTaskWatchers(int $taskId): array
	{
		return array_map('intval', \CPullWatch::GetUserList("TASK_VIEW_{$taskId}"));
	}
}