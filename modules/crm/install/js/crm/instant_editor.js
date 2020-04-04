BX.CrmInstantEditor = function ()
{
	this._id = '';
	this._settings = {};
	this._fields = [];
	this._readonlyFieldNames = [];
};

BX.CrmInstantEditor.prototype =
{
	initialize: function (id, settings)
	{
		this._id = id;
		this._settings = settings ? settings : {};

		var containerID = this.getSetting('containerID', ['workarea']);
		if(!BX.type.isArray(containerID))
		{
			containerID = [containerID];
		}

		for(var i = 0; i < containerID.length; i++)
		{
			var container = BX(containerID[i]);
			if(!container)
			{
				continue;
			}

			var fieldContainers = BX.findChildren(container, { 'className':'crm-instant-editor-fld-block' }, true);
			if(fieldContainers)
			{
				for(var j = 0; j < fieldContainers.length; j++)
				{
					this.registerField(fieldContainers[j]);
				}
			}
		}

		BX.onCustomEvent('CrmInstantEditorCreated', [ this ]);
	},
	getId: function()
	{
		return this._id;
	},
	registerField: function(fieldContainer)
	{
		if(!BX.type.isDomNode(fieldContainer))
		{
			return false;
		}

		var button = BX.findChild(fieldContainer, { 'className':'crm-instant-editor-fld-btn' }, true, false);
		if(!button)
		{
			return false;
		}

		for(var i = 0; i < this._fields.length; i++)
		{
			if(this._fields[i].getContainer() === fieldContainer)
			{
				return false;
			}
		}

		var field = BX.CrmInstantEditorField.create(fieldContainer, button, this);
		this._fields.push(field);

		return true;
	},
	getSetting:function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	setSetting:function(name, value)
	{
		this._settings[name] = value;
	},
	riseSaveFieldValueEvent: function(name, value)
	{
		BX.onCustomEvent(this, 'CrmInstantEditorFieldValueSaved', [ name, value ]);
	},
	saveFieldValue: function(name, value, type, callback)
	{
		var self = this;
		BX.ajax(
			{
				'url': this.getSetting('url', '/bitrix/components/bitrix/crm.deal.show/ajax.php'),
				'method': 'POST',
				'dataType': 'json',
				'data':
				{
					'MODE': 'UPDATE',
					'OWNER_ID': this.getSetting('ownerID'),
					'OWNER_TYPE': this.getSetting('ownerType'),
					'FIELD_NAME': name,
					'FIELD_VALUE': value,
					'FIELD_TYPE': BX.type.isString(type) ? type : '',
					'DISABLE_USER_FIELD_CHECK': 'Y'
				},
				onsuccess: function(data)
				{
					BX.onCustomEvent(self, 'CrmInstantEditorFieldValueSaved', [ name, value ]);
					if(BX.type.isFunction(callback))
					{
						callback(data);
					}
					//Synchronize fields
					if(!BX.type.isArray(name))
					{
						self.setFieldValue(name, value);
					}
					else
					{
						for(var i = 0; i < name.length; i++)
						{
							self.setFieldValue(name[i], value);
						}
					}
				},
				onfailure: function(data)
				{
					if(BX.type.isFunction(callback))
					{
						callback(data);
					}
					//self._processAjaxError(data);
				}
			}
		);
	},
	updateField: function(field)
	{
		if(!field)
		{
			return;
		}

		this.saveFieldValue(
			field.getFieldName(),
			field.getFieldValue(),
			field.getType().toUpperCase(),
			function(result) { field.processUpdate(result); }
		);
	},
	setFieldValue: function(name, val)
	{
		var fields = this.getFieldsByName(name);

		for(var i = 0; i < fields.length; i++)
		{
			fields[i].setFieldValue(val);
		}

		BX.onCustomEvent(this, 'CrmInstantEditorFieldValueChanged', [ name, val ]);
	},
	setFieldReadOnly: function(name, readonly)
	{
		for(var i = 0; i < this._readonlyFieldNames.length; i++)
		{
			if(this._readonlyFieldNames[i] === name)
			{
				return;
			}
		}

		this._readonlyFieldNames.push(name);

		BX.onCustomEvent('CrmInstantEditorSetFieldReadOnly', [ this, name, readonly ]);
		var fields = this.getFieldsByName(name);
		for(var j = 0; j < fields.length; j++)
		{
			fields[j].setReadOnly(readonly);
		}
	},
	getReadOnlyFieldNames: function()
	{
		return this._readonlyFieldNames;
	},
	getField: function(name)
	{
		for(var i = 0; i < this._fields.length; i++)
		{
			var field = this._fields[i];
			if(field.getFieldID() === name)
			{
				return field;
			}
		}

		return null;
	},
	getFieldsByName: function(name)
	{
		var result = [];
		for(var i = 0; i < this._fields.length; i++)
		{
			var field = this._fields[i];
			if(field.getFieldID() === name)
			{
				result.push(field);
			}
		}

		return result;
	}
};
BX.CrmInstantEditor._default = null;
BX.CrmInstantEditor.getDefault = function()
{
	return this._default;
};

BX.CrmInstantEditor.items = {};
BX.CrmInstantEditor.create = function(id, settings)
{
	if(!BX.type.isNotEmptyString(id))
	{
		throw 'BX.CrmInstantEditor.create: id is not defined!';
	}

	var self = new BX.CrmInstantEditor();
	self.initialize(id, settings);
	this.items[id] = self;

	if(!this._default)
	{
		this._default = self;
	}

	return self;
};

BX.CrmInstantEditorFieldMode =
{
	edit: 1,
	view: 2
};

BX.CrmInstantEditorField = function()
{
	this._mode = BX.CrmInstantEditorFieldMode.view;
	this._type = 'undefined';
	this._value = null;
	this._isReadOnly = false;
	this._isDisabled = false;
	this._settings = {};
	this._container = this._button = this._manager = null;
	this._buttonClickHandler = BX.delegate(this._handleButtonClick, this);
	this._fieldEditorBlurHandler = BX.delegate(this._handleFieldEditorBlur, this);
	this._fieldViewerClickHandler = BX.delegate(this._handleFieldViewerClick, this);
	this._lheSaveContentHandler = BX.delegate(this._handleLheSaveContent, this);
	this._externalEditor = null;
};

BX.CrmInstantEditorField.prototype =
{
	initialize:function (container, button, manager)
	{
		if(!container)
		{
			throw "BX.CrmInstantEditorField.initialize: 'container' is not defined!";
		}
		this._container = container;

		if(!button)
		{
			throw "BX.CrmInstantEditorField.initialize: 'button' is not defined!";
		}
		this._button = button;
		button.setAttribute('title', BX.CrmInstantEditorMessages.editButtonTitle);

		if(!manager)
		{
			throw "BX.CrmInstantEditorField.initialize: 'manager' is not defined!";
		}
		this._manager = manager;

		var fieldViewers;
		var fieldSuffixes;

		if(BX.hasClass(this._button, 'crm-instant-editor-fld-btn-input'))
		{
			this._type = 'text';
			fieldViewers = BX.findChild(this._container, { 'className':'crm-instant-editor-fld-text' }, true, true);
			fieldSuffixes = BX.findChild(this._container, { 'className':'crm-instant-editor-fld-suffix' }, true, true);
		}
		else if(BX.hasClass(this._button, 'crm-instant-editor-fld-btn-lhe'))
		{
			this._type = 'lhe';
			fieldViewers = BX.findChild(this._container, { 'className':'crm-instant-editor-fld-text' }, true, true);

			var data = {};
			var lheData = BX.findChild(this._container, { 'tagName':'input', 'className':'crm-instant-editor-lhe-data' }, true, false);
			if(lheData)
			{
				try
				{
					data = eval('(' + lheData.value + ')');
					this._settings = data; //replace settings
				}
				catch(ex)
				{
				}
			}
		}

		this._initFieldValue();
		BX.bind(this._button, 'click', this._buttonClickHandler);

		if(fieldViewers)
		{
			for(var i = 0; i < fieldViewers.length; i++)
			{
				BX.bind(fieldViewers[i], 'click', this._fieldViewerClickHandler);
			}
		}

		if(fieldSuffixes)
		{
			for(var j = 0; j < fieldSuffixes.length; j++)
			{
				BX.bind(fieldSuffixes[j], 'click', this._fieldViewerClickHandler);
			}
		}
	},
	getManager: function()
	{
		return this._manager;
	},
	getMode: function()
	{
		return this._mode;
	},
	isReadOnly: function()
	{
		return this._isReadOnly;
	},
	setReadOnly: function(readonly)
	{
		readonly = !!readonly;
		if(this._isReadOnly !== readonly)
		{
			this._isReadOnly = readonly;
			this._switch2View(true);
		}
	},
	setExternalEditor: function(editor)
	{
		this._externalEditor = editor;
	},
	getType: function()
	{
		return this._type;
	},
	isMultiple: function()
	{
		return this._type === 'multitext';
	},
	getParentNode: function()
	{
	  return this._container.parentNode;
	},
	getFieldID: function()
	{
		var ary = BX.findChild(this._container, { 'className':'crm-instant-editor-data-name' }, true, true);
		if(!ary)
		{
			return '';
		}

		var result = '';
		for(var i = 0; i < ary.length; i++)
		{
			if(result.length > 0)
			{
				result += '_';
			}
			result += ary[i].value;
		}

		return result;
	},
	getFieldName: function()
	{
		var ary = BX.findChild(this._container, { 'className':'crm-instant-editor-data-name' }, true, true);
		if(!ary)
		{
			return [];
		}

		var result = [];
		for(var i = 0; i < ary.length; i++)
		{
			result.push(ary[i].value);
		}

		return result;
	},
	getContainer: function()
	{
		return this._container;
	},
	getSetting: function(name, defval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defval;
	},
	setSetting: function(name, value)
	{
		this._settings[name] = value;
	},
	processUpdate: function(result)
	{
		var name =  this.getFieldName();
		//Process update completion
		if(this._type === 'phone' && this.getCalltoFormat() === BX.CrmCalltoFormat.custom)
		{
			if(result['DATA'] && result['DATA'][name] && result['DATA'][name]['VIEW_HTML'])
			{
				this.setSetting('viewHTML', result['DATA'][name]['VIEW_HTML']);
			}
			this._isDisabled = false;
			this._switch2View(true);
		}
	},
	_initFieldValue: function()
	{
		if(this._value !== null)
		{
			return;
		}

		if(this.isMultiple())
		{
			this._value = [];
		}
		else
		{
			var el = BX.findChild(this._container, { 'className':'crm-instant-editor-data-value' }, true, false);
			this._value = el ? el.value : '';
		}
	},
	getCalltoFormat: function()
	{
		return this._manager.getSetting('callToFormat', BX.CrmCalltoFormat.slashless);
	},
	getFieldValue: function()
	{
		this._initFieldValue();
		return this._value;
	},
	setFieldValue: function(v)
	{
		this._setFieldValue(v);

		var fieldViewer, fieldEditor;
		if(this._type === 'text' || this._type === 'phone' || this._type === 'email' || this._type.indexOf('web') === 0 || this._type.indexOf('im') === 0)
		{
			fieldViewer = BX.findChild(this._container, { 'className':'crm-instant-editor-fld-text' }, true, false);
			fieldEditor = BX.findChild(this._container, { 'className':'crm-instant-editor-data-input' }, true, false);
			fieldEditor.value = v;
			fieldViewer.innerHTML = v;
		}
		else if(this._type === 'lhe')
		{
			fieldViewer = BX.findChild(this._container, { 'className':'crm-instant-editor-fld-text' }, true, false);
			fieldViewer.innerHTML = v;
		}
	},
	_setFieldValue: function(val)
	{
		this._value = val;

		if(!this.isMultiple())
		{
			var f = BX.findChild(this._container, { 'className':'crm-instant-editor-data-value' }, true, false);
			if(f)
			{
				f.value = val;
			}
		}
	},
	_handleButtonClick: function(e)
	{
		e = window.event || e;
		BX.PreventDefault(e);

		if(this._externalEditor)
		{
			this._externalEditor.openExternalFieldEditor(this);
			return;
		}

		this._switch2Edit();
	},
	_handleFieldEditorBlur: function(e)
	{
		e = window.event || e;

		if(this._type === 'multitext')
		{
			var target = e.target || e.srcElement;
			var fieldEditor = BX.findChild(this._container, { 'className':'crm-instant-editor-data-input' }, true, true);
			if(fieldEditor)
			{
				for(var i = 0; i < fieldEditor.length; i++)
				{
					if(fieldEditor[i] === target)
					{
						return;
					}
				}
			}
		}

		BX.PreventDefault(e);
		if(this._type === 'phone' && this.getCalltoFormat() === BX.CrmCalltoFormat.custom)
		{
			//We need to update viewer attributes after update. Disable link until updated attributes is retrived.
			this._isDisabled = true;
			this.setSetting('viewHTML', '');
		}
		this._switch2View();
		this._manager.updateField(this);
	},
	_handleFieldViewerClick: function(e)
	{
		e = window.event || e;
		BX.PreventDefault(e);

		if(this._externalEditor)
		{
			this._externalEditor.openExternalFieldEditor(this);
			return;
		}

		this._switch2Edit(e);
	},
	_handleLheSaveContent: function()
	{
		if(this._mode !== BX.CrmInstantEditorFieldMode.edit)
		{
			return;
		}

		this._switch2View();
		this._manager.updateField(this);
	},
	_getDOMElement: function(settingName)
	{
		var v = this.getSetting(settingName);
		return BX.type.isNotEmptyString(v) ? BX(v) : null;
	},
	_getJSObject: function(settingName, parent)
	{
		if(!parent)
		{
			parent = window;
		}
		var v = this.getSetting(settingName);
		return BX.type.isNotEmptyString(v) && typeof(parent[v]) != 'undefined' ? parent[v] : null;
	},
	_switch2Edit: function(force)
	{
		if(force === undefined || force === null)
		{
			force = false;
		}

		if(this._isReadOnly || (!force && this._mode === BX.CrmInstantEditorFieldMode.edit))
		{
			return;
		}

		BX.addClass(this._container, 'crm-instant-editor-fld-editable');

		var fieldEditor, fieldViewer;
		if(this._type === 'text' || this._type === 'phone'|| this._type === 'email' || this._type.indexOf('web') === 0 || this._type.indexOf('im') === 0)
		{
			fieldEditor = BX.findChild(this._container, { 'className':'crm-instant-editor-data-input' }, true, false);
			fieldViewer = BX.findChild(this._container, { 'className':'crm-instant-editor-fld-text' }, true, false);

			fieldEditor.style.display = '';
			fieldEditor.value = BX.util.htmlspecialcharsback(fieldViewer.innerHTML);
			fieldEditor.focus();

			BX.bind(fieldEditor, 'blur', this._fieldEditorBlurHandler);
		}
		else if(this._type === 'lhe')
		{
			var wrapper = this._getDOMElement('wrapperId');
			if(wrapper)
			{
				//var pos = BX.pos(this._container, false);
				wrapper.style.display = '';
			}

			var lhe = this._getJSObject('jsName');
			if(lhe)
			{
				// prevent 	#BXCURSOR# insert after text paste.
				if(lhe.bHandleOnPaste)
				{
					lhe.bHandleOnPaste = false;
				}

				lhe.ReInit(this.getFieldValue());
				// add custom onblur processing
				BX.addCustomEvent(lhe, 'OnSaveContent', this._lheSaveContentHandler);
			}
		}
		this._mode = BX.CrmInstantEditorFieldMode.edit;
	},
	_switch2View: function(force)
	{
		if(force === undefined || force === null)
		{
			force = false;
		}

		if(!force && this._mode === BX.CrmInstantEditorFieldMode.view)
		{
			return;
		}

		var fieldEditor, fieldViewer, v;
		if(this._type === 'text' || this._type === 'phone'|| this._type === 'email' || this._type.indexOf('web') === 0 || this._type.indexOf('im') === 0)
		{
			fieldEditor = BX.findChild(this._container, { 'className':'crm-instant-editor-data-input' }, true, false);
			fieldViewer = BX.findChild(this._container, { 'className':'crm-instant-editor-fld-text' }, true, false);

			v = fieldEditor.value;
			this._setFieldValue(v);
			fieldViewer.innerHTML = BX.util.htmlspecialchars(v);
			fieldEditor.style.display = 'none';

			BX.unbind(fieldEditor, 'blur', this._fieldEditorBlurHandler);
		}
		else if(this._type === 'lhe')
		{
			fieldViewer = BX.findChild(this._container, { 'className':'crm-instant-editor-fld-text' }, true, false);

			var wrapper = this._getDOMElement('wrapperId');
			if(wrapper)
			{
				wrapper.style.display = 'none';
			}

			var lhe = this._getJSObject('jsName');
			if(lhe)
			{
				//lhe.SaveContent(); already saved
				v = lhe.GetContent();
				this._setFieldValue(v);
				fieldViewer.innerHTML = v;
			}
		}
		if(fieldViewer)
		{
			if(this._isDisabled)
			{
				BX.addClass(fieldViewer, 'crm-instant-editor-fld-disabled');
			}
			else
			{
				BX.removeClass(fieldViewer, 'crm-instant-editor-fld-disabled');
			}
		}

		BX.removeClass(this._container, 'crm-instant-editor-fld-editable');

		var button = BX.findChild(this._container, { 'className':'crm-instant-editor-fld-btn' }, true, false);
		if(button)
		{
			if(this._isReadOnly)
			{
				BX.addClass(this._container, 'crm-detail-editable-locked');
				button.setAttribute('title', BX.CrmInstantEditorMessages.lockButtonTitle);
			}
			else
			{
				BX.removeClass(this._container, 'crm-detail-editable-locked');
				button.setAttribute('title', BX.CrmInstantEditorMessages.editButtonTitle);
			}
		}

		this._mode = BX.CrmInstantEditorFieldMode.view;
	}
};

BX.CrmInstantEditorField.create = function(container, button, manager)
{
	var self = new BX.CrmInstantEditorField();
	self.initialize(container, button, manager);
	return self;
};


BX.CrmInstantEditorMessages =
{
	editButtonTitle: 'Click to edit',
	lockButtonTitle: 'Edit is not allowed'
};
