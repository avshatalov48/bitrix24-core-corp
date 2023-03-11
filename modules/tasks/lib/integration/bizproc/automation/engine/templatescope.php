<?php

namespace Bitrix\Tasks\Integration\Bizproc\Automation\Engine;

class TemplateScope extends \Bitrix\Bizproc\Automation\Engine\TemplateScope
{
	private string $projectName = '';

	public function setProjectName(string $name): self
	{
		$this->projectName = $name;

		return $this;
	}

	public function toArray(): array
	{
		$result = parent::toArray();
		if ($this->projectName)
		{
			$result['DocumentType']['Name'] = $this->projectName;
		}

		return $result;
	}
}
