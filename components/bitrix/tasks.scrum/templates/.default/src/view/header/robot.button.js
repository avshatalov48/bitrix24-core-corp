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
			<button class="${className}">
				${Loc.getMessage('TASKS_SCRUM_ROBOTS_BUTTON')}
			</button>
		`;

		Event.bind(node, 'click', this.onClick.bind(this));

		return node;
	}

	onClick()
	{
		if (this.isShowLimitSidePanel())
		{
			BX.UI.InfoHelper.show('limit_tasks_robots', {
				isLimit: true,
				limitAnalyticsLabels: {
					module: 'tasks',
					source: 'scrumActiveSprint',
				},
			});
		}
		else
		{
			const url = '/bitrix/components/bitrix/tasks.automation/slider.php?site_id='
				+ Loc.getMessage('SITE_ID') + '&project_id=' + this.groupId;

			this.sidePanel.openSidePanel(url, {
				customLeftBoundary: 0,
				cacheable: false,
				loader: 'bizproc:automation-loader',
			});
		}
	}

	isShowLimitSidePanel(): boolean
	{
		return (this.isTaskLimitsExceeded && !this.canUseAutomation);
	}
}