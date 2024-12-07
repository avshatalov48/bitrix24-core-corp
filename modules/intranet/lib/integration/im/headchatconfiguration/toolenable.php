<?php

namespace Bitrix\Intranet\Integration\Im\HeadChatConfiguration;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;

class ToolEnable extends Base
{
	private const AVATAR_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAUCSURBVHgB1VrPaxtHFH6z+mFZqo3c0tKGlMo04EKpJRdfnBQq/QPBOeeQBnLILckh10bOtYe6Nx8KdQ855JTgQ69qCk4uAa8LLQgKUWhwSwqtG2PFkqydvG8ibeTVamd2LTvKB+vVjkc778f33rx9K0FDQKHyX5YSiUWHPwrL+ghDJGWWhMj2TKuRpJqUzibFYhvW3t49uzRVo0NCUERAaCeVuiIcWeS7FCkabCnE8mGUCa3AK8Gdqx4LHw5SropmcymsIqEUmH3w/MbQBfeAPVL+dWF8yXS+kQJs9ZwcG7uDj3Q8qIlGo2TiDUs3ga1+QSaTG3R8wgMw2Ebh/u6ibmKgAooyzM2jpEwAspLEHcgQNGkghTrCl2kEEBQXvgp8xq6zWHsaIQghvrIXxn/sG/cOqIAF518PbYKwzYE95w3svhjg4KmMoPBAtpMJD+CAAp2AyVFETMQFnRgT6nxEKMyu75Z7B9yVOrn+EUXA/KRFlz9MqHMXWw1JK09atPa0TUMGqDTNVNrGhbuik0iUKQIg+Pefjh0QHoAnbn6cpNv51LA9knWSyavdC3XnqNY/+26Mbp5Kauc9fObQpd8aNES4Xojjqp1IFLVbsg9gfRPAO+c/wFKSJmKCdtqSKv86imYR0fVCWSlgCXGFQgJCgSamuJ5LeK6J1v5p0ze1Fu3sh1dEkPhSnaPS5/z7Mbo+raePDtW6VPSKpESjMWWBPhQSsHzpnbh23sP/HXUEYSYtlDGigGVftETIKhNc/unzVF/W8QLUuPR7Qx0rf7a094ySqSA709/Km34BQevlsh++/qNJt/7ad69XnuwHKgHh5ycjpFph5bhmM9t51WZ1Uk8bCI/g9EKrRCyCBwTlRf5+3Sh6bs+maCYzeBEEIWjjJ3wvQJdBXsR3oWSI9LptlP5nMlag8AAyiU54ANSCl/yAjRG7eoj0nDVSwOSGOyFKnmqA01UJcso8Pce5nbGtK5+R6nSA5Uxdr7sf4g0HShAd4iw8qrpABUw2GVgOh6pCfYJ1guP/8smEcbosvR0zUaCGtGKT5hlgq0nGWHu6PzAWZtIWnX1Pn8kAI0W5VWlJx3msmwdLmG71QQtPJMxTpcl66LPGeZqtuy1uhuxhUn0ik0CJrb2D7ofwpSnzkqFa1/PfQpM41mrdJQPc+rttFKTIRl7h1XhLGnsR6+hqqA7uKePn1+sVkw4zglSXp8H/QXn+20+SRl64Vm3y84ImL0tpb57JzKmIkiRZE1EkDWAZbFjYSZEl/AAKzU+mfP+n20+6u7lWeEIZZC2rM/6gZc69oEdh2inI5VDixFiUZ7l+VOtt9p55suBG1zQ3umquSdCu4MEb9CZAiNXNhfGL+Oiaz2o2l9Wu/AaAre72SV0F8ITPzvuORhzccF4Cddxr74T8+i76osf5LiAMapun09O9A30RyNF9biSpxDJxjJa8w30KqMiW8hqNGPjp62IvdbrwzYH2F2+tgms0IlC8P53xrRgCd5YCp1b5mlOrEv5Mpjzw/6RB4ZdnizIW++HY3xmA80xlsCFomtlr1gfPc1LKCh3i3UFI2Byw5/w470WoXsaRUwpW570oiDJehP+pAXvDkU6Zi78LNCx0BKd0etmeE6FSePQfe7Ai1G4XJTrbUTc+ST8LroSjCN7FUF6ddJVxLKsgJOU73b6cO+Fl5wMC2lyuPLYcx6ZM5m5UoXvxAp7NHYmLQrNGAAAAAElFTkSuQmCC';
	private const TOOLS_LIST = [
		'news',
		'instant_messenger',
		'calendar',
		'docs',
		'mail',
		'workgroups',
		'tasks',
		'crm',
		'automation',
		'inventory_management',
		'sign',
		'scrum',
		'invoices',
		'saleshub',
		'sites',
	];

	public function isValid(): bool
	{
		return parent::isValid() && in_array($this->getCode(), self::TOOLS_LIST);
	}

	public function getChatTitle(): string
	{
		return Loc::getMessage('INTRANET_INTEGRATION_IM_TOOL_ENABLE_REQUEST_CHAT_TITLE', [
			'#FULL_NAME#' => CurrentUser::get()->getFullName(),
		]) ?? '';
	}

	public function getAvatar(): string
	{
		return self::AVATAR_BASE64;
	}

	public function getBannerId(): string
	{
		return 'SupervisorEnableFeatureMessage';
	}

	public function getBannerDescription(): string
	{
		return Loc::getMessage('INTRANET_INTEGRATION_IM_TOOL_ENABLE_REQUEST_BANNER_DESCRIPTION') ?? '';
	}
}