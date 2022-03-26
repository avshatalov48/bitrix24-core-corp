<?php
namespace Bitrix\Tasks\Rest\Controllers\TourGuide;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Tasks\TourGuide;

/**
 * Class FirstScrumCreation
 *
 * @package Bitrix\Tasks\Rest\Controllers\TourGuide
 */
class FirstScrumCreation extends Controller
{
	public function finishAction(): bool
	{
		/** @var TourGuide\FirstScrumCreation $firstScrumCreation */
		$firstScrumCreation = TourGuide\FirstScrumCreation::getInstance(CurrentUser::get()->getId());
		$firstScrumCreation->finish();

		return true;
	}
}