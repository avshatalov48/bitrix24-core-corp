<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Control\Exception\TemplateNotFoundException;
use Bitrix\Tasks\Internals\Task\Template\TemplateDependenceTable;
use Bitrix\Tasks\Internals\Task\Template\TemplateObject;

class TemplateDependence
{
	use BaseTemplateControlTrait;

	private const FIELD_DEPEND = 'DEPENDS_ON';

	private $userId;
	private $templateId;

	/* @var TemplateObject $template */
	private $template;

	public function __construct(int $userId, int $templateId)
	{
		$this->userId = $userId;
		$this->templateId = $templateId;
	}

	/**
	 * @throws TemplateNotFoundException
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function set(array $data): void
	{
		if (
			!array_key_exists(self::FIELD_DEPEND, $data)
			|| !is_array($data[self::FIELD_DEPEND])
		)
		{
			return;
		}

		$this->loadByTemplate();
		$this->deleteByTemplate();

		if (empty($data[self::FIELD_DEPEND]))
		{
			return;
		}

		$depends = array_values($data[self::FIELD_DEPEND]);

		if (empty($depends))
		{
			return;
		}

		$insertRows = [];
		foreach ($depends as $depend)
		{
			$depend = (int) $depend;

			if ($depend < 1)
			{
				continue;
			}
			$insertRows[] = '('.$this->templateId.', '. $depend .')';
		}

		if (empty($insertRows))
		{
			return;
		}

		$sql = "
			INSERT IGNORE INTO ". TemplateDependenceTable::getTableName() ."
			(`TEMPLATE_ID`, `DEPENDS_ON_ID`)
			VALUES
			". implode(", ", $insertRows) ."
		";

		Application::getConnection()->query($sql);
	}

	/**
	 * @throws TemplateNotFoundException
	 * @throws SystemException
	 */
	private function loadByTemplate(): void
	{
		$this->loadTemplate();
		$this->template->fillDependencies();
	}

	public function getTableClass(): string
	{
		return TemplateDependenceTable::class;
	}
}