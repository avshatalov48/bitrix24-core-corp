
export default class OnlyofficeExternalLinkItem extends BX.UI.Viewer.Item
{
	documentViewUrl: string;

	constructor(options)
	{
		options = options || {};

		super(options);
		this.documentViewUrl = options.documentViewUrl;
	}

	setPropertiesByNode (node: HTMLElement)
	{
		super.setPropertiesByNode(node);
		this.documentViewUrl = node.dataset.documentViewUrl;
	}

	getDocumentViewUrl(): string
	{
		return this.documentViewUrl;
	}

	setController (controller: BX.UI.Viewer.Controller)
	{
		super.setController(controller);

		this.controller.preload = 0;
	}

	loadData ()
	{
		const uid = BX.util.getRandomString(16);
		BX.Disk.sendTelemetryEvent({
			action: 'start',
			uid: uid
		});

		BX.SidePanel.Instance.open(this.getDocumentViewUrl(), {
			width: '100%',
			cacheable: false,
			customLeftBoundary: 30,
			allowChangeHistory: false,
			data: {
				documentEditor: true,
				uid: uid
			}
		});

		return new BX.Promise();
	}
}