<?php
namespace Bitrix\Tasks\Copy;

use Bitrix\Main\Copy\Container;
use Bitrix\Main\Copy\ContainerCollection;
use Bitrix\Tasks\Copy\Implement\Template as TemplateImplementer;
use Bitrix\Tasks\Copy\Implement\TemplateCheckList as CheckListImplementer;
use Bitrix\Tasks\Copy\Template as TemplateCopier;
use Bitrix\Tasks\Copy\TemplateChecklist as TemplateChecklistCopier;

class TemplateManager
{
	private $executiveUserId;
	private $templateIdsToCopy = [];

	private $markerChecklist = true;
	private $markerFiles = true;

	public function __construct($executiveUserId, array $templateIdsToCopy)
	{
		$this->executiveUserId = $executiveUserId;
		$this->templateIdsToCopy = $templateIdsToCopy;
	}

	public function markChecklist($marker)
	{
		$this->markerChecklist = (bool) $marker;
	}

	public function startCopy()
	{
		$containerCollection = $this->getContainerCollection();

		$templateImplementer = $this->getTemplateImplementer();
		$templateCopier = $this->getTemplateCopier($templateImplementer);

		if ($this->markerChecklist)
		{
			$checklistImplementer = $this->getChecklistImplementer();
			$templateCopier->addEntityToCopy($this->getChecklistCopier($checklistImplementer));
		}

		return $templateCopier->copy($containerCollection);
	}

	private function getContainerCollection()
	{
		$containerCollection = new ContainerCollection();

		foreach ($this->templateIdsToCopy as $taskId)
		{
			$containerCollection[] = new Container($taskId);
		}

		return $containerCollection;
	}

	private function getTemplateImplementer()
	{
		global $USER_FIELD_MANAGER;

		$templateImplementer = new TemplateImplementer();
		$templateImplementer->setUserFieldManager($USER_FIELD_MANAGER);
		$templateImplementer->setExecutiveUserId($this->executiveUserId);

		return $templateImplementer;
	}

	private function getTemplateCopier(TemplateImplementer $templateImplementer)
	{
		return new TemplateCopier($templateImplementer);
	}

	private function getChecklistImplementer()
	{
		return new CheckListImplementer();
	}

	private function getChecklistCopier($checklistImplementer)
	{
		return new TemplateChecklistCopier($checklistImplementer, $this->executiveUserId);
	}
}