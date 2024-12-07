<?php

namespace Bitrix\Tasks\Flow\Provider\Query;

use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Flow\Internal\FlowTable;
use Bitrix\Tasks\Flow\Search\FullTextSearch;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use InvalidArgumentException;

/**
 * @method self whereId(int|array $value, string $operator = '=')
 * @method self whereCreatorId(int|array $value, string $operator = '=')
 * @method self whereOwnerId(int|array $value, string $operator = '=')
 * @method self whereGroupId(int|array $value, string $operator = '=')
 * @method self whereTemplateId(int|array $value, string $operator = '=')
 * @method self whereEfficiency(int|array $value, string $operator = '=')
 * @method self whereActive(bool|array $value, string $operator = '=')
 * @method self wherePlannedCompletionTime(int|array $value, string $operator = '=')
 * @method self whereActivity(DateTime|array $value, string $operator = '=')
 * @method self whereName(string|array $value, string $operator = '=')
 * @method self whereDescription(string|array $value, string $operator = '=')
 * @method self whereDistributionType(string|array $value, string $operator = '=')
 */
class ExpandedFlowQuery extends FlowQuery
{
	public function whereFulltext(string $fulltext): static
	{
		$this->where->whereIn('ID', (new FullTextSearch())->find($fulltext));
		return $this;
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function whereCounter(string $type): static
	{
		if (!CounterDictionary::isValid($type))
		{
			throw new InvalidArgumentException('Invalid counter type');
		}

		$counters = Counter::getInstance($this->userId)
			->getRawCounters(Counter\CounterDictionary::META_PROP_FLOW)[$type] ?? [];

		$flowIds = [];
		foreach ($counters as $flowId => $counter)
		{
			$flowIds[] = (int)$flowId;
		}

		if (empty($flowIds))
		{
			return $this;
		}

		$this->where->whereIn('ID', $flowIds);
		return $this;
	}

	public function __call(string $name, mixed $args = []): static
	{
		if (str_starts_with($name, 'where'))
		{
			if (empty($args))
			{
				throw new InvalidArgumentException('Empty filter');
			}
			$property = lcfirst(substr($name, 5));
			$property = (new Converter(Converter::TO_UPPER | Converter::TO_SNAKE))->process($property);
			if (!$this->isValidField($property))
			{
				throw new InvalidArgumentException('Invalid filter');
			}
			
			$value = $args[0];
			$operator = count($args) === 1 ? '=' : $args[1];
			$this->where->where($property, $operator, $value);
		}

		return $this;
	}

	protected function isValidField(string $field): bool
	{
		static $fields = null;
		if (null === $fields)
		{
			$fields = FlowTable::getEntity()->getFields();
		}

		foreach ($fields as $entityField)
		{
			if ($entityField instanceof ScalarField && $entityField->getName() === $field)
			{
				return true;
			}
		}

		return false;
	}

	protected function init(): void
	{
		$this->where = Query::filter();
	}
}