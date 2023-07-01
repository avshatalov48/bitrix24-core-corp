this.BX = this.BX || {};
(function (exports,main_core,main_core_events,ui_dialogs_messagebox,ui_stageflow,crm_stageModel,main_loader,main_popup,crm_messagesender) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3;
	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var BACKGROUND_COLOR = 'd3d7dc';
	var ItemDetailsComponent = /*#__PURE__*/function () {
	  function ItemDetailsComponent(params) {
	    var _this = this;
	    babelHelpers.classCallCheck(this, ItemDetailsComponent);
	    babelHelpers.defineProperty(this, "categoryId", null);
	    babelHelpers.defineProperty(this, "receiversJSONString", '');
	    if (main_core.Type.isPlainObject(params)) {
	      this.entityTypeId = main_core.Text.toInteger(params.entityTypeId);
	      this.entityTypeName = params.entityTypeName;
	      this.id = main_core.Text.toInteger(params.id);
	      if (BX.Crm.PartialEditorDialog && params.serviceUrl) {
	        this.partialEditorId = 'partial_editor_' + this.entityTypeId + '_' + this.id;
	        BX.Crm.PartialEditorDialog.registerEntityEditorUrl(this.entityTypeId, params.serviceUrl);
	      }
	      if (params.hasOwnProperty('editorContext')) {
	        this.editorContext = params.editorContext;
	      }
	      if (params.hasOwnProperty('categoryId')) {
	        this.categoryId = main_core.Text.toInteger(params.categoryId);
	        this.categories = params.categories;
	      }
	      if (main_core.Type.isElementNode(params.errorTextContainer)) {
	        this.errorTextContainer = params.errorTextContainer;
	      }
	      if (main_core.Type.isArray(params.stages)) {
	        this.stages = [];
	        params.stages.forEach(function (data) {
	          _this.stages.push(new crm_stageModel.StageModel(data));
	        });
	      }
	      this.currentStageId = params.currentStageId;
	      this.messages = params.messages;
	      this.signedParameters = params.signedParameters;
	      this.documentButtonParameters = params.documentButtonParameters;
	      this.userFieldCreateUrl = params.userFieldCreateUrl;
	      this.editorGuid = params.editorGuid;
	      this.isStageFlowActive = params.isStageFlowActive;
	      this.pullTag = params.pullTag;
	      this.bizprocStarterConfig = params.bizprocStarterConfig;
	      this.automationCheckAutomationTourGuideData = main_core.Type.isPlainObject(params.automationCheckAutomationTourGuideData) ? params.automationCheckAutomationTourGuideData : null;
	      if (main_core.Type.isString(params.receiversJSONString)) {
	        this.receiversJSONString = params.receiversJSONString;
	      }
	      this.isPageTitleEditable = Boolean(params.isPageTitleEditable);
	    }
	    this.container = document.querySelector('[data-role="crm-item-detail-container"]');
	    this.handleClosePartialEntityEditor = this.handleClosePartialEntityEditor.bind(this);
	    this.handleErrorPartialEntityEditor = this.handleErrorPartialEntityEditor.bind(this);
	  }
	  babelHelpers.createClass(ItemDetailsComponent, [{
	    key: "getCurrentCategory",
	    value: function getCurrentCategory() {
	      var _this2 = this;
	      var currentCategory = null;
	      if (this.categories && this.categoryId) {
	        this.categories.forEach(function (category) {
	          if (category.categoryId === _this2.categoryId) {
	            currentCategory = category;
	          }
	        });
	      }
	      return currentCategory;
	    }
	  }, {
	    key: "getLoader",
	    value: function getLoader() {
	      if (!this.loader) {
	        this.loader = new main_loader.Loader({
	          size: 200,
	          offset: {
	            left: '-100px',
	            top: '-200px'
	          }
	        });
	        this.loader.layout.style.zIndex = 300;
	      }
	      return this.loader;
	    }
	  }, {
	    key: "startProgress",
	    value: function startProgress() {
	      this.isProgress = true;
	      if (!this.getLoader().isShown() && this.container) {
	        this.getLoader().show(this.container);
	      }
	      this.hideErrors();
	    }
	  }, {
	    key: "stopProgress",
	    value: function stopProgress() {
	      this.isProgress = false;
	      this.getLoader().hide();
	    }
	  }, {
	    key: "getStageById",
	    value: function getStageById(id) {
	      var result = null;
	      var key = 0;
	      while (true) {
	        if (!this.stages[key]) {
	          break;
	        }
	        var stage = this.stages[key];
	        if (stage.getId() === id) {
	          result = stage;
	          break;
	        }
	        key++;
	      }
	      return result;
	    }
	  }, {
	    key: "getStageByStatusId",
	    value: function getStageByStatusId(statusId) {
	      var result = null;
	      var key = 0;
	      while (true) {
	        if (!this.stages[key]) {
	          break;
	        }
	        var stage = this.stages[key];
	        if (stage.getStatusId() === statusId) {
	          result = stage;
	          break;
	        }
	        key++;
	      }
	      return result;
	    }
	  }, {
	    key: "init",
	    value: function init() {
	      this.initStageFlow();
	      this.bindEvents();
	      this.initDocumentButton();
	      this.initReceiversRepository();
	      if (this.id > 0) {
	        this.initPageTitleButtons();
	        this.initPull();
	        this.initTours();
	      }
	    }
	  }, {
	    key: "initDocumentButton",
	    value: function initDocumentButton() {
	      if (main_core.Type.isPlainObject(this.documentButtonParameters) && this.documentButtonParameters.buttonId && BX.DocumentGenerator && BX.DocumentGenerator.Button) {
	        this.documentButton = new BX.DocumentGenerator.Button(this.documentButtonParameters.buttonId, this.documentButtonParameters);
	        this.documentButton.init();
	      }
	    }
	  }, {
	    key: "initReceiversRepository",
	    value: function initReceiversRepository() {
	      crm_messagesender.ReceiverRepository.onDetailsLoad(this.entityTypeId, this.id, this.receiversJSONString);
	    }
	  }, {
	    key: "initPageTitleButtons",
	    value: function initPageTitleButtons() {
	      var pageTitleButtons = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span id=\"pagetitle_btn_wrapper\" class=\"pagetitile-button-container\">\n\t\t\t\t<span id=\"page_url_copy_btn\" class=\"crm-page-link-btn\"></span>\n\t\t\t</span>\n\t\t"])));
	      if (this.isPageTitleEditable) {
	        var editButton = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span id=\"pagetitle_edit\" class=\"pagetitle-edit-button\"></span>\n\t\t\t"])));
	        main_core.Dom.prepend(editButton, pageTitleButtons);
	      }
	      var pageTitle = document.getElementById('pagetitle');
	      main_core.Dom.insertAfter(pageTitleButtons, pageTitle);
	      if (main_core.Type.isArray(this.categories) && this.categories.length > 0) {
	        var currentCategory = this.getCurrentCategory();
	        if (currentCategory) {
	          var categoriesSelector = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div id=\"pagetitle_sub\" class=\"pagetitle-sub\">\n\t\t\t\t\t\t<a href=\"#\" onclick=\"", "\">", "</a>\n\t\t\t\t\t</div>\n\t\t\t\t"])), this.onCategorySelectorClick.bind(this), currentCategory.text);
	          main_core.Dom.insertAfter(categoriesSelector, pageTitleButtons);
	        }
	      }
	    }
	  }, {
	    key: "onCategorySelectorClick",
	    value: function onCategorySelectorClick(event) {
	      var _this3 = this;
	      if (!this.categoryId || !this.categories) {
	        return;
	      }
	      var notCurrentCategories = this.categories.filter(function (category) {
	        return category.categoryId !== _this3.categoryId;
	      });
	      notCurrentCategories.forEach(function (category) {
	        delete category.href;
	        category.onclick = function () {
	          _this3.onCategorySelect(category.categoryId);
	        };
	      });
	      main_popup.PopupMenu.show({
	        id: 'item-detail-' + this.entityTypeId + '-' + this.id,
	        bindElement: event.target,
	        items: notCurrentCategories
	      });
	    }
	  }, {
	    key: "onCategorySelect",
	    value: function onCategorySelect(categoryId) {
	      if (this.isProgress) {
	        return;
	      }
	      this.startProgress();
	      main_core.ajax.runAction('crm.controller.item.update', {
	        analyticsLabel: 'crmItemDetailsChangeCategory',
	        data: {
	          entityTypeId: this.entityTypeId,
	          id: this.id,
	          fields: {
	            categoryId: categoryId
	          }
	        }
	      }).then(function () {
	        setTimeout(function () {
	          //todo what if editor is changed ?
	          window.location.reload();
	        }, 500);
	      })["catch"](this.showErrorsFromResponse.bind(this));
	    }
	  }, {
	    key: "initStageFlow",
	    value: function initStageFlow() {
	      if (this.stages) {
	        var flowStagesData = this.prepareStageFlowStagesData();
	        var stageFlowContainer = document.querySelector('[data-role="stageflow-wrap"]');
	        if (stageFlowContainer) {
	          this.stageflowChart = new ui_stageflow.StageFlow.Chart({
	            backgroundColor: BACKGROUND_COLOR,
	            currentStage: this.currentStageId,
	            isActive: this.isStageFlowActive === true,
	            onStageChange: this.onStageChange.bind(this),
	            labels: {
	              finalStageName: main_core.Loc.getMessage('CRM_ITEM_DETAIL_STAGEFLOW_FINAL_STAGE_NAME'),
	              finalStagePopupTitle: main_core.Loc.getMessage('CRM_ITEM_DETAIL_STAGEFLOW_FINAL_STAGE_POPUP'),
	              finalStagePopupFail: main_core.Loc.getMessage('CRM_ITEM_DETAIL_STAGEFLOW_FINAL_STAGE_POPUP_FAIL'),
	              finalStageSelectorTitle: main_core.Loc.getMessage('CRM_ITEM_DETAIL_STAGEFLOW_FINAL_STAGE_SELECTOR')
	            }
	          }, flowStagesData);
	          stageFlowContainer.appendChild(this.stageflowChart.render());
	        }
	      }
	    }
	  }, {
	    key: "prepareStageFlowStagesData",
	    value: function prepareStageFlowStagesData() {
	      var flowStagesData = [];
	      var isNew = this.id <= 0;
	      this.stages.forEach(function (stage) {
	        var data = stage.getData();
	        var color = stage.getColor().indexOf('#') === 0 ? stage.getColor().substr(1) : stage.getColor();
	        if (isNew) {
	          color = BACKGROUND_COLOR;
	        }
	        data.isSuccess = stage.isSuccess();
	        data.isFail = stage.isFailure();
	        data.color = color;
	        flowStagesData.push(data);
	      });
	      return flowStagesData;
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      main_core_events.EventEmitter.subscribe('BX.Crm.ItemDetailsComponent:onClickDelete', this.handleItemDelete.bind(this));
	      if (this.bizprocStarterConfig) {
	        main_core_events.EventEmitter.subscribe('BX.Crm.ItemDetailsComponent:onClickBizprocTemplates', this.handleBPTemplatesShow.bind(this));
	      }
	      if (this.editorGuid && this.userFieldCreateUrl && BX.SidePanel && BX.Crm.EntityEditor) {
	        main_core_events.EventEmitter.subscribe('BX.UI.EntityConfigurationManager:onCreateClick', this.handleUserFieldCreationUrlClick.bind(this));
	      }
	    }
	  }, {
	    key: "initPull",
	    value: function initPull() {
	      var _this4 = this;
	      var Pull = BX.PULL;
	      if (!Pull) {
	        console.error('pull is not initialized');
	        return;
	      }
	      if (!this.pullTag) {
	        return;
	      }
	      Pull.subscribe({
	        moduleId: 'crm',
	        command: this.pullTag,
	        callback: function callback(params) {
	          if (params && params.item && params.item.data) {
	            var columnId = params.item.data.columnId;
	            if (_this4.stageflowChart && _this4.stageflowChart.isActive) {
	              var currentStage = _this4.getStageById(_this4.stageflowChart.currentStage);
	              if (currentStage && currentStage.statusId !== columnId) {
	                var newStage = _this4.getStageByStatusId(columnId);
	                if (newStage) {
	                  _this4.updateStage(newStage);
	                }
	              }
	            }
	          }
	        }
	      });
	      Pull.extendWatch(this.pullTag);
	    }
	  }, {
	    key: "getEditor",
	    value: function getEditor() {
	      if (BX.Crm.EntityEditor) {
	        if (this.editorGuid) {
	          return BX.Crm.EntityEditor.get(this.editorGuid);
	        }
	        return BX.Crm.EntityEditor.getDefault();
	      }
	      return null;
	    }
	  }, {
	    key: "bindPartialEntityEditorEvents",
	    value: function bindPartialEntityEditorEvents() {
	      main_core_events.EventEmitter.subscribe('Crm.PartialEditorDialog.Close', this.handleClosePartialEntityEditor);
	      main_core_events.EventEmitter.subscribe('Crm.PartialEditorDialog.Error', this.handleErrorPartialEntityEditor);
	    }
	  }, {
	    key: "unBindPartialEntityEditorEvents",
	    value: function unBindPartialEntityEditorEvents() {
	      main_core_events.EventEmitter.unsubscribe('Crm.PartialEditorDialog.Close', this.handleClosePartialEntityEditor);
	      main_core_events.EventEmitter.unsubscribe('Crm.PartialEditorDialog.Error', this.handleErrorPartialEntityEditor);
	    }
	  }, {
	    key: "onStageChange",
	    value: function onStageChange(stageFlowStage) {
	      var _this5 = this;
	      if (this.isProgress) {
	        return;
	      }
	      var stage = this.getStageById(stageFlowStage.getId());
	      if (!stage) {
	        console.error('Wrong stage');
	        return;
	      }
	      this.startProgress();
	      main_core.ajax.runAction('crm.controller.item.update', {
	        analyticsLabel: 'crmItemDetailsMoveItem',
	        data: {
	          entityTypeId: this.entityTypeId,
	          id: this.id,
	          fields: {
	            stageId: stage.getStatusId()
	          }
	        }
	      }).then(function () {
	        _this5.stopProgress();
	        var currentSlider = null;
	        if (main_core.Reflection.getClass('BX.SidePanel.Instance.getTopSlider')) {
	          currentSlider = BX.SidePanel.Instance.getTopSlider();
	        }
	        if (currentSlider !== null) {
	          if (main_core.Reflection.getClass('BX.Crm.EntityEvent')) {
	            var eventParams = null;
	            if (currentSlider) {
	              eventParams = {
	                "sliderUrl": currentSlider.getUrl()
	              };
	            }
	            BX.Crm.EntityEvent.fireUpdate(_this5.entityTypeId, _this5.id, '', eventParams);
	          }
	        }
	        _this5.updateStage(stage);
	      })["catch"](function (response) {
	        _this5.stopProgress();
	        if (!_this5.partialEditorId) {
	          _this5.showErrorsFromResponse(response);
	          return;
	        }
	        var requiredFields = [];
	        response.errors.forEach(function (_ref) {
	          var code = _ref.code,
	            customData = _ref.customData;
	          if (code === 'CRM_FIELD_ERROR_REQUIRED' && customData.fieldName) {
	            requiredFields.push(customData.fieldName);
	          }
	        });
	        if (requiredFields.length > 0) {
	          BX.Crm.PartialEditorDialog.close(_this5.partialEditorId);
	          _this5.partialEntityEditor = BX.Crm.PartialEditorDialog.create(_this5.partialEditorId, {
	            title: BX.prop.getString(_this5.messages, "partialEditorTitle", "Please fill in all required fields"),
	            entityTypeName: _this5.entityTypeName,
	            entityTypeId: _this5.entityTypeId,
	            entityId: _this5.id,
	            fieldNames: requiredFields,
	            helpData: null,
	            context: _this5.editorContext || null,
	            isController: true,
	            stageId: stage.getStatusId()
	          });
	          _this5.bindPartialEntityEditorEvents();
	          _this5.partialEntityEditor.open();
	        } else {
	          _this5.showErrorsFromResponse(response);
	        }
	      });
	    }
	  }, {
	    key: "updateStage",
	    value: function updateStage(stage) {
	      var currentStage = this.getStageById(this.stageflowChart.currentStage);
	      this.stageflowChart.setCurrentStageId(stage.getId());
	      main_core_events.EventEmitter.emit('BX.Crm.ItemDetailsComponent:onStageChange', {
	        entityTypeId: this.entityTypeId,
	        id: this.id,
	        stageId: stage.getStatusId(),
	        previousStageId: currentStage ? currentStage.getStatusId() : null
	      });
	    }
	  }, {
	    key: "showError",
	    value: function showError(error) {
	      if (main_core.Type.isElementNode(this.errorTextContainer)) {
	        this.errorTextContainer.innerText = error;
	        this.errorTextContainer.parentElement.style.display = 'block';
	      } else {
	        console.error(error);
	      }
	    }
	  }, {
	    key: "showErrors",
	    value: function showErrors(errors) {
	      var severalErrorsText = '';
	      errors.forEach(function (message) {
	        severalErrorsText = severalErrorsText + message + ' ';
	      });
	      this.showError(severalErrorsText);
	    }
	  }, {
	    key: "hideErrors",
	    value: function hideErrors() {
	      if (main_core.Type.isElementNode(this.errorTextContainer)) {
	        this.errorTextContainer.innerText = '';
	        this.errorTextContainer.parentElement.style.display = 'none';
	      }
	    }
	  }, {
	    key: "showErrorsFromResponse",
	    value: function showErrorsFromResponse(_ref2) {
	      var errors = _ref2.errors;
	      this.stopProgress();
	      var messages = [];
	      errors.forEach(function (_ref3) {
	        var message = _ref3.message;
	        return messages.push(message);
	      });
	      this.showErrors(messages);
	    }
	  }, {
	    key: "normalizeUrl",
	    value: function normalizeUrl(url) {
	      // Allow redirects only in the current domain
	      return url.setHost('');
	    } // region EventHandlers
	  }, {
	    key: "handleItemDelete",
	    value: function handleItemDelete() {
	      var _this6 = this;
	      if (this.isProgress) {
	        return;
	      }
	      ui_dialogs_messagebox.MessageBox.show({
	        title: this.messages.deleteItemTitle,
	        message: this.messages.deleteItemMessage,
	        modal: true,
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_CANCEL,
	        onYes: function onYes(messageBox) {
	          _this6.startProgress();
	          main_core.ajax.runAction('crm.controller.item.delete', {
	            analyticsLabel: 'crmItemDetailsDeleteItem',
	            data: {
	              entityTypeId: _this6.entityTypeId,
	              id: _this6.id
	            }
	          }).then(function (_ref4) {
	            var data = _ref4.data;
	            _this6.stopProgress();
	            var currentSlider = null;
	            if (main_core.Reflection.getClass('BX.SidePanel.Instance.getTopSlider')) {
	              currentSlider = BX.SidePanel.Instance.getTopSlider();
	            }
	            if (currentSlider !== null) {
	              if (main_core.Reflection.getClass('BX.Crm.EntityEvent')) {
	                var eventParams = null;
	                if (currentSlider) {
	                  eventParams = {
	                    "sliderUrl": currentSlider.getUrl()
	                  };
	                }
	                BX.Crm.EntityEvent.fireDelete(_this6.entityTypeId, _this6.id, '', eventParams);
	              }
	              currentSlider.close();
	            } else {
	              var link = data.redirectUrl;
	              if (main_core.Type.isStringFilled(link)) {
	                var url = _this6.normalizeUrl(new main_core.Uri(link));
	                location.href = url.toString();
	              }
	            }
	          })["catch"](_this6.showErrorsFromResponse.bind(_this6));
	          messageBox.close();
	        }
	      });
	    }
	  }, {
	    key: "handleBPTemplatesShow",
	    value: function handleBPTemplatesShow(event) {
	      var starter = new BX.Bizproc.Starter(this.bizprocStarterConfig);
	      starter.showTemplatesMenu(event.data.button.button);
	    }
	  }, {
	    key: "handleClosePartialEntityEditor",
	    value: function handleClosePartialEntityEditor(event) {
	      this.unBindPartialEntityEditorEvents();
	      this.stopProgress();
	      var data = event.getData();
	      if (main_core.Type.isArray(data) && data.length === 2) {
	        var parameters = data[1];
	        if (parameters.isCancelled) {
	          return;
	        }
	        var stage = this.getStageByStatusId(parameters.stageId);
	        if (!stage) {
	          return;
	        }
	        this.updateStage(stage);
	      }
	    }
	  }, {
	    key: "handleErrorPartialEntityEditor",
	    value: function handleErrorPartialEntityEditor(event) {
	      this.unBindPartialEntityEditorEvents();
	      this.stopProgress();
	      var data = event.getData();
	      if (main_core.Type.isArray(data) && data[1] && main_core.Type.isArray(data[1].errors)) {
	        this.showErrorsFromResponse({
	          errors: data[1].errors
	        });
	      }
	    }
	  }, {
	    key: "handleUserFieldCreationUrlClick",
	    value: function handleUserFieldCreationUrlClick(event) {
	      var data = event.getData();
	      if (data.hasOwnProperty('isCanceled')) {
	        event.setData(_objectSpread(_objectSpread({}, data), {
	          isCanceled: true
	        }));
	        BX.SidePanel.Instance.open(this.userFieldCreateUrl, {
	          allowChangeHistory: false,
	          cacheable: false,
	          events: {
	            onClose: this.onCreateUserFieldSliderClose.bind(this)
	          }
	        });
	      }
	    }
	  }, {
	    key: "onCreateUserFieldSliderClose",
	    value: function onCreateUserFieldSliderClose(event) {
	      var slider = event.getSlider();
	      var sliderData = slider.getData();
	      var userFieldData = sliderData.get('userFieldData');
	      if (userFieldData && main_core.Type.isString(userFieldData)) {
	        this.reloadPageIfNotChanged();
	      }
	    } //endregion
	  }, {
	    key: "reloadPageIfNotChanged",
	    value: function reloadPageIfNotChanged() {
	      var editor = this.getEditor();
	      if (editor) {
	        if (editor.isChanged()) {
	          ui_dialogs_messagebox.MessageBox.alert(this.messages.onCreateUserFieldAddMessage);
	        } else {
	          window.location.reload();
	        }
	      }
	    }
	  }, {
	    key: "initTours",
	    value: function initTours() {
	      var _this7 = this;
	      if (this.automationCheckAutomationTourGuideData) {
	        main_core.Runtime.loadExtension('bizproc.automation.guide').then(function (exports) {
	          var CrmCheckAutomationGuide = exports.CrmCheckAutomationGuide;
	          if (CrmCheckAutomationGuide) {
	            var _this7$categoryId;
	            CrmCheckAutomationGuide.showCheckAutomation(_this7.entityTypeName, (_this7$categoryId = _this7.categoryId) !== null && _this7$categoryId !== void 0 ? _this7$categoryId : 0, _this7.automationCheckAutomationTourGuideData['options']);
	          }
	        });
	      }
	    }
	  }]);
	  return ItemDetailsComponent;
	}();

	exports.ItemDetailsComponent = ItemDetailsComponent;

}((this.BX.Crm = this.BX.Crm || {}),BX,BX.Event,BX.UI.Dialogs,BX.UI,BX.Crm.Models,BX,BX.Main,BX.Crm.MessageSender));
//# sourceMappingURL=item-details-component.bundle.js.map
