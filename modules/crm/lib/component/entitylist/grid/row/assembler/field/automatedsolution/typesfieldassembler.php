<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Row\Assembler\Field\AutomatedSolution;

use Bitrix\Crm\Model\Dynamic\Type;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Router;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Grid\Settings;
use Bitrix\Main\Localization\Loc;

final class TypesFieldAssembler extends FieldAssembler
{
	private const MAX_TYPES_IN_ROW = 10;

	private Router $router;

	public function __construct(array $columnIds, ?Settings $settings = null)
	{
		parent::__construct($columnIds, $settings);

		$this->router = Container::getInstance()->getRouter();
	}

	protected function prepareColumn($value)
	{
		if (!is_array($value))
		{
			return '';
		}

		return $this->prepareContent($value);
	}

	private function prepareContent(array $typeIds): string
	{
		sort($typeIds);

		$typesToRenderAsLinks = $typeIds;
		$remainingTypesCount = 0;
		if (count($typesToRenderAsLinks) > self::MAX_TYPES_IN_ROW)
		{
			$typesToRenderAsLinks = array_slice($typesToRenderAsLinks, 0, self::MAX_TYPES_IN_ROW);
			$remainingTypesCount = count($typeIds) - count($typesToRenderAsLinks);
		}

		$links = [];
		foreach ($typesToRenderAsLinks as $typeId)
		{
			$type = $this->getType($typeId);
			if (!$type)
			{
				continue;
			}

			$url = $this->router->getItemListUrlInCurrentView($type->getEntityTypeId());

			$links[] = '<a href="'.htmlspecialcharsbx($url).'">'.htmlspecialcharsbx($type->getTitle()).'</a>';
		}

		$content = implode(', ', $links);
		if ($remainingTypesCount > 0)
		{
			$content .= Loc::getMessage(
				'CRM_GRID_ROW_ASSEMBLER_AUTOMATED_SOLUTION_TYPES_MORE',
				['#MORE_COUNT#' => $remainingTypesCount],
			);
		}

		return $content;
	}

	private function getType(int $id): ?Type
	{
		return Container::getInstance()->getType($id);
	}
}
