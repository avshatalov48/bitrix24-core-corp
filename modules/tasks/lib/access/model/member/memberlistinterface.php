<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Model\Member;

use Bitrix\Main\Access\User\AccessibleUser;
use Bitrix\Tasks\Access\AccessibleTask;

interface MemberListInterface
{
	public function __construct(AccessibleUser $user, AccessibleTask $task);

	public function getHasRightUsers(): ?array;
	public function getAccesibleUsers(): ?array;
}