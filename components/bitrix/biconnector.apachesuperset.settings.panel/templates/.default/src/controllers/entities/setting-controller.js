/* eslint-disable no-underscore-dangle */
import { Type } from 'main.core';
import { ApacheSupersetAnalytics } from 'biconnector.apache-superset-analytics';
import { EventEmitter, BaseEvent } from 'main.core.events';
import type { AnalyticInfo } from './analytic-info';

const SidePanel = BX.SidePanel;
export class SettingController extends BX.UI.EntityEditorController
{
	analytic: AnalyticInfo;
	constructor(id, settings)
	{
		super();

		this.initialize(id, settings);
		this.analytic = settings.config?.dashboardAnalyticInfo ?? {};

		EventEmitter.subscribeOnce('BX.UI.EntityEditor:onInit', (event: BaseEvent) => {
			const [editor] = event.getData();
			editor?._toolPanel.disableSaveButton();
		});

		EventEmitter.subscribeOnce('BX.UI.EntityEditor:onControlChange', (event: BaseEvent) => {
			const [editor] = event.getData();
			editor?._toolPanel.enableSaveButton();
		});

		EventEmitter.subscribeOnce('BX.UI.EntityEditor:onCancel', (event: BaseEvent) => {
			const [, eventArguments] = event.getData();
			eventArguments.enableCloseConfirmation = false;
		});

		EventEmitter.subscribeOnce('BX.UI.EntityEditor:onSave', (event: BaseEvent) => {
			const [, eventArguments] = event.getData();
			eventArguments.enableCloseConfirmation = false;
		});
	}

	onAfterSave()
	{
		let analyticOptions;
		if (Type.isStringFilled(this.analytic.type))
		{
			analyticOptions = {
				type: this.analytic.type.toLowerCase(),
				p1: ApacheSupersetAnalytics.buildAppIdForAnalyticRequest(this.analytic.appId),
				p2: this.analytic.id,
				c_element: 'grid_menu',
				status: 'success',
			};
		}
		else
		{
			analyticOptions = {
				c_element: 'grid_settings',
				status: 'success',
			};
		}

		ApacheSupersetAnalytics.sendAnalytics('edit', 'report_settings', analyticOptions);
		this?._editor?._modeSwitch.reset();

		this.#sendOnSaveEvent();
		this.innerCancel();
	}

	#sendOnSaveEvent(): void
	{
		const previousSlider = BX.SidePanel.Instance.getPreviousSlider(BX.SidePanel.Instance.getSliderByWindow(window));
		const parent = previousSlider ? previousSlider.getWindow() : top;
		if (!parent.BX.Event)
		{
			return;
		}

		parent.BX.Event.EventEmitter.emit('BX.BIConnector.Settings:onAfterSave');
	}

	innerCancel()
	{
		SidePanel.Instance.close();
	}
}
