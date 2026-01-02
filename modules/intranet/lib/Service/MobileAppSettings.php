<?php

namespace Bitrix\Intranet\Service;

use Bitrix\Intranet\Contract\OptionContract;
use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Dto\EntitySelector\EntitySelectorCodeDto;
use Bitrix\Intranet\Internal\Service\EntitySelectorCodeService;

class MobileAppSettings
{
	private const DEFAULT_FOR_ALL_RIGHT_LIST = ['UA'];

	public function __construct(
		private OptionContract $option,
		private CurrentUser $currentUser,
		private EntitySelectorCodeService $codeService,
	)
	{
	}

	public function isReady(): bool
	{
		return $this->option->get('mobile_app_is_ready_to_ban_screenshots', 'Y') === 'Y';
	}

	public function canTakeScreenshot(?int $userId = null): bool
	{
		if (!$this->isTakeScreenshotDisabled())
		{
			return true;
		}

		$userId ??= $this->currentUser->getId();

		if (!$userId)
		{
			return false;
		}

		return !$this->codeService->isUserBelongsToEntitySelectorCode($userId, $this->getTakeScreenshotRights());
	}

	public function isTakeScreenshotDisabled(): bool
	{
		return $this->option->get('copy_screenshot_disabled', 'N') === 'Y';
	}

	public function getTakeScreenshotRightsList(): array
	{
		$option = $this->option->get('copy_screenshot_disabled_rights', null);

		if (!is_string($option))
		{
			return self::DEFAULT_FOR_ALL_RIGHT_LIST;
		}

		$value = unserialize($option, ['allowed_classes' => false]);

		return is_array($value) ? $value : self::DEFAULT_FOR_ALL_RIGHT_LIST;
	}

	public function getTakeScreenshotRights(): EntitySelectorCodeDto
	{
		return $this->codeService->createEntitySelectorCodeDtoFromCodeList($this->getTakeScreenshotRightsList());
	}

	public function setTakeScreenshotRights(array $rights): void
	{
		$this->option->set('copy_screenshot_disabled_rights', serialize($rights));
	}

	public function canCopyText(?int $userId = null): bool
	{
		if (!$this->isCopyTextDisabled())
		{
			return true;
		}

		$userId ??= $this->currentUser->getId();

		if (!$userId)
		{
			return false;
		}

		return !$this->codeService->isUserBelongsToEntitySelectorCode($userId, $this->getCopyTextRights());
	}

	public function isCopyTextDisabled(): bool
	{
		return $this->option->get('copy_text_disabled', 'N') === 'Y';
	}

	public function getCopyTextRights(): EntitySelectorCodeDto
	{
		return $this->codeService->createEntitySelectorCodeDtoFromCodeList($this->getCopyTextRightsList());
	}

	public function getCopyTextRightsList(): array
	{
		$option = $this->option->get('copy_text_disabled_rights', null);

		if (!is_string($option))
		{
			return self::DEFAULT_FOR_ALL_RIGHT_LIST;
		}

		$value = unserialize($option, ['allowed_classes' => false]);

		return is_array($value) ? $value : self::DEFAULT_FOR_ALL_RIGHT_LIST;
	}

	public function setCopyTextRights(array $rights): void
	{
		$this->option->set('copy_text_disabled_rights', serialize($rights));
	}

	public function setAllowScreenshot(bool $allow): void
	{
		$this->option->set(
			'copy_screenshot_disabled',
			$allow ? 'N' : 'Y',
		);
	}

	public function setAllowCopyText(bool $allow): void
	{
		$this->option->set(
			'copy_text_disabled',
			$allow ? 'N' : 'Y',
		);
	}
}
