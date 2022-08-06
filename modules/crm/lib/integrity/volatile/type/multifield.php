<?php

namespace Bitrix\Crm\Integrity\Volatile\Type;

use Bitrix\Crm\Integrity\Volatile\FieldCategory;
use CCrmFieldMulti;
use CCrmOwnerType;

class MultiField extends BaseField
{
	/** @var string */
	protected $type;

	protected function getType(): string
	{
		return $this->type;
	}

	protected function getValuesFromData(array $data): array
	{
		$result = [];

		$type = $this->getType();

		if (is_array($data['FM'][$type]) && !empty($data['FM'][$type]))
		{
			foreach($data['FM'][$type] as $item)
			{
				if (array_key_exists('VALUE', $item))
				{
					$value = !is_array($item['VALUE']) ? (string)$item['VALUE'] : '';
					if ($value !== '')
					{
						$result[] = $value;
					}
				}
			}
		}

		return $result;
	}

	public function __construct(int $volatileTypeId, int $entityTypeId, string $type)
	{
		parent::__construct($volatileTypeId, $entityTypeId);
		$this->fieldCategory = FieldCategory::MULTI;
		$this->type = $type;
	}

	public function getMatchName(): string
	{
		$matchName = parent::getMatchName();

		return $matchName === '' ? $this->getType() : $matchName;
	}

	public function getValues(int $entityId): array
	{
		$entityTypeId = $this->getEntityTypeId();
		$type = $this->getType();

		$res = CCrmFieldMulti::GetList(
			['ID' => 'asc'],
			[
				'ENTITY_ID' => CCrmOwnerType::ResolveName($entityTypeId),
				'ELEMENT_ID' => $entityId,
				'TYPE_ID' => $type,
			]
		);

		$data = [];
		$index = 0;
		while($row = $res->Fetch())
		{
			$data['n' . $index++] = [
				'VALUE' => $row['VALUE'],
				'VALUE_TYPE' => $row['VALUE_TYPE'],
			];
		}
		$data = ['FM' => [$type => $data]];

		return $this->getValuesFromData($data);
	}
}