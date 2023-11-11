<?php

namespace Bitrix\Tasks\Internals\Task\Template;

use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Internals\MemberTrait;
use Bitrix\Tasks\Internals\Task\EO_Template;
use Bitrix\Tasks\Internals\Task\TemplateTable;
use Bitrix\Tasks\Member\MemberService;
use Bitrix\Tasks\Member\Service\TemplateMemberService;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Main\ORM\Fields;
use CTaskTemplates;

class TemplateObject extends EO_Template
{
	use MemberTrait;

	public function toArray(): array
	{
		$fields = TemplateTable::getEntity()->getFields();

		$data = [];
		foreach ($fields as $fieldName => $field)
		{
			if (
				$field instanceof Fields\Relations\Reference
				|| $field instanceof Fields\Relations\OneToMany
				|| $field instanceof Fields\Relations\ManyToMany
				|| $field instanceof Fields\ExpressionField
			)
			{
				continue;
			}

			$data[$fieldName] = $this->get($fieldName);

			if ($data[$fieldName] instanceof DateTime)
			{
				$data[$fieldName] = $data[$fieldName]->getTimestamp();
			}
		}
		return $data;
	}

	public function getChildren(): array
	{
		$result = [];
		$res = CTaskTemplates::getList(
			['BASE_TEMPLATE_ID' => 'asc'],
			['BASE_TEMPLATE_ID' => $this->getId()],
			false,
			['INCLUDE_TEMPLATE_SUBTREE' => true],
			['*', 'UF_*', 'BASE_TEMPLATE_ID']
		);
		while ($item = $res->fetch())
		{
			if ((int)$item['ID'] === $this->getId())
			{
				continue;
			}
			$result[(int)$item['ID']] = $item;
		}

		return $result;
	}

	public function getResponsibleMemberId(): array
	{
		return $this->getMembersIdsByRole(RoleDictionary::ROLE_RESPONSIBLE);
	}

	public function getMemberService(): MemberService
	{
		return new TemplateMemberService($this->getId());
	}
}