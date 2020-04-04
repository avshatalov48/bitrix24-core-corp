<?php


use Bitrix\Report\VisualConstructor\Views\Component\BaseViewComponent;

class CrmReportVcWidgetContentColumnFunnel extends BaseViewComponent
{
	public function executeComponent()
	{
		$this->arResult['WIDGET_DATA'] = $this->buildEntities();
		/** @var \Bitrix\Report\VisualConstructor\Entity\Widget $widget */
		//$widget = $this->arParams['WIDGET'];
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
					$entity['value'] = $reportResult['items'][0]['value'];
					$entity['color'] = $reportResult['items'][0]['color'];
					$entity['singleData'] = true;
					$entity['unitOfMeasurement'] = $reportResult['config']['unitOfMeasurement'];
				}
				else
				{
					if (isset($reportResult['config']['additionalValues']['sum']))
					{
						$entity['topAdditionalTitleAmount'] = $reportResult['config']['additionalValues']['sum']['titleShort'];
						$entity['topAdditionalTitleAmount'] = 0;
					}



					if (!$reportResult['items'])
					{
						continue;
					}

					foreach ($reportResult['items'] as $id => $item)
					{
						$column = [
							'title' => $item['label'],
							'value' => $item['value'],
							'color' => $item['color'],
							'firstAdditionalTitle' => $reportResult['config']['titleShort'],
							'firstAdditionalValue' => $item['value'],
							'secondAdditionalTitle' => $reportResult['config']['titleShort'],
							'secondAdditionalValue' => $item['value'],
						];
						if (isset($reportResult['config']['additionalValues']['sum']))
						{
							$column['topAdditionalTitle'] = $reportResult['config']['additionalValues']['sum']['titleShort'];
							$column['topAdditionalValue'] = $item['additionalValues']['sum']['value'];
						}

						$entity['columns'][] = $column;



					}
				}

				if (!empty($reportResult['config']['valuesAmount']['firstAdditionalAmount']))
				{
					$entity['firstAdditionalTitleAmount'] = $reportResult['config']['valuesAmount']['firstAdditionalAmount']['title'];
					$entity['firstAdditionalValueAmount'] = $reportResult['config']['valuesAmount']['firstAdditionalAmount']['value'];
				}

				if (!empty($reportResult['config']['valuesAmount']['secondAdditionalAmount']))
				{
					$entity['secondAdditionalTitleAmount'] = $reportResult['config']['valuesAmount']['secondAdditionalAmount']['title'];
					$entity['secondAdditionalValueAmount'] = $reportResult['config']['valuesAmount']['secondAdditionalAmount']['value'];
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