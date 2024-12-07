import { ajax as Ajax, Loc, Runtime, Uri } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';

export const loadDiskFileDialog = (dialogName, params: Object<string, string> = {}): Promise => {
	return new Promise((resolve): void => {
		Runtime.loadExtension('disk.legacy.file-dialog').then(() => {
			const handleInit = (event: BaseEvent): void => {
				const [name] = event.getData();
				if (dialogName === name)
				{
					EventEmitter.unsubscribe(BX.DiskFileDialog, 'inited', handleInit);
					resolve();
				}
			};

			EventEmitter.subscribe(BX.DiskFileDialog, 'inited', handleInit);

			// Invokes BX.DiskFileDialog.init
			Ajax.get(getDialogInitUrl(dialogName, params));
		});
	});
};

const getDialogInitUrl = (dialogName, params: Object<string, string> = {}): string => {
	const url = `/bitrix/tools/disk/uf.php?action=openDialog&SITE_ID=${
		Loc.getMessage('SITE_ID')}&dialog2=Y&ACTION=SELECT&MULTI=Y&dialogName=${dialogName}`
	;

	return Uri.addParam(url, params);
};
