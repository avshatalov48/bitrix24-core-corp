<?php

namespace Bitrix\Extranet\Contract\Service;

use Bitrix\Extranet\Entity\Collection\ExtranetUserCollection;
use Bitrix\Main\Result;

interface CollaberService
{
	public function isCollaberById(int $id): bool;

	public function setCollaberRoleByUserId(int $id): Result;

	public function removeCollaberRoleByUserId(int $id): Result;

	public function getCollaberCollection(): ExtranetUserCollection;

	public function getCollaberIds(): array;

	public function clearCache(): void;
}
