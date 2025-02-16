<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid\ActionCellTemplateFactory;

use Bitrix\Sign\Contract\Grid\MyDocuments\ActionCellTemplate;

class DownloadLinkTemplate implements ActionCellTemplate
{
	public function __construct(
		private readonly ?string $downloadFileLink,
		private readonly ?string $downloadText,
	)
	{}

	public function render(): ?string
	{
		$downloadFileLink = htmlspecialcharsbx($this->downloadFileLink);
		if ($this->downloadFileLink !== null)
		{
			return <<<HTML
				<a class="sign-grid-download-link" href="$downloadFileLink">
					<span class="sign-grid-download-link-text">
						$this->downloadText
					</span>
				</a>
			HTML;
		}

		return null;
	}
}
