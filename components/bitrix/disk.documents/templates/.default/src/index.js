import {Sharing} from './fields/sharing';
import {ExternalLink} from './fields/externallink';
import Toolbar from './toolbar';
import GridList from './grid/list';
import GridTile from './grid/tile';
import {Options as GridOptions, Options} from './options';
import {Ears} from 'ui.ears';
import {EventEmitter} from 'main.core.events';
import Backend from './backend';

function showShared(objectId, node) {
	new Sharing(objectId, node);
}

function showExternalLink(objectId, node) {
	new ExternalLink(objectId, node);
}

const TileGridEmptyBlockGenerator = GridTile.generateEmptyBlock;
export {
	showExternalLink,
	showShared,
	Toolbar,
	Options,
	TileGridEmptyBlockGenerator,
	Backend
}

//Template things
BX.ready(() => {
	if (BX.Main.gridManager && BX.Main.gridManager
		.getInstanceById(GridOptions.getGridId()))
	{
		new GridList;
	}
	else if (BX.Main.tileGridManager && BX.Main.tileGridManager
		.getInstanceById(GridOptions.getGridId()))
	{
		new GridTile;
	}
	else
	{
		EventEmitter.subscribeOnce(EventEmitter.GLOBAL_TARGET, 'Grid::ready', ({compatData: [instance]}) => {
			if (instance && instance.getId() === GridOptions.getGridId())
			{
				new GridList;
			}
		});
		EventEmitter.subscribeOnce(EventEmitter.GLOBAL_TARGET, 'BX.TileGrid.Grid:initialized', ({compatData: [instance]}) => {
			if (instance && instance.getId() === GridOptions.getGridId())
			{
				new GridTile;
			}
		});
	}

	if (document.querySelector('#disk-documents-control-panel'))
	{
		const ears = new Ears({
			container: document.querySelector('#disk-documents-control-panel'),
			noScrollbar: false,
			className: 'disk-documents-ears'
		});
		ears.init();
	}

	const func = (id, uploader) => {
		uploader.limits["uploadFileExt"] = Options.getEditableExt().join(',');
		uploader.limits["uploadFile"] = '.' + Options.getEditableExt().join(',.');
		if (uploader.fileInput)
		{
			uploader.fileInput.accept = uploader.limits["uploadFile"];
		}
	}
	if (BX.UploaderManager && BX.UploaderManager.getById('DiskDocuments'))
	{
		func('DiskDocuments', BX.UploaderManager.getById('DiskDocuments'));
	}
	else
	{
		const listener = ({compatData: [id, uploader]}) => {
			setTimeout(() => {
				func(id, uploader)
			}, 200);
			EventEmitter.unsubscribe(EventEmitter.GLOBAL_TARGET, 'onUploaderIsInited', listener);
		};
		EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, 'onUploaderIsInited', listener);
	}
});