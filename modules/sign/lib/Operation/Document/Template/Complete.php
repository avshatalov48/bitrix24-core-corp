<?php

namespace Bitrix\Sign\Operation\Document\Template;

use Bitrix\Main;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Repository\Document\TemplateFolderRelationRepository;
use Bitrix\Sign\Repository\Document\TemplateRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\DateTime;
use Bitrix\Sign\Type\Template\EntityType;
use Bitrix\Sign\Type\Template\Status;
use Bitrix\Sign\Type\Template\Visibility;

final class Complete implements Contract\Operation
{
	private readonly TemplateRepository $templateRepository;
	private readonly TemplateFolderRelationRepository $templateFolderRelationRepository;

	public function __construct(
		private readonly Item\Document\Template $template,
		?TemplateRepository $templateRepository = null,
		?TemplateFolderRelationRepository $templateFolderRelationRepository = null
	)
	{
		$this->templateRepository = $templateRepository ?? Container::instance()->getDocumentTemplateRepository();
		$this->templateFolderRelationRepository = $templateFolderRelationRepository ?? Container::instance()->getTemplateFolderRelationRepository();
	}

	public function launch(): Main\Result
	{
		if ($this->template->id === null)
		{
			return (new Main\Result())->addError(new Main\Error('Template is not saved'));
		}

		$this->template->dateModify = new DateTime();
		$this->template->modifiedById = Main\Engine\CurrentUser::get()->getId();

		$updateParentResult = $this->templateFolderRelationRepository->updateParent(
			$this->template->folderId,
			[$this->template->id],
			EntityType::TEMPLATE
		);
		if (!$updateParentResult->isSuccess())
		{
			return (new Main\Result())->addError(new Main\Error('Update parent id error'));
		}

		if ($this->template->status === Status::COMPLETED)
		{
			return $this->templateRepository->update($this->template);
		}

		$this->template->status = Status::COMPLETED;
		$this->template->visibility = Visibility::VISIBLE;

		return $this->templateRepository->update($this->template);
	}
}