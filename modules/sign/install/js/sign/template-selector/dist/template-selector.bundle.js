this.BX = this.BX || {};
(function (exports,ui_sidepanel_layout,ui_uploader_core,sidepanel,main_core_events,main_core,main_loader) {
	'use strict';

	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");

	var _request = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("request");

	class Backend extends main_core_events.EventEmitter {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _request, {
	      value: _request2
	    });
	    Object.defineProperty(this, _cache, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    this.setEventNamespace('BX.Sign.TemplateSelector.Backend');
	    this.subscribeFromOptions(options == null ? void 0 : options.events);
	    this.setOptions(options);
	  }

	  setOptions(options) {
	    babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].set('options', { ...options
	    });
	  }

	  getOptions() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].get('options', {});
	  }

	  getTemplatesList() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _request)[_request]('list');
	  }

	}

	function _request2(action) {
	  return new Promise((resolve, reject) => {
	    main_core.ajax.runAction(`sign.blank.${action}`).then(result => {
	      resolve(result);
	    }).catch(error => {
	      reject(error);
	    });
	  });
	}

	let _ = t => t,
	    _t,
	    _t2;

	var _cache$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");

	var _getLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLoader");

	var _getProgressLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getProgressLayout");

	class LoadingStatus {
	  constructor({
	    targetContainer
	  }) {
	    Object.defineProperty(this, _getProgressLayout, {
	      value: _getProgressLayout2
	    });
	    Object.defineProperty(this, _getLoader, {
	      value: _getLoader2
	    });
	    Object.defineProperty(this, _cache$1, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });

	    if (main_core.Type.isDomNode(targetContainer)) {
	      main_core.Dom.append(this.getLayout(), targetContainer);
	    }
	  }

	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].remember('loaderContainer', () => {
	      const layout = main_core.Tag.render(_t || (_t = _`
				<div class="sign-template-selector-list-item-loader">
					${0}
				</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _getProgressLayout)[_getProgressLayout]());
	      void babelHelpers.classPrivateFieldLooseBase(this, _getLoader)[_getLoader]().show(layout);
	      return layout;
	    });
	  }

	  show() {
	    main_core.Dom.addClass(this.getLayout(), 'sign-template-selector-list-item-loader-show');
	  }

	  hide() {
	    main_core.Dom.removeClass(this.getLayout(), 'sign-template-selector-list-item-loader-show');
	  }

	  updateStatus(value) {
	    babelHelpers.classPrivateFieldLooseBase(this, _getProgressLayout)[_getProgressLayout]().textContent = `${value}%`;
	  }

	}

	function _getLoader2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].remember('loader', () => {
	    return new main_loader.Loader({
	      size: 60
	    });
	  });
	}

	function _getProgressLayout2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].remember('progressLayout', () => {
	    return main_core.Tag.render(_t2 || (_t2 = _`
				<div class="sign-template-selector-list-item-loader-progress"></div>
			`));
	  });
	}

	let _$1 = t => t,
	    _t$1,
	    _t2$1,
	    _t3,
	    _t4,
	    _t5,
	    _t6,
	    _t7;

	var _cache$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");

	class ListItem extends main_core_events.EventEmitter {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _cache$2, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    this.setEventNamespace('BX.Sign.TemplateSelector.ListItem');
	    this.subscribeFromOptions(options == null ? void 0 : options.events);
	    this.setOptions(options);
	    this.setSelected(options == null ? void 0 : options.selected);
	    this.setLoading(options == null ? void 0 : options.loading);

	    if (main_core.Type.isDomNode(options == null ? void 0 : options.targetContainer)) {
	      this.appendTo(options == null ? void 0 : options.targetContainer);
	    }
	  }

	  setOptions(options) {
	    babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].set('options', { ...options
	    });
	  }

	  getOptions() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].get('options', {});
	  }

	  setLoading(value) {
	    babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].set('loading', value);

	    if (value) {
	      this.getLoadingStatus().show();
	    } else {
	      this.getLoadingStatus().hide();
	    }
	  }

	  updateStatus(value) {
	    this.getLoadingStatus().updateStatus(value);
	  }

	  getLoading() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].get('loading', false);
	  }

	  hasTitle() {
	    var _this$getOptions;

	    return main_core.Type.isStringFilled((_this$getOptions = this.getOptions()) == null ? void 0 : _this$getOptions.title);
	  }

	  hasDescription() {
	    var _this$getOptions2;

	    return main_core.Type.isStringFilled((_this$getOptions2 = this.getOptions()) == null ? void 0 : _this$getOptions2.description);
	  }

	  getIconLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('iconLayout', () => {
	      const {
	        iconClass = '',
	        iconBackground
	      } = this.getOptions();

	      const additionalClass = (() => {
	        if (main_core.Type.isStringFilled(iconBackground)) {
	          return ` sign-template-selector-list-item-icon-${main_core.Text.encode(iconBackground)}`;
	        }

	        return '';
	      })();

	      return main_core.Tag.render(_t$1 || (_t$1 = _$1`
				<div class="sign-template-selector-list-item-icon${0}">
					<div class="sign-template-selector-list-item-icon-wrapper">
						<div class="${0}">
							<i></i>
						</div>
					</div>
				</div>
			`), additionalClass, iconClass);
	    });
	  }

	  getTitleLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('titleLayout', () => {
	      const {
	        title = ''
	      } = this.getOptions();
	      return main_core.Tag.render(_t2$1 || (_t2$1 = _$1`
				<div class="sign-template-selector-list-item-text-title">
					${0}
				</div>
			`), main_core.Text.encode(title));
	    });
	  }

	  getDescriptionLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('descriptionLayout', () => {
	      const {
	        description = ''
	      } = this.getOptions();
	      return main_core.Tag.render(_t3 || (_t3 = _$1`
				<div class="sign-template-selector-list-item-text-description">
					${0}
				</div>
			`), main_core.Text.encode(description));
	    });
	  }

	  getTextLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('textLayout', () => {
	      return main_core.Tag.render(_t4 || (_t4 = _$1`
				<div class="sign-template-selector-list-item-text">
					${0}
					${0}
				</div>
			`), this.hasTitle() ? this.getTitleLayout() : '', this.hasDescription() ? this.getDescriptionLayout() : '');
	    });
	  }

	  getAdditionalTextLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('additionalTextLayout', () => {
	      return main_core.Tag.render(_t5 || (_t5 = _$1`
				<div class="sign-template-selector-list-item-additional-text"></div>
			`));
	    });
	  }

	  onEditClick(event) {
	    event.preventDefault();
	    this.emit('onEditClick');
	  }

	  getEditButton() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('editButton', () => {
	      return main_core.Tag.render(_t6 || (_t6 = _$1`
				<div 
					class="sign-template-selector-list-item-edit-button"
					onclick="${0}"
					title="${0}"
				></div>
			`), this.onEditClick.bind(this), main_core.Loc.getMessage('SIGN_TEMPLATE_SELECTOR_EDIT_BUTTON_TITLE'));
	    });
	  }

	  onClick(event) {
	    event.preventDefault();
	    this.emit('onClick');
	  }

	  getLoadingStatus() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('loadingStatus', () => {
	      return new LoadingStatus({
	        targetContainer: this.getLayout()
	      });
	    });
	  }

	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('layout', () => {
	      const {
	        title = '',
	        editable
	      } = this.getOptions();
	      return main_core.Tag.render(_t7 || (_t7 = _$1`
				<div 
					class="sign-template-selector-list-item" 
					title="${0}"
					onclick="${0}"
				>
					${0}
					${0}
					${0}
				</div>
			`), main_core.Text.encode(title), this.onClick.bind(this), this.getIconLayout(), this.getTextLayout(), editable ? this.getEditButton() : '');
	    });
	  }

	  setSelected(value) {
	    if (value) {
	      main_core.Dom.addClass(this.getLayout(), 'sign-template-selector-list-item-selected');
	      this.emit('onSelect');
	    } else {
	      main_core.Dom.removeClass(this.getLayout(), 'sign-template-selector-list-item-selected');
	    }
	  }

	  appendTo(targetContainer) {
	    main_core.Dom.append(this.getLayout(), targetContainer);
	  }

	  prependTo(targetContainer) {
	    main_core.Dom.prepend(this.getLayout(), targetContainer);
	  }

	  getId() {
	    return this.getOptions().id;
	  }

	}

	let _$2 = t => t,
	    _t$2,
	    _t2$2,
	    _t3$1;

	var _cache$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");

	var _setOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setOptions");

	var _getOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOptions");

	var _getLoader$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLoader");

	var _resetSelected = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resetSelected");

	var _onLastTemplatesListItemClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onLastTemplatesListItemClick");

	var _onLastTemplatesListItemEditClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onLastTemplatesListItemEditClick");

	var _onLastTemplatesListItemSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onLastTemplatesListItemSelect");

	var _setLastTemplatesItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setLastTemplatesItems");

	var _getLastTemplatesItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLastTemplatesItems");

	var _cleanLastTemplatesListLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cleanLastTemplatesListLayout");

	var _disableSaveButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disableSaveButton");

	var _enableSaveButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("enableSaveButton");

	var _setSaveButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setSaveButton");

	var _getSaveButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSaveButton");

	/**
	 * @namespace BX.Sign
	 */
	class TemplateSelector extends main_core_events.EventEmitter {
	  constructor(_options) {
	    super();
	    Object.defineProperty(this, _getSaveButton, {
	      value: _getSaveButton2
	    });
	    Object.defineProperty(this, _setSaveButton, {
	      value: _setSaveButton2
	    });
	    Object.defineProperty(this, _enableSaveButton, {
	      value: _enableSaveButton2
	    });
	    Object.defineProperty(this, _disableSaveButton, {
	      value: _disableSaveButton2
	    });
	    Object.defineProperty(this, _cleanLastTemplatesListLayout, {
	      value: _cleanLastTemplatesListLayout2
	    });
	    Object.defineProperty(this, _getLastTemplatesItems, {
	      value: _getLastTemplatesItems2
	    });
	    Object.defineProperty(this, _setLastTemplatesItems, {
	      value: _setLastTemplatesItems2
	    });
	    Object.defineProperty(this, _onLastTemplatesListItemSelect, {
	      value: _onLastTemplatesListItemSelect2
	    });
	    Object.defineProperty(this, _onLastTemplatesListItemEditClick, {
	      value: _onLastTemplatesListItemEditClick2
	    });
	    Object.defineProperty(this, _onLastTemplatesListItemClick, {
	      value: _onLastTemplatesListItemClick2
	    });
	    Object.defineProperty(this, _resetSelected, {
	      value: _resetSelected2
	    });
	    Object.defineProperty(this, _getLoader$1, {
	      value: _getLoader2$1
	    });
	    Object.defineProperty(this, _getOptions, {
	      value: _getOptions2
	    });
	    Object.defineProperty(this, _setOptions, {
	      value: _setOptions2
	    });
	    Object.defineProperty(this, _cache$3, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    this.setEventNamespace('BX.Sign.TemplateSelector');
	    this.subscribeFromOptions(_options == null ? void 0 : _options.events);

	    babelHelpers.classPrivateFieldLooseBase(this, _setOptions)[_setOptions](_options);

	    babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].remember('fileUploader', () => {
	      return new ui_uploader_core.Uploader({
	        controller: 'ui.dev.fileUploader.testUploaderController',
	        controllerOptions: {
	          action: 'createByFiles'
	        },
	        browseElement: this.getUploadItems().map(item => {
	          return item.getLayout();
	        }),
	        acceptedFileTypes: ['.jpg', '.jpeg', '.png', '.pdf', '.doc', '.docx', '.rtf'],
	        multiple: true,
	        events: {
	          'File:onAdd': event => {
	            const {
	              file
	            } = event.getData();
	            const newTemplate = new ListItem({
	              id: file.id,
	              title: file.clientPreview.name,
	              iconClass: 'ui-icon sign-template-selector-last-templates-list-item-icon-image',
	              iconBackground: 'blue',
	              events: {
	                onClick: babelHelpers.classPrivateFieldLooseBase(this, _onLastTemplatesListItemClick)[_onLastTemplatesListItemClick].bind(this),
	                onEditClick: babelHelpers.classPrivateFieldLooseBase(this, _onLastTemplatesListItemEditClick)[_onLastTemplatesListItemEditClick].bind(this)
	              },
	              editable: true,
	              loading: true
	            });

	            babelHelpers.classPrivateFieldLooseBase(this, _getLastTemplatesItems)[_getLastTemplatesItems]().unshift(newTemplate);

	            babelHelpers.classPrivateFieldLooseBase(this, _resetSelected)[_resetSelected]();

	            newTemplate.prependTo(this.getLastTemplatesListLayout());
	          },
	          'File:onUploadProgress': event => {
	            const {
	              progress
	            } = event.getData();

	            const newTemplate = babelHelpers.classPrivateFieldLooseBase(this, _getLastTemplatesItems)[_getLastTemplatesItems]()[0];

	            newTemplate.updateStatus(progress);
	          },
	          'File:onUploadComplete': () => {
	            const timeoutID = setTimeout(() => {
	              const newTemplate = babelHelpers.classPrivateFieldLooseBase(this, _getLastTemplatesItems)[_getLastTemplatesItems]()[0];

	              newTemplate.getLoadingStatus().hide();
	              newTemplate.setSelected(true);
	              clearTimeout(timeoutID);
	            }, 1000);
	          }
	        }
	      });
	    });
	  }

	  getBackend() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].remember('backend', () => {
	      return new Backend({
	        events: {
	          onError: error => {
	            this.emit('onError', {
	              error
	            });
	          }
	        }
	      });
	    });
	  }

	  getFileUploader() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].get('fileUploader');
	  }

	  getUploadItems() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].remember('uploadItems', () => {
	      return [new ListItem({
	        id: 'image',
	        iconClass: 'ui-icon ui-icon-file-img',
	        title: main_core.Loc.getMessage('SIGN_TEMPLATE_SELECTOR_UPLOAD_IMAGE_TITLE'),
	        description: main_core.Loc.getMessage('SIGN_TEMPLATE_SELECTOR_UPLOAD_IMAGE_DESCRIPTION')
	      }), new ListItem({
	        id: 'pdf',
	        iconClass: 'ui-icon ui-icon-file-pdf',
	        title: main_core.Loc.getMessage('SIGN_TEMPLATE_SELECTOR_UPLOAD_PDF_TITLE'),
	        description: main_core.Loc.getMessage('SIGN_TEMPLATE_SELECTOR_UPLOAD_PDF_DESCRIPTION')
	      }), new ListItem({
	        id: 'doc',
	        iconClass: 'ui-icon ui-icon-file-doc',
	        title: main_core.Loc.getMessage('SIGN_TEMPLATE_SELECTOR_UPLOAD_DOC_TITLE'),
	        description: main_core.Loc.getMessage('SIGN_TEMPLATE_SELECTOR_UPLOAD_DOC_DESCRIPTION')
	      })];
	    });
	  }

	  getLastTemplatesListLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].remember('lastTemplatesListLayout', () => {
	      return main_core.Tag.render(_t$2 || (_t$2 = _$2`
				<div class="sign-template-selector-last-templates-list"></div>
			`));
	    });
	  }

	  getUploadLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].remember('uploadLayout', () => {
	      return main_core.Tag.render(_t2$2 || (_t2$2 = _$2`
				<div class="sign-template-selector-upload">
					<div class="sign-template-selector-upload-title">
						${0}
					</div>
					<div class="sign-template-selector-upload-list">
						${0}
					</div>
				</div>
			`), main_core.Loc.getMessage('SIGN_TEMPLATE_SELECTOR_UPLOAD_TITLE'), this.getUploadItems().map(item => item.getLayout()));
	    });
	  }

	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].remember('layout', () => {
	      var _options$upload;

	      const options = babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]();

	      return main_core.Tag.render(_t3$1 || (_t3$1 = _$2`
				<div class="sign-template-selector">
					${0}
					<div class="sign-template-selector-last-templates">
						<div class="sign-template-selector-last-templates-title">
							${0}
						</div>
						${0}
					</div>
				</div>
			`), (options == null ? void 0 : (_options$upload = options.upload) == null ? void 0 : _options$upload.enabled) !== false ? this.getUploadLayout() : '', main_core.Loc.getMessage('SIGN_TEMPLATE_SELECTOR_LAST_TEMPLATES_TITLE'), this.getLastTemplatesListLayout());
	    });
	  }

	  showLoader() {
	    void babelHelpers.classPrivateFieldLooseBase(this, _getLoader$1)[_getLoader$1]().show(this.getLastTemplatesListLayout());
	  }

	  hideLoader() {
	    void babelHelpers.classPrivateFieldLooseBase(this, _getLoader$1)[_getLoader$1]().hide();
	  }

	  drawList() {
	    babelHelpers.classPrivateFieldLooseBase(this, _cleanLastTemplatesListLayout)[_cleanLastTemplatesListLayout]();

	    this.showLoader();

	    babelHelpers.classPrivateFieldLooseBase(this, _disableSaveButton)[_disableSaveButton]();

	    this.getBackend().getTemplatesList().then(({
	      data
	    }) => {
	      this.hideLoader();

	      const options = babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]();

	      babelHelpers.classPrivateFieldLooseBase(this, _setLastTemplatesItems)[_setLastTemplatesItems](data.map(blank => {
	        var _options$templatesLis;

	        return new ListItem({
	          id: blank.ID,
	          title: blank.TITLE,
	          iconClass: 'ui-icon sign-template-selector-last-templates-list-item-icon-image',
	          iconBackground: 'blue',
	          events: {
	            onClick: babelHelpers.classPrivateFieldLooseBase(this, _onLastTemplatesListItemClick)[_onLastTemplatesListItemClick].bind(this),
	            onEditClick: babelHelpers.classPrivateFieldLooseBase(this, _onLastTemplatesListItemEditClick)[_onLastTemplatesListItemEditClick].bind(this),
	            onSelect: babelHelpers.classPrivateFieldLooseBase(this, _onLastTemplatesListItemSelect)[_onLastTemplatesListItemSelect].bind(this)
	          },
	          targetContainer: this.getLastTemplatesListLayout(),
	          editable: options == null ? void 0 : (_options$templatesLis = options.templatesList) == null ? void 0 : _options$templatesLis.editable
	        });
	      }));
	    });
	  }

	  renderTo(targetContainer) {
	    main_core.Dom.append(this.getLayout(), targetContainer);
	    void this.drawList();
	  }

	  openSlider() {
	    void this.drawList();
	    const SidePanel = main_core.Reflection.getClass('BX.SidePanel');

	    if (!main_core.Type.isNil(SidePanel)) {
	      SidePanel.Instance.open('template-selector', {
	        width: 628,
	        contentCallback: () => {
	          return ui_sidepanel_layout.Layout.createContent({
	            extensions: ['sign.template-selector'],
	            title: main_core.Loc.getMessage('SIGN_TEMPLATE_SELECTOR_SLIDER_TITLE'),
	            content: () => {
	              return this.getLayout();
	            },
	            buttons: ({
	              cancelButton,
	              SaveButton
	            }) => {
	              babelHelpers.classPrivateFieldLooseBase(this, _setSaveButton)[_setSaveButton](new SaveButton({
	                text: main_core.Loc.getMessage('SIGN_TEMPLATE_SELECTOR_SLIDER_SELECT_TEMPLATE_BUTTON_LABEL'),
	                onclick: () => {
	                  this.emit('onSelect');
	                  SidePanel.Instance.close();
	                }
	              }));

	              babelHelpers.classPrivateFieldLooseBase(this, _disableSaveButton)[_disableSaveButton]();

	              return [babelHelpers.classPrivateFieldLooseBase(this, _getSaveButton)[_getSaveButton](), cancelButton];
	            }
	          });
	        }
	      });
	    }
	  }

	}

	function _setOptions2(options) {
	  babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].set('options', options);
	}

	function _getOptions2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].get('options', {});
	}

	function _getLoader2$1() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].remember('loader', () => {
	    return new main_loader.Loader({
	      target: this.getLastTemplatesListLayout()
	    });
	  });
	}

	function _resetSelected2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _getLastTemplatesItems)[_getLastTemplatesItems]().forEach(listItem => {
	    listItem.setSelected(false);
	  });
	}

	function _onLastTemplatesListItemClick2(event) {
	  babelHelpers.classPrivateFieldLooseBase(this, _resetSelected)[_resetSelected]();

	  const targetItem = event.getTarget();
	  targetItem.setSelected(true);
	}

	function _onLastTemplatesListItemEditClick2(event) {
	  const target = event.getTarget();
	  const documentId = target.getId();
	  const SidePanel = main_core.Reflection.getClass('BX.SidePanel');

	  if (!main_core.Type.isNil(SidePanel)) {
	    SidePanel.Instance.open(`/sign/edit/${documentId}/`, {
	      allowChangeHistory: false
	    });
	  }
	}

	function _onLastTemplatesListItemSelect2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _enableSaveButton)[_enableSaveButton]();
	}

	function _setLastTemplatesItems2(items) {
	  babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].set('lastTemplatesItems', [...items]);
	}

	function _getLastTemplatesItems2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].get('lastTemplatesItems');
	}

	function _cleanLastTemplatesListLayout2() {
	  main_core.Dom.clean(this.getLastTemplatesListLayout());
	}

	function _disableSaveButton2() {
	  const saveButton = babelHelpers.classPrivateFieldLooseBase(this, _getSaveButton)[_getSaveButton]();

	  if (saveButton) {
	    saveButton.setDisabled(true);
	  }
	}

	function _enableSaveButton2() {
	  const saveButton = babelHelpers.classPrivateFieldLooseBase(this, _getSaveButton)[_getSaveButton]();

	  if (saveButton) {
	    saveButton.setDisabled(false);
	  }
	}

	function _setSaveButton2(button) {
	  babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].set('saveButton', button);
	}

	function _getSaveButton2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].get('saveButton');
	}

	exports.TemplateSelector = TemplateSelector;

}((this.BX.Sign = this.BX.Sign || {}),BX.UI.SidePanel,BX.UI.Uploader,BX,BX.Event,BX,BX));
//# sourceMappingURL=template-selector.bundle.js.map
