import {Dom} from 'main.core';
import {EventEmitter, BaseEvent} from 'main.core.events';

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
		EventEmitter.subscribe('SidePanel.Slider:onLoad', (event: BaseEvent) => {
			const [sliderEvent] = event.getCompatData();
			const sidePanel = sliderEvent.getSlider();
			sidePanel.setCacheable(false);
			this.emit('onLoadSidePanel', sidePanel);
		});

		EventEmitter.subscribe('SidePanel.Slider:onClose', (event: BaseEvent) => {
			const [sliderEvent] = event.getCompatData();
			const sidePanel = sliderEvent.getSlider();
			this.emit('onCloseSidePanel', sidePanel);
		});

		/* eslint-disable */
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