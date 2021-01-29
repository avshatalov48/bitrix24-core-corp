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
		Manager.openSlider(tile.link, options).then(
			() => this.emit(EventTypes.AppLocalSliderClose, new BaseEvent({data:{type: tile.getType()}})));
	}
}

export{
	AppLocal
}