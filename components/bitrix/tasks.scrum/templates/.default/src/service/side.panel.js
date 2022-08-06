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
	}

	isPreviousSidePanelExist(currentSidePanel): Boolean
	{
		return Boolean(this.sidePanelManager.getPreviousSlider(currentSidePanel));
	}

	getTopSidePanel()
	{
		const topSidePanel = this.sidePanelManager.getTopSlider();

		return topSidePanel ? topSidePanel : null;
	}

	reloadTopSidePanel()
	{
		if (this.sidePanelManager.getTopSlider())
		{
			this.sidePanelManager.getTopSlider().reload();
		}
	}

	closeTopSidePanel()
	{
		if (this.sidePanelManager.getTopSlider())
		{
			this.sidePanelManager.getTopSlider().close();
		}
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

	showByExtension(name: string, params: Object): Promise
	{
		const extensionName = 'tasks.scrum.' + name.toLowerCase();

		return top.BX.Runtime.loadExtension(extensionName)
			.then((exports) => {

				name = name.replaceAll('-', '');

				if (exports && exports[name])
				{
					const extension = new exports[name](params);

					extension.show();

					return extension;
				}
				else
				{
					return null;
				}
			})
		;
	}
}