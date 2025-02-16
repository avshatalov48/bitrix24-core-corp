<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid\ActionCellTemplateFactory;

use Bitrix\Sign\Item\MyDocumentsGrid\Row;
use Bitrix\Sign\Type\MyDocumentsGrid\Action;
use Bitrix\Sign\Contract\Grid\MyDocuments\ActionCellTemplate;

class Factory
{
	public function create(
		Row $row,
		?string $textForActionColumn
	): ActionCellTemplate
	{
		$action = $row->action;
		$document = $row->document;
		$myMember = $row->myMemberInProcess;
		$downloadFileLink = $row->file->url ?? null;
		$isActionDownloadOrView = in_array($action, [Action::DOWNLOAD, Action::VIEW]);
		$initiatedByEmployee = $document->isInitiatedByEmployee();
		$initiatedByCompany = $document->isInitiatedByCompany();
		$isDocumentStatusStoppedForEmployeeScenario = $initiatedByEmployee && $document->isDocumentStopped();
		$actionDate = $row->document->editDate
			?? $row->document->approvedDate
			?? $row->document->cancelledDate
			?? $row->document->signDate
			?? null
		;

		if (($initiatedByEmployee && $isActionDownloadOrView) || ($initiatedByCompany && $myMember->isSigner()))
		{
			if ($action === Action::DOWNLOAD && $myMember->isSigner())
			{
				if ($isDocumentStatusStoppedForEmployeeScenario && $downloadFileLink !== null)
				{
					return new CompositeTemplate([
						new RefusedDocumentTemplate($actionDate),
						new DownloadLinkTemplate(
							$downloadFileLink,
							$textForActionColumn,
						)
					]);
				}

				if ($myMember->isDone())
				{
					return new CompositeTemplate([
						new SignedDocumentTemplate($document->signDate),
						new DownloadLinkTemplate(
							$downloadFileLink,
							$textForActionColumn,
						)
					]);
				}
			}

			if ($downloadFileLink !== null && $action === Action::VIEW)
			{
				return new ViewCellTemplate(
					$document->sendDate,
					$downloadFileLink,
					$textForActionColumn,
				);
			}

			return new CompletedActionTextTemplate(
				$row,
				$actionDate,
			);
		}

		return new DefaultCompletedActionTextTemplate(
			$textForActionColumn,
			$actionDate,
		);
	}
}