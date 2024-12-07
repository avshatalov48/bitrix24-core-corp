<?php

namespace Bitrix\Mobile\AvaMenu\Profile;

use Bitrix\Main\Localization\Loc;

class Profile
{
	public function getData($reloadFromDb = false): array
	{
		return [
			...$this->getMainData($reloadFromDb),
			'customData' => [
				'entryParams' => $this->getEntryParams(),
				'ahaMoment' => [
					'shouldShow' => $this->shouldShowAhaMoment(),
				],
			],
		];
	}

	public function getMainData($reloadFromDb = false): array
	{
		return [
			'title' => $this->getTitle($reloadFromDb),
			'imageUrl' => $this->getImageUrl($reloadFromDb),
		];
	}

	private function getTitle($reloadFromDb = false): string
	{
		global $USER;

		if ($reloadFromDb)
		{
			$res = \Bitrix\Main\UserTable::getList([
				'filter' => [
					'=ID' => $USER->getId(),
				],
				'select' => ['NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN'],
			]);

			if (!($userFields = $res->fetch()))
			{
				return '';
			}
		}
		else
		{
			$userFields = [
				"NAME" => $USER->GetFirstName(),
				"LAST_NAME" => $USER->GetLastName(),
				"SECOND_NAME" => $USER->GetSecondName(),
				"LOGIN" => $USER->GetLogin(),
			];
		}

		return \CUser::FormatName(
			\CSite::GetNameFormat(false),
			$userFields,
			false,
			false
		);
	}

	private function getImageUrl($reloadFromDb = false): string
	{
		global $USER;

		static $url = null;
		if ($url !== null && !$reloadFromDb)
		{
			return $url;
		}

		$selectFields = [
			'FIELDS' => ['PERSONAL_PHOTO'],
		];

		$dbUser = \CUser::GetList(
			["last_name" => "asc", "name" => "asc"],
			'',
			["ID" => $USER->GetID()],
			$selectFields
		);
		$curUser = $dbUser->Fetch();
		$avatarSource = "";

		if ((int)$curUser["PERSONAL_PHOTO"] > 0)
		{
			$avatar = \CFile::ResizeImageGet(
				$curUser["PERSONAL_PHOTO"],
				["width" => 100, "height" => 100],
				BX_RESIZE_IMAGE_EXACT,
				false
			);

			if ($avatar && $avatar["src"] <> '')
			{
				$avatarSource = $avatar["src"];
			}
			else
			{
				$url = '';

				return $url;
			}
		}

		$url = str_starts_with($avatarSource, 'http')
			? $avatarSource
			: \Bitrix\Main\Engine\UrlManager::getInstance()->getHostUrl() . $avatarSource;

		return $url;
	}

	private function getEntryParams(): array
	{
		global $USER;
		$canEditProfile = $USER->CanDoOperation('edit_own_profile');
		$editProfilePath = \Bitrix\MobileApp\Janative\Manager::getComponentPath("user.profile");

		return [
			'type' => 'component',
			'scriptPath' => $editProfilePath,
			'componentCode' => 'profile.view',
			'params' => [
				'userId' => $USER->getId(),
				'mode' => $canEditProfile ? 'edit' : 'view',
				'items' => [],
				'sections' => [
					['id' => 'top', 'backgroundColor' => '#f0f0f0'],
					['id' => '1', 'backgroundColor' => '#f0f0f0'],
				],
			],
			'rootWidget' => [
				'name' => $canEditProfile ? 'form' : 'list',
				'settings' => [
					'objectName' => 'form',
					'items' => [
						[
							// TODO: add title
							'id' => 'PERSONAL_PHOTO',
							'useLetterImage' => true,
							'color' => '#2e455a',
							'imageUrl' => $this->getImageUrl(),
							'type' => 'userpic',
							'title' => '',
							'sectionCode' => '0',
						],
						[
							'type' => 'loading',
							'sectionCode' => '1',
							'title' => '',
						],
					],
					'sections' => [
						['id' => '0', 'backgroundColor' => '#f0f0f0'],
						['id' => '1', 'backgroundColor' => '#f0f0f0'],
					],
					'groupStyle' => true,
					'title' => Loc::getMessage('PROFILE_INFO'),
				],
			],
		];
	}

	private function shouldShowAhaMoment(): string
	{
		return (new \Bitrix\Mobile\Controller\AvaMenu())->getAhaMomentStatusAction();
	}
}
