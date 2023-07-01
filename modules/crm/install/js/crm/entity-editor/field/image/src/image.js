import {EventEmitter, BaseEvent} from "main.core.events";
import {Runtime, ajax as Ajax} from "main.core";

export class EntityEditorImage extends BX.UI.EntityEditorImage
{
	loadInput()
	{
		const context = {};
		if (this._schemeElement)
		{
			context.ownerEntityTypeId = this._schemeElement.getDataIntegerParam('ownerEntityTypeId', null);
			context.ownerEntityId = this._schemeElement.getDataIntegerParam('ownerEntityId', null);
			context.ownerEntityCategoryId = this._schemeElement.getDataIntegerParam('ownerEntityCategoryId', null);
			context.permissionToken = this._schemeElement.getDataStringParam('permissionToken', null);
		}
		Ajax.runAction(
			'crm.entity.renderImageInput',
			{
				data: {
					entityTypeName: this._editor.getEntityTypeName(),
					entityId: this._editor.getEntityId(),
					fieldName: this.getDataKey(),
					fieldValue: this.getValue(),
					context,
				}
			}
		).then((result) => {
			const assets = result.data.assets;
			const assetsToLoad = [
				...assets.hasOwnProperty('css')
					? assets.css
					: [],
				...assets.hasOwnProperty('js')
					? assets.js
					: [],
			];
			BX.load(assetsToLoad, () => {
				if (assets.hasOwnProperty('string'))
				{
					Promise
						.all(assets.string.map((stringValue) => Runtime.html(null, stringValue)))
						.then(() => {
							this.onEditorHtmlLoad(result.data.html);
						})
					;
				}
				else
				{
					this.onEditorHtmlLoad(result.data.html);
				}
			});
		}, (result) => {
			this.onEditorHtmlLoad(result.errors[0].message);
		});
	}
	onEditorHtmlLoad(html)
	{
		if(this._mode === BX.UI.EntityEditorMode.edit && this._innerWrapper)
		{
			Runtime.html(this._innerWrapper, html);

			BX.addCustomEvent(window, "onAfterPopupShow", this._dialogShowHandler);
			BX.addCustomEvent(window, "onPopupClose", this._dialogCloseHandler);

			setTimeout(() => {
				this.bindFileEvents();
			}, 500);
		}
	}

	static create(id, settings)
	{
		const self = new BX.Crm.EntityEditorImage();
		self.initialize(id, settings);
		return self;
	}
}

// crm implementation of image field for ui version of entity editor
EventEmitter.subscribe('BX.UI.EntityEditorControlFactory:onInitialize', (event: BaseEvent) => {
	const data = event.getData();
	if (data[0])
	{
		data[0].methods['crm_image'] = (type, controlId, settings) => {
			if (type === 'crm_image')
			{
				return BX.Crm.EntityEditorImage.create(controlId, settings);
			}
			return null;
		};
	}
	event.setData(data);
});
