/* eslint-disable */
this.BX = this.BX || {};
this.BX.AI = this.BX.AI || {};
this.BX.AI.CopilotChat = this.BX.AI.CopilotChat || {};
(function (exports,ui_iconSet_actions,ai_speechConverter,main_core_events,ui_iconSet_api_core,ui_vue3,ui_iconSet_main,main_date,main_popup,ui_iconSet_api_vue,ui_bbcode_formatter_htmlFormatter,main_loader,main_core) {
	'use strict';

	const Status = Object.freeze({
	  COPILOT_WRITING: 'copilot-writing',
	  NONE: 'none'
	});
	const CopilotChatStatus = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    status: {
	      type: String,
	      required: false,
	      default: Status.NONE
	    }
	  },
	  computed: {
	    Status() {
	      return Status;
	    },
	    writingStatusIcon() {
	      return {
	        name: ui_iconSet_api_vue.Set.PENCIL_60,
	        size: 14,
	        color: '#fff'
	      };
	    },
	    containerClassname() {
	      return ['ai__copilot-chat_status', `--${this.status}`];
	    }
	  },
	  template: `
		<div class="ai__copilot-chat_status-wrapper">
			<div class="ai__copilot-chat_status">
				<template v-if="status === Status.COPILOT_WRITING">
					<span class="ai__copilot-chat_status-icon --typing">
						<BIcon
							v-bind="writingStatusIcon"
						/>
					</span>
					<span>{{ $Bitrix.Loc.getMessage('AI_COPILOT_CHAT_STATUS_COPILOT_WRITING') }}</span>
				</template>
			</div>
		</div>
	`
	};

	const NewMessagesVisibilityObserverEvents = Object.freeze({
	  VIEW_NEW_MESSAGE: 'viewNewMessage'
	});
	var _observer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("observer");
	var _root = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("root");
	var _observableElements = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("observableElements");
	var _getThreshold = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getThreshold");
	class NewMessagesVisibilityObserver extends main_core_events.EventEmitter {
	  constructor(options) {
	    super(options);
	    Object.defineProperty(this, _getThreshold, {
	      value: _getThreshold2
	    });
	    Object.defineProperty(this, _observer, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _root, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _observableElements, {
	      writable: true,
	      value: []
	    });
	    this.setEventNamespace('AI.CopilotChat.InterSectionManager');
	  }
	  init() {
	    // eslint-disable-next-line @bitrix24/bitrix24-rules/no-io-without-polyfill
	    babelHelpers.classPrivateFieldLooseBase(this, _observer)[_observer] = new IntersectionObserver(entries => {
	      entries.forEach(entry => {
	        const isMessageVisible = entry.isIntersecting && entry.intersectionRatio > 0.5;
	        if (isMessageVisible) {
	          const messageElement = entry.target;
	          this.emit(NewMessagesVisibilityObserverEvents.VIEW_NEW_MESSAGE, new main_core_events.BaseEvent({
	            data: {
	              id: main_core.Dom.attr(messageElement, 'data-id')
	            }
	          }));
	          babelHelpers.classPrivateFieldLooseBase(this, _observer)[_observer].unobserve(messageElement);
	        }
	      });
	    }, {
	      root: babelHelpers.classPrivateFieldLooseBase(this, _root)[_root],
	      threshold: babelHelpers.classPrivateFieldLooseBase(this, _getThreshold)[_getThreshold]()
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _observableElements)[_observableElements].forEach(element => {
	      babelHelpers.classPrivateFieldLooseBase(this, _observer)[_observer].observe(element);
	    });
	  }
	  observe(element) {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _root)[_root] || !babelHelpers.classPrivateFieldLooseBase(this, _observer)[_observer]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _observableElements)[_observableElements].push(element);
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _observer)[_observer].observe(element);
	    }
	  }
	  unobserve(element) {
	    babelHelpers.classPrivateFieldLooseBase(this, _observer)[_observer].unobserve(element);
	  }
	  setRoot(root) {
	    babelHelpers.classPrivateFieldLooseBase(this, _root)[_root] = root;
	  }
	}
	function _getThreshold2() {
	  const arrayWithZeros = Array.from({
	    length: 101
	  }).fill(0);
	  return arrayWithZeros.map((zero, index) => index * 0.01);
	}

	const CopilotChatAvatar = {
	  props: {
	    src: String,
	    alt: String
	  },
	  template: `
		<img
			class="ai__copilot-chat-avatar"
			:alt="alt"
			:src="src"
		/>
	`
	};

	const CopilotChatHeaderMenu = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    menuItems: {
	      type: Array,
	      required: true,
	      default: () => []
	    }
	  },
	  computed: {
	    items() {
	      return this.menuItems;
	    },
	    menuIconProps() {
	      return {
	        name: ui_iconSet_api_vue.Set.MORE,
	        size: 24
	      };
	    }
	  },
	  beforeMount() {
	    this.menu = new main_popup.Menu({
	      items: this.items
	    });
	  },
	  mounted() {
	    this.menu.getPopupWindow().setBindElement(this.$refs.menuButton);
	  },
	  beforeUnmount() {
	    this.menu.destroy();
	  },
	  methods: {
	    toggleMenu() {
	      if (this.isMenuOpen()) {
	        this.hideMenu();
	      } else {
	        this.showMenu();
	      }
	    },
	    showMenu() {
	      this.menu.show();
	    },
	    hideMenu() {
	      this.menu.close();
	    },
	    isMenuOpen() {
	      return this.menu.getPopupWindow().isShown();
	    }
	  },
	  template: `
		<button
			ref="menuButton"
			@click="toggleMenu"
			class="ai__copilot-chat-header-menu"
		>
			<span class="ai__copilot-chat-header-menu_icon">
				<b-icon
					v-bind="menuIconProps"
				></b-icon>
			</span>
		</button>
	`
	};

	const CopilotChatHeader = {
	  components: {
	    CopilotChatAvatar,
	    CopilotChatHeaderMenu,
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  emits: ['clickOnCloseIcon'],
	  props: {
	    title: String,
	    subtitle: String,
	    avatar: String,
	    useCloseIcon: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    menu: Object
	  },
	  computed: {
	    closeIconProps() {
	      return {
	        name: ui_iconSet_api_vue.Set.CROSS_40,
	        size: 24
	      };
	    },
	    isMenuExists() {
	      return this.menu && this.menu.items && this.menu.items.length > 0;
	    },
	    menuItems() {
	      var _this$menu$items, _this$menu;
	      return (_this$menu$items = (_this$menu = this.menu) == null ? void 0 : _this$menu.items) != null ? _this$menu$items : [];
	    }
	  },
	  methods: {
	    handleClickOnCloseIcon() {
	      this.$emit('clickOnCloseIcon');
	    }
	  },
	  template: `
		<div class="ai__copilot-chat-header">
			<button
				v-if="useCloseIcon"
				@click="handleClickOnCloseIcon"
				class="ai__copilot-chat-header_close-icon"
			>
				<b-icon
					v-bind="closeIconProps"
				 />
			</button>
			<div class="ai__copilot-chat-header_avatar">
				<CopilotChatAvatar
					:src="avatar"
					:alt="title"
				/>
			</div>
			<div class="ai__copilot-chat-header_info">
				<h4 class="ai__copilot-chat-header_title">
					{{ title }}
				</h4>
				<div class="ai__copilot-chat-header_subtitle">
					{{ subtitle }}
				</div>
			</div>
			<div
				v-if="isMenuExists"
				class="ai__copilot-chat-header_menu"
			>
				<copilot-chat-header-menu :menu-items="menuItems"></copilot-chat-header-menu>
			</div>
		</div>
	`
	};

	const CopilotChatVoiceInputBtn = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  emits: ['input', 'start', 'stop'],
	  data() {
	    return {
	      converter: null,
	      isVoiceRecording: false
	    };
	  },
	  computed: {
	    IconSet() {
	      return ui_iconSet_api_core.Set;
	    },
	    isVoiceInputDisabled() {
	      return ai_speechConverter.SpeechConverter.isBrowserSupport() === false;
	    }
	  },
	  methods: {
	    handleClickOnStartVoiceInputBtn() {
	      if (this.isVoiceRecording) {
	        this.stopVoiceInput();
	      } else {
	        this.startVoiceInput();
	      }
	    },
	    startVoiceInput() {
	      const converter = ui_vue3.toRaw(this.converter);
	      converter.start();
	    },
	    stopVoiceInput() {
	      const converter = ui_vue3.toRaw(this.converter);
	      converter.stop();
	    },
	    handleSpeechConverterStartEvent() {
	      this.$emit('start');
	      this.isVoiceRecording = true;
	    },
	    handleSpeechConverterStopEvent() {
	      this.isVoiceRecording = false;
	      this.$emit('stop');
	    },
	    handleSpeechConverterResultEvent(e) {
	      this.$emit('input', e.getData().text);
	    },
	    handleSpeechConverterErrorEvent(e) {
	      console.error(e);
	      this.isVoiceRecording = false;
	    }
	  },
	  mounted() {
	    if (this.isVoiceInputDisabled === false) {
	      this.converter = new ai_speechConverter.SpeechConverter({});
	      const converter = ui_vue3.toRaw(this.converter);
	      converter.subscribe(ai_speechConverter.speechConverterEvents.start, this.handleSpeechConverterStartEvent);
	      converter.subscribe(ai_speechConverter.speechConverterEvents.stop, this.handleSpeechConverterStopEvent);
	      converter.subscribe(ai_speechConverter.speechConverterEvents.result, this.handleSpeechConverterResultEvent.bind(this));
	      converter.subscribe(ai_speechConverter.speechConverterEvents.error, this.handleSpeechConverterErrorEvent);
	    }
	  },
	  unmounted() {
	    const converter = ui_vue3.toRaw(this.converter);
	    converter.unsubscribe(ai_speechConverter.speechConverterEvents.start, this.handleSpeechConverterStartEvent);
	    converter.unsubscribe(ai_speechConverter.speechConverterEvents.stop, this.handleSpeechConverterStopEvent);
	    converter.unsubscribe(ai_speechConverter.speechConverterEvents.result, this.handleSpeechConverterResultEvent.bind(this));
	    converter.unsubscribe(ai_speechConverter.speechConverterEvents.error, this.handleSpeechConverterErrorEvent);
	  },
	  template: `
		<button
			:disabled="isVoiceInputDisabled"
			@click="handleClickOnStartVoiceInputBtn"
			class="ai__copilot-chat-input_voice-input"
		>
			<span
				v-if="isVoiceRecording === false"
				class="ai__copilot-chat-input_voice-input-no-record-icon-wrapper"
			>
				<BIcon
					:size="24"
					:name="IconSet.MICROPHONE_ON"
				/>
			</span>
			<span
				v-else
				class="ai__copilot-chat-input_voice-input-record-icon-wrapper"
			>
				<span class="ai__copilot-chat-input_voice-input-record-icon"></span>
			</span>
		</button>
	`
	};

	const CopilotChatInput = {
	  components: {
	    CopilotChatVoiceInputBtn
	  },
	  emits: ['submit'],
	  props: {
	    disabled: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    placeholder: {
	      type: String,
	      required: false,
	      default: main_core.Loc.getMessage('AI_COPILOT_CHAT_INPUT_PLACEHOLDER')
	    }
	  },
	  data() {
	    return {
	      userMessage: '',
	      userMessageBeforeVoiceInput: this.userMessage,
	      isRecording: false
	    };
	  },
	  computed: {
	    isSubmitButtonDisabled() {
	      return !this.userMessage || this.userMessage.trim().length === 0 || this.isRecording;
	    },
	    containerClassname() {
	      return {
	        'ai__copilot-chat-input': true,
	        '--disabled': this.disabled
	      };
	    }
	  },
	  mounted() {
	    setTimeout(() => {
	      this.$refs.textarea.focus();
	    }, 500);
	  },
	  methods: {
	    handleSubmitButton(e) {
	      e.target.blur();
	      this.submitMessage();
	    },
	    handleEnterKeyDown(e) {
	      var _this$userMessage;
	      if (e.shiftKey || e.ctrlKey) {
	        return true;
	      }
	      e.preventDefault();
	      if ((_this$userMessage = this.userMessage) != null && _this$userMessage.trim()) {
	        this.submitMessage();
	      }
	      return false;
	    },
	    submitMessage() {
	      this.$emit('submit', this.userMessage.trim());
	      this.userMessage = '';
	    },
	    handleInput(e) {
	      this.userMessage = e.target.value;
	    },
	    handleVoiceInputText(text) {
	      this.userMessage = this.userMessageBeforeVoiceInput + text;
	      this.updateTextareaHeight();
	    },
	    handleStartVoiceInput() {
	      this.isRecording = true;
	      if (this.userMessage && this.userMessage.at(-1) !== ' ') {
	        this.userMessage += ' ';
	      }
	      this.userMessageBeforeVoiceInput = this.userMessage;
	    },
	    handleStopVoiceInput() {
	      this.isRecording = false;
	      this.$refs.textarea.focus();
	    },
	    updateTextareaHeight() {
	      const textarea = this.$refs.textarea;
	      main_core.Dom.style(textarea, 'height', 'auto');
	      main_core.Dom.style(textarea, 'height', `${textarea.scrollHeight}px`);
	    }
	  },
	  watch: {
	    userMessage() {
	      requestAnimationFrame(() => {
	        this.updateTextareaHeight();
	      });
	    },
	    disabled(isDisabled) {
	      if (isDisabled === false) {
	        this.$refs.textarea.focus();
	      } else {
	        this.$refs.textarea.blur();
	      }
	    }
	  },
	  template: `
		<div :class="containerClassname">
			<div class="ai__copilot-chat-input_textarea-wrapper">
				<textarea
					type="text" class="ai__copilot-chat-input_textarea"
					ref="textarea"
					:placeholder="placeholder"
					rows="1"
					@input="handleInput"
					@keydown.enter="handleEnterKeyDown"
					:value="userMessage"
				/>
			</div>
			<div class="ai__copilot-chat-input_actions">
				<CopilotChatVoiceInputBtn
					@start="handleStartVoiceInput"
					@input="handleVoiceInputText"
					@stop="handleStopVoiceInput"
				/>
				<button @click="handleSubmitButton" :disabled="isSubmitButtonDisabled" class="ai__copilot-chat-input_submit"></button>
			</div>
		</div>
	`
	};

	const isMessageFromCopilot = authorId => {
	  return authorId === 0;
	};

	const CopilotChatMessage = {
	  props: {
	    avatar: String,
	    avatarAlt: String,
	    messageTitle: String,
	    messageText: String,
	    time: String,
	    buttons: {
	      type: Array,
	      required: false,
	      default: () => []
	    },
	    colorScheme: String,
	    status: {
	      type: String,
	      required: false
	    }
	  },
	  computed: {
	    messageButtons() {
	      return this.buttons;
	    },
	    formattedTime() {
	      const date = new Date(this.time);
	      return `${date.getHours()}:${date.getMinutes()}`;
	    },
	    isUserMessage() {
	      return true; // replace with the actual code
	    }
	  },

	  template: `
		<div
			class="ai__copilot-chat-message"
			:class="'--color-schema-' + colorScheme"
		>
			<div class="ai__copilot-chat-message_avatar-wrapper">
				<img
					class="ai__copilot-chat-message_avatar"
					:src="avatar"
					:alt="avatarAlt"
					:title="avatarAlt"
				>
			</div>
			<div class="ai__copilot-chat-message-content-wrapper">
				<div class="ai__copilot-chat-message-content">
					<div class="ai__copilot-chat-message-content-main">
						<div
							v-if="messageTitle"
							class="ai__copilot-chat-message-title"
						>
							{{ messageTitle }}
						</div>
						<div class="ai__copilot-chat-message-text">
							{{ messageText }}
						</div>
					</div>
					<div class="ai__copilot-chat-message_time">
						{{ formattedTime }}
					</div>
					<div
						v-if="status"
						class="ai__copilot-chat-message_status"
						:class="'--' + status"
					></div>
				</div>
			</div>
			<div v-if="messageButtons.length > 0" class="ai__copilot-chat-message_action-buttons">
				<button
					v-for="button in messageButtons"
					class="ai__copilot-chat-message_action-button"
					:class="{'--selected': button.isSelected}"
				>
					{{ button.text }}
				</button>
			</div>
		</div>
	`
	};

	const CopilotChatWelcomeMessage = {
	  props: {
	    avatar: String,
	    title: String,
	    content: String
	  },
	  template: `
		<div class="landing__copilot-landing-chat-welcome-message">
			<div class="landing__copilot-landing-chat-welcome-message_avatar-wrapper">
				<img
					class="landing__copilot-landing-chat-welcome-message_avatar"
					src="/dev/ai/copilot-chat/images/avatar-example-4x.png"
					alt="Copilot Designer"
				>
			</div>
			<div class="landing__copilot-landing-chat-welcome-message_content">
				<h6 class="landing__copilot-landing-chat-welcome-message_title">{{ title }}</h6>
				<div v-html="content"></div>
			</div>
		</div>
	`
	};

	const CopilotChatMessagesDateGroup = {
	  props: {
	    date: {
	      type: String,
	      required: false,
	      default: ''
	    }
	  },
	  computed: {
	    formattedDate() {
	      const format = [['today', 'today'], ['yesterday', 'yesterday'], ['m', 'l, d F'], ['', 'l, d F Y']];
	      return main_date.DateTimeFormat.format(format, new Date(this.date), new Date());
	    }
	  },
	  template: `
		<div class="ai__copilot-chat-messages-date-group">
			<div class="ai__copilot-chat-messages-date-group__date">
				<div class="ai__copilot-chat-messages-date-group__date-label">
					{{ formattedDate }}
				</div>
			</div>
			<div class="ai__copilot-chat-messages-date-group__content">
				<slot></slot>
			</div>
		</div>
	`
	};

	const containerClassname = 'ai__copilot-chat_new-messages-label';
	const CopilotChatNewMessagesLabel = {
	  template: `
		<div class="${containerClassname}">
			{{ $Bitrix.Loc.getMessage('AI_COPILOT_CHAT_NEW_MESSAGES_LABEL') }}
		</div>
	`
	};

	const CopilotChatMessagesAuthorGroup = {
	  components: {
	    CopilotChatNewMessagesLabel
	  },
	  props: {
	    avatar: {
	      type: String,
	      required: false
	    },
	    showNewMessagesLabel: {
	      type: Boolean,
	      required: false,
	      default: false
	    }
	  },
	  template: `
		<CopilotChatNewMessagesLabel v-if="showNewMessagesLabel" />
		<div class="ai__copilot-chat_messages-author-group">
			<div class="ai__copilot-chat_messages-author-group__avatar">
				<img v-if="avatar" :src="avatar" alt="#">
			</div>
			<div class="ai__copilot-chat_messages-author-group__messages">
				<slot></slot>
			</div>
		</div>
	`
	};

	const CopilotChatMessageType = Object.freeze({
	  DEFAULT: 'Default',
	  BUTTON_CLICK_MESSAGE: 'ButtonClicked',
	  WELCOME_FLOWS: 'WelcomeFlows',
	  WELCOME_SITE_WITH_AI: 'GreetingSiteWithAi',
	  SYSTEM: 'System'
	});

	const CopilotChatNewMessageVisibilityObserver = {
	  mounted(element, binding) {
	    const isMessageViewed = binding.value;
	    if (isMessageViewed === false) {
	      binding.instance.observer.observe(element);
	    }
	  },
	  beforeUnmount(element, binding) {
	    binding.instance.observer.unobserve(element);
	  }
	};

	const CopilotChatMessageMenu = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    menuItems: {
	      type: Array,
	      required: true,
	      default: () => []
	    },
	    message: {
	      type: Object,
	      required: true,
	      default: () => ({})
	    }
	  },
	  data() {
	    return {
	      isMenuOpen: false
	    };
	  },
	  computed: {
	    items() {
	      return this.menuItems.map(item => {
	        return {
	          ...item,
	          onclick: (event, menuItem) => {
	            const myCustomData = {
	              message: {
	                id: this.message.id,
	                content: this.message.content,
	                dateCreated: this.message.dateCreated
	              }
	            };
	            this.hideMenu();
	            return item.onclick(event, menuItem, myCustomData);
	          }
	        };
	      });
	    },
	    menuIconProps() {
	      return {
	        name: ui_iconSet_api_vue.Set.MORE,
	        size: 22
	      };
	    },
	    menuButtonClassname() {
	      return {
	        'ai__copilot-chat-message-menu': true,
	        '--open': this.isMenuOpen
	      };
	    }
	  },
	  methods: {
	    toggleMenu() {
	      if (this.isMenuOpen) {
	        this.hideMenu();
	      } else {
	        this.showMenu();
	      }
	    },
	    showMenu() {
	      var _this$menu;
	      if (!this.menu) {
	        this.initMenu();
	      }
	      (_this$menu = this.menu) == null ? void 0 : _this$menu.show();
	    },
	    hideMenu() {
	      var _this$menu2;
	      (_this$menu2 = this.menu) == null ? void 0 : _this$menu2.close();
	    },
	    initMenu() {
	      this.menu = new main_popup.Menu({
	        items: this.items,
	        angle: {
	          offset: main_core.Dom.getPosition(this.$refs.menuButton).width / 2 + 23
	        },
	        events: {
	          onPopupShow: () => {
	            this.isMenuOpen = true;
	          },
	          onPopupClose: () => {
	            this.isMenuOpen = false;
	          }
	        },
	        bindElement: this.$refs.menuButton
	      });
	      main_core.bind(document.body.querySelector('.ai__copilot-chat_main'), 'scroll', () => {
	        this.hideMenu();
	      });
	      return this.menu;
	    }
	  },
	  beforeUnmount() {
	    var _this$menu3;
	    (_this$menu3 = this.menu) == null ? void 0 : _this$menu3.destroy();
	  },
	  template: `
		<button
			ref="menuButton"
			@click="toggleMenu"
			:class="menuButtonClassname"
		>
			<BIcon v-bind="menuIconProps"></BIcon>
		</button>
	`
	};

	const CopilotChatMessageDefault = {
	  components: {
	    CopilotChatMessageMenu
	  },
	  emits: ['buttonClick'],
	  props: {
	    message: {
	      type: Object,
	      required: true
	    },
	    avatar: {
	      type: String,
	      required: true
	    },
	    title: {
	      type: String,
	      required: false
	    },
	    useAvatarTail: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    menuItems: {
	      type: Array,
	      required: false,
	      default: () => []
	    },
	    color: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    disableAllActions: {
	      type: Boolean,
	      required: false,
	      default: false
	    }
	  },
	  computed: {
	    messageData() {
	      return this.message;
	    },
	    messageContent() {
	      return this.messageData.content;
	    },
	    isMessageFromCopilot() {
	      return isMessageFromCopilot(this.messageData.authorId);
	    },
	    formattedMessageContent() {
	      const htmlFormatter = new ui_bbcode_formatter_htmlFormatter.HtmlFormatter({
	        containerMode: 'collapsed'
	      });
	      return htmlFormatter.format({
	        source: this.messageContent
	      });
	    },
	    formattedDeliveryTime() {
	      return this.formatTime(this.messageData.dateCreated);
	    },
	    messageButtons() {
	      var _this$messageData$par, _this$messageData$par2;
	      return (_this$messageData$par = (_this$messageData$par2 = this.messageData.params) == null ? void 0 : _this$messageData$par2.buttons) != null ? _this$messageData$par : [];
	    },
	    isSomeButtonSelected() {
	      return this.messageButtons.some(button => button.isSelected);
	    },
	    showMenuButton() {
	      var _this$menuItems;
	      return ((_this$menuItems = this.menuItems) == null ? void 0 : _this$menuItems.length) > 0;
	    }
	  },
	  methods: {
	    formatTime(dateTime) {
	      if (!dateTime) {
	        return '';
	      }
	      const date = new Date(dateTime);
	      const hours = date.getHours().toString().padStart(2, '0');
	      const minutes = date.getMinutes().toString().padStart(2, '0');
	      return `${hours}:${minutes}`;
	    }
	  },
	  mounted() {
	    if (this.isMessageFromCopilot) {
	      main_core.Dom.append(this.formattedMessageContent, this.$refs.content);
	    } else {
	      this.$refs.content.innerText = this.messageContent;
	    }
	  },
	  template: `
		<div
			:data-id="messageData.id"
			class="ai__copilot-chat-message"
			:class="'--color-schema-' + color"
		>
			<div
				class="ai__copilot-chat-message-content-wrapper"
				:class="{ '--with-tail': useAvatarTail }"
			>
				<div class="ai__copilot-chat-message-content">
					<div class="ai__copilot-chat-message-content-main">
						<div
							v-if="title"
							class="ai__copilot-chat-message-title"
						>
							{{ title }}
						</div>
						<div class="ai__copilot-chat-message-text" ref="content"></div>
					</div>
					<div class="ai__copilot-chat-message_status-info">
						<div
							v-if="messageData.dateCreated"
							class="ai__copilot-chat-message_time"
						>
							{{ formattedDeliveryTime }}
						</div>
						<div
							v-if="messageData.status"
							class="ai__copilot-chat-message_status"
							:class="'--' + messageData.status"
						></div>
					</div>
				</div>
				<div v-if="messageButtons.length > 0" class="ai__copilot-chat-message_action-buttons">
					<button
						v-for="button in messageButtons"
						class="ai__copilot-chat-message_action-button"
						:class="{'--selected': button.isSelected}"
						:disabled="isSomeButtonSelected || disableAllActions"
						@click="$emit('buttonClick', button.id)"
					>
						{{ button.title }}
					</button>
				</div>
			</div>
			<div
				v-if="showMenuButton"
				class="ai__copilot-chat-message_menu"
			>
				<CopilotChatMessageMenu
					:menu-items="menuItems"
					:message="message"
				/>
			</div>
		</div>
	`
	};

	const CopilotChatMessageWelcome = {
	  props: {
	    message: {
	      type: Object,
	      required: false
	    },
	    avatar: {
	      type: String,
	      required: false
	    }
	  },
	  computed: {
	    messageInfo() {
	      return this.message;
	    },
	    title() {
	      var _this$messageInfo$par, _this$messageInfo$par2;
	      return (_this$messageInfo$par = (_this$messageInfo$par2 = this.messageInfo.params) == null ? void 0 : _this$messageInfo$par2.title) != null ? _this$messageInfo$par : '';
	    },
	    subtitle() {
	      var _this$messageInfo$par3, _this$messageInfo$par4;
	      return (_this$messageInfo$par3 = (_this$messageInfo$par4 = this.messageInfo.params) == null ? void 0 : _this$messageInfo$par4.subtitle) != null ? _this$messageInfo$par3 : '';
	    },
	    content() {
	      var _this$messageInfo$par5, _this$messageInfo$par6;
	      return (_this$messageInfo$par5 = (_this$messageInfo$par6 = this.messageInfo.params) == null ? void 0 : _this$messageInfo$par6.content) != null ? _this$messageInfo$par5 : '';
	    }
	  },
	  template: `
		<div class="ai__copilot-chat-message-welcome">
			<header class="ai__copilot-chat-message-welcome_header">
				<div class="ai__copilot-chat-message-welcome_header-left">
					<img
						:src="avatar"
						alt="#"
						class="ai__copilot-chat-message-welcome_avatar"
					>
				</div>
				<div class="ai__copilot-chat-message-welcome_header-right">
					<h5 class="ai__copilot-chat-message-welcome_title">{{ title }}</h5>
					<p v-if="subtitle" class="ai__copilot-chat-message-welcome_subtitle">{{ subtitle }}</p>
				</div>
			</header>
			<main class="ai__copilot-chat-message-welcome_main">
				<div class="ai__copilot-chat-message-welcome_content">
					<slot name="content">
						{{ content }}
					</slot>
				</div>
			</main>
		</div>
	`
	};

	let _ = t => t,
	  _t;
	const CopilotChatMessageSiteWithAi = {
	  components: {
	    CopilotChatMessageWelcome
	  },
	  props: {
	    message: {
	      type: Object,
	      required: false
	    },
	    avatar: {
	      type: String,
	      required: false
	    },
	    disableAllActions: {
	      type: Boolean,
	      required: false,
	      default: false
	    }
	  },
	  inject: ['instance'],
	  computed: {
	    chatInstance() {
	      return this.instance;
	    },
	    messageInfo() {
	      return {
	        ...this.message,
	        params: {
	          ...this.message.params,
	          title: 'Привет! Я — Веб-дизайнер',
	          subtitle: '',
	          content: ''
	        }
	      };
	    }
	  },
	  methods: {
	    renderContent() {
	      var _this$messageInfo$par, _this$messageInfo$par2;
	      const buttons = (_this$messageInfo$par = (_this$messageInfo$par2 = this.messageInfo.params) == null ? void 0 : _this$messageInfo$par2.buttons) != null ? _this$messageInfo$par : [];
	      const isMessageHaveCreateSiteButton = buttons.length > 0;
	      const paragraph2 = isMessageHaveCreateSiteButton ? this.$Bitrix.Loc.getMessage('AI_COPILOT_CHAT_WELCOME_MESSAGE_SITE_WITH_AI_2', {
	        '#LINK#': `<a href="#" class="${this.disableAllActions ? 'disabled' : ''}" ref="createSiteLink">`,
	        '#/LINK#': '</a>'
	      }) : '';
	      const content = main_core.Tag.render(_t || (_t = _`
				<div ref="root">
					<p>${0}</p>
					<p>${0}</p>
				</div>
			`), this.$Bitrix.Loc.getMessage('AI_COPILOT_CHAT_WELCOME_MESSAGE_SITE_WITH_AI_1'), paragraph2);
	      if (isMessageHaveCreateSiteButton) {
	        main_core.bind(content.createSiteLink, 'click', () => {
	          var _this$messageInfo$par3, _this$messageInfo$par4, _this$messageInfo$par5, _this$messageInfo$par6, _this$messageInfo$par7;
	          if (!((_this$messageInfo$par3 = this.messageInfo.params) != null && _this$messageInfo$par3.buttons) || this.messageInfo.params.buttons.length === 0) {
	            return;
	          }
	          this.chatInstance.addUserMessage({
	            type: 'ButtonClicked',
	            content: (_this$messageInfo$par4 = this.messageInfo.params) == null ? void 0 : (_this$messageInfo$par5 = _this$messageInfo$par4.buttons[0]) == null ? void 0 : _this$messageInfo$par5.text,
	            params: {
	              messageId: this.messageInfo.id,
	              buttonId: (_this$messageInfo$par6 = this.messageInfo.params) == null ? void 0 : (_this$messageInfo$par7 = _this$messageInfo$par6.buttons[0]) == null ? void 0 : _this$messageInfo$par7.id
	            }
	          });
	        });
	      }
	      this.$refs.content.innerHTML = '';
	      main_core.Dom.append(content.root, this.$refs.content);
	    }
	  },
	  watch: {
	    disableAllActions() {
	      this.renderContent();
	    }
	  },
	  mounted() {
	    this.renderContent();
	  },
	  template: `
		<CopilotChatMessageWelcome
			:avatar="avatar"
			:message="messageInfo"
		>
			<template #content>
				<div ref="content"></div>
			</template>
		</CopilotChatMessageWelcome>
	`
	};

	const CopilotChatMessageWelcomeFlows = {
	  components: {
	    CopilotChatMessageWelcome
	  },
	  props: {
	    message: {
	      type: Object,
	      required: false
	    },
	    avatar: {
	      type: String,
	      required: false
	    }
	  },
	  computed: {
	    messageInfo() {
	      return this.message;
	    }
	  },
	  template: `
		<CopilotChatMessageWelcome
			:avatar="avatar"
			:message="messageInfo"
		/>
	`
	};

	const CopilotChatMessageColor = Object.freeze({
	  USER: 'user',
	  COPILOT: 'copilot',
	  ERROR: 'error',
	  USER_WITH_HIGHLIGHT_TEXT: 'userWithHighlightText'
	});

	const CopilotChatMessages = {
	  components: {
	    CopilotChatMessage,
	    CopilotChatWelcomeMessage,
	    CopilotChatMessageDefault,
	    CopilotChatMessageWelcome,
	    CopilotChatMessageSiteWithAi,
	    CopilotChatMessageWelcomeFlows,
	    CopilotChatMessagesDateGroup,
	    CopilotChatMessagesAuthorGroup
	  },
	  emits: ['clickMessageButton'],
	  props: {
	    messages: {
	      type: Array,
	      required: false,
	      default: () => []
	    },
	    copilotAvatar: String,
	    userAvatar: String,
	    copilotMessageTitle: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    userMessageMenuItems: {
	      type: Array,
	      required: false,
	      default: () => []
	    },
	    copilotMessageMenuItems: {
	      type: Array,
	      required: false,
	      default: () => []
	    },
	    welcomeMessageHtmlElement: HTMLElement
	  },
	  inject: ['observer'],
	  directives: {
	    CopilotChatNewMessageVisibilityObserver
	  },
	  computed: {
	    messagesList() {
	      return this.messages;
	    },
	    messagesGroupedByDayAndAuthor() {
	      const groupsOfMessages = this.groupMessagesByDay(this.messagesList);
	      const result = {};
	      Object.entries(groupsOfMessages).forEach(([date, {
	        messages,
	        isNewMessagesStartHere
	      }]) => {
	        result[date] = this.groupMessagesByAuthor(messages, isNewMessagesStartHere);
	      });
	      return result;
	    },
	    sortedByDateMessagesGroups() {
	      return Object.keys(this.messagesGroupedByDayAndAuthor).sort();
	    }
	  },
	  methods: {
	    groupMessagesByDay(messages) {
	      let isMessagesContainsUnread = false;
	      return messages.reduce((groupedMessages, message) => {
	        const messageDeliveryDate = new Date(message.dateCreated);
	        const messageIsoDate = this.formatISODate(messageDeliveryDate);
	        if (groupedMessages[messageIsoDate] === undefined) {
	          // eslint-disable-next-line no-param-reassign
	          groupedMessages[messageIsoDate] = {
	            messages: [],
	            isNewMessagesStartHere: false
	          };
	        }
	        if (message.viewed === false && isMessagesContainsUnread === false) {
	          // eslint-disable-next-line no-param-reassign
	          groupedMessages[messageIsoDate].isNewMessagesStartHere = true;
	          isMessagesContainsUnread = true;
	        }
	        groupedMessages[messageIsoDate].messages.push(message);
	        return groupedMessages;
	      }, {});
	    },
	    groupMessagesByAuthor(messages, isNewMessagesStartHere) {
	      let currentAuthor = -Infinity;
	      let isNewMessagesLabelWasAdded = false;
	      return messages.reduce((messagesGroupedByAuthor, message) => {
	        if (message.viewed === false && isNewMessagesLabelWasAdded === false && isNewMessagesStartHere === true) {
	          messagesGroupedByAuthor.push([message.authorId, [], true]);
	          isNewMessagesLabelWasAdded = true;
	        } else if (message.authorId !== currentAuthor) {
	          messagesGroupedByAuthor.push([message.authorId, []]);
	          currentAuthor = message.authorId;
	        }
	        messagesGroupedByAuthor.at(-1)[1].push(message);
	        return messagesGroupedByAuthor;
	      }, []);
	    },
	    getMessageMenuItems(message) {
	      if (message.authorId === null || message.authorId === undefined) {
	        return [];
	      }
	      return isMessageFromCopilot(message.authorId) ? this.copilotMessageMenuItems : this.userMessageMenuItems;
	    },
	    formatISODate(date) {
	      const year = date.getFullYear();
	      const month = (date.getMonth() + 1).toString().padStart(2, '0');
	      const day = date.getDate().toString().padStart(2, '0');
	      return `${year}-${month}-${day}`;
	    },
	    getMessageColor(message) {
	      if (message.type === CopilotChatMessageType.BUTTON_CLICK_MESSAGE) {
	        return CopilotChatMessageColor.USER_WITH_HIGHLIGHT_TEXT;
	      }
	      if (isMessageFromCopilot(message.authorId)) {
	        return CopilotChatMessageColor.COPILOT;
	      }
	      return CopilotChatMessageColor.USER;
	    },
	    getMessageComponent(message) {
	      switch (message.type) {
	        case CopilotChatMessageType.DEFAULT:
	          return CopilotChatMessageDefault;
	        case CopilotChatMessageType.WELCOME_FLOWS:
	          return CopilotChatMessageWelcomeFlows;
	        case CopilotChatMessageType.WELCOME_SITE_WITH_AI:
	          return CopilotChatMessageSiteWithAi;
	        default:
	          return CopilotChatMessageDefault;
	      }
	    },
	    getMessageTitle(authorId) {
	      return this.isMessageFromCopilot(authorId) ? this.copilotMessageTitle : '';
	    },
	    getMessageAvatarByAuthorId(authorId) {
	      return this.isMessageFromCopilot(authorId) ? this.copilotAvatar : this.userAvatar;
	    },
	    getAuthorMessagesGroupAvatar(authorId, messages) {
	      const lastMessage = messages.at(-1);
	      const isLastMessageIsWelcome = lastMessage.type !== 'Default' && lastMessage.type !== 'ButtonClicked';
	      if (authorId === null || authorId === undefined || isLastMessageIsWelcome) {
	        return null;
	      }
	      return this.getMessageAvatarByAuthorId(authorId);
	    },
	    isMessageFromCopilot(authorId) {
	      return authorId < 1;
	    },
	    isMessageHaveButtons(message) {
	      var _message$params, _message$params$butto;
	      return (message == null ? void 0 : (_message$params = message.params) == null ? void 0 : (_message$params$butto = _message$params.buttons) == null ? void 0 : _message$params$butto.length) > 0;
	    },
	    handleMessageButtonClick(messageId, buttonId) {
	      this.$emit('clickMessageButton', {
	        messageId,
	        buttonId
	      });
	    },
	    isLastMessage(dateGroupIndex, authorGroupIndex, messageIndexAtAuthorGroup) {
	      return (dateGroupIndex + 1) * (authorGroupIndex + 1) + messageIndexAtAuthorGroup === this.messages.length;
	    }
	  },
	  mounted() {
	    main_core.Dom.append(this.welcomeMessageHtmlElement, this.$refs.welcomeMessage);
	  },
	  template: `
		<CopilotChatMessagesDateGroup
			v-for="(date, dateGroupIndex) of sortedByDateMessagesGroups"
			:date="date"
		>
			<CopilotChatMessagesAuthorGroup
				v-for="([authorId, messagesFromCurrentAuthor, showNewMessagesLabel], authorGroupIndex) in messagesGroupedByDayAndAuthor[date]"
				:avatar="getAuthorMessagesGroupAvatar(authorId, messagesFromCurrentAuthor)"
				:show-new-messages-label="showNewMessagesLabel"
			>
				<ul class="ai__copilot-chat-messages">
					<li
						v-for="(message, index) of messagesFromCurrentAuthor"
						class="ai__copilot-chat-messages_message-wrapper"
					>
						<component :is="getMessageComponent(message)" 
								v-copilot-chat-new-message-visibility-observer="message.viewed"
								@buttonClick="handleMessageButtonClick(message.id, $event)"
								:message="message"
								:title="getMessageTitle(message.authorId)"
								:color="getMessageColor(message)"
								:avatar="getMessageAvatarByAuthorId(message.authorId)"
								:useAvatarTail="index === messagesFromCurrentAuthor.length - 1 && isMessageHaveButtons(message) === false"
								:disable-all-actions="isLastMessage(dateGroupIndex, authorGroupIndex, index) === false"
								:menu-items="getMessageMenuItems(message)"
						></component>
					</li>
				</ul>
			</CopilotChatMessagesAuthorGroup>
	
		</CopilotChatMessagesDateGroup>
	`
	};

	const CopilotChatHistoryLoader = {
	  props: {
	    text: {
	      type: String,
	      required: false,
	      default: main_core.Loc.getMessage('AI_COPILOT_CHAT_MESSAGES_HISTORY_LOADER_TEXT')
	    }
	  },
	  beforeMount() {
	    const color = getComputedStyle(document.body).getPropertyValue('--ui-color-base-02');
	    this.loader = new main_loader.Loader({
	      color,
	      size: 60
	    });
	  },
	  mounted() {
	    this.loader.show(this.$refs.loaderContainer);
	  },
	  unmounted() {
	    this.loader.hide();
	    this.loader = null;
	  },
	  template: `
		<div class="ai__copilot-chat-history-loader">
			<div ref="loaderContainer" class="ai__copilot-chat-history-loader_animation-container"></div>
			<div class="ai__copilot-chat_history-loader_text">
				{{ text }}
			</div>
		</div>
	`
	};

	let _$1 = t => t,
	  _t$1;
	const CopilotChatWarningMessage = {
	  name: 'CopilotWarningMessage',
	  methods: {
	    showArticle() {
	      const Helper = main_core.Reflection.getClass('top.BX.Helper');
	      const articleCode = '20412666';
	      Helper == null ? void 0 : Helper.show(`redirect=detail&code=${articleCode}`);
	    }
	  },
	  mounted() {
	    const warningMessage = main_core.Tag.render(_t$1 || (_t$1 = _$1`<span>${0}</span>`), this.$Bitrix.Loc.getMessage('AI_COPILOT_CHAT_ANSWER_WARNING', {
	      '#LINK_START#': '<a ref="link" href="#">',
	      '#LINK_END#': '</a>'
	    }));
	    main_core.Event.bind(warningMessage.link, 'click', this.showArticle);
	    main_core.Dom.append(warningMessage.root, this.$refs.container);
	  },
	  template: `
		<div
			ref="container"
			class="ai__copilot-chat-warning-message"
		></div>
	`
	};

	const CopilotChat = {
	  name: 'CopilotChat',
	  components: {
	    CopilotChatHeader,
	    CopilotChatMessages,
	    CopilotChatInput,
	    CopilotChatHistoryLoader,
	    CopilotChatStatus,
	    CopilotChatWarningMessage
	  },
	  props: {
	    header: Object,
	    welcomeMessageHtml: HTMLElement,
	    botOptions: Object,
	    scrollToTheEndAfterFirstShow: Object,
	    slots: Object,
	    isShowWarningMessage: {
	      type: Object,
	      required: false,
	      default: () => ({
	        value: false
	      })
	    },
	    messages: Array,
	    copilotChatInstance: Object,
	    useInput: {
	      type: Boolean,
	      required: false,
	      default: true
	    },
	    disableInput: Object,
	    showLoader: Object,
	    showCopilotWritingStatus: Object,
	    status: Object,
	    useStatus: Object,
	    copilotMessageMenuItems: {
	      type: Array,
	      required: false,
	      default: () => []
	    },
	    userAvatar: {
	      type: Object,
	      required: false
	    },
	    userMessageMenuItems: {
	      type: Array,
	      required: false,
	      default: () => []
	    },
	    inputPlaceholder: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    loaderText: {
	      type: String,
	      required: false
	    }
	  },
	  data() {
	    return {
	      isCopilotWriting: false,
	      isShowErrorScreen: false
	    };
	  },
	  provide() {
	    return {
	      instance: this.copilotChatInstance,
	      observer: this.observer
	    };
	  },
	  computed: {
	    userPhoto() {
	      var _this$userAvatar;
	      return ((_this$userAvatar = this.userAvatar) == null ? void 0 : _this$userAvatar.value) || '/bitrix/js/ui/icons/b24/images/ui-user.svg?v2';
	    },
	    isInputDisabled() {
	      return this.disableInput.value === true;
	    },
	    isLoaderShown() {
	      return this.showLoader.value === true;
	    },
	    isWarningMessageShown() {
	      var _this$isShowWarningMe;
	      return ((_this$isShowWarningMe = this.isShowWarningMessage) == null ? void 0 : _this$isShowWarningMe.value) === true;
	    },
	    instance() {
	      return this.copilotChatInstance;
	    },
	    Slot() {
	      return {
	        ...this.slots
	      };
	    },
	    headerProps() {
	      var _this$header, _this$header2, _this$header3, _this$header4, _this$header5;
	      return {
	        title: (_this$header = this.header) == null ? void 0 : _this$header.title,
	        subtitle: (_this$header2 = this.header) == null ? void 0 : _this$header2.subtitle,
	        avatar: (_this$header3 = this.header) == null ? void 0 : _this$header3.avatar,
	        useCloseIcon: (_this$header4 = this.header) == null ? void 0 : _this$header4.useCloseIcon,
	        menu: (_this$header5 = this.header) == null ? void 0 : _this$header5.menu
	      };
	    },
	    botData() {
	      var _this$botOptions$mess, _this$botOptions;
	      return {
	        messageTitle: this.botOptions.messageTitle,
	        avatar: this.botOptions.avatar,
	        messageMenuItems: (_this$botOptions$mess = (_this$botOptions = this.botOptions) == null ? void 0 : _this$botOptions.messageMenuItems) != null ? _this$botOptions$mess : []
	      };
	    },
	    messagesList() {
	      return this.messages;
	    },
	    haveNewMessages() {
	      return this.messagesList.some(message => message.viewed === false);
	    },
	    copilotChatStatus() {
	      return this.status.value;
	    },
	    isChatStatusUsed() {
	      return this.useStatus.value;
	    },
	    isScrollToTheEndAfterMounted() {
	      return this.scrollToTheEndAfterFirstShow.value;
	    }
	  },
	  methods: {
	    hideChat() {
	      this.copilotChatInstance.hide();
	    },
	    async handleSubmitMessage(userMessage) {
	      const newMessage = {
	        content: userMessage
	      };
	      this.instance.addUserMessage(newMessage);
	    },
	    scrollMessagesListAfterOpen() {
	      if (this.haveNewMessages) {
	        const newMessagesLabel = this.$refs.main.querySelector(`.${containerClassname}`);
	        newMessagesLabel == null ? void 0 : newMessagesLabel.scrollIntoView();
	      } else {
	        this.scrollMessagesListToTheEnd();
	      }
	    },
	    scrollMessagesListToTheEnd(isSmooth = false) {
	      this.$refs.main.scrollTo({
	        left: 0,
	        top: 9999,
	        behavior: isSmooth ? 'smooth' : 'auto'
	      });
	    },
	    handleClickOnMessageButton(eventData) {
	      this.instance.emitClickOnMessageButton({
	        messageId: eventData.messageId,
	        buttonId: eventData.buttonId
	      });
	    },
	    handleAddNewUserMessage() {
	      requestAnimationFrame(() => {
	        this.scrollMessagesListToTheEnd(true);
	      });
	    }
	  },
	  beforeCreate() {
	    this.observer = new NewMessagesVisibilityObserver();
	    this.observer.subscribe(NewMessagesVisibilityObserverEvents.VIEW_NEW_MESSAGE, event => {
	      this.instance.setNewMessageIsViewed(event.getData().id);
	    });
	  },
	  mounted() {
	    this.observer.setRoot(this.$refs.main);
	    this.observer.init();
	    this.instance.subscribe(CopilotChatEvents.ADD_USER_MESSAGE, this.handleAddNewUserMessage);
	    requestAnimationFrame(() => {
	      if (this.isScrollToTheEndAfterMounted) {
	        this.scrollMessagesListAfterOpen();
	      }
	    });
	    main_core.bind(this.$refs.main, 'scroll', event => {
	      if (event.target.scrollTop < 100) {
	        this.instance.emit(CopilotChatEvents.MESSAGES_SCROLL_TOP);
	      }
	    });
	  },
	  beforeUnmount() {
	    this.instance.unsubscribe(CopilotChatEvents.ADD_USER_MESSAGE, this.handleAddNewUserMessage);
	  },
	  watch: {
	    'messagesList.length': function (newMessagesCount, oldMessagesCount) {
	      if (newMessagesCount - oldMessagesCount === 1) {
	        requestAnimationFrame(() => {
	          this.scrollMessagesListToTheEnd(true);
	        });
	      }
	      requestAnimationFrame(() => {
	        if (oldMessagesCount === 0 && newMessagesCount > 1 && this.isScrollToTheEndAfterMounted) {
	          this.scrollMessagesListToTheEnd();
	        }
	      });
	    }
	  },
	  template: `
		<div class="ai__copilot-chat">
			<header class="ai__copilot-chat_header">
				<CopilotChatHeader
					:title="headerProps.title"
					:subtitle="headerProps.subtitle"
					:avatar="headerProps.avatar"
					:use-close-icon="headerProps.useCloseIcon"
					:menu="headerProps.menu"
					@clickOnCloseIcon="hideChat"
				/>
			</header>
			<main ref="main" class="ai__copilot-chat_main">
				<div
					v-if="isLoaderShown"
					class="ai__copilot-chat_main-loader-container"
				>
					<slot name="loader">
						<CopilotChatHistoryLoader :text="loaderText" />
					</slot>
				</div>
				<div v-else-if="isShowErrorScreen">
					<slot name="loaderError">
						Sorry, we can't load messages, try later
					</slot>
				</div>
				<CopilotChatMessages
					v-else
					@clickMessageButton="handleClickOnMessageButton"
					:user-avatar="userPhoto"
					:copilot-avatar="botData.avatar"
					:messages="messagesList"
					:welcome-message-html-element="welcomeMessageHtml"
					:copilot-message-title="botData.messageTitle"
					:copilot-message-menu-items="botData.messageMenuItems"
					:user-message-menu-items="userMessageMenuItems"
				></CopilotChatMessages>
				<CopilotChatStatus
					v-if="isLoaderShown === false && isChatStatusUsed"
					:status="copilotChatStatus"
				/>
				<div id="anchor"></div>
			</main>
			<footer class="ai__copilot-chat_footer">
				<CopilotChatInput
					v-if="useInput"
					:disabled="isInputDisabled"
					:placeholder="inputPlaceholder"
					@submit="handleSubmitMessage"
				/>
				<div
					v-if="isWarningMessageShown"
					class="ai__copilot-chat_warning-message"
				>
					<CopilotChatWarningMessage />
				</div>
			</footer>
		</div>
	`
	};

	let _$2 = t => t,
	  _t$2;
	const CopilotChatEvents = Object.freeze({
	  ADD_USER_MESSAGE: 'addUserMessage',
	  ADD_BOT_MESSAGE: 'addBotMessage',
	  ADD_OLD_MESSAGES: 'addBotMessage',
	  ADD_NEW_MESSAGES: 'addMessages',
	  CLICK_ON_MESSAGE_BUTTON: 'clickOnMessageButton',
	  VIEW_NEW_MESSAGE: 'viewMessage',
	  SHOW_LOADER: 'showLoader',
	  HIDE_LOADER: 'hideLoader',
	  SHOW_COPILOT_WRITING_LOADER: 'showCopilotWritingLoader',
	  HIDE_COPILOT_WRITING_LOADER: 'hideCopilotWritingLoader',
	  DISABLE_INPUT_FIELD: 'disableInputField',
	  ENABLE_INPUT_FIELD: 'enableInputField',
	  SET_MESSAGE_STATUS: 'setMessageStatus',
	  SHOW_ERROR_SCREEN: 'showErrorScreen',
	  HIDE_ERROR_SCREEN: 'hideErrorScreen',
	  MESSAGES_SCROLL_TOP: 'messagesListScrollTop'
	});
	const CopilotChatMessageStatus = {
	  DEPART: 'depart',
	  SENT: 'sent',
	  DELIVERED: 'delivered',
	  ERROR: 'error'
	};
	var _copilotChatOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotChatOptions");
	var _popupOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popupOptions");
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _app = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("app");
	var _messages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("messages");
	var _isShowLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isShowLoader");
	var _isInputDisabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isInputDisabled");
	var _chatStatus = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("chatStatus");
	var _useChatStatus = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("useChatStatus");
	var _copilotMessageMenuItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotMessageMenuItems");
	var _userMessageMenuItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userMessageMenuItems");
	var _scrollToTheEndAfterFirstShow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("scrollToTheEndAfterFirstShow");
	var _showCopilotWarningMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showCopilotWarningMessage");
	var _copilotChatBotOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotChatBotOptions");
	var _userAvatar = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userAvatar");
	var _inputPlaceholder = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputPlaceholder");
	var _loaderText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loaderText");
	var _findMessageButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("findMessageButton");
	var _setMessageStatus = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setMessageStatus");
	var _addNewMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addNewMessage");
	var _initPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initPopup");
	var _renderPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderPopupContent");
	class CopilotChat$1 extends main_core_events.EventEmitter {
	  constructor(options = {}) {
	    var _options$botOptions, _options$userAvatar, _options$inputPlaceho;
	    super(options);
	    Object.defineProperty(this, _renderPopupContent, {
	      value: _renderPopupContent2
	    });
	    Object.defineProperty(this, _initPopup, {
	      value: _initPopup2
	    });
	    Object.defineProperty(this, _addNewMessage, {
	      value: _addNewMessage2
	    });
	    Object.defineProperty(this, _setMessageStatus, {
	      value: _setMessageStatus2
	    });
	    Object.defineProperty(this, _findMessageButton, {
	      value: _findMessageButton2
	    });
	    Object.defineProperty(this, _copilotChatOptions, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _popupOptions, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _app, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _messages, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isShowLoader, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isInputDisabled, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _chatStatus, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _useChatStatus, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _copilotMessageMenuItems, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _userMessageMenuItems, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _scrollToTheEndAfterFirstShow, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _showCopilotWarningMessage, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _copilotChatBotOptions, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _userAvatar, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputPlaceholder, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _loaderText, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('AI.CopilotChat');
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotChatOptions)[_copilotChatOptions] = options || {};
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotChatBotOptions)[_copilotChatBotOptions] = ui_vue3.ref((_options$botOptions = options == null ? void 0 : options.botOptions) != null ? _options$botOptions : {});
	    babelHelpers.classPrivateFieldLooseBase(this, _popupOptions)[_popupOptions] = (options == null ? void 0 : options.popupOptions) || {};
	    babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages] = ui_vue3.ref([]);
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotMessageMenuItems)[_copilotMessageMenuItems] = ui_vue3.ref(main_core.Type.isArray(options.botOptions.messageMenuItems) ? options.botOptions.messageMenuItems : []);
	    babelHelpers.classPrivateFieldLooseBase(this, _userMessageMenuItems)[_userMessageMenuItems] = ui_vue3.ref(main_core.Type.isArray(options.userMessageMenuItems) ? options.userMessageMenuItems : []);
	    babelHelpers.classPrivateFieldLooseBase(this, _isShowLoader)[_isShowLoader] = ui_vue3.ref(false);
	    babelHelpers.classPrivateFieldLooseBase(this, _isInputDisabled)[_isInputDisabled] = ui_vue3.ref(false);
	    babelHelpers.classPrivateFieldLooseBase(this, _chatStatus)[_chatStatus] = ui_vue3.ref(Status.NONE);
	    babelHelpers.classPrivateFieldLooseBase(this, _useChatStatus)[_useChatStatus] = ui_vue3.ref(main_core.Type.isBoolean(options.useChatStatus) ? options.useChatStatus : true);
	    babelHelpers.classPrivateFieldLooseBase(this, _scrollToTheEndAfterFirstShow)[_scrollToTheEndAfterFirstShow] = ui_vue3.ref(main_core.Type.isBoolean(options.scrollToTheEndAfterFirstShow) ? options.scrollToTheEndAfterFirstShow : true);
	    babelHelpers.classPrivateFieldLooseBase(this, _showCopilotWarningMessage)[_showCopilotWarningMessage] = ui_vue3.ref(options.showCopilotWarningMessage === true);
	    babelHelpers.classPrivateFieldLooseBase(this, _userAvatar)[_userAvatar] = ui_vue3.ref((_options$userAvatar = options.userAvatar) != null ? _options$userAvatar : '');
	    babelHelpers.classPrivateFieldLooseBase(this, _inputPlaceholder)[_inputPlaceholder] = (_options$inputPlaceho = options.inputPlaceholder) != null ? _options$inputPlaceho : main_core.Loc.getMessage('AI_COPILOT_CHAT_INPUT_PLACEHOLDER');
	    babelHelpers.classPrivateFieldLooseBase(this, _loaderText)[_loaderText] = options.loaderText;
	  }
	  static getDefaultMinWidth() {
	    return 375;
	  }
	  static getDefaultHeight() {
	    return 669;
	  }
	  isMessageInList(messageId) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].value.findLast(message => {
	      return message.id === messageId;
	    });
	  }
	  addUserMessage(message, emitEvent = true) {
	    const newUserMessage = {
	      type: 'Default',
	      ...message,
	      authorId: 1,
	      status: CopilotChatMessageStatus.DEPART
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _addNewMessage)[_addNewMessage](newUserMessage, emitEvent);
	  }
	  addBotMessage(message, emitEvent = true) {
	    const newUserMessage = {
	      type: 'Default',
	      ...message,
	      authorId: 0,
	      status: null
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _addNewMessage)[_addNewMessage](newUserMessage, emitEvent);
	  }
	  addSystemMessage(message, emitEvent = true) {
	    const newSystemMessage = {
	      ...message,
	      authorId: null,
	      status: null
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _addNewMessage)[_addNewMessage](newSystemMessage, emitEvent);
	  }
	  enableInput() {
	    babelHelpers.classPrivateFieldLooseBase(this, _isInputDisabled)[_isInputDisabled].value = false;
	    this.emit(CopilotChatEvents.ENABLE_INPUT_FIELD);
	  }
	  disableInput() {
	    babelHelpers.classPrivateFieldLooseBase(this, _isInputDisabled)[_isInputDisabled].value = true;
	    this.emit(CopilotChatEvents.DISABLE_INPUT_FIELD);
	  }
	  showLoader() {
	    babelHelpers.classPrivateFieldLooseBase(this, _isShowLoader)[_isShowLoader].value = true;
	    this.emit(CopilotChatEvents.SHOW_LOADER);
	  }
	  hideLoader() {
	    babelHelpers.classPrivateFieldLooseBase(this, _isShowLoader)[_isShowLoader].value = false;
	    this.emit(CopilotChatEvents.HIDE_LOADER);
	  }
	  setMessageStatusDepart(messageId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _setMessageStatus)[_setMessageStatus](messageId, CopilotChatMessageStatus.DEPART);
	  }
	  setMessageStatusDelivered(messageId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _setMessageStatus)[_setMessageStatus](messageId, CopilotChatMessageStatus.DELIVERED);
	  }
	  setMessageStatusSent(messageId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _setMessageStatus)[_setMessageStatus](messageId, CopilotChatMessageStatus.SENT);
	  }
	  setCopilotWritingStatus(value) {
	    if (value === true) {
	      babelHelpers.classPrivateFieldLooseBase(this, _chatStatus)[_chatStatus].value = Status.COPILOT_WRITING;
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _chatStatus)[_chatStatus].value = Status.NONE;
	    }
	  }
	  setNewMessageIsViewed(messageId) {
	    this.emit(CopilotChatEvents.VIEW_NEW_MESSAGE, new main_core_events.BaseEvent({
	      data: {
	        id: messageId
	      }
	    }));
	  }
	  setMessageId(messageId, newMessageId) {
	    const message = babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].value.find(currentMessage => currentMessage.id === messageId);
	    if (!message) {
	      return;
	    }
	    message.id = newMessageId;
	  }
	  setMessageDate(messageId, date) {
	    const message = babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].value.find(currentMessage => currentMessage.id === messageId);
	    if (!message) {
	      return;
	    }
	    message.dateCreated = date;
	  }
	  emitClickOnMessageButton(data) {
	    const {
	      buttonId,
	      messageId
	    } = data;
	    const clickedMessageButton = babelHelpers.classPrivateFieldLooseBase(this, _findMessageButton)[_findMessageButton](messageId, buttonId);
	    clickedMessageButton.isSelected = true;
	    this.emit(CopilotChatEvents.CLICK_ON_MESSAGE_BUTTON, {
	      messageId,
	      button: {
	        ...clickedMessageButton
	      }
	    });
	  }
	  show() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _initPopup)[_initPopup]();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	  }
	  hide() {
	    var _babelHelpers$classPr;
	    (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr.close();
	  }
	  isShown() {
	    var _babelHelpers$classPr2;
	    return Boolean((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr2.isShown());
	  }
	  adjustPosition() {
	    var _babelHelpers$classPr3;
	    (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr3.adjustPosition({
	      forceBindPosition: true
	    });
	  }
	  setUserAvatar(avatar) {
	    babelHelpers.classPrivateFieldLooseBase(this, _userAvatar)[_userAvatar].value = avatar;
	  }
	}
	function _findMessageButton2(messageId, buttonId) {
	  var _searchedMessage$para, _searchedMessage$para2;
	  const searchedMessage = babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].value.find(message => message.id === messageId);
	  if (main_core.Type.isArray((_searchedMessage$para = searchedMessage.params) == null ? void 0 : _searchedMessage$para.buttons) === false) {
	    return null;
	  }
	  return (_searchedMessage$para2 = searchedMessage.params.buttons.find(button => button.id === buttonId)) != null ? _searchedMessage$para2 : null;
	}
	function _setMessageStatus2(messageId, status) {
	  const message = babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].value.find(currentMessage => currentMessage.id === messageId);
	  if (!message) {
	    return;
	  }
	  message.status = status;
	  this.emit(CopilotChatEvents.SET_MESSAGE_STATUS, {
	    messageId,
	    status
	  });
	}
	function _addNewMessage2(message, emitEvent = true) {
	  var _message$viewed;
	  const newMessageId = Math.round(-Math.random() * 1000);
	  const isCopilotChatShown = this.isShown();
	  const newMessage = {
	    id: newMessageId,
	    dateCreated: new Date().toISOString(),
	    status: CopilotChatMessageStatus.DEPART,
	    authorId: 0,
	    ...message,
	    viewed: (_message$viewed = message == null ? void 0 : message.viewed) != null ? _message$viewed : isCopilotChatShown
	  };
	  babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].value.push(newMessage);
	  if (emitEvent === false) {
	    return;
	  }
	  if (newMessage.authorId === 0) {
	    this.emit(CopilotChatEvents.ADD_BOT_MESSAGE, {
	      message: newMessage
	    });
	  } else {
	    this.emit(CopilotChatEvents.ADD_USER_MESSAGE, {
	      message: newMessage
	    });
	  }
	}
	function _initPopup2() {
	  var _babelHelpers$classPr4, _babelHelpers$classPr5, _babelHelpers$classPr6, _babelHelpers$classPr7, _babelHelpers$classPr8, _babelHelpers$classPr9, _babelHelpers$classPr10;
	  const adjustPopupPosition = this.adjustPosition.bind(this);
	  main_core.bind(window, 'resize', adjustPopupPosition);
	  babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = new main_popup.Popup({
	    ...babelHelpers.classPrivateFieldLooseBase(this, _popupOptions)[_popupOptions],
	    content: babelHelpers.classPrivateFieldLooseBase(this, _renderPopupContent)[_renderPopupContent](),
	    minWidth: (_babelHelpers$classPr4 = (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _popupOptions)[_popupOptions]) == null ? void 0 : _babelHelpers$classPr5.minWidth) != null ? _babelHelpers$classPr4 : CopilotChat$1.getDefaultMinWidth(),
	    height: (_babelHelpers$classPr6 = (_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _popupOptions)[_popupOptions]) == null ? void 0 : _babelHelpers$classPr7.height) != null ? _babelHelpers$classPr6 : CopilotChat$1.getDefaultHeight(),
	    contentNoPaddings: true,
	    padding: 0,
	    borderRadius: '16px',
	    className: `ai__copilot-chat-popup ${(_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _popupOptions)[_popupOptions].className) != null ? _babelHelpers$classPr8 : ''}`,
	    cacheable: (_babelHelpers$classPr9 = (_babelHelpers$classPr10 = babelHelpers.classPrivateFieldLooseBase(this, _popupOptions)[_popupOptions]) == null ? void 0 : _babelHelpers$classPr10.cacheable) != null ? _babelHelpers$classPr9 : false,
	    events: {
	      onPopupAfterClose: () => {
	        var _babelHelpers$classPr11, _babelHelpers$classPr12;
	        babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = null;
	        babelHelpers.classPrivateFieldLooseBase(this, _app)[_app].unmount();
	        if (main_core.Type.isFunction((_babelHelpers$classPr11 = babelHelpers.classPrivateFieldLooseBase(this, _popupOptions)[_popupOptions]) == null ? void 0 : (_babelHelpers$classPr12 = _babelHelpers$classPr11.events) == null ? void 0 : _babelHelpers$classPr12.onPopupAfterClose)) {
	          var _babelHelpers$classPr13, _babelHelpers$classPr14;
	          (_babelHelpers$classPr13 = babelHelpers.classPrivateFieldLooseBase(this, _popupOptions)[_popupOptions]) == null ? void 0 : (_babelHelpers$classPr14 = _babelHelpers$classPr13.events) == null ? void 0 : _babelHelpers$classPr14.onPopupAfterClose();
	        }
	        main_core.unbind(window, 'resize', adjustPopupPosition);
	      }
	    }
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup];
	}
	function _renderPopupContent2() {
	  var _babelHelpers$classPr15, _babelHelpers$classPr16, _babelHelpers$classPr17, _babelHelpers$classPr18, _babelHelpers$classPr19;
	  const appContainer = main_core.Tag.render(_t$2 || (_t$2 = _$2`<div class="ai__copilot-chat-popup-content"></div>`));
	  babelHelpers.classPrivateFieldLooseBase(this, _app)[_app] = ui_vue3.BitrixVue.createApp({
	    name: 'CopilotChatPopup',
	    components: {
	      CopilotChat: CopilotChat,
	      ...((_babelHelpers$classPr15 = babelHelpers.classPrivateFieldLooseBase(this, _copilotChatOptions)[_copilotChatOptions]) == null ? void 0 : _babelHelpers$classPr15.vueComponents)
	    },
	    template: `
				<CopilotChat>
					<template v-slot:loader>
						${(_babelHelpers$classPr16 = (_babelHelpers$classPr17 = babelHelpers.classPrivateFieldLooseBase(this, _copilotChatOptions)[_copilotChatOptions].slots) == null ? void 0 : _babelHelpers$classPr17.LOADER) != null ? _babelHelpers$classPr16 : ''}
					</template>
					<template v-slot:loaderError>
						${(_babelHelpers$classPr18 = (_babelHelpers$classPr19 = babelHelpers.classPrivateFieldLooseBase(this, _copilotChatOptions)[_copilotChatOptions].slots) == null ? void 0 : _babelHelpers$classPr19.LOADER_ERROR) != null ? _babelHelpers$classPr18 : ''}
					</template>
				</CopilotChat>
			`
	  }, {
	    ...babelHelpers.classPrivateFieldLooseBase(this, _copilotChatOptions)[_copilotChatOptions],
	    messages: babelHelpers.classPrivateFieldLooseBase(this, _messages)[_messages].value,
	    copilotChatInstance: this,
	    showLoader: babelHelpers.classPrivateFieldLooseBase(this, _isShowLoader)[_isShowLoader],
	    disableInput: babelHelpers.classPrivateFieldLooseBase(this, _isInputDisabled)[_isInputDisabled],
	    status: babelHelpers.classPrivateFieldLooseBase(this, _chatStatus)[_chatStatus],
	    useStatus: babelHelpers.classPrivateFieldLooseBase(this, _useChatStatus)[_useChatStatus],
	    scrollToTheEndAfterFirstShow: babelHelpers.classPrivateFieldLooseBase(this, _scrollToTheEndAfterFirstShow)[_scrollToTheEndAfterFirstShow],
	    copilotMessageMenuItems: babelHelpers.classPrivateFieldLooseBase(this, _copilotMessageMenuItems)[_copilotMessageMenuItems].value,
	    userMessageMenuItems: babelHelpers.classPrivateFieldLooseBase(this, _userMessageMenuItems)[_userMessageMenuItems].value,
	    isShowWarningMessage: babelHelpers.classPrivateFieldLooseBase(this, _showCopilotWarningMessage)[_showCopilotWarningMessage],
	    botOptions: babelHelpers.classPrivateFieldLooseBase(this, _copilotChatBotOptions)[_copilotChatBotOptions].value,
	    userAvatar: babelHelpers.classPrivateFieldLooseBase(this, _userAvatar)[_userAvatar],
	    inputPlaceholder: babelHelpers.classPrivateFieldLooseBase(this, _inputPlaceholder)[_inputPlaceholder],
	    loaderText: babelHelpers.classPrivateFieldLooseBase(this, _loaderText)[_loaderText]
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _app)[_app].mount(appContainer);
	  return appContainer;
	}

	exports.CopilotChat = CopilotChat$1;
	exports.CopilotChatEvents = CopilotChatEvents;
	exports.CopilotChatMessageType = CopilotChatMessageType;

}((this.BX.AI.CopilotChat.UI = this.BX.AI.CopilotChat.UI || {}),BX,BX.AI,BX.Event,BX.UI.IconSet,BX.Vue3,BX,BX.Main,BX.Main,BX.UI.IconSet,BX.UI.BBCode.Formatter,BX,BX));
//# sourceMappingURL=copilot-chat.bundle.js.map
