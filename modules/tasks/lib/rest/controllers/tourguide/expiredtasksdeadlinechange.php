<?php
namespace Bitrix\Tasks\Rest\Controllers\TourGuide;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Tasks\TourGuide;

/**
 * Class ExpiredTasksDeadlineChange
 *
 * @package Bitrix\Tasks\Rest\Controllers\TourGuide
 */
class ExpiredTasksDeadlineChange extends Controller
{
	public function proceedAction(): bool
	{
		/** @var TourGuide\ExpiredTasksDeadlineChange $expiredTour */
		$expiredTour = TourGuide\ExpiredTasksDeadlineChange::getInstance(CurrentUser::get()->getId());
		return $expiredTour->proceed();
	}

	public function markShowedStepAction(): bool
	{
		return true;
	}
}