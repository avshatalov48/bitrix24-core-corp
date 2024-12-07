<?php

namespace Bitrix\Recyclebin\Controller\Action;

use Bitrix\Main\UI\Filter\Options;
use Bitrix\Recyclebin\Internals\BatchActionManager;

trait PrepareTrait
{
	protected function doAction(array $params): array
	{
		$data = $this->getData($params);
		$hash = $data['HASH'];

		$batchActionManager = new BatchActionManager();
		$batchActionManager
			->addToSession($this->getDataSessionName(), $hash, $data)
			->deleteFromSession($this->getProgressSessionName(), $hash)
		;

		return [
			'hash' => $hash,
		];
	}

	protected function getData(array $params): array
	{
		$gridId = $params['gridId'];
		$entityIds = $params['entityIds'] ?? null;

		$data = [
			'GRID_ID' => $gridId,
		];

		if (is_array($entityIds))
		{
			sort($entityIds, SORT_NUMERIC);
			$hash = md5(implode(':', [mb_strtoupper($gridId), implode(',', $entityIds)]));

			return array_merge($data, [
				'HASH' => $hash,
				'ENTITY_IDS' => $entityIds
			]);
		}

		$filter = (new Options($gridId))->getFilter();

		ksort($filter, SORT_STRING);
		$hash = md5(
			implode(':', [
				mb_strtoupper($gridId),
				implode(',', array_map(static fn($k, $v) => "{$k}:{$v}", array_keys($filter), $filter))
			])
		);

		return array_merge($data, [
			'HASH' => $hash,
			'FILTER' => $filter,
		]);
	}
}
