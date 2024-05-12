<?php

namespace Bitrix\Crm\Security\QueryBuilder;

use Bitrix\Crm\Security\QueryBuilder\Result\InConditionResult;
use Bitrix\Crm\Security\QueryBuilder\Result\JoinResult;
use Bitrix\Crm\Security\QueryBuilder\Result\JoinWithUnionResult;
use Bitrix\Crm\Security\QueryBuilder\Result\RawQueryResult;
use Bitrix\Crm\Security\QueryBuilder\Result\ResultOption;
use Bitrix\Crm\Service\UserPermissions;

class OptionsBuilder
{
	private ResultOption $resultType;

	private string $aliasPrefix = '';

	private array $operations = [UserPermissions::OPERATION_READ];

	private ?bool $readAllAllowed = null;

	private ?bool $skipCheckOtherEntityTypes = null;

	/** @deprecated used only in compatible mode */
	private ?array $limitByIds = null;

	/** @deprecated used only in compatible mode */
	private ?bool $useDistinctUnion = null;

	public function __construct(
		ResultOption $resultType
	)
	{
		$this->resultType = $resultType;
	}

	public function setAliasPrefix(string $prefix): self
	{
		$this->aliasPrefix = $prefix;

		return $this;
	}

	public function setOperations(array $operations): self
	{
		$this->operations = $operations;

		return $this;
	}

	public function setReadAllAllowed(?bool $readAllAllowed): self
	{
		$this->readAllAllowed = $readAllAllowed;

		return $this;
	}

	public function setSkipCheckOtherEntityTypes(bool $skipCheckOtherEntityTypes): self
	{
		$this->skipCheckOtherEntityTypes = $skipCheckOtherEntityTypes;

		return $this;
	}

	public function setLimitByIds(?array $limitByIds): self
	{
		$this->limitByIds = $limitByIds;

		return $this;
	}

	public function setUseDistinctUnion(?bool $useDistinctUnion): self
	{
		$this->useDistinctUnion = $useDistinctUnion;

		return $this;
	}

	public function build(): QueryBuilderOptions
	{
		$isReadAll = is_null($this->readAllAllowed) ? false : $this->readAllAllowed;
		$skipCheck = is_null($this->skipCheckOtherEntityTypes) ? false : $this->skipCheckOtherEntityTypes;
		$useDistinctUnion = is_null($this->useDistinctUnion) ? false : $this->useDistinctUnion;

		return new QueryBuilderOptions(
			resultType: $this->resultType,
			operations: $this->operations,
			aliasPrefix: $this->aliasPrefix,
			readAllAllowed: $isReadAll,
			skipCheckOtherEntityTypes: $skipCheck,
			limitByIds: $this->limitByIds,
			useDistinctUnion: $useDistinctUnion,
		);
	}

	public static function makeEmptyOptions(): QueryBuilderOptions
	{
		return new QueryBuilderOptions(new Result\InConditionResult());
	}

	public static function makeFromArray(array $options): self
	{
		// Result priority. needReturnRawQuery -> needUseJoin -> needUseInCondition
		$identityColumnName = (string)($options['IDENTITY_COLUMN'] ?? 'ID');

		// RawQuery
		if(isset($options['RAW_QUERY']) && ($options['RAW_QUERY'] === true || is_array($options['RAW_QUERY'])))
		{
			$order = null;
			$limit = null;
			if(is_array($options['RAW_QUERY']) && isset($options['RAW_QUERY']['TOP']) && (int)$options['RAW_QUERY']['TOP'] > 0)
			{
				$order =
					isset($options['RAW_QUERY']['SORT_TYPE'])
					&& mb_strtoupper($options['RAW_QUERY']['SORT_TYPE']) === 'DESC'
						? 'DESC'
						: 'ASC'
				;

				$limit = (int)$options['RAW_QUERY']['TOP'];
			}

			$result = new RawQueryResult($order, $limit, false, $identityColumnName);
		}
		// needUseJoin
		elseif (isset($options['PERMISSION_SQL_TYPE']) && $options['PERMISSION_SQL_TYPE'] === 'FROM')
		{
			if (($options['PERMISSION_BUILDER_OPTION_OBSERVER_JOIN_AS_UNION'] ?? false) === true)
			{
				$result = new JoinWithUnionResult($identityColumnName);
			}
			else
			{
				$result = new JoinResult($identityColumnName);
			}

		}
		// needUseInCondition
		else
		{
			$result = new InConditionResult($identityColumnName);
		}


		$optionsBuilder = new self($result);


		if (isset($options['RESTRICT_BY_IDS']) && is_array($options['RESTRICT_BY_IDS']))
		{
			$optionsBuilder->setLimitByIds($options['RESTRICT_BY_IDS']);
		}

		if (isset($options['READ_ALL']) && $options['READ_ALL'])
		{
			$optionsBuilder->setReadAllAllowed(true);
		}

		if (isset($options['PERMISSION_SQL_UNION']) && $options['PERMISSION_SQL_UNION'] === 'DISTINCT')
		{
			$optionsBuilder->setUseDistinctUnion(true);
		}

		return $optionsBuilder;
	}

}