<?php

namespace Bitrix\Sign\Operation\Member;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Field\FieldValue;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Repository\FieldValueRepository;
use Bitrix\Sign\Service\Container;

class SaveFields implements Operation
{
	private readonly FieldValueRepository $fieldValueRepository;
	public function __construct(
		private readonly Member $member,
		private readonly array $fields,
		?FieldValueRepository $fieldValueRepository = null,
	) {
		$container = Container::instance();
		$this->fieldValueRepository = $fieldValueRepository ?? $container->getFieldValueRepository();
	}

	public function launch(): Main\Result
	{
		$this->fieldValueRepository->deleteAllByMemberId($this->member->id);

		foreach ($this->fields as $field)
		{
			$name = trim((string)($field['name'] ?? ''));
			$value = trim((string)($field['value'] ?? ''));
			$result = $this->saveField($name, $value);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		return new Main\Result();
	}

	private function saveField(string $name, string $value): Main\Result
	{
		if (empty($name) || empty($value))
		{
			return new Main\Result();
		}

		$item = new FieldValue(
			fieldName: $name,
			memberId: $this->member->id,
			value: $value,
		);

		return $this->fieldValueRepository->add($item);
	}
}