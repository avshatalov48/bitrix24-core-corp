<?php

namespace Bitrix\Crm\Integration\Report\View\Customers;

use Bitrix\Crm\Integration\Report\Handler\SalesDynamics;
use Bitrix\Crm\Integration\Report\Handler\SalesDynamics\BaseGraph;
use Bitrix\Main\Context;
use Bitrix\Main\Type\Date;
use Bitrix\Report\VisualConstructor\Config\Common;
use Bitrix\Report\VisualConstructor\Views\Component\Base;

class FinancialRatingGrid extends Base
{
	const VIEW_KEY = 'crm_financial_rating_grid';
	const MAX_RENDER_REPORT_COUNT = 15;
	const USE_IN_VISUAL_CONSTRUCTOR = false;

	public function __construct()
	{
		parent::__construct();

		$this->setDraggable(false);
		$this->setComponentName('bitrix:crm.report.financialrating.grid');
		$this->setPreviewImageUri('/bitrix/images/report/visualconstructor/preview/grid.svg');
		$this->setCompatibleDataType(Common::MULTIPLE_REPORT_TYPE);
	}

	/**
	 * Handle all data prepared for this view.
	 *
	 * @param array $dataFromReport Calculated data from report handler.
	 * @return array
	 */
	public function handlerFinallyBeforePassToView($dataFromReport)
	{
		$result = $dataFromReport[0];

		$contacts = [];
		$companies = [];
		foreach ($result as $resultItem)
		{
			$value = $resultItem['value'];
			if($value['ownerType'] == \CCrmOwnerType::ContactName)
			{
				$contacts[$value['ownerId']] = [
					'TITLE' => \CCrmViewHelper::GetHiddenEntityCaption(\CCrmOwnerType::Contact)
				];
			}
			else
			{
				$companies[$value['ownerId']] = [
					'TITLE' => \CCrmViewHelper::GetHiddenEntityCaption(\CCrmOwnerType::Company)
				];
			}
		}
		\CCrmOwnerType::PrepareEntityInfoBatch(\CCrmOwnerType::Contact, $contacts, true);
		\CCrmOwnerType::PrepareEntityInfoBatch(\CCrmOwnerType::Company, $companies, true);

		foreach ($contacts as $contactId => $contactFields)
		{
			$key = \CCrmOwnerType::ContactName . "_" . $contactId;
			$result[$key]['clientFields'] = $contactFields;
		}
		foreach ($companies as $companyId => $companyFields)
		{
			$key = \CCrmOwnerType::CompanyName . "_" . $companyId;
			$result[$key]['clientFields'] = $companyFields;
		}
		return $result;
	}
}