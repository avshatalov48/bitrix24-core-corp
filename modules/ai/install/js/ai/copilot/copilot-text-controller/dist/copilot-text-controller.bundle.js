/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_popup,ai_engine,ui_feedback_form,ai_ajaxErrorHandler,ui_notification,main_core_events,ui_iconSet_main,main_core,ui_iconSet_api_core) {
	'use strict';

	var _engine = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("engine");
	var _category = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("category");
	var _context = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("context");
	var _useResultStack = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("useResultStack");
	var _selectedText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedText");
	var _userMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userMessage");
	var _commandCode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("commandCode");
	var _selectedEngineCode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedEngineCode");
	var _currentGenerateRequestId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentGenerateRequestId");
	var _resultStack = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resultStack");
	var _toolingDataByCategory = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("toolingDataByCategory");
	var _addResultToStack = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addResultToStack");
	var _getSelectedEngineCode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSelectedEngineCode");
	var _getTooling = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTooling");
	var _setEnginePayload = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setEnginePayload");
	var _isCommandRequiredUserMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isCommandRequiredUserMessage");
	var _isCommandRequiredContextMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isCommandRequiredContextMessage");
	var _getPromptByCode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPromptByCode");
	var _initEngine = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initEngine");
	var _excludeZeroPromptFromPrompts = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("excludeZeroPromptFromPrompts");
	class CopilotTextControllerEngine {
	  constructor(options) {
	    var _options$useResultSta;
	    Object.defineProperty(this, _excludeZeroPromptFromPrompts, {
	      value: _excludeZeroPromptFromPrompts2
	    });
	    Object.defineProperty(this, _initEngine, {
	      value: _initEngine2
	    });
	    Object.defineProperty(this, _getPromptByCode, {
	      value: _getPromptByCode2
	    });
	    Object.defineProperty(this, _isCommandRequiredContextMessage, {
	      value: _isCommandRequiredContextMessage2
	    });
	    Object.defineProperty(this, _isCommandRequiredUserMessage, {
	      value: _isCommandRequiredUserMessage2
	    });
	    Object.defineProperty(this, _setEnginePayload, {
	      value: _setEnginePayload2
	    });
	    Object.defineProperty(this, _getTooling, {
	      value: _getTooling2
	    });
	    Object.defineProperty(this, _getSelectedEngineCode, {
	      value: _getSelectedEngineCode2
	    });
	    Object.defineProperty(this, _addResultToStack, {
	      value: _addResultToStack2
	    });
	    Object.defineProperty(this, _engine, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _category, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _context, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _useResultStack, {
	      writable: true,
	      value: true
	    });
	    Object.defineProperty(this, _selectedText, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _userMessage, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _commandCode, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _selectedEngineCode, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _currentGenerateRequestId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _resultStack, {
	      writable: true,
	      value: []
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _category)[_category] = options.category;
	    babelHelpers.classPrivateFieldLooseBase(this, _useResultStack)[_useResultStack] = (_options$useResultSta = options.useResultStack) != null ? _options$useResultSta : babelHelpers.classPrivateFieldLooseBase(this, _useResultStack)[_useResultStack];
	    babelHelpers.classPrivateFieldLooseBase(this, _initEngine)[_initEngine]({
	      moduleId: options.moduleId,
	      category: options.category,
	      contextId: options.contextId,
	      contextParameters: options.contextParameters
	    });
	  }
	  async init() {
	    if (babelHelpers.classPrivateFieldLooseBase(CopilotTextControllerEngine, _toolingDataByCategory)[_toolingDataByCategory][babelHelpers.classPrivateFieldLooseBase(this, _category)[_category]] === undefined) {
	      babelHelpers.classPrivateFieldLooseBase(CopilotTextControllerEngine, _toolingDataByCategory)[_toolingDataByCategory][babelHelpers.classPrivateFieldLooseBase(this, _category)[_category]] = babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].getTooling('text');
	    }
	    const res = await babelHelpers.classPrivateFieldLooseBase(CopilotTextControllerEngine, _toolingDataByCategory)[_toolingDataByCategory][babelHelpers.classPrivateFieldLooseBase(this, _category)[_category]];
	    babelHelpers.classPrivateFieldLooseBase(CopilotTextControllerEngine, _toolingDataByCategory)[_toolingDataByCategory][babelHelpers.classPrivateFieldLooseBase(this, _category)[_category]] = res;
	    babelHelpers.classPrivateFieldLooseBase(this, _excludeZeroPromptFromPrompts)[_excludeZeroPromptFromPrompts]();
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedEngineCode)[_selectedEngineCode] = babelHelpers.classPrivateFieldLooseBase(this, _getSelectedEngineCode)[_getSelectedEngineCode](res.data.engines);
	  }
	  async completions() {
	    babelHelpers.classPrivateFieldLooseBase(this, _setEnginePayload)[_setEnginePayload]();
	    const id = Math.round(Math.random() * 10000);
	    babelHelpers.classPrivateFieldLooseBase(this, _currentGenerateRequestId)[_currentGenerateRequestId] = id;
	    try {
	      const res = await babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].textCompletions();
	      const result = res.data.result || res.data.last.data;
	      if (babelHelpers.classPrivateFieldLooseBase(this, _currentGenerateRequestId)[_currentGenerateRequestId] !== id) {
	        return null;
	      }
	      if (babelHelpers.classPrivateFieldLooseBase(this, _useResultStack)[_useResultStack]) {
	        babelHelpers.classPrivateFieldLooseBase(this, _addResultToStack)[_addResultToStack](result);
	      }
	      return result;
	    } catch (res) {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _currentGenerateRequestId)[_currentGenerateRequestId] !== id) {
	        return null;
	      }
	      throw getBaseErrorFromResponse(res);
	    }
	  }
	  getPrompts() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getTooling)[_getTooling]().promptsSystem;
	  }
	  getPermissions() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getTooling)[_getTooling]().permissions;
	  }
	  getEngines() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getTooling)[_getTooling]().engines;
	  }
	  setSelectedEngineCode(code) {
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedEngineCode)[_selectedEngineCode] = code;
	  }
	  getSelectedEngineCode() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _selectedEngineCode)[_selectedEngineCode];
	  }
	  getCategory() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _category)[_category];
	  }
	  getContextId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].getContextId();
	  }
	  setContext(context) {
	    babelHelpers.classPrivateFieldLooseBase(this, _context)[_context] = context;
	  }
	  setSelectedText(selectedText) {
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedText)[_selectedText] = selectedText;
	  }
	  getOriginalMessage() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _selectedText)[_selectedText] || babelHelpers.classPrivateFieldLooseBase(this, _context)[_context] || '';
	  }
	  setUserMessage(userMessage) {
	    babelHelpers.classPrivateFieldLooseBase(this, _userMessage)[_userMessage] = userMessage;
	  }
	  getCommandCode() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _commandCode)[_commandCode];
	  }
	  setCommandCode(commandCode) {
	    babelHelpers.classPrivateFieldLooseBase(this, _commandCode)[_commandCode] = commandCode;
	  }
	  cancelCompletion() {
	    babelHelpers.classPrivateFieldLooseBase(this, _currentGenerateRequestId)[_currentGenerateRequestId] = -1;
	  }
	  isCopilotFirstLaunch() {
	    return Boolean(babelHelpers.classPrivateFieldLooseBase(this, _getTooling)[_getTooling]().first_launch);
	  }
	  setCopilotBannerLaunchedFlag() {
	    babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].setBannerLaunched();
	  }
	  setAnalyticParameters(parameters) {
	    babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].setAnalyticParameters(parameters);
	  }
	  async getDataForFeedbackForm() {
	    try {
	      const feedDataResult = await babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].getFeedbackData();
	      const messages = feedDataResult.data.context_messages;
	      const authorMessage = feedDataResult.data.original_message;
	      const payload = babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].getPayload();
	      return payload ? {
	        context_messages: messages,
	        author_message: authorMessage,
	        ...payload.getRawData(),
	        ...payload.getMarkers()
	      } : {};
	    } catch (error) {
	      console.error(error);
	      const payload = babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].getPayload();
	      return payload ? {
	        ...payload.getRawData(),
	        ...payload.getMarkers()
	      } : {};
	    }
	  }
	}
	function _addResultToStack2(result) {
	  const stackSize = 3;
	  babelHelpers.classPrivateFieldLooseBase(this, _resultStack)[_resultStack].unshift(result);
	  if (babelHelpers.classPrivateFieldLooseBase(this, _resultStack)[_resultStack].length > stackSize) {
	    babelHelpers.classPrivateFieldLooseBase(this, _resultStack)[_resultStack].pop();
	  }
	}
	function _getSelectedEngineCode2(engines) {
	  var _engines$;
	  const selectedEngine = engines.find(engine => engine.selected);
	  return (selectedEngine == null ? void 0 : selectedEngine.code) || ((_engines$ = engines[0]) == null ? void 0 : _engines$.code);
	}
	function _getTooling2() {
	  var _babelHelpers$classPr;
	  return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(CopilotTextControllerEngine, _toolingDataByCategory)[_toolingDataByCategory][babelHelpers.classPrivateFieldLooseBase(this, _category)[_category]]) == null ? void 0 : _babelHelpers$classPr.data;
	}
	function _setEnginePayload2() {
	  const command = babelHelpers.classPrivateFieldLooseBase(this, _commandCode)[_commandCode];
	  const userMessage = babelHelpers.classPrivateFieldLooseBase(this, _userMessage)[_userMessage] || undefined;
	  const originalMessage = this.getOriginalMessage();
	  const payload = new ai_engine.Text({
	    prompt: {
	      code: command
	    },
	    engineCode: babelHelpers.classPrivateFieldLooseBase(this, _selectedEngineCode)[_selectedEngineCode]
	  });
	  payload.setMarkers({
	    original_message: babelHelpers.classPrivateFieldLooseBase(this, _isCommandRequiredContextMessage)[_isCommandRequiredContextMessage](command) ? originalMessage : undefined,
	    user_message: babelHelpers.classPrivateFieldLooseBase(this, _isCommandRequiredUserMessage)[_isCommandRequiredUserMessage](command) ? userMessage : undefined,
	    current_result: babelHelpers.classPrivateFieldLooseBase(this, _resultStack)[_resultStack]
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].setPayload(payload);
	}
	function _isCommandRequiredUserMessage2(commandCode) {
	  const prompts = babelHelpers.classPrivateFieldLooseBase(this, _getTooling)[_getTooling]().promptsSystem;
	  const searchPrompt = babelHelpers.classPrivateFieldLooseBase(this, _getPromptByCode)[_getPromptByCode](prompts, commandCode);
	  if (!searchPrompt) {
	    return false;
	  }
	  return searchPrompt.required.user_message;
	}
	function _isCommandRequiredContextMessage2(commandCode) {
	  const prompts = babelHelpers.classPrivateFieldLooseBase(this, _getTooling)[_getTooling]().promptsSystem;
	  const searchPrompt = babelHelpers.classPrivateFieldLooseBase(this, _getPromptByCode)[_getPromptByCode](prompts, commandCode);
	  if (!searchPrompt) {
	    return false;
	  }
	  return searchPrompt.required.context_message;
	}
	function _getPromptByCode2(prompts, commandCode) {
	  let searchPrompt = null;
	  prompts.some(prompt => {
	    var _prompt$children;
	    if (prompt.code === commandCode) {
	      searchPrompt = prompt;
	      return true;
	    }
	    return (_prompt$children = prompt.children) == null ? void 0 : _prompt$children.some(childrenPrompt => {
	      if (childrenPrompt.code === commandCode) {
	        searchPrompt = childrenPrompt;
	        return true;
	      }
	      return false;
	    });
	  });
	  return searchPrompt;
	}
	function _initEngine2(initEngineOptions) {
	  babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine] = new ai_engine.Engine();
	  babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].setModuleId(initEngineOptions.moduleId).setContextId(initEngineOptions.contextId).setContextParameters(initEngineOptions.contextParameters).setParameters({
	    promptCategory: initEngineOptions.category
	  });
	}
	function _excludeZeroPromptFromPrompts2() {
	  const zeroPromptIndex = babelHelpers.classPrivateFieldLooseBase(CopilotTextControllerEngine, _toolingDataByCategory)[_toolingDataByCategory][babelHelpers.classPrivateFieldLooseBase(this, _category)[_category]].data.promptsSystem.findIndex(prompt => {
	    return prompt.code === 'zero_prompt';
	  });
	  if (zeroPromptIndex > -1) {
	    babelHelpers.classPrivateFieldLooseBase(CopilotTextControllerEngine, _toolingDataByCategory)[_toolingDataByCategory][babelHelpers.classPrivateFieldLooseBase(this, _category)[_category]].data.promptsSystem.splice(zeroPromptIndex, 1);
	  }
	}
	Object.defineProperty(CopilotTextControllerEngine, _toolingDataByCategory, {
	  writable: true,
	  value: {}
	});
	function getBaseErrorFromResponse(res) {
	  if (res instanceof Error) {
	    return new main_core.BaseError(res.message, 'undefined', {});
	  }
	  if (main_core.Type.isString(res)) {
	    return new main_core.BaseError(res, 'undefined', {});
	  }
	  const firstErrorData = res.errors[0];
	  if (!firstErrorData) {
	    return null;
	  }
	  const {
	    message,
	    code,
	    customData
	  } = firstErrorData;
	  return new main_core.BaseError(message, code, customData);
	}

	class BaseCommand {
	  constructor(options) {
	    this.copilotTextController = options == null ? void 0 : options.copilotTextController;
	  }
	}

	class AddBelowCommand extends BaseCommand {
	  execute() {
	    this.copilotTextController.emit('add_below', {
	      result: this.copilotTextController.getAiResultText(),
	      code: this.copilotTextController.getLastCommandCode()
	    });
	  }
	}

	var _copilotContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotContainer");
	var _inputField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputField");
	class CancelCommand extends BaseCommand {
	  constructor(options) {
	    super(options);
	    Object.defineProperty(this, _copilotContainer, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputField, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer)[_copilotContainer] = options.copilotContainer;
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField] = options.inputField;
	  }
	  execute() {
	    this.copilotTextController.destroyAllMenus();
	    this.copilotTextController.openGeneralMenu();
	    this.copilotTextController.clearResultStack();
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField].clearErrors();
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField].clear();
	    if (this.copilotTextController.isReadonly() === false) {
	      babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField].enable();
	    }
	    this.copilotTextController.clearResultField();
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer)[_copilotContainer], '--error');
	    // this.#selectedCommand = null;
	    this.copilotTextController.emit('cancel');
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField].focus();
	    this.copilotTextController.getAnalytics().sendEventCancel();
	  }
	}

	var _inputField$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputField");
	var _copilotContainer$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotContainer");
	class EditResultCommand extends BaseCommand {
	  constructor(options) {
	    super(options);
	    Object.defineProperty(this, _inputField$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _copilotContainer$1, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1] = options.inputField;
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$1)[_copilotContainer$1] = options.copilotContainer;
	  }
	  execute() {
	    this.copilotTextController.destroyAllMenus();
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].enable();
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].clearErrors();
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$1)[_copilotContainer$1], '--error');
	    // this.#resultField.clearResult();
	    this.copilotTextController.openGeneralMenu();
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].focus();
	    this.copilotTextController.getAnalytics().sendEventEditResult();
	    // this.#selectedCommand = null;
	  }
	}

	var _commandCode$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("commandCode");
	class GenerateWithRequiredUserMessageCommand extends BaseCommand {
	  constructor(options) {
	    super(options);
	    Object.defineProperty(this, _commandCode$1, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _commandCode$1)[_commandCode$1] = options.commandCode;
	  }
	  async execute() {
	    const data = new FormData();
	    data.append('promptCode', babelHelpers.classPrivateFieldLooseBase(this, _commandCode$1)[_commandCode$1]);
	    try {
	      const res = await main_core.ajax.runAction('ai.prompt.getTextByCode', {
	        data
	      });
	      this.copilotTextController.generateWithRequiredUserMessage(babelHelpers.classPrivateFieldLooseBase(this, _commandCode$1)[_commandCode$1], res.data.text);
	    } catch (e) {
	      console.error(e);
	    }
	  }
	}

	var _commandCode$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("commandCode");
	var _prompts = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prompts");
	class GenerateWithoutRequiredUserMessage extends BaseCommand {
	  constructor(options) {
	    super(options);
	    Object.defineProperty(this, _commandCode$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _prompts, {
	      writable: true,
	      value: []
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _prompts)[_prompts] = options.prompts;
	    babelHelpers.classPrivateFieldLooseBase(this, _commandCode$2)[_commandCode$2] = options.commandCode;
	  }
	  execute() {
	    this.copilotTextController.generateWithoutRequiredUserMessage(babelHelpers.classPrivateFieldLooseBase(this, _commandCode$2)[_commandCode$2], babelHelpers.classPrivateFieldLooseBase(this, _prompts)[_prompts]);
	  }
	}

	class OpenAboutCopilot extends BaseCommand {
	  execute() {
	    const articleCode = '19092894';
	    const Helper = main_core.Reflection.getClass('top.BX.Helper');
	    if (Helper) {
	      Helper.show(`redirect=detail&code=${articleCode}`);
	    }
	  }
	}

	var _category$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("category");
	var _isBeforeGeneration = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isBeforeGeneration");
	var _openFeedbackForm = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openFeedbackForm");
	class OpenFeedbackFormCommand extends BaseCommand {
	  constructor(options) {
	    super(options);
	    Object.defineProperty(this, _openFeedbackForm, {
	      value: _openFeedbackForm2
	    });
	    Object.defineProperty(this, _category$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isBeforeGeneration, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _category$1)[_category$1] = options.category;
	    babelHelpers.classPrivateFieldLooseBase(this, _isBeforeGeneration)[_isBeforeGeneration] = options.isBeforeGeneration;
	  }
	  async execute() {
	    await babelHelpers.classPrivateFieldLooseBase(this, _openFeedbackForm)[_openFeedbackForm]();
	  }
	}
	async function _openFeedbackForm2() {
	  var _data, _data$context_message, _data2, _data$author_message, _data3;
	  const senderPagePreset = `${babelHelpers.classPrivateFieldLooseBase(this, _category$1)[_category$1]},${babelHelpers.classPrivateFieldLooseBase(this, _isBeforeGeneration)[_isBeforeGeneration] ? 'before' : 'after'}`;
	  let data = null;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isBeforeGeneration)[_isBeforeGeneration] === false) {
	    data = await this.copilotTextController.getDataForFeedbackForm();
	  }
	  const contextMessages = ((_data = data) == null ? void 0 : (_data$context_message = _data.context_messages) == null ? void 0 : _data$context_message.length) > 0 ? JSON.stringify((_data2 = data) == null ? void 0 : _data2.context_messages) : undefined;
	  const authorMessage = (_data$author_message = (_data3 = data) == null ? void 0 : _data3.author_message) != null ? _data$author_message : undefined;
	  const formIdNumber = Math.round(Math.random() * 1000);
	  main_core.Runtime.loadExtension(['ui.feedback.form']).then(() => {
	    var _data4, _data4$prompt, _data5, _data6, _data7, _data7$current_result, _data8, _data8$current_result;
	    BX.UI.Feedback.Form.open({
	      id: `ai.copilot.feedback-${formIdNumber}`,
	      forms: [{
	        zones: ['es'],
	        id: 684,
	        lang: 'es',
	        sec: 'svvq1x'
	      }, {
	        zones: ['en'],
	        id: 686,
	        lang: 'en',
	        sec: 'tjwodz'
	      }, {
	        zones: ['de'],
	        id: 688,
	        lang: 'de',
	        sec: 'nrwksg'
	      }, {
	        zones: ['com.br'],
	        id: 690,
	        lang: 'com.br',
	        sec: 'kpte6m'
	      }, {
	        zones: ['ru', 'by', 'kz'],
	        id: 692,
	        lang: 'ru',
	        sec: 'jbujn0'
	      }],
	      presets: {
	        sender_page: senderPagePreset,
	        prompt_code: (_data4 = data) == null ? void 0 : (_data4$prompt = _data4.prompt) == null ? void 0 : _data4$prompt.code,
	        user_message: (_data5 = data) == null ? void 0 : _data5.user_message,
	        original_message: (_data6 = data) == null ? void 0 : _data6.original_message,
	        author_message: authorMessage,
	        context_messages: contextMessages,
	        last_result0: (_data7 = data) == null ? void 0 : (_data7$current_result = _data7.current_result) == null ? void 0 : _data7$current_result[1],
	        language: main_core.Loc.getMessage('LANGUAGE_ID'),
	        cp_answer: (_data8 = data) == null ? void 0 : (_data8$current_result = _data8.current_result) == null ? void 0 : _data8$current_result[0]
	      }
	    });
	  }).catch(err => {
	    console.err(err);
	  });
	}

	class RepeatCommand extends BaseCommand {
	  execute() {
	    this.copilotTextController.generate();
	  }
	}

	class RepeatGenerateCommand extends BaseCommand {
	  execute() {
	    this.copilotTextController.adjustMenusPosition();
	    this.copilotTextController.generate();
	  }
	}

	class SaveCommand extends BaseCommand {
	  execute() {
	    this.copilotTextController.emit('save', new main_core_events.BaseEvent({
	      data: {
	        result: this.copilotTextController.getAiResultText(),
	        code: this.copilotTextController.getLastCommandCode()
	      }
	    }));
	    this.copilotTextController.getAnalytics().sendEventSave();
	  }
	}

	var _engineCode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("engineCode");
	class SetEngineCommand extends BaseCommand {
	  constructor(options) {
	    super(options);
	    Object.defineProperty(this, _engineCode, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _engineCode)[_engineCode] = options.engineCode;
	  }
	  execute() {
	    this.copilotTextController.setSelectedEngine(babelHelpers.classPrivateFieldLooseBase(this, _engineCode)[_engineCode]);
	  }
	}

	class CloseCommand$$1 extends BaseCommand {
	  execute() {
	    this.copilotTextController.emit('close');
	  }
	}

	class OpenImageConfigurator extends BaseCommand {
	  execute() {
	    this.copilotTextController.emit('show-image-configurator');
	  }
	}

	class CopilotErrorMenuItems {
	  static getMenuItems(options) {
	    const {
	      inputField,
	      copilotTextController,
	      copilotContainer
	    } = options;
	    return [{
	      code: 'repeat',
	      text: main_core.Loc.getMessage('AI_COPILOT_COMMAND_REPEAT'),
	      icon: 'left-semicircular-anticlockwise-arrow-1',
	      command: new RepeatCommand({
	        copilotTextController
	      }),
	      notHighlight: true
	    }, {
	      text: main_core.Loc.getMessage('AI_COPILOT_COMMAND_EDIT'),
	      code: 'edit',
	      icon: 'pencil-60',
	      command: new EditResultCommand({
	        inputField,
	        copilotTextController,
	        copilotContainer
	      }),
	      notHighlight: true
	    }, {
	      code: 'cancel',
	      text: main_core.Loc.getMessage('AI_COPILOT_COMMAND_CANCEL'),
	      icon: 'cross-45',
	      command: new CancelCommand({
	        copilotTextController,
	        inputField,
	        copilotContainer
	      }),
	      notHighlight: true
	    }];
	  }
	}

	class CopilotProvidersMenuItems {
	  static getMenuItems(options) {
	    const {
	      engines,
	      selectedEngineCode,
	      canEditSettings = false,
	      copilotTextController
	    } = options;
	    const connectAiMenuItem = {
	      text: main_core.Loc.getMessage('AI_COPILOT_COMMAND_CONNECT_AI'),
	      disabled: true,
	      icon: ui_iconSet_api_core.Actions.PLUS_50
	    };
	    let result = [...getMenuItemsFromEngines(engines, selectedEngineCode, copilotTextController), connectAiMenuItem, {
	      separator: true
	    }, getMarketMenuItem()];
	    if (canEditSettings) {
	      const settingsPageLink = main_core.Extension.getSettings('ai.copilot.copilot-text-controller').settingsPageLink;
	      result = [...result, {
	        code: 'ai_settings',
	        text: main_core.Loc.getMessage('AI_COPILOT_MENU_ITEM_AI_SETTINGS'),
	        icon: ui_iconSet_api_core.Actions.SETTINGS_4,
	        href: settingsPageLink
	      }];
	    }
	    return result;
	  }
	}
	function getMenuItemsFromEngines(engines, selectedEngineCode, copilotTextController) {
	  return engines.map(engine => {
	    return {
	      code: engine.code,
	      text: engine.title,
	      icon: ui_iconSet_api_core.Main.ROBOT,
	      selected: selectedEngineCode === engine.code,
	      command: new SetEngineCommand({
	        engines,
	        copilotTextController,
	        engineCode: engine.code
	      })
	    };
	  });
	}
	function getMarketMenuItem() {
	  return {
	    code: 'market',
	    href: '/market/collection/ai_provider_partner_crm/',
	    text: main_core.Loc.getMessage('AI_COPILOT_SEARCH_IN_MARKET'),
	    icon: ui_iconSet_api_core.Main.MARKET_1,
	    arrow: false
	  };
	}

	class CopilotMenuItems {
	  static getMenuItems(options) {
	    throw new Error('You must override method: getMenuItems');
	  }
	}

	class CopilotResultMenuItems extends CopilotMenuItems {
	  static getMenuItems(options, category) {
	    var _options$inputField, _options$copilotTextC;
	    const {
	      prompts,
	      selectedText,
	      copilotContainer = null
	    } = options;
	    const inputField = (_options$inputField = options.inputField) != null ? _options$inputField : null;
	    const copilotTextController = (_options$copilotTextC = options.copilotTextController) != null ? _options$copilotTextC : null;
	    const saveMenuItemText = selectedText ? 'AI_COPILOT_COMMAND_REPLACE' : 'AI_COPILOT_COMMAND_SAVE';
	    const saveMenuItem = {
	      text: main_core.Loc.getMessage(saveMenuItemText),
	      code: 'save',
	      icon: 'check',
	      command: new SaveCommand({
	        copilotTextController
	      }),
	      notHighlight: true
	    };
	    const promptMasterMenuItem = copilotTextController.getLastCommandCode() === 'zero_prompt' && copilotTextController.isReadonly() === false && copilotTextController.getSelectedPromptCodeWithSimpleTemplate() === null ? {
	      code: 'prompt-master',
	      text: main_core.Loc.getMessage('AI_COPILOT_MENU_ITEM_CREATE_PROMPT'),
	      icon: ui_iconSet_api_core.Main.BOOKMARK_1,
	      notHighlight: true,
	      command: async () => {
	        await copilotTextController.showPromptMasterPopup();
	      }
	    } : null;
	    const editMenuItem = {
	      text: main_core.Loc.getMessage('AI_COPILOT_COMMAND_EDIT'),
	      code: 'edit',
	      icon: 'pencil-60',
	      command: new EditResultCommand({
	        inputField,
	        copilotTextController,
	        copilotContainer
	      }),
	      notHighlight: true
	    };
	    const addBelowMenuItem = selectedText ? {
	      text: main_core.Loc.getMessage('AI_COPILOT_COMMAND_ADD_BELOW'),
	      code: 'add_below',
	      icon: 'download',
	      command: new AddBelowCommand({
	        copilotTextController
	      }),
	      notHighlight: true
	    } : null;
	    return [promptMasterMenuItem, {
	      separator: true
	    }, saveMenuItem, addBelowMenuItem, editMenuItem, ...getResultMenuPromptItems(prompts), {
	      text: main_core.Loc.getMessage('AI_COPILOT_COMMAND_REPEAT'),
	      code: 'repeat',
	      icon: 'left-semicircular-anticlockwise-arrow-1',
	      command: new RepeatGenerateCommand({
	        copilotTextController
	      }),
	      notHighlight: true
	    }, {
	      separator: true
	    }, getFeedbackMenuItem(category, copilotTextController), {
	      text: main_core.Loc.getMessage('AI_COPILOT_COMMAND_CANCEL'),
	      code: 'cancel',
	      icon: 'cross-45',
	      notHighlight: true,
	      command: new CancelCommand({
	        inputField,
	        copilotTextController
	      })
	    }].filter(item => item);
	  }
	  static getMenuItemsForReadonlyResult(category, copilotTextController, inputField, copilotContainer) {
	    return [{
	      text: main_core.Loc.getMessage('AI_COPILOT_COMMAND_COPY'),
	      code: 'copy',
	      icon: ui_iconSet_api_core.Actions.COPY_PLATES,
	      notHighlight: true,
	      command: {
	        execute() {
	          BX.clipboard.copy(copilotTextController.getAiResultText());
	          ui_notification.UI.Notification.Center.notify({
	            content: main_core.Loc.getMessage('AI_COPILOT_TEXT_IS_COPIED')
	          });
	          copilotTextController.getAnalytics().sendEventCopyResult();
	        }
	      }
	    }, {
	      text: main_core.Loc.getMessage('AI_COPILOT_COMMAND_EDIT'),
	      code: 'cancel',
	      icon: ui_iconSet_api_core.Actions.PENCIL_60,
	      command: new CancelCommand({
	        inputField,
	        copilotTextController,
	        copilotContainer
	      }),
	      notHighlight: true
	    }, {
	      separator: true
	    }, getFeedbackMenuItem(category, copilotTextController), {
	      text: main_core.Loc.getMessage('AI_COPILOT_COMMAND_CLOSE'),
	      code: 'close',
	      icon: ui_iconSet_api_core.Actions.CROSS_45,
	      notHighlight: true,
	      command: new CloseCommand$$1({
	        copilotTextController
	      })
	    }];
	  }
	}
	function getResultMenuPromptItems(prompts) {
	  const workWithResultPrompts = prompts.filter(prompt => {
	    return prompt.workWithResult;
	  });
	  return workWithResultPrompts.map(prompt => {
	    return {
	      text: prompt.title,
	      code: prompt.code,
	      icon: prompt.icon
	    };
	  });
	}
	function getFeedbackMenuItem(category, copilotTextController) {
	  return {
	    code: 'feedback',
	    text: main_core.Loc.getMessage('AI_COPILOT_MENU_ITEM_AI_FEEDBACK'),
	    icon: ui_iconSet_api_core.Main.FEEDBACK,
	    notHighlight: true,
	    command: new OpenFeedbackFormCommand({
	      category,
	      isBeforeGeneration: false,
	      copilotTextController
	    })
	  };
	}

	class CopilotGeneralMenuItems extends CopilotMenuItems {
	  static getMenuItems(options) {
	    const {
	      engines,
	      selectedEngineCode,
	      canEditSettings = false,
	      copilotTextController,
	      addImageMenuItem = false,
	      userPrompts,
	      systemPrompts,
	      favouritePrompts
	    } = options;
	    const favouriteSectionSeparator = favouritePrompts.length > 0 ? CopilotGeneralMenuItems.getFavouritePromptsSeparatorMenuItem() : null;
	    const imageMenuItem = addImageMenuItem ? [{
	      code: 'image',
	      text: main_core.Loc.getMessage('AI_COPILOT_MENU_ITEM_AI_IMAGE'),
	      icon: ui_iconSet_api_core.Main.MAGIC_IMAGE,
	      command: new OpenImageConfigurator({
	        copilotTextController
	      }),
	      labelText: main_core.Loc.getMessage('AI_COPILOT_MENU_ITEM_LABEL_NEW')
	    }] : [];
	    return [...imageMenuItem, favouriteSectionSeparator, ...getGeneralMenuItemsFromPrompts(favouritePrompts, copilotTextController, true), ...(copilotTextController.isReadonly() === false ? [{
	      code: 'user-prompt-separator',
	      separator: true,
	      title: main_core.Loc.getMessage('AI_COPILOT_USER_PROMPTS_MENU_SECTION'),
	      text: main_core.Loc.getMessage('AI_COPILOT_USER_PROMPTS_MENU_SECTION'),
	      isNew: true
	    }, ...getGeneralMenuItemsFromPrompts(userPrompts, copilotTextController, false), {
	      code: 'promptLib',
	      text: main_core.Loc.getMessage('AI_COPILOT_MENU_ITEM_AI_PROMPT_LIB'),
	      icon: ui_iconSet_api_core.Main.PROMPTS_LIBRARY,
	      highlightText: true,
	      command: async () => {
	        if (BX.SidePanel) {
	          copilotTextController.getAnalytics().setCategoryPromptSaving();
	          copilotTextController.getAnalytics().sendEventOpenPromptLibrary();
	          BX.SidePanel.Instance.open('/bitrix/components/bitrix/ai.prompt.library.grid/slider.php', {
	            cacheable: false,
	            events: {
	              onCloseStart: () => {
	                copilotTextController.getAnalytics().setCategoryText();
	                copilotTextController.updateGeneralMenuPrompts();
	              }
	            }
	          });
	        } else {
	          window.location.href = '/bitrix/components/bitrix/ai.prompt.library.grid/slider.php';
	        }
	      }
	    }] : []), ...getGeneralMenuItemsFromPrompts(systemPrompts, copilotTextController), ...getSelectedEngineMenuItem(engines, selectedEngineCode, copilotTextController, canEditSettings), {
	      code: 'about_open_copilot',
	      text: main_core.Loc.getMessage('AI_COPILOT_MENU_ITEM_ABOUT_COPILOT'),
	      icon: ui_iconSet_api_core.Main.INFO,
	      command: new OpenAboutCopilot()
	    }, {
	      code: 'feedback',
	      text: main_core.Loc.getMessage('AI_COPILOT_MENU_ITEM_AI_FEEDBACK'),
	      icon: ui_iconSet_api_core.Main.FEEDBACK,
	      command: new OpenFeedbackFormCommand({
	        copilotTextController,
	        category: copilotTextController.getCategory(),
	        isBeforeGeneration: false
	      })
	    }].filter(item => item);
	  }
	  static getMenuItem(prompt, prompts, copilotTextController, isFavouriteSection = false) {
	    let command = null;
	    if (prompt.required) {
	      command = prompt.type === 'simpleTemplate' ? new GenerateWithRequiredUserMessageCommand({
	        copilotTextController,
	        commandCode: prompt.code
	      }) : new GenerateWithoutRequiredUserMessage({
	        copilotTextController,
	        prompts,
	        commandCode: copilotTextController.getMenuItemCodeFromPrompt(prompt.code)
	      });
	    }
	    const code = isFavouriteSection ? copilotTextController.getMenuItemCodeFromFavouritePrompt(prompt.code) : prompt.code;
	    return {
	      id: code,
	      command,
	      code: prompt.code,
	      text: prompt.title,
	      children: getGeneralMenuItemsFromPrompts(prompt.children || [], copilotTextController),
	      separator: prompt.separator,
	      title: prompt.title,
	      icon: prompt.icon,
	      section: prompt.section,
	      isFavourite: copilotTextController.isReadonly() === true ? null : prompt.isFavorite,
	      isShowFavouriteIconOnHover: isFavouriteSection && copilotTextController.isReadonly() === false
	    };
	  }
	  static getFavouritePromptsSeparatorMenuItem() {
	    return {
	      code: 'favourite-prompts-items-separator',
	      separator: true,
	      title: main_core.Loc.getMessage('AI_COPILOT_FAVOURITE_PROMPTS_MENU_SECTION'),
	      text: main_core.Loc.getMessage('AI_COPILOT_FAVOURITE_PROMPTS_MENU_SECTION')
	    };
	  }
	}
	function getGeneralMenuItemsFromPrompts(prompts, copilotTextController, isFavouriteSection = false) {
	  return prompts.map(prompt => {
	    return CopilotGeneralMenuItems.getMenuItem(prompt, prompts, copilotTextController, isFavouriteSection);
	  }).filter(item => item.code !== 'zero_prompt');
	}
	function getSelectedEngineMenuItem(engines, selectedEngineCode, copilotTextController, canEditSettings = false) {
	  return [{
	    separator: true,
	    title: main_core.Loc.getMessage('AI_COPILOT_PROVIDER_MENU_SECTION'),
	    text: main_core.Loc.getMessage('AI_COPILOT_PROVIDER_MENU_SECTION')
	  }, {
	    id: 'provider',
	    code: 'provider',
	    text: main_core.Loc.getMessage('AI_COPILOT_MENU_ITEM_OPEN_COPILOT'),
	    children: CopilotProvidersMenuItems.getMenuItems({
	      engines,
	      selectedEngineCode,
	      canEditSettings,
	      copilotTextController
	    }),
	    icon: ui_iconSet_api_core.Main.COPILOT_AI
	  }];
	}

	class BaseMenuItem extends main_core_events.EventEmitter {
	  constructor(options) {
	    var _options$children;
	    super();
	    this.id = '';
	    this.setEventNamespace('AI.CopilotMenuItem');
	    if (options.id) {
	      this.id = options.id;
	    }
	    this.code = options.code;
	    this.text = options.text;
	    this.icon = options.icon;
	    this.href = options.href;
	    this.children = (_options$children = options.children) != null ? _options$children : [];
	    this.onClick = options.onClick;
	    this.disabled = options.disabled;
	  }
	  getOptions() {
	    return {
	      id: this.id,
	      code: this.code,
	      text: this.text,
	      icon: this.icon,
	      href: this.href,
	      command: this.onClick,
	      disabled: this.disabled,
	      children: this.children.map(childrenMenuItem => {
	        if (childrenMenuItem instanceof BaseMenuItem) {
	          return childrenMenuItem.getOptions();
	        }
	        return childrenMenuItem;
	      })
	    };
	  }
	}

	class AboutCopilotMenuItem extends BaseMenuItem {
	  constructor(options) {
	    super({
	      text: main_core.Loc.getMessage('AI_COPILOT_MENU_ITEM_ABOUT_COPILOT'),
	      icon: ui_iconSet_api_core.Main.INFO,
	      onClick: () => {
	        const articleCode = '19092894';
	        const Helper = main_core.Reflection.getClass('top.BX.Helper');
	        if (Helper) {
	          Helper.show(`redirect=detail&code=${articleCode}`);
	        }
	      },
	      ...options
	    });
	  }
	}

	class ChangeRequestMenuItem extends BaseMenuItem {
	  constructor(options) {
	    super({
	      text: main_core.Loc.getMessage('AI_COPILOT_READONLY_COMMAND_EDIT'),
	      icon: ui_iconSet_api_core.Main.EDIT_PENCIL,
	      ...options
	    });
	  }
	}

	var _getText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getText");
	class CopyResultMenuItem extends BaseMenuItem {
	  constructor(options) {
	    super({
	      text: main_core.Loc.getMessage('AI_COPILOT_COMMAND_COPY'),
	      onClick: (event, menuItem, menu) => {
	        const isCopyingSuccess = BX.clipboard.copy(babelHelpers.classPrivateFieldLooseBase(this, _getText)[_getText]());
	        if (isCopyingSuccess === false) {
	          return;
	        }
	        menu.markMenuItemSelected(menuItem.getId());
	        setTimeout(() => {
	          menu.unmarkMenuItemSelected(menuItem.getId());
	        }, 800);
	      },
	      ...options
	    });
	    Object.defineProperty(this, _getText, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _getText)[_getText] = options.getText;
	  }
	}

	var _isOpenBeforeGeneration = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isOpenBeforeGeneration");
	var _engine$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("engine");
	var _openFeedbackForm$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openFeedbackForm");
	class FeedbackMenuItem extends BaseMenuItem {
	  constructor(options) {
	    super({
	      code: 'feedback',
	      icon: ui_iconSet_api_core.Main.FEEDBACK,
	      text: main_core.Loc.getMessage('AI_COPILOT_MENU_ITEM_AI_FEEDBACK'),
	      onClick: async () => {
	        return babelHelpers.classPrivateFieldLooseBase(this, _openFeedbackForm$1)[_openFeedbackForm$1]();
	      },
	      ...options
	    });
	    Object.defineProperty(this, _openFeedbackForm$1, {
	      value: _openFeedbackForm2$1
	    });
	    Object.defineProperty(this, _isOpenBeforeGeneration, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _engine$1, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _isOpenBeforeGeneration)[_isOpenBeforeGeneration] = options.isBeforeGeneration;
	    babelHelpers.classPrivateFieldLooseBase(this, _engine$1)[_engine$1] = options.engine;
	  }
	}
	async function _openFeedbackForm2$1() {
	  var _data, _data$context_message, _data2, _data$author_message, _data3;
	  const senderPagePreset = `${babelHelpers.classPrivateFieldLooseBase(this, _engine$1)[_engine$1].getCategory()},${babelHelpers.classPrivateFieldLooseBase(this, _isOpenBeforeGeneration)[_isOpenBeforeGeneration] ? 'before' : 'after'}`;
	  let data = null;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isOpenBeforeGeneration)[_isOpenBeforeGeneration] === false) {
	    data = await babelHelpers.classPrivateFieldLooseBase(this, _engine$1)[_engine$1].getDataForFeedbackForm();
	  }
	  const contextMessages = ((_data = data) == null ? void 0 : (_data$context_message = _data.context_messages) == null ? void 0 : _data$context_message.length) > 0 ? (_data2 = data) == null ? void 0 : _data2.context_messages : undefined;
	  const authorMessage = (_data$author_message = (_data3 = data) == null ? void 0 : _data3.author_message) != null ? _data$author_message : undefined;
	  try {
	    var _data4, _data4$prompt, _data5, _data6, _data7, _data7$current_result, _data8, _data8$current_result;
	    await main_core.Runtime.loadExtension(['ui.feedback.form']);
	    BX.UI.Feedback.Form.open({
	      id: 'ai.copilot.feedback',
	      forms: [{
	        zones: ['es'],
	        id: 684,
	        lang: 'es',
	        sec: 'svvq1x'
	      }, {
	        zones: ['en'],
	        id: 686,
	        lang: 'en',
	        sec: 'tjwodz'
	      }, {
	        zones: ['de'],
	        id: 688,
	        lang: 'de',
	        sec: 'nrwksg'
	      }, {
	        zones: ['com.br'],
	        id: 690,
	        lang: 'com.br',
	        sec: 'kpte6m'
	      }, {
	        zones: ['ru', 'by', 'kz'],
	        id: 692,
	        lang: 'ru',
	        sec: 'jbujn0'
	      }],
	      presets: {
	        sender_page: senderPagePreset,
	        prompt_code: (_data4 = data) == null ? void 0 : (_data4$prompt = _data4.prompt) == null ? void 0 : _data4$prompt.code,
	        user_message: (_data5 = data) == null ? void 0 : _data5.user_message,
	        original_message: (_data6 = data) == null ? void 0 : _data6.original_message,
	        author_message: authorMessage,
	        context_messages: contextMessages,
	        last_result0: (_data7 = data) == null ? void 0 : (_data7$current_result = _data7.current_result) == null ? void 0 : _data7$current_result[1],
	        language: main_core.Loc.getMessage('LANGUAGE_ID'),
	        cp_answer: (_data8 = data) == null ? void 0 : (_data8$current_result = _data8.current_result) == null ? void 0 : _data8$current_result[0]
	      }
	    });
	  } catch (err) {
	    console.error(err);
	  }
	}

	class MarketMenuItem extends BaseMenuItem {
	  constructor(options) {
	    super({
	      icon: ui_iconSet_api_core.Main.MARKET_1,
	      text: main_core.Loc.getMessage('AI_COPILOT_SEARCH_IN_MARKET'),
	      href: '/market/collection/ai_provider_partner_crm/',
	      ...options
	    });
	  }
	}

	class OpenCopilotMenuItem extends BaseMenuItem {
	  constructor(options) {
	    super({
	      id: 'open-copilot',
	      code: 'open-copilot',
	      icon: ui_iconSet_api_core.Main.COPILOT_AI,
	      text: main_core.Loc.getMessage('AI_COPILOT_MENU_ITEM_OPEN_COPILOT'),
	      ...options
	    });
	  }
	}

	class ProviderMenuItem extends BaseMenuItem {
	  constructor(options) {
	    super({
	      icon: ui_iconSet_api_core.Main.ROBOT,
	      ...options
	    });
	    this.selected = options.selected === true;
	  }
	  getOptions() {
	    return {
	      ...super.getOptions(),
	      selected: this.selected
	    };
	  }
	}

	class RepeatCopilotMenuItem extends BaseMenuItem {
	  constructor(options) {
	    super({
	      icon: ui_iconSet_api_core.Actions.LEFT_SEMICIRCULAR_ANTICLOCKWISE_ARROW_1,
	      text: main_core.Loc.getMessage('AI_COPILOT_COMMAND_REPEAT'),
	      ...options
	    });
	  }
	}

	class SettingsMenuItem extends BaseMenuItem {
	  constructor(options) {
	    const settingsPageLink = main_core.Extension.getSettings('ai.copilot.copilot-text-controller').settingsPageLink;
	    super({
	      text: main_core.Loc.getMessage('AI_COPILOT_MENU_ITEM_AI_SETTINGS'),
	      icon: ui_iconSet_api_core.Main.SETTINGS,
	      href: settingsPageLink,
	      ...options
	    });
	  }
	}

	class CancelCopilotMenuItem extends BaseMenuItem {
	  constructor(options) {
	    super({
	      icon: ui_iconSet_api_core.Actions.CROSS_45,
	      text: main_core.Loc.getMessage('AI_COPILOT_COMMAND_CANCEL'),
	      ...options
	    });
	  }
	}

	class ConnectModelMenuItem extends BaseMenuItem {
	  constructor(options) {
	    super({
	      icon: ui_iconSet_api_core.Actions.PLUS_50,
	      text: main_core.Loc.getMessage('AI_COPILOT_COMMAND_CONNECT_AI'),
	      disabled: true,
	      ...options
	    });
	  }
	}

	var _engine$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("engine");
	var _inputField$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputField");
	var _resultField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resultField");
	var _copilotContainer$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotContainer");
	var _category$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("category");
	var _readonly = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("readonly");
	var _selectedEngineCode$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedEngineCode");
	var _selectedPromptCodeWithSimpleTemplate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedPromptCodeWithSimpleTemplate");
	var _generalMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("generalMenu");
	var _resultMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resultMenu");
	var _errorMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("errorMenu");
	var _selectedText$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedText");
	var _context$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("context");
	var _resultStack$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resultStack");
	var _currentGenerateRequestId$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentGenerateRequestId");
	var _errorsCount = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("errorsCount");
	var _generationResultText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("generationResultText");
	var _warningField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("warningField");
	var _addImageMenuItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addImageMenuItem");
	var _copilotInputEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotInputEvents");
	var _CopilotMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("CopilotMenu");
	var _copilotMenuEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotMenuEvents");
	var _analytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("analytics");
	var _currentRole = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentRole");
	var _rolesDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("rolesDialog");
	var _showResultInCopilot = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showResultInCopilot");
	var _inputFieldContainerClickEventHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputFieldContainerClickEventHandler");
	var _inputFieldSubmitEventHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputFieldSubmitEventHandler");
	var _inputFieldInputEventHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputFieldInputEventHandler");
	var _inputFieldGoOutFromBottomEventHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputFieldGoOutFromBottomEventHandler");
	var _inputFieldStartRecordingEventHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputFieldStartRecordingEventHandler");
	var _inputFieldStopRecordingEventHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputFieldStopRecordingEventHandler");
	var _inputFieldCancelLoadingEventHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputFieldCancelLoadingEventHandler");
	var _inputFieldAdjustHeightEventHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputFieldAdjustHeightEventHandler");
	var _toolingDataByCategory$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("toolingDataByCategory");
	var _subscribeToInputFieldEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeToInputFieldEvents");
	var _unsubscribeToInputFieldEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("unsubscribeToInputFieldEvents");
	var _handleInputContainerClickEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleInputContainerClickEvent");
	var _handleInputFieldGoOutFromBottomEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleInputFieldGoOutFromBottomEvent");
	var _handleInputFieldInputEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleInputFieldInputEvent");
	var _handleInputFieldStartRecordingEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleInputFieldStartRecordingEvent");
	var _handleInputFieldStopRecordingEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleInputFieldStopRecordingEvent");
	var _handleInputFieldCancelLoadingEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleInputFieldCancelLoadingEvent");
	var _handleInputFieldAdjustHeightEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleInputFieldAdjustHeightEvent");
	var _handleInputFieldSubmitEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleInputFieldSubmitEvent");
	var _adjustMenus = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("adjustMenus");
	var _getTooling$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTooling");
	var _getSelectedEngineCode$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSelectedEngineCode");
	var _initGeneralMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initGeneralMenu");
	var _setMenuItemPromptIsFavourite = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setMenuItemPromptIsFavourite");
	var _setMenuItemPromptFavourite = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setMenuItemPromptFavourite");
	var _unsetMenuItemPromptFavourite = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("unsetMenuItemPromptFavourite");
	var _showRolesDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showRolesDialog");
	var _getPromptTitleByCommandFromPrompts = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPromptTitleByCommandFromPrompts");
	var _setEnginePayload$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setEnginePayload");
	var _isCommandRequiredUserMessage$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isCommandRequiredUserMessage");
	var _isCommandRequiredContextMessage$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isCommandRequiredContextMessage");
	var _getPromptByCode$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPromptByCode");
	var _setSelectedEngine = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setSelectedEngine");
	var _addResultToStack$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addResultToStack");
	var _handleGenerateError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleGenerateError");
	var _initErrorMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initErrorMenu");
	var _openResultMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openResultMenu");
	var _initResultMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initResultMenu");
	var _getRoleInfoForMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getRoleInfoForMenu");
	var _useRole = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("useRole");
	var _getResultMenuItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getResultMenuItems");
	class CopilotTextController extends main_core_events.EventEmitter {
	  constructor(_options) {
	    super();
	    Object.defineProperty(this, _getResultMenuItems, {
	      value: _getResultMenuItems2
	    });
	    Object.defineProperty(this, _useRole, {
	      value: _useRole2
	    });
	    Object.defineProperty(this, _getRoleInfoForMenu, {
	      value: _getRoleInfoForMenu2
	    });
	    Object.defineProperty(this, _initResultMenu, {
	      value: _initResultMenu2
	    });
	    Object.defineProperty(this, _openResultMenu, {
	      value: _openResultMenu2
	    });
	    Object.defineProperty(this, _initErrorMenu, {
	      value: _initErrorMenu2
	    });
	    Object.defineProperty(this, _handleGenerateError, {
	      value: _handleGenerateError2
	    });
	    Object.defineProperty(this, _addResultToStack$1, {
	      value: _addResultToStack2$1
	    });
	    Object.defineProperty(this, _setSelectedEngine, {
	      value: _setSelectedEngine2
	    });
	    Object.defineProperty(this, _getPromptByCode$1, {
	      value: _getPromptByCode2$1
	    });
	    Object.defineProperty(this, _isCommandRequiredContextMessage$1, {
	      value: _isCommandRequiredContextMessage2$1
	    });
	    Object.defineProperty(this, _isCommandRequiredUserMessage$1, {
	      value: _isCommandRequiredUserMessage2$1
	    });
	    Object.defineProperty(this, _setEnginePayload$1, {
	      value: _setEnginePayload2$1
	    });
	    Object.defineProperty(this, _getPromptTitleByCommandFromPrompts, {
	      value: _getPromptTitleByCommandFromPrompts2
	    });
	    Object.defineProperty(this, _showRolesDialog, {
	      value: _showRolesDialog2
	    });
	    Object.defineProperty(this, _unsetMenuItemPromptFavourite, {
	      value: _unsetMenuItemPromptFavourite2
	    });
	    Object.defineProperty(this, _setMenuItemPromptFavourite, {
	      value: _setMenuItemPromptFavourite2
	    });
	    Object.defineProperty(this, _setMenuItemPromptIsFavourite, {
	      value: _setMenuItemPromptIsFavourite2
	    });
	    Object.defineProperty(this, _initGeneralMenu, {
	      value: _initGeneralMenu2
	    });
	    Object.defineProperty(this, _getSelectedEngineCode$1, {
	      value: _getSelectedEngineCode2$1
	    });
	    Object.defineProperty(this, _getTooling$1, {
	      value: _getTooling2$1
	    });
	    Object.defineProperty(this, _adjustMenus, {
	      value: _adjustMenus2
	    });
	    Object.defineProperty(this, _handleInputFieldSubmitEvent, {
	      value: _handleInputFieldSubmitEvent2
	    });
	    Object.defineProperty(this, _handleInputFieldAdjustHeightEvent, {
	      value: _handleInputFieldAdjustHeightEvent2
	    });
	    Object.defineProperty(this, _handleInputFieldCancelLoadingEvent, {
	      value: _handleInputFieldCancelLoadingEvent2
	    });
	    Object.defineProperty(this, _handleInputFieldStopRecordingEvent, {
	      value: _handleInputFieldStopRecordingEvent2
	    });
	    Object.defineProperty(this, _handleInputFieldStartRecordingEvent, {
	      value: _handleInputFieldStartRecordingEvent2
	    });
	    Object.defineProperty(this, _handleInputFieldInputEvent, {
	      value: _handleInputFieldInputEvent2
	    });
	    Object.defineProperty(this, _handleInputFieldGoOutFromBottomEvent, {
	      value: _handleInputFieldGoOutFromBottomEvent2
	    });
	    Object.defineProperty(this, _handleInputContainerClickEvent, {
	      value: _handleInputContainerClickEvent2
	    });
	    Object.defineProperty(this, _unsubscribeToInputFieldEvents, {
	      value: _unsubscribeToInputFieldEvents2
	    });
	    Object.defineProperty(this, _subscribeToInputFieldEvents, {
	      value: _subscribeToInputFieldEvents2
	    });
	    Object.defineProperty(this, _engine$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputField$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _resultField, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _copilotContainer$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _category$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _readonly, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _selectedEngineCode$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _selectedPromptCodeWithSimpleTemplate, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _generalMenu, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _resultMenu, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _errorMenu, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _selectedText$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _context$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _resultStack$1, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _currentGenerateRequestId$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _errorsCount, {
	      writable: true,
	      value: 0
	    });
	    Object.defineProperty(this, _generationResultText, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _warningField, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _addImageMenuItem, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _copilotInputEvents, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _CopilotMenu, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _copilotMenuEvents, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _analytics, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _currentRole, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _rolesDialog, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _showResultInCopilot, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputFieldContainerClickEventHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputFieldSubmitEventHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputFieldInputEventHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputFieldGoOutFromBottomEventHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputFieldStartRecordingEventHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputFieldStopRecordingEventHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputFieldCancelLoadingEventHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputFieldAdjustHeightEventHandler, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _engine$2)[_engine$2] = _options.engine;
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2] = _options.inputField;
	    babelHelpers.classPrivateFieldLooseBase(this, _category$2)[_category$2] = _options.category;
	    babelHelpers.classPrivateFieldLooseBase(this, _resultField)[_resultField] = _options.resultField;
	    babelHelpers.classPrivateFieldLooseBase(this, _readonly)[_readonly] = _options.readonly === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _warningField)[_warningField] = _options.warningField;
	    babelHelpers.classPrivateFieldLooseBase(this, _context$1)[_context$1] = _options.context;
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedText$1)[_selectedText$1] = _options.selectedText;
	    babelHelpers.classPrivateFieldLooseBase(this, _addImageMenuItem)[_addImageMenuItem] = _options.addImageMenuItem === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents] = _options.copilotInputEvents;
	    babelHelpers.classPrivateFieldLooseBase(this, _CopilotMenu)[_CopilotMenu] = _options.copilotMenu;
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotMenuEvents)[_copilotMenuEvents] = _options.copilotMenuEvents;
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics] = _options.analytics;
	    babelHelpers.classPrivateFieldLooseBase(this, _showResultInCopilot)[_showResultInCopilot] = _options.showResultInCopilot;
	    babelHelpers.classPrivateFieldLooseBase(this, _inputFieldContainerClickEventHandler)[_inputFieldContainerClickEventHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleInputContainerClickEvent)[_handleInputContainerClickEvent].bind(this);
	    babelHelpers.classPrivateFieldLooseBase(this, _inputFieldSubmitEventHandler)[_inputFieldSubmitEventHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleInputFieldSubmitEvent)[_handleInputFieldSubmitEvent].bind(this);
	    babelHelpers.classPrivateFieldLooseBase(this, _inputFieldInputEventHandler)[_inputFieldInputEventHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleInputFieldInputEvent)[_handleInputFieldInputEvent].bind(this);
	    babelHelpers.classPrivateFieldLooseBase(this, _inputFieldGoOutFromBottomEventHandler)[_inputFieldGoOutFromBottomEventHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleInputFieldGoOutFromBottomEvent)[_handleInputFieldGoOutFromBottomEvent].bind(this);
	    babelHelpers.classPrivateFieldLooseBase(this, _inputFieldStartRecordingEventHandler)[_inputFieldStartRecordingEventHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleInputFieldStartRecordingEvent)[_handleInputFieldStartRecordingEvent].bind(this);
	    babelHelpers.classPrivateFieldLooseBase(this, _inputFieldStopRecordingEventHandler)[_inputFieldStopRecordingEventHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleInputFieldStopRecordingEvent)[_handleInputFieldStopRecordingEvent].bind(this);
	    babelHelpers.classPrivateFieldLooseBase(this, _inputFieldCancelLoadingEventHandler)[_inputFieldCancelLoadingEventHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleInputFieldCancelLoadingEvent)[_handleInputFieldCancelLoadingEvent].bind(this);
	    babelHelpers.classPrivateFieldLooseBase(this, _inputFieldAdjustHeightEventHandler)[_inputFieldAdjustHeightEventHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleInputFieldAdjustHeightEvent)[_handleInputFieldAdjustHeightEvent].bind(this);
	    this.setEventNamespace('AI.Copilot.TextController');
	  }
	  setSelectedPromptCodeWithSimpleTemplate(code) {
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedPromptCodeWithSimpleTemplate)[_selectedPromptCodeWithSimpleTemplate] = code;
	  }
	  getSelectedPromptCodeWithSimpleTemplate() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _selectedPromptCodeWithSimpleTemplate)[_selectedPromptCodeWithSimpleTemplate];
	  }
	  setCopilotContainer(copilotContainer) {
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$2)[_copilotContainer$2] = copilotContainer;
	  }
	  setSelectedText(text) {
	    if (main_core.Type.isString(text)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _selectedText$1)[_selectedText$1] = text;
	    }
	  }
	  getSelectedText() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _selectedText$1)[_selectedText$1];
	  }
	  setContext(text) {
	    babelHelpers.classPrivateFieldLooseBase(this, _context$1)[_context$1] = text;
	  }
	  getContext() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _context$1)[_context$1];
	  }
	  setSelectedEngine(engineCode) {
	    babelHelpers.classPrivateFieldLooseBase(this, _setSelectedEngine)[_setSelectedEngine](engineCode);
	  }
	  setExtraMarkers(extraMarkers = {}) {
	    var _babelHelpers$classPr;
	    const payload = ((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _engine$2)[_engine$2]) == null ? void 0 : _babelHelpers$classPr.getPayload()) || new ai_engine.Text();
	    payload.setMarkers({
	      ...payload.getMarkers(),
	      ...extraMarkers
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _engine$2)[_engine$2].setPayload(payload);
	  }
	  async init() {
	    if (babelHelpers.classPrivateFieldLooseBase(CopilotTextController, _toolingDataByCategory$1)[_toolingDataByCategory$1][babelHelpers.classPrivateFieldLooseBase(this, _category$2)[_category$2]] === undefined) {
	      babelHelpers.classPrivateFieldLooseBase(CopilotTextController, _toolingDataByCategory$1)[_toolingDataByCategory$1][babelHelpers.classPrivateFieldLooseBase(this, _category$2)[_category$2]] = babelHelpers.classPrivateFieldLooseBase(this, _engine$2)[_engine$2].getTooling('text');
	    }
	    const res = await babelHelpers.classPrivateFieldLooseBase(CopilotTextController, _toolingDataByCategory$1)[_toolingDataByCategory$1][babelHelpers.classPrivateFieldLooseBase(this, _category$2)[_category$2]];
	    babelHelpers.classPrivateFieldLooseBase(CopilotTextController, _toolingDataByCategory$1)[_toolingDataByCategory$1][babelHelpers.classPrivateFieldLooseBase(this, _category$2)[_category$2]] = res;
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedEngineCode$1)[_selectedEngineCode$1] = babelHelpers.classPrivateFieldLooseBase(this, _getSelectedEngineCode$1)[_getSelectedEngineCode$1](res.data.engines);
	    babelHelpers.classPrivateFieldLooseBase(this, _currentRole)[_currentRole] = res.data.role;
	  }
	  openGeneralMenu() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _initGeneralMenu)[_initGeneralMenu]();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu].getPopup().subscribeFromOptions({
	      onBeforeShow: () => {
	        if (babelHelpers.classPrivateFieldLooseBase(this, _readonly)[_readonly]) {
	          babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].disable();
	        }
	      },
	      onAfterShow: () => {
	        if (babelHelpers.classPrivateFieldLooseBase(this, _readonly)[_readonly] === false) {
	          babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].enable();
	          babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].focus();
	        }
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu].setBindElement(babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$2)[_copilotContainer$2], {
	      top: 8
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu].open();
	    babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu].show();
	  }
	  clearResultStack() {
	    babelHelpers.classPrivateFieldLooseBase(this, _resultStack$1)[_resultStack$1] = [];
	  }
	  showMenu() {
	    var _babelHelpers$classPr2, _babelHelpers$classPr3, _babelHelpers$classPr4;
	    (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu]) == null ? void 0 : _babelHelpers$classPr2.show();
	    (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu]) == null ? void 0 : _babelHelpers$classPr3.show();
	    (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) == null ? void 0 : _babelHelpers$classPr4.show();
	  }
	  getOpenMenu() {
	    var _babelHelpers$classPr5, _babelHelpers$classPr6, _babelHelpers$classPr7;
	    if ((_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) != null && _babelHelpers$classPr5.isShown()) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu];
	    }
	    if ((_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu]) != null && _babelHelpers$classPr6.isShown()) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu];
	    }
	    if ((_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu]) != null && _babelHelpers$classPr7.isShown()) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu];
	    }
	    return null;
	  }
	  getAiResultText() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _generationResultText)[_generationResultText];
	  }
	  getCategory() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _category$2)[_category$2];
	  }
	  getLastCommandCode() {
	    var _babelHelpers$classPr8, _babelHelpers$classPr9;
	    return ((_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _engine$2)[_engine$2].getPayload().getRawData()) == null ? void 0 : (_babelHelpers$classPr9 = _babelHelpers$classPr8.prompt) == null ? void 0 : _babelHelpers$classPr9.code) || '';
	  }
	  isContainsElem(elem) {
	    var _babelHelpers$classPr10, _babelHelpers$classPr11, _babelHelpers$classPr12;
	    return ((_babelHelpers$classPr10 = babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) == null ? void 0 : _babelHelpers$classPr10.contains(elem)) || ((_babelHelpers$classPr11 = babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu]) == null ? void 0 : _babelHelpers$classPr11.contains(elem)) || ((_babelHelpers$classPr12 = babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu]) == null ? void 0 : _babelHelpers$classPr12.contains(elem));
	  }
	  generateWithRequiredUserMessage(commandCode, promptText) {
	    if (promptText) {
	      babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].setHtmlContent(promptText);
	    }
	    this.setSelectedPromptCodeWithSimpleTemplate(commandCode);
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].focus(true);
	  }
	  generateWithoutRequiredUserMessage(commandCode, prompts) {
	    babelHelpers.classPrivateFieldLooseBase(this, _setEnginePayload$1)[_setEnginePayload$1]({
	      command: commandCode,
	      markers: {
	        originalMessage: babelHelpers.classPrivateFieldLooseBase(this, _selectedText$1)[_selectedText$1] || babelHelpers.classPrivateFieldLooseBase(this, _context$1)[_context$1],
	        userMessage: babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].getValue()
	      }
	    });
	    const commandTextForInputField = babelHelpers.classPrivateFieldLooseBase(this, _getPromptTitleByCommandFromPrompts)[_getPromptTitleByCommandFromPrompts](prompts, commandCode);
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].setValue(commandTextForInputField);
	    this.generate();
	  }
	  hideAllMenus() {
	    var _babelHelpers$classPr13, _babelHelpers$classPr14, _babelHelpers$classPr15, _babelHelpers$classPr16;
	    (_babelHelpers$classPr13 = babelHelpers.classPrivateFieldLooseBase(this, _rolesDialog)[_rolesDialog]) == null ? void 0 : _babelHelpers$classPr13.hide();
	    babelHelpers.classPrivateFieldLooseBase(this, _rolesDialog)[_rolesDialog] = null;
	    (_babelHelpers$classPr14 = babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) == null ? void 0 : _babelHelpers$classPr14.hide();
	    (_babelHelpers$classPr15 = babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu]) == null ? void 0 : _babelHelpers$classPr15.hide();
	    (_babelHelpers$classPr16 = babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu]) == null ? void 0 : _babelHelpers$classPr16.hide();
	  }
	  destroyAllMenus() {
	    var _babelHelpers$classPr17, _babelHelpers$classPr18, _babelHelpers$classPr19, _babelHelpers$classPr20;
	    (_babelHelpers$classPr17 = babelHelpers.classPrivateFieldLooseBase(this, _rolesDialog)[_rolesDialog]) == null ? void 0 : _babelHelpers$classPr17.hide();
	    babelHelpers.classPrivateFieldLooseBase(this, _rolesDialog)[_rolesDialog] = null;
	    (_babelHelpers$classPr18 = babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) == null ? void 0 : _babelHelpers$classPr18.close();
	    (_babelHelpers$classPr19 = babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu]) == null ? void 0 : _babelHelpers$classPr19.close();
	    (_babelHelpers$classPr20 = babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu]) == null ? void 0 : _babelHelpers$classPr20.close();
	    babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu] = null;
	  }
	  start() {
	    this.openGeneralMenu();
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeToInputFieldEvents)[_subscribeToInputFieldEvents]();
	  }
	  async updateGeneralMenuPrompts() {
	    try {
	      var _babelHelpers$classPr21, _babelHelpers$classPr22;
	      (_babelHelpers$classPr21 = babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) == null ? void 0 : _babelHelpers$classPr21.setLoader();
	      const res = await babelHelpers.classPrivateFieldLooseBase(this, _engine$2)[_engine$2].getTooling('text');
	      babelHelpers.classPrivateFieldLooseBase(CopilotTextController, _toolingDataByCategory$1)[_toolingDataByCategory$1][babelHelpers.classPrivateFieldLooseBase(this, _category$2)[_category$2]] = res;
	      const {
	        promptsOther,
	        promptsSystem,
	        promptsFavorite,
	        engines,
	        permissions
	      } = res.data;
	      const items = CopilotGeneralMenuItems.getMenuItems({
	        userPrompts: promptsOther,
	        systemPrompts: promptsSystem,
	        favouritePrompts: promptsFavorite,
	        engines,
	        selectedEngineCode: babelHelpers.classPrivateFieldLooseBase(this, _selectedEngineCode$1)[_selectedEngineCode$1],
	        canEditSettings: permissions.can_edit_settings === true,
	        copilotTextController: this,
	        addImageMenuItem: babelHelpers.classPrivateFieldLooseBase(this, _addImageMenuItem)[_addImageMenuItem]
	      });
	      (_babelHelpers$classPr22 = babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) == null ? void 0 : _babelHelpers$classPr22.updateMenuItemsExceptRoleItem(items);
	    } catch (e) {
	      console.error(e);
	      ui_notification.UI.Notification.Center.notify({
	        id: 'update-copilot-menu-error',
	        content: main_core.Loc.getMessage('AI_COPILOT_UPDATE_MENU_ERROR')
	      });
	    } finally {
	      var _babelHelpers$classPr23;
	      (_babelHelpers$classPr23 = babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) == null ? void 0 : _babelHelpers$classPr23.removeLoader();
	    }
	  }
	  isFirstLaunch() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]().first_launch;
	  }
	  finish() {
	    this.reset();
	    this.destroyAllMenus();
	    babelHelpers.classPrivateFieldLooseBase(this, _unsubscribeToInputFieldEvents)[_unsubscribeToInputFieldEvents]();
	  }
	  reset() {
	    var _babelHelpers$classPr24;
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedText$1)[_selectedText$1] = '';
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedPromptCodeWithSimpleTemplate)[_selectedPromptCodeWithSimpleTemplate] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _context$1)[_context$1] = '';
	    babelHelpers.classPrivateFieldLooseBase(this, _currentGenerateRequestId$1)[_currentGenerateRequestId$1] = -1;
	    babelHelpers.classPrivateFieldLooseBase(this, _resultStack$1)[_resultStack$1] = [];
	    (_babelHelpers$classPr24 = babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2]) == null ? void 0 : _babelHelpers$classPr24.clear();
	  }
	  isInitFinished() {
	    return Boolean(babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]());
	  }
	  isPromptsLoaded() {
	    var _babelHelpers$classPr25;
	    return Boolean((_babelHelpers$classPr25 = babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]()) == null ? void 0 : _babelHelpers$classPr25.promptsOther);
	  }
	  clearResultField() {
	    babelHelpers.classPrivateFieldLooseBase(this, _resultField)[_resultField].clearResult();
	    babelHelpers.classPrivateFieldLooseBase(this, _adjustMenus)[_adjustMenus]();
	  }
	  isReadonly() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _readonly)[_readonly] === true;
	  }
	  // eslint-disable-next-line consistent-return
	  async getDataForFeedbackForm() {
	    try {
	      const feedDataResult = await babelHelpers.classPrivateFieldLooseBase(this, _engine$2)[_engine$2].getFeedbackData();
	      const messages = feedDataResult.data.context_messages;
	      const authorMessage = feedDataResult.data.original_message;
	      const payload = babelHelpers.classPrivateFieldLooseBase(this, _engine$2)[_engine$2].getPayload();
	      return payload ? {
	        context_messages: messages,
	        author_message: authorMessage,
	        ...payload.getRawData(),
	        ...payload.getMarkers()
	      } : {};
	    } catch (error) {
	      console.error(error);
	      const payload = babelHelpers.classPrivateFieldLooseBase(this, _engine$2)[_engine$2].getPayload();
	      return payload ? {
	        ...payload.getRawData(),
	        ...payload.getMarkers()
	      } : {};
	    }
	  }
	  async setPromptIsFavourite(promptCode, isFavourite) {
	    try {
	      babelHelpers.classPrivateFieldLooseBase(this, _setMenuItemPromptIsFavourite)[_setMenuItemPromptIsFavourite](promptCode, isFavourite);
	      const data = new FormData();
	      data.append('promptCode', promptCode);
	      const action = isFavourite ? 'addInFavoriteList' : 'deleteFromFavoriteList';
	      await main_core.ajax.runAction(`ai.prompt.${action}`, {
	        data
	      });
	    } catch (error) {
	      const prompts = [...babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]().promptsOther, ...babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]().promptsSystem];
	      const searchPrompt = babelHelpers.classPrivateFieldLooseBase(this, _getPromptByCode$1)[_getPromptByCode$1](prompts, promptCode);
	      const message = isFavourite ? main_core.Loc.getMessage('AI_COPILOT_ADD_PROMPT_TO_FAVOURITE_ERROR', {
	        '#NAME#': searchPrompt.title
	      }) : main_core.Loc.getMessage('AI_COPILOT_REMOVE_PROMPT_FROM_FAVOURITE_ERROR', {
	        '#NAME#': searchPrompt.title
	      });
	      ui_notification.UI.Notification.Center.notify({
	        id: `set-favourite-error-${searchPrompt.code}`,
	        content: message,
	        autoHide: true
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _setMenuItemPromptIsFavourite)[_setMenuItemPromptIsFavourite](promptCode, !isFavourite);
	      console.error(error);
	    }
	  }
	  getMenuItemCodeFromPrompt(promptCode) {
	    return promptCode;
	  }
	  getMenuItemCodeFromFavouritePrompt(promptCode) {
	    return `${promptCode}:favourite`;
	  }
	  async generate() {
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].startGenerating();
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$2)[_copilotContainer$2], '--error');
	    this.destroyAllMenus();
	    const id = Math.round(Math.random() * 10000);
	    babelHelpers.classPrivateFieldLooseBase(this, _currentGenerateRequestId$1)[_currentGenerateRequestId$1] = id;
	    try {
	      var _babelHelpers$classPr28;
	      const res = await babelHelpers.classPrivateFieldLooseBase(this, _engine$2)[_engine$2].textCompletions();
	      const result = res.data.result || res.data.last.data;
	      if (babelHelpers.classPrivateFieldLooseBase(this, _currentGenerateRequestId$1)[_currentGenerateRequestId$1] !== id) {
	        return;
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].finishGenerating();
	      babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].disable();
	      babelHelpers.classPrivateFieldLooseBase(this, _generationResultText)[_generationResultText] = res.data.result;
	      if (babelHelpers.classPrivateFieldLooseBase(this, _showResultInCopilot)[_showResultInCopilot] === true || babelHelpers.classPrivateFieldLooseBase(this, _showResultInCopilot)[_showResultInCopilot] === undefined && babelHelpers.classPrivateFieldLooseBase(this, _selectedText$1)[_selectedText$1] || babelHelpers.classPrivateFieldLooseBase(this, _readonly)[_readonly]) {
	        var _babelHelpers$classPr26, _babelHelpers$classPr27;
	        (_babelHelpers$classPr26 = babelHelpers.classPrivateFieldLooseBase(this, _resultField)[_resultField]) == null ? void 0 : _babelHelpers$classPr26.clearResult();
	        (_babelHelpers$classPr27 = babelHelpers.classPrivateFieldLooseBase(this, _resultField)[_resultField]) == null ? void 0 : _babelHelpers$classPr27.addResult(babelHelpers.classPrivateFieldLooseBase(this, _generationResultText)[_generationResultText]);
	      } else {
	        this.emit('aiResult', {
	          result
	        });
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _addResultToStack$1)[_addResultToStack$1](result);
	      (_babelHelpers$classPr28 = babelHelpers.classPrivateFieldLooseBase(this, _warningField)[_warningField]) == null ? void 0 : _babelHelpers$classPr28.expand();
	      babelHelpers.classPrivateFieldLooseBase(this, _openResultMenu)[_openResultMenu]();
	    } catch (res) {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _currentGenerateRequestId$1)[_currentGenerateRequestId$1] !== id) {
	        return;
	      }
	      this.getAnalytics().sendEventError();
	      babelHelpers.classPrivateFieldLooseBase(this, _handleGenerateError)[_handleGenerateError](res);
	    }
	  }
	  adjustMenusPosition() {
	    var _babelHelpers$classPr29, _babelHelpers$classPr30, _babelHelpers$classPr31;
	    (_babelHelpers$classPr29 = babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) == null ? void 0 : _babelHelpers$classPr29.adjustPosition();
	    (_babelHelpers$classPr30 = babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu]) == null ? void 0 : _babelHelpers$classPr30.adjustPosition();
	    (_babelHelpers$classPr31 = babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu]) == null ? void 0 : _babelHelpers$classPr31.adjustPosition();
	  }
	  // eslint-disable-next-line sonarjs/cognitive-complexity
	  getAnalytics() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _selectedText$1)[_selectedText$1] || babelHelpers.classPrivateFieldLooseBase(this, _selectedText$1)[_selectedText$1] === '') {
	      babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setTypeTextNew();
	    } else if (babelHelpers.classPrivateFieldLooseBase(this, _readonly)[_readonly]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setTypeTextEdit();
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setTypeTextReply();
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _engine$2)[_engine$2] && babelHelpers.classPrivateFieldLooseBase(this, _engine$2)[_engine$2].getPayload()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setP1('prompt', this.getLastCommandCode()).setP2('provider', babelHelpers.classPrivateFieldLooseBase(this, _selectedEngineCode$1)[_selectedEngineCode$1]);
	    }
	    const usedTextInput = babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].usedTextInput();
	    const usedVoiceRecord = babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].usedVoiceRecord();
	    if (usedTextInput && usedVoiceRecord) {
	      babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setContextTypeFromTextAndAudio();
	    } else if (usedTextInput) {
	      babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setContextTypeFromText();
	    } else if (usedVoiceRecord) {
	      babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setContextTypeFromAudio();
	    }
	    if (this.getSelectedText()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setContextElementPopupButton();
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setContextElementSpaceButton();
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _readonly)[_readonly]) {
	      if (this.getSelectedText()) {
	        babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setContextElementReadonlyQuote();
	      } else {
	        babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setContextElementReadonlyCommon();
	      }
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics];
	  }
	  async showPromptMasterPopup() {
	    const {
	      PromptMasterPopup,
	      PromptMasterPopupEvents
	    } = await main_core.Runtime.loadExtension('ai.prompt-master');
	    const popup = new PromptMasterPopup({
	      masterOptions: {
	        prompt: babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].getValue()
	      },
	      popupEvents: {
	        onPopupShow: () => {
	          var _ref;
	          this.emit('prompt-master-show');
	          (_ref = this === null || this === void 0 ? void 0 : babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu]) == null ? void 0 : _ref.disableArrowsKey();
	        },
	        onPopupDestroy: () => {
	          this.emit('prompt-master-destroy');
	        }
	      },
	      analyticFields: {
	        c_section: babelHelpers.classPrivateFieldLooseBase(this, _category$2)[_category$2]
	      }
	    });
	    popup.subscribe(PromptMasterPopupEvents.SAVE_SUCCESS, () => {
	      this.updateGeneralMenuPrompts();
	    });
	    popup.show();
	  }
	}
	function _subscribeToInputFieldEvents2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].subscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].containerClick, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldContainerClickEventHandler)[_inputFieldContainerClickEventHandler]);
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].subscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].submit, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldSubmitEventHandler)[_inputFieldSubmitEventHandler]);
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].subscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].input, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldInputEventHandler)[_inputFieldInputEventHandler]);
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].subscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].goOutFromBottom, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldGoOutFromBottomEventHandler)[_inputFieldGoOutFromBottomEventHandler]);
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].subscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].startRecording, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldStartRecordingEventHandler)[_inputFieldStartRecordingEventHandler]);
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].subscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].stopRecording, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldStopRecordingEventHandler)[_inputFieldStopRecordingEventHandler]);
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].subscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].cancelLoading, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldCancelLoadingEventHandler)[_inputFieldCancelLoadingEventHandler]);
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].subscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].adjustHeight, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldAdjustHeightEventHandler)[_inputFieldAdjustHeightEventHandler]);
	}
	function _unsubscribeToInputFieldEvents2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].unsubscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].containerClick, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldContainerClickEventHandler)[_inputFieldContainerClickEventHandler]);
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].unsubscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].submit, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldSubmitEventHandler)[_inputFieldSubmitEventHandler]);
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].unsubscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].input, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldInputEventHandler)[_inputFieldInputEventHandler]);
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].unsubscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].goOutFromBottom, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldGoOutFromBottomEventHandler)[_inputFieldGoOutFromBottomEventHandler]);
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].unsubscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].startRecording, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldStartRecordingEventHandler)[_inputFieldStartRecordingEventHandler]);
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].unsubscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].stopRecording, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldStopRecordingEventHandler)[_inputFieldStopRecordingEventHandler]);
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].unsubscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].cancelLoading, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldCancelLoadingEventHandler)[_inputFieldCancelLoadingEventHandler]);
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].unsubscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].adjustHeight, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldAdjustHeightEventHandler)[_inputFieldAdjustHeightEventHandler]);
	}
	function _handleInputContainerClickEvent2() {
	  var _babelHelpers$classPr32;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].isDisabled() && (_babelHelpers$classPr32 = babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu]) != null && _babelHelpers$classPr32.isShown() && babelHelpers.classPrivateFieldLooseBase(this, _readonly)[_readonly] === false) {
	    const editCommand = new EditResultCommand({
	      copilotTextController: this,
	      inputField: babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2]
	    });
	    editCommand.execute();
	  }
	}
	function _handleInputFieldGoOutFromBottomEvent2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu].enableArrowsKey();
	}
	function _handleInputFieldInputEvent2(e) {
	  var _babelHelpers$classPr33;
	  const text = e.getData();
	  if (!text) {
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedPromptCodeWithSimpleTemplate)[_selectedPromptCodeWithSimpleTemplate] = null;
	  }
	  (_babelHelpers$classPr33 = babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) == null ? void 0 : _babelHelpers$classPr33.disableArrowsKey();
	  requestAnimationFrame(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _adjustMenus)[_adjustMenus]();
	  });
	}
	function _handleInputFieldStartRecordingEvent2() {
	  var _babelHelpers$classPr34;
	  (_babelHelpers$classPr34 = babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) == null ? void 0 : _babelHelpers$classPr34.hide();
	}
	function _handleInputFieldStopRecordingEvent2() {
	  var _babelHelpers$classPr35;
	  (_babelHelpers$classPr35 = babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) == null ? void 0 : _babelHelpers$classPr35.show();
	}
	function _handleInputFieldCancelLoadingEvent2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _currentGenerateRequestId$1)[_currentGenerateRequestId$1] = -1;
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].finishGenerating();
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].focus();
	  if (babelHelpers.classPrivateFieldLooseBase(this, _readonly)[_readonly]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].clear();
	  }
	  this.openGeneralMenu();
	}
	function _handleInputFieldAdjustHeightEvent2() {
	  setTimeout(() => {
	    // this.#adjustMenus();
	  }, 150);
	}
	function _handleInputFieldSubmitEvent2() {
	  const userPrompt = babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].getValue();
	  if (!userPrompt) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _setEnginePayload$1)[_setEnginePayload$1]({
	    command: 'zero_prompt',
	    markers: {
	      userMessage: userPrompt,
	      originalMessage: babelHelpers.classPrivateFieldLooseBase(this, _selectedText$1)[_selectedText$1] || babelHelpers.classPrivateFieldLooseBase(this, _context$1)[_context$1] || '',
	      current_result: babelHelpers.classPrivateFieldLooseBase(this, _resultStack$1)[_resultStack$1]
	    }
	  });
	  this.generate();
	}
	function _adjustMenus2() {
	  var _babelHelpers$classPr36;
	  (_babelHelpers$classPr36 = babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) == null ? void 0 : _babelHelpers$classPr36.adjustPosition();
	}
	function _getTooling2$1() {
	  var _babelHelpers$classPr37;
	  return (_babelHelpers$classPr37 = babelHelpers.classPrivateFieldLooseBase(CopilotTextController, _toolingDataByCategory$1)[_toolingDataByCategory$1][babelHelpers.classPrivateFieldLooseBase(this, _category$2)[_category$2]]) == null ? void 0 : _babelHelpers$classPr37.data;
	}
	function _getSelectedEngineCode2$1(engines) {
	  var _engines$;
	  const selectedEngine = engines.find(engine => engine.selected);
	  return (selectedEngine == null ? void 0 : selectedEngine.code) || ((_engines$ = engines[0]) == null ? void 0 : _engines$.code);
	}
	function _initGeneralMenu2() {
	  const {
	    promptsOther: userPrompts,
	    promptsSystem: systemPrompts,
	    promptsFavorite: favouritePrompts,
	    engines,
	    permissions
	  } = babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]();
	  babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu] = new (babelHelpers.classPrivateFieldLooseBase(this, _CopilotMenu)[_CopilotMenu])({
	    roleInfo: babelHelpers.classPrivateFieldLooseBase(this, _getRoleInfoForMenu)[_getRoleInfoForMenu]({
	      withOpenRolesDialogAction: true,
	      subtitle: main_core.Loc.getMessage('AI_COPILOT_GENERAL_MENU_ROLE_SUBTITLE')
	    }),
	    items: CopilotGeneralMenuItems.getMenuItems({
	      userPrompts,
	      systemPrompts,
	      favouritePrompts,
	      engines,
	      selectedEngineCode: babelHelpers.classPrivateFieldLooseBase(this, _selectedEngineCode$1)[_selectedEngineCode$1],
	      canEditSettings: permissions.can_edit_settings === true,
	      copilotTextController: this,
	      addImageMenuItem: babelHelpers.classPrivateFieldLooseBase(this, _addImageMenuItem)[_addImageMenuItem]
	    }),
	    keyboardControlOptions: {
	      clearHighlightAfterType: babelHelpers.classPrivateFieldLooseBase(this, _readonly)[_readonly] === false,
	      canGoOutFromTop: babelHelpers.classPrivateFieldLooseBase(this, _readonly)[_readonly] === false,
	      highlightFirstItemAfterShow: babelHelpers.classPrivateFieldLooseBase(this, _readonly)[_readonly] === true
	    },
	    forceTop: true,
	    cacheable: false
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu].subscribe('set-favourite', async e => {
	    const isFavourite = e.getData().isFavourite;
	    const promptCode = e.getData().promptCode;
	    await this.setPromptIsFavourite(promptCode, isFavourite);
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu].subscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotMenuEvents)[_copilotMenuEvents].clearHighlight, () => {
	    var _babelHelpers$classPr38;
	    (_babelHelpers$classPr38 = babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) == null ? void 0 : _babelHelpers$classPr38.disableArrowsKey();
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].enableEnterAndArrows();
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu].subscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotMenuEvents)[_copilotMenuEvents].highlightMenuItem, () => {
	    var _babelHelpers$classPr39;
	    (_babelHelpers$classPr39 = babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) == null ? void 0 : _babelHelpers$classPr39.enableArrowsKey();
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].disableEnterAndArrows();
	  });
	}
	function _setMenuItemPromptIsFavourite2(promptCode, isFavourite) {
	  if (isFavourite) {
	    babelHelpers.classPrivateFieldLooseBase(this, _setMenuItemPromptFavourite)[_setMenuItemPromptFavourite](promptCode);
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _unsetMenuItemPromptFavourite)[_unsetMenuItemPromptFavourite](promptCode);
	  }
	}
	function _setMenuItemPromptFavourite2(promptCode) {
	  const prompts = [...babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]().promptsOther, ...babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]().promptsSystem];
	  const searchPrompt = babelHelpers.classPrivateFieldLooseBase(this, _getPromptByCode$1)[_getPromptByCode$1](prompts, promptCode);
	  if (babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]().promptsFavorite.length === 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu].insertItemAfterRole(CopilotGeneralMenuItems.getFavouritePromptsSeparatorMenuItem());
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]().promptsFavorite.push(searchPrompt);
	  searchPrompt.isFavorite = true;
	  const copilotMenuItem = CopilotGeneralMenuItems.getMenuItem(searchPrompt, prompts, this, true);
	  babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu].insertItemAfter(CopilotGeneralMenuItems.getFavouritePromptsSeparatorMenuItem().code, copilotMenuItem);
	  babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu].setItemIsFavourite(this.getMenuItemCodeFromPrompt(promptCode), true);
	}
	function _unsetMenuItemPromptFavourite2(promptCode) {
	  const prompts = [...babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]().promptsOther, ...babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]().promptsSystem];
	  const searchPrompt = babelHelpers.classPrivateFieldLooseBase(this, _getPromptByCode$1)[_getPromptByCode$1](prompts, promptCode);
	  searchPrompt.isFavorite = false;
	  const searchPromptIndexInFavouriteList = babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]().promptsFavorite.findIndex(prompt => {
	    return prompt.code === searchPrompt.code;
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]().promptsFavorite.splice(searchPromptIndexInFavouriteList, 1);
	  if (babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]().promptsFavorite.length === 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu].removeItem(CopilotGeneralMenuItems.getFavouritePromptsSeparatorMenuItem().code);
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu].removeItem(this.getMenuItemCodeFromFavouritePrompt(promptCode));
	  babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu].setItemIsFavourite(this.getMenuItemCodeFromPrompt(promptCode), false);
	}
	async function _showRolesDialog2() {
	  var _babelHelpers$classPr40;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _rolesDialog)[_rolesDialog]) {
	    return Promise.resolve();
	  }
	  await main_core.Runtime.loadExtension('ui.vue3');
	  const {
	    RolesDialog,
	    RolesDialogEvents
	  } = await main_core.Runtime.loadExtension('ai.roles-dialog');
	  const dialogOptions = {
	    moduleId: babelHelpers.classPrivateFieldLooseBase(this, _engine$2)[_engine$2].getModuleId(),
	    contextId: babelHelpers.classPrivateFieldLooseBase(this, _engine$2)[_engine$2].getContextId(),
	    selectedRoleCode: (_babelHelpers$classPr40 = babelHelpers.classPrivateFieldLooseBase(this, _currentRole)[_currentRole]) == null ? void 0 : _babelHelpers$classPr40.code,
	    title: main_core.Loc.getMessage('AI_COPILOT_ROLES_DIALOG_TITLE')
	  };
	  babelHelpers.classPrivateFieldLooseBase(this, _rolesDialog)[_rolesDialog] = new RolesDialog(dialogOptions);
	  babelHelpers.classPrivateFieldLooseBase(this, _rolesDialog)[_rolesDialog].subscribe(RolesDialogEvents.SELECT_ROLE, e => {
	    var _babelHelpers$classPr41, _babelHelpers$classPr42;
	    const role = e.getData().role;
	    babelHelpers.classPrivateFieldLooseBase(this, _currentRole)[_currentRole] = role;
	    (_babelHelpers$classPr41 = babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu]) == null ? void 0 : _babelHelpers$classPr41.updateRoleInfo(role);
	    if ((_babelHelpers$classPr42 = babelHelpers.classPrivateFieldLooseBase(this, _rolesDialog)[_rolesDialog]) != null && _babelHelpers$classPr42.hide) {
	      babelHelpers.classPrivateFieldLooseBase(this, _rolesDialog)[_rolesDialog].hide();
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _rolesDialog)[_rolesDialog].subscribe(RolesDialogEvents.HIDE, () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _rolesDialog)[_rolesDialog] = null;
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _rolesDialog)[_rolesDialog].show();
	}
	function _getPromptTitleByCommandFromPrompts2(prompts, command) {
	  let result = '';
	  for (const currentPrompt of prompts) {
	    if (currentPrompt.code === command) {
	      result = currentPrompt.title;
	      break;
	    }
	    const promptChildren = currentPrompt.children;
	    if (promptChildren && promptChildren.length > 0) {
	      const promptTitle = babelHelpers.classPrivateFieldLooseBase(this, _getPromptTitleByCommandFromPrompts)[_getPromptTitleByCommandFromPrompts](promptChildren, command);
	      if (promptTitle) {
	        result = `${currentPrompt.title} - ${promptTitle}`;
	        break;
	      }
	    }
	  }
	  return result;
	}
	function _setEnginePayload2$1(options = {}) {
	  var _babelHelpers$classPr43, _babelHelpers$classPr44, _babelHelpers$classPr45;
	  const command = options.command || '';
	  const markers = options.markers || {};
	  const userMessage = markers.userMessage || undefined;
	  const originalMessage = markers.originalMessage || undefined;
	  const payload = new ai_engine.Text({
	    prompt: {
	      code: command
	    },
	    engineCode: babelHelpers.classPrivateFieldLooseBase(this, _selectedEngineCode$1)[_selectedEngineCode$1],
	    roleCode: babelHelpers.classPrivateFieldLooseBase(this, _useRole)[_useRole]() ? (_babelHelpers$classPr43 = babelHelpers.classPrivateFieldLooseBase(this, _currentRole)[_currentRole]) == null ? void 0 : _babelHelpers$classPr43.code : undefined
	  });
	  const oldPayloadMarkers = (_babelHelpers$classPr44 = (_babelHelpers$classPr45 = babelHelpers.classPrivateFieldLooseBase(this, _engine$2)[_engine$2].getPayload()) == null ? void 0 : _babelHelpers$classPr45.getMarkers()) != null ? _babelHelpers$classPr44 : {};
	  payload.setMarkers({
	    ...oldPayloadMarkers,
	    original_message: babelHelpers.classPrivateFieldLooseBase(this, _isCommandRequiredContextMessage$1)[_isCommandRequiredContextMessage$1](command) ? originalMessage : undefined,
	    user_message: babelHelpers.classPrivateFieldLooseBase(this, _isCommandRequiredUserMessage$1)[_isCommandRequiredUserMessage$1](command) ? userMessage : undefined,
	    current_result: babelHelpers.classPrivateFieldLooseBase(this, _resultStack$1)[_resultStack$1]
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _engine$2)[_engine$2].setPayload(payload);
	  const analytic = this.getAnalytics();
	  babelHelpers.classPrivateFieldLooseBase(this, _engine$2)[_engine$2].setAnalyticParameters({
	    category: analytic.getCategory(),
	    type: analytic.getType(),
	    c_sub_section: analytic.getCSubSection(),
	    c_element: analytic.getCElement()
	  });
	}
	function _isCommandRequiredUserMessage2$1(commandCode) {
	  const prompts = [...babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]().promptsOther, ...babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]().promptsSystem];
	  const searchPrompt = babelHelpers.classPrivateFieldLooseBase(this, _getPromptByCode$1)[_getPromptByCode$1](prompts, commandCode);
	  if (!searchPrompt) {
	    return false;
	  }
	  return searchPrompt.required.user_message || searchPrompt.type === 'simpleTemplate';
	}
	function _isCommandRequiredContextMessage2$1(commandCode) {
	  const prompts = [...babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]().promptsOther, ...babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]().promptsSystem];
	  const searchPrompt = babelHelpers.classPrivateFieldLooseBase(this, _getPromptByCode$1)[_getPromptByCode$1](prompts, commandCode);
	  if (!searchPrompt) {
	    return false;
	  }
	  return searchPrompt.required.context_message;
	}
	function _getPromptByCode2$1(prompts, commandCode) {
	  let searchPrompt = null;
	  prompts.some(prompt => {
	    var _prompt$children;
	    if (prompt.code === commandCode) {
	      searchPrompt = prompt;
	      return true;
	    }
	    return (_prompt$children = prompt.children) == null ? void 0 : _prompt$children.some(childrenPrompt => {
	      if (childrenPrompt.code === commandCode) {
	        searchPrompt = childrenPrompt;
	        return true;
	      }
	      return false;
	    });
	  });
	  return searchPrompt;
	}
	function _setSelectedEngine2(engineCode) {
	  const data = babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]();
	  babelHelpers.classPrivateFieldLooseBase(this, _selectedEngineCode$1)[_selectedEngineCode$1] = engineCode;
	  babelHelpers.classPrivateFieldLooseBase(this, _generalMenu)[_generalMenu].replaceMenuItemSubmenu({
	    code: 'provider',
	    children: CopilotProvidersMenuItems.getMenuItems({
	      engines: data.engines,
	      selectedEngineCode: engineCode,
	      canEditSettings: data.permissions.can_edit_settings,
	      copilotTextController: this
	    })
	  });
	}
	function _addResultToStack2$1(result) {
	  const stackSize = 3;
	  babelHelpers.classPrivateFieldLooseBase(this, _resultStack$1)[_resultStack$1].unshift(result);
	  if (babelHelpers.classPrivateFieldLooseBase(this, _resultStack$1)[_resultStack$1].length > stackSize) {
	    babelHelpers.classPrivateFieldLooseBase(this, _resultStack$1)[_resultStack$1].pop();
	  }
	}
	function _handleGenerateError2(res) {
	  var _res$errors, _res$errors$;
	  const maxGenerateRestartErrors = 4;
	  const firstErrorCode = res == null ? void 0 : (_res$errors = res.errors) == null ? void 0 : (_res$errors$ = _res$errors[0]) == null ? void 0 : _res$errors$.code;
	  if (res instanceof Error) {
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].setErrors([{
	      message: res.message,
	      code: -1,
	      customData: {}
	    }]);
	  } else if (main_core.Type.isString(res)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].setErrors([{
	      message: res,
	      code: -1,
	      customData: {}
	    }]);
	  } else if (firstErrorCode === 100 && babelHelpers.classPrivateFieldLooseBase(this, _errorsCount)[_errorsCount] < maxGenerateRestartErrors) {
	    babelHelpers.classPrivateFieldLooseBase(this, _errorsCount)[_errorsCount] += 1;
	    this.generate();
	    return;
	  } else {
	    switch (firstErrorCode) {
	      case 'AI_ENGINE_ERROR_OTHER':
	        {
	          const command = new OpenFeedbackFormCommand({
	            category: this.getCategory(),
	            isBeforeGeneration: false,
	            copilotTextController: this
	          });
	          res.errors[0].customData = {
	            clickHandler: () => command.execute()
	          };
	          babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].setErrors([{
	            code: 'AI_ENGINE_ERROR_OTHER',
	            message: main_core.Loc.getMessage('AI_COPILOT_ERROR_OTHER'),
	            customData: {
	              clickHandler: () => command.execute()
	            }
	          }]);
	          break;
	        }
	      case 'AI_ENGINE_ERROR_PROVIDER':
	        {
	          babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].setErrors([{
	            code: 'AI_ENGINE_ERROR_PROVIDER',
	            message: main_core.Loc.getMessage('AI_COPILOT_ERROR_PROVIDER')
	          }]);
	          break;
	        }
	      case 'LIMIT_IS_EXCEEDED_BAAS':
	        {
	          break;
	        }
	      default:
	        {
	          babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].setErrors(res.errors);
	        }
	    }
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _errorsCount)[_errorsCount] = 0;
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].finishGenerating();
	  if (firstErrorCode === 'LIMIT_IS_EXCEEDED_BAAS') {
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].disable();
	    setTimeout(() => {
	      const baasPopup = main_popup.PopupManager.getPopups().find(popup => popup.getId().includes('baas'));
	      if (!baasPopup) {
	        return;
	      }
	      const baasPopupAutoHide = baasPopup.autoHide;
	      baasPopup.subscribe('onClose', e => {
	        baasPopup.setAutoHide(baasPopupAutoHide);
	      });
	      baasPopup == null ? void 0 : baasPopup.setAutoHide(false);
	    }, 200);
	  } else if (firstErrorCode === 'LIMIT_IS_EXCEEDED_MONTHLY' || firstErrorCode === 'LIMIT_IS_EXCEEDED_DAILY' || firstErrorCode === 'SERVICE_IS_NOT_AVAILABLE_BY_TARIFF') {
	    this.emit('close');
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _initErrorMenu)[_initErrorMenu]();
	    babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu].adjustPosition();
	    babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu].open();
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$2)[_copilotContainer$2], '--error');
	  }
	  ai_ajaxErrorHandler.AjaxErrorHandler.handleTextGenerateError({
	    baasOptions: {
	      bindElement: babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2].getContainer().querySelector('.ai__copilot_input-field-baas-point'),
	      context: babelHelpers.classPrivateFieldLooseBase(this, _engine$2)[_engine$2].getContextId(),
	      useAngle: false
	    },
	    errorCode: firstErrorCode
	  });
	}
	function _initErrorMenu2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu] = new (babelHelpers.classPrivateFieldLooseBase(this, _CopilotMenu)[_CopilotMenu])({
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$2)[_copilotContainer$2],
	    offsetTop: 8,
	    items: CopilotErrorMenuItems.getMenuItems({
	      inputField: babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2],
	      copilotTextController: this,
	      copilotContainer: babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$2)[_copilotContainer$2]
	    }),
	    keyboardControlOptions: {
	      canGoOutFromTop: false,
	      highlightFirstItemAfterShow: true,
	      clearHighlightAfterType: false
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu].setBindElement(babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$2)[_copilotContainer$2], {
	    top: 8
	  });
	}
	function _openResultMenu2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _initResultMenu)[_initResultMenu]();
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu].setBindElement(babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$2)[_copilotContainer$2], {
	    top: 8
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu].open();
	}
	function _initResultMenu2() {
	  const items = babelHelpers.classPrivateFieldLooseBase(this, _getResultMenuItems)[_getResultMenuItems]();
	  babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu] = new (babelHelpers.classPrivateFieldLooseBase(this, _CopilotMenu)[_CopilotMenu])({
	    items,
	    roleInfo: babelHelpers.classPrivateFieldLooseBase(this, _getRoleInfoForMenu)[_getRoleInfoForMenu]({
	      withOpenRolesDialogAction: false,
	      subtitle: main_core.Loc.getMessage('AI_COPILOT_RESULT_MENU_ROLE_SUBTITLE')
	    }),
	    keyboardControlOptions: {
	      clearHighlightAfterType: false,
	      canGoOutFromTop: false,
	      highlightFirstItemAfterShow: true
	    },
	    cacheable: false
	  });
	}
	function _getRoleInfoForMenu2(params) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _useRole)[_useRole]() === false) {
	    return undefined;
	  }
	  const roleInfo = {
	    role: babelHelpers.classPrivateFieldLooseBase(this, _currentRole)[_currentRole],
	    subtitle: params.subtitle
	  };
	  if (params.withOpenRolesDialogAction) {
	    roleInfo.onclick = babelHelpers.classPrivateFieldLooseBase(this, _showRolesDialog)[_showRolesDialog].bind(this);
	  }
	  return roleInfo;
	}
	function _useRole2() {
	  return this.isReadonly() === false;
	}
	function _getResultMenuItems2() {
	  const prompts = babelHelpers.classPrivateFieldLooseBase(this, _getTooling$1)[_getTooling$1]().promptsOther;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _readonly)[_readonly]) {
	    return CopilotResultMenuItems.getMenuItemsForReadonlyResult(babelHelpers.classPrivateFieldLooseBase(this, _category$2)[_category$2], this, babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2], babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$2)[_copilotContainer$2]);
	  }
	  return CopilotResultMenuItems.getMenuItems({
	    prompts,
	    selectedText: babelHelpers.classPrivateFieldLooseBase(this, _selectedText$1)[_selectedText$1],
	    copilotTextController: this,
	    inputField: babelHelpers.classPrivateFieldLooseBase(this, _inputField$2)[_inputField$2],
	    copilotContainer: babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$2)[_copilotContainer$2],
	    showResultInCopilot: babelHelpers.classPrivateFieldLooseBase(this, _showResultInCopilot)[_showResultInCopilot]
	  }, babelHelpers.classPrivateFieldLooseBase(this, _category$2)[_category$2]);
	}
	Object.defineProperty(CopilotTextController, _toolingDataByCategory$1, {
	  writable: true,
	  value: {}
	});

	exports.CopilotTextControllerEngine = CopilotTextControllerEngine;
	exports.CopilotTextController = CopilotTextController;
	exports.AboutCopilotMenuItem = AboutCopilotMenuItem;
	exports.ChangeRequestMenuItem = ChangeRequestMenuItem;
	exports.CopyResultMenuItem = CopyResultMenuItem;
	exports.FeedbackMenuItem = FeedbackMenuItem;
	exports.MarketMenuItem = MarketMenuItem;
	exports.OpenCopilotMenuItem = OpenCopilotMenuItem;
	exports.ProviderMenuItem = ProviderMenuItem;
	exports.SettingsMenuItem = SettingsMenuItem;
	exports.CancelCopilotMenuItem = CancelCopilotMenuItem;
	exports.RepeatCopilotMenuItem = RepeatCopilotMenuItem;
	exports.ConnectModelMenuItem = ConnectModelMenuItem;

}((this.BX.AI = this.BX.AI || {}),BX.Main,BX.AI,BX.UI.Feedback,BX.AI,BX,BX.Event,BX,BX,BX.UI.IconSet));
//# sourceMappingURL=copilot-text-controller.bundle.js.map
