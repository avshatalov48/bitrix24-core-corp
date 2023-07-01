<?php

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline\Entity\CustomLogoTable;
use Bitrix\Crm\Timeline\Entity\Object\CustomLogo;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Error;
use Bitrix\Main\File\Image;
use Bitrix\Main\File\Image\Info;
use Bitrix\Main\ORM\Data\Result;

class Logo extends Base
{
	protected const ICON_WIDTH = 60;
	protected const ICON_HEIGHT = 60;
	protected const ICON_MIME = 'image/png';
	protected const PAGE_ID = 'logos';

	/**
	 * @var CustomLogoTable
	 */
	protected CustomLogoTable $logoTable;

	protected function init(): void
	{
		parent::init();

		$this->logoTable = new CustomLogoTable();
	}

	public function getAction(string $code): ?array
	{
		$logo = $this->getLogoDataByCode($code);

		if (!$logo)
		{
			$this->addError(new Error("Logo not found for code `$code`", ErrorCode::NOT_FOUND));
			return null;
		}

		return [
			'logo' => $logo,
		];
	}

	public function listAction(): Page
	{
		$results = $this->getPreparedSystemLogos();

		$userLogos = $this->logoTable::getList([
			'select' => [
				'CODE',
				'FILE_ID',
			],
		])
			->fetchCollection()
			->getAll()
		;

		/**
		 * @var CustomLogo $logo
		 */
		foreach ($userLogos as $logo)
		{
			$results[] = $this->getLogoDataByObject($logo);
		}

		return new Page(self::PAGE_ID, $results, count($results));
	}

	protected function getPreparedSystemLogos(): array
	{
		$results = [];
		foreach ($this->getSystemLogoCodes() as $code)
		{
			$results[] = $this->getLogoDataByCode($code);
		}

		return $results;
	}

	protected function getSystemLogoCodes(): array
	{
		return \Bitrix\Crm\Service\Timeline\Layout\Common\Logo::getSystemLogoCodes();
	}

	protected function getLogoDataByCode(string $code): ?array
	{
		$factory = $this->getLogoFactory($code);

		return $this->getPreparedLogoData($factory->createLogo(), $factory->isSystem());
	}

	protected function getLogoDataByObject(CustomLogo $item): ?array
	{
		$factory = $this->getLogoFactory($item->getCode());
		$logo = $factory
			->createLogo()
			->setBackgroundSize()
			->setBackgroundUrl($item->getFileUri())
		;

		return $this->getPreparedLogoData($logo, $factory->isSystem());
	}

	protected function getLogoFactory(string $code): \Bitrix\Crm\Service\Timeline\Layout\Common\Logo
	{
		return \Bitrix\Crm\Service\Timeline\Layout\Common\Logo::getInstance($code);
	}

	protected function getPreparedLogoData(?\Bitrix\Crm\Service\Timeline\Layout\Body\Logo $logo, bool $isSystem): ?array
	{
		if (!$logo)
		{
			return null;
		}

		return [
			'code' => $logo->getIconCode(),
			'isSystem' => $isSystem,
			'fileUri' => $logo->getBackgroundUrl() ?? '',
		];
	}

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

		$result = $this->logoTable::add([
			'CODE' => $code,
			'FILE_ID' => $fileId,
		]);

		if ($result->isSuccess())
		{
			return [
				'logo' => $this->getLogoDataByCode($code),
			];
		}

		foreach ($result->getErrors() as $error)
		{
			$this->addError($error);
		}

		return null;
	}

	protected function checkAndSaveFile(string $fileContent): ?int
	{
		$fileFields = \CRestUtil::saveFile($fileContent);
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
		return (int) \CFile::saveFile($fileFields, 'crm');
	}

	public function deleteAction(string $code): ?bool
	{
		if (!$this->isAdmin())
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		$logo = $this->getLogo($code);

		if (!$logo)
		{
			$this->addError(new Error("Logo not found for code `$code`", ErrorCode::NOT_FOUND));
			return null;
		}

		$result = $this->delete($logo);
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

	protected function isAdmin(): bool
	{
		return Container::getInstance()->getUserPermissions()->isAdmin();
	}

	protected function getLogo(string $code): ?CustomLogo
	{
		return $this->logoTable::getByCode($code);
	}

	protected function delete(CustomLogo $logo): Result
	{
		return $logo->delete();
	}
}
