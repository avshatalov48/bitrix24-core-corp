this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,main_core_events,main_popup,main_loader,ui_buttons,crm_field_listEditor) {
	'use strict';

	let _ = t => t,
	    _t,
	    _t2,
	    _t3,
	    _t4,
	    _t5,
	    _t6,
	    _t7,
	    _t8;

	/**
	 * @namespace BX.Crm.Requisite
	 */
	class FieldsetViewer extends main_core_events.EventEmitter {
	  constructor(options = {}) {
	    super();
	    this.cache = new main_core.Cache.MemoryCache();
	    this.setEventNamespace('BX.Crm.Requisite.FieldsetViewer');
	    this.subscribeFromOptions((options == null ? void 0 : options.events) || {});
	    this.setOptions(options);
	    main_core.Event.bind(options.bindElement, 'click', this.onBindElementClick.bind(this));
	  }

	  setData(data) {
	    this.cache.set('data', data);
	  }

	  getData() {
	    return this.cache.get('data', {});
	  }

	  load() {
	    return new Promise(resolve => {
	      const {
	        entityTypeId,
	        entityId,
	        fieldListEditorOptions
	      } = this.getOptions();
	      const presetId = fieldListEditorOptions ? fieldListEditorOptions.fieldsPanelOptions ? fieldListEditorOptions.fieldsPanelOptions.presetId : null : null;
	      BX.ajax.runAction('crm.api.fieldset.load', {
	        json: {
	          entityTypeId,
	          entityId,
	          presetId
	        }
	      }).then(result => {
	        resolve(result.data);
	      });
	    });
	  }

	  setOptions(options) {
	    this.cache.set('options', { ...options
	    });
	  }

	  getOptions() {
	    return this.cache.get('options');
	  }

	  getPopup() {
	    return this.cache.remember('popup', () => {
	      const options = this.getOptions();
	      return new main_popup.Popup({
	        bindElement: options.bindElement,
	        autoHide: false,
	        width: 570,
	        height: 478,
	        className: 'crm-requisite-fieldset-viewer',
	        noAllPaddings: true,
	        ...(main_core.Type.isPlainObject(options == null ? void 0 : options.popupOptions) ? options == null ? void 0 : options.popupOptions : {}),
	        events: {
	          onClose: () => {
	            this.emit('onClose', {
	              changed: this.getIsChanged()
	            });
	            this.setIsChanged(false);
	          }
	        }
	      });
	    });
	  }

	  setIsChanged(value) {
	    this.cache.set('isChanged', main_core.Text.toBoolean(value));
	  }

	  getIsChanged() {
	    return this.cache.get('isChanged', false);
	  }

	  getLoader() {
	    return this.cache.remember('loader', () => {
	      return new main_loader.Loader();
	    });
	  }

	  show() {
	    const popup = this.getPopup();
	    main_core.Dom.clean(popup.getContentContainer());
	    void this.getLoader().show(popup.getContentContainer());
	    this.load().then(result => {
	      this.setData({ ...result
	      });
	      popup.setContent(this.createPopupContent(result));
	    });
	    popup.show();
	  }

	  hide() {
	    this.getPopup().close();
	  }

	  onBindElementClick(event) {
	    event.preventDefault();
	    this.show();
	  }

	  createPopupContent(data) {
	    return main_core.Tag.render(_t || (_t = _`
			<div class="crm-requisite-fieldset-viewer-content">
				${0}
				${0}
				${0}
				${0}
			</div>
		`), this.createBannerLayout(data), this.createListLayout(data), this.getFooterLayout(), this.createCloseButton());
	  }

	  createBannerLayout(data) {
	    const title = main_core.Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER_BANNER_TITLE').replace('{{requisite}}', ` <strong>${main_core.Text.encode(data == null ? void 0 : data.title)}</strong>`);

	    const description = (() => {
	      let text = main_core.Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER_BANNER_DESCRIPTION');

	      if (main_core.Type.isStringFilled(data == null ? void 0 : data.more)) {
	        text += ` <a class="ui-link" href="${main_core.Text.encode(data == null ? void 0 : data.more)}">
						${main_core.Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER_BANNER_MORE_LINK_LABEL')}
					</a>`;
	      }

	      return text;
	    })();

	    return main_core.Tag.render(_t2 || (_t2 = _`
			<div class="crm-requisite-fieldset-viewer-banner">
				<div class="crm-requisite-fieldset-viewer-banner-text">
					<div class="crm-requisite-fieldset-viewer-banner-text-title">
						${0}
					</div>
					<div class="crm-requisite-fieldset-viewer-banner-text-description">
						${0}
					</div>
				</div>
			</div>
		`), title, description);
	  }

	  createListLayout(data) {
	    return main_core.Tag.render(_t3 || (_t3 = _`
			<div class="crm-requisite-fieldset-viewer-list">
				${0}
			</div>
		`), this.createListContainer(data.fields));
	  }

	  createListContainer(fields) {
	    return main_core.Tag.render(_t4 || (_t4 = _`
			<div class="crm-requisite-fieldset-viewer-list-container">
				${0}
			</div>
		`), fields.map(options => {
	      return this.createListItem(options);
	    }));
	  }

	  createListItem(options) {
	    const editButton = (() => {
	      var _options$editing;

	      if (main_core.Type.isStringFilled(options == null ? void 0 : (_options$editing = options.editing) == null ? void 0 : _options$editing.url)) {
	        const onEditButtonClick = () => {
	          var _options$editing2;

	          BX.SidePanel.Instance.open(options == null ? void 0 : (_options$editing2 = options.editing) == null ? void 0 : _options$editing2.url, {
	            cacheable: false,
	            events: {
	              onClose: () => {
	                this.show();
	              }
	            }
	          });
	          this.setIsChanged(true);
	        };

	        return main_core.Tag.render(_t5 || (_t5 = _`
					<span 
						class="ui-btn ui-btn-link" 
						onclick="${0}">
							${0}
					</span>
				`), onEditButtonClick, main_core.Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER_LIST_ITEM_VALUE_LINK_LABEL'));
	      }

	      return '';
	    })();

	    return main_core.Tag.render(_t6 || (_t6 = _`
			<div class="crm-requisite-fieldset-viewer-list-item">
				<div class="crm-requisite-fieldset-viewer-list-item-left">
					<div class="crm-requisite-fieldset-viewer-list-item-label">${0}</div>
					<div class="crm-requisite-fieldset-viewer-list-item-value">${0}</div>
				</div>
				<div class="crm-requisite-fieldset-viewer-list-item-right">
					${0}
				</div>
			</div>
		`), main_core.Text.encode(options == null ? void 0 : options.label), main_core.Text.encode(options == null ? void 0 : options.value), editButton);
	  }

	  createCloseButton() {
	    return this.cache.remember('closeButton', () => {
	      const onCloseClick = () => {
	        this.hide();
	      };

	      return main_core.Tag.render(_t7 || (_t7 = _`
				<div 
					class="crm-requisite-fieldset-viewer-close-button"
					onclick="${0}"
				></div>
			`), onCloseClick);
	    });
	  }

	  getFieldListEditor() {
	    return this.cache.remember('fieldListEditor', () => {
	      const options = this.getOptions();
	      return new crm_field_listEditor.ListEditor({
	        setId: this.getData().id,
	        title: main_core.Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER__SET_EDITOR_TITLE'),
	        editable: {
	          label: {
	            label: main_core.Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER__SET_EDITOR_NAME_LABEL'),
	            type: 'string'
	          },
	          required: {
	            label: main_core.Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER__SET_EDITOR_REQUIRED_LABEL'),
	            type: 'checkbox'
	          }
	        },
	        autoSave: false,
	        cacheable: false,
	        events: {
	          onSave: () => this.show()
	        },
	        fieldsPanelOptions: {
	          hideVirtual: 1,
	          ...(main_core.Type.isPlainObject(options.fieldsPanelOptions) ? options.fieldsPanelOptions : {})
	        },
	        ...(main_core.Type.isPlainObject(options.fieldListEditorOptions) ? options.fieldListEditorOptions : {})
	      });
	    });
	  }

	  getEditButton() {
	    return this.cache.remember('editButton', () => {
	      return new ui_buttons.Button({
	        text: main_core.Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER_EDIT_BUTTON_LABEL'),
	        color: ui_buttons.Button.Color.LIGHT_BORDER,
	        icon: ui_buttons.Button.Icon.EDIT,
	        size: ui_buttons.Button.Size.SMALL,
	        round: true,
	        events: {
	          click: this.onEditButtonClick.bind(this)
	        }
	      });
	    });
	  }

	  onEditButtonClick() {
	    this.getFieldListEditor().showSlider();
	    this.setIsChanged(true);
	  }

	  getFooterLayout() {
	    return this.cache.remember('footerLayout', () => {
	      return main_core.Tag.render(_t8 || (_t8 = _`
				<div class="crm-requisite-fieldset-viewer-footer">
					${0}
				</div>
			`), this.getEditButton().render());
	    });
	  }

	}

	exports.FieldsetViewer = FieldsetViewer;

}((this.BX.Crm.Requisite = this.BX.Crm.Requisite || {}),BX,BX.Event,BX.Main,BX,BX.UI,BX.Crm.Field));
//# sourceMappingURL=fieldset-viewer.bundle.js.map
