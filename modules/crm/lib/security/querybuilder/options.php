<?php

namespace Bitrix\Crm\Security\QueryBuilder;

use Bitrix\Main\ArgumentOutOfRangeException;

class Options
{
	private $operations = [
		\Bitrix\Crm\Service\UserPermissions::OPERATION_READ,
	];
	private $aliasPrefix = '';
	private $identityColumnName = 'ID';
	private $limitByIds = [];
	private $readAllAllowed = false;
	private $needReturnRawQuery = false;
	private $rawQueryLimit = 0;
	private $rawQueryOrder = 'ASC';
	private $useDistinctUnion = false;
	private $useJoin = false;
	private $skipCheckOtherEntityTypes = false;
	private $rawQueryDistinct = false;

	public static function createFromArray(array $options): Options
	{
		$result = new self();

		if (isset($options['IDENTITY_COLUMN']) && (string)$options['IDENTITY_COLUMN'] !== '')
		{
			$result->setIdentityColumnName((string)$options['IDENTITY_COLUMN']);
		}
		if (isset($options['RESTRICT_BY_IDS']) && is_array($options['RESTRICT_BY_IDS']))
		{
			$result->setLimitByIds($options['RESTRICT_BY_IDS']);
		}
		if (isset($options['READ_ALL']) && $options['READ_ALL'])
		{
			$result->setReadAllAllowed(true);
		}
		if (isset($options['PERMISSION_SQL_UNION']) && $options['PERMISSION_SQL_UNION'] === 'DISTINCT')
		{
			$result->setUseDistinctUnion(true);
		}
		if (isset($options['PERMISSION_SQL_TYPE']) && $options['PERMISSION_SQL_TYPE'] === 'FROM')
		{
			$result->setUseJoin(true);
		}
		if(isset($options['RAW_QUERY']) && ($options['RAW_QUERY'] === true || is_array($options['RAW_QUERY'])))
		{
			$result->setNeedReturnRawQuery(true);
			if(is_array($options['RAW_QUERY']) && isset($options['RAW_QUERY']['TOP']) && (int)$options['RAW_QUERY']['TOP'] > 0)
			{
				$order =
					isset($options['RAW_QUERY']['SORT_TYPE'])
					&& mb_strtoupper($options['RAW_QUERY']['SORT_TYPE']) === 'DESC'
						? 'DESC'
						: 'ASC'
				;
				$result->setRawQueryOrder($order);
				$result->setRawQueryLimit((int)$options['RAW_QUERY']['TOP']);
			}
		}

		return $result;
	}

	public function getOperations(): array
	{
		return $this->operations;
	}

	/**
	 * Set array of operations. Can contain constants \Bitrix\Crm\Service\UserPermissions::OPERATION_*
	 *
	 * @param $operations array
	 * @return $this
	 */
	public function setOperations(array $operations): Options
	{
		$this->operations = $operations;

		return $this;
	}

	public function getAliasPrefix(): string
	{
		return $this->aliasPrefix;
	}

	public function setAliasPrefix(string $aliasPrefix): Options
	{
		$this->aliasPrefix = $aliasPrefix;

		return $this;
	}

	public function getIdentityColumnName(): string
	{
		return $this->identityColumnName;
	}

	public function setIdentityColumnName(string $identityColumnName): Options
	{
		$this->identityColumnName = $identityColumnName;

		return $this;
	}

	public function getLimitByIds(): array
	{
		return $this->limitByIds;
	}

	public function setLimitByIds(array $limitByIds): Options
	{
		$this->limitByIds = [];
		foreach ($limitByIds as $id)
		{
			if ($id > 0)
			{
				$this->limitByIds[] = (int)$id;
			}
		}

		return $this;
	}

	public function isReadAllAllowed(): bool
	{
		return $this->readAllAllowed;
	}

	public function setReadAllAllowed(bool $readAllAllowed): Options
	{
		$this->readAllAllowed = $readAllAllowed;

		return $this;
	}

	public function needReturnRawQuery(): bool
	{
		return $this->needReturnRawQuery;
	}

	public function setNeedReturnRawQuery(bool $needReturnRawQuery): Options
	{
		$this->needReturnRawQuery = $needReturnRawQuery;

		return $this;
	}

	public function getRawQueryLimit(): int
	{
		return $this->rawQueryLimit;
	}

	public function setRawQueryLimit(int $rawQueryLimit): Options
	{
		$this->rawQueryLimit = $rawQueryLimit;

		return $this;
	}

	public function getRawQueryOrder(): string
	{
		return $this->rawQueryOrder;
	}

	public function setRawQueryOrder(string $rawQueryOrder): Options
	{
		$rawQueryOrder = strtoupper($rawQueryOrder);
		$possibleValues = ['ASC', 'DESC'];
		if (!in_array($rawQueryOrder, $possibleValues))
		{
			throw new ArgumentOutOfRangeException('RawQueryOrder', $possibleValues);
		}
		$this->rawQueryOrder = $rawQueryOrder;

		return $this;
	}


	public function needUseDistinctUnion(): bool
	{
		return $this->useDistinctUnion;
	}

	public function setUseDistinctUnion(bool $useDistinctUnion): Options
	{
		$this->useDistinctUnion = $useDistinctUnion;

		return $this;
	}

	public function needUseJoin(): bool
	{
		return $this->useJoin;
	}

	public function setUseJoin(bool $useJoin): Options
	{
		$this->useJoin = $useJoin;

		return $this;
	}

	public function setSkipCheckOtherEntityTypes(bool $skipCheckOtherEntityTypes): Options
	{
		$this->skipCheckOtherEntityTypes = $skipCheckOtherEntityTypes;

		return $this;
	}

	public function canSkipCheckOtherEntityTypes(): bool
	{
		return $this->skipCheckOtherEntityTypes;
	}

	public function setUseRawQueryDistinct(bool $useDistinct): Options
	{
		$this->rawQueryDistinct = $useDistinct;
		return $this;
	}

	public function isUseRawQueryDistinct(): bool
	{
		return $this->rawQueryDistinct;
	}
}
