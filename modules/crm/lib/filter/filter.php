<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm;
use Bitrix\Crm\PhaseSemantics;

class Filter extends \Bitrix\Main\Filter\Filter
{
	protected function getDateFieldNames(): array
	{
		$result = [];
		$fields = $this->getFields();
		foreach ($fields as $field)
		{
			if ($field->getType() === 'date')
			{
				$result[] = $field->getName();
			}
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function getFields()
	{
		$fields = parent::getFields();

		if (
			isset($this->params['filterFieldsCallback'])
			&& is_callable($this->params['filterFieldsCallback'])
		)
		{
			$fields = array_filter(
				$fields,
				$this->params['filterFieldsCallback'],
				ARRAY_FILTER_USE_KEY
			);
		}

		if (
			isset($this->params['modifyFieldsCallback'])
			&& is_callable($this->params['modifyFieldsCallback'])
		)
		{
			$fields = array_map(
				$this->params['modifyFieldsCallback'],
				$fields
			);
		}

		return $fields;
	}

	/**
	 * Prepare list filter params.
	 * @param array $filter Source Filter.
	 * @return void
	 */
	public function prepareListFilterParams(array &$filter): void
	{
		foreach ($filter as $k => $v)
		{
			$match = array();
			if (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $match))
			{
				Crm\UI\Filter\Range::prepareFrom($filter, $match[1], $v);
			}
			elseif (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $match))
			{
				if ($v != '' && in_array($match[1], $this->getDateFieldNames()) && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
				{
					$v = \CCrmDateTimeHelper::SetMaxDayTime($v);
				}
				Crm\UI\Filter\Range::prepareTo($filter, $match[1], $v);
			}

			$this->entityDataProvider->prepareListFilterParam($filter, $k);
		}
		Crm\UI\Filter\EntityHandler::internalize($this->getFieldArrays(), $filter);
	}

	/**
	 * In order to use a join with smart process entities in the filter,
	 * need to execute this code, which will create a smart process entity class
	 * @param \Bitrix\Main\Event $event
	 */
	public static function onFiredUserProviderQuery(\Bitrix\Main\Event $event): void
	{
		$module = $event->getParameter('module');
		$entityTypeId = $event->getParameter('entityTypeId');
		if ($module === 'crm' && \CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
		{
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
			if ($factory)
			{
				$factory->getDataClass();
			}
		}
	}

	public static function applyStageSemanticFilter(array &$filter, array $requestFilter, string $fieldStageSemantic): void
	{
		if (empty($requestFilter[$fieldStageSemantic]))
		{
			return;
		}

		$semanticFilter = [];
		if (in_array(PhaseSemantics::PROCESS, $requestFilter[$fieldStageSemantic], true))
		{
			$semanticFilter[] = [
				'STAGE.SEMANTICS' => '',
			];
			$semanticFilter[] = [
				'STAGE.SEMANTICS' => PhaseSemantics::PROCESS,
			];
		}
		if (in_array(PhaseSemantics::SUCCESS, $requestFilter[$fieldStageSemantic], true))
		{
			$semanticFilter[] = [
				'STAGE.SEMANTICS' => PhaseSemantics::SUCCESS,
			];
		}
		if (in_array(PhaseSemantics::FAILURE, $requestFilter[$fieldStageSemantic], true))
		{
			$semanticFilter[] = [
				'STAGE.SEMANTICS' => PhaseSemantics::FAILURE,
			];
		}

		if (!empty($semanticFilter))
		{
			$filter[] = array_merge([
				'LOGIC' => 'OR',
			], $semanticFilter);
		}
	}
}
