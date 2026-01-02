/* eslint-disable */
this.BX = this.BX || {};
(function (exports,crm_itemDetailsComponent_pagetitle,crm_itemDetailsComponent_stageFlow,crm_messagesender,crm_stageModel,crm_stage_permissionChecker,main_core,main_core_events,main_loader,ui_dialogs_messagebox,ui_stageflow,crm_integration_analytics) {
	'use strict';

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var BACKGROUND_COLOR = 'd3d7dc';
	var ItemDetailsComponent = /*#__PURE__*/function () {
	  function ItemDetailsComponent(params) {
	    var _this = this;
	    babelHelpers.classCallCheck(this, ItemDetailsComponent);
	    babelHelpers.defineProperty(this, "categoryId", null);
	    babelHelpers.defineProperty(this, "permissionChecker", null);
	    babelHelpers.defineProperty(this, "receiversJSONString", '');
	    babelHelpers.defineProperty(this, "targetUpdateStage", null);
	    babelHelpers.defineProperty(this, "stageBeforeUpdate", null);
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
	        this.permissionChecker = crm_stage_permissionChecker.PermissionChecker.createFromStageModels(this.stages);
	      }
	      this.currentStageId = params.currentStageId;
	      this.messages = params.messages;
	      this.signedParameters = params.signedParameters;
	      this.userFieldCreateUrl = params.userFieldCreateUrl;
	      this.editorGuid = params.editorGuid;
	      this.isStageFlowActive = params.isStageFlowActive;
	      this.pullTag = params.pullTag;
	      this.bizprocStarterConfig = params.bizprocStarterConfig;
	      this.automationCheckAutomationTourGuideData = main_core.Type.isPlainObject(params.automationCheckAutomationTourGuideData) ? params.automationCheckAutomationTourGuideData : null;
	      if (main_core.Type.isString(params.receiversJSONString)) {
	        this.receiversJSONString = params.receiversJSONString;
	      }
	      if (main_core.Type.isStringFilled(params.categorySelectorTarget)) {
	        this.categorySelectorTarget = params.categorySelectorTarget;
	      }
	    }
	    this.container = document.querySelector('[data-role="crm-item-detail-container"]');
	    this.handleClosePartialEntityEditor = this.handleClosePartialEntityEditor.bind(this);
	    this.handleErrorPartialEntityEditor = this.handleErrorPartialEntityEditor.bind(this);
	  }
	  babelHelpers.createClass(ItemDetailsComponent, [{
	    key: "isNew",
	    value: function isNew() {
	      return this.id <= 0;
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
	    key: "startStageUpdateProgress",
	    value: function startStageUpdateProgress(stage) {
	      this.isProgress = true;
	      this.targetUpdateStage = stage;
	      this.stageflowChart.adjust();
	    }
	  }, {
	    key: "stopStageUpdateProgress",
	    value: function stopStageUpdateProgress() {
	      this.isProgress = false;
	      this.targetUpdateStage = null;
	      this.stageflowChart.adjust();
	    }
	  }, {
	    key: "showStageUpdatingNotification",
	    value: function showStageUpdatingNotification() {
	      if (this.targetUpdateStage === null || !this.messages.stageLoadingMessage) {
	        return;
	      }
	      var stageName = main_core.Text.encode(this.targetUpdateStage.getName());
	      BX.UI.Notification.Center.notify({
	        content: this.messages.stageLoadingMessage.replace('#stage#', stageName),
	        autoHideDelay: 3000
	      });
	    }
	  }, {
	    key: "isStageUpdating",
	    value: function isStageUpdating() {
	      return this.targetUpdateStage !== null && this.isProgress;
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
	      this.initReceiversRepository();
	      this.initCategoriesSelector();
	      if (!this.isNew()) {
	        this.initPull();
	        this.initTours();
	      }
	    }
	  }, {
	    key: "initReceiversRepository",
	    value: function initReceiversRepository() {
	      crm_messagesender.ReceiverRepository.onDetailsLoad(this.entityTypeId, this.id, this.receiversJSONString);
	    }
	  }, {
	    key: "initCategoriesSelector",
	    value: function initCategoriesSelector() {
	      if (!main_core.Type.isArrayFilled(this.categories) || !main_core.Type.isStringFilled(this.categorySelectorTarget)) {
	        return;
	      }
	      var changer = crm_itemDetailsComponent_pagetitle.CategoryChanger.renderToTarget(this.categorySelectorTarget, {
	        entityTypeId: this.entityTypeId,
	        entityId: this.id,
	        categoryId: this.categoryId,
	        categories: this.categories,
	        editorGuid: this.editorGuid
	      });
	      if (!changer) {
	        return;
	      }
	      changer.subscribe('onProgressStart', this.startProgress.bind(this));
	      changer.subscribe('onProgressStop', this.stopProgress.bind(this));
	    }
	  }, {
	    key: "initStageFlow",
	    value: function initStageFlow() {
	      if (!this.stages) {
	        return;
	      }
	      var flowStagesData = this.prepareStageFlowStagesData();
	      var stageFlowContainer = document.querySelector('[data-role="stageflow-wrap"]');
	      if (!stageFlowContainer) {
	        return;
	      }
	      var chartParams = {
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
	      };
	      this.stageflowChart = new crm_itemDetailsComponent_stageFlow.Chart(chartParams, flowStagesData, this.permissionChecker, this.getStageById.bind(this), this.isStageUpdating.bind(this), main_core.Runtime.throttle(this.showStageUpdatingNotification.bind(this), 300), this.isNew(), this.entityTypeId);
	      main_core.Dom.append(this.stageflowChart.render(), stageFlowContainer);
	    }
	  }, {
	    key: "prepareStageFlowStagesData",
	    value: function prepareStageFlowStagesData() {
	      var _this2 = this;
	      var flowStagesData = [];
	      this.stages.forEach(function (stage) {
	        var data = stage.getData();
	        var color = stage.getColor().indexOf('#') === 0 ? stage.getColor().substr(1) : stage.getColor();
	        if (_this2.isNew()) {
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
	      var _this3 = this;
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
	          var _params$item, _this3$stageflowChart;
	          if (!(params !== null && params !== void 0 && (_params$item = params.item) !== null && _params$item !== void 0 && _params$item.data)) {
	            return;
	          }
	          var columnId = params.item.data.columnId;
	          if ((_this3$stageflowChart = _this3.stageflowChart) !== null && _this3$stageflowChart !== void 0 && _this3$stageflowChart.isActive) {
	            var currentStage = _this3.getStageById(_this3.stageflowChart.currentStage);
	            if ((currentStage === null || currentStage === void 0 ? void 0 : currentStage.statusId) !== columnId) {
	              var newStage = _this3.getStageByStatusId(columnId);
	              if (newStage) {
	                _this3.updateStage(newStage);
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
	      var _this4 = this;
	      if (this.isProgress) {
	        return;
	      }
	      var stage = this.getStageById(stageFlowStage.getId());
	      if (!stage) {
	        console.error('Wrong stage');
	        return;
	      }
	      this.prepareAnalyticsData(stage);
	      this.stageBeforeUpdate = this.getStageById(this.currentStageId);
	      this.setStageToFlowChart(stage);
	      this.startStageUpdateProgress(stage);
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
	        _this4.sendAnalyticsData(crm_integration_analytics.Dictionary.STATUS_SUCCESS);
	        _this4.stopStageUpdateProgress();
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
	            BX.Crm.EntityEvent.fireUpdate(_this4.entityTypeId, _this4.id, '', eventParams);
	          }
	        }
	        _this4.stageBeforeUpdate = null;
	        _this4.updateStage(stage);
	      })["catch"](function (response) {
	        _this4.stopStageUpdateProgress();
	        if (_this4.stageBeforeUpdate !== null) {
	          _this4.setStageToFlowChart(_this4.stageBeforeUpdate);
	          _this4.stageBeforeUpdate = null;
	        }
	        if (!_this4.partialEditorId) {
	          _this4.showErrorsFromResponse(response);
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
	          BX.Crm.PartialEditorDialog.close(_this4.partialEditorId);
	          _this4.partialEntityEditor = BX.Crm.PartialEditorDialog.create(_this4.partialEditorId, {
	            title: BX.prop.getString(_this4.messages, "partialEditorTitle", "Please fill in all required fields"),
	            entityTypeName: _this4.entityTypeName,
	            entityTypeId: _this4.entityTypeId,
	            entityId: _this4.id,
	            fieldNames: requiredFields,
	            helpData: null,
	            context: _this4.editorContext || null,
	            isController: true,
	            stageId: stage.getStatusId()
	          });
	          _this4.bindPartialEntityEditorEvents();
	          _this4.partialEntityEditor.open();
	        } else {
	          _this4.sendAnalyticsData(crm_integration_analytics.Dictionary.STATUS_ERROR);
	          _this4.showErrorsFromResponse(response);
	        }
	      });
	    }
	  }, {
	    key: "updateStage",
	    value: function updateStage(stage) {
	      var currentStage = this.getStageById(this.stageflowChart.currentStage);
	      this.setStageToFlowChart(stage);
	      main_core_events.EventEmitter.emit('BX.Crm.ItemDetailsComponent:onStageChange', {
	        entityTypeId: this.entityTypeId,
	        id: this.id,
	        stageId: stage.getStatusId(),
	        previousStageId: currentStage ? currentStage.getStatusId() : null
	      });
	    }
	  }, {
	    key: "setStageToFlowChart",
	    value: function setStageToFlowChart(stage) {
	      this.stageflowChart.setCurrentStageId(stage.getId());
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
	      var _this5 = this;
	      if (this.isProgress) {
	        return;
	      }
	      ui_dialogs_messagebox.MessageBox.show({
	        title: this.messages.deleteItemTitle,
	        message: this.messages.deleteItemMessage,
	        modal: true,
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_CANCEL,
	        onYes: function onYes(messageBox) {
	          _this5.startProgress();
	          main_core.ajax.runAction('crm.controller.item.delete', {
	            analyticsLabel: 'crmItemDetailsDeleteItem',
	            data: {
	              entityTypeId: _this5.entityTypeId,
	              id: _this5.id
	            }
	          }).then(function (_ref4) {
	            var data = _ref4.data;
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
	                BX.Crm.EntityEvent.fireDelete(_this5.entityTypeId, _this5.id, '', eventParams);
	              }
	              currentSlider.close();
	            } else {
	              var link = data.redirectUrl;
	              if (main_core.Type.isStringFilled(link)) {
	                var url = _this5.normalizeUrl(new main_core.Uri(link));
	                location.href = url.toString();
	              }
	            }
	          })["catch"](_this5.showErrorsFromResponse.bind(_this5));
	          messageBox.close();
	        }
	      });
	    }
	  }, {
	    key: "handleBPTemplatesShow",
	    value: function handleBPTemplatesShow(event) {
	      if (this.bizprocStarterConfig.availabilityLock) {
	        // eslint-disable-next-line no-eval
	        eval(this.bizprocStarterConfig.availabilityLock);
	        return;
	      }
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
	          this.sendAnalyticsData(crm_integration_analytics.Dictionary.STATUS_ERROR);
	          return;
	        }
	        var stage = this.getStageByStatusId(parameters.stageId);
	        if (!stage) {
	          this.sendAnalyticsData(crm_integration_analytics.Dictionary.STATUS_ERROR);
	          return;
	        }
	        this.updateStage(stage);
	        this.sendAnalyticsData(crm_integration_analytics.Dictionary.STATUS_SUCCESS);
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
	      var _this6 = this;
	      if (this.automationCheckAutomationTourGuideData) {
	        main_core.Runtime.loadExtension('bizproc.automation.guide').then(function (exports) {
	          var CrmCheckAutomationGuide = exports.CrmCheckAutomationGuide;
	          if (CrmCheckAutomationGuide) {
	            var _this6$categoryId;
	            CrmCheckAutomationGuide.showCheckAutomation(_this6.entityTypeName, (_this6$categoryId = _this6.categoryId) !== null && _this6$categoryId !== void 0 ? _this6$categoryId : 0, _this6.automationCheckAutomationTourGuideData['options']);
	          }
	        });
	      }
	    }
	  }, {
	    key: "prepareAnalyticsData",
	    value: function prepareAnalyticsData(stage) {
	      var stageSemantic = stage.getSemantics();
	      if (stageSemantic === 'F') {
	        this.analyticsData = crm_integration_analytics.Builder.Entity.CloseEvent.createDefault(this.entityTypeId).setElement(crm_integration_analytics.Dictionary.ELEMENT_LOSE_BUTTON).buildData();
	      } else if (stageSemantic === 'S') {
	        this.analyticsData = crm_integration_analytics.Builder.Entity.CloseEvent.createDefault(this.entityTypeId).setElement(crm_integration_analytics.Dictionary.ELEMENT_WON_BUTTON).buildData();
	      } else {
	        this.analyticsData = crm_integration_analytics.Builder.Entity.ChangeStageEvent.createDefault(this.entityTypeId).setElement(crm_integration_analytics.Dictionary.ELEMENT_STAGE_BAR_BUTTON).buildData();
	      }
	    }
	  }, {
	    key: "sendAnalyticsData",
	    value: function sendAnalyticsData(status) {
	      if (!this.analyticsData) {
	        return;
	      }
	      BX.UI.Analytics.sendData(_objectSpread(_objectSpread({}, this.analyticsData), {}, {
	        status: status
	      }));
	    }
	  }]);
	  return ItemDetailsComponent;
	}();

	exports.ItemDetailsComponent = ItemDetailsComponent;

}((this.BX.Crm = this.BX.Crm || {}),BX.Crm.ItemDetailsComponent.PageTitle,BX.Crm.ItemDetailsComponent,BX.Crm.MessageSender,BX.Crm.Models,BX.Crm.Stage,BX,BX.Event,BX,BX.UI.Dialogs,BX.UI,BX.Crm.Integration.Analytics));
//# sourceMappingURL=item-details-component.bundle.js.map
