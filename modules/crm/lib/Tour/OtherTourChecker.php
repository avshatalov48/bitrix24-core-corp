<?php

namespace Bitrix\Crm\Tour;

trait OtherTourChecker
{
	private function isUserSeenOtherTour(Base $tour): bool
	{
		return $tour->isUserSeenTour();
	}
}
