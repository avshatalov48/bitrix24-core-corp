<?php

namespace Bitrix\BizprocMobile\UI;

use Bitrix\Bizproc\Workflow\Entity\WorkflowUserTable;
use Bitrix\Main\Type\DateTime;

class WorkflowUserDetailView extends \Bitrix\Bizproc\UI\WorkflowUserDetailView
{
	protected ?DateTime $modified = null;

	public function isLifeFeedProcess(): bool
	{
		return $this->workflow->getModuleId() === 'lists' && $this->workflow->getEntity() === 'BizprocDocument';
	}

	public function getModifiedTimestamp(): ?int
	{
		$this->loadModified();

		return $this->modified?->getTimestamp();
	}

	public function getTime(): string
	{
		$this->loadModified();

		return \CBPViewHelper::formatDateTime($this->modified);
	}

	private function loadModified(): void
	{
		if (is_null($this->modified))
		{
			$row = WorkflowUserTable::getByPrimary(
				['USER_ID' => $this->userId, 'WORKFLOW_ID' => $this->workflow->getId()],
				['select' => ['MODIFIED']]
			)->fetch();
			if ($row)
			{
				$this->modified = $row['MODIFIED'];
			}
		}
	}

	public function getWorkflowResult(): ?array
	{
		return \CBPViewHelper::getWorkflowResult($this->getId(), $this->userId, \CBPViewHelper::MOBILE_CONTEXT);
	}

	public function getDescription(): ?string
	{
		$description = $this->getClearDescription();
		if ($description)
		{
			$parser = new \CTextParser();
			$bbDescription = $parser->convertHTMLToBB(
				$description,
				[
					'ANCHOR' => 'Y',
					'BIU' => 'Y',
					'FONT' => 'Y',
					'LIST' => 'Y',
					'NL2BR' => 'Y',
					'IMG' => 'Y',

					'HTML' => 'N',
					'QUOTE' => 'N',
					'CODE' => 'N',
					'SMILES' => 'N',
					'VIDEO' => 'N',
					'TABLE' => 'N',
					'ALIGN' => 'N',
					'P' => 'N',
				]
			);

			$codeView = new BbCodeView($bbDescription);

			return $codeView->getText();
		}

		return null;
	}
}
