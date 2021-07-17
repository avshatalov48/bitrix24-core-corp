<?php
namespace Bitrix\Tasks\Rest\Controllers\TourGuide;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Tasks\TourGuide;

/**
 * Class FirstProjectCreation
 *
 * @package Bitrix\Tasks\Rest\Controllers\TourGuide
 */
class FirstProjectCreation extends Controller
{
	public function finishAction(): bool
	{
		/** @var TourGuide\FirstProjectCreation $firstProjectCreation */
		$firstProjectCreation = TourGuide\FirstProjectCreation::getInstance(CurrentUser::get()->getId());
		$firstProjectCreation->finish();

		return true;
	}
}