<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\V2\Internal\Entity\Task\View;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\Trait\CastTrait;

class ViewMapper
{
	use CastTrait;

	public function mapToEntity(array $view): View
	{
		/** @var null|DateTime $viewedDate */
		$viewedDate = $view['VIEWED_DATE'];

		return new View(
			taskId: (int)$view['TASK_ID'],
			userId: (int)$view['USER_ID'],
			viewedTs: $viewedDate?->getTimestamp(),
			isRealView: $view['IS_REAL_VIEW'] === 'Y',
		);
	}

	public function mapFromEntity(View $view): array
	{
		return [
			'TASK_ID' => $view->taskId,
			'USER_ID' => $view->userId,
			'VIEWED_DATE' => $this->castTimestamp($view->viewedTs),
			'IS_REAL_VIEW' => $view->isRealView ? 'Y' : 'N',
		];
	}
}