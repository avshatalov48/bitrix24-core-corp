this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,landing_ui_panel_fieldspanel,ui_notification,ui_draganddrop_draggable,ui_sidepanel_layout,ui_buttons,main_loader,main_core_events,ui_forms,_ui_layoutForm,main_core) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4;
	const {
	  MemoryCache
	} = main_core.Cache;
	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	class Item extends main_core_events.EventEmitter {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _cache, {
	      writable: true,
	      value: new MemoryCache()
	    });
	    this.setEventNamespace('BX.Crm.Field.ListEditor.Item');
	    this.subscribeFromOptions(options.events);
	    this.setOptions(options);
	    this.onFormChange = this.onFormChange.bind(this);
	  }
	  setOptions(options) {
	    babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].set('options', {
	      ...options
	    });
	  }
	  getOptions() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].get('options', {});
	  }
	  getCustomTitleLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('customTitleLayout', () => {
	      return this.getLayout().querySelector('.crm-field-list-editor-item-text-custom-title');
	    });
	  }
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('layout', () => {
	      const {
	        data,
	        categoryCaption
	      } = this.getOptions();
	      const {
	        sourceData
	      } = this.getOptions();
	      const label = data.label || sourceData.caption;
	      const preparedCategoryCaption = (() => {
	        if (main_core.Type.isStringFilled(categoryCaption)) {
	          return `&middot; ${main_core.Text.encode(categoryCaption)}`;
	        }
	        return '';
	      })();
	      return main_core.Tag.render(_t || (_t = _`
				<div class="crm-field-list-editor-item" data-name="${0}">
					<div class="crm-field-list-editor-item-header">
						<div class="crm-field-list-editor-item-drag-button"></div>
						<div class="crm-field-list-editor-item-text">
							<div class="crm-field-list-editor-item-text-source-title">
								<span class="crm-field-list-editor-item-text-source-title-inner">${0}</span>
								<span class="crm-field-list-editor-item-text-source-title-inner">${0}</span>
							</div>
							<div class="crm-field-list-editor-item-text-custom-title">
								<div class="crm-field-list-editor-item-text-custom-title-inner">${0}</div>
							</div>
						</div>
						<div class="crm-field-list-editor-item-actions">
							<div 
								class="crm-field-list-editor-item-button-edit"
								onclick="${0}"
							></div>
							<div 
								class="crm-field-list-editor-item-button-remove"
								onclick="${0}"
							></div>
						</div>
					</div>
					<div class="crm-field-list-editor-item-body">
						${0}
					</div>
				</div>
			`), main_core.Text.encode((sourceData == null ? void 0 : sourceData.name) || ''), main_core.Text.encode((sourceData == null ? void 0 : sourceData.caption) || ''), preparedCategoryCaption, main_core.Text.encode(label), this.onEditClick.bind(this), this.onRemoveClick.bind(this), this.getFormLayout());
	    });
	  }
	  onEditClick(event) {
	    event.preventDefault();
	    if (!this.isOpened()) {
	      this.open();
	    } else {
	      this.close();
	    }
	  }
	  onRemoveClick(event) {
	    event.preventDefault();
	    this.emit('onRemove');
	  }
	  open() {
	    main_core.Dom.addClass(this.getLayout(), 'crm-field-list-editor-item-opened');
	  }
	  isOpened() {
	    return main_core.Dom.hasClass(this.getLayout(), 'crm-field-list-editor-item-opened');
	  }
	  close() {
	    main_core.Dom.removeClass(this.getLayout(), 'crm-field-list-editor-item-opened');
	  }
	  createTextInput(options) {
	    return main_core.Tag.render(_t2 || (_t2 = _`
			<div class="ui-form-row crm-field-list-editor-item-form-text-row">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text">${0}</div>
				</div>
				<div class="ui-form-content">
					<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
						<input
							type="text"
							name="${0}"
							value="${0}"
							oninput="${0}"
							class="ui-ctl-element">	
					</div>	
				</div>
			</div>
		`), options.label, options.name, options.value, this.onFormChange);
	  }
	  createCheckbox(options) {
	    return main_core.Tag.render(_t3 || (_t3 = _`
			<div class="ui-form-row crm-field-list-editor-item-form-checkbox-row">
				<div class="ui-form-content">
					<label class="ui-ctl ui-ctl-checkbox">
						<input 
							type="checkbox" 
							name="${0}"
							class="ui-ctl-element"
							onchange="${0}"
							${0}
						>
						<div class="ui-ctl-label-text">${0}</div>
					</label>	
				</div>
			</div>
		`), options.name, this.onFormChange, options.checked ? 'checked' : '', options.label);
	  }
	  getAllInputs() {
	    return [...this.getLayout().querySelectorAll('.ui-ctl-element')];
	  }
	  getValue() {
	    return this.getAllInputs().reduce((acc, input) => {
	      acc[input.name] = input.type === 'checkbox' ? input.checked : input.value;
	      return acc;
	    }, {
	      ...this.getOptions().data
	    });
	  }
	  onFormChange() {
	    const value = this.getValue();
	    this.getCustomTitleLayout().textContent = value.caption || value.label;
	    this.emit('onChange');
	  }
	  getFormControls() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('formControls', () => {
	      const editableEntries = Object.entries(this.getOptions().editable);
	      const {
	        data
	      } = this.getOptions();
	      return editableEntries.map(([name, options]) => {
	        if (options.type === 'string') {
	          return this.createTextInput({
	            name,
	            label: options.label,
	            value: data[name]
	          });
	        }
	        return this.createCheckbox({
	          name,
	          label: options.label,
	          checked: data[name]
	        });
	      });
	    });
	  }
	  getFormLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('formLayout', () => {
	      return main_core.Tag.render(_t4 || (_t4 = _`
				<div class="crm-field-list-editor-item-form">
					<div class="ui-form">
						${0}
					</div>
				</div>
			`), this.getFormControls());
	    });
	  }
	}

	const {
	  MemoryCache: MemoryCache$1
	} = main_core.Cache;
	var _instance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instance");
	var _cache$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	class Backend {
	  constructor() {
	    Object.defineProperty(this, _cache$1, {
	      writable: true,
	      value: new MemoryCache$1()
	    });
	  }
	  static getInstance() {
	    if (!babelHelpers.classPrivateFieldLooseBase(Backend, _instance)[_instance]) {
	      babelHelpers.classPrivateFieldLooseBase(Backend, _instance)[_instance] = new Backend();
	    }
	    return babelHelpers.classPrivateFieldLooseBase(Backend, _instance)[_instance];
	  }
	  getFieldsList(presetId) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].remember('fieldsList', () => {
	      return new Promise((resolve, reject) => {
	        main_core.ajax.runAction('crm.api.form.field.list', {
	          json: {
	            presetId
	          }
	        }).then(result => {
	          var _result$data;
	          if (main_core.Type.isPlainObject(result == null ? void 0 : (_result$data = result.data) == null ? void 0 : _result$data.tree)) {
	            resolve(result.data.tree);
	          } else {
	            reject(result);
	          }
	        }).catch(error => {
	          reject(error);
	        });
	      });
	    });
	  }
	  getFieldsSet(id) {
	    return new Promise((resolve, reject) => {
	      main_core.ajax.runAction('crm.api.fieldset.get', {
	        json: {
	          id
	        }
	      }).then(result => {
	        if (main_core.Type.isPlainObject(result == null ? void 0 : result.data)) {
	          resolve(result.data);
	        } else {
	          reject(result);
	        }
	      }).catch(error => {
	        reject(error);
	      });
	    });
	  }
	  saveFieldsSet(options) {
	    return new Promise((resolve, reject) => {
	      main_core.ajax.runAction('crm.api.fieldset.set', {
	        json: {
	          options
	        }
	      }).then(result => {
	        if (main_core.Type.isPlainObject(result == null ? void 0 : result.data)) {
	          resolve(result);
	        } else {
	          reject(result);
	        }
	      }).catch(error => {
	        reject(error);
	      });
	    });
	  }
	}
	Object.defineProperty(Backend, _instance, {
	  writable: true,
	  value: null
	});

	let _$1 = t => t,
	  _t$1,
	  _t2$1,
	  _t3$1;
	const {
	  MemoryCache: MemoryCache$2
	} = main_core.Cache;

	/**
	 * @memberOf BX.Crm.Field
	 */
	var _cache$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _loadPromise = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadPromise");
	var _defaultOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("defaultOptions");
	var _adjustSliderDragAndDropOffsets = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("adjustSliderDragAndDropOffsets");
	class ListEditor extends main_core_events.EventEmitter {
	  constructor(options = {}) {
	    super();
	    Object.defineProperty(this, _adjustSliderDragAndDropOffsets, {
	      value: _adjustSliderDragAndDropOffsets2
	    });
	    Object.defineProperty(this, _cache$2, {
	      writable: true,
	      value: new MemoryCache$2()
	    });
	    Object.defineProperty(this, _loadPromise, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.Crm.Field.ListEditor');
	    this.subscribeFromOptions(options.events);
	    this.setTitle(options.title || '');
	    this.onWindowResize = this.onWindowResize.bind(this);
	    this.setOptions({
	      ...babelHelpers.classPrivateFieldLooseBase(ListEditor, _defaultOptions)[_defaultOptions],
	      ...options
	    });
	    this.onDebounceChange = main_core.Runtime.debounce(this.onDebounceChange, this.getOptions().debouncingDelay, this);
	    this.draggable = new ui_draganddrop_draggable.Draggable({
	      container: this.getListContainer(),
	      draggable: '.crm-field-list-editor-item',
	      dragElement: '.crm-field-list-editor-item-drag-button',
	      offset: {
	        x: -800
	      },
	      context: window.top
	    });
	    this.draggable.subscribe('end', this.onSortEnd.bind(this));
	    this.showLoader();
	    babelHelpers.classPrivateFieldLooseBase(this, _loadPromise)[_loadPromise] = Promise.all([this.loadFieldsDictionary(), this.loadValue()]).then(([fieldsDictionary, value]) => {
	      if (main_core.Type.isPlainObject(fieldsDictionary)) {
	        this.setFieldsDictionary(fieldsDictionary);
	      } else {
	        console.error('BX.Crm.Field.ListEditor: Invalid fields dictionary');
	      }
	      if (main_core.Type.isPlainObject(value)) {
	        this.setClientEntityTypeId(value.clientEntityTypeId);
	        this.setEntityTypeId(value.entityTypeId);
	        if (main_core.Type.isStringFilled(value.title) && !main_core.Type.isStringFilled(this.getTitle())) {
	          this.setTitle(value.title);
	        }
	        if (main_core.Type.isArrayFilled(value.fields)) {
	          value.fields.forEach(itemData => {
	            this.addItem({
	              sourceData: this.getFieldByName(itemData.name),
	              data: itemData
	            });
	          });
	        }
	      } else {
	        console.error('BX.Crm.Field.ListEditor: Invalid value');
	      }
	      this.hideLoader();
	    });
	  }
	  setData(data) {
	    babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].set('data', {
	      ...data
	    });
	  }
	  getData() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].get('data', {});
	  }
	  setTitle(title) {
	    babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].set('title', title);
	  }
	  getTitle() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].get('title', '');
	  }
	  setClientEntityTypeId(clientEntityTypeId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].set('clientEntityTypeId', clientEntityTypeId);
	  }
	  getClientEntityTypeId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].get('clientEntityTypeId');
	  }
	  setEntityTypeId(entityTypeId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].set('entityTypeId', entityTypeId);
	  }
	  getEntityTypeId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].get('entityTypeId');
	  }
	  getLoader() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('loader', () => {
	      return new main_loader.Loader({
	        target: this.getLayout()
	      });
	    });
	  }
	  showLoader() {
	    main_core.Dom.addClass(this.getLayout(), 'crm-field-list-editor-state-load');
	    void this.getLoader().show();
	  }
	  hideLoader() {
	    main_core.Dom.removeClass(this.getLayout(), 'crm-field-list-editor-state-load');
	    void this.getLoader().hide();
	  }
	  setOptions(options) {
	    babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].set('options', {
	      ...options
	    });
	  }
	  getOptions() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].get('options', {});
	  }
	  setFieldsDictionary(fields) {
	    babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].set('fieldsDictionary', fields);
	  }
	  getFieldsDictionary() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].get('fieldsDictionary', []);
	  }
	  getListContainer() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('listContainer', () => {
	      return main_core.Tag.render(_t$1 || (_t$1 = _$1`
				<div class="crm-field-list-editor-list"></div>
			`));
	    });
	  }
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('layout', () => {
	      return main_core.Tag.render(_t2$1 || (_t2$1 = _$1`
				<div class="crm-field-list-editor">
					${0}
					<div class="crm-field-list-editor-footer">
						<span 
							class="ui-link ui-link-dashed"
							onclick="${0}"
						>
							${0}
						</span>
					</div>
				</div>
			`), this.getListContainer(), this.onAddFieldClick.bind(this), main_core.Loc.getMessage('CRM_FIELD_LIST_EDITOR_ADD_FIELD_BUTTON_LABEL'));
	    });
	  }
	  renderTo(target) {
	    if (!main_core.Type.isDomNode(target)) {
	      console.error('target is not a DOM element');
	    }
	    main_core.Dom.append(this.getLayout(), target);
	  }
	  loadFieldsDictionary() {
	    const fieldsPanelOptions = {
	      ...this.getOptions().fieldsPanelOptions,
	      disabledFields: this.getValue().map(field => {
	        return field.name;
	      })
	    };
	    return Backend.getInstance().getFieldsList((fieldsPanelOptions == null ? void 0 : fieldsPanelOptions.presetId) || null);
	  }
	  loadValue() {
	    return Backend.getInstance().getFieldsSet(this.getOptions().setId).then(result => {
	      return result.options;
	    });
	  }
	  getItems() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('items', []);
	  }
	  setItems(items) {
	    babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].set('items', items);
	  }
	  addItem(options) {
	    const items = this.getItems();
	    const hasItem = items.some(item => {
	      return item.getOptions().data.name === options.data.name;
	    });
	    if (!hasItem) {
	      const item = new Item({
	        ...options,
	        categoryCaption: this.getCategoryCaption(options.data.name),
	        editable: this.getOptions().editable,
	        events: {
	          onChange: () => {
	            this.onChange();
	          },
	          onRemove: this.onRemoveItemClick.bind(this)
	        }
	      });
	      items.push(item);
	      main_core.Dom.append(item.getLayout(), this.getListContainer());
	    }
	  }
	  onRemoveItemClick(event) {
	    const target = event.getTarget();
	    main_core.Dom.remove(target.getLayout());
	    this.setItems(this.getItems().filter(item => {
	      return item !== target;
	    }));
	    this.onChange();
	  }
	  getFieldByName(name) {
	    const fieldsDictionary = this.getFieldsDictionary();
	    return Object.values(fieldsDictionary).reduce((acc, category) => {
	      if (!acc) {
	        return category.FIELDS.find(field => {
	          return field.name === name;
	        });
	      }
	      return acc;
	    }, null);
	  }
	  getCategoryCaption(fieldName) {
	    const fieldsDictionary = this.getFieldsDictionary();
	    return Object.values(fieldsDictionary).reduce((acc, category) => {
	      if (!acc) {
	        const hasField = category.FIELDS.some(field => {
	          return field.name === fieldName;
	        });
	        if (hasField) {
	          return category.CAPTION;
	        }
	      }
	      return acc;
	    }, '');
	  }
	  showFieldsPanel(panelOptions) {
	    const fieldsPanel = landing_ui_panel_fieldspanel.FieldsPanel.getInstance();
	    main_core.Dom.append(fieldsPanel.layout, window.top.document.body);
	    return fieldsPanel.show(panelOptions);
	  }
	  onAddFieldClick(event) {
	    event.preventDefault();
	    const fieldsPanelOptions = {
	      ...this.getOptions().fieldsPanelOptions,
	      disabledFields: this.getValue().map(field => {
	        return field.name;
	      })
	    };
	    this.showFieldsPanel(fieldsPanelOptions).then(result => {
	      this.setFieldsDictionary(landing_ui_panel_fieldspanel.FieldsPanel.getInstance().getCrmFields());
	      return result;
	    }).then(result => {
	      result.forEach(fieldName => {
	        const fieldData = this.getFieldByName(fieldName);
	        if (!main_core.Type.isString(fieldData.label) && main_core.Type.isString(fieldData.caption)) {
	          fieldData.label = fieldData.caption;
	        }
	        this.addItem({
	          sourceData: fieldData,
	          data: fieldData
	        });
	        this.onChange();
	      });
	    });
	  }
	  onChange() {
	    this.emit('onChange');
	    this.onDebounceChange();
	  }
	  onDebounceChange() {
	    if (this.getOptions().autoSave) {
	      void this.save();
	    }
	    this.emit('onDebounceChange');
	  }
	  save() {
	    const fieldsPanelOptions = {
	      ...this.getOptions().fieldsPanelOptions,
	      disabledFields: this.getValue().map(field => {
	        return field.name;
	      })
	    };
	    return Backend.getInstance().saveFieldsSet({
	      id: this.getOptions().setId,
	      presetId: (fieldsPanelOptions == null ? void 0 : fieldsPanelOptions.presetId) || null,
	      entityTypeId: this.getEntityTypeId(),
	      clientEntityTypeId: this.getClientEntityTypeId(),
	      ...this.getData(),
	      fields: this.getValue()
	    }).then(() => {
	      this.emit('onSave');
	    });
	  }
	  getValue() {
	    return this.getItems().map(item => {
	      return item.getValue();
	    });
	  }
	  onWindowResize() {
	    babelHelpers.classPrivateFieldLooseBase(this, _adjustSliderDragAndDropOffsets)[_adjustSliderDragAndDropOffsets]();
	  }
	  showSlider() {
	    const buttons = [];
	    if (!this.getOptions().autoSave) {
	      buttons.push(new ui_buttons.SaveButton({
	        onclick: button => {
	          button.setWaiting(true);
	          this.save().then(() => {
	            button.setWaiting(false);
	            BX.SidePanel.Instance.close();
	          }).catch(data => {
	            top.BX.UI.Notification.Center.notify({
	              content: data.errors.map(item => main_core.Text.encode(item.message)).join('\n'),
	              autoHide: false
	            });
	            button.setWaiting(false);
	          });
	        }
	      }));
	    }
	    BX.SidePanel.Instance.open('crm:field-list-editor', {
	      width: 600,
	      cacheable: this.getOptions().cacheable,
	      contentCallback: () => {
	        return babelHelpers.classPrivateFieldLooseBase(this, _loadPromise)[_loadPromise].then(() => ui_sidepanel_layout.Layout.createContent({
	          extensions: ['crm.field.list-editor'],
	          title: this.getTitle(),
	          content: () => this.getLayout(),
	          buttons: ({
	            cancelButton
	          }) => {
	            return [...buttons, cancelButton];
	          }
	        })).catch(({
	          errors
	        }) => ui_sidepanel_layout.Layout.createContent({
	          extensions: ['ui.sidepanel-content'],
	          design: {
	            section: false
	          },
	          content: () => {
	            const title = main_core.Loc.getMessage('CRM_FIELD_LIST_EDITOR_ERROR_IN_LOAD');
	            const msg = ((errors || [])[0] || {}).message || 'Unknown error';
	            return main_core.Tag.render(_t3$1 || (_t3$1 = _$1`
								<div class="ui-slider-no-access">
									<div class="ui-slider-no-access-inner">
										<div class="ui-slider-no-access-title">${0}</div>
										<div class="ui-slider-no-access-subtitle">${0}</div>
										<div class="ui-slider-no-access-img">
											<div class="ui-slider-no-access-img-inner"></div>
										</div>
									</div>
								</div>
							`), main_core.Text.encode(title), main_core.Text.encode(msg));
	          },
	          buttons: ({
	            closeButton
	          }) => {
	            return [closeButton];
	          }
	        }));
	      },
	      events: {
	        onOpenComplete: () => {
	          const timeoutId = setTimeout(() => {
	            clearTimeout(timeoutId);
	            babelHelpers.classPrivateFieldLooseBase(this, _adjustSliderDragAndDropOffsets)[_adjustSliderDragAndDropOffsets]();
	          }, 500);
	          main_core.Event.bind(window, 'resize', this.onWindowResize);
	        },
	        onClose: () => {
	          main_core.Event.unbind(window, 'resize', this.onWindowResize);
	        }
	      }
	    });
	  }
	  onSortEnd() {
	    const listNodes = [...this.getListContainer().children];
	    this.getItems().sort((a, b) => {
	      const aIndex = listNodes.findIndex(node => {
	        return a.getLayout() === node;
	      });
	      const bIndex = listNodes.findIndex(node => {
	        return b.getLayout() === node;
	      });
	      return aIndex - bIndex;
	    });
	    this.onChange();
	  }
	}
	function _adjustSliderDragAndDropOffsets2() {
	  const sliderLayout = this.getLayout().closest('.ui-sidepanel-layout');
	  if (main_core.Type.isDomNode(sliderLayout)) {
	    const offsetLeft = -sliderLayout.getBoundingClientRect().left;
	    this.draggable.setOptions({
	      ...this.draggable.getOptions(),
	      offset: {
	        x: offsetLeft
	      }
	    });
	  }
	}
	Object.defineProperty(ListEditor, _defaultOptions, {
	  writable: true,
	  value: {
	    setId: 0,
	    autoSave: true,
	    cacheable: true,
	    fieldsPanelOptions: {},
	    debouncingDelay: 500
	  }
	});

	exports.Backend = Backend;
	exports.ListEditor = ListEditor;

}((this.BX.Crm.Field = this.BX.Crm.Field || {}),BX.Landing.UI.Panel,BX,BX.UI.DragAndDrop,BX.UI.SidePanel,BX.UI,BX,BX.Event,BX,BX,BX));
//# sourceMappingURL=list-editor.bundle.js.map
