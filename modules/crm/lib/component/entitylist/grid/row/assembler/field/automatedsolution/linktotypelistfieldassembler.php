<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Row\Assembler\Field\AutomatedSolution;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Router;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Grid\Settings;

final class LinkToTypeListFieldAssembler extends FieldAssembler
{
	private readonly Router $router;

	public function __construct(array $columnIds, ?Settings $settings = null)
	{
		parent::__construct($columnIds, $settings);

		$this->router = Container::getInstance()->getRouter();
	}

	protected function prepareRow(array $row): array
	{
		if (empty($this->getColumnIds()))
		{
			return $row;
		}

		$row['columns'] ??= [];

		$automatedSolutionId = (int)($row['data']['ID'] ?? null);

		foreach ($this->getColumnIds() as $columnId)
		{
			$row['columns'][$columnId] = $this->prepareContent($automatedSolutionId, (string)($row['data'][$columnId] ?? null));
		}

		return $row;
	}

	private function prepareContent(int $automatedSolutionId, string $automatedSolutionTitle): string
	{
		$safeTitle = htmlspecialcharsbx($automatedSolutionTitle);
		$externalTypeListUri = $this->router->getExternalTypeListUrl();

		$externalTypeListUri->addParams([
			'apply_filter' => 'Y',
			'AUTOMATED_SOLUTION' => $automatedSolutionId,
		]);

		$safeExternalTypeListUri = htmlspecialcharsbx((string)\CUtil::JSEscape($externalTypeListUri));

		return "<a href=\"{$safeExternalTypeListUri}\">{$safeTitle}</a>";
	}
}
