<?php

namespace Bitrix\Sign\Agent\Member;

use Bitrix\Sign\Debug\Logger;
use Bitrix\Sign\Operation\Member\Reminder\CheckForgottenReminder;
use Bitrix\Sign\Operation\Member\Reminder\PlanNextRemindDate;
use Bitrix\Sign\Operation\Member\Reminder\Send;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\NotifyCalculationService;
use Bitrix\Sign\Type\DocumentStatus;

final class SigningReminderAgent
{
	public static function getPlanNextRemindDateAgentName(int $documentId): string
	{
		return "\\Bitrix\\Sign\\Agent\\Member\\SigningReminderAgent::planNextRemindDate($documentId);";
	}

	public static function planNextRemindDate(int $documentId): string
	{
		$container = Container::instance();
		$documentRepository = $container->getDocumentRepository();

		$document = $documentRepository->getById($documentId);
		if ($document === null)
		{
			return '';
		}
		if (DocumentStatus::isFinalByDocument($document))
		{
			return '';
		}

		$checkForgottenReminderAgentResult = (new CheckForgottenReminder($document))->launch();
		if ($checkForgottenReminderAgentResult->isForgotten)
		{
			return '';
		}

		$result = (new PlanNextRemindDate($document, (new NotifyCalculationService)->getPlanMemberAndSendReminderLimit($documentId)))->launch();
		if (!$result->isSuccess())
		{
			$logger = Logger::getInstance();
			$logger->warning(
				'Failed to plan next remind date for document {documentId}: ' . implode('|| ', $result->getErrorMessages()),
				[
					'documentId' => $documentId,
				],
			);
		}

		return static::getPlanNextRemindDateAgentName($documentId);
	}

	public static function getNotifyAgentName(int $documentId): string
	{
		return "\\Bitrix\\Sign\\Agent\\Member\\SigningReminderAgent::notify($documentId);";
	}

	public static function notify(int $documentId): string
	{
		$documentRepository = Container::instance()->getDocumentRepository();
		$document = $documentRepository->getById($documentId);
		if ($document === null)
		{
			return '';
		}
		if (DocumentStatus::isFinalByDocument($document))
		{
			return '';
		}

		$checkForgottenReminderAgentResult = (new CheckForgottenReminder($document))->launch();
		if ($checkForgottenReminderAgentResult->isForgotten)
		{
			return '';
		}

		$result = (new Send($document, (new NotifyCalculationService)->getPlanMemberAndSendReminderLimit($documentId)))->launch();
		if (!$result->isSuccess())
		{
			$logger = Logger::getInstance();
			$logger->warning(
				'Failed to send signing remind for document {documentId}: ' . implode('|| ', $result->getErrorMessages()),
				[
					'documentId' => $documentId,
				],
			);
		}

		return static::getNotifyAgentName($documentId);
	}
}