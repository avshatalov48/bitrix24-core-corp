import {EventEmitter} from 'main.core.events';

export class SidePanel extends EventEmitter
{
	constructor()
	{
		super();

		this.setEventNamespace('BX.Tasks.Scrum.Methodology.SidePanel');

		/* eslint-disable */
		this.sidePanelManager = BX.SidePanel.Instance;
		/* eslint-enable */
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