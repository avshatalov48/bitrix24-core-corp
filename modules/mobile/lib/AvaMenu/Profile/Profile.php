<?php

namespace Bitrix\Mobile\AvaMenu\Profile;

use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Mobile\AvaMenu\Profile\Type\BaseType;
use Bitrix\Mobile\Context;

class Profile
{
	private Context $context;
	private BaseType $type;

	public function __construct()
	{
		$this->context = new Context();
		$this->type = $this->getType();
	}

	public function getUserType(): string
	{
		if ($this->context->isCollaber)
		{
			return 'collaber';
		}

		return "user";
	}

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

	public function getAvatar(): array
	{
		return $this->type->getAvatar();
	}

	public function getMainData($reloadFromDb = false): array
	{

		$title = $this->getTitle($reloadFromDb);
		$imageUrl = $this->getImageUrl($reloadFromDb);

		$mainData = [
			'title' => $title,
			'imageUrl' => $imageUrl,
		];

		$profileStyle = $this->type->getStyle();
		if (is_array($profileStyle))
		{
			$mainData = [...$mainData, ...$profileStyle];
		}

		return $mainData;
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
			true,
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
				$scr = $avatar["src"];
				$url = str_starts_with($scr, 'http')
					? $scr
					: UrlManager::getInstance()->getHostUrl() . $scr;
				$url = Uri::urnEncode($url);
			}
			else
			{
				$url = '';

				return $url;
			}
		}
		else
		{
			$url = '';

			return $url;
		}

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

	private function getType(): BaseType
	{
		$profileTypeClass = Type\User::class;
		if ($this->context->isCollaber)
		{
			$profileTypeClass = Type\Collaber::class;
		}
		else if ($this->context->extranet)
		{
			$profileTypeClass = Type\Extranet::class;
		}

		return new $profileTypeClass($this->getTitle(), $this->getImageUrl());
	}

	private function shouldShowAhaMoment(): string
	{
		return (new \Bitrix\Mobile\Controller\AvaMenu())->getAhaMomentStatusAction();
	}
}
