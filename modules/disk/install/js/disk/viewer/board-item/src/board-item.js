export default class BoardItem extends BX.UI.Viewer.Item
{
	documentViewUrl: string;

	constructor(options)
	{
		options = options || {};

		super(options);
		this.documentViewUrl = options.documentViewUrl;
	}

	setPropertiesByNode(node: HTMLElement)
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
		const promise = new BX.Promise();

		this.controller.runActionByNode(this.sourceNode, 'open');

		promise.fulfill(this);

		return promise;
	}

	getSliderQueryParameters(): Object
	{
		return {
			action: 'disk.api.documentService.goToEditOrPreview',
			serviceCode: 'board',
			objectId: this.objectId || 0,
			attachedObjectId: this.attachedObjectId || 0,
			versionId: this.versionId || 0,
		};
	}
}
