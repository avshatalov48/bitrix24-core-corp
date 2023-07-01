import {EntityEditorBaseAddressField} from "crm.entity-editor.field.address.base";
import {Tag, Dom, Type} from "main.core";
import {EventEmitter} from "main.core.events";

export class EntityEditorUiAddressField extends BX.UI.EntityEditorField
{
	constructor()
	{
		super();
		this._field = null;
		this._autocompleteEnabled = false;
		this._restrictionsCallback = null;
	}

	initialize(id, settings)
	{
		super.initialize(id, settings);
		let params = this._schemeElement.getData();

		this._autocompleteEnabled = BX.prop.getBoolean(params, "autocompleteEnabled", false);
		if (!this._autocompleteEnabled)
		{
			this._restrictionsCallback = BX.prop.getString(params, "featureRestrictionCallback", '');
		}
		settings.enableAutocomplete = this._autocompleteEnabled;
		settings.hideDefaultAddressType = true;
		settings.addressZoneConfig = BX.prop.getObject(params, "addressZoneConfig", {});
		settings.defaultAddressTypeByCategory = BX.prop.getInteger(params, "defaultAddressTypeByCategory", 0);
		this._field = EntityEditorBaseAddressField.create(id, settings);
		this._field.setMultiple(true);
		this._field.setTypesList(BX.prop.getObject(params, "types", {}));
		EventEmitter.subscribe(this._field, 'onUpdate', this.onAddressListUpdate.bind(this));
		EventEmitter.subscribe(this._field, 'onStartLoadAddress', this.onStartLoadAddress.bind(this));
		EventEmitter.subscribe(this._field, 'onAddressLoaded', this.onAddressLoaded.bind(this));
		EventEmitter.subscribe(this._field, 'onError', this.onError.bind(this));
		this._valueNode = null;
		this._modelTypes = [];

		this.initializeFromModel();
	}

	initializeFromModel()
	{
		let value = this.prepareValue(this.getValue());
		this._modelTypes = Object.keys(value);
		this._field.setValue(value);
	}

	prepareValue(value)
	{
		return Type.isPlainObject(value) ? value : {};
	}

	onBeforeSubmit()
	{

		if (!Type.isDomNode(this._valueNode))
		{
			return;
		}
		Dom.clean(this._valueNode);

		let values = {};
		for (let type of this._modelTypes)
		{
			values[type] = "";
		}
		for (let address of this._field.getValue())
		{
			if (address.value.length)
			{
				values[address.type] = address.value;
			}
		}

		let fieldNamePrefix = this.getName();
		for (let type in values)
		{
			if (!values.hasOwnProperty(type))
			{
				continue;
			}
			let value = values[type];
			let name ='';
			if (Type.isStringFilled(value))
			{
				name = `${fieldNamePrefix}[${type}]`;
			}
			else
			{
				name = `${fieldNamePrefix}[${type}][DELETED]`;
				value = 'Y';
			}

			let node = Tag.render`<input type="hidden">`;
			node.name = name;
			node.value = value;
			Dom.append(node, this._valueNode);
		}
	}

	refreshLayout(options)
	{
		this.initializeFromModel();
		super.refreshLayout(options);
	}

	layout(options)
	{
		if (this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({classNames: ["ui-entity-editor-content-block-field-address crm-entity-widget-content-block"]});
		this.adjustWrapper();

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

		let fieldContainer = this._field.layout(this._mode === BX.UI.EntityEditorMode.edit);
		fieldContainer.classList.add('ui-entity-editor-content-block');
		this._wrapper.appendChild(fieldContainer);

		this._valueNode = Tag.render`<span></span>`;
		this._wrapper.appendChild(this._valueNode);

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

	createTitleMarker()
	{
		if(this._mode === BX.UI.EntityEditorMode.view)
		{
			return null;
		}

		if (this._restrictionsCallback && this._restrictionsCallback.length)
		{
			let lockIcon = Tag.render` <span class="tariff-lock"></span>`;
			lockIcon.setAttribute('onclick', this._restrictionsCallback);
			return lockIcon;
		}

		return super.createTitleMarker();
	}

	onAddressListUpdate()
	{
		this.markAsChanged();
	}

	onStartLoadAddress()
	{
		let toolPanel = this.getEditor()._toolPanel;
		if (toolPanel)
		{
			toolPanel.setLocked(true);
		}
	}

	onAddressLoaded()
	{
		let toolPanel = this.getEditor()._toolPanel;
		if (toolPanel)
		{
			toolPanel.setLocked(false);
		}
	}

	onError(event)
	{
		const data = event.getData();
		this.showError(data.error);

		const toolPanel = this.getEditor()._toolPanel;
		if (toolPanel)
		{
			toolPanel.setLocked(false);
		}
	}

	static onInitializeEditorControlFactory(event)
	{
		let data = event.getData();
		if (data[0])
		{
			data[0].methods["crm_address"] = (type, controlId, settings) =>
			{
				if (type === "crm_address")
				{
					return EntityEditorUiAddressField.create(controlId, settings);
				}
				return null;
			};
		}
		event.setData(data);
	}

	static create(id, settings)
	{
		let self = new this(id, settings);
		self.initialize(id, settings);
		return self;
	}
}

EventEmitter.subscribe('BX.UI.EntityEditorControlFactory:onInitialize', EntityEditorUiAddressField.onInitializeEditorControlFactory);