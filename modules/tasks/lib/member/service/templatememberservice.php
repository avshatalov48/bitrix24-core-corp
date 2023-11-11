<?php

namespace Bitrix\Tasks\Member\Service;

use Bitrix\Tasks\Member\MemberService;
use Bitrix\Tasks\Member\Repository;

class TemplateMemberService extends MemberService
{
	public function getRepository(): Repository
	{
		return new Repository\TemplateRepository($this->entityId);
	}
}