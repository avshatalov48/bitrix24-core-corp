import {EventEmitter} from 'main.core.events';

export class SidePanel extends EventEmitter
{
	constructor()
	{
		super();

		this.setEventNamespace('BX.Tasks.Scrum.DodSidePanel');

		/* eslint-disable */
		this.sidePanelManager = BX.SidePanel.Instance;
		/* eslint-enable */

		this.BX = window.top.BX;

		this.bindEvents();
	}

	bindEvents()
	{
		/* eslint-disable */
		this.BX.addCustomEvent(window.top, 'SidePanel.Slider:onLoad', (event) => {
			const sidePanel = event.getSlider();
			sidePanel.setCacheable(false);
			this.emit('onLoadSidePanel', sidePanel);
		});
		this.BX.addCustomEvent(window.top, 'SidePanel.Slider:onClose', (event) => {
			const sidePanel = event.getSlider();
			this.emit('onCloseSidePanel', sidePanel);
		});
		/* eslint-enable */
	}

	isPreviousSidePanelExist(currentSidePanel): Boolean
	{
		return Boolean(this.sidePanelManager.getPreviousSlider(currentSidePanel));
	}

	reloadTopSidePanel()
	{
		this.sidePanelManager.getTopSlider().reload();
	}

	closeTopSidePanel()
	{
		this.sidePanelManager.getTopSlider().close();
	}

	reloadPreviousSidePanel(currentSidePanel)
	{
		const previousSidePanel = this.sidePanelManager.getPreviousSlider(currentSidePanel);
		previousSidePanel.reload();
	}

	openSidePanelByUrl(url)
	{
		this.sidePanelManager.open(url);
	}

	openSidePanel(id, options)
	{
		this.sidePanelManager.open(id, options);
	}
}