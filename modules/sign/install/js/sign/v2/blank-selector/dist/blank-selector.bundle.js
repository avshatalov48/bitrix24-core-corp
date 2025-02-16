/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
(function (exports,main_date,main_popup,sign_v2_signSettings,ui_sidepanel_layout,ui_uploader_tileWidget,ui_uploader_core,main_loader,ui_icons,main_core,main_core_events,sign_v2_api,ui_entitySelector) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2;
	var _layout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layout");
	var _props = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("props");
	var _titleNode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("titleNode");
	var _descriptionNode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("descriptionNode");
	var _createListItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createListItem");
	class ListItem {
	  constructor(props) {
	    Object.defineProperty(this, _createListItem, {
	      value: _createListItem2
	    });
	    Object.defineProperty(this, _layout, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _props, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _titleNode, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _descriptionNode, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _titleNode)[_titleNode] = main_core.Tag.render(_t || (_t = _`
			<span class="sign-blank-selector__list_item-title"></span>
		`));
	    babelHelpers.classPrivateFieldLooseBase(this, _descriptionNode)[_descriptionNode] = main_core.Tag.render(_t2 || (_t2 = _`
			<span class="sign-blank-selector__list_item-info"></span>
		`));
	    this.setProps(props);
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout] = babelHelpers.classPrivateFieldLooseBase(this, _createListItem)[_createListItem]();
	  }
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout];
	  }
	  setTitle(title = '') {
	    babelHelpers.classPrivateFieldLooseBase(this, _titleNode)[_titleNode].textContent = title;
	    babelHelpers.classPrivateFieldLooseBase(this, _titleNode)[_titleNode].title = title;
	    this.setProps({
	      ...this.getProps(),
	      title
	    });
	  }
	  setDescription(description = '') {
	    babelHelpers.classPrivateFieldLooseBase(this, _descriptionNode)[_descriptionNode].textContent = description;
	    babelHelpers.classPrivateFieldLooseBase(this, _descriptionNode)[_descriptionNode].title = description;
	    this.setProps({
	      ...this.getProps(),
	      description
	    });
	  }
	  getProps() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _props)[_props];
	  }
	  setProps(props) {
	    babelHelpers.classPrivateFieldLooseBase(this, _props)[_props] = props;
	  }
	}
	function _createListItem2() {
	  const {
	    title,
	    description,
	    modifier
	  } = this.getProps();
	  this.setTitle(title);
	  this.setDescription(description);
	  return main_core.Dom.create('div', {
	    attrs: {
	      className: `sign-blank-selector__list_item --${modifier}`
	    },
	    children: [babelHelpers.classPrivateFieldLooseBase(this, _titleNode)[_titleNode], babelHelpers.classPrivateFieldLooseBase(this, _descriptionNode)[_descriptionNode]]
	  });
	}

	let _$1 = t => t,
	  _t$1,
	  _t2$1,
	  _t3,
	  _t4;
	var _placeholder = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("placeholder");
	var _preview = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("preview");
	var _loader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loader");
	class Blank extends ListItem {
	  constructor(props) {
	    super({
	      ...props,
	      modifier: 'blank'
	    });
	    Object.defineProperty(this, _placeholder, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _preview, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _loader, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _placeholder)[_placeholder] = main_core.Tag.render(_t$1 || (_t$1 = _$1`<div class="sign-blank-selector__list_item-status"></div>`));
	    babelHelpers.classPrivateFieldLooseBase(this, _preview)[_preview] = main_core.Tag.render(_t2$1 || (_t2$1 = _$1`
			<div class="sign-blank-selector__list_item-preview" hidden>
				<img
					onload="${0}"
				/>
			</div>
		`), () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _preview)[_preview].hidden = false;
	      babelHelpers.classPrivateFieldLooseBase(this, _placeholder)[_placeholder].hidden = true;
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader] = new main_loader.Loader({
	      size: 30,
	      target: babelHelpers.classPrivateFieldLooseBase(this, _placeholder)[_placeholder]
	    });
	    const layout = this.getLayout();
	    main_core.Dom.prepend(babelHelpers.classPrivateFieldLooseBase(this, _placeholder)[_placeholder], layout);
	    main_core.Dom.prepend(babelHelpers.classPrivateFieldLooseBase(this, _preview)[_preview], layout);
	  }
	  setAvatarWithDescription(description, userAvatarUrl) {
	    this.setDescription(description);
	    this.setProps({
	      ...this.getProps(),
	      userAvatarUrl
	    });
	    const avatarIcon = userAvatarUrl ? main_core.Tag.render(_t3 || (_t3 = _$1`
				<img class="sign-blank-selector__list_item-info-avatar" src="${0}" />
			`), userAvatarUrl) : main_core.Tag.render(_t4 || (_t4 = _$1`
				<span class="sign-blank-selector__list_item-info-avatar ui-icon ui-icon-common-user">
					<i></i>
				</span>
			`));
	    const {
	      lastElementChild: descriptionNode
	    } = this.getLayout();
	    main_core.Dom.prepend(avatarIcon, descriptionNode);
	  }
	  select() {
	    main_core.Dom.addClass(this.getLayout(), '--active');
	  }
	  deselect() {
	    main_core.Dom.removeClass(this.getLayout(), '--active');
	    this.getLayout().blur();
	  }
	  remove() {
	    main_core.Dom.remove(this.getLayout());
	  }
	  setId(id) {
	    this.getLayout().dataset.id = id;
	  }
	  setReady(isReady) {
	    if (!isReady) {
	      babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader].show();
	      return;
	    }
	    const layout = this.getLayout();
	    layout.tabIndex = '0';
	    babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader].hide();
	    main_core.Dom.addClass(layout, '--loaded');
	  }
	  setPreview(previewUrl) {
	    if (previewUrl) {
	      babelHelpers.classPrivateFieldLooseBase(this, _preview)[_preview].firstElementChild.src = previewUrl;
	    }
	  }
	}

	let _$2 = t => t,
	  _t$2;
	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _setOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setOptions");
	var _getOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOptions");
	var _getApi = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getApi");
	var _getBlankSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBlankSelector");
	var _getTagSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTagSelector");
	var _resetBlankSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resetBlankSelector");
	/**
	 * @namespace BX.Sign.V2
	 */
	class BlankField$$1 extends main_core_events.EventEmitter {
	  constructor(_options) {
	    var _options$data;
	    super();
	    Object.defineProperty(this, _resetBlankSelector, {
	      value: _resetBlankSelector2
	    });
	    Object.defineProperty(this, _getTagSelector, {
	      value: _getTagSelector2
	    });
	    Object.defineProperty(this, _getBlankSelector, {
	      value: _getBlankSelector2
	    });
	    Object.defineProperty(this, _getApi, {
	      value: _getApi2
	    });
	    Object.defineProperty(this, _getOptions, {
	      value: _getOptions2
	    });
	    Object.defineProperty(this, _setOptions, {
	      value: _setOptions2
	    });
	    Object.defineProperty(this, _cache, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    this.setEventNamespace('BX.Sign.BlankSelector.BlankField');
	    this.subscribeFromOptions(_options == null ? void 0 : _options.events);
	    babelHelpers.classPrivateFieldLooseBase(this, _setOptions)[_setOptions](_options);
	    const blankId = _options == null ? void 0 : (_options$data = _options.data) == null ? void 0 : _options$data.blankId;
	    if (main_core.Type.isStringFilled(blankId) || main_core.Type.isNumber(blankId)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _getApi)[_getApi]().getBlankById(_options.data.blankId).then(({
	        id,
	        title
	      }) => {
	        babelHelpers.classPrivateFieldLooseBase(this, _getTagSelector)[_getTagSelector]().addTag({
	          id,
	          title,
	          entityId: 'blank'
	        });
	      });
	    }
	  }
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('layout', () => {
	      const layout = main_core.Tag.render(_t$2 || (_t$2 = _$2`
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
	function _setOptions2(options) {
	  babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].set('options', options);
	}
	function _getOptions2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].get('options', {});
	}
	function _getApi2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('api', () => new sign_v2_api.Api());
	}
	function _getBlankSelector2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('blankSelector', () => {
	    return new BlankSelector({
	      ...babelHelpers.classPrivateFieldLooseBase(this, _getOptions)[_getOptions]().selectorOptions,
	      events: {
	        toggleSelection: event => {
	          const {
	            id,
	            title,
	            selected
	          } = event.getData();
	          const tagSelector = babelHelpers.classPrivateFieldLooseBase(this, _getTagSelector)[_getTagSelector]();
	          if (selected) {
	            tagSelector.addTag({
	              id,
	              title,
	              entityId: 'blank'
	            });
	            tagSelector.showAddButton();
	            this.emit('onSelect', event);
	            return;
	          }
	          if (tagSelector.getTags().length === 0) {
	            tagSelector.showAddButton();
	          }
	          this.emit('onCancel');
	        },
	        onSliderClose: () => {
	          const tagSelector = babelHelpers.classPrivateFieldLooseBase(this, _getTagSelector)[_getTagSelector]();
	          if (tagSelector.getTags().length === 0) {
	            tagSelector.showAddButton();
	          }
	          babelHelpers.classPrivateFieldLooseBase(this, _resetBlankSelector)[_resetBlankSelector]();
	        }
	      }
	    });
	  });
	}
	function _getTagSelector2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('tagSelector', () => {
	    return new ui_entitySelector.TagSelector({
	      id: main_core.Text.getRandom(),
	      multiple: false,
	      showTextBox: false,
	      addButtonCaption: main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_FIELD_ADD_BUTTON_LABEL'),
	      tagMaxWidth: 500,
	      events: {
	        onAddButtonClick: () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _getBlankSelector)[_getBlankSelector]().openInSlider();
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
	function _resetBlankSelector2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].delete('blankSelector');
	}

	let _$3 = t => t,
	  _t$3,
	  _t2$2,
	  _t3$1,
	  _t4$1,
	  _t5,
	  _t6;
	const uploaderOptions = {
	  controller: 'sign.upload.blankUploadController',
	  acceptedFileTypes: ['.jpg', '.jpeg', '.png', '.pdf', '.doc', '.docx', '.rtf', '.odt'],
	  multiple: true,
	  autoUpload: false,
	  maxFileSize: 50 * 1024 * 1024,
	  maxFileCount: 100,
	  imageMaxFileSize: 10 * 1024 * 1024,
	  maxTotalFileSize: 50 * 1024 * 1024
	};
	const errorPopupOptions = {
	  id: 'qwerty',
	  padding: 20,
	  offsetLeft: 40,
	  offsetTop: -12,
	  angle: true,
	  darkMode: true,
	  width: 300,
	  autoHide: true,
	  cacheable: false,
	  bindOptions: {
	    position: 'bottom'
	  }
	};
	var _cache$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _blanks = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("blanks");
	var _tileWidget = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("tileWidget");
	var _tileWidgetContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("tileWidgetContainer");
	var _uploadButtonsContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("uploadButtonsContainer");
	var _relatedTarget = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("relatedTarget");
	var _blanksContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("blanksContainer");
	var _page = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("page");
	var _loadMoreButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadMoreButton");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _config = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("config");
	var _checkForFilesValid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("checkForFilesValid");
	var _onFileBeforeAdd = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFileBeforeAdd");
	var _getImagesLimit = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getImagesLimit");
	var _onFileAdd = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFileAdd");
	var _onFileRemove = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFileRemove");
	var _onUploadStart = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onUploadStart");
	var _toggleTileVisibility = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("toggleTileVisibility");
	var _createUploadButtons = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createUploadButtons");
	var _resumeUploading = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resumeUploading");
	var _loadBlanks = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadBlanks");
	var _setupBlank = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setupBlank");
	var _normalizeTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("normalizeTitle");
	var _addBlank = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addBlank");
	var _setSaveButtonIntoSlider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setSaveButtonIntoSlider");
	var _disableSaveButtonIntoSlider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disableSaveButtonIntoSlider");
	var _enableSaveButtonIntoSlider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("enableSaveButtonIntoSlider");
	var _getSaveButtonIntoSlider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSaveButtonIntoSlider");
	class BlankSelector extends main_core_events.EventEmitter {
	  constructor(config) {
	    var _config$events;
	    super();
	    Object.defineProperty(this, _getSaveButtonIntoSlider, {
	      value: _getSaveButtonIntoSlider2
	    });
	    Object.defineProperty(this, _enableSaveButtonIntoSlider, {
	      value: _enableSaveButtonIntoSlider2
	    });
	    Object.defineProperty(this, _disableSaveButtonIntoSlider, {
	      value: _disableSaveButtonIntoSlider2
	    });
	    Object.defineProperty(this, _setSaveButtonIntoSlider, {
	      value: _setSaveButtonIntoSlider2
	    });
	    Object.defineProperty(this, _addBlank, {
	      value: _addBlank2
	    });
	    Object.defineProperty(this, _normalizeTitle, {
	      value: _normalizeTitle2
	    });
	    Object.defineProperty(this, _setupBlank, {
	      value: _setupBlank2
	    });
	    Object.defineProperty(this, _loadBlanks, {
	      value: _loadBlanks2
	    });
	    Object.defineProperty(this, _resumeUploading, {
	      value: _resumeUploading2
	    });
	    Object.defineProperty(this, _createUploadButtons, {
	      value: _createUploadButtons2
	    });
	    Object.defineProperty(this, _toggleTileVisibility, {
	      value: _toggleTileVisibility2
	    });
	    Object.defineProperty(this, _onUploadStart, {
	      value: _onUploadStart2
	    });
	    Object.defineProperty(this, _onFileRemove, {
	      value: _onFileRemove2
	    });
	    Object.defineProperty(this, _onFileAdd, {
	      value: _onFileAdd2
	    });
	    Object.defineProperty(this, _getImagesLimit, {
	      value: _getImagesLimit2
	    });
	    Object.defineProperty(this, _onFileBeforeAdd, {
	      value: _onFileBeforeAdd2
	    });
	    Object.defineProperty(this, _checkForFilesValid, {
	      value: _checkForFilesValid2
	    });
	    Object.defineProperty(this, _cache$1, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    Object.defineProperty(this, _blanks, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _tileWidget, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _tileWidgetContainer, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _uploadButtonsContainer, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _relatedTarget, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _blanksContainer, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _page, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _loadMoreButton, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _config, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.Sign.V2.BlankSelector');
	    this.subscribeFromOptions((_config$events = config == null ? void 0 : config.events) != null ? _config$events : {});
	    babelHelpers.classPrivateFieldLooseBase(this, _config)[_config] = config;
	    this.selectedBlankId = 0;
	    babelHelpers.classPrivateFieldLooseBase(this, _blanks)[_blanks] = new Map();
	    babelHelpers.classPrivateFieldLooseBase(this, _page)[_page] = 0;
	    const uploadButtons = babelHelpers.classPrivateFieldLooseBase(this, _createUploadButtons)[_createUploadButtons]();
	    const dragArea = main_core.Tag.render(_t$3 || (_t$3 = _$3`
			<label class="sign-blank-selector__list_drag-area-label">
				${0}
			</label>
		`), main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_DRAG_AREA'));
	    const widgetOptions = {
	      slots: {
	        afterDropArea: {
	          computed: {
	            title: () => main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_CLEAR_ALL')
	          },
	          methods: {
	            clear: () => {
	              this.clearFiles({
	                removeFromServer: false
	              });
	            }
	          },
	          template: `
						<span
							class="sign-blank-selector__tile-widget_clear-btn"
							:title="title"
							@click="clear()"
						>
						</span>
					`
	        }
	      }
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _uploadButtonsContainer)[_uploadButtonsContainer] = main_core.Tag.render(_t2$2 || (_t2$2 = _$3`
			<div class="sign-blank-selector__list --with-buttons">
				${0}
				${0}
			</div>
		`), uploadButtons, dragArea);
	    babelHelpers.classPrivateFieldLooseBase(this, _tileWidget)[_tileWidget] = new ui_uploader_tileWidget.TileWidget({
	      ...uploaderOptions,
	      ...config.uploaderOptions,
	      dropElement: babelHelpers.classPrivateFieldLooseBase(this, _uploadButtonsContainer)[_uploadButtonsContainer],
	      browseElement: [...uploadButtons, dragArea],
	      events: {
	        [ui_uploader_core.UploaderEvent.BEFORE_FILES_ADD]: event => babelHelpers.classPrivateFieldLooseBase(this, _onFileBeforeAdd)[_onFileBeforeAdd](event),
	        [ui_uploader_core.UploaderEvent.FILE_ADD]: event => babelHelpers.classPrivateFieldLooseBase(this, _onFileAdd)[_onFileAdd](event),
	        [ui_uploader_core.UploaderEvent.FILE_REMOVE]: event => babelHelpers.classPrivateFieldLooseBase(this, _onFileRemove)[_onFileRemove](event),
	        [ui_uploader_core.UploaderEvent.UPLOAD_START]: event => babelHelpers.classPrivateFieldLooseBase(this, _onUploadStart)[_onUploadStart](event)
	      }
	    }, widgetOptions);
	    babelHelpers.classPrivateFieldLooseBase(this, _relatedTarget)[_relatedTarget] = null;
	    main_core.Event.bind(document, 'mousedown', event => {
	      babelHelpers.classPrivateFieldLooseBase(this, _relatedTarget)[_relatedTarget] = event.target;
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _blanksContainer)[_blanksContainer] = main_core.Tag.render(_t3$1 || (_t3$1 = _$3`
			<div
				class="sign-blank-selector__list"
				onfocusin="${0}"
				onclick="${0}"
			></div>
		`), ({
	      target
	    }) => {
	      this.selectBlank(Number(target.dataset.id));
	    }, ({
	      target,
	      ctrlKey,
	      metaKey
	    }) => {
	      if (ctrlKey || metaKey) {
	        this.resetSelectedBlank(Number(target.dataset.id), babelHelpers.classPrivateFieldLooseBase(this, _relatedTarget)[_relatedTarget]);
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _tileWidgetContainer)[_tileWidgetContainer] = main_core.Tag.render(_t4$1 || (_t4$1 = _$3`
			<div class="sign-blank-selector__tile-widget"></div>
		`));
	    babelHelpers.classPrivateFieldLooseBase(this, _loadMoreButton)[_loadMoreButton] = main_core.Tag.render(_t5 || (_t5 = _$3`
			<div class="sign-blank-selector__load-more --hidden">
				<span onclick="${0}">
					${0}
				</span>
			</div>
		`), () => babelHelpers.classPrivateFieldLooseBase(this, _loadBlanks)[_loadBlanks](babelHelpers.classPrivateFieldLooseBase(this, _page)[_page] + 1), main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_LOAD_MORE'));
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	  }
	  async createBlankFromOuterUploaderFiles(files) {
	    if (files.length === 0) {
	      return;
	    }
	    const firstFile = files.at(0);
	    const blank = new Blank({
	      title: firstFile.getName()
	    });
	    blank.setReady(false);
	    main_core.Dom.prepend(blank.getLayout(), babelHelpers.classPrivateFieldLooseBase(this, _blanksContainer)[_blanksContainer]);
	    try {
	      var _babelHelpers$classPr;
	      const filesIds = files.map(file => file.getServerFileId());
	      const blankData = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].createBlank(filesIds, (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].type) != null ? _babelHelpers$classPr : null, sign_v2_signSettings.isTemplateMode(babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].documentMode));
	      babelHelpers.classPrivateFieldLooseBase(this, _setupBlank)[_setupBlank]({
	        ...blankData,
	        userName: main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_CREATED_MYSELF')
	      }, blank);
	      return blankData.id;
	    } catch (ex) {
	      blank == null ? void 0 : blank.remove == null ? void 0 : blank.remove();
	      console.log(ex);
	      throw ex;
	    }
	  }
	  async createBlank() {
	    const uploader = babelHelpers.classPrivateFieldLooseBase(this, _tileWidget)[_tileWidget].getUploader();
	    const files = uploader.getFiles();
	    if (files.length === 0) {
	      return;
	    }
	    const [firstFile] = files;
	    await babelHelpers.classPrivateFieldLooseBase(this, _resumeUploading)[_resumeUploading]();
	    const blank = firstFile.getCustomData(firstFile.getId());
	    try {
	      var _babelHelpers$classPr2;
	      const filesIds = files.map(file => file.getServerFileId());
	      const blankData = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].createBlank(filesIds, (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].type) != null ? _babelHelpers$classPr2 : null, sign_v2_signSettings.isTemplateMode(babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].documentMode));
	      babelHelpers.classPrivateFieldLooseBase(this, _setupBlank)[_setupBlank]({
	        ...blankData,
	        userName: main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_CREATED_MYSELF')
	      }, blank);
	      return blankData.id;
	    } catch (ex) {
	      blank.remove();
	      throw ex;
	    }
	  }
	  resetSelectedBlank() {
	    const blank = babelHelpers.classPrivateFieldLooseBase(this, _blanks)[_blanks].get(this.selectedBlankId);
	    blank == null ? void 0 : blank.deselect();
	    this.selectedBlankId = 0;
	    if (blank) {
	      this.emit('toggleSelection', {
	        selected: false
	      });
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _enableSaveButtonIntoSlider)[_enableSaveButtonIntoSlider]();
	  }
	  async modifyBlankTitle(blankId, blankTitle) {
	    let blank = babelHelpers.classPrivateFieldLooseBase(this, _blanks)[_blanks].get(blankId);
	    if (!blank) {
	      await this.loadBlankById(blankId);
	      blank = babelHelpers.classPrivateFieldLooseBase(this, _blanks)[_blanks].get(blankId);
	    }
	    blank.setTitle(blankTitle);
	  }
	  hasBlank(blankId) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _blanks)[_blanks].has(blankId);
	  }
	  getBlank(blankId) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _blanks)[_blanks].get(blankId);
	  }
	  async loadBlankById(blankId) {
	    const blankData = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].getBlankById(blankId);
	    if (!this.hasBlank(blankId)) {
	      const blank = new Blank({
	        title: blankData.title
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _addBlank)[_addBlank](blankData, blank);
	    }
	  }
	  async selectBlank(blankId) {
	    if (blankId !== this.selectedBlankId) {
	      this.resetSelectedBlank();
	    }
	    this.selectedBlankId = blankId;
	    babelHelpers.classPrivateFieldLooseBase(this, _toggleTileVisibility)[_toggleTileVisibility](false);
	    let blank = babelHelpers.classPrivateFieldLooseBase(this, _blanks)[_blanks].get(blankId);
	    if (!blank) {
	      await this.loadBlankById(blankId);
	      blank = babelHelpers.classPrivateFieldLooseBase(this, _blanks)[_blanks].get(blankId);
	    }
	    const {
	      title
	    } = blank.getProps();
	    blank.select();
	    this.emit('toggleSelection', {
	      id: blankId,
	      selected: true,
	      title: babelHelpers.classPrivateFieldLooseBase(this, _normalizeTitle)[_normalizeTitle](title)
	    });
	  }
	  deleteBlank(blankId) {
	    const lastBlank = babelHelpers.classPrivateFieldLooseBase(this, _blanks)[_blanks].get(blankId);
	    if (lastBlank) {
	      babelHelpers.classPrivateFieldLooseBase(this, _blanks)[_blanks].delete(blankId);
	      lastBlank.remove();
	    }
	  }
	  clearFiles(options) {
	    const uploader = babelHelpers.classPrivateFieldLooseBase(this, _tileWidget)[_tileWidget].getUploader();
	    uploader.removeFiles(options);
	  }
	  isFilesReadyForUpload() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _tileWidget)[_tileWidget].getUploader().getFiles().length === 0) {
	      return false;
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _tileWidget)[_tileWidget].getUploader().getFiles().every(file => file.getErrors().length <= 0);
	  }
	  getLayout() {
	    var _babelHelpers$classPr3;
	    babelHelpers.classPrivateFieldLooseBase(this, _tileWidget)[_tileWidget].renderTo(babelHelpers.classPrivateFieldLooseBase(this, _tileWidgetContainer)[_tileWidgetContainer]);
	    babelHelpers.classPrivateFieldLooseBase(this, _toggleTileVisibility)[_toggleTileVisibility](false);
	    const canUploadNewBlank = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].canUploadNewBlank) != null ? _babelHelpers$classPr3 : true;
	    const selectorContainer = main_core.Tag.render(_t6 || (_t6 = _$3`
			<div class="sign-blank-selector">
				${0}
				${0}
				<p class="sign-blank-selector__templates_title">
					${0}
				</p>
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _tileWidgetContainer)[_tileWidgetContainer], canUploadNewBlank ? babelHelpers.classPrivateFieldLooseBase(this, _uploadButtonsContainer)[_uploadButtonsContainer] : '', main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_RECENT_TEMPLATES_TITLE'), babelHelpers.classPrivateFieldLooseBase(this, _blanksContainer)[_blanksContainer], babelHelpers.classPrivateFieldLooseBase(this, _loadMoreButton)[_loadMoreButton]);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _page)[_page] === 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _loadBlanks)[_loadBlanks](1);
	    }
	    return selectorContainer;
	  }
	  openInSlider() {
	    const SidePanel = main_core.Reflection.getClass('BX.SidePanel');
	    if (!main_core.Type.isNil(SidePanel)) {
	      SidePanel.Instance.open('v2-blank-selector', {
	        width: 628,
	        cacheable: false,
	        events: {
	          onClose: () => {
	            this.emit('onSliderClose');
	          }
	        },
	        contentCallback: () => {
	          return ui_sidepanel_layout.Layout.createContent({
	            extensions: ['sign.v2.blank-selector'],
	            title: main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_SLIDER_TITLE'),
	            content: () => this.getLayout(),
	            buttons: ({
	              cancelButton,
	              SaveButton
	            }) => {
	              babelHelpers.classPrivateFieldLooseBase(this, _setSaveButtonIntoSlider)[_setSaveButtonIntoSlider](new SaveButton({
	                text: main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_SLIDER_SELECT_BLANK_BUTTON_LABEL'),
	                onclick: () => {
	                  SidePanel.Instance.close();
	                }
	              }));
	              babelHelpers.classPrivateFieldLooseBase(this, _disableSaveButtonIntoSlider)[_disableSaveButtonIntoSlider]();
	              return [babelHelpers.classPrivateFieldLooseBase(this, _getSaveButtonIntoSlider)[_getSaveButtonIntoSlider](), cancelButton];
	            }
	          });
	        }
	      });
	    }
	  }
	  disableSelectedBlank(blankId) {
	    const blank = babelHelpers.classPrivateFieldLooseBase(this, _blanks)[_blanks].get(blankId);
	    if (blank) {
	      main_core.Dom.addClass(blank.getLayout(), '--disabled');
	    }
	  }
	  enableSelectedBlank(blankId) {
	    const blank = babelHelpers.classPrivateFieldLooseBase(this, _blanks)[_blanks].get(blankId);
	    if (blank) {
	      main_core.Dom.removeClass(blank.getLayout(), '--disabled');
	    }
	  }
	}
	function _checkForFilesValid2(addedFiles) {
	  const isImage = file => file.getType().includes('image/');
	  const allAddedImages = addedFiles.every(file => isImage(file));
	  const validExtension = addedFiles.every(file => {
	    // TODO merge with this.#config.uploaderOptions.acceptedFileTypes
	    return uploaderOptions.acceptedFileTypes.includes(`.${file.getExtension()}`);
	  });
	  if (!validExtension || addedFiles.length > 1 && !allAddedImages) {
	    return false;
	  }
	  const uploader = babelHelpers.classPrivateFieldLooseBase(this, _tileWidget)[_tileWidget].getUploader();
	  const files = uploader.getFiles();
	  const filesLength = files.length;
	  const imagesLimit = babelHelpers.classPrivateFieldLooseBase(this, _getImagesLimit)[_getImagesLimit]();
	  if (filesLength === 0 && addedFiles.length === 1) {
	    return true;
	  }
	  const allExistImages = files.every(file => isImage(file));
	  return allAddedImages && allExistImages && imagesLimit - filesLength >= addedFiles.length;
	}
	function _onFileBeforeAdd2(event) {
	  const {
	    files: addedFiles
	  } = event.getData();
	  const valid = babelHelpers.classPrivateFieldLooseBase(this, _checkForFilesValid)[_checkForFilesValid](addedFiles);
	  if (valid) {
	    return;
	  }
	  let bindElement = babelHelpers.classPrivateFieldLooseBase(this, _uploadButtonsContainer)[_uploadButtonsContainer].firstElementChild;
	  if (main_core.Dom.hasClass(babelHelpers.classPrivateFieldLooseBase(this, _uploadButtonsContainer)[_uploadButtonsContainer], '--hidden')) {
	    const {
	      $refs: {
	        container
	      }
	    } = babelHelpers.classPrivateFieldLooseBase(this, _tileWidget)[_tileWidget].getRootComponent();
	    bindElement = container.firstElementChild;
	  }
	  const errorPopup = new main_popup.Popup({
	    ...errorPopupOptions,
	    bindElement,
	    content: main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_UPLOAD_HINT', {
	      '%imageCountLimit%': babelHelpers.classPrivateFieldLooseBase(this, _getImagesLimit)[_getImagesLimit]()
	    })
	  });
	  errorPopup.show();
	  event.preventDefault();
	}
	function _getImagesLimit2() {
	  var _babelHelpers$classPr4, _babelHelpers$classPr5, _babelHelpers$classPr6, _babelHelpers$classPr7;
	  return main_core.Type.isInteger(parseInt((_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _config)[_config]) == null ? void 0 : (_babelHelpers$classPr5 = _babelHelpers$classPr4.uploaderOptions) == null ? void 0 : _babelHelpers$classPr5.maxFileCount, 10)) ? (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _config)[_config]) == null ? void 0 : (_babelHelpers$classPr7 = _babelHelpers$classPr6.uploaderOptions) == null ? void 0 : _babelHelpers$classPr7.maxFileCount : uploaderOptions.maxFileCount;
	}
	function _onFileAdd2(event) {
	  const title = event.data.file.getName();
	  babelHelpers.classPrivateFieldLooseBase(this, _toggleTileVisibility)[_toggleTileVisibility](true);
	  this.resetSelectedBlank();
	  this.emit('addFile', {
	    title: babelHelpers.classPrivateFieldLooseBase(this, _normalizeTitle)[_normalizeTitle](title)
	  });
	}
	function _onFileRemove2(event) {
	  this.emit('removeFile');
	  const uploader = babelHelpers.classPrivateFieldLooseBase(this, _tileWidget)[_tileWidget].getUploader();
	  const files = uploader.getFiles();
	  if (files.length === 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _toggleTileVisibility)[_toggleTileVisibility](false);
	    this.emit('clearFiles');
	  }
	}
	function _onUploadStart2() {
	  const uploader = babelHelpers.classPrivateFieldLooseBase(this, _tileWidget)[_tileWidget].getUploader();
	  const [firstFile] = uploader.getFiles();
	  const title = firstFile.getName();
	  const fileId = firstFile.getId();
	  const uploadingBlank = new Blank({
	    title
	  });
	  uploadingBlank.setReady(false);
	  main_core.Dom.prepend(uploadingBlank.getLayout(), babelHelpers.classPrivateFieldLooseBase(this, _blanksContainer)[_blanksContainer]);
	  firstFile.setCustomData(fileId, uploadingBlank);
	}
	function _toggleTileVisibility2(shouldShow) {
	  const hiddenClass = '--hidden';
	  if (shouldShow) {
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _tileWidgetContainer)[_tileWidgetContainer], hiddenClass);
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _uploadButtonsContainer)[_uploadButtonsContainer], hiddenClass);
	    return;
	  }
	  main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _tileWidgetContainer)[_tileWidgetContainer], hiddenClass);
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _uploadButtonsContainer)[_uploadButtonsContainer], hiddenClass);
	  this.clearFiles({
	    removeFromServer: false
	  });
	}
	function _createUploadButtons2() {
	  const buttons = {
	    img: {
	      title: main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_CREATE_NEW_PIC'),
	      description: 'jpeg, png'
	    },
	    pdf: {
	      title: main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_NEW_PDF'),
	      description: 'Adobe Acrobat'
	    },
	    doc: {
	      title: main_core.Loc.getMessage('SIGN_BLANK_SELECTOR_NEW_DOC'),
	      description: 'doc, docx'
	    }
	  };
	  const entries = Object.entries(buttons);
	  return entries.map(([key, {
	    title,
	    description
	  }]) => {
	    const listItem = new ListItem({
	      title,
	      description,
	      modifier: key
	    });
	    return listItem.getLayout();
	  });
	}
	async function _resumeUploading2() {
	  const uploader = babelHelpers.classPrivateFieldLooseBase(this, _tileWidget)[_tileWidget].getUploader();
	  const pendingFiles = uploader.getFiles();
	  uploader.setMaxParallelUploads(pendingFiles.length);
	  const uploadPromise = new Promise(resolve => {
	    uploader.subscribeOnce('onUploadComplete', resolve);
	  });
	  uploader.start();
	  await uploadPromise;
	}
	async function _loadBlanks2(page) {
	  const loader = new main_loader.Loader({
	    target: babelHelpers.classPrivateFieldLooseBase(this, _blanksContainer)[_blanksContainer],
	    size: 80,
	    mode: 'custom'
	  });
	  loader.show();
	  try {
	    var _babelHelpers$classPr8;
	    const blanksOnPage = 3;
	    const data = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].loadBlanks(page, (_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].type) != null ? _babelHelpers$classPr8 : null, blanksOnPage);
	    if (data.length < blanksOnPage) {
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _loadMoreButton)[_loadMoreButton], '--hidden');
	    } else {
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _loadMoreButton)[_loadMoreButton], '--hidden');
	    }
	    if (data.length > 0) {
	      data.forEach(blankData => {
	        if (this.hasBlank(blankData.id)) {
	          return;
	        }
	        const {
	          title
	        } = blankData;
	        const blank = new Blank({
	          title
	        });
	        babelHelpers.classPrivateFieldLooseBase(this, _addBlank)[_addBlank](blankData, blank);
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _page)[_page] = page;
	    }
	  } catch {
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _loadMoreButton)[_loadMoreButton], '--hidden');
	  }
	  loader.destroy();
	}
	function _setupBlank2(blankData, blank) {
	  const {
	    id: blankId,
	    previewUrl,
	    userAvatarUrl,
	    userName,
	    dateCreate
	  } = blankData;
	  const creationDate = dateCreate ? new Date(dateCreate) : new Date();
	  const descriptionText = `${userName}, ${main_date.DateTimeFormat.format('j M. Y', creationDate)}`;
	  blank.setId(blankId);
	  blank.setReady(true);
	  blank.setPreview(previewUrl);
	  blank.setAvatarWithDescription(descriptionText, userAvatarUrl);
	  babelHelpers.classPrivateFieldLooseBase(this, _blanks)[_blanks].set(blankId, blank);
	}
	function _normalizeTitle2(title) {
	  const acceptedType = uploaderOptions.acceptedFileTypes.find(fileType => {
	    return title.endsWith(fileType);
	  });
	  if (!acceptedType) {
	    return title;
	  }
	  const dotExtensionIndex = title.lastIndexOf(acceptedType);
	  return title.slice(0, dotExtensionIndex);
	}
	function _addBlank2(blankData, blank) {
	  babelHelpers.classPrivateFieldLooseBase(this, _setupBlank)[_setupBlank](blankData, blank);
	  main_core.Dom.append(blank.getLayout(), babelHelpers.classPrivateFieldLooseBase(this, _blanksContainer)[_blanksContainer]);
	}
	function _setSaveButtonIntoSlider2(button) {
	  babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].set('saveButton', button);
	}
	function _disableSaveButtonIntoSlider2() {
	  const saveButton = babelHelpers.classPrivateFieldLooseBase(this, _getSaveButtonIntoSlider)[_getSaveButtonIntoSlider]();
	  saveButton == null ? void 0 : saveButton.setDisabled(true);
	}
	function _enableSaveButtonIntoSlider2() {
	  const saveButton = babelHelpers.classPrivateFieldLooseBase(this, _getSaveButtonIntoSlider)[_getSaveButtonIntoSlider]();
	  saveButton == null ? void 0 : saveButton.setDisabled(false);
	}
	function _getSaveButtonIntoSlider2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].get('saveButton');
	}

	exports.BlankField = BlankField$$1;
	exports.ListItem = ListItem;
	exports.BlankSelector = BlankSelector;

}((this.BX.Sign.V2 = this.BX.Sign.V2 || {}),BX.Main,BX.Main,BX.Sign.V2,BX.UI.SidePanel,BX.UI.Uploader,BX.UI.Uploader,BX,BX,BX,BX.Event,BX.Sign.V2,BX.UI.EntitySelector));
//# sourceMappingURL=blank-selector.bundle.js.map
