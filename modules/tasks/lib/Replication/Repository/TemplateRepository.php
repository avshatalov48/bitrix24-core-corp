<?php

namespace Bitrix\Tasks\Replication\Repository;

use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Bitrix\Tasks\Internals\Task\Template\TemplateObject;
use Bitrix\Tasks\Internals\Task\TemplateTable;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Replication\RepositoryInterface;

class TemplateRepository implements RepositoryInterface
{
	private static array $cache = [];

	private ?TemplateObject $template = null;

	private int $templateId;

	public static function getInstance(int $templateId): static
	{
		if (!isset(static::$cache[$templateId]))
		{
			static::$cache[$templateId] = new static($templateId);
		}

		return static::$cache[$templateId];
	}

	public function __construct(int $templateId)
	{
		$this->templateId = $templateId;
	}

	public function getEntity(): ?TemplateObject
	{
		if (!is_null($this->template))
		{
			return $this->template;
		}

		try
		{
			$query = TemplateTable::query();
			$query
				->setSelect(['*', 'UF_*', 'MEMBERS', 'TAG_LIST', 'DEPENDENCIES', 'SCENARIO', 'CHECKLIST_DATA'])
				->where('ID', $this->templateId);

			$this->template = $query->exec()->fetchObject();
		}
		catch (SystemException $exception)
		{
			LogFacade::logThrowable($exception);
			return null;
		}

		return $this->template;
	}

	public function drop(): void
	{
		$this->template = null;
	}

	public function inject(TaskObject|TemplateObject $object): static
	{
		if ($object instanceof TemplateObject)
		{
			$this->template = $object;
		}

		return $this;
	}
}