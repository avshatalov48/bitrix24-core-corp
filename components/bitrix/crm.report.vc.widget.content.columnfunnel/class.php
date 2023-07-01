<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Report\VisualConstructor\Views\Component\BaseViewComponent;

class CrmReportVcWidgetContentColumnFunnel extends BaseViewComponent
{
	public function executeComponent()
	{
		$this->arResult['WIDGET_DATA'] = $this->buildEntities();
		/** @var \Bitrix\Report\VisualConstructor\Entity\Widget $widget */ //$widget = $this->arParams['WIDGET'];
		//$this->arResult['SHORT_MODE'] = $widget->getWidgetHandler()->getFormElement('shortMode')->getValue();
		parent::executeComponent();
	}

	private function buildEntities()
	{
		$entities = [];
		if (!empty($this->arParams['RESULT']['data']))
		{
			foreach ($this->arParams['RESULT']['data'] as $reportHandlerId => $reportResult)
			{
				$entity = [];
				$entity['title'] = $reportResult['title'];
				if (isset($reportResult['config']['mode']) && $reportResult['config']['mode'] === 'singleData')
				{
					$entity['topAdditionalTitle'] = $reportResult['config']['topAdditionalTitle'] ?? '';
					$entity['topAdditionalValue'] = $reportResult['config']['topAdditionalValue'] ?? null;
					$entity['singleData'] = true;
					$entity['topAdditionalValueUnit'] = $reportResult['config']['topAdditionalValueUnit'] ?? null;
				}

				if (!empty($reportResult['items']))
				{
					foreach ($reportResult['items'] as $id => $item)
					{
						$column = [
							'title' => $item['label'],
							'value' => $item['value'],
							'color' => $item['color'],
							'link' => !empty($item['link']) ? $item['link'] : ''
						];

						if (isset($item['additionalValues']['firstAdditionalValue']))
						{
							$column['firstAdditionalTitle'] = $reportResult['config']['additionalValues']['firstAdditionalValue']['titleShort'];
							$column['firstAdditionalValue'] = $item['additionalValues']['firstAdditionalValue']['value'];
							$column['firstAdditionalUnit'] = !empty($item['additionalValues']['firstAdditionalValue']['unitOfMeasurement'])
								? $item['additionalValues']['firstAdditionalValue']['unitOfMeasurement'] : '';
						}

						if (isset($item['additionalValues']['secondAdditionalValue']))
						{
							$column['secondAdditionalTitle'] = $reportResult['config']['additionalValues']['secondAdditionalValue']['titleShort'];
							$column['secondAdditionalValue'] = $item['additionalValues']['secondAdditionalValue']['value'];
							$column['secondAdditionalUnit'] = !empty($item['additionalValues']['secondAdditionalValue']['unitOfMeasurement'])
								? $item['additionalValues']['secondAdditionalValue']['unitOfMeasurement'] : '';
						}

						if (isset($item['additionalValues']['thirdAdditionalValue']))
						{
							$column['thirdAdditionalTitle'] = $reportResult['config']['additionalValues']['thirdAdditionalValue']['titleShort'];
							$column['thirdAdditionalValue'] = $item['additionalValues']['thirdAdditionalValue']['value'];
							$column['thirdAdditionalUnit'] = !empty($item['additionalValues']['thirdAdditionalValue']['unitOfMeasurement'])
								? $item['additionalValues']['thirdAdditionalValue']['unitOfMeasurement'] : '';

						}

						if (isset($item['additionalValues']['forthAdditionalValue']))
						{
							$column['forthAdditionalTitle'] = $item['additionalValues']['forthAdditionalValue']['title'] ?? $reportResult['config']['additionalValues']['forthAdditionalValue']['titleShort'];
							$column['forthAdditionalValue'] = $item['additionalValues']['forthAdditionalValue']['value'];
							$column['forthAdditionalUnit'] = !empty($item['additionalValues']['forthAdditionalValue']['unitOfMeasurement'])
								? $item['additionalValues']['forthAdditionalValue']['unitOfMeasurement'] : '';
						}
						$entity['columns'][] = $column;

					}
				}

				if (!empty($reportResult['config']['valuesAmount']['firstAdditionalAmount']))
				{
					$entity['firstAdditionalTitleAmount'] = $reportResult['config']['valuesAmount']['firstAdditionalAmount']['title'];
					$entity['firstAdditionalValueAmount'] = $reportResult['config']['valuesAmount']['firstAdditionalAmount']['value'];
					$entity['firstAdditionalAmountTargetUrl'] = $reportResult['config']['valuesAmount']['firstAdditionalAmount']['targetUrl'];
				}

				if (!empty($reportResult['config']['valuesAmount']['secondAdditionalAmount']))
				{
					$entity['secondAdditionalTitleAmount'] = $reportResult['config']['valuesAmount']['secondAdditionalAmount']['title'];
					$entity['secondAdditionalValueAmount'] = $reportResult['config']['valuesAmount']['secondAdditionalAmount']['value'];
				}

				if (!empty($reportResult['config']['valuesAmount']['thirdAdditionalAmount']))
				{
					$entity['thirdAdditionalTitleAmount'] = $reportResult['config']['valuesAmount']['thirdAdditionalAmount']['title'];
					$entity['thirdAdditionalValueAmount'] = $reportResult['config']['valuesAmount']['thirdAdditionalAmount']['value'];
				}

				if (!empty($reportResult['config']['valuesAmount']['forthAdditionalAmount']))
				{
					$entity['forthAdditionalTitleAmount'] = $reportResult['config']['valuesAmount']['forthAdditionalAmount']['title'];
					$entity['forthAdditionalValueAmount'] = $reportResult['config']['valuesAmount']['forthAdditionalAmount']['value'];
				}

				if (!empty($entity))
				{
					$entities[] = $entity;
				}

			}
		}

		return $entities;
	}
}