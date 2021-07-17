<?php
namespace Bitrix\Tasks\Rest\Controllers\TourGuide;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Tasks\TourGuide;

/**
 * Class FirstTimelineTaskCreation
 *
 * @package Bitrix\Tasks\Rest\Controllers\TourGuide
 */
class FirstTimelineTaskCreation extends Controller
{
	public function finishAction(): bool
	{
		/** @var TourGuide\FirstTimelineTaskCreation $firstTimelineTaskCreation */
		$firstTimelineTaskCreation = TourGuide\FirstTimelineTaskCreation::getInstance(CurrentUser::get()->getId());
		$firstTimelineTaskCreation->finish();

		return true;
	}

	public function markShowedStepAction(): bool
	{
		return true;
	}
}