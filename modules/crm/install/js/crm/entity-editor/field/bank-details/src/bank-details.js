import {EntityEditorFieldsetField} from "crm.entity-editor.field.fieldset";
import {BaseEvent, EventEmitter} from "main.core.events";
import {Loc, Tag, Type} from "main.core";

export class EntityEditorBankDetailsField extends EntityEditorFieldsetField
{
	getAddButton()
	{
		return Tag.render`
		<div class="ui-entity-editor-container-actions">
			<span>${Loc.getMessage('CRM_EDITOR_REQUISITE_BANK_DETAILS_ADD_LABEL')}</span><span> </span>
			<span class="ui-entity-editor-content-create-lnk" onclick="${this.onAddButtonClick.bind(this)}">${Loc.getMessage('CRM_EDITOR_REQUISITE_BANK_DETAILS_ADD_LINK_TEXT')}</span>
		</div>`;
	}

	onAddButtonClick()
	{
		this.addEmptyValue({scrollToTop: true});
	}

	addEmptyValue(options)
	{
		let editor = super.addEmptyValue(options);
		if (BX.prop.getBoolean(options, 'scrollToTop', false))
		{
			setTimeout(() =>
			{
				let container = editor.getContainer();
				if (Type.isDomNode(container))
				{
					let pos = BX.pos(container);
					let startPos = window.pageYOffset;
					let finishPos = pos.top - 15;
					let reverseDirection = startPos > finishPos;
					if (reverseDirection)
					{
						startPos = -startPos;
						finishPos = -finishPos;
					}

					new BX.easing({
						duration: 500,
						start: {top: startPos},
						finish: {top: finishPos},
						transition: BX.easing.transitions.quart,
						step: (state) =>
						{
							window.scrollTo(0, state.top * (reverseDirection ? -1 : 1));
						}
					}).animate();
				}
			}, 10);
		}
		return editor;
	}

	getResolverProperty()
	{
		return BX.prop.getArray(this.getSchemeElement().getData(), 'resolverProperty', {});
	}

	prepareEntityEditorContext()
	{
		const context = super.prepareEntityEditorContext();

		const schemeData = this.getSchemeElement().getData();
		const resolverProperty = BX.prop.getObject(schemeData, "resolverProperty", null)
		if (resolverProperty)
		{
			context["resolverProperty"] = resolverProperty;
		}

		return context;
	}
}

EventEmitter.subscribe('BX.UI.EntityEditorControlFactory:onInitialize', (event: BaseEvent) =>
{
	let data = event.getData();
	if (data[0])
	{
		data[0].methods["bankDetails"] = function(type, controlId, settings)
		{
			if (type === "bankDetails")
			{
				return EntityEditorBankDetailsField.create(controlId, settings);
			}
			return null;
		};
	}
	event.setData(data);
});