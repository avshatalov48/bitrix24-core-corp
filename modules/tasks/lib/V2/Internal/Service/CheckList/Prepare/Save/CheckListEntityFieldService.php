<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\CheckList\Prepare\Save;

use Bitrix\Tasks\V2\Internal\Service\TariffService;

class CheckListEntityFieldService
{
	public function __construct(
		private readonly TariffService $tariffService,
	)
	{

	}

	public function prepare(array $items): array
	{
		if ($this->tariffService->isStakeholderAvailable())
		{
			return $items;
		}

		foreach ($items as &$item)
		{
			$accompliceNames = array_column($item['accomplices'] ?? [], 'name');
			$auditorNames = array_column($item['auditors'] ?? [], 'name');

			$names = array_merge($accompliceNames, $auditorNames);

			$title = trim(str_replace($names, '', $item['title']));

			if (!empty($title))
			{
				$item['title'] = $title;
			}

			unset($item['accomplices'], $item['auditors']);
		}

		return $items;
	}
}