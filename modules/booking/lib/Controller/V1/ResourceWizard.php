<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Controller\V1\Response\ResourceWizardResponseResponse;
use Bitrix\Booking\Entity\Enum\Notification\ReminderNotificationDelay;
use Bitrix\Booking\Entity\Slot\Range;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Internals\Integration;
use Bitrix\Booking\Internals\Integration\Notifications\TemplateRepository;
use Bitrix\Booking\Internals\Integration\Notifications\LegalEntityProvider;
use Bitrix\Booking\Internals\Service\Notifications\MessageSenderPicker;
use Bitrix\Booking\Internals\Service\Notifications\NotificationType;
use Bitrix\Booking\Internals\Service\OptionDictionary;
use Bitrix\Booking\Internals\Service\Time;
use Bitrix\Booking\Provider\AdsProvider;
use Bitrix\Booking\Service\BookingFeature;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;

class ResourceWizard extends BaseController
{
	private int $userId;
	private TemplateRepository $templateRepository;
	private LegalEntityProvider $notificationsLegalEntityProvider;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->userId = (int)CurrentUser::get()->getId();
		$this->templateRepository = new TemplateRepository();
		$this->notificationsLegalEntityProvider = new LegalEntityProvider();
	}

	public function getAction(): ResourceWizardResponseResponse|null
	{
		BookingFeature::turnOnTrialIfPossible();

		try
		{
			return new ResourceWizardResponseResponse(
				advertisingResourceTypes: (new AdsProvider())->getAdsResourceTypes(),
				notificationsSettings: $this->getNotificationsSettings(),
				companyScheduleSlots: $this->getCompanyScheduleSlots(),
				isCompanyScheduleAccess: $this->isCompanyScheduleAccess(),
				companyScheduleUrl: $this->getCompanyScheduleUrl(),
				weekStart: $this->getWeekStart(),
				isChannelChoiceAvailable: $this->notificationsLegalEntityProvider->isRu() === true,
			);
		}
		catch (Exception $e)
		{
			$this->addError(ErrorBuilder::buildFromException($e));

			return null;
		}
	}

	private function getNotificationsSettings(): array
	{
		$notificationsExpanded = $this->getNotificationsExpanded();
		$messageSender = MessageSenderPicker::pickCurrent();

		return [
			'senders' => [
				[
					'moduleId' => $messageSender->getModuleId(),
					'code' => $messageSender->getCode(),
					'canUse' => $messageSender->canUse(),
				],
			],
			'notifications' => array_map(
				fn (NotificationType $notificationType) => [
					'type' => $notificationType->value,
					'templates' => $this->templateRepository->getTemplatesByNotificationType($notificationType),
					'managerNotification' => $this->getManagerNotificationText($notificationType),
					'isExpanded' => $notificationsExpanded[$notificationType->value] ?? ($notificationType === NotificationType::Info),
					'settings' => $this->getSettings($notificationType),
				],
				NotificationType::cases(),
			),
		];
	}

	private function getNotificationsExpanded(): array
	{
		$option = Container::getOptionRepository()->get($this->userId, OptionDictionary::NotificationsExpanded);
		$notificationsExpanded = json_decode($option ?? '', true);

		return is_array($notificationsExpanded) ? $notificationsExpanded : [];
	}

	private function getManagerNotificationText(NotificationType $notificationType): string
	{
		Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/crm/lib/activity/provider/booking/bookingtodo.php');

		// TODO: убрать fallback, когда сольётся ветка booking/crm-todo
		return match ($notificationType) {
			NotificationType::Confirmation => Loc::getMessage('CRM_ACTIVITY_PROVIDER_BOOKING_TODO_DESCRIPTION_CONFIRM') ?? Loc::getMessage('BOOKING_CONTROLLER_RESOURCE_WIZARD_TODO_DESCRIPTION_CONFIRM'),
			NotificationType::Delayed => Loc::getMessage('CRM_ACTIVITY_PROVIDER_BOOKING_TODO_DESCRIPTION_DELAY') ?? Loc::getMessage('BOOKING_CONTROLLER_RESOURCE_WIZARD_TODO_DESCRIPTION_DELAY'),
			default => '',
		};
	}

	private function getSettings(NotificationType $notificationType): array
	{
		return match ($notificationType)
		{
			NotificationType::Info => [
				'notification' => [
					'delayValues' => [
						[
							'name' => Loc::getMessage('BOOKING_CONTROLLER_RESOURCE_WIZARD_INFO_IMMEDIATELY'),
							'value' => 0,
						],
						[
							'name' => Loc::getMessage('BOOKING_CONTROLLER_RESOURCE_WIZARD_INFO_IN_TEN_MINUTES'),
							'value' => 10 * Time::SECONDS_IN_MINUTE,
						],
						[
							'name' => Loc::getMessage('BOOKING_CONTROLLER_RESOURCE_WIZARD_INFO_IN_HOUR'),
							'value' => Time::SECONDS_IN_HOUR,
						],
					],
				],
			],
			NotificationType::Confirmation => [
				'notification' => [
					'delayValues' => [
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_CONFIRM_NOTIFICATION_BEFORE_ONE_WEEK'
							),
							'value' => 7 * Time::SECONDS_IN_DAY,
						],
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_CONFIRM_NOTIFICATION_BEFORE_THREE_DAYS'
							),
							'value' => 3 * Time::SECONDS_IN_DAY,
						],
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_CONFIRM_NOTIFICATION_BEFORE_ONE_DAY'
							),
							'value' => Time::SECONDS_IN_DAY,
						],
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_CONFIRM_NOTIFICATION_BEFORE_THREE_HOURS'
							),
							'value' => 3 * Time::SECONDS_IN_HOUR,
						],
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_CONFIRM_NOTIFICATION_BEFORE_ONE_HOUR'
							),
							'value' => Time::SECONDS_IN_HOUR,
						],
					],
					'repeatValues' => [
						[
							'name' => Loc::getMessage('BOOKING_CONTROLLER_RESOURCE_WIZARD_CONFIRM_ZERO_CNT'),
							'value' => 0,
						],
						[
							'name' => Loc::getMessage('BOOKING_CONTROLLER_RESOURCE_WIZARD_CONFIRM_ONE_CNT'),
							'value' => 1,
						],
						[
							'name' => Loc::getMessage('BOOKING_CONTROLLER_RESOURCE_WIZARD_CONFIRM_TWO_CNT'),
							'value' => 2,
						],
						[
							'name' => Loc::getMessage('BOOKING_CONTROLLER_RESOURCE_WIZARD_CONFIRM_THREE_CNT'),
							'value' => 3,
						],
					],
					'repeatIntervalValues' => [
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_CONFIRM_NO_RESPONSE_WITHIN_ONE_DAY'
							),
							'value' => Time::SECONDS_IN_DAY,
						],
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_CONFIRM_NO_RESPONSE_WITHIN_THREE_HOURS'
							),
							'value' => 3 * Time::SECONDS_IN_HOUR,
						],
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_CONFIRM_NO_RESPONSE_WITHIN_THIRTY_MINUTES'
							),
							'value' => 30 * Time::SECONDS_IN_MINUTE,
						],
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_CONFIRM_NO_RESPONSE_WITHIN_FIFTEEN_MINUTES'
							),
							'value' => 15 * Time::SECONDS_IN_MINUTE,
						],
					]
				],
				'counter' => [
					'delayValues' => [
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_CONFIRM_COUNTER_BEFORE_THREE_HOURS'
							),
							'value' => 3 * Time::SECONDS_IN_HOUR,
						],
						[
							'name' => Loc::getMessage('BOOKING_CONTROLLER_RESOURCE_WIZARD_CONFIRM_COUNTER_BEFORE_ONE_DAY'),
							'value' => Time::SECONDS_IN_DAY,
						],
						[
							'name' => Loc::getMessage('BOOKING_CONTROLLER_RESOURCE_WIZARD_CONFIRM_COUNTER_BEFORE_THREE_DAYS'),
							'value' => 3 * Time::SECONDS_IN_DAY,
						],
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_CONFIRM_COUNTER_BEFORE_ONE_WEEK'
							),
							'value' => 7 * Time::SECONDS_IN_DAY,
						],
					],
				],
			],
			NotificationType::Reminder => [
				'notification' => [
					'delayValues' => [
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_REMIND_IN_MORNING_ON_BOOKING_DAY'
							),
							'value' => ReminderNotificationDelay::Morning->value,
						],
						[
							'name' => Loc::getMessage('BOOKING_CONTROLLER_RESOURCE_WIZARD_REMIND_BEFORE_ONE_WEEK'),
							'value' => 7 * Time::SECONDS_IN_DAY,
						],
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_REMIND_BEFORE_THREE_DAYS'
							),
							'value' => 3 * Time::SECONDS_IN_DAY,
						],
						[
							'name' => Loc::getMessage('BOOKING_CONTROLLER_RESOURCE_WIZARD_REMIND_BEFORE_ONE_DAY'),
							'value' => Time::SECONDS_IN_DAY,
						],
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_REMIND_BEFORE_THREE_HOURS'
							),
							'value' => 3 * Time::SECONDS_IN_HOUR,
						],
					],
				],
			],
			NotificationType::Delayed => [
				'notification' => [
					'delayValues' => [
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_DELAYED_NOTIFICATION_IMMEDIATELY'
							),
							'value' => 0,
						],
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_DELAYED_NOTIFICATION_IN_FIVE_MINUTES'
							),
							'value' => 5 * Time::SECONDS_IN_MINUTE,
						],
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_DELAYED_NOTIFICATION_IN_TEN_MINUTES'
							),
							'value' => 10 * Time::SECONDS_IN_MINUTE,
						],
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_DELAYED_NOTIFICATION_IN_THIRTY_MINUTES'
							),
							'value' => 30 * Time::SECONDS_IN_MINUTE,
						],
					],
				],
				'counter' => [
					'delayValues' => [
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_DELAYED_COUNTER_IN_FIVE_MINUTES'
							),
							'value' => 5 * Time::SECONDS_IN_MINUTE,
						],
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_DELAYED_COUNTER_IN_TEN_MINUTES'
							),
							'value' => 10 * Time::SECONDS_IN_MINUTE,
						],
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_DELAYED_COUNTER_IN_FIFTEEN_MINUTES'
							),
							'value' => 15 * Time::SECONDS_IN_MINUTE,
						],
					]
				],
			],
			NotificationType::Feedback => [
				'notification' => [
					'delayValues' => [
						[
							'name' => Loc::getMessage(
								'BOOKING_CONTROLLER_RESOURCE_WIZARD_FEEDBACK_IMMEDIATELY'
							),
							'value' => 0,
						],
					],
				],
			],
			default => [],
		};
	}

	/**
	 * @return Range[]
	 */
	private function getCompanyScheduleSlots(): array
	{
		$companyRange = Integration\Calendar\Schedule::getRange();

		return [$companyRange];
	}

	private function isCompanyScheduleAccess(): bool
	{
		return Integration\Intranet\CompanySchedule::isScheduleSettingsAvailable();
	}

	private function getCompanyScheduleUrl(): string
	{
		return Integration\Intranet\CompanySchedule::getScheduleSettingsUrl();
	}

	private function getWeekStart(): string
	{
		return Integration\Calendar\Schedule::getWeekStart();
	}
}
