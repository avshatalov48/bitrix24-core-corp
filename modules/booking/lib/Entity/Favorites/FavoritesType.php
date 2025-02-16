<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Favorites;

enum FavoritesType: string
{
	case Added = 'ADDED';
	case Removed = 'REMOVED';
}
