<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Type\Collection;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Internals\Task\Priority;
use Bitrix\Tasks\V2\Internal\Entity;

class TemplateControlMapper
{
	public function mapToControl(Entity\Template $template): array
	{
		$converter = new Converter(Converter::KEYS | Converter::RECURSIVE | Converter::TO_SNAKE | Converter::TO_UPPER);

		$fields = $converter->process($this->getTemplateFields($template));

		$this->mapTaskFields($fields);
		$this->mapPriorityFields($fields);
		$this->mapMembersFields($fields);
		$this->mapGroupField($fields);
		$this->mapStageField($fields);
		$this->mapDateFields($fields);
		$this->mapParentField($fields);

		return $fields;
	}

	private function mapTaskFields(array &$fields): void
	{
		if (isset($fields['TASK']['ID']))
		{
			$fields['TASK_ID'] = $fields['TASK']['ID'];
		}

		unset($fields['TASK']);
	}

	private function mapPriorityFields(array &$fields): void
	{
		if (!isset($fields['PRIORITY']))
		{
			return;
		}

		$fields['PRIORITY'] = match ($fields['PRIORITY']) {
			Entity\Task\Priority::Low->value => Priority::LOW,
			Entity\Task\Priority::Average->value => Priority::AVERAGE,
			Entity\Task\Priority::High->value => Priority::HIGH,
		};
	}

	private function mapMembersFields(array &$fields): void
	{
		if (isset($fields['CREATOR']))
		{
			$fields['CREATED_BY'] = $fields['CREATOR']['ID'];
			unset($fields['CREATOR']);
		}

		if (isset($fields['RESPONSIBLE_COLLECTION']))
		{
			$responsibleCollection = array_map(
				static fn(array $member): int => $member['ID'],
				$fields['RESPONSIBLE_COLLECTION']
			);

			Collection::normalizeArrayValuesByInt($responsibleCollection, false);

			$fields['RESPONSIBLES'] = $responsibleCollection;

			unset($fields['RESPONSIBLE_COLLECTION']);
		}

		if (isset($fields['ACCOMPLICES']))
		{
			$accomplices = array_map(
				static fn(array $member): int => $member['ID'],
				$fields['ACCOMPLICES']
			);

			Collection::normalizeArrayValuesByInt($accomplices, false);

			$fields['ACCOMPLICES'] = $accomplices;
		}

		if (isset($fields['AUDITORS']))
		{
			$auditors = array_map(
				static fn(array $member): int => $member['ID'],
				$fields['AUDITORS']
			);

			Collection::normalizeArrayValuesByInt($auditors, false);

			$fields['AUDITORS'] = $auditors;
		}
	}

	private function mapGroupField(array &$fields): void
	{
		if (!isset($fields['GROUP']))
		{
			return;
		}

		$fields['GROUP_ID'] = $fields['GROUP']['ID'];
		unset($fields['GROUP']);
	}

	private function mapStageField(array &$fields): void
	{
		if (!isset($fields['STAGE']))
		{
			return;
		}

		$fields['STAGE_ID'] = $fields['STAGE']['ID'];
		unset($fields['STAGE']);
	}

	private function mapDateFields(array &$fields): void
	{
		if (isset($fields['DEADLINE_AFTER_TS']))
		{
			$fields['DEADLINE'] = $fields['DEADLINE_AFTER_TS'] > 0 ? DateTime::createFromTimestamp($fields['DEADLINE_AFTER_TS'])->toString() : '';
			unset($fields['DEADLINE_AFTER_TS']);
		}

		if (isset($fields['START_DATE_PLAN_TS']))
		{
			$fields['START_DATE_PLAN'] = DateTime::createFromTimestamp($fields['START_DATE_PLAN_TS'])->toString();
			unset($fields['START_DATE_PLAN_TS']);
		}

		if (isset($fields['END_DATE_PLAN_TS']))
		{
			$fields['END_DATE_PLAN'] = DateTime::createFromTimestamp($fields['END_DATE_PLAN_TS'])->toString();
			unset($fields['END_DATE_PLAN_TS']);
		}
	}

	private function mapParentField(array &$fields): void
	{
		if (!isset($fields['PARENT']))
		{
			return;
		}

		$fields['PARENT_ID'] = $fields['PARENT']['ID'];
		unset($fields['PARENT']);
	}

	private function getTemplateFields(Entity\Template $template): array
	{
		$templateFields = $template->toArray();

		foreach($templateFields as $key => $field)
		{
			if ($field === null)
			{
				unset($templateFields[$key]);
			}
		}

		return $templateFields;
	}
}
