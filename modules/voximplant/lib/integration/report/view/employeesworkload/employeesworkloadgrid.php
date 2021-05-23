<?php

namespace Bitrix\Voximplant\Integration\Report\View\EmployeesWorkload;

use Bitrix\Report\VisualConstructor\Config\Common;
use Bitrix\Report\VisualConstructor\Views\Component\Base;

/**
 * Class EmployeesWorkloadGrid
 * @package Bitrix\Voximplant\Integration\Report\View\EmployeesWorkload
 */
class EmployeesWorkloadGrid extends Base
{
	public const VIEW_KEY = 'employees_workload_grid';
	public const MAX_RENDER_REPORT_COUNT = 1;
	public const USE_IN_VISUAL_CONSTRUCTOR = false;

	/**
	 * EmployeesWorkloadGrid constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->setDraggable(false);
		$this->setComponentName('bitrix:voximplant.report.employeesworkload.grid');
		$this->setPreviewImageUri('/bitrix/images/report/visualconstructor/preview/grid.svg');
		$this->setCompatibleDataType(Common::MULTIPLE_REPORT_TYPE);
	}
}