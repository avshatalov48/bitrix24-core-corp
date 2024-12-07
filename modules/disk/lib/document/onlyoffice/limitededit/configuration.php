<?php
declare(strict_types=1);

namespace Bitrix\Disk\Document\OnlyOffice\LimitedEdit;

use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;

final class Configuration
{
	public const OPTION_LIMIT_EDIT = 'edit-time-limited-onlyoffice-access';

	public function __construct()
	{
	}

	/**
	 * Enables limited document edit.
	 * When somebody edits document first time on the portal,
	 * we setup agent to get the trial feature back.
	 * @see \Bitrix\Disk\Document\OnlyOffice\Bitrix24Scenario::trackFirstEditForLimitedEdit
	 * @see DisablerAgent::disable
	 * @return void
	 * @throws ArgumentOutOfRangeException
	 */
	public function enableLimitEdit(): void
	{
		Option::set('disk', self::OPTION_LIMIT_EDIT, 'Y');
	}

	/**
	 * Shows if limit edit was disabled.
	 * It's neccessary to understand that limit edit was disabled by value 'N' becase
	 * it happens when we trial period is over.
	 *
	 * It means that we already have disabled time limited edit and we should not enable it again.
	 * @return bool
	 */
	public function wasLimitEditDisabled(): bool
	{
		return Option::get('disk', self::OPTION_LIMIT_EDIT, 'xxx') === 'N';
	}

	public function isLimitEditEnabled(): bool
	{
		return Option::get('disk', self::OPTION_LIMIT_EDIT, 'N') === 'Y';
	}

	public function disableLimitEdit(): void
	{
		Option::set('disk', self::OPTION_LIMIT_EDIT, 'N');
	}
}