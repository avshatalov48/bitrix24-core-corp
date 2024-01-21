<?php

namespace Bitrix\Tasks\Member;

use Bitrix\Tasks\Internals\Task\EO_Member_Collection;
use Bitrix\Tasks\Internals\Task\Template\EO_TemplateMember_Collection;
use Bitrix\Tasks\Internals\Task\Template\TemplateObject;
use Bitrix\Tasks\Internals\TaskObject;

interface RepositoryInterface
{
	public function getEntity(): TaskObject|TemplateObject|null;
	public function getMembers(): EO_TemplateMember_Collection|EO_Member_Collection;
	public function getType(): string;
}