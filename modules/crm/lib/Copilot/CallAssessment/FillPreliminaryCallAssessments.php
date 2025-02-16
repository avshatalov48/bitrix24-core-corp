<?php

namespace Bitrix\Crm\Copilot\CallAssessment;

use Bitrix\Crm\Copilot\CallAssessment\Controller\CopilotCallAssessmentController;
use Bitrix\Crm\Copilot\CallAssessment\Enum\AutoCheckType;
use Bitrix\Crm\Copilot\CallAssessment\Enum\CallType;
use Bitrix\Crm\Copilot\CallAssessment\Enum\ClientType;
use Bitrix\Crm\Integration\AI\Model\QueueTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

final class FillPreliminaryCallAssessments
{
	private const AUTO_CHECK_TYPE_ID = AutoCheckType::FIRST_INCOMING;

	public static function isWaiting(): bool
	{
		return (Option::get('crm', 'waiting_call_assessment_rules_filling', 'N') === 'Y');
	}

	public function execute(): void
	{
		$controller = CopilotCallAssessmentController::getInstance();

		$collection = $controller->getList();
		if ($collection->isEmpty())
		{
			foreach ($this->getData() as $data)
			{
				$callAssessment = CallAssessmentItem::createFromArray($data);
				$controller->add($callAssessment);
			}
		}

		Option::delete('crm', ['name' => 'waiting_call_assessment_rules_filling']);
	}

	private function getData(): array
	{
		return [
			$this->getFirstImpressionAssessments(),
			$this->getDealSupportAssessments(),
			$this->getCommunicationEtiquetteAssessments(),
			$this->getObjectionManagementAssessments(),
			$this->getComplaintHandlingAssessments(),
			$this->getPresentationOfNewAssessments(),
			$this->getIncreaseLoyaltyAssessments(),
			$this->getSpecialOfferAssessments(),
		];
	}

	private function getFirstImpressionAssessments(): array
	{
		return [
			'title' => Loc::getMessage('FPCA_FIRST_IMPRESSION_TITLE'),
			'prompt' => Loc::getMessage('FPCA_FIRST_IMPRESSION_PROMPT'),
			'gist' => Loc::getMessage('FPCA_FIRST_IMPRESSION_GIST'),
			'clientTypeIds' => [ClientType::NEW->value],
			'callTypeId' => CallType::ALL->value,
			'isEnabled' => true,
			'autoCheckTypeId' => self::AUTO_CHECK_TYPE_ID->value,
			'jobId' => 0,
			'status' => QueueTable::EXECUTION_STATUS_SUCCESS,
			'code' => 'first_impression',
			'low_border' => CallAssessmentItem::LOW_BORDER_DEFAULT,
			'high_border' => CallAssessmentItem::HIGH_BORDER_DEFAULT,
		];
	}

	private function getDealSupportAssessments(): array
	{
		return [
			'title' => Loc::getMessage('FPCA_DEAL_SUPPORT_TITLE'),
			'prompt' => Loc::getMessage('FPCA_DEAL_SUPPORT_PROMPT'),
			'gist' => Loc::getMessage('FPCA_DEAL_SUPPORT_GIST'),
			'clientTypeIds' => [ClientType::IN_WORK->value],
			'callTypeId' => CallType::ALL->value,
			'isEnabled' => true,
			'autoCheckTypeId' => self::AUTO_CHECK_TYPE_ID->value,
			'jobId' => 0,
			'status' => QueueTable::EXECUTION_STATUS_SUCCESS,
			'code' => 'deal_support',
			'low_border' => CallAssessmentItem::LOW_BORDER_DEFAULT,
			'high_border' => CallAssessmentItem::HIGH_BORDER_DEFAULT,
		];
	}

	private function getCommunicationEtiquetteAssessments(): array
	{
		return [
			'title' => Loc::getMessage('FPCA_COMMUNICATION_ETIQUETTE_TITLE'),
			'prompt' => Loc::getMessage('FPCA_COMMUNICATION_ETIQUETTE_PROMPT'),
			'gist' => Loc::getMessage('FPCA_COMMUNICATION_ETIQUETTE_GIST'),
			'clientTypeIds' => [ClientType::NEW->value, ClientType::IN_WORK->value, ClientType::RETURN_CUSTOMER->value, ClientType::REPEATED_APPROACH->value],
			'callTypeId' => CallType::ALL->value,
			'isEnabled' => true,
			'autoCheckTypeId' => self::AUTO_CHECK_TYPE_ID->value,
			'jobId' => 0,
			'status' => QueueTable::EXECUTION_STATUS_SUCCESS,
			'code' => 'communication_etiquette',
			'low_border' => CallAssessmentItem::LOW_BORDER_DEFAULT,
			'high_border' => CallAssessmentItem::HIGH_BORDER_DEFAULT,
		];
	}

	private function getObjectionManagementAssessments(): array
	{
		return [
			'title' => Loc::getMessage('FPCA_OBJECTION_MANAGEMENT_TITLE'),
			'prompt' => Loc::getMessage('FPCA_OBJECTION_MANAGEMENT_PROMPT'),
			'gist' => Loc::getMessage('FPCA_OBJECTION_MANAGEMENT_GIST'),
			'clientTypeIds' => [ClientType::NEW->value, ClientType::IN_WORK->value, ClientType::RETURN_CUSTOMER->value, ClientType::REPEATED_APPROACH->value],
			'callTypeId' => CallType::ALL->value,
			'isEnabled' => true,
			'autoCheckTypeId' => self::AUTO_CHECK_TYPE_ID->value,
			'jobId' => 0,
			'status' => QueueTable::EXECUTION_STATUS_SUCCESS,
			'code' => 'objection_management',
			'low_border' => CallAssessmentItem::LOW_BORDER_DEFAULT,
			'high_border' => CallAssessmentItem::HIGH_BORDER_DEFAULT,
		];
	}

	private function getComplaintHandlingAssessments(): array
	{
		return [
			'title' => Loc::getMessage('FPCA_COMPLAINT_HANDLING_TITLE'),
			'prompt' => Loc::getMessage('FPCA_COMPLAINT_HANDLING_PROMPT'),
			'gist' => Loc::getMessage('FPCA_COMPLAINT_HANDLING_GIST'),
			'clientTypeIds' => [ClientType::NEW->value, ClientType::IN_WORK->value, ClientType::RETURN_CUSTOMER->value, ClientType::REPEATED_APPROACH->value],
			'callTypeId' => CallType::ALL->value,
			'isEnabled' => true,
			'autoCheckTypeId' => self::AUTO_CHECK_TYPE_ID->value,
			'jobId' => 0,
			'status' => QueueTable::EXECUTION_STATUS_SUCCESS,
			'code' => 'complaint_handling',
			'low_border' => CallAssessmentItem::LOW_BORDER_DEFAULT,
			'high_border' => CallAssessmentItem::HIGH_BORDER_DEFAULT,
		];
	}

	private function getPresentationOfNewAssessments(): array
	{
		return [
			'title' => Loc::getMessage('FPCA_PRESENTATION_OF_NEW_TITLE'),
			'prompt' => Loc::getMessage('FPCA_PRESENTATION_OF_NEW_PROMPT'),
			'gist' => Loc::getMessage('FPCA_PRESENTATION_OF_NEW_GIST'),
			'clientTypeIds' => [ClientType::NEW->value, ClientType::IN_WORK->value, ClientType::RETURN_CUSTOMER->value, ClientType::REPEATED_APPROACH->value],
			'callTypeId' => CallType::OUTGOING->value,
			'isEnabled' => true,
			'autoCheckTypeId' => self::AUTO_CHECK_TYPE_ID->value,
			'jobId' => 0,
			'status' => QueueTable::EXECUTION_STATUS_SUCCESS,
			'code' => 'presentation_of_new',
			'low_border' => CallAssessmentItem::LOW_BORDER_DEFAULT,
			'high_border' => CallAssessmentItem::HIGH_BORDER_DEFAULT,
		];
	}

	private function getIncreaseLoyaltyAssessments(): array
	{
		return [
			'title' => Loc::getMessage('FPCA_INCREASE_LOYALTY_TITLE'),
			'prompt' => Loc::getMessage('FPCA_INCREASE_LOYALTY_PROMPT'),
			'gist' => Loc::getMessage('FPCA_INCREASE_LOYALTY_GIST'),
			'clientTypeIds' => [ClientType::RETURN_CUSTOMER->value],
			'callTypeId' => CallType::ALL->value,
			'isEnabled' => true,
			'autoCheckTypeId' => self::AUTO_CHECK_TYPE_ID->value,
			'jobId' => 0,
			'status' => QueueTable::EXECUTION_STATUS_SUCCESS,
			'code' => 'increase_loyalty',
			'low_border' => CallAssessmentItem::LOW_BORDER_DEFAULT,
			'high_border' => CallAssessmentItem::HIGH_BORDER_DEFAULT,
		];
	}

	private function getSpecialOfferAssessments(): array
	{
		return [
			'title' => Loc::getMessage('FPCA_SPECIAL_OFFER_TITLE'),
			'prompt' => Loc::getMessage('FPCA_SPECIAL_OFFER_PROMPT'),
			'gist' => Loc::getMessage('FPCA_SPECIAL_OFFER_GIST'),
			'clientTypeIds' => [ClientType::NEW->value, ClientType::IN_WORK->value, ClientType::RETURN_CUSTOMER->value, ClientType::REPEATED_APPROACH->value],
			'callTypeId' => CallType::ALL->value,
			'isEnabled' => true,
			'autoCheckTypeId' => self::AUTO_CHECK_TYPE_ID->value,
			'jobId' => 0,
			'status' => QueueTable::EXECUTION_STATUS_SUCCESS,
			'code' => 'special_offer',
			'low_border' => CallAssessmentItem::LOW_BORDER_DEFAULT,
			'high_border' => CallAssessmentItem::HIGH_BORDER_DEFAULT,
		];
	}
}