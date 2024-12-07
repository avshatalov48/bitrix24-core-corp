<?php

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Layout\Common;
use Bitrix\Crm\Timeline\Entity\CustomIconTable;
use Bitrix\Crm\Timeline\Entity\Object\CustomIcon;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Error;
use Bitrix\Main\File\Image;
use Bitrix\Main\File\Image\Info;
use Bitrix\Main\ORM\Data\Result;
use CFile;
use CRestUtil;

class Icon extends Base
{
	protected const ICON_WIDTH = 24;
	protected const ICON_HEIGHT = 24;
	protected const ICON_MIME = 'image/png';
	protected const PAGE_ID = 'icons';

	/**
	 * @var CustomIconTable
	 */
	protected CustomIconTable $iconTable;

	protected function init(): void
	{
		parent::init();

		$this->iconTable = new CustomIconTable();
	}

	// region ACTIONS
	// 'crm.timeline.icon.get' method handler
	public function getAction(string $code): ?array
	{
		$icon = $this->getIconDataByCode($code);
		if (!$icon)
		{
			$this->addError(
				new Error("Icon not found for code `$code`", ErrorCode::NOT_FOUND)
			);

			return null;
		}

		return [
			'icon' => $icon,
		];
	}

	// 'crm.timeline.icon.list' method handler
	public function listAction(): Page
	{
		$results = $this->getPreparedSystemIcons();

		$userIcons = $this->iconTable::getList([
			'select' => [
				'CODE',
				'FILE_ID',
			],
			'cache' => ['ttl' => 864000],
		])
			->fetchCollection()
			->getAll()
		;

		foreach ($userIcons as $icon)
		{
			$results[] = $this->getIconDataByObject($icon);
		}

		return new Page(
			self::PAGE_ID,
			$results,
			count($results)
		);
	}

	// 'crm.timeline.icon.add' method handler
	public function addAction(string $code, string $fileContent): ?array
	{
		if (!$this->isAdmin())
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		$fileId = $this->checkAndSaveFile($fileContent);
		if (!$fileId)
		{
			$this->addError(new Error('File not saved', 'FILE_SAVE_ERROR'));

			return null;
		}

		$result = $this->iconTable::add([
			'CODE' => $code,
			'FILE_ID' => $fileId,
		]);

		if ($result->isSuccess())
		{
			return [
				'icon' => $this->getIconDataByCode($code),
			];
		}

		foreach ($result->getErrors() as $error)
		{
			$this->addError($error);
		}

		return null;
	}

	// 'crm.timeline.icon.delete' method handler
	public function deleteAction(string $code): ?bool
	{
		if (!$this->isAdmin())
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		$icon = $this->getIcon($code);
		if (!$icon)
		{
			$this->addError(
				new Error("Icon not found for code `$code`", ErrorCode::NOT_FOUND)
			);

			return null;
		}

		$result = $this->delete($icon);
		if ($result->isSuccess())
		{
			return true;
		}

		foreach ($result->getErrors() as $error)
		{
			$this->addError($error);
		}

		return null;
	}
	// endregion

	protected function getPreparedSystemIcons(): array
	{
		$results = [];
		foreach ($this->getSystemIcons() as $code)
		{
			$results[] = $this->getIconDataByCode($code);
		}

		return $results;
	}

	protected function getSystemIcons(): array
	{
		return Common\Icon::getSystemIcons();
	}

	protected function getIconDataByCode(string $code): ?array
	{
		return Common\Icon::initFromCode($code)->getData();
	}

	protected function getIconDataByObject(CustomIcon $icon): ?array
	{
		return Common\Icon::initFromObject($icon)->getData();
	}

	protected function checkAndSaveFile(string $fileContent): ?int
	{
		$fileFields = CRestUtil::saveFile($fileContent);
		if (!is_array($fileFields))
		{
			$this->addError(new Error('Invalid image', ErrorCode::INVALID_ARG_VALUE));

			return null;
		}

		$info = (new Image($fileFields['tmp_name']))->getInfo();

		if (
			!($info instanceof Info)
			|| $info->getWidth() !== self::ICON_WIDTH
			|| $info->getHeight() !== self::ICON_HEIGHT
			|| $info->getMime() !== self::ICON_MIME
		)
		{
			$this->addError(new Error(
				'Only png ' . self::ICON_WIDTH . 'px on ' . self::ICON_HEIGHT . 'px is supported',
				ErrorCode::INVALID_ARG_VALUE
			));

			return null;
		}

		return $this->saveFile($fileFields);
	}

	protected function saveFile(array $fileFields): int
	{
		$fileFields['MODULE_ID'] = 'crm';

		return (int)CFile::saveFile($fileFields, 'crm');
	}

	protected function isAdmin(): bool
	{
		return Container::getInstance()->getUserPermissions()->isAdmin();
	}

	protected function getIcon(string $code): ?CustomIcon
	{
		return $this->iconTable::getByCode($code);
	}

	protected function delete(CustomIcon $icon): Result
	{
		return $icon->delete();
	}
}
