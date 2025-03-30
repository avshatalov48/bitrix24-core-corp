
export default class BoardExternalLinkItem extends BX.UI.Viewer.Item
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

	setController(controller: BX.UI.Viewer.Controller)
	{
		super.setController(controller);

		this.controller.preload = 0;
	}

	loadData()
	{
		window.open(this.getDocumentViewUrl(), '_blank').focus();

		return new BX.Promise();
	}
}