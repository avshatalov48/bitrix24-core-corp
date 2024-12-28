<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid\ActionCellTemplateFactory;

class CompositeTemplate implements ActionCellTemplate
{
	private array $templates;

	public function __construct(array $templates)
	{
		$this->templates = $templates;
	}

	public function get(): string
	{
		$output = '';
		foreach ($this->templates as $template)
		{
			$output .= $template->get();
		}

		return $output;
	}
}