import {Manager} from 'salescenter.manager';
import {EventEmitter} from 'main.core.events';
import * as Tile from 'salescenter.tile';
import {EventTypes} from "../event-types";

const URL = '/marketplace/detail/#app#/';

class AppUninstalled extends EventEmitter {

	constructor()
	{
		super();
		this.setEventNamespace('BX.Salescenter.Marketplace.TileSlider.AppUninstalled');
	}

	open(tile: Tile.Base, options = {})
	{
		let url = URL.replace("#app#", encodeURIComponent(tile.code));

		Manager.openSlider(url, options).then(
			() => this.emit(EventTypes.AppUninstalledSliderClose));
	}
}

export{
	AppUninstalled
}
