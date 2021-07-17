<?php
namespace Bitrix\Tasks\Copy\Implement;

use Bitrix\Bizproc\Copy\Implement\WorkflowTemplate;

class Robots extends WorkflowTemplate
{
	private $mapStatusIdsCopiedDocumentTmp = [];

	public function __construct($targetDocumentType = [], $mapStatusIdsCopiedDocument = [])
	{
		parent::__construct($targetDocumentType, $mapStatusIdsCopiedDocument);

		// todo after bizproc change realized
		$this->mapStatusIdsCopiedDocumentTmp = $mapStatusIdsCopiedDocument;
	}

	public function prepareFieldsToCopy(array $fields)
	{
		$fields = parent::prepareFieldsToCopy($fields);

		if (is_array($fields['TEMPLATE']))
		{
			foreach ($fields['TEMPLATE'] as &$activity)
			{
				$activity = $this->updateChangeStageActivity($activity);
			}
		}

		return $fields;
	}

	private function updateChangeStageActivity($activity): array
	{
		if ($activity['Type'] === 'TasksChangeStageActivity')
		{
			if (isset($activity['Properties']['TargetStage']))
			{
				$activity['Properties']['TargetStage'] = $this->mapStatusIdsCopiedDocumentTmp[
					$activity['Properties']['TargetStage']
				];
			}
		}

		if (is_array($activity['Children']))
		{
			foreach ($activity['Children'] as &$child)
			{
				$child = $this->updateChangeStageActivity($child);
			}
		}

		return $activity;
	}
}