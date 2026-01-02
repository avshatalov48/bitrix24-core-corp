import { Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';

import { SidePanel } from '../../service/side.panel';
import { Button, ButtonIcon, ButtonColor, ButtonSize } from 'ui.buttons';

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
		const robotBtn = new Button({
			text: Loc.getMessage('TASKS_SCRUM_ROBOTS_BUTTON'),
			color: ButtonColor.LIGHT_BORDER,
			size: ButtonSize.EXTRA_SMALL,
			noCaps: true,
			round: true,
			dependOnTheme: true,
			onclick: () => {
				this.onClick();
			},
		});

		if (this.isShowLimitSidePanel())
		{
			robotBtn.setIcon(ButtonIcon.LOCK);
		}
		else
		{
			robotBtn.setIcon(ButtonIcon.ROBOTS);
		}

		return robotBtn.render();
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
