<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Favorites;

use Bitrix\Booking\Entity\Favorites\Favorites;
use Bitrix\Main\Result;

class FavoritesResult extends Result
{
	public function __construct(private Favorites $favorites)
	{
		parent::__construct();
	}

	public function getFavorites(): Favorites
	{
		return $this->favorites;
	}
}
