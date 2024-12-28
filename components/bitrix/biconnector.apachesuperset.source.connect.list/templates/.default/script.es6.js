import { Reflection } from 'main.core';
import { EventEmitter } from 'main.core.events';

/**
 * @namespace BX.BIConnector
 */
class SourceConnectList
{
	#slider: BX.Sidepanel.Slider;

	constructor()
	{
		this.#slider = BX.SidePanel.Instance.getSliderByWindow(window);
		this.#subscribeOnEvents();
	}

	#subscribeOnEvents()
	{
		EventEmitter.subscribe('SidePanel.Slider:onClose', this.#onCloseSlider.bind(this));
		EventEmitter.subscribe('SidePanel.Slider:onMessage', (event) => {
			const [messageEvent] = event.getData();
			if (messageEvent.getEventId() === 'BIConnector:ExternalConnection:onConnectionCreated')
			{
				this.#closeSlider();
			}
		});
	}

	#closeSlider()
	{
		this.#slider.close();
	}

	#onCloseSlider()
	{
		BX.SidePanel.Instance.postMessage(window, 'BIConnector:ExternalConnectionGrid:reload', {});
	}
}

Reflection.namespace('BX.BIConnector').SourceConnectList = SourceConnectList;
