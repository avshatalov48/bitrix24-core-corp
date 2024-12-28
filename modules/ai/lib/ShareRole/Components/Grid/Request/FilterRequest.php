<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Components\Grid\Request;

use Bitrix\AI\ShareRole\Service\GridRole\Dto\GridFilterParamsDto;
use Bitrix\AI\ShareRole\Service\GridRole\Enum\Order;
use Bitrix\AI\ShareRole\Service\GridRole\Dto\GridParamsDto;
use Bitrix\Main\Type\DateTime;

class FilterRequest
{
	public function __construct()
	{
	}

	public function getDataFromParams(array $params): GridParamsDto
	{
		$dto = new GridParamsDto();

		if (array_key_exists('order', $params) && is_array($params['order']))
		{
			$dto->order = $this->getOrders($params['order']);
		}

		$dto->filter = $this->getFilter($params);

		return $dto;
	}

	protected function getFilter(array $params): GridFilterParamsDto
	{
		$filterDto = new GridFilterParamsDto();

		if (!array_key_exists('filter', $params) || !is_array($params['filter']))
		{
			return $filterDto;
		}

		$filters = $params['filter'];

		if (array_key_exists('NAME', $filters) && is_string($filters['NAME']))
		{
			$filterDto->name = $filters['NAME'];
		}

		if (array_key_exists('AUTHOR', $filters) && is_array($filters['AUTHOR']))
		{
			foreach ($filters['AUTHOR'] as $author)
			{
				$filterDto->authors[] = (int)$author;
			}
		}
		if (array_key_exists('EDITOR', $filters) && is_array($filters['EDITOR']))
		{
			foreach ($filters['EDITOR'] as $editor)
			{
				$filterDto->editors[] = (int)$editor;
			}
		}

		if (array_key_exists('SHARE', $filters) && is_array($filters['SHARE']))
		{
			foreach ($filters['SHARE'] as $share)
			{
				$filterDto->share[] = (int)$share;
			}
		}

		if (array_key_exists('IS_ACTIVE', $filters) && is_string($filters['IS_ACTIVE']))
		{
			$filterDto->isActive = $filters['IS_ACTIVE'] === 'Y';
		}

		if (array_key_exists('IS_DELETED', $filters) && is_string($filters['IS_DELETED']))
		{
			$filterDto->isDeleted = $filters['IS_DELETED'] === 'Y';
		}

		if (
			array_key_exists('>=DATE_MODIFY', $filters)
			&& array_key_exists('<=DATE_MODIFY', $filters)
			&& is_string($filters['>=DATE_MODIFY'])
			&& is_string($filters['<=DATE_MODIFY'])
		)
		{
			$dateModifyStart = DateTime::createFromText($filters['>=DATE_MODIFY']);
			$dateModifyEnd = DateTime::createFromText($filters['<=DATE_MODIFY']);

			if (!empty($dateModifyStart) && !empty($dateModifyEnd))
			{
				$filterDto->dateModifyStart = DateTime::createFromTimestamp($dateModifyStart->getTimestamp());
				$filterDto->dateModifyEnd = DateTime::createFromTimestamp($dateModifyEnd->getTimestamp());
			}
		}

		if (
			array_key_exists('>=DATE_CREATE', $filters)
			&& array_key_exists('<=DATE_CREATE', $filters)
			&& is_string($filters['>=DATE_CREATE'])
			&& is_string($filters['<=DATE_CREATE'])
		)
		{
			$dateCreateStart = DateTime::createFromText($filters['>=DATE_CREATE']);
			$dateCreateEnd = DateTime::createFromText($filters['<=DATE_CREATE']);

			if (!empty($dateCreateStart) && !empty($dateCreateEnd))
			{
				$filterDto->dateCreateStart = DateTime::createFromTimestamp($dateCreateStart->getTimestamp());
				$filterDto->dateCreateEnd = DateTime::createFromTimestamp($dateCreateEnd->getTimestamp());
			}
		}

		return $filterDto;
	}

	protected function getOrders(array $orders): array
	{
		foreach ($orders as $field => $rule)
		{
			if (!in_array($rule, [Order::Desc, Order::Asc]))
			{
				continue;
			}

			$enum = Order::tryFrom($field);

			if (!is_null($enum))
			{
				return [$enum->value, $rule];
			}
		}

		return [];
	}

	public function addLimiterAndOffset(GridParamsDto $gridParamsDto, array $params): GridParamsDto
	{
		if (array_key_exists('limit', $params) && is_numeric($params['limit']))
		{
			$gridParamsDto->limit = $params['limit'];
		}

		if (array_key_exists('offset', $params) && is_numeric($params['offset']))
		{
			$gridParamsDto->offset = $params['offset'];
		}

		return $gridParamsDto;
	}
}