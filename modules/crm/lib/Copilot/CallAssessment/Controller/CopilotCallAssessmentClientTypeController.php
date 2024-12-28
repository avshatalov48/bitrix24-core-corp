<?php

namespace Bitrix\Crm\Copilot\CallAssessment\Controller;

use Bitrix\Crm\Copilot\CallAssessment\Entity\CopilotCallAssessmentClientTypeTable;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Application;
use Bitrix\Main\DB\Result;
use Bitrix\Main\ORM\Data\AddResult;

final class CopilotCallAssessmentClientTypeController
{
	use Singleton;

	public function deleteByAssessmentId(int $assessmentId): Result
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$sql =
			'DELETE FROM b_crm_copilot_call_assessment_client_type '
			. ' WHERE ASSESSMENT_ID =' . $sqlHelper->convertToDbInteger($assessmentId)
		;

		return Application::getConnection()->query($sql);
	}

	public function add(int $assessmentId, int $clientTypeId): AddResult
	{
		return CopilotCallAssessmentClientTypeTable::add([
			'ASSESSMENT_ID' => $assessmentId,
			'CLIENT_TYPE_ID' => $clientTypeId,
		]);
	}

	public function getByAssessmentIds(array $assessmentIds): array
	{
		return CopilotCallAssessmentClientTypeTable::getList([
			'select' => ['*'],
			'filter' => ['@ASSESSMENT_ID' => $assessmentIds],
		])->fetchAll();
	}
}
