<?php

namespace Bitrix\Crm\Integration\Report\View;

use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Report\VisualConstructor\Entity\Widget;
use Bitrix\Report\VisualConstructor\Views\Component\Base;

abstract class WidgetPanel extends Base
{
	const VIEW_KEY = 'widget_panel';
	const MAX_RENDER_REPORT_COUNT = 15;
	const USE_IN_VISUAL_CONSTRUCTOR = false;

	public function __construct()
	{
		parent::__construct();

		$this->setDraggable(false);
		$this->setComponentName('bitrix:crm.report.vc.content.widgetpanel');
		$this->setComponentTemplateName($this->getDisplayComponentTemplate());
		$this->addComponentParameters('WIDGET_PANEL_PARAMS', $this->getDisplayComponentParams());
		$this->setPreviewImageUri('/bitrix/images/report/visualconstructor/preview/grid.svg');
	}

	public function prepareWidgetContent(Widget $widget, $withCalculatedData = false)
	{
		$result = parent::prepareWidgetContent($widget, $withCalculatedData);
		$result['isHeadEnabled'] = false;

		return $result;
	}

	/**
	 * Handle all data prepared for this view.
	 *
	 * @param array $dataFromReport Calculated data from report handler.
	 * @return array
	 */
	public function handlerFinallyBeforePassToView($dataFromReport)
	{
		return $dataFromReport;
	}

	public function getDisplayComponentTemplate()
	{
		return '';
	}

	public function getDisplayComponentParams()
	{
		return [
			'GUID' => $this->getPanelGuid(),
			'ENTITY_TYPES' => $this->getEntityTypes(),
			'LAYOUT' => 'L50R50',
			'ENABLE_NAVIGATION' => false,
			'PATH_TO_DEMO_DATA' => $this->getPathToDemoData(),
			'IS_SUPERVISOR' => $this->isSuperVisor(),
			'ROWS' => $this->getDefaultRows(),
			'DEMO_TITLE' => $this->getDemoTitle(),
			'DEMO_CONTENT' => $this->getDemoContent(),
			'HIDE_FILTER' => true,
		];
	}

	/**
	 * @return string
	 */
	abstract public function getPanelGuid();

	/**
	 * @return array
	 */
	abstract public function getEntityTypes();

	/**
	 * @return array
	 */
	abstract public function getDefaultRows();

	/**
	 * @return bool
	 */
	public function isSuperVisor()
	{
		$currentUserId = \CCrmSecurityHelper::GetCurrentUserID();
		return \CCrmPerms::IsAdmin($currentUserId) || IntranetManager::isSupervisor($currentUserId);
	}

	/**
	 * @return string
	 */
	abstract public function getPathToDemoData();

	public function getDemoTitle()
	{
		return "";
	}

	public function getDemoContent()
	{
		return "";
	}
}