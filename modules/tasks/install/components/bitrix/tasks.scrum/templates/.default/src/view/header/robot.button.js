import {Event, Loc, Tag} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {SidePanel} from '../../service/side.panel';

import '../../css/robot.button.css';

type Params = {
	sidePanel: SidePanel,
	groupId: number,
	isTaskLimitsExceeded: boolean,
	canUseAutomation: boolean
}

export class RobotButton extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.sidePanel = params.sidePanel;
		this.isTaskLimitsExceeded = params.isTaskLimitsExceeded;
		this.canUseAutomation = params.canUseAutomation;
		this.groupId = params.groupId;

		this.setEventNamespace('BX.Tasks.Scrum.RobotButton');
	}

	render(): HTMLElement
	{
		let className = 'tasks-scrum-robot-btn ui-btn ui-btn-light-border ui-btn-no-caps ui-btn-themes ui-btn-round';
		if (this.isShowLimitSidePanel())
		{
			className += ' ui-btn-icon-lock';
		}

		const node = Tag.render`
			<div class="tasks-scrum-button-container">
				<button class="${className}">
					${Loc.getMessage('TASKS_SCRUM_ROBOTS_BUTTON')}
				</button>
			</div>
		`;

		Event.bind(node, 'click', this.onClick.bind(this));

		return node;
	}

	onClick()
	{
		if (this.isShowLimitSidePanel())
		{
			// eslint-disable-next-line
			BX.UI.InfoHelper.show('limit_tasks_robots');
		}
		else
		{
			const url = '/bitrix/components/bitrix/tasks.automation/slider.php?site_id='
				+ Loc.getMessage('SITE_ID') + '&project_id=' + this.groupId;

			this.sidePanel.openSidePanelByUrl(url);
		}
	}

	isShowLimitSidePanel(): boolean
	{
		return (this.isTaskLimitsExceeded && !this.canUseAutomation);
	}
}