import { Event, Loc, Tag } from 'main.core';
import { EventEmitter } from 'main.core.events';

import { SidePanel } from '../../service/side.panel';

import '../../css/robot.button.css';

type Params = {
	sidePanel: SidePanel,
	groupId: number,
	isTaskLimitsExceeded: boolean,
	canUseAutomation: boolean,
	isAutomationEnabled: boolean,
}

export class RobotButton extends EventEmitter
{
	node: HTMLElement;

	constructor(params: Params)
	{
		super(params);

		this.sidePanel = params.sidePanel;
		this.isTaskLimitsExceeded = params.isTaskLimitsExceeded;
		this.canUseAutomation = params.canUseAutomation;
		this.isAutomationEnabled = params.isAutomationEnabled;
		this.groupId = params.groupId;

		this.setEventNamespace('BX.Tasks.Scrum.RobotButton');
	}

	render(): HTMLElement
	{
		let className = 'ui-btn ui-btn-light-border ui-btn-no-caps ui-btn-themes ui-btn-round';
		if (this.isShowLimitSidePanel())
		{
			className += ' ui-btn-icon-lock ui-btn-xs';
		}
		else
		{
			className += ' tasks-scrum-robot-btn';
		}

		this.node = Tag.render`
			<button class="${className}">
				${Loc.getMessage('TASKS_SCRUM_ROBOTS_BUTTON')}
			</button>
		`;

		Event.bind(this.node, 'click', this.onClick.bind(this));

		return this.node;
	}

	onClick()
	{
		if (this.isShowLimitSidePanel())
		{
			const sliderCode = this.isAutomationEnabled ? 'limit_tasks_robots' : 'limit_crm_rules_off';

			BX.Runtime.loadExtension('ui.info-helper').then(({ FeaturePromotersRegistry }) => {
				if (FeaturePromotersRegistry)
				{
					FeaturePromotersRegistry.getPromoter({ code: sliderCode, bindElement: this.node }).show();
				}
				else
				{
					BX.UI.InfoHelper.show(sliderCode, {
						isLimit: true, limitAnalyticsLabels: {
							module: 'tasks',
							source: 'scrumActiveSprint',
						},
					});
				}
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
		return !this.isAutomationEnabled || this.isTaskLimitsExceeded || !this.canUseAutomation;
	}
}
