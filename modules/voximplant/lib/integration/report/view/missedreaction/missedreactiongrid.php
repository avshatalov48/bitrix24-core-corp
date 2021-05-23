<?php

namespace Bitrix\Voximplant\Integration\Report\View\MissedReaction;

use Bitrix\Report\VisualConstructor\Config\Common;
use Bitrix\Report\VisualConstructor\Views\Component\Base;

/**
 * Class MissedReaction
 * @package Bitrix\Voximplant\Integration\Report\View\MissedReaction
 */
class MissedReactionGrid extends Base
{
	public const VIEW_KEY = 'missed_reaction_grid';
	public const MAX_RENDER_REPORT_COUNT = 1;
	public const USE_IN_VISUAL_CONSTRUCTOR = false;

	/**
	 * LostCallsGrid constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->setDraggable(false);
		$this->setComponentName('bitrix:voximplant.report.missedreaction.grid');
		$this->setPreviewImageUri('/bitrix/images/report/visualconstructor/preview/grid.svg');
		$this->setCompatibleDataType(Common::MULTIPLE_REPORT_TYPE);
	}
}