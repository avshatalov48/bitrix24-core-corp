<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Enum\User\Profile;

enum SocialMediaType: string
{
	case TWITTER = 'twitter';
	case FACEBOOK = 'facebook';
	case LINKEDIN = 'linkedin';
	case XING = 'xing';
	case ZOOM = 'zoom';
	case SKYPE = 'skype'; // todo: rename to microsoft teams
}
