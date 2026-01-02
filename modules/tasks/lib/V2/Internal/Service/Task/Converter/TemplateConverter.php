<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Converter;

use Bitrix\Tasks\V2\Internal\Entity;

class TemplateConverter
{
	public function convert(Entity\Template $template): Entity\Task
	{
		return new Entity\Task(
			title:       $template->title,
			description: $template->description,
			creator:     $template->creator,
			responsible: $template->responsibleCollection->getFirstEntity(),
			deadlineTs:  $template->deadlineAfterTs,
			startPlanTs: $template->startDatePlanTs,
			endPlanTs:   $template->endDatePlanTs,
			fileIds:     $template->fileIds,
			checklist:   $template->checklist,
			group:       $template->group,
			priority:    $template->priority,
			accomplices: $template->accomplices,
			auditors:    $template->auditors,
			replicate:   $template->replicate,
		);
	}
}
