<?php

namespace Bitrix\Intranet\Contract\Repository;

use Bitrix\Intranet\Entity\Collection\UserCollection;

interface UserRepository
{
	public function findUsersByLogins(array $logins): UserCollection;

	public function findUsersByPhoneNumbers(array $phoneNumbers): UserCollection;

	public function findUsersByIds(array $ids): UserCollection;

	public function findUsersByEmails(array $emails): UserCollection;

	public function findUsersByLoginsAndEmails(array $emails): UserCollection;

	public function findUsersByLoginsAndPhoneNumbers(array $phoneNumbers): UserCollection;

	public function findUsersByUserGroup(int $userGroup): UserCollection;
}