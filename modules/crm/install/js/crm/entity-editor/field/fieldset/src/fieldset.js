import {Loc, Tag, Dom, Type} from "main.core";
import {EventEmitter, BaseEvent} from "main.core.events";
import './fieldset.css';

export class EntityEditorFieldsetField extends BX.UI.EntityEditorField
{
	constructor()
	{
		super();
		this._entityEditorList = {};
		this._entityId = null;
		this._fieldsetContainer = null;

		this._addEmptyValue = false;

		this._nextIndex = 0;
	}

	static create(id, settings)
	{
		let self = new this(id, settings);
		self.initialize(id, settings);
		return self;
	}

	doInitialize()
	{
		super.doInitialize();
		this._entityId = this._editor.getId() + '_' + this.getId() + '_fields';

		this._addEmptyValue = this.getDataBooleanParam("addEmptyValue", false);

		let nextIndex = BX.prop.getInteger(this.getSchemeElement().getData(), "nextIndex", 0);
		this._nextIndex = (nextIndex > 0) ? nextIndex : 0;
	}

	layout(options)
	{
		if (this._hasLayout)
		{
			return;
		}

		this.ensureWrapperCreated({classNames: ["ui-entity-editor-content-block-field-fieldset-wrapper"]});
		this.adjustWrapper();

		if (!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		this._fieldsetContainer = Tag.render`<div class="ui-entity-editor-container"></div>`;
		Dom.append(this._fieldsetContainer, this._wrapper);

		if (this._mode === BX.UI.EntityEditorMode.edit)
		{
			let addButtonPanel = this.getAddButton();
			Dom.append(addButtonPanel, this._wrapper);
		}
		setTimeout(() => this.initializeExistedValues(), 0);

		this.registerLayout(options);
		this._hasLayout = true;
	}

	clearLayout()
	{
		super.clearLayout();
		this._entityEditorList = {};
	}

	createEntityEditor(id, values = {}, context = {})
	{
		let containerId = this._entityId + '_container_' + id;
		Dom.append(Tag.render`<div id="${containerId}" class="ui-entity-editor-field-fieldset"></div>`, this._fieldsetContainer);

		let entityEditorId = this._entityId + '_' + id;
		let prefix = this.getName() + '[' + id + ']';
		let section = {
			'name': entityEditorId + '_SECTION',
			'type': 'section',
			'enableToggling': false,
			'transferable': false,
			'data': {'isRemovable': false, 'enableTitle': false, 'enableToggling': false},
			'elements': this.getFields(prefix)
		};

		let config = BX.UI.EntityConfig.create(
			entityEditorId,
			{
				data: [section],
				scope: "C",
				enableScopeToggle: false,
				canUpdatePersonalConfiguration: false,
				canUpdateCommonConfiguration: false,
				options: []
			}
		);

		let scheme = BX.UI.EntityScheme.create(
			entityEditorId,
			{
				current: [section],
				available: []
			}
		);

		let entityEditor = BX.UI.EntityEditor.create(
			entityEditorId,
			{
				model: BX.UI.EntityEditorModelFactory.create(
					"",
					"",
					{
						isIdentifiable: false,
						data: this.getFieldsValues(prefix, values)
					}
				),
				config: config,
				scheme: scheme,
				context: context,
				containerId: containerId,
				serviceUrl: this._editor.getServiceUrl(),

				entityTypeName: "",
				entityId: 0,
				validators: [],
				controllers: [],
				detailManagerId: "",
				initialMode: BX.UI.EntityEditorMode.getName(this._mode),
				enableModeToggle: true,
				enableConfigControl: false,
				enableVisibilityPolicy: true,
				enableToolPanel: true,
				enableBottomPanel: false,
				enableFieldsContextMenu: true,
				enablePageTitleControls: false,
				readOnly: (this._mode == BX.UI.EntityEditorMode.view),
				enableAjaxForm: false,
				enableRequiredUserFieldCheck: true,
				enableSectionEdit: false,
				enableSectionCreation: false,
				enableSectionDragDrop: true,
				enableFieldDragDrop: true,
				enableSettingsForAll: false,
				enableContextDataLayout: false,
				formTagName: 'div',
				externalContextId: "",
				contextId: "",
				options: {'show_always': 'Y'},
				ajaxData: [],
				isEmbedded: true
			}
		);
		entityEditor._enableCloseConfirmation = false;
		EventEmitter.subscribe(entityEditor, 'onControlChanged', (event) =>
		{
			if (!this.isChanged())
			{
				this.markAsChanged();
			}
		});

		let container = entityEditor.getContainer();
		if (Type.isDomNode(container))
		{
			Dom.prepend(this.getDeleteButton(id), container);
		}

		if(values.hasOwnProperty("DELETED") && values["DELETED"] === 'Y')
		{
			this.layoutDeletedValue(entityEditor, id);
		}

		return entityEditor;
	}

	layoutDeletedValue(entityEditor, id)
	{
		if (entityEditor instanceof BX.UI.EntityEditor)
		{
			let container = entityEditor.getContainer();
			if (Type.isDomNode(container))
			{
				container.style.display = 'none';

				let inputName = `${this.getName()}[${id}][DELETED]`;
				if (!container.querySelector(`input[name="${inputName}"]`))
				{
					Dom.append(
						Tag.render`<input type="hidden" name="${inputName}" value="Y" />`,
						container
					);
				}
			}
		}
	}

	onDeleteButtonClick(id)
	{
		if (this._entityEditorList.hasOwnProperty(id))
		{
			this.layoutDeletedValue(this._entityEditorList[id], id);
			this.markAsChanged();
		}
	}

	onAddButtonClick()
	{
		this.addEmptyValue();
	}

	addEmptyValue(options)
	{
		let value = this.getValue();

		let id = 'n' + this._nextIndex++;

		value.push({
			'ID': id
		});

		this.getModel().setField(this.getName(), value);

		this._entityEditorList[id] = this.createEntityEditor(id);
		this.markAsChanged();

		return this._entityEditorList[id];
	}

	getEditors()
	{
		return this._entityEditorList;
	}

	getFields(prefix)
	{
		let fields = BX.clone(BX.prop.getArray(this.getSchemeElement().getData(), 'fields', []));
		for (let index = 0; index < fields.length; index++)
		{
			fields[index].name = this.getFieldName(fields[index].name, prefix);
		}
		return fields;
	};

	getFieldsValues(prefix, values)
	{
		let result = {};
		for (let fieldId in values)
		{
			result[this.getFieldName(fieldId, prefix)] = values[fieldId];
		}
		return result;
	};

	getFieldName(originalName, prefix)
	{
		return prefix + '[' + originalName + ']';
	}

	getDeleteButton(id)
	{
		return Tag.render`
		<div class="ui-entity-editor-field-fieldset-delete">
			<span class="ui-link ui-link-secondary" onclick="${this.onDeleteButtonClick.bind(this, id)}">
				${Loc.getMessage('UI_ENTITY_EDITOR_DELETE')}
			</span>
		</div>`;
	}

	getAddButton()
	{
		return Tag.render`
		<div class="ui-entity-editor-content-block-add-field">
			<span class="ui-entity-card-content-add-field" onclick="${this.onAddButtonClick.bind(this)}">
				${Loc.getMessage('UI_ENTITY_EDITOR_ADD')}
			</span>
		</div>`;
	}

	initializeExistedValues()
	{
		let existedItems = this.getValue();
		if (existedItems.length)
		{
			for (let item of existedItems)
			{
				if (!this._entityEditorList[item.ID])
				{
					this._entityEditorList[item.ID] = this.createEntityEditor(item.ID, item);
				}
			}
		}
		else if (this._mode === BX.UI.EntityEditorMode.edit && this._addEmptyValue)
		{
			this.addEmptyValue();
		}
	}
}

EventEmitter.subscribe('BX.UI.EntityEditorControlFactory:onInitialize', (event: BaseEvent) => {
		let data = event.getData();
		if (data[0])
		{
			data[0].methods["fieldset"] = (type, controlId, settings) => {
				if (type === "fieldset")
				{
					return EntityEditorFieldsetField.create(controlId, settings);
				}
				return null;
			};
		}
		event.setData(data);
});
