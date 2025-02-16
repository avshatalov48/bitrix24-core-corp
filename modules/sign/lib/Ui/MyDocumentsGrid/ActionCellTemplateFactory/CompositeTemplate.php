<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid\ActionCellTemplateFactory;

use Bitrix\Sign\Contract\Grid\MyDocuments\ActionCellTemplate;

class CompositeTemplate implements ActionCellTemplate
{
	/**
	 * @param ActionCellTemplate[] $templates
	 */
	public function __construct(
		private readonly array $templates,
	)
	{
	}

	public function render(): string
	{
		$output = '';
		foreach ($this->templates as $template)
		{
			$output .= $template->render();
		}

		return $output;
	}
}