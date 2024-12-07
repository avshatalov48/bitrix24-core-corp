<?php
namespace Bitrix\Tasks\Comments\Task;

use Bitrix\Main;
use Bitrix\Tasks\Comments;
use Bitrix\Tasks\Comments\Internals\Comment;
use Bitrix\Tasks\Internals\Log\Log;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\Task\ViewedTable;
use Bitrix\Tasks\Util\User;

/**
 * Class CommentReader
 *
 * @package Bitrix\Tasks\Comments\Task
 */
class CommentReader
{
	private static $instances = [];
	private static $userNames = [];

	private $taskId;
	private $commentId;
	/** @var Comment $comment */
	private $comment;
	private $members;

	/**
	 * CommentReader constructor.
	 *
	 * @param int $taskId
	 * @param int $commentId
	 */
	protected function __construct(int $taskId, int $commentId)
	{
		$this->taskId = $taskId;
		$this->commentId = $commentId;

		$this->fillData();
	}

	/**
	 * @param int $taskId
	 * @param int $commentId
	 * @return static
	 */
	public static function getInstance(int $taskId, int $commentId = 0): self
	{
		if (!isset(self::$instances[$taskId]))
		{
			self::$instances[$taskId] = new self($taskId, $commentId);
		}
		return self::$instances[$taskId];
	}

	private function fillData(): void
	{
		$this->fillTaskData();
		$this->fillCommentData();
	}

	private function fillTaskData(): void
	{
		if (!$this->taskId)
		{
			return;
		}

		$task = TaskRegistry::getInstance()->get($this->taskId, true);
		if (!$task)
		{
			return;
		}

		$this->members = $task['MEMBER_LIST'];
		$this->getUserNames(array_map(
			static function($member) {
				return $member['USER_ID'];
			},
			$this->members
		));
	}

	private function fillCommentData(): void
	{
		if (!$this->commentId)
		{
			return;
		}
	}

	/**
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function read(): void
	{
		$usersToSkipReading = $this->getUsersToSkipReading();
		$commentsCountByUser = [];

		foreach ($this->members as $member)
		{
			$memberId = (int) $member['USER_ID'];

			if (
				$memberId < 1
				|| array_key_exists($memberId, $commentsCountByUser)
				|| in_array($memberId, $usersToSkipReading, true)
			)
			{
				continue;
			}

			$commentsCount = Comments\Task::getNewCommentsCountForTasks([$this->taskId], $memberId);
			if (!array_key_exists($this->taskId, $commentsCount))
			{
				continue;
			}
			$commentsCount = $commentsCount[$this->taskId];

			$commentsCountByUser[$memberId] = $commentsCount;

			if ($commentsCount <= 1)
			{
				ViewedTable::set($this->taskId, $memberId);
			}
		}
	}

	/**
	 * @return array
	 */
	private function getUsersToSkipReading(): array
	{
		$usersToSkipReading = [];

		foreach ($this->comment->getData() as $part)
		{
			if (!is_array($part) || empty($part))
			{
				continue;
			}

			[$code, $replaces] = $part;
			switch ($code)
			{
				case 'COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_FIELD_DEADLINE_V2':
				case 'COMMENT_POSTER_COMMENT_TASK_EXPIRED_V2':
					foreach ($this->members as $member)
					{
						if ($member['TYPE'] !== MemberTable::MEMBER_TYPE_AUDITOR)
						{
							$usersToSkipReading[] = $member['USER_ID'];
						}
					}
					break;

				case 'COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_FIELD_RESPONSIBLE_ID':
				case 'COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_FIELD_ASSIGNEE':
				case 'COMMENT_POSTER_COMMENT_TASK_ADD_TO_FLOW_WITH_MANUAL_DISTRIBUTION':
					foreach ($this->members as $member)
					{
						if ($member['TYPE'] === MemberTable::MEMBER_TYPE_RESPONSIBLE)
						{
							$usersToSkipReading[] = $member['USER_ID'];
						}
					}
					break;

				case 'COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_FIELD_CREATED_BY':
					foreach ($this->members as $member)
					{
						if ($member['TYPE'] === MemberTable::MEMBER_TYPE_ORIGINATOR)
						{
							$usersToSkipReading[] = $member['USER_ID'];
						}
					}
					break;

				case 'COMMENT_POSTER_COMMENT_TASK_EXPIRED_SOON_V2':
				case 'COMMENT_POSTER_COMMENT_TASK_EXPIRED_SOON_NO_MEMBERS_V2':
				case 'COMMENT_POSTER_COMMENT_TASK_PINGED_STATUS_MSGVER_1':
					$rolesToSkip = [
						MemberTable::MEMBER_TYPE_RESPONSIBLE,
						MemberTable::MEMBER_TYPE_ACCOMPLICE,
					];
					foreach ($this->members as $member)
					{
						if (in_array($member['TYPE'], $rolesToSkip, true))
						{
							$usersToSkipReading[] = $member['USER_ID'];
						}
					}
					break;

				case 'COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_FIELD_ACCOMPLICES':
					$ids = [];
					preg_match_all('/(?<=\[USER=)\d+(?=])/', $replaces['#NEW_VALUE#'], $ids);
					$usersToSkipReading = array_merge($usersToSkipReading, array_map('intval', $ids[0]));
					break;

				case 'COMMENT_POSTER_COMMENT_TASK_UPDATE_STATUS_2_RENEW_V2':
				case 'COMMENT_POSTER_COMMENT_TASK_UPDATE_STATUS_2_RENEW_NO_MEMBERS_V2':
					$members = $this->getMembersByRole();
					$createdBy = current($members[MemberTable::MEMBER_TYPE_ORIGINATOR]);
					$responsibleMembers = array_merge(
						$members[MemberTable::MEMBER_TYPE_RESPONSIBLE],
						$members[MemberTable::MEMBER_TYPE_ACCOMPLICE]
					);
					if ($this->comment->getAuthorId() === $createdBy)
					{
						$usersToSkipReading = array_merge($usersToSkipReading, $responsibleMembers);
					}
					else
					{
						$usersToSkipReading = array_merge(
							$usersToSkipReading,
							$responsibleMembers,
							$members[MemberTable::MEMBER_TYPE_ORIGINATOR]
						);
					}
					break;

				case 'COMMENT_POSTER_COMMENT_TASK_UPDATE_STATUS_4_V2':
					$members = $this->getMembersByRole();
					$createdBy = current($members[MemberTable::MEMBER_TYPE_ORIGINATOR]);
					$responsibleMembers = array_merge(
						$members[MemberTable::MEMBER_TYPE_RESPONSIBLE],
						$members[MemberTable::MEMBER_TYPE_ACCOMPLICE]
					);
					$usersToSkipReading[] = $createdBy;
					if (!in_array($this->comment->getAuthorId(), $responsibleMembers, true))
					{
						$usersToSkipReading = array_merge($usersToSkipReading, $responsibleMembers);
					}
					break;

				case 'COMMENT_POSTER_COMMENT_TASK_UPDATE_STATUS_5_V2':
				case 'COMMENT_POSTER_COMMENT_TASK_UPDATE_STATUS_5_APPROVE_V2':
				case 'COMMENT_POSTER_COMMENT_TASK_UPDATE_CHANGES_FIELD_AUDITORS':
				default:
					break;
			}
		}

		return array_unique($usersToSkipReading);
	}

	private function getMembersByRole(): array
	{
		$res = [
			MemberTable::MEMBER_TYPE_ORIGINATOR => [],
			MemberTable::MEMBER_TYPE_RESPONSIBLE => [],
			MemberTable::MEMBER_TYPE_ACCOMPLICE => [],
			MemberTable::MEMBER_TYPE_AUDITOR => [],
		];
		foreach ($this->members as $member)
		{
			$res[$member['TYPE']][] = $member['USER_ID'];
		}
		return $res;
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
	 * Checks if comment contains codes from $codes (all codes from $codes if $all).
	 *
	 * @param array $codes
	 * @param bool $all
	 * @return bool
	 */
	public function isContainCodes(array $codes, bool $all = false): bool
	{
		$commentCodes = [];

		foreach ($this->comment->getData() as $part)
		{
			if (!is_array($part) || empty($part))
			{
				continue;
			}

			[$code, ] = $part;
			$commentCodes[] = $code;
		}

		if ($all)
		{
			$intersection = array_intersect($commentCodes, $codes);

			return (!empty($intersection) && count($intersection) === count($codes));
		}

		return !empty(array_intersect($commentCodes, $codes));
	}

	/**
	 * @return int
	 */
	public function getCommentId(): int
	{
		return $this->commentId;
	}

	/**
	 * @param int $commentId
	 */
	public function setCommentId(int $commentId): void
	{
		$this->commentId = $commentId;
		$this->fillCommentData();
	}

	/**
	 * @return array
	 */
	public function getCommentData(): array
	{
		if (!$this->comment)
		{
			return [];
		}

		return $this->comment->getData();
	}

	/**
	 * @param array $commentData
	 */
	public function setCommentData(array $commentData): void
	{
		$this->comment = Comment::createFromData(
			$commentData['MESSAGE'],
			$commentData['AUTHOR_ID'],
			$commentData['TYPE'],
			unserialize($commentData['AUX_DATA'], ['allowed_classes' => false])
		);
	}
}