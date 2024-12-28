/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,tasks_flow_editForm,ui_iconSet_api_core,ui_iconSet_main,ai_copilotChat_ui,main_core) {
	'use strict';

	const getDefaultChatOptions = () => {
	  const popupWidth = 420;
	  const avatarSrc = '/bitrix/js/tasks/flow/copilot-advice/images/copilot-advice-avatar.png';
	  return {
	    popupOptions: {
	      fixed: true,
	      width: popupWidth,
	      bindElement: {
	        left: window.innerWidth - popupWidth - 85,
	        top: 50
	      },
	      cacheable: false,
	      className: 'tasks-flow__copilot-chat-popup',
	      animation: {
	        showClassName: 'tasks-flow__copilot-chat-popup-show',
	        closeClassName: 'tasks-flow__copilot-chat-popup-close',
	        closeAnimationType: 'animation'
	      }
	    },
	    loaderText: main_core.Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_LOADER_TEXT'),
	    header: {
	      title: main_core.Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_TITLE'),
	      avatar: avatarSrc,
	      useCloseIcon: true
	    },
	    botOptions: {
	      avatar: avatarSrc,
	      messageTitle: main_core.Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_BOT_TITLE'),
	      messageMenuItems: []
	    },
	    useInput: false,
	    useChatStatus: false,
	    scrollToTheEndAfterFirstShow: false,
	    showCopilotWarningMessage: true
	  };
	};

	let _ = t => t,
	  _t;
	var _flowData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("flowData");
	var _copilotChat = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotChat");
	var _ifFirstShow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("ifFirstShow");
	var _getChatOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getChatOptions");
	var _getContextMenuItemHtml = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getContextMenuItemHtml");
	var _fetchAdvices = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fetchAdvices");
	var _getFirstMessageByEfficiency = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFirstMessageByEfficiency");
	class Chat {
	  constructor(flowData) {
	    Object.defineProperty(this, _getFirstMessageByEfficiency, {
	      value: _getFirstMessageByEfficiency2
	    });
	    Object.defineProperty(this, _fetchAdvices, {
	      value: _fetchAdvices2
	    });
	    Object.defineProperty(this, _getContextMenuItemHtml, {
	      value: _getContextMenuItemHtml2
	    });
	    Object.defineProperty(this, _getChatOptions, {
	      value: _getChatOptions2
	    });
	    Object.defineProperty(this, _flowData, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _copilotChat, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _ifFirstShow, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _flowData)[_flowData] = flowData;
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotChat)[_copilotChat] = new ai_copilotChat_ui.CopilotChat(babelHelpers.classPrivateFieldLooseBase(this, _getChatOptions)[_getChatOptions]());
	    babelHelpers.classPrivateFieldLooseBase(this, _ifFirstShow)[_ifFirstShow] = true;
	  }
	  show() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _ifFirstShow)[_ifFirstShow]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _copilotChat)[_copilotChat].showLoader();
	      void babelHelpers.classPrivateFieldLooseBase(this, _fetchAdvices)[_fetchAdvices]();
	      babelHelpers.classPrivateFieldLooseBase(this, _ifFirstShow)[_ifFirstShow] = false;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotChat)[_copilotChat].show();
	  }
	  hide() {
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotChat)[_copilotChat].hide();
	  }
	}
	function _getChatOptions2() {
	  const chatOptions = getDefaultChatOptions();
	  chatOptions.botOptions.messageMenuItems = [{
	    id: 'create-task',
	    text: main_core.Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_CREATE_TASK'),
	    html: babelHelpers.classPrivateFieldLooseBase(this, _getContextMenuItemHtml)[_getContextMenuItemHtml](main_core.Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_CREATE_TASK'), ui_iconSet_api_core.Main.TASKS),
	    onclick: (event, menuItem, data) => {
	      BX.SidePanel.Instance.open(babelHelpers.classPrivateFieldLooseBase(this, _flowData)[_flowData].createTaskUrl, {
	        requestMethod: 'post',
	        requestParams: {
	          DESCRIPTION: `[QUOTE]${data.message.content}[/QUOTE]`
	        },
	        cacheable: false
	      });
	    }
	  }, {
	    id: 'create-meeting',
	    html: babelHelpers.classPrivateFieldLooseBase(this, _getContextMenuItemHtml)[_getContextMenuItemHtml](main_core.Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_CREATE_MEETING'), ui_iconSet_api_core.Main.CALENDAR_1),
	    onclick: (event, menuItem, data) => {
	      const quotedMessage = `[QUOTE]${data.message.content}[/QUOTE]`;
	      const sliderLoader = new BX.Calendar.SliderLoader('NEW', {
	        entryDescription: quotedMessage
	      });
	      sliderLoader.show();
	    }
	  }];
	  if (babelHelpers.classPrivateFieldLooseBase(this, _flowData)[_flowData].canEditFlow) {
	    chatOptions.header.menu = {
	      items: [{
	        id: 'edit-flow',
	        text: main_core.Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_EDIT_FLOW'),
	        onclick: (event, menuItem) => {
	          menuItem == null ? void 0 : menuItem.menuWindow.close == null ? void 0 : menuItem.menuWindow.close();
	          tasks_flow_editForm.EditForm.createInstance({
	            flowId: babelHelpers.classPrivateFieldLooseBase(this, _flowData)[_flowData].flowId
	          });
	        }
	      }]
	    };
	  }
	  return chatOptions;
	}
	function _getContextMenuItemHtml2(text, icon) {
	  return main_core.Tag.render(_t || (_t = _`
			<div class="tasks-flow__copilot-chat-context-menu-item">
				<span>${0}</span>
				<span class="ui-icon-set --${0}"></span>
			</div>
		`), text, icon);
	}
	async function _fetchAdvices2() {
	  var _result$data$advices, _result$data, _result$data$createDa, _result$data2;
	  const result = await main_core.ajax.runAction('tasks.flow.Copilot.Advice.get', {
	    data: {
	      flowId: babelHelpers.classPrivateFieldLooseBase(this, _flowData)[_flowData].flowId
	    }
	  });
	  if (result.status !== 'success') {
	    return;
	  }
	  const advices = (_result$data$advices = (_result$data = result.data) == null ? void 0 : _result$data.advices) != null ? _result$data$advices : [];
	  const createDate = new Date((_result$data$createDa = (_result$data2 = result.data) == null ? void 0 : _result$data2.createDateTime) != null ? _result$data$createDa : new Date());
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotChat)[_copilotChat].addBotMessage(babelHelpers.classPrivateFieldLooseBase(this, _getFirstMessageByEfficiency)[_getFirstMessageByEfficiency](babelHelpers.classPrivateFieldLooseBase(this, _flowData)[_flowData].flowEfficiency, createDate));
	  advices.forEach(advice => {
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotChat)[_copilotChat].addBotMessage({
	      content: advice,
	      status: 'delivered',
	      dateCreated: createDate.toString(),
	      viewed: true
	    });
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _copilotChat)[_copilotChat].hideLoader();
	}
	function _getFirstMessageByEfficiency2(efficiency, dateCreated) {
	  const subtitle = efficiency > 80 ? main_core.Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_SYSTEM_MESSAGE_SUBTITLE_HIGH', {
	    '#EFFICIENCY#': efficiency
	  }) : main_core.Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_SYSTEM_MESSAGE_SUBTITLE_LOW', {
	    '#EFFICIENCY#': efficiency
	  });
	  const content = efficiency > 80 ? main_core.Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_SYSTEM_MESSAGE_HIGH') : main_core.Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_SYSTEM_MESSAGE_LOW');
	  return {
	    content: '',
	    status: 'delivered',
	    type: ai_copilotChat_ui.CopilotChatMessageType.WELCOME_FLOWS,
	    dateCreated: dateCreated.toString(),
	    viewed: true,
	    params: {
	      title: main_core.Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_SYSTEM_MESSAGE_TITLE'),
	      subtitle,
	      content
	    }
	  };
	}

	var _copilotChat$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotChat");
	var _createCopilotChat = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createCopilotChat");
	class ExampleChat {
	  constructor() {
	    Object.defineProperty(this, _createCopilotChat, {
	      value: _createCopilotChat2
	    });
	    Object.defineProperty(this, _copilotChat$1, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotChat$1)[_copilotChat$1] = babelHelpers.classPrivateFieldLooseBase(this, _createCopilotChat)[_createCopilotChat]();
	  }
	  show() {
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotChat$1)[_copilotChat$1].show();
	  }
	  hide() {
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotChat$1)[_copilotChat$1].hide();
	  }
	}
	function _createCopilotChat2() {
	  const chat = new ai_copilotChat_ui.CopilotChat(getDefaultChatOptions());
	  chat.addBotMessage({
	    content: '',
	    status: 'delivered',
	    type: ai_copilotChat_ui.CopilotChatMessageType.WELCOME_FLOWS,
	    dateCreated: new Date().toString(),
	    viewed: true,
	    params: {
	      title: main_core.Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_SYSTEM_MESSAGE_TITLE'),
	      subtitle: main_core.Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_SYSTEM_MESSAGE_SUBTITLE_EXAMPLE'),
	      content: main_core.Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_SYSTEM_MESSAGE_EXAMPLE')
	    }
	  });
	  chat.addBotMessage({
	    content: main_core.Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_MESSAGE_EXAMPLE_1'),
	    status: 'delivered',
	    dateCreated: new Date().toString(),
	    viewed: true
	  });
	  chat.addBotMessage({
	    content: main_core.Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_MESSAGE_EXAMPLE_2'),
	    status: 'delivered',
	    dateCreated: new Date().toString(),
	    viewed: true
	  });
	  chat.addBotMessage({
	    content: main_core.Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_MESSAGE_EXAMPLE_3'),
	    status: 'delivered',
	    dateCreated: new Date().toString(),
	    viewed: true
	  });
	  return chat;
	}

	class CopilotAdvice {
	  static showExample() {
	    if (CopilotAdvice.currentChat) {
	      CopilotAdvice.currentChat.hide();
	    }
	    CopilotAdvice.currentChat = new ExampleChat();
	    CopilotAdvice.currentChat.show();
	  }
	  static show(flowData) {
	    if (CopilotAdvice.currentChat) {
	      CopilotAdvice.currentChat.hide();
	    }
	    CopilotAdvice.currentChat = new Chat(flowData);
	    CopilotAdvice.currentChat.show();
	  }
	}
	CopilotAdvice.currentChat = null;

	exports.CopilotAdvice = CopilotAdvice;

}((this.BX.Tasks.Flow = this.BX.Tasks.Flow || {}),BX.Tasks.Flow,BX.UI.IconSet,BX,BX.AI.CopilotChat.UI,BX));
//# sourceMappingURL=copilot-advice.bundle.js.map
