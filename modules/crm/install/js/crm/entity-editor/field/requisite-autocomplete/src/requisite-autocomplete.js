import {EventEmitter} from "main.core.events";
import {Dom, Loc, Tag} from "main.core";
import {RequisiteAutocompleteField} from "crm.entity-editor.field.requisite.autocomplete";

export class EntityEditorRequisiteAutocomplete extends BX.UI.EntityEditorField
{
	constructor()
	{
		super();
		this._autocomplete = null;
		this._autocompleteData = null;
	}

	doInitialize()
	{
		let params = this._schemeElement.getData();
		let enabled = BX.prop.getBoolean(params, "enabled", false);
		this._autocomplete = RequisiteAutocompleteField.create(this.getName(), {
			placeholderText: BX.prop.getString(params, "placeholder", ""),
			enabled: enabled,
			featureRestrictionCallback: BX.prop.getString(params, "featureRestrictionCallback", ''),
			searchAction: 'crm.requisite.entity.search',
			feedbackFormParams: BX.prop.getObject(params, "feedback_form", {}),
			showFeedbackLink: !enabled
		});
		this._autocomplete.subscribe('onSelectValue', this.onSelectAutocompleteValue.bind(this));
		this._autocomplete.subscribe('onClear', this.onClearAutocompleteValue.bind(this));
	}

	createTitleMarker()
	{
		if(this._mode === BX.UI.EntityEditorMode.view)
		{
			return null;
		}

		let restrictionCallback = BX.prop.getString(this._schemeElement.getData(), "featureRestrictionCallback", '');
		if (restrictionCallback === '')
		{
			return super.createTitleMarker();
		}
		let lockIcon = Tag.render` <span class="tariff-lock"></span>`;
		lockIcon.setAttribute('onclick', restrictionCallback);
		return lockIcon;
	}

	layout(options)
	{
		if (this._hasLayout)
		{
			return;
		}

		if (this._mode === BX.UI.EntityEditorMode.view)
		{
			if(!this._wrapper)
			{
				this._wrapper = BX.create("div");
			}
		}
		else
		{
			this.ensureWrapperCreated({ classNames: [ "ui-entity-editor-field-text" ] });
			this.adjustWrapper();
		}

		if (!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}
		if (this.isDragEnabled())
		{
			Dom.append(this.createDragButton(), this._wrapper);
		}

		Dom.append(this.createTitleNode(this.getTitle()), this._wrapper);

		if (this._mode === BX.UI.EntityEditorMode.edit)
		{
			let autocompleteContainer = Tag.render`<div class="ui-entity-editor-content-block"></div>`;
			this._autocomplete.layout(autocompleteContainer);
			this.updateAutocompleteState();
			Dom.append(autocompleteContainer, this._wrapper);
		}

		if (this.isContextMenuEnabled())
		{
			this._wrapper.appendChild(this.createContextMenuButton());
		}

		if (this.isDragEnabled())
		{
			this.initializeDragDropAbilities();
		}

		this.registerLayout(options);
		this._hasLayout = true;
	}

	isNeedToDisplay()
	{
		return super.isNeedToDisplay() && this._mode === BX.UI.EntityEditorMode.edit;
	}

	updateAutocompleteState()
	{
		let autocompleteState = null;
		try
		{
			autocompleteState = JSON.parse(this.getValue());
		}
		catch (e)
		{}
		this._autocomplete.setState(autocompleteState);
		this._autocomplete.setContext(this.getAutocompleteContext());
	}

	onSelectAutocompleteValue(event)
	{
		this._autocompleteData = event.getData();
		this.markAsChanged();
	}

	onClearAutocompleteValue(event)
	{
		this._autocomplete.setCurrentItem(null);
		this._autocompleteData = null;
	}

	getAutocompleteData()
	{
		return this._autocompleteData;
	}

	getAutocompleteContext()
	{
		return {
			'typeId': 'ITIN',
			'presetId': this._editor.getControlById('PRESET_ID').getValue()
		};
	}

	static create(id, settings)
	{
		let self = new this(id, settings);
		self.initialize(id, settings);
		return self;
	}

	static onInitializeEditorControlFactory(event)
	{
		let data = event.getData();
		if (data[0])
		{
			data[0].methods["requisite_autocomplete"] = (type, controlId, settings) => {
				if (type === "requisite_autocomplete")
				{
					return EntityEditorRequisiteAutocomplete.create(controlId, settings);
				}
				return null;
			};
		}
		event.setData(data);
	}

}

EventEmitter.subscribe('BX.UI.EntityEditorControlFactory:onInitialize', EntityEditorRequisiteAutocomplete.onInitializeEditorControlFactory);