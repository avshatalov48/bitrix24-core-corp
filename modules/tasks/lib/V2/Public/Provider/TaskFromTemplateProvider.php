<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Provider;

use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Repository\TemplateRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Service\Task\Converter\TemplateConverter;

class TaskFromTemplateProvider
{
	public function __construct(
		private readonly TemplateRepositoryInterface $repository,
		private readonly TemplateConverter $converter,
	)
	{
	}

	public function getTaskByTemplateId(int $templateId): ?Task
	{
		$template = $this->repository->getById($templateId);

		if ($template === null)
		{
			return null;
		}

		return $this->converter->convert($template);
	}
}
