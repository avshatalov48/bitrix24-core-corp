export default class OnlyOfficeItem extends BX.UI.Viewer.Item
{
	constructor(options)
	{
		options = options || {};

		super(options);
		this.objectId = options.objectId;
		this.attachedObjectId = options.attachedObjectId;
		this.versionId = options.versionId;
		this.openEditInsteadPreview = options.openEditInsteadPreview;
	}

	setController (controller: BX.UI.Viewer.Controller)
	{
		super.setController(controller);

		this.controller.preload = 0;
	}

	enableEditInsteadPreview()
	{
		this.openEditInsteadPreview = true;
	}

	setPropertiesByNode (node: HTMLElement)
	{
		super.setPropertiesByNode(node);

		this.objectId = node.dataset.objectId;
		this.attachedObjectId = node.dataset.attachedObjectId;
		this.versionId = node.dataset.versionId;
		this.openEditInsteadPreview = node.dataset.openEditInsteadPreview;
	}

	loadData ()
	{
		const uid = BX.util.getRandomString(16);
		BX.Disk.sendTelemetryEvent({
			action: 'start',
			uid: uid
		});

		BX.SidePanel.Instance.open(BX.util.add_url_param('/bitrix/services/main/ajax.php', this.getSliderQueryParameters()), {
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

	getSliderQueryParameters(): Object
	{
		let action = 'disk.api.documentService.goToPreview';
		if (this.openEditInsteadPreview && BX.Disk.getDocumentService() === 'onlyoffice')
		{
			action = 'disk.api.documentService.goToEditOrPreview';
		}

		return {
			action: action,
			serviceCode: 'onlyoffice',
			objectId: this.objectId || 0,
			attachedObjectId: this.attachedObjectId || 0,
			versionId: this.versionId || 0
		}
	}
}