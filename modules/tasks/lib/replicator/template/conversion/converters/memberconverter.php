<?php

namespace Bitrix\Tasks\Replicator\Template\Conversion\Converters;

use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Replicator\Template\Conversion\Converter;
use Bitrix\Tasks\Replicator\Template\Repository;

final class MemberConverter implements Converter
{
	public function convert(Repository $repository): array
	{
		$taskFields = [];

		$members = $repository->getTemplate()->getMembers();
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