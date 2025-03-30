/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_sidepanel_layout,ui_uploader_core,ui_buttons,sidepanel,main_loader,main_core,main_core_events,ui_entitySelector) {
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
	    this.setEventNamespace('BX.Sign.BlankSelector.Backend');
	    this.subscribeFromOptions(options == null ? void 0 : options.events);
	    this.setOptions(options);
	  }
	  setOptions(options) {
	    babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].set('options', {
	      ...options
	    });
	  }
	  getOptions() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].get('options', {});
	  }
	  getBlanksList(options) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _request)[_request]('list', main_core.Type.isPlainObject(options) ? options : {});
	  }
	  getBlankById(id) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _request)[_request]('getById', {
	      id
	    });
	  }
	}
	function _request2(action, data = {}) {
	  return new Promise((resolve, reject) => {
	    main_core.ajax.runAction(`sign.api.blank.${action}`, {
	      data
	    }).then(result => {
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
				<div class="sign-blank-selector-list-item-loader">
					${0}
				</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _getProgressLayout)[_getProgressLayout]());
	      void babelHelpers.classPrivateFieldLooseBase(this, _getLoader)[_getLoader]().show(layout);
	      return layout;
	    });
	  }
	  show() {
	    main_core.Dom.addClass(this.getLayout(), 'sign-blank-selector-list-item-loader-show');
	  }
	  hide() {
	    main_core.Dom.removeClass(this.getLayout(), 'sign-blank-selector-list-item-loader-show');
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
				<div class="sign-blank-selector-list-item-loader-progress"></div>
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
	    this.setEventNamespace('BX.Sign.BlankSelector.ListItem');
	    this.subscribeFromOptions(options == null ? void 0 : options.events);
	    this.setOptions(options);
	    this.setSelected(options == null ? void 0 : options.selected);
	    this.setLoading(options == null ? void 0 : options.loading);
	    if (main_core.Type.isDomNode(options == null ? void 0 : options.targetContainer)) {
	      this.appendTo(options == null ? void 0 : options.targetContainer);
	    }
	  }
	  setOptions(options) {
	    babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].set('options', {
	      ...options
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
	          return ` sign-blank-selector-list-item-icon-${main_core.Text.encode(iconBackground)}`;
	        }
	        return '';
	      })();
	      return main_core.Tag.render(_t$1 || (_t$1 = _$1`
				<div class="sign-blank-selector-list-item-icon${0}">
					<div class="sign-blank-selector-list-item-icon-wrapper">
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
				<div class="sign-blank-selector-list-item-text-title">
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
				<div class="sign-blank-selector-list-item-text-description">
					${0}
				</div>
			`), main_core.Text.encode(description));
	    });
	  }
	  getTextLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('textLayout', () => {
	      return main_core.Tag.render(_t4 || (_t4 = _$1`
				<div class="sign-blank-selector-list-item-text">
					${0}
					${0}
				</div>
			`), this.hasTitle() ? this.getTitleLayout() : '', this.hasDescription() ? this.getDescriptionLayout() : '');
	    });
	  }
	  getAdditionalTextLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('additionalTextLayout', () => {
	      return main_core.Tag.render(_t5 || (_t5 = _$1`
				<div class="sign-blank-selector-list-item-additional-text"></div>
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
					class="sign-blank-selector-list-item-edit-button"
					onclick="${0}"
					title="${0}"
				></div>
			`), this.onEditClick.bind(this), main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_EDIT_BUTTON_TITLE'));
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
					class="sign-blank-selector-list-item"
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
	      main_core.Dom.addClass(this.getLayout(), 'sign-blank-selector-list-item-selected');
	      this.emit('onSelect');
	    } else {
	      main_core.Dom.removeClass(this.getLayout(), 'sign-blank-selector-list-item-selected');
	    }
	  }
	  isSelected() {
	    return main_core.Dom.hasClass(this.getLayout(), 'sign-blank-selector-list-item-selected');
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
	  getData() {
	    return this.getOptions().data;
	  }
	}

	let _$2 = t => t,
	  _t$2,
	  _t2$2,
	  _t3$1;
	var _cache$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _setOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setOptions");
	var _getOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOptions");
	var _setCurrentPageNumber = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setCurrentPageNumber");
	var _getCurrentPageNumber = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCurrentPageNumber");
	var _getLoadMoreButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLoadMoreButton");
	var _getLoader$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLoader");
	var _resetSelected = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resetSelected");
	var _onLastBlanksListItemClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onLastBlanksListItemClick");
	var _onLastBlanksListItemEditClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onLastBlanksListItemEditClick");
	var _onLastBlanksListItemSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onLastBlanksListItemSelect");
	var _setLastBlanksItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setLastBlanksItems");
	var _getLastBlanksItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLastBlanksItems");
	var _cleanLastBlanksListLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cleanLastBlanksListLayout");
	var _disableSaveButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disableSaveButton");
	var _enableSaveButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("enableSaveButton");
	var _loadPage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadPage");
	var _setSaveButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setSaveButton");
	var _getSaveButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSaveButton");
	/**
	 * @namespace BX.Sign
	 */
	class BlankSelector extends main_core_events.EventEmitter {
	  constructor(_options) {
	    super();
	    Object.defineProperty(this, _getSaveButton, {
	      value: _getSaveButton2
	    });
	    Object.defineProperty(this, _setSaveButton, {
	      value: _setSaveButton2
	    });
	    Object.defineProperty(this, _loadPage, {
	      value: _loadPage2
	    });
	    Object.defineProperty(this, _enableSaveButton, {
	      value: _enableSaveButton2
	    });
	    Object.defineProperty(this, _disableSaveButton, {
	      value: _disableSaveButton2
	    });
	    Object.defineProperty(this, _cleanLastBlanksListLayout, {
	      value: _cleanLastBlanksListLayout2
	    });
	    Object.defineProperty(this, _getLastBlanksItems, {
	      value: _getLastBlanksItems2
	    });
	    Object.defineProperty(this, _setLastBlanksItems, {
	      value: _setLastBlanksItems2
	    });
	    Object.defineProperty(this, _onLastBlanksListItemSelect, {
	      value: _onLastBlanksListItemSelect2
	    });
	    Object.defineProperty(this, _onLastBlanksListItemEditClick, {
	      value: _onLastBlanksListItemEditClick2
	    });
	    Object.defineProperty(this, _onLastBlanksListItemClick, {
	      value: _onLastBlanksListItemClick2
	    });
	    Object.defineProperty(this, _resetSelected, {
	      value: _resetSelected2
	    });
	    Object.defineProperty(this, _getLoader$1, {
	      value: _getLoader2$1
	    });
	    Object.defineProperty(this, _getLoadMoreButton, {
	      value: _getLoadMoreButton2
	    });
	    Object.defineProperty(this, _getCurrentPageNumber, {
	      value: _getCurrentPageNumber2
	    });
	    Object.defineProperty(this, _setCurrentPageNumber, {
	      value: _setCurrentPageNumber2
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
	        acceptedFileTypes: ['.jpg', '.jpeg', '.png', '.pdf', '.doc', '.docx', '.rtf', '.odt'],
	        multiple: true,
	        events: {
	          'File:onAdd': event => {
	            const {
	              file
	            } = event.getData();
	            const newBlank = new ListItem({
	              id: file.id,
	              title: file.clientPreview.name,
	              iconClass: 'ui-icon sign-blank-selector-last-blanks-list-item-icon-image',
	              iconBackground: 'blue',
	              events: {
	                onClick: babelHelpers.classPrivateFieldLooseBase(this, _onLastBlanksListItemClick)[_onLastBlanksListItemClick].bind(this),
	                onEditClick: babelHelpers.classPrivateFieldLooseBase(this, _onLastBlanksListItemEditClick)[_onLastBlanksListItemEditClick].bind(this)
	              },
	              editable: true,
	              loading: true
	            });
	            babelHelpers.classPrivateFieldLooseBase(this, _getLastBlanksItems)[_getLastBlanksItems]().unshift(newBlank);
	            babelHelpers.classPrivateFieldLooseBase(this, _resetSelected)[_resetSelected]();
	            newBlank.prependTo(this.getLastBlanksListLayout());
	          },
	          'File:onUploadProgress': event => {
	            const {
	              progress
	            } = event.getData();
	            const newBlank = babelHelpers.classPrivateFieldLooseBase(this, _getLastBlanksItems)[_getLastBlanksItems]()[0];
	            newBlank.updateStatus(progress);
	          },
	          'File:onUploadComplete': () => {
	            const timeoutID = setTimeout(() => {
	              const newBlank = babelHelpers.classPrivateFieldLooseBase(this, _getLastBlanksItems)[_getLastBlanksItems]()[0];
	              newBlank.getLoadingStatus().hide();
	              newBlank.setSelected(true);
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
	        title: main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_UPLOAD_IMAGE_TITLE'),
	        description: main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_UPLOAD_IMAGE_DESCRIPTION')
	      }), new ListItem({
	        id: 'pdf',
	        iconClass: 'ui-icon ui-icon-file-pdf',
	        title: main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_UPLOAD_PDF_TITLE'),
	        description: main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_UPLOAD_PDF_DESCRIPTION')
	      }), new ListItem({
	        id: 'doc',
	        iconClass: 'ui-icon ui-icon-file-doc',
	        title: main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_UPLOAD_DOC_TITLE'),
	        description: main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_UPLOAD_DOC_DESCRIPTION')
	      })];
	    });
	  }
	  getLastBlanksListLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].remember('lastTemplatesListLayout', () => {
	      return main_core.Tag.render(_t$2 || (_t$2 = _$2`
				<div class="sign-blank-selector-last-blanks-list"></div>
			`));
	    });
	  }
	  getUploadLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].remember('uploadLayout', () => {
	      return main_core.Tag.render(_t2$2 || (_t2$2 = _$2`
				<div class="sign-blank-selector-upload">
					<div class="sign-blank-selector-upload-title">
						${0}
					</div>
					<div class="sign-blank-selector-upload-list">
						${0}
					</div>
				</div>
			`), main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_UPLOAD_TITLE'), this.getUploadItems().map(item => item.getLayout()));
	    });
	  }
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].remember('layout', () => {
	      var _options$upload;
	      const options = babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]();
	      return main_core.Tag.render(_t3$1 || (_t3$1 = _$2`
				<div class="sign-blank-selector">
					${0}
					<div class="sign-blank-selector-last-blanks">
						<div class="sign-blank-selector-last-blanks-title">
							${0}
						</div>
						${0}
					</div>
					<div class="sign-blank-selector-footer">
						${0}
					</div>
				</div>
			`), (options == null ? void 0 : (_options$upload = options.upload) == null ? void 0 : _options$upload.enabled) !== false ? this.getUploadLayout() : '', main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_LAST_BLANKS_TITLE'), this.getLastBlanksListLayout(), babelHelpers.classPrivateFieldLooseBase(this, _getLoadMoreButton)[_getLoadMoreButton]().render());
	    });
	  }
	  showLoader() {
	    void babelHelpers.classPrivateFieldLooseBase(this, _getLoader$1)[_getLoader$1]().show(this.getLastBlanksListLayout());
	  }
	  hideLoader() {
	    void babelHelpers.classPrivateFieldLooseBase(this, _getLoader$1)[_getLoader$1]().hide();
	  }
	  drawList() {
	    babelHelpers.classPrivateFieldLooseBase(this, _setCurrentPageNumber)[_setCurrentPageNumber](1);
	    babelHelpers.classPrivateFieldLooseBase(this, _cleanLastBlanksListLayout)[_cleanLastBlanksListLayout]();
	    this.showLoader();
	    babelHelpers.classPrivateFieldLooseBase(this, _disableSaveButton)[_disableSaveButton]();
	    void babelHelpers.classPrivateFieldLooseBase(this, _loadPage)[_loadPage]();
	    const moreButton = babelHelpers.classPrivateFieldLooseBase(this, _getLoadMoreButton)[_getLoadMoreButton]();
	    moreButton.setDisabled(false);
	    moreButton.setText(main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_LOAD_MORE_BUTTON_LABEL'));
	  }
	  renderTo(targetContainer) {
	    main_core.Dom.append(this.getLayout(), targetContainer);
	    void this.drawList();
	  }
	  getSelectedItem() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getLastBlanksItems)[_getLastBlanksItems]().find(item => {
	      return item.isSelected();
	    });
	  }
	  openSlider() {
	    void this.drawList();
	    const SidePanel = main_core.Reflection.getClass('BX.SidePanel');
	    if (!main_core.Type.isNil(SidePanel)) {
	      SidePanel.Instance.open('blank-selector', {
	        width: 628,
	        cacheable: false,
	        events: {
	          onClose: () => {
	            this.emit('onCancel');
	          }
	        },
	        contentCallback: () => {
	          return ui_sidepanel_layout.Layout.createContent({
	            extensions: ['sign.blank-selector'],
	            title: main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_SLIDER_TITLE'),
	            content: () => {
	              return this.getLayout();
	            },
	            buttons: ({
	              cancelButton,
	              SaveButton
	            }) => {
	              babelHelpers.classPrivateFieldLooseBase(this, _setSaveButton)[_setSaveButton](new SaveButton({
	                text: main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_SLIDER_SELECT_BLANK_BUTTON_LABEL'),
	                onclick: () => {
	                  this.emit('onSelect', this.getSelectedItem().getData());
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
	function _setCurrentPageNumber2(page) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].set('currentPageNumber', page);
	}
	function _getCurrentPageNumber2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].get('currentPageNumber', 1);
	}
	function _getLoadMoreButton2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].remember('loadMoreButton', () => {
	    return new ui_buttons.Button({
	      text: main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_LOAD_MORE_BUTTON_LABEL'),
	      color: ui_buttons.Button.Color.LIGHT_BORDER,
	      size: ui_buttons.Button.Size.LARGE,
	      onclick: button => {
	        const currentPageNumber = babelHelpers.classPrivateFieldLooseBase(this, _getCurrentPageNumber)[_getCurrentPageNumber]();
	        babelHelpers.classPrivateFieldLooseBase(this, _setCurrentPageNumber)[_setCurrentPageNumber](currentPageNumber + 1);
	        button.setWaiting(true);
	        babelHelpers.classPrivateFieldLooseBase(this, _loadPage)[_loadPage](currentPageNumber).then(data => {
	          button.setWaiting(false);
	          if (!main_core.Type.isArrayFilled(data)) {
	            button.setDisabled(true);
	            button.setText(main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_LOAD_MORE_BUTTON_ALL_LOADED_LABEL'));
	          }
	        });
	      }
	    });
	  });
	}
	function _getLoader2$1() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].remember('loader', () => {
	    return new main_loader.Loader({
	      target: this.getLastBlanksListLayout()
	    });
	  });
	}
	function _resetSelected2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _getLastBlanksItems)[_getLastBlanksItems]().forEach(listItem => {
	    listItem.setSelected(false);
	  });
	}
	function _onLastBlanksListItemClick2(event) {
	  babelHelpers.classPrivateFieldLooseBase(this, _resetSelected)[_resetSelected]();
	  const targetItem = event.getTarget();
	  targetItem.setSelected(true);
	}
	function _onLastBlanksListItemEditClick2(event) {
	  const target = event.getTarget();
	  const documentId = target.getId();
	  const SidePanel = main_core.Reflection.getClass('BX.SidePanel');
	  if (!main_core.Type.isNil(SidePanel)) {
	    SidePanel.Instance.open(`/sign/edit/${documentId}/`, {
	      allowChangeHistory: false
	    });
	  }
	}
	function _onLastBlanksListItemSelect2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _enableSaveButton)[_enableSaveButton]();
	}
	function _setLastBlanksItems2(items) {
	  babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].set('lastBlanksItems', [...items]);
	}
	function _getLastBlanksItems2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].get('lastBlanksItems', []);
	}
	function _cleanLastBlanksListLayout2() {
	  main_core.Dom.clean(this.getLastBlanksListLayout());
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
	function _loadPage2(page = 1) {
	  return this.getBackend().getBlanksList({
	    page,
	    countPerPage: 12
	  }).then(({
	    data
	  }) => {
	    this.hideLoader();
	    const options = babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]();
	    babelHelpers.classPrivateFieldLooseBase(this, _setLastBlanksItems)[_setLastBlanksItems]([...babelHelpers.classPrivateFieldLooseBase(this, _getLastBlanksItems)[_getLastBlanksItems](), ...data.map(blank => {
	      var _options$blanksList, _options$state;
	      return new ListItem({
	        id: blank.ID,
	        title: blank.TITLE,
	        data: {
	          id: blank.ID,
	          title: blank.TITLE
	        },
	        iconClass: 'ui-icon sign-blank-selector-last-blanks-list-item-icon-image',
	        iconBackground: 'blue',
	        events: {
	          onClick: babelHelpers.classPrivateFieldLooseBase(this, _onLastBlanksListItemClick)[_onLastBlanksListItemClick].bind(this),
	          onEditClick: babelHelpers.classPrivateFieldLooseBase(this, _onLastBlanksListItemEditClick)[_onLastBlanksListItemEditClick].bind(this),
	          onSelect: babelHelpers.classPrivateFieldLooseBase(this, _onLastBlanksListItemSelect)[_onLastBlanksListItemSelect].bind(this)
	        },
	        targetContainer: this.getLastBlanksListLayout(),
	        editable: options == null ? void 0 : (_options$blanksList = options.blanksList) == null ? void 0 : _options$blanksList.editable,
	        selected: String(options == null ? void 0 : (_options$state = options.state) == null ? void 0 : _options$state.selectedBlankId) === String(blank.ID)
	      });
	    })]);
	    return data;
	  });
	}
	function _setSaveButton2(button) {
	  babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].set('saveButton', button);
	}
	function _getSaveButton2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].get('saveButton');
	}

	let _$3 = t => t,
	  _t$3;
	var _cache$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _setOptions$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setOptions");
	var _getOptions$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOptions");
	var _getBackend = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBackend");
	var _getBlankSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBlankSelector");
	var _getTagSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTagSelector");
	/**
	 * @namespace BX.Sign
	 */
	class BlankField extends main_core_events.EventEmitter {
	  constructor(_options) {
	    var _options$data, _options$data2;
	    super();
	    Object.defineProperty(this, _getTagSelector, {
	      value: _getTagSelector2
	    });
	    Object.defineProperty(this, _getBlankSelector, {
	      value: _getBlankSelector2
	    });
	    Object.defineProperty(this, _getBackend, {
	      value: _getBackend2
	    });
	    Object.defineProperty(this, _getOptions$1, {
	      value: _getOptions2$1
	    });
	    Object.defineProperty(this, _setOptions$1, {
	      value: _setOptions2$1
	    });
	    Object.defineProperty(this, _cache$4, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    this.setEventNamespace('BX.Sign.BlankSelector.BlankField');
	    this.subscribeFromOptions(_options == null ? void 0 : _options.events);
	    babelHelpers.classPrivateFieldLooseBase(this, _setOptions$1)[_setOptions$1](_options);
	    if (main_core.Type.isDomNode(_options == null ? void 0 : _options.targetContainer)) {
	      this.renderTo(_options.targetContainer);
	    }
	    if (main_core.Type.isStringFilled(_options == null ? void 0 : (_options$data = _options.data) == null ? void 0 : _options$data.blankId) || main_core.Type.isNumber(_options == null ? void 0 : (_options$data2 = _options.data) == null ? void 0 : _options$data2.blankId)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _getBackend)[_getBackend]().getBlankById(_options.data.blankId).then(result => {
	        babelHelpers.classPrivateFieldLooseBase(this, _getTagSelector)[_getTagSelector]().addTag({
	          id: result.data.id,
	          title: result.data.title,
	          entityId: 'blank'
	        });
	      });
	    }
	  }
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache$4)[_cache$4].remember('layout', () => {
	      const layout = main_core.Tag.render(_t$3 || (_t$3 = _$3`
				<div class="sign-blank-selector-field">
				</div>
			`));
	      babelHelpers.classPrivateFieldLooseBase(this, _getTagSelector)[_getTagSelector]().renderTo(layout);
	      return layout;
	    });
	  }
	  renderTo(targetContainer) {
	    if (main_core.Type.isDomNode(targetContainer)) {
	      main_core.Dom.append(this.getLayout(), targetContainer);
	    }
	  }
	}
	function _setOptions2$1(options) {
	  babelHelpers.classPrivateFieldLooseBase(this, _cache$4)[_cache$4].set('options', options);
	}
	function _getOptions2$1() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$4)[_cache$4].get('options', {});
	}
	function _getBackend2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$4)[_cache$4].remember('backend', () => {
	    return new Backend();
	  });
	}
	function _getBlankSelector2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$4)[_cache$4].remember('blankSelector', () => {
	    return new BlankSelector({
	      ...(babelHelpers.classPrivateFieldLooseBase(this, _getOptions$1)[_getOptions$1]().selectorOptions || {}),
	      events: {
	        onSelect: event => {
	          const data = event.getData();
	          babelHelpers.classPrivateFieldLooseBase(this, _getTagSelector)[_getTagSelector]().addTag({
	            id: data.id,
	            title: data.title,
	            entityId: 'blank'
	          });
	          this.emit('onSelect', data);
	        },
	        onCancel: () => {
	          const tagSelector = babelHelpers.classPrivateFieldLooseBase(this, _getTagSelector)[_getTagSelector]();
	          if (tagSelector.getTags().length === 0) {
	            tagSelector.showAddButton();
	          }
	        }
	      }
	    });
	  });
	}
	function _getTagSelector2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$4)[_cache$4].remember('tagSelector', () => {
	    return new ui_entitySelector.TagSelector({
	      id: main_core.Text.getRandom(),
	      multiple: false,
	      showTextBox: false,
	      addButtonCaption: main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_FIELD_ADD_BUTTON_LABEL'),
	      tagMaxWidth: 500,
	      events: {
	        onAddButtonClick: () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _getBlankSelector)[_getBlankSelector]().openSlider();
	          babelHelpers.classPrivateFieldLooseBase(this, _getTagSelector)[_getTagSelector]().hideTextBox();
	        },
	        onAfterTagRemove: () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _getTagSelector)[_getTagSelector]().hideTextBox();
	          babelHelpers.classPrivateFieldLooseBase(this, _getTagSelector)[_getTagSelector]().showAddButton();
	          this.emit('onRemove');
	        }
	      }
	    });
	  });
	}

	exports.BlankSelector = BlankSelector;
	exports.BlankField = BlankField;

}((this.BX.Sign = this.BX.Sign || {}),BX.UI.SidePanel,BX.UI.Uploader,BX.UI,BX,BX,BX,BX.Event,BX.UI.EntitySelector));
//# sourceMappingURL=blank-selector.bundle.js.map
