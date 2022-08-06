<?php

namespace Bitrix\Crm\Service\Broker;

use Bitrix\Crm\Service\Broker;
use Bitrix\Crm\Service\Router;
use Bitrix\Main\Application;
use Bitrix\Main\UserTable;

class User extends Broker
{
	protected const DEFAULT_PERSONAL_PHOTO_SIZE = 63;

	/** @var Router */
	protected $router;
	/** @var string */
	protected $nameFormat;

	public function __construct()
	{
		$this->router = \Bitrix\Crm\Service\Container::getInstance()->getRouter();
		$this->nameFormat = Application::getInstance()->getContext()->getCulture()->getNameFormat();
	}

	public function getName(int $id): ?string
	{
		return $this->getById($id)['FORMATTED_NAME'] ?? null;
	}

	public function getWorkPosition(int $id): ?string
	{
		return $this->getById($id)['WORK_POSITION'] ?? null;
	}

	public function isRealUser(int $id): bool
	{
		return ($this->getById($id)['IS_REAL_USER'] ?? 'N') === 'Y';
	}

	protected function loadEntry(int $id): ?array
	{
		$userRaw = UserTable::getList([
			'select' => [
				'ID', 'LOGIN', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'TITLE', 'PERSONAL_PHOTO', 'WORK_POSITION', 'IS_REAL_USER',
			],
			'filter' => ['=ID' => $id]
		])->fetch();

		if (!is_array($userRaw))
		{
			return null;
		}

		return $this->normalizeUser($userRaw);
	}

	/**
	 * @inheritDoc
	 */
	protected function loadEntries(array $ids): array
	{
		$userList = UserTable::getList([
			'select' => [
				'ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'TITLE', 'PERSONAL_PHOTO', 'WORK_POSITION', 'IS_REAL_USER',
			], 'filter' => [
				'=ID' => $ids,
			],
		]);

		$entries = [];
		while($userRaw = $userList->fetch())
		{
			$user = $this->normalizeUser($userRaw);

			$entries[$user['ID']] = $user;
		}

		return $entries;
	}

	protected function normalizeUser(array $user): array
	{
		$user['ID'] = (int)$user['ID'];
		$user['FORMATTED_NAME'] = \CUser::FormatName($this->nameFormat, $user, false, false);
		$user['SHOW_URL'] = $this->router->getUserPersonalUrl($user['ID']);
		if($user['PERSONAL_PHOTO'] > 0)
		{
			$photo = \CFile::ResizeImageGet($user['PERSONAL_PHOTO'], [
				'width' => static::DEFAULT_PERSONAL_PHOTO_SIZE,
				'height' => static::DEFAULT_PERSONAL_PHOTO_SIZE,
			], BX_RESIZE_IMAGE_EXACT, true, false, true);
			if($photo)
			{
				$user['PHOTO_URL'] = $photo['src'];
			}
		}

		return $user;
	}
}