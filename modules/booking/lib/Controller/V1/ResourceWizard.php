<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Controller\V1\Response\ResourceWizardResponseResponse;
use Bitrix\Booking\Entity\Slot\Range;
use Bitrix\Booking\Integration;
use Bitrix\Booking\Integration\Notifications\TemplateRepository;
use Bitrix\Booking\Internals\Notifications\MessageSenderPicker;
use Bitrix\Booking\Internals\NotificationType;
use Bitrix\Booking\Internals\Query\AdvertisingResourceType\GetListHandler;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Request;

class ResourceWizard extends BaseController
{
	private TemplateRepository $templateRepository;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->templateRepository = new TemplateRepository();
	}

	public function getAction(): ResourceWizardResponseResponse
	{
		return $this->handleRequest(function()
		{
			return new ResourceWizardResponseResponse(
				advertisingResourceTypes: (new GetListHandler())(),
				notificationsSettings: $this->getNotificationsSettings(),
				companyScheduleSlots: $this->getCompanyScheduleSlots(),
				isCompanyScheduleAccess: $this->isCompanyScheduleAccess(),
				weekStart: $this->getWeekStart(),
			);
		});
	}

	private function getNotificationsSettings(): array
	{
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
				function (NotificationType $notificationType) {
					return [
						'type' => $notificationType->value,
						'templates' => $this->templateRepository->getTemplatesByNotificationType($notificationType),
					];
				},
				NotificationType::cases(),
			),
		];
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
		return CurrentUser::get()->isAdmin();
	}

	private function getWeekStart(): string
	{
		return Integration\Calendar\Schedule::getWeekStart();
	}
}
