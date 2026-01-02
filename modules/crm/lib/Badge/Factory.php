<?php

namespace Bitrix\Crm\Badge;

use Bitrix\Crm\Badge\Type\AiCallFieldsFillingResult;
use Bitrix\Crm\Badge\Type\AiCallScoringStatus;
use Bitrix\Crm\Badge\Type\BizprocWorkflowStatus;
use Bitrix\Crm\Badge\Type\BookingStatus;
use Bitrix\Crm\Badge\Type\CalendarSharingStatus;
use Bitrix\Crm\Badge\Type\CallStatus;
use Bitrix\Crm\Badge\Type\CopilotCallAssessmentStatus;
use Bitrix\Crm\Badge\Type\MailMessageDeliveryStatus;
use Bitrix\Crm\Badge\Type\OpenLineStatus;
use Bitrix\Crm\Badge\Type\PaymentStatus;
use Bitrix\Crm\Badge\Type\RestAppStatus;
use Bitrix\Crm\Badge\Type\SmsStatus;
use Bitrix\Crm\Badge\Type\TaskStatus;
use Bitrix\Crm\Badge\Type\TodoStatus;
use Bitrix\Crm\Badge\Type\WorkflowCommentStatus;
use Bitrix\Main\ArgumentException;

final class Factory
{
	public static function getBadgeInstance(string $type, string $value): Badge
	{
		$className = match ($type) {
			Badge::CALL_STATUS_TYPE => CallStatus::class,
			Badge::PAYMENT_STATUS_TYPE => PaymentStatus::class,
			Badge::OPENLINE_STATUS_TYPE => OpenLineStatus::class,
			Badge::REST_APP_TYPE => RestAppStatus::class,
			Badge::SMS_STATUS_TYPE => SmsStatus::class,
			Badge::CALENDAR_SHARING_STATUS_TYPE => CalendarSharingStatus::class,
			Badge::TASK_STATUS_TYPE => TaskStatus::class,
			Badge::MAIL_MESSAGE_DELIVERY_STATUS_TYPE => MailMessageDeliveryStatus::class,
			Badge::AI_CALL_FIELDS_FILLING_RESULT => AiCallFieldsFillingResult::class,
			Badge::BIZPROC_WORKFLOW_STATUS_TYPE => BizprocWorkflowStatus::class,
			Badge::WORKFLOW_COMMENT_STATUS_TYPE => WorkflowCommentStatus::class,
			Badge::TODO_STATUS_TYPE => TodoStatus::class,
			Badge::COPILOT_CALL_ASSESSMENT_STATUS_TYPE => CopilotCallAssessmentStatus::class,
			Badge::AI_CALL_SCORING_STATUS => AiCallScoringStatus::class,
			Badge::BOOKING_STATUS_TYPE => BookingStatus::class,
			default => throw new ArgumentException('Unknown badge type: ' . $type),
		};

		return new $className($value);
	}
}
