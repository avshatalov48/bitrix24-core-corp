<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Internal;

enum Type: string
{
	case OneDayNotViewed = 'OneDayNotViewed';
	case TwoDaysNotViewed = 'TwoDaysNotViewed';
	case TooManyTasks = 'TooManyTasks';
	case InviteToMobile = 'InviteToMobile';
	case ResponsibleInvitationNotAcceptedOneDay = 'ResponsibleInvitationNotAcceptedOneDay';
	case ResponsibleInvitationAccepted = 'ResponsibleInvitationAccepted';
	case InvitedResponsibleNotViewTaskTwoDays = 'InvitedResponsibleNotViewTaskTwoDays';
}
