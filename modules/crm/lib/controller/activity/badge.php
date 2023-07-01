<?php

namespace Bitrix\Crm\Controller\Activity;

use Bitrix\Crm\Badge\Model\CustomBadgeTable;
use Bitrix\Crm\Badge\Model\CustomBadge;
use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\CultureTable;
use Bitrix\Main\ORM\Data\Result;

class Badge extends Base
{
	protected const PAGE_ID = 'badges';

	/**
	 * @var CustomBadgeTable
	 */
	protected CustomBadgeTable $badgeTable;

	protected function init(): void
	{
		parent::init();

		$this->badgeTable = new CustomBadgeTable();
	}

	public function getAction(string $code): ?array
	{
		$badge = $this->getBadge($code);

		if (!$badge)
		{
			$this->addError(new Error("Badge not found for code `$code`", ErrorCode::NOT_FOUND));
			return null;
		}

		return [
			'badge' => $badge,
		];
	}

	public function listAction(): Page
	{
		$badges = $this->badgeTable::getList([
			'select' => [
				'CODE',
				'TITLE',
				'VALUE',
				'TYPE',
			],
		])
			->fetchCollection()
			->getAll()
		;

		return new Page(self::PAGE_ID, $badges, count($badges));
	}

	/**
	 * @param string $code
	 * @param string|array $title
	 * @param string|array $value
	 * @param string $type
	 * @return array|null
	 */
	public function addAction(string $code, $title, $value, string $type): ?array
	{
		if (!$this->isAdmin())
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		$this->validateTranslatedText($title, 'title');
		$this->validateTranslatedText($value, 'value');
		$this->validateType($type);
		if (!empty($this->getErrors()))
		{
			return null;
		}

		$result = $this->badgeTable::add([
			'CODE' => $code,
			'TITLE' => $title,
			'VALUE' => $value,
			'TYPE' => $type,
		]);

		if ($result->isSuccess())
		{
			return [
				'badge' => $this->getBadge($code),
			];
		}

		foreach ($result->getErrors() as $error)
		{
			$this->addError($error);
		}

		return null;
	}

	public function deleteAction(string $code): ?bool
	{
		if (!$this->isAdmin())
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		$badge = $this->getBadge($code);

		if (!$badge)
		{
			$this->addError(new Error("Badge not found for code `$code`", ErrorCode::NOT_FOUND));
			return null;
		}

		$result = $this->delete($badge);
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

	protected function getBadge(string $code): ?CustomBadge
	{
		return $this->badgeTable::query()
			->addSelect('*')
			->where('CODE', $code)
			->fetchObject()
		;
	}

	protected function delete(CustomBadge $badge): Result
	{
		return $badge->delete();
	}

	private function validateTranslatedText($value, string $fieldName): void
	{
		if (is_string($value))
		{
			if ($value === '')
			{
				$this->addError(\Bitrix\Crm\Controller\ErrorCode::getRequiredArgumentMissingError($fieldName));
			}
		}
		elseif (is_array($value))
		{
			$langIds = CultureTable::getList(['cache' => ['ttl' => 3600]])->fetchCollection()->getCodeList();
			foreach ($value as $langId => $langValue)
			{
				if (!in_array($langId, $langIds, true))
				{
					$this->addError(new Error('Language `' . $langId . '` was not found'));
				}
				if (!is_string($langValue) || $langValue == '')
				{
					$this->addError(new Error('Wrong value of field `' . $fieldName . '` for language ' . $langId));
				}
			}
		}
		else
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getRequiredArgumentMissingError($fieldName));
		}
	}

	private function validateType(string $type): void
	{
		if (!in_array($type, [
			CustomBadgeTable::TYPE_SUCCESS,
			CustomBadgeTable::TYPE_FAILURE,
			CustomBadgeTable::TYPE_WARNING,
			CustomBadgeTable::TYPE_PRIMARY,
			CustomBadgeTable::TYPE_SECONDARY,
		], true))
		{
			$this->addError(new Error('Wrong value of field `type`', 'WRONG_TYPE_VALUE'));
		}
	}
}
