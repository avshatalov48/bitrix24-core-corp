import {Dom} from 'main.core';
import {EventEmitter} from 'main.core.events';

export class SidePanel extends EventEmitter
{
	constructor()
	{
		super();

		this.setEventNamespace('BX.Tasks.Scrum.SidePanel');

		/* eslint-disable */
		this.sidePanelManager = BX.SidePanel.Instance;
		/* eslint-enable */

		this.bindEvents();
	}

	bindEvents()
	{
		/* eslint-disable */
		BX.addCustomEvent(window, 'SidePanel.Slider:onLoad', (event) => {
			const sidePanel = event.getSlider();
			sidePanel.setCacheable(false);
			this.emit('onLoadSidePanel', sidePanel);
		});
		BX.addCustomEvent(window, 'SidePanel.Slider:onClose', (event) => {
			const sidePanel = event.getSlider();
			this.emit('onCloseSidePanel', sidePanel);
		});
		BX.addCustomEvent(window, 'onAfterPopupShow', (popupWindow) => {
			const topSlider = this.sidePanelManager.getTopSlider();
			const topSidePanelZIndex = (topSlider ? topSlider.getZindex() : 1000);
			const popupWindowZIndex = popupWindow.getZindex();
			const zIndex = (topSidePanelZIndex > popupWindowZIndex ? topSidePanelZIndex + 1 : popupWindowZIndex + 1);
			Dom.style(popupWindow.getPopupContainer(), 'zIndex', zIndex);
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

	reloadPreviousSidePanel(currentSidePanel)
	{
		const previousSidePanel = this.sidePanelManager.getPreviousSlider(currentSidePanel);
		previousSidePanel.reload();
	}

	openSidePanelByUrl(url)
	{
		this.sidePanelManager.open(url);
	}

	openSidePanel(id, contentCallback)
	{
		this.sidePanelManager.open(id, {
			contentCallback: contentCallback,
			zIndex: 1000
		});
	}
}