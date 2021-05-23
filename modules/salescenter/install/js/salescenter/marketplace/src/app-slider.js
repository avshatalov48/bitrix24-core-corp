import {EventTypes} from "./event-types";
import {AppLocal} from "./tile-slider/app-local";
import {AppInstalled} from "./tile-slider/app-installed";
import {AppUninstalled} from "./tile-slider/app-uninstalled";
import {EventEmitter, BaseEvent} from 'main.core.events';
import * as Tile from 'salescenter.tile';

class AppSlider extends EventEmitter {

	constructor()
	{
		super();
		this.setEventNamespace('BX.Salescenter.AppSlider');
	}

	openAppLocal(tile: Tile.Base, options = {})
	{
		if (tile.hasOwnProperty('width'))
		{
			options.width = Number(tile.width);
		}

		if (tile.getType() === Tile.Marketplace.type())
		{
			this.openApp(tile, options);
		}
		else
		{
			let system = new AppLocal();
			system.open(tile, options);
			system.subscribe(
				EventTypes.AppLocalSliderClose,
				(e) => this.emit(EventTypes.AppSliderSliderClose, new BaseEvent({data: e.data}))
			);
		}
	}

	openAppLocalLink(link, options = {})
	{
		let system = new AppLocal();
		system.openLink(link, options);
		system.subscribe(
			EventTypes.AppLocalSliderClose,
			(e) => this.emit(EventTypes.AppSliderSliderClose, new BaseEvent({data: e.data}))
		);
	}

	openApp(tile: Tile.Marketplace, options = {})
	{
		if (tile.isInstalled())
		{
			(new AppInstalled()).open(tile);
		}
		else
		{
			let uninstalled = new AppUninstalled();
			uninstalled.open(tile, options);
			uninstalled.subscribe(
				EventTypes.AppUninstalledSliderClose,
				() => this.emit(EventTypes.AppSliderSliderClose, new BaseEvent({data:{type: Tile.Marketplace.type()}})));
		}
	}
}

export{
	AppSlider
}