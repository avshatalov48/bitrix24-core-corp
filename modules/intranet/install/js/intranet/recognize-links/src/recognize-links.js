import { Runtime, Extension } from 'main.core';

class RecognizeLinks
{
	constructor()
	{
		this.serverName = location.origin;
		this.netUrl = Extension.getSettings('intranet.recognize-links').netUrl;
	}

	analyze(): void
	{
		const referrer = document.referrer;
		if (referrer !== '' && !referrer.includes(this.serverName) && (this.netUrl === '' || !referrer.includes(this.netUrl)))
		{
			Runtime.loadExtension('im.public').then((exports) => {
				const { Messenger } = exports;
				Messenger.desktop.openPage(document.location.href, { skipNativeBrowser: true });
			});
		}
	}
}

setTimeout(() => {
	const isImInstalled = Extension.getSettings('intranet.recognize-links').isImInstalled;
	if (isImInstalled)
	{
		(new RecognizeLinks()).analyze();
	}
}, 200);
