this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,ui_sidepanel_layout) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9, _templateObject10, _templateObject11, _templateObject12, _templateObject13, _templateObject14, _templateObject15, _templateObject16;

	function _classStaticPrivateMethodGet(receiver, classConstructor, method) { _classCheckPrivateStaticAccess(receiver, classConstructor); return method; }

	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	var DEFAULT_WIDGETS_COUNT = 10;
	var ERROR_CODE_FORM_READ_ACCESS_DENIED = 1;
	var ERROR_CODE_WIDGET_WRITE_ACCESS_DENIED = 4;
	var ERROR_CODE_OPENLINES_WRITE_ACCESS_DENIED = 6;
	var Embed = /*#__PURE__*/function () {
	  function Embed() {
	    babelHelpers.classCallCheck(this, Embed);
	  }

	  babelHelpers.createClass(Embed, null, [{
	    key: "open",

	    /**
	     * @public
	     * @access public
	     */
	    value: function open(formId) {
	      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {
	        widgetsCount: DEFAULT_WIDGETS_COUNT,
	        activeMenuItemId: 'inline'
	      };
	      BX.SidePanel.Instance.open("crm.webform:embed", {
	        width: 900,
	        cacheable: false,
	        events: {
	          onCloseComplete: function onCloseComplete(event) {
	            // const slider = event.getSlider();
	            if (main_core.Type.isFunction(options.onCloseComplete)) {
	              options.onCloseComplete();
	            }
	          } // onLoad: (event) => {
	          // 	BX.UI.Switcher.initByClassName();
	          // }

	        },
	        contentCallback: function contentCallback() {
	          var menuItems = [{
	            label: main_core.Loc.getMessage('EMBED_SLIDER_INLINE_HEADING1'),
	            id: 'inline'
	          }, {
	            label: main_core.Loc.getMessage('EMBED_SLIDER_CLICK_HEADING1'),
	            id: 'click'
	          }, {
	            label: main_core.Loc.getMessage('EMBED_SLIDER_AUTO_HEADING1'),
	            id: 'auto'
	          }, {
	            label: main_core.Loc.getMessage('EMBED_SLIDER_WIDGET_HEADER'),
	            id: 'widgets'
	          }, {
	            label: main_core.Loc.getMessage('EMBED_SLIDER_OPENLINES_HEADER'),
	            id: 'openlines'
	          }, {
	            label: main_core.Loc.getMessage('EMBED_SLIDER_PUBLIC_LINK'),
	            id: 'link'
	          }];
	          menuItems.forEach(function (item) {
	            return item.active = item.id === options.activeMenuItemId;
	          });
	          return ui_sidepanel_layout.Layout.createContent({
	            extensions: ['crm.form.embed', 'ui.forms', 'ui.sidepanel-content', 'clipboard', 'ui.switcher', 'ui.notification'],
	            title: BX.Loc.getMessage('EMBED_SLIDER_TITLE'),
	            design: {
	              section: false
	            },
	            menu: {
	              items: menuItems
	            },
	            content: function content() {
	              return BX.ajax.runAction('crm.form.getEmbed', {
	                json: {
	                  formId: formId
	                }
	              }).then(function (response) {
	                return {
	                  embedData: response.data
	                };
	              }).then(function (responseEmbed) {
	                return BX.ajax.runAction('crm.form.getWidgetsForEmbed', {
	                  json: {
	                    formId: formId,
	                    count: options.widgetsCount
	                  }
	                }).then(function (response) {
	                  responseEmbed.widgetsData = response.data;
	                  return responseEmbed;
	                }).catch(function (response) {
	                  if (main_core.Type.isObject(response.data) && main_core.Type.isObject(response.data.error) && response.data.error.status === 'access denied') {
	                    responseEmbed.widgetsData = null;
	                    return responseEmbed;
	                  }

	                  throw new Error(BX.Loc.getMessage('EMBED_SLIDER_GENERAL_ERROR'));
	                });
	              }).then(function (responseWidgets) {
	                return BX.ajax.runAction('crm.form.getOpenlinesForEmbed', {
	                  json: {
	                    formId: formId,
	                    count: options.widgetsCount
	                  }
	                }).then(function (response) {
	                  responseWidgets.openlinesData = response.data;
	                  return responseWidgets;
	                }).catch(function (response) {
	                  if (main_core.Type.isObject(response.data) && main_core.Type.isObject(response.data.error) && response.data.error.status === 'access denied') {
	                    responseWidgets.openlinesData = null;
	                    return responseWidgets;
	                  }

	                  throw new Error(BX.Loc.getMessage('EMBED_SLIDER_GENERAL_ERROR'));
	                });
	              }).then(function (result) {
	                return _classStaticPrivateMethodGet(Embed, Embed, _renderSliderContent).call(Embed, formId, result.embedData, result.widgetsData, result.openlinesData);
	              }).catch(function (response) {
	                var errorMessage = BX.Loc.getMessage('EMBED_SLIDER_GENERAL_ERROR');

	                if (main_core.Type.isObject(response.data) && main_core.Type.isObject(response.data.error) && response.data.error.status === 'access denied') {
	                  if (response.data.error.code === ERROR_CODE_FORM_READ_ACCESS_DENIED) {
	                    errorMessage = BX.Loc.getMessage('EMBED_SLIDER_FORM_ACCESS_DENIED');
	                  }
	                }

	                return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t\t<div class=\"ui-alert ui-alert-warning\">\n\t\t\t\t\t\t\t\t\t<span class=\"ui-alert-message\">", "</span>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t"])), errorMessage);
	              });
	            },
	            buttons: function buttons(_ref) {
	              var closeButton = _ref.closeButton;
	              return [closeButton];
	            }
	          });
	        }
	      });
	    }
	    /**
	     * @this Switcher
	     * @param formId number
	     * @param widgetId number
	     * @package
	     */

	  }]);
	  return Embed;
	}();

	function _handleToggledWidgetSwitcher(formId, widgetId) {
	  var _this = this;

	  this.setLoading(true); // save old widget values for rollback on error

	  var dataSetOld = this.getNode().closest('.crm-form-embed-widgets-block').querySelector('.crm-form-embed-widgets-detail').dataset;
	  var formNameOld = dataSetOld.formName;
	  var formTypeOld = dataSetOld.formType; // get values for current form

	  var dataSetCurrent = this.getNode().closest('.crm-form-embed-widgets').dataset;
	  var formName = dataSetCurrent.formName;
	  var formType = dataSetCurrent.formType; // set related forms field to new values

	  _classStaticPrivateMethodGet(Embed, Embed, _updateRelatedForms).call(Embed, this.isChecked(), formName, formType, this);

	  return BX.ajax.runAction('crm.form.assignWidgetToForm', {
	    json: {
	      formId: formId,
	      buttonId: widgetId,
	      assigned: this.isChecked() ? 'Y' : 'N'
	    }
	  }).then(function (response) {
	    _this.setLoading(false); // set to returned values (must match current)


	    _this.check(response.data.assigned, false);

	    _classStaticPrivateMethodGet(Embed, Embed, _updateRelatedForms).call(Embed, _this.isChecked(), response.data.formName, response.data.formType, _this);

	    top.BX.UI.Notification.Center.notify({
	      content: BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_FORM_ALERT_SETTINGS_SAVED')
	    });
	  }).catch(function (response) {
	    _this.setLoading(false); // rollback on error


	    _this.check(!_this.isChecked(), false);

	    _classStaticPrivateMethodGet(Embed, Embed, _updateRelatedForms).call(Embed, formNameOld.length > 0, formNameOld, formTypeOld, _this);

	    var messageId = 'EMBED_SLIDER_WIDGET_FORM_ALERT_ERROR';

	    if (!main_core.Type.isUndefined(response.data.error.status) && response.data.error.status === 'access denied') {
	      messageId = response.data.error.code === ERROR_CODE_WIDGET_WRITE_ACCESS_DENIED ? 'EMBED_SLIDER_WIDGET_FORM_ALERT_ERROR_ACCESS_DENIED_WIDGET' : 'EMBED_SLIDER_WIDGET_FORM_ALERT_ERROR_ACCESS_DENIED_FORM';
	    }

	    top.BX.UI.Notification.Center.notify({
	      content: BX.Loc.getMessage(messageId)
	    });
	  });
	}

	function _handleToggledLineSwitcher(formId, lineId) {
	  var _this2 = this;

	  this.setLoading(true); // save old widget values for rollback on error

	  var formNameOld = this.getNode().closest('.crm-form-embed-widgets-block').querySelector('.crm-form-embed-widgets-detail').dataset.formName; // get values for current form

	  var formName = this.getNode().closest('.crm-form-embed-widgets').dataset.formName; // set related forms field to new values

	  _classStaticPrivateMethodGet(Embed, Embed, _updateRelatedOpenlineForms).call(Embed, this.isChecked() ? formName : '', this);

	  return BX.ajax.runAction('crm.form.assignOpenlinesToForm', {
	    json: {
	      formId: formId,
	      lineId: lineId,
	      assigned: this.isChecked() ? 'Y' : 'N' // afterMessage: 'N',

	    }
	  }).then(function (response) {
	    _this2.setLoading(false); // set to returned values (must match current)


	    _this2.check(response.data.assigned, false);

	    _classStaticPrivateMethodGet(Embed, Embed, _updateRelatedOpenlineForms).call(Embed, _this2.isChecked() ? response.data.formName : '', _this2);

	    top.BX.UI.Notification.Center.notify({
	      content: BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_FORM_ALERT_SETTINGS_SAVED')
	    });
	  }).catch(function (response) {
	    _this2.setLoading(false); // rollback on error


	    _this2.check(!_this2.isChecked(), false);

	    _classStaticPrivateMethodGet(Embed, Embed, _updateRelatedOpenlineForms).call(Embed, formNameOld.length > 0 ? formNameOld : '', _this2);

	    var messageId = 'EMBED_SLIDER_OPENLINES_FORM_ALERT_ERROR';

	    if (!main_core.Type.isUndefined(response.data.error.status) && response.data.error.status === 'access denied') {
	      messageId = response.data.error.code === ERROR_CODE_OPENLINES_WRITE_ACCESS_DENIED ? 'EMBED_SLIDER_WIDGET_FORM_ALERT_ERROR_ACCESS_DENIED' // 'EMBED_SLIDER_WIDGET_FORM_ALERT_ERROR_ACCESS_DENIED_OPENLINES'
	      : 'EMBED_SLIDER_WIDGET_FORM_ALERT_ERROR_ACCESS_DENIED_FORM';
	    }

	    top.BX.UI.Notification.Center.notify({
	      content: BX.Loc.getMessage(messageId)
	    });
	  });
	}

	function _updateRelatedOpenlineForms(formName, switcher) {
	  var elem = switcher.getNode().closest('.crm-form-embed-widgets-block').querySelector('.crm-form-embed-widgets-detail');
	  elem.textContent = formName;
	  elem.setAttribute('data-form-name', formName);
	}

	function _updateRelatedForms(assign, formName, formType, switcher) {
	  assign ? _classStaticPrivateMethodGet(Embed, Embed, _setRelatedForms).call(Embed, switcher, formName, formType) : _classStaticPrivateMethodGet(Embed, Embed, _clearRelatedForms).call(Embed, switcher);
	}

	function _setRelatedForms(switcher, formName, formType) {
	  var formTypeMessage = _classStaticPrivateMethodGet(Embed, Embed, _getFormTypeMessage).call(Embed, formType);

	  var elem = switcher.getNode().closest('.crm-form-embed-widgets-block').querySelector('.crm-form-embed-widgets-detail');
	  elem.textContent = formTypeMessage + ': ' + formName;
	  elem.setAttribute('data-form-name', formName);
	  elem.setAttribute('data-form-type', formTypeMessage);
	}

	function _clearRelatedForms(switcher) {
	  var elem = switcher.getNode().closest('.crm-form-embed-widgets-block').querySelector('.crm-form-embed-widgets-detail');
	  elem.textContent = '';
	  elem.setAttribute('data-form-name', '');
	  elem.setAttribute('data-form-type', '');
	}

	function _getFormTypeMessage(formType) {
	  var formTypeLangKey = 'EMBED_SLIDER_WIDGET_FORM_TYPE_MESSAGE_' + formType.toUpperCase();
	  return BX.Loc.hasMessage(formTypeLangKey) ? BX.Loc.getMessage(formTypeLangKey) : formType;
	}

	function _renderSliderContent(formId, embedData, widgetsData, openlinesData) {
	  var widgetBox = _classStaticPrivateMethodGet(Embed, Embed, _renderWidgets).call(Embed, formId, widgetsData);

	  var openlinesBox = _classStaticPrivateMethodGet(Embed, Embed, _renderOpenlines).call(Embed, formId, openlinesData);

	  var pubLinkBox = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-slider-section\" data-menu-item-id=\"link\">\n\t\t\t\t<div class=\"ui-slider-content-box\">\n\t\t\t\t\t<div class=\"ui-slider-heading-3\">", "</div>\n\t\t\t\t\t<div class=\"ui-slider-inner-box\">\n\t\t\t\t\t\t<div class=\"ui-slider-paragraph\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"crm-form-embed-inner-section\">\n\t\t\t\t\t\t\t<div class=\"crm-form-embed-script crm-form-embed-publink\"><a href=\"", "\" target=\"_blank\">", "</a></div>\n\t\t\t\t\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-primary crm-form-embed-btn-copy\">", "</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), BX.Loc.getMessage('EMBED_SLIDER_PUBLIC_LINK'), BX.Loc.getMessage('EMBED_SLIDER_PUBLIC_LINK_HEADER'), main_core.Text.encode(embedData.embed.pubLink), main_core.Text.encode(embedData.embed.pubLink), BX.Loc.getMessage('EMBED_SLIDER_COPY_BUTTON'));
	  top.BX.clipboard.bindCopyClick(pubLinkBox.querySelector('.crm-form-embed-btn-copy'), {
	    text: pubLinkBox.querySelector('.crm-form-embed-script')
	  });
	  return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-form-embed-slider-wrapper\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), pubLinkBox, widgetBox, openlinesBox, _classStaticPrivateMethodGet(Embed, Embed, _renderCodeBlockWrapper).call(Embed, formId, 'inline', embedData, 'inline'), _classStaticPrivateMethodGet(Embed, Embed, _renderCodeBlockWrapper).call(Embed, formId, 'click', embedData, 'click'), _classStaticPrivateMethodGet(Embed, Embed, _renderCodeBlockWrapper).call(Embed, formId, 'auto', embedData, 'auto'));
	}

	function _renderCodeBlockWrapper(formId, type, embedData, blockId) {
	  var viewOptions = main_core.Type.isObject(embedData.embed.viewOptions[type]) ? embedData.embed.viewOptions[type] : {},
	      viewValues = main_core.Type.isObject(embedData.embed.viewValues[type]) ? embedData.embed.viewValues[type] : {};
	  return _classStaticPrivateMethodGet(Embed, Embed, _renderCodeBlock).call(Embed, BX.Loc.getMessage('EMBED_SLIDER_' + blockId.toUpperCase() + '_HEADING1'), BX.Loc.getMessage('EMBED_SLIDER_' + blockId.toUpperCase() + '_HEADING2'), BX.Loc.getMessage('EMBED_SLIDER_' + blockId.toUpperCase() + '_DESCRIPTION'), embedData.embed.scripts[type].text, viewValues, viewOptions, embedData.dict, type, formId, blockId);
	}

	function _renderWidgets(formId, widgetsData) {
	  if (main_core.Type.isNull(widgetsData)) {
	    return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-slider-section\" data-menu-item-id=\"widgets\">\n\t\t\t\t\t<div class=\"ui-slider-content-box\">\n\t\t\t\t\t\t<div class=\"ui-slider-heading-3\">", "</div>\n\t\t\t\t\t\t<div class=\"ui-slider-inner-box\">\n\t\t\t\t\t\t\t<div class=\"ui-slider-paragraph\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"ui-alert ui-alert-warning\">\n\t\t\t\t\t\t\t\t<span class=\"ui-alert-message\">", "</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), BX.Loc.getMessage('EMBED_SLIDER_WIDGET_HEADER'), BX.Loc.getMessage('EMBED_SLIDER_WIDGET_HEADER_INNER'), BX.Loc.getMessage('EMBED_SLIDER_WIDGET_ACCESS_DENIED'));
	  }

	  var allIds = Object.keys(widgetsData.widgets);
	  var allWidgetsUrl = main_core.Type.isStringFilled(widgetsData.url.allWidgets) ? widgetsData.url.allWidgets : '/crm/button/';
	  var widgetsInner;

	  if (allIds.length === 0) {
	    widgetsInner = BX.Loc.getMessage('EMBED_SLIDER_WIDGET_EMPTY');
	  } else {
	    widgetsInner = _classStaticPrivateMethodGet(Embed, Embed, _renderWidgetsList).call(Embed, widgetsData.widgets, formId, widgetsData.formName, widgetsData.formType);
	  }

	  var widgetBox = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-slider-section\" data-menu-item-id=\"widgets\">\n\t\t\t\t<div class=\"ui-slider-content-box\">\n\t\t\t\t\t<div class=\"ui-slider-heading-3\">", "</div>\n\t\t\t\t\t<div class=\"ui-slider-inner-box\">\n\t\t\t\t\t\t<div class=\"ui-slider-paragraph\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<!--<p class=\"ui-slider-paragraph-2\"></p>-->\n\t\t\t\t\t\t<div class=\"crm-form-embed-inner-section\">\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), BX.Loc.getMessage('EMBED_SLIDER_WIDGET_HEADER'), BX.Loc.getMessage('EMBED_SLIDER_WIDGET_HEADER_INNER'));
	  widgetBox.querySelector('.crm-form-embed-inner-section').append(widgetsInner);

	  if (widgetsData.showMoreLink) {
	    var showMoreLink = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<p class=\"crm-form-embed-widgets-all-buttons\">\n\t\t\t\t\t<a href=\"", "\" target=\"_blank\" onclick=\"BX.SidePanel.Instance.open('", "'); return false;\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</a>\n\t\t\t\t</p>\n\t\t\t"])), main_core.Text.encode(allWidgetsUrl), main_core.Text.encode(allWidgetsUrl), BX.Loc.getMessage('EMBED_SLIDER_WIDGET_FORM_ALL_WIDGETS'));
	    widgetBox.querySelector('.crm-form-embed-inner-section').append(showMoreLink);
	  }

	  return widgetBox;
	}

	function _renderOpenlines(formId, openlinesData) {
	  if (main_core.Type.isNull(openlinesData)) {
	    return main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-slider-section\" data-menu-item-id=\"openlines\">\n\t\t\t\t\t<div class=\"ui-slider-content-box\">\n\t\t\t\t\t\t<div class=\"ui-slider-heading-3\">", "</div>\n\t\t\t\t\t\t<div class=\"ui-slider-inner-box\">\n\t\t\t\t\t\t\t<div class=\"ui-slider-paragraph\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"ui-alert ui-alert-warning\">\n\t\t\t\t\t\t\t\t<span class=\"ui-alert-message\">", "</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_HEADER'), BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_HEADER_INNER'), BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_ACCESS_DENIED'));
	  }

	  var allIds = Object.keys(openlinesData.lines);
	  var allLinesUrl = main_core.Type.isStringFilled(openlinesData.url.allLines) ? openlinesData.url.allLines : '/services/contact_center/openlines';
	  var linesInner;

	  if (allIds.length === 0) {
	    linesInner = BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_EMPTY');
	  } else {
	    linesInner = _classStaticPrivateMethodGet(Embed, Embed, _renderLinesList).call(Embed, openlinesData.lines, formId, openlinesData.formName);
	  }

	  var linesBox = main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-slider-section\" data-menu-item-id=\"openlines\">\n\t\t\t\t<div class=\"ui-slider-content-box\">\n\t\t\t\t\t<div class=\"ui-slider-heading-3\">", "</div>\n\t\t\t\t\t<div class=\"ui-slider-inner-box\">\n\t\t\t\t\t\t<div class=\"ui-slider-paragraph\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"crm-form-embed-inner-section\">\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_HEADER'), BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_HEADER_INNER'));
	  linesBox.querySelector('.crm-form-embed-inner-section').append(linesInner);

	  if (openlinesData.showMoreLink) {
	    var showMoreLink = main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<p class=\"crm-form-embed-widgets-all-buttons\">\n\t\t\t\t\t<a href=\"", "\" target=\"_blank\" onclick=\"BX.SidePanel.Instance.open('", "'); return false;\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</a>\n\t\t\t\t</p>\n\t\t\t"])), main_core.Text.encode(allLinesUrl), main_core.Text.encode(allLinesUrl), BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_FORM_ALL_LINES'));
	    linesBox.querySelector('.crm-form-embed-inner-section').append(showMoreLink);
	  }

	  return linesBox;
	}

	function _renderWidgetsList(widgets, formId, currentFormName, currentFormType) {
	  var widgetsList = main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-form-embed-widgets\" data-form-name=\"", "\" data-form-type=\"", "\"></div>"])), main_core.Text.encode(currentFormName), main_core.Text.encode(currentFormType));

	  var widgetIds = _classStaticPrivateMethodGet(Embed, Embed, _getOrderedWidgetIds).call(Embed, widgets);

	  widgetIds.forEach(function (id) {
	    var data = widgets[id];
	    widgetsList.append(_classStaticPrivateMethodGet(Embed, Embed, _renderWidgetRow).call(Embed, data.id, data.name, data.relatedFormNames, currentFormType, data.checked, formId));
	  });
	  return widgetsList;
	}

	function _renderLinesList(lines, formId, currentFormName) {
	  var linesList = main_core.Tag.render(_templateObject11 || (_templateObject11 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-form-embed-widgets\" data-form-name=\"", "\"></div>"])), main_core.Text.encode(currentFormName));

	  var lineIds = _classStaticPrivateMethodGet(Embed, Embed, _getOrderedWidgetIds).call(Embed, lines);

	  lineIds.forEach(function (id) {
	    var data = lines[id];
	    var checked = data.formEnabled && data.checked;
	    var formName = data.formEnabled ? data.formName : '';
	    linesList.append(_classStaticPrivateMethodGet(Embed, Embed, _renderLineRow).call(Embed, data.id, data.name, formName, checked, formId));
	  });
	  return linesList;
	}

	function _getOrderedWidgetIds(widgets) {
	  var widgetIds = Object.keys(widgets);
	  widgetIds.sort(function (a, b) {
	    var aData = widgets[a];
	    var bData = widgets[b];

	    switch (true) {
	      case aData.checked && bData.checked || !aData.checked && !bData.checked:
	        return 0;

	      case aData.checked && !bData.checked:
	        return -1;

	      case !aData.checked && bData.checked:
	        return 1;
	    }
	  });
	  return widgetIds;
	}

	function _createSwitcher(checked, inputName) {
	  var switcherNode = document.createElement('span');
	  switcherNode.className = 'ui-switcher';
	  return new top.BX.UI.Switcher({
	    node: switcherNode,
	    checked: checked,
	    inputName: inputName
	  });
	}

	function _renderWidgetRow(widgetId, widgetName, formNames, formType, checked, formId) {
	  var switcher = _classStaticPrivateMethodGet(Embed, Embed, _createSwitcher).call(Embed, checked, 'crm-form-embed-widget-input-' + widgetId);

	  switcher.handlers = {
	    toggled: _classStaticPrivateMethodGet(BX.Crm.Form.Embed, Embed, _handleToggledWidgetSwitcher).bind(switcher, formId, widgetId)
	  };
	  formNames = Object.values(formNames);

	  var sFormType = _classStaticPrivateMethodGet(Embed, Embed, _getFormTypeMessage).call(Embed, formType);

	  var sFormNames = main_core.Type.isArray(formNames) && formNames.length > 0 ? formNames.join(', ') : '';
	  var sFormNamesField = sFormNames.length > 0 ? sFormType + ': ' + sFormNames : '';
	  var row = main_core.Tag.render(_templateObject12 || (_templateObject12 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-form-embed-widgets-block\">\n\t\t\t\t<div class=\"crm-form-embed-widgets-name-block\">\n\t\t\t\t\t<span class=\"crm-form-embed-widgets-name\">", "</span>\n\t\t\t\t\t<span\n\t\t\t\t\t\tclass=\"crm-form-embed-widgets-detail\"\n\t\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t\tdata-form-name=\"", "\"\n\t\t\t\t\t\tdata-form-type=\"", "\"\n\t\t\t\t\t>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-form-embed-widgets-control\">\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(widgetName), main_core.Text.encode(sFormNamesField), main_core.Text.encode(sFormNames), main_core.Text.encode(sFormType), main_core.Text.encode(sFormNamesField));
	  row.querySelector('.crm-form-embed-widgets-control').append(switcher.getNode());
	  return row;
	}

	function _renderLineRow(lineId, lineName, formName, checked, formId) {
	  var switcher = _classStaticPrivateMethodGet(Embed, Embed, _createSwitcher).call(Embed, checked, 'crm-form-embed-line-input-' + lineId);

	  switcher.handlers = {
	    toggled: _classStaticPrivateMethodGet(BX.Crm.Form.Embed, Embed, _handleToggledLineSwitcher).bind(switcher, formId, lineId)
	  };
	  var row = main_core.Tag.render(_templateObject13 || (_templateObject13 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-form-embed-widgets-block\">\n\t\t\t\t<div class=\"crm-form-embed-widgets-name-block\">\n\t\t\t\t\t<span class=\"crm-form-embed-widgets-name\">", "</span>\n\t\t\t\t\t<span\n\t\t\t\t\t\tclass=\"crm-form-embed-widgets-detail\"\n\t\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t\tdata-form-name=\"", "\"\n\t\t\t\t\t>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-form-embed-widgets-control\">\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(lineName), main_core.Text.encode(formName), main_core.Text.encode(formName), main_core.Text.encode(formName));
	  row.querySelector('.crm-form-embed-widgets-control').append(switcher.getNode());
	  return row;
	}

	function _renderCodeBlock(heading1, heading2, description, code, viewValues, viewOptions, dict, type, formId, blockId) {
	  var contentBox = main_core.Tag.render(_templateObject14 || (_templateObject14 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-slider-section\" data-menu-item-id=\"", "\">\n\t\t\t\t<div class=\"ui-slider-content-box\">\n\t\t\t\t\t<div class=\"ui-slider-heading-3\">", "</div>\n\t\t\t\t\t<div class=\"ui-slider-inner-box\">\n\t\t\t\t\t\t<!-- <p class=\"ui-slider-paragraph-2\">", "</p> -->\n\t\t\t\t\t\t<p class=\"ui-slider-paragraph\">", "</p>\n\t\t\t\t\t\t<div class=\"crm-form-embed-inner-section\">\n\t\t\t\t\t\t\t<div class=\"crm-form-embed-script\"><pre><span>", "</span></pre></div>\n\t\t\t\t\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-primary crm-form-embed-btn-copy\">", "</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Text.encode(blockId), main_core.Text.encode(heading1), main_core.Text.encode(heading2), main_core.Text.encode(description), main_core.Text.encode(code), BX.Loc.getMessage('EMBED_SLIDER_COPY_BUTTON'));
	  contentBox.querySelector('.crm-form-embed-inner-section').prepend(_classStaticPrivateMethodGet(Embed, Embed, _renderViewOptions).call(Embed, viewValues, viewOptions, dict, type, formId));
	  top.BX.clipboard.bindCopyClick(contentBox.querySelector('.crm-form-embed-btn-copy'), {
	    text: contentBox.querySelector('.crm-form-embed-script')
	  });
	  return contentBox;
	}

	function _renderViewOptions(values, options, dict, type, formId) {
	  var renderedOptions = main_core.Tag.render(_templateObject15 || (_templateObject15 = babelHelpers.taggedTemplateLiteral(["<div></div>"])));
	  Object.keys(options).forEach(function (key) {
	    var keyName = _classStaticPrivateMethodGet(Embed, Embed, _getOptionKeyName).call(Embed, key);

	    var keyOptions = main_core.Type.isArray(options[key]) ? options[key] : [];
	    var selected = main_core.Type.isUndefined(values[key]) ? keyOptions[0] : values[key];

	    var selectedName = _classStaticPrivateMethodGet(Embed, Embed, _getOptionValueName).call(Embed, key, selected, dict);

	    var elementId = "crm-form-embed-select-".concat(formId, "-").concat(type, "-").concat(key);
	    var wrapperId = "crm-form-embed-wrapper-".concat(formId, "-").concat(type, "-").concat(key);
	    var optionElem = main_core.Tag.render(_templateObject16 || (_templateObject16 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"inline-options crm-form-embed-field-item-wrapper\" id=\"", "\">\n\t\t\t\t\t<label>", ":</label>\n\t\t\t\t\t<span class=\"crm-form-embed-field-item-text-button\" id=\"", "\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Text.encode(wrapperId), main_core.Text.encode(keyName), main_core.Text.encode(elementId), main_core.Text.encode(selectedName));
	    main_core.Dom.append(optionElem, renderedOptions);

	    if (key === "vertical" && values["type"] === "popup") {
	      var elem = renderedOptions.querySelector("#".concat(wrapperId));
	      main_core.Dom.hide(elem);
	    }

	    var button = renderedOptions.querySelector('#' + elementId);
	    var items = [];
	    keyOptions.forEach(function (option) {
	      var valueName = _classStaticPrivateMethodGet(Embed, Embed, _getOptionValueName).call(Embed, key, option, dict);

	      items.push({
	        text: valueName,
	        value: main_core.Text.encode(option),
	        onclick: function onclick(event, item) {
	          var elem = renderedOptions.querySelector("#crm-form-embed-wrapper-".concat(formId, "-").concat(type, "-vertical"));

	          if (key === "type") {
	            item.value === "popup" && main_core.Dom.hide(elem);
	            item.value === "panel" && main_core.Dom.show(elem);
	          }

	          var prevValue = button.textContent;
	          button.textContent = '...';
	          item.getMenuWindow().close();
	          return BX.ajax.runAction('crm.form.setViewOption', {
	            json: {
	              formId: formId,
	              type: type,
	              key: key,
	              value: item.value
	            }
	          }).then(function (response) {
	            button.textContent = valueName;
	            top.BX.UI.Notification.Center.notify({
	              content: BX.Loc.getMessage('EMBED_SLIDER_OPENLINES_FORM_ALERT_SETTINGS_SAVED')
	            });
	          }).catch(function (response) {
	            if (key === "type") {
	              item.value === "popup" && main_core.Dom.show(elem);
	              item.value === "panel" && main_core.Dom.hide(elem);
	            }

	            button.textContent = prevValue;
	            var messageId = 'EMBED_SLIDER_FORM_SETTINGS_ALERT_ERROR';

	            if (!main_core.Type.isUndefined(response.data.error.status) && response.data.error.status === 'access denied') {
	              messageId = 'EMBED_SLIDER_WIDGET_FORM_ALERT_ERROR_ACCESS_DENIED_FORM';
	            }

	            top.BX.UI.Notification.Center.notify({
	              content: BX.Loc.getMessage(messageId)
	            });
	          });
	        }
	      });
	    });
	    var menu = new top.BX.PopupMenuWindow({
	      bindElement: button,
	      items: items
	    });
	    button.addEventListener("click", function () {
	      menu.show();
	    });
	  });
	  return renderedOptions;
	}

	function _getOptionValueName(key, value, dict) {
	  var namesForOption = main_core.Type.isArray(dict['viewOptions'][key + 's']) ? dict['viewOptions'][key + 's'] : null;
	  var result = value;

	  if (namesForOption !== null) {
	    namesForOption.forEach(function (elem) {
	      if (elem.id.toString() === value.toString()) {
	        result = elem.name;
	      }
	    });
	  }

	  return main_core.Text.encode(result);
	}

	function _getOptionKeyName(key) {
	  var msgKey = 'EMBED_SLIDER_KEY_' + key.toUpperCase();
	  return BX.Loc.hasMessage(msgKey) ? BX.Loc.getMessage(msgKey) : main_core.Text.encode(key);
	}

	exports.Embed = Embed;

}((this.BX.Crm.Form = this.BX.Crm.Form || {}),BX,BX.UI.SidePanel));
//# sourceMappingURL=embed.bundle.js.map
