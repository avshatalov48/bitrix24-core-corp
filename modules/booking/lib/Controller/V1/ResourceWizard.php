<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Controller\V1\Response\ResourceWizardResponseResponse;
use Bitrix\Booking\Entity\Slot\Range;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Internals\Integration;
use Bitrix\Booking\Internals\Integration\Notifications\TemplateRepository;
use Bitrix\Booking\Internals\Service\Notifications\MessageSenderPicker;
use Bitrix\Booking\Internals\Service\Notifications\NotificationType;
use Bitrix\Booking\Provider\AdsProvider;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Request;

class ResourceWizard extends BaseController
{
	private TemplateRepository $templateRepository;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->templateRepository = new TemplateRepository();
	}

	public function getAction(): ResourceWizardResponseResponse|null
	{
		try
		{
			return new ResourceWizardResponseResponse(
				advertisingResourceTypes: (new AdsProvider())->getAdsResourceTypes(),
				notificationsSettings: $this->getNotificationsSettings(),
				companyScheduleSlots: $this->getCompanyScheduleSlots(),
				isCompanyScheduleAccess: $this->isCompanyScheduleAccess(),
				weekStart: $this->getWeekStart(),
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
