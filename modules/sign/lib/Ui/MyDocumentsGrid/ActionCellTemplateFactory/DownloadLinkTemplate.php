<?php

namespace Bitrix\Sign\Ui\MyDocumentsGrid\ActionCellTemplateFactory;

class DownloadLinkTemplate implements ActionCellTemplate
{
	private ?string $downloadFileLink;
	private ?string $downloadText;

	public function __construct(
		?string $downloadFileLink,
		?string $downloadText
	)
	{
		$this->downloadFileLink = $downloadFileLink;
		$this->downloadText = $downloadText;
	}

	public function get(): ?string
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
