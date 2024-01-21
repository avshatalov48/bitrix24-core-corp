<?php

namespace Bitrix\Tasks\Member\Repository;

use Bitrix\Tasks\Internals\Task\Template\EO_TemplateMember_Collection;
use Bitrix\Tasks\Internals\Task\Template\TemplateObject;
use Bitrix\Tasks\Member\RepositoryInterface;
use Bitrix\Tasks\Provider\TemplateProvider;
use Exception;

class TemplateRepository implements RepositoryInterface
{
	private ?TemplateObject $template = null;

	public function __construct(private int $templateId)
	{
	}

	public function getEntity(): TemplateObject|null
	{
		if (!is_null($this->template))
		{
			return $this->template;
		}

		global $DB, $USER_FIELD_MANAGER;
		try
		{
			$provider = new TemplateProvider($DB, $USER_FIELD_MANAGER);
			$rows = $provider->getList(arFilter: ['=ID' => $this->templateId], arSelect: ['ID']);
			$templates = [];
			while ($template = $rows->Fetch())
			{
				$templates[]['ID'] = $template['ID'];
			}

			if (empty($templates))
			{
				return null;
			}

			$this->template = new TemplateObject($templates[0]);
		}
		catch (Exception)
		{
			return null;
		}

		$this->template->fillMembers();

		return $this->template;
	}

	public function getMembers(): EO_TemplateMember_Collection
	{
		return $this->getEntity()->getMembers();
	}

	public function getType(): string
	{
		return 'Template';
	}
}