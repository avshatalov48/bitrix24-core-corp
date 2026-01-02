<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Widget\Content;

use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\User\Widget\BaseContent;
use Bitrix\Intranet\Util;
use Bitrix\Intranet;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use CComponentEngine;
use CFile;

class Main extends BaseContent
{
	private CurrentUser $currentUser;

	public function __construct(Intranet\User $user)
	{
		$this->currentUser = CurrentUser::get();
		parent::__construct($user);
	}

	public function getName(): string
	{
		return 'main';
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 */
	public function getConfiguration(): array
	{
		return [
			'fullName' => htmlspecialcharsbx($this->currentUser->getFormattedName()),
			'status' => $this->getStatus(),
			'workPosition' => $this->getWorkPosition(),
			'vacation' => $this->getVacationInformation(),
			'url' => $this->getProfileUrl(),
			'tools' => $this->getTools(),
			'userPhotoSrc' => $this->getUserPhotoSrc(),
			'role' => $this->user->getUserRole(),
			'id' => (int)$this->currentUser->getId(),
			'isTimemanAvailable' => self::isTimemanSectionAvailable(),
		];
	}

	public static function isTimemanSectionAvailable(): bool
	{
		return Intranet\Internal\Integration\Timeman\WorkTime::canUse();
	}

	private function getWorkPosition(): ?string
	{
		$workPosition = htmlspecialcharsbx($this->currentUser->getWorkPosition());

		if (!$workPosition && $this->user->isIntranet() && !$this->currentUser->isAdmin())
		{
			return Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_MAIN_WORK_POSITION_STUB_EMPLOYEE');
		}

		return $workPosition;
	}

	private function getVacationInformation(): ?string
	{
		$vacationInformation = Intranet\UserAbsence::isAbsentOnVacation($this->currentUser->getId(), true);

		if (isset($vacationInformation['DATE_TO_TS']) && $vacationInformation['DATE_TO_TS'] > 0)
		{
			$format = Application::getInstance()->getContext()->getCulture()?->getDayShortMonthFormat() ?? 'd M';
			$formattedDate = FormatDate($format, $vacationInformation['DATE_TO_TS']);

			return Loc::getMessage(
				'INTRANET_USER_WIDGET_CONTENT_MAIN_VACATION',
				['#DATE#' => $formattedDate],
			);
		}

		return null;
	}

	private function getStatus(): ?string
	{
		$userId = (int)$this->currentUser->getId();
		$status = Util::getUserStatus($userId);

		return Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_MAIN_' . $status);
	}

	public function getUserPhotoSrc(): string
	{
		$userPersonalPhotoSrc = '';
		$userPhotoId = (int)$this->currentUser->getPersonalPhotoId();

		if ($userPhotoId > 0
			&& $this->currentUser->isAuthorized()
			&& ($imageConfig = CFile::ResizeImageGet(
				$userPhotoId,
				[
					'width' => 100,
					'height' => 100,
				],
				BX_RESIZE_IMAGE_EXACT,
			))
			&& is_array($imageConfig)
			&& !empty($imageConfig['src'])
		) {
			$userPersonalPhotoSrc = $imageConfig['src'];
		}

		return (string)$userPersonalPhotoSrc;
	}

	private function getProfileUrl(): string
	{
		$isExtranet = Loader::includeModule('extranet') && \CExtranet::IsExtranetSite();
		$profileLink = $isExtranet ? SITE_DIR . 'contacts/personal' : SITE_DIR . 'company/personal';

		return CComponentEngine::MakePathFromTemplate(
			$profileLink . '/user/#user_id#/',
			['user_id' => $this->currentUser->getId()],
		);
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 */
	private function getTools(): ToolCollection
	{
		$isExtranet = Loader::includeModule('extranet') && \CExtranet::IsExtranetSite();
		$items = new ToolCollection();

		if ($isExtranet)
		{
			return $items;
		}

		/** @var Tool\BaseTool[] $tools */
		$tools = [
			Tool\MyDocuments::class,
			Tool\Security::class,
			Tool\SalaryVacation::class,
			Tool\Extension::class,
		];

		foreach ($tools as $tool)
		{
			if ($tool::isAvailable($this->user))
			{
				$items->add(new $tool($this->user));
			}
		}

		return $items;
	}
}
