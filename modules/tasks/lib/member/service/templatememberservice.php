<?php

namespace Bitrix\Tasks\Member\Service;

use Bitrix\Tasks\Member\AbstractMemberService;
use Bitrix\Tasks\Member\Repository\TemplateRepository;
use Bitrix\Tasks\Member\RepositoryInterface;

class TemplateMemberService extends AbstractMemberService
{
	public function getRepository(): RepositoryInterface
	{
		return new TemplateRepository($this->entityId);
	}
}