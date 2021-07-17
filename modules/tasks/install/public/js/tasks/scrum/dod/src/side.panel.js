import {Dom} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

export class SidePanel extends EventEmitter
{
	constructor()
	{
		super();

		this.setEventNamespace('BX.Tasks.Scrum.DodSidePanel');

		/* eslint-disable */
		this.sidePanelManager = BX.SidePanel.Instance;
		this.contentSidePanelManager = new BX.SidePanel.Manager({});
		/* eslint-enable */

		this.contentSidePanels = new Set();

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

		EventEmitter.subscribe('SidePanel.Slider:onCloseComplete', (event: BaseEvent) => {
			const [sliderEvent] = event.getCompatData();
			const sidePanel = sliderEvent.getSlider();
			if (this.contentSidePanels.has(sidePanel.getUrl()))
			{
				this.contentSidePanels.delete(sidePanel.getUrl());
				if (!this.contentSidePanels.size)
				{
					this.resetBodyWidthHack();
					this.addEscapePressHandler();
				}
			}
		});
	}

	openSidePanel(id, options)
	{
		this.applyBodyWidthHack();
		this.removeEscapePressHandler();

		this.contentSidePanelManager.open(id, options);

		this.contentSidePanels.add(id);
	}

	existFrameTopSlider(): boolean
	{
		return Boolean(this.sidePanelManager.getTopSlider());
	}

	addEscapePressHandler()
	{
		const sidePanel = this.sidePanelManager.getTopSlider();
		if (sidePanel)
		{
			const frameWindow = sidePanel.getFrameWindow();
			frameWindow.addEventListener('keydown', sidePanel.handleFrameKeyDown);
		}
	}

	removeEscapePressHandler()
	{
		const sidePanel = this.sidePanelManager.getTopSlider();
		if (sidePanel)
		{
			const frameWindow = sidePanel.getFrameWindow();
			frameWindow.removeEventListener('keydown', sidePanel.handleFrameKeyDown);
		}
	}

	applyBodyWidthHack()
	{
		if (this.existFrameTopSlider())
		{
			Dom.addClass(document.body, 'tasks-scrum-dod-panel-padding');
		}
	}

	resetBodyWidthHack()
	{
		if (this.existFrameTopSlider())
		{
			Dom.removeClass(document.body, 'tasks-scrum-dod-panel-padding');
		}
	}
}