import {Manager} from 'salescenter.manager';
import * as Tile from 'salescenter.tile';
import {EventEmitter, BaseEvent} from 'main.core.events';
import {EventTypes} from "../event-types";

class AppLocal extends EventEmitter {

	constructor()
	{
		super();
		this.setEventNamespace('BX.Salescenter.TileSlider.AppSystem');
	}

	open(tile: Tile.Base, options = {})
	{
		this.openLink(tile.link, options, {data:{type: tile.getType()}});
	}

	openLink(link, options = {}, eventOptions = {})
	{
		Manager.openSlider(link, options).then(
			() => this.emit(EventTypes.AppLocalSliderClose, new BaseEvent(eventOptions)));
	}
}

export{
	AppLocal
}