<?php

namespace Bitrix\Tasks\Replicator\Template\Repository;

use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\Task\Template\TemplateObject;
use Bitrix\Tasks\Replicator\Template\Repository;
use Bitrix\Tasks\TemplateTable;

class TemplateRepository implements Repository
{
	private ?TemplateObject $template = null;

	public function __construct(private int $templateId)
	{
	}

	public function getTemplate(): ?TemplateObject
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
		catch (SystemException)
		{
			//todo: log there
			return null;
		}

		return $this->template;
	}
}