<?php

namespace Bitrix\Tasks\Replication\Template\Repetition\Conversion\Converters;

use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Replication\Template\Repetition\Conversion\ConverterInterface;
use Bitrix\Tasks\Replication\RepositoryInterface;

final class MemberConverter implements ConverterInterface
{
	public function convert(RepositoryInterface $repository): array
	{
		$taskFields = [];

		$members = $repository->getEntity()->getMembers();
		foreach ($members as $member)
		{
			$field = $this->getTaskFieldName($member->getType());
			if (is_null($field))
			{
				continue;
			}
			$taskFields[$this->getTaskFieldName($member->getType())][] = $member->getUserId();
		}

		return $taskFields;
	}

	public function getTemplateFieldName(): string
	{
		return 'MEMBERS';
	}

	private function getTaskFieldName(string $key): ?string
	{
		if ($key === RoleDictionary::ROLE_AUDITOR)
		{
			return 'AUDITORS';
		}

		if ($key === RoleDictionary::ROLE_ACCOMPLICE)
		{
			return 'ACCOMPLICES';
		}

		return null;
	}
}