<?php

namespace Bitrix\Intranet\Contract\Repository;

use Bitrix\Intranet\Entity\Collection\UserCollection;

interface UserRepository
{
	public function findUsersByLogins(array $logins): UserCollection;

	public function findUsersByPhoneNumbers(array $phoneNumbers): UserCollection;

	public function findUsersByIds(array $ids): UserCollection;

	public function findUsersByEmails(array $emails): UserCollection;
}