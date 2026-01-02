/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,ui_vue3,pull_queuemanager,ui_buttons,ui_bbcode_parser,ui_textEditor,ui_iconSet_main,ui_notification,ui_entitySelector,ui_infoHelper,ui_forms,main_date,crm_timeline_tools,main_core_events,ui_datePicker,main_core) {
	'use strict';

	const BreadcrumbsEvents = {
	  itemClick: 'crm:copilot:call-assessment:breadcrumb-item-click'
	};
	const Breadcrumbs = {
	  props: {
	    activeTabId: {
	      type: String
	    }
	  },
	  data() {
	    return {
	      tabs: this.getTabsData()
	    };
	  },
	  methods: {
	    itemTitleClassList(id, isSoon) {
	      return ['crm-copilot__call-assessment_breadcrumbs-item-title', {
	        '--active': id === this.activeTabId
	      }, {
	        '--soon': isSoon
	      }];
	    },
	    getTab(id) {
	      return this.getTabsData().find(tab => tab.id === id);
	    },
	    getTabsData() {
	      return [{
	        id: 'about',
	        title: main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_ABOUT')
	      }, {
	        id: 'client',
	        title: main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT')
	      }, {
	        id: 'settings',
	        title: main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS')
	      }, {
	        id: 'control',
	        title: main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL')
	      }];
	    },
	    emitClickEvent(itemId) {
	      const tab = this.getTab(itemId);
	      if (!tab.soon) {
	        this.$Bitrix.eventEmitter.emit(BreadcrumbsEvents.itemClick, {
	          itemId
	        });
	      }
	    }
	  },
	  computed: {
	    soonTitle() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_BREADCRUMBS_SOON');
	    }
	  },
	  template: `
		<div class="crm-copilot__call-assessment_breadcrumbs-wrapper">
			<div
				v-for="(tab, index) in tabs"
				@click="emitClickEvent(tab.id)"
				class="crm-copilot__call-assessment_breadcrumbs-item"
			>
				<span v-if="tab.soon" class="crm-copilot__call-assessment_breadcrumbs-soon">
					{{ soonTitle }}
				</span>
				<span :class="itemTitleClassList(tab.id, tab.soon)">
					{{ tab.title }}
				</span>
				<span 
					v-if="index+1 < tabs.length"
					class="crm-copilot__call-assessment_breadcrumbs-divider"
				></span>
			</div>
		</div>
	`
	};

	const ButtonEvents = {
	  click: 'crm:copilot:call-assessment:navigation-button-click'
	};
	const Button = {
	  props: {
	    id: {
	      type: String,
	      required: true
	    },
	    isEnabled: {
	      type: Boolean,
	      default: true
	    }
	  },
	  mounted() {
	    this.initButton();
	  },
	  watch: {
	    isEnabled(value) {
	      this.button.setState(value ? ui_buttons.ButtonState.ACTIVE : ui_buttons.ButtonState.DISABLED);
	    }
	  },
	  methods: {
	    initButton() {
	      this.button = new ui_buttons.Button({
	        text: main_core.Loc.getMessage(`CRM_COPILOT_CALL_ASSESSMENT_NAVIGATION_BUTTON_${this.id.toUpperCase()}`),
	        color: this.buttonColor,
	        round: true,
	        size: BX.UI.Button.Size.LARGE,
	        onclick: () => {
	          this.emitClickEvent();
	        }
	      });
	      if (this.isEnabled !== true) {
	        this.button.setState(ui_buttons.ButtonState.DISABLED);
	      }
	      this.button.setDataSet({
	        id: `crm-copilot-call-assessment-buttons-${this.id.toLowerCase()}`
	      });
	      if (this.$refs.button) {
	        this.button.renderTo(this.$refs.button);
	      }
	    },
	    emitClickEvent() {
	      if (this.isEnabled) {
	        this.$Bitrix.eventEmitter.emit(ButtonEvents.click, {
	          id: this.id
	        });
	      }
	    }
	  },
	  computed: {
	    buttonColor() {
	      if (this.id === 'continue' || this.id === 'submit' || this.id === 'close') {
	        return ui_buttons.Button.Color.SUCCESS;
	      }
	      if (this.id === 'update') {
	        return ui_buttons.Button.Color.LIGHT_BORDER;
	      }
	      return ui_buttons.Button.Color.LIGHT;
	    }
	  },
	  template: `
		<div ref="button" class="crm-copilot-call-assessment-button"></div>
	`
	};

	const ARTICLE_CODE = '23240682';
	const Navigation = {
	  components: {
	    Button
	  },
	  props: {
	    activeTabId: {
	      type: String
	    },
	    isEnabled: {
	      type: Boolean
	    },
	    readOnly: {
	      type: Boolean,
	      default: false
	    },
	    showSaveButton: {
	      type: Boolean,
	      default: false
	    }
	  },
	  data() {
	    return {
	      firstPage: 'about',
	      lastPage: 'control'
	    };
	  },
	  computed: {
	    help() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_NAVIGATION_HELP');
	    },
	    buttons() {
	      return ['cancel', 'back', 'continue', 'submit', 'close', 'update'];
	    }
	  },
	  methods: {
	    showArticle() {
	      var _window$top$BX, _window$top$BX$Helper;
	      (_window$top$BX = window.top.BX) == null ? void 0 : (_window$top$BX$Helper = _window$top$BX.Helper) == null ? void 0 : _window$top$BX$Helper.show(`redirect=detail&code=${ARTICLE_CODE}`);
	    }
	  },
	  template: `
		<div class="crm-copilot__call-assessment_navigation-container">
			<div class="crm-copilot__call-assessment_navigation-buttons-wrapper">
				<Button v-if="activeTabId !== lastPage" id="continue" :is-enabled="isEnabled" />
				<Button v-if="activeTabId === lastPage && !readOnly" id="submit" />
				<Button v-if="activeTabId === lastPage && readOnly" id="close" />
				<Button v-if="activeTabId === firstPage" id="cancel" />
				<Button v-if="activeTabId !== firstPage" id="back" />
			</div>
			<div v-if="showSaveButton && activeTabId !== lastPage">
				<Button id="update" :is-enabled="isEnabled" />
			</div>
			<div v-else class="crm-copilot__call-assessment_article" @click="showArticle">
				<span class="ui-icon-set --help"></span>
				{{ help }}
			</div>
		</div>
	`
	};

	const TextEditorWrapperComponent = {
	  components: {
	    TextEditorComponent: ui_textEditor.TextEditorComponent
	  },
	  emits: ['change'],
	  props: {
	    textEditor: ui_textEditor.TextEditor
	  },
	  data() {
	    return {
	      textEditorEvents: {
	        onChange: this.emitChangeData
	      }
	    };
	  },
	  mounted() {
	    this.setTextEditorHeight();
	    this.windowResizeHandler = this.windowResizeHandler.bind(this);
	    main_core.Event.bind(window, 'resize', this.windowResizeHandler);
	  },
	  unmounted() {
	    main_core.Event.unbind(window, 'resize', this.windowResizeHandler);
	  },
	  methods: {
	    emitChangeData() {
	      if (!main_core.Type.isFunction(this.onChangeDebounce)) {
	        this.onChangeDebounce = main_core.Runtime.debounce(this.onChange, 100, this);
	      }
	      this.onChangeDebounce();
	    },
	    onChange() {
	      this.$emit('change', {
	        prompt: this.textEditor.getText()
	      });
	    },
	    windowResizeHandler() {
	      this.setTextEditorHeight();
	    },
	    setTextEditorHeight() {
	      var _document$querySelect, _document$querySelect2, _this$textEditor3;
	      const editorOffsetTop = this.$el.parentNode.offsetTop;
	      const navigationClassName = '.crm-copilot__call-assessment_navigation-container';
	      const navigationOffsetTop = (_document$querySelect = (_document$querySelect2 = document.querySelector(navigationClassName)) == null ? void 0 : _document$querySelect2.offsetTop) != null ? _document$querySelect : 0;
	      const textEditorContainerBottomPadding = 20;
	      const availableHeight = navigationOffsetTop - editorOffsetTop - textEditorContainerBottomPadding;
	      const minHeight = Math.round(availableHeight * 0.5);
	      const maxHeight = Math.round(availableHeight * 0.8);
	      if (minHeight < 200) {
	        var _this$textEditor;
	        (_this$textEditor = this.textEditor) == null ? void 0 : _this$textEditor.setMinHeight(maxHeight);
	      } else {
	        var _this$textEditor2;
	        (_this$textEditor2 = this.textEditor) == null ? void 0 : _this$textEditor2.setMinHeight(minHeight);
	      }
	      (_this$textEditor3 = this.textEditor) == null ? void 0 : _this$textEditor3.setMaxHeight(maxHeight);
	    }
	  },
	  template: `
		<TextEditorComponent 
			:events="textEditorEvents"
			:editor-instance="textEditor"
		/>
	`
	};

	const LENGTH_RANGE = {
	  MIN: 100,
	  MAX: 5000
	};
	var _errorMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("errorMessage");
	class PromptLength {
	  constructor() {
	    Object.defineProperty(this, _errorMessage, {
	      writable: true,
	      value: null
	    });
	  }
	  validate(prompt) {
	    if (prompt.length < LENGTH_RANGE.MIN) {
	      babelHelpers.classPrivateFieldLooseBase(this, _errorMessage)[_errorMessage] = main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_ABOUT_PROMPT_LENGTH_MIN_ERROR', {
	        '#MIN_VALUE#': LENGTH_RANGE.MIN
	      });
	      return false;
	    }
	    if (prompt.length > LENGTH_RANGE.MAX) {
	      babelHelpers.classPrivateFieldLooseBase(this, _errorMessage)[_errorMessage] = main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_ABOUT_PROMPT_LENGTH_MAX_ERROR', {
	        '#MAX_VALUE#': LENGTH_RANGE.MAX
	      });
	      return false;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _errorMessage)[_errorMessage] = null;
	    return true;
	  }
	  getError() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _errorMessage)[_errorMessage];
	  }
	}

	const AiDisabledInSettings = {
	  methods: {
	    showLimitCopilotOffSlider() {
	      var _top$BX$UI, _top$BX$UI$InfoHelper;
	      (_top$BX$UI = top.BX.UI) == null ? void 0 : (_top$BX$UI$InfoHelper = _top$BX$UI.InfoHelper) == null ? void 0 : _top$BX$UI$InfoHelper.show('limit_v2_crm_copilot_call_assessment_off');
	    }
	  },
	  computed: {
	    message() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_AI_DISABLED');
	    }
	  },
	  template: `
		<div class="crm-copilot__call-assessment-ai-disabled">
			<span v-html="message"></span>
			<span
				@click="showLimitCopilotOffSlider"
				class="ui-icon-set --help"
			></span>
		</div>
	`
	};

	const BasePage = {
	  components: {
	    Breadcrumbs,
	    AiDisabledInSettings
	  },
	  props: {
	    readOnly: {
	      type: Boolean,
	      default: false
	    },
	    isEnabled: {
	      type: Boolean,
	      default: true
	    },
	    isActive: {
	      type: Boolean
	    },
	    data: {
	      type: Object,
	      default: {}
	    },
	    settings: {
	      type: Object,
	      default: {}
	    }
	  },
	  data() {
	    return {
	      id: null // must be defined in child class
	    };
	  },

	  methods: {
	    getId() {
	      return this.id;
	    },
	    getData() {
	      // must be implement in child class

	      return {};
	    },
	    isReadyToMoveOn() {
	      return this.validate();
	    },
	    validate() {
	      // must be implement in child class

	      return true;
	    },
	    onValidationFailed() {
	      ui_notification.UI.Notification.Center.notify({
	        content: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_VALIDATION_ERROR'),
	        autoHideDelay: 3000
	      });
	    },
	    emitChangeData() {
	      if (!main_core.Type.isFunction(this.onChangeDebounce)) {
	        this.onChangeDebounce = main_core.Runtime.debounce(this.onChange, 100, this);
	      }
	      this.onChangeDebounce();
	    },
	    onChange() {
	      this.$emit('onChange', {
	        id: this.id,
	        data: this.getData()
	      });
	    },
	    getBodyFieldClassList(additionalClasses = []) {
	      return ['crm-copilot__call-assessment_page-section-body-field', ...additionalClasses, {
	        '--read-only': this.isEnabled !== true
	      }];
	    }
	  }
	};

	const AboutPage = {
	  extends: BasePage,
	  components: {
	    TextEditorComponent: ui_textEditor.TextEditorComponent,
	    TextEditorWrapperComponent
	  },
	  props: {
	    textEditor: ui_textEditor.TextEditor
	  },
	  data() {
	    return {
	      id: 'about',
	      parser: new ui_bbcode_parser.BBCodeParser(),
	      promptLengthValidator: new PromptLength(),
	      promptLengthError: null
	    };
	  },
	  methods: {
	    getData() {
	      return {
	        prompt: this.textEditor.getText()
	      };
	    },
	    isReadyToMoveOn() {
	      return main_core.Type.isStringFilled(this.textEditor.getText());
	    },
	    validate() {
	      return this.promptLengthValidator.validate(this.getPlainPromptText());
	    },
	    onValidationFailed() {
	      this.promptLengthError = this.promptLengthValidator.getError();
	    },
	    onChange() {
	      this.promptLengthError = null;
	      BasePage.methods.onChange.call(this);
	    },
	    getPlainPromptText() {
	      return this.parser.parse(this.textEditor.getText()).toPlainText().trim();
	    }
	  },
	  computed: {
	    pageTitle() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_ABOUT_TITLE');
	    },
	    pageDescription() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_ABOUT_DESCRIPTION');
	    }
	  },
	  template: `
		<div v-if="isActive">
			<div class="crm-copilot__call-assessment_page-section">
				<Breadcrumbs :active-tab-id="id" />
				<header class="crm-copilot__call-assessment_page-section-header">
					<div class="crm-copilot__call-assessment_page-section-header-title">
						{{ pageTitle }}
					</div>
					<div class="crm-copilot__call-assessment_page-section-header-description">
						{{ pageDescription }}
					</div>
				</header>
				<div class="crm-copilot__call-assessment_page-section-body">
					<AiDisabledInSettings v-if="!isEnabled" />
					<div :class="this.getBodyFieldClassList()">
						<TextEditorWrapperComponent
							:textEditor="textEditor"
							@change="onChange"
						/>
						<div v-if="promptLengthError" class="crm-copilot__call-assessment_page-section-body-field-error">
							{{ promptLengthError }}
						</div>
					</div>
				</div>
			</div>
		</div>
	`
	};

	const clientType = Object.freeze({
	  new: 1,
	  inWork: 2,
	  return: 3,
	  repeated: 4
	});

	const ClientPage = {
	  extends: BasePage,
	  data() {
	    var _this$data$activeClie;
	    return {
	      id: 'client',
	      activeClientTypeIds: (_this$data$activeClie = this.data.activeClientTypeIds) != null ? _this$data$activeClie : []
	    };
	  },
	  methods: {
	    getData() {
	      return {
	        clientTypeIds: [...this.activeClientTypeIds]
	      };
	    },
	    getClientTypeClassList(clientTypeId) {
	      const clientType$$1 = this.clientTypes.find(client => client.id === clientTypeId);
	      return ['crm-copilot__call-assessment_page-section-body-field-client-type', `--client-${clientType$$1.id}`, {
	        '--active': this.activeClientTypeIds.includes(clientTypeId)
	      }, {
	        '--readonly': this.readOnly
	      }];
	    },
	    onClientTypeSelect(clientTypeId) {
	      if (this.readOnly) {
	        return;
	      }
	      if (this.activeClientTypeIds.includes(clientTypeId)) {
	        const index = this.activeClientTypeIds.indexOf(clientTypeId);
	        this.activeClientTypeIds.splice(index, 1);
	      } else {
	        this.activeClientTypeIds.push(clientTypeId);
	      }
	      this.emitChangeData();
	    },
	    onCheckboxClick() {
	      return !this.readOnly;
	    },
	    validate() {
	      return main_core.Type.isArrayFilled(this.activeClientTypeIds);
	    }
	  },
	  computed: {
	    pageTitle() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT_TITLE');
	    },
	    pageDescription() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT_DESCRIPTION');
	    },
	    clientTypes() {
	      return [{
	        id: clientType.new,
	        title: main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT_TYPE_NEW_TITLE'),
	        description: main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT_TYPE_NEW_DESCRIPTION')
	      }, {
	        id: clientType.inWork,
	        title: main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT_TYPE_IN_WORK_TITLE'),
	        description: main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT_TYPE_IN_WORK_DESCRIPTION')
	      }, {
	        id: clientType.return,
	        title: main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT_TYPE_RETURN_CUSTOMER_TITLE'),
	        description: main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT_TYPE_RETURN_CUSTOMER_DESCRIPTION')
	      }, {
	        id: clientType.repeated,
	        title: main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT_TYPE_REPEATED_APPROACH_TITLE'),
	        description: main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CLIENT_TYPE_REPEATED_APPROACH_DESCRIPTION')
	      }];
	    }
	  },
	  template: `
		<div v-if="isActive">
			<div class="crm-copilot__call-assessment_page-section">
				<Breadcrumbs :active-tab-id="id" />
				<header class="crm-copilot__call-assessment_page-section-header">
					<div class="crm-copilot__call-assessment_page-section-header-title">
						{{ pageTitle }}
					</div>
					<div class="crm-copilot__call-assessment_page-section-header-description">
						{{ pageDescription }}
					</div>
				</header>
				<div class="crm-copilot__call-assessment_page-section-body">
					<AiDisabledInSettings v-if="!isEnabled" />
					<div :class="this.getBodyFieldClassList()">
						<div v-for="(clientType, index) in clientTypes">
							<div
								:class=getClientTypeClassList(clientType.id)
								@click="onClientTypeSelect(clientType.id)"
							>
								<div class="crm-copilot__call-assessment_page-section-body-field-client-type-avatar">
								</div>
								<div class="crm-copilot__call-assessment_page-section-body-field-client-type-info">
									<div class="crm-copilot__call-assessment_page-section-body-field-client-type-title">
										{{ clientType.title }}
									</div>
									<div class="crm-copilot__call-assessment_page-section-body-field-client-type-description">
										{{ clientType.description }}
									</div>
								</div>
								<input
									:class="(readOnly ? 'readonly' : '')"
									:onclick="onCheckboxClick"
									type="checkbox"
									v-model="activeClientTypeIds"
									:value="clientType.id"
								>
							</div>
							<hr
								v-if="index + 1 < clientTypes.length"
								class="crm-copilot__call-assessment_page-section-body-field-client-type-divider"
							>
						</div>
					</div>
				</div>
			</div>
		</div>
	`
	};

	var _errorMessage$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("errorMessage");
	class BordersAccord {
	  constructor() {
	    Object.defineProperty(this, _errorMessage$1, {
	      writable: true,
	      value: null
	    });
	  }
	  validate(lowBorder, highBorder) {
	    if (highBorder < lowBorder) {
	      babelHelpers.classPrivateFieldLooseBase(this, _errorMessage$1)[_errorMessage$1] = main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_VALIDATION_ERROR');
	      return false;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _errorMessage$1)[_errorMessage$1] = null;
	    return true;
	  }
	  getError() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _errorMessage$1)[_errorMessage$1];
	  }
	}

	const DELTA = 10;
	const Pill = {
	  emits: ['change'],
	  props: {
	    value: {
	      type: Number,
	      required: true
	    },
	    additionalClass: {
	      type: String
	    }
	  },
	  data() {
	    return {
	      currentValue: this.value
	    };
	  },
	  methods: {
	    onInlineValueKeyDown(event) {
	      const {
	        key,
	        code,
	        ctrlKey,
	        metaKey
	      } = event;
	      if (metaKey || ctrlKey) {
	        const allowedKeysWithCtrlAndMeta = ['KeyA', 'KeyZ', 'KeyC', 'KeyV', 'KeyR', 'KeyX'];
	        if (!allowedKeysWithCtrlAndMeta.includes(code)) {
	          event.preventDefault();
	          return false;
	        }
	        return true;
	      }
	      const allowedKeys = ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Backspace', 'Delete'];
	      if (allowedKeys.includes(code)) {
	        return true;
	      }
	      if (key === ' ' || Number.isNaN(Number(key))) {
	        event.preventDefault();
	        return false;
	      }
	      return true;
	    },
	    onInlineValueChange(event) {
	      const value = Number(this.$refs.input.innerText);
	      this.executeValueChange(value);
	      this.$nextTick(() => {
	        const range = document.createRange();
	        const selection = window.getSelection();
	        range.selectNodeContents(this.$refs.input);
	        range.collapse(false);
	        selection.removeAllRanges();
	        selection.addRange(range);
	        this.$refs.input.focus();
	      });
	    },
	    onInlineValueBlur() {
	      const onlyNums = this.$refs.input.innerText.replaceAll(/\D/g, '');
	      if (onlyNums === '') {
	        this.executeValueChange(0);
	      }
	    },
	    changeValue(action) {
	      let value = action === 'plus' ? this.value + DELTA : this.value - DELTA;
	      if (value % DELTA > 0) {
	        value = Math.ceil(value / DELTA) * 10;
	      }
	      this.executeValueChange(value);
	    },
	    executeValueChange(value, action = null) {
	      this.currentValue = value;
	      this.$refs.input.innerText = value === 0 ? 0 : this.$refs.input.innerText.replace(/^0+/, '');

	      // @todo animation will be redone
	      // if (action)
	      // {
	      // 	const target = (action === 'plus' ? this.$refs.plus : this.$refs.minus);
	      // 	const timeoutIdName = (action === 'plus' ? 'timeoutIdPlus' : 'timeoutIdMinus');
	      //
	      // 	Dom.removeClass(target, '--click');
	      // 	setTimeout(() => {
	      // 		if (Type.isNumber(this[timeoutIdName]))
	      // 		{
	      // 			clearTimeout(this[timeoutIdName]);
	      // 		}
	      // 		Dom.addClass(target, '--click');
	      // 		this[timeoutIdName] = setTimeout(() => {
	      // 			Dom.removeClass(target, '--click');
	      // 		}, 600);
	      // 	}, 10);
	      // }

	      main_core.Runtime.debounce(() => {
	        this.$emit('change', this.currentValue);
	      }, 300, this)();
	    }
	  },
	  computed: {
	    title() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PILL_TITLE');
	    },
	    classList() {
	      return ['crm-copilot__call-assessment-pill', this.additionalClass];
	    },
	    minusButtonClassList() {
	      return ['crm-copilot__call-assessment-pill-minus', {
	        '--disabled': this.currentValue <= 0
	      }];
	    },
	    plusButtonClassList() {
	      return ['crm-copilot__call-assessment-pill-plus', {
	        '--disabled': this.currentValue >= 100
	      }];
	    }
	  },
	  watch: {
	    currentValue(value) {
	      if (value <= 0) {
	        this.currentValue = 0;
	      }
	      if (value >= 100) {
	        this.currentValue = 100;
	      }
	      if (Number.isNaN(value)) {
	        this.currentValue = 0;
	      }
	      this.$refs.input.innerText = this.currentValue;
	    }
	  },
	  template: `
		<div :class="classList">
			<div class="crm-copilot__call-assessment-pill-title" v-html="title">
			</div>
			<div class="crm-copilot__call-assessment-pill-control">
				<div
					:class="minusButtonClassList"
					@click="changeValue('minus')"
					ref="minus"
				>
				</div>
				<div class="crm-copilot__call-assessment-pill-value-container">
					<div 
						class="crm-copilot__call-assessment-pill-value"
						contenteditable="true"
						type="text"
						@keydown="onInlineValueKeyDown"
						@input="onInlineValueChange"
						@blur="onInlineValueBlur"
						ref="input"
					>
						{{ currentValue }}
					</div>
					<div class="crm-copilot__call-assessment-pill-percent">
					</div>
				</div>
				<div
					:class="plusButtonClassList"
					@click="changeValue('plus')"
					ref="plus"
				>
				</div>
			</div>
		</div>
	`
	};

	const HelpLink = {
	  props: {
	    articleCode: {
	      type: String,
	      required: true
	    }
	  },
	  methods: {
	    onClick() {
	      var _window$top$BX, _window$top$BX$Helper;
	      (_window$top$BX = window.top.BX) == null ? void 0 : (_window$top$BX$Helper = _window$top$BX.Helper) == null ? void 0 : _window$top$BX$Helper.show(`redirect=detail&code=${this.articleCode}`);
	    }
	  },
	  template: `
		<span class="crm-copilot__call-assessment-help-link" ref="container" @click="onClick">
			${main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_HELP')}
		</span>
	`
	};

	const TextWithDialog = {
	  components: {
	    HelpLink
	  },
	  props: {
	    id: {
	      type: String,
	      required: true
	    },
	    content: {
	      type: String,
	      required: true
	    },
	    dialogTargetId: {
	      type: String,
	      required: false,
	      default: null
	    },
	    articleCode: {
	      type: String,
	      required: true
	    },
	    value: {
	      type: Number
	    },
	    items: {
	      type: Array
	    },
	    tabs: {
	      type: Array
	    },
	    selectedItems: {
	      type: Array
	    }
	  },
	  data() {
	    var _this$isChecked, _this$value;
	    return {
	      status: (_this$isChecked = this.isChecked) != null ? _this$isChecked : false,
	      currentValue: (_this$value = this.value) != null ? _this$value : null
	    };
	  },
	  mounted() {
	    this.initDialog();
	  },
	  updated() {
	    this.initDialog();
	  },
	  watch: {
	    currentValue(value) {
	      this.emitSelectItem(value);
	    }
	  },
	  methods: {
	    initDialog() {
	      var _this$items, _this$tabs, _this$selectedItems;
	      if (this.dialogTargetId === null) {
	        return;
	      }
	      const targetNode = document.getElementById(this.dialogTargetId);
	      this.clientSelectorDialog = new ui_entitySelector.Dialog({
	        targetNode,
	        context: `CRM_COPILOT_CALL_ASSESSMENT_DIALOG_${this.id}`,
	        multiple: false,
	        dropdownMode: true,
	        showAvatars: false,
	        enableSearch: false,
	        width: 450,
	        zIndex: 2500,
	        items: (_this$items = this.items) != null ? _this$items : [],
	        tabs: (_this$tabs = this.tabs) != null ? _this$tabs : [],
	        selectedItems: (_this$selectedItems = this.selectedItems) != null ? _this$selectedItems : [],
	        events: {
	          'Item:onSelect': event => {
	            const {
	              item: {
	                id
	              }
	            } = event.getData();
	            this.currentValue = id;
	          }
	        }
	      });
	      main_core.Event.bind(targetNode, 'click', () => {
	        this.clientSelectorDialog.show();
	      });
	    },
	    emitSelectItem(itemId) {
	      this.$emit('onSelectItem', itemId);
	    }
	  },
	  template: `
		<div>
			<span
				class="crm-copilot__call-assessment_call_count"
				v-html="content"
			></span>
			<HelpLink :articleCode="articleCode" />
		</div>
	`
	};

	const ControlPage = {
	  extends: BasePage,
	  components: {
	    TextWithDialog,
	    Pill
	  },
	  data() {
	    return {
	      id: 'control',
	      lowBorder: this.data.lowBorder,
	      highBorder: this.data.highBorder,
	      validator: new BordersAccord()
	    };
	  },
	  methods: {
	    getData() {
	      return {
	        lowBorder: this.lowBorder,
	        highBorder: this.highBorder
	      };
	    },
	    setLowBorder(value) {
	      this.lowBorder = value;
	    },
	    setHighBorder(value) {
	      this.highBorder = value;
	    },
	    validate() {
	      return this.validator.validate(this.lowBorder, this.highBorder);
	    },
	    onValidationFailed() {
	      ui_notification.UI.Notification.Center.notify({
	        content: this.validator.getError(),
	        autoHideDelay: 3000
	      });
	    }
	  },
	  computed: {
	    pageTitle() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_TITLE');
	    },
	    pageDescription() {
	      return main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_DESCRIPTION');
	    }
	  },
	  template: `
		<div v-if="isActive">
			<div class="crm-copilot__call-assessment_page-section">
				<Breadcrumbs :active-tab-id="id"/>
				<header class="crm-copilot__call-assessment_page-section-header">
					<div class="crm-copilot__call-assessment_page-section-header-title">
						{{ pageTitle }}
					</div>
					<div class="crm-copilot__call-assessment_page-section-header-description">
						{{ pageDescription }}
					</div>
				</header>
				<div class="crm-copilot__call-assessment_page-section-body">
					<AiDisabledInSettings v-if="!isEnabled" />
					<div class="crm-copilot__call-assessment_page-section-body-field-container">
						<div class="crm-copilot__call-assessment_page-section-body-field-title --low-icon">
							{{ this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_LOW_BORDER_TITLE') }}
						</div>
						<div class="crm-copilot__call-assessment_page-section-body-field-content">
							<div
								class="crm-copilot__call-assessment_page-section-body-field-subtitle"
								v-html="this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_LOW_BORDER_DESCRIPTION')"
							>
							</div>
							<Pill
								additionalClass="--low"
								:value="lowBorder"
								@change="setLowBorder"
							/>
							<div class="crm-copilot__call-assessment_page-section-body-field-additional">
								{{ this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_LOW_BORDER_ADDITIONAL') }}
							</div>
						</div>
					</div>
					
					<div class="crm-copilot__call-assessment_page-section-body-field-container">
						<div class="crm-copilot__call-assessment_page-section-body-field-title --high-icon">
							{{ this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_HIGH_BORDER_TITLE') }}
						</div>
						<div class="crm-copilot__call-assessment_page-section-body-field-content">
							<div 
								class="crm-copilot__call-assessment_page-section-body-field-subtitle"
								v-html="this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_HIGH_BORDER_DESCRIPTION')"
							>
							</div>
							<Pill
								additionalClass="--high"
								:value="highBorder"
								@change="setHighBorder"
							/>
							<div class="crm-copilot__call-assessment_page-section-body-field-additional">
								{{ this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_CONTROL_HIGH_BORDER_ADDITIONAL') }}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	`
	};

	const Button$1 = {
	  props: {
	    action: {
	      type: String,
	      required: true
	    },
	    index: {
	      type: Number,
	      required: true
	    }
	  },
	  computed: {
	    classList() {
	      return {
	        'crm-copilot__call-assessment-availability-selector-button': true,
	        '--add': this.action === 'add',
	        '--remove': this.action !== 'add'
	      };
	    }
	  },
	  methods: {
	    onClick() {
	      const {
	        action,
	        index
	      } = this;
	      this.$emit('button-click', {
	        index,
	        action
	      });
	    }
	  },
	  template: `
		<div :class="classList" @click="onClick"></div>
	`
	};

	const Chevron = {
	  props: {
	    isExpanded: {
	      type: Boolean,
	      default: false
	    }
	  },
	  computed: {
	    classList() {
	      return {
	        'crm-copilot__call-assessment-availability-selector-chevron': true,
	        '--expanded': this.isExpanded
	      };
	    }
	  },
	  template: `
		<div :class="classList"></div>
	`
	};

	const PeriodRow = {
	  components: {
	    Chevron
	  },
	  props: {
	    startPoint: {
	      type: String,
	      required: true
	    },
	    endPoint: {
	      type: String,
	      required: true
	    },
	    modelValue: {
	      type: Object,
	      default: {}
	    }
	  },
	  emits: ['update:modelValue'],
	  data() {
	    return {
	      currentStartPoint: this.startPoint,
	      currentEndPoint: this.endPoint,
	      isExpanded: false
	    };
	  },
	  created() {
	    this.datePicker = new ui_datePicker.DatePicker({
	      rangeStartInput: this.$refs.start,
	      rangeEndInput: this.$refs.end,
	      selectionMode: 'range',
	      defaultTime: '08:00',
	      dateFormat: crm_timeline_tools.DatetimeConverter.getSiteDateTimeFormat(true),
	      timeFormat: crm_timeline_tools.DatetimeConverter.getSiteShortTimeFormat(),
	      selectedDates: [this.currentStartPoint, this.currentEndPoint],
	      enableTime: true,
	      events: {
	        onSelectChange: event => {
	          const dates = event.getTarget().getSelectedDates().map(date => event.getTarget().formatDate(date, crm_timeline_tools.DatetimeConverter.getSiteDateTimeFormat(true)));
	          if (dates[0]) {
	            this.currentStartPoint = dates[0];
	          }
	          if (dates[1]) {
	            this.currentEndPoint = dates[1];
	          }
	          this.$emit('update:modelValue', {
	            startPoint: this.currentStartPoint,
	            endPoint: this.currentEndPoint
	          });
	        },
	        onShow: () => {
	          this.isExpanded = true;
	        },
	        onHide: () => {
	          this.isExpanded = false;
	        }
	      }
	    });
	  },
	  mounted() {
	    this.datePicker.setTargetNode(this.$refs.wrapper);
	    this.datePicker.selectRange(this.currentStartPoint, this.currentEndPoint);
	  },
	  beforeUnmount() {
	    this.datePicker.destroy();
	  },
	  methods: {
	    showPicker(event) {
	      event.preventDefault();
	      event.stopPropagation();
	      this.datePicker.show();
	    }
	  },
	  template: `
		<div
			class="crm-copilot__call-assessment-availability-selector-row"
			ref="wrapper"
		>
			<div class="crm-copilot__call-assessment-availability-selector-row-wrapper">
				<input
					type="text"
					ref="start"
					class="crm-copilot__call-assessment-availability-selector-input"
					autocomplete="off"
					readonly="readonly"
					@click="showPicker"
					:value="currentStartPoint"
				>
				<Chevron 
					:isExpanded="isExpanded"
					@click="showPicker"
				/>
			</div>
			<div class="crm-copilot__call-assessment-availability-selector-delimiter"></div>
			<div class="crm-copilot__call-assessment-availability-selector-row-wrapper">
				<input
					type="text"
					ref="end"
					class="crm-copilot__call-assessment-availability-selector-input"
					autocomplete="off"
					readonly="readonly"
					@click="showPicker"
					:value="currentEndPoint"
				>
				<Chevron 
					:isExpanded="isExpanded"
					@click="showPicker"
				/>
			</div>
		</div>
	`
	};

	const weekdayType = Object.freeze({
	  monday: 'monday',
	  tuesday: 'tuesday',
	  wednesday: 'wednesday',
	  thursday: 'thursday',
	  friday: 'friday',
	  saturday: 'saturday',
	  sunday: 'sunday',
	  working: 'working',
	  weekends: 'weekends'
	});

	var _errorMessage$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("errorMessage");
	class TimePeriod {
	  constructor() {
	    Object.defineProperty(this, _errorMessage$2, {
	      writable: true,
	      value: null
	    });
	  }
	  validate(startTime, endTime) {
	    const parseTime = dateTimeStr => {
	      const [, time] = dateTimeStr.split(' ');
	      const [hours, minutes] = time.split(':').map(Number);
	      return new Date(9999, 11, 31, hours, minutes);
	    };
	    const start = parseTime(startTime);
	    const end = parseTime(endTime);
	    if (Number.isNaN(start) || Number.isNaN(end)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _errorMessage$2)[_errorMessage$2] = main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_VALIDATION_TIME_ERROR_1');
	      return false;
	    }
	    if (start.getTime() >= end.getTime()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _errorMessage$2)[_errorMessage$2] = main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_VALIDATION_TIME_ERROR_2');
	      return false;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _errorMessage$2)[_errorMessage$2] = null;
	    return true;
	  }
	  getError() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _errorMessage$2)[_errorMessage$2];
	  }
	}

	const CustomRow = {
	  components: {
	    Chevron
	  },
	  props: {
	    weekdayType: {
	      type: String,
	      required: true
	    },
	    startPoint: {
	      type: String,
	      required: true
	    },
	    endPoint: {
	      type: String,
	      required: true
	    },
	    modelValue: {
	      type: Object,
	      default: {}
	    }
	  },
	  emits: ['update:modelValue'],
	  data() {
	    var _this$weekdayType;
	    return {
	      currentWeekdayType: (_this$weekdayType = this.weekdayType) != null ? _this$weekdayType : weekdayType.working,
	      currentStartPoint: this.startPoint,
	      currentEndPoint: this.endPoint,
	      isExpandedStartPoint: false,
	      isExpandedEndPoint: false,
	      timePeriodValidator: new TimePeriod()
	    };
	  },
	  created() {
	    this.datePicker = new ui_datePicker.DatePicker({
	      type: 'time',
	      dateFormat: crm_timeline_tools.DatetimeConverter.getSiteDateTimeFormat(true),
	      timeFormat: crm_timeline_tools.DatetimeConverter.getSiteShortTimeFormat(),
	      minuteStep: 5,
	      events: {
	        onSelectChange: event => {
	          const wrapper = event.getTarget().getInputField().parentNode;
	          if (!main_core.Type.isDomNode(wrapper)) {
	            return;
	          }
	          const dates = event.getTarget().getSelectedDates().map(date => event.getTarget().formatDate(date, crm_timeline_tools.DatetimeConverter.getSiteDateTimeFormat(true)));
	          if (!dates[0]) {
	            return;
	          }
	          if ((wrapper == null ? void 0 : wrapper.getAttribute('id')) === 'start-point-selector') {
	            this.currentStartPoint = dates[0];
	          } else if ((wrapper == null ? void 0 : wrapper.getAttribute('id')) === 'end-point-selector') {
	            this.currentEndPoint = dates[0];
	          }
	          const isValidRow = this.timePeriodValidator.validate(this.currentStartPoint, this.currentEndPoint);
	          if (isValidRow) {
	            main_core.Dom.removeClass(this.$refs.start, '--invalid');
	            main_core.Dom.removeClass(this.$refs.end, '--invalid');
	          } else {
	            main_core.Dom.addClass(this.$refs.start, '--invalid');
	            main_core.Dom.addClass(this.$refs.end, '--invalid');
	          }
	          this.$emit('update:modelValue', {
	            weekdayType: this.currentWeekdayType,
	            startPoint: this.currentStartPoint,
	            endPoint: this.currentEndPoint
	          });
	        },
	        onHide: () => {
	          this.isExpandedStartPoint = false;
	          this.isExpandedEndPoint = false;
	        }
	      }
	    });
	  },
	  beforeUnmount() {
	    this.datePicker.destroy();
	  },
	  computed: {
	    weekdayTypeList() {
	      return [{
	        value: weekdayType.monday,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PERIOD_SELECTOR_PERIOD_MONDAY')
	      }, {
	        value: weekdayType.tuesday,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PERIOD_SELECTOR_PERIOD_TUESDAY')
	      }, {
	        value: weekdayType.wednesday,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PERIOD_SELECTOR_PERIOD_WEDNESDAY')
	      }, {
	        value: weekdayType.thursday,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PERIOD_SELECTOR_PERIOD_THURSDAY')
	      }, {
	        value: weekdayType.friday,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PERIOD_SELECTOR_PERIOD_FRIDAY')
	      }, {
	        value: weekdayType.saturday,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PERIOD_SELECTOR_PERIOD_SATURDAY')
	      }, {
	        value: weekdayType.sunday,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PERIOD_SELECTOR_PERIOD_SUNDAY')
	      }, {
	        value: weekdayType.working,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PERIOD_SELECTOR_PERIOD_WORKING_DAYS')
	      }, {
	        value: weekdayType.weekends,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PERIOD_SELECTOR_PERIOD_WEEKENDS')
	      }];
	    },
	    startTimeString() {
	      return this.currentStartPoint.split(' ')[1];
	    },
	    endTimeString() {
	      return this.currentEndPoint.split(' ')[1];
	    }
	  },
	  watch: {
	    currentWeekdayType(weekdayType$$1) {
	      this.currentWeekdayType = weekdayType$$1;
	      this.$emit('update:modelValue', {
	        weekdayType: this.currentWeekdayType,
	        startPoint: this.currentStartPoint,
	        endPoint: this.currentEndPoint
	      });
	    }
	  },
	  methods: {
	    showPicker(event) {
	      event.preventDefault();
	      event.stopPropagation();
	      const wrapper = event.target.parentNode;
	      if (!main_core.Type.isDomNode(wrapper)) {
	        return;
	      }
	      this.datePicker.setTargetNode(wrapper);
	      if ((wrapper == null ? void 0 : wrapper.getAttribute('id')) === 'start-point-selector') {
	        this.datePicker.setInputField(this.$refs.start);
	        this.datePicker.selectDate(this.currentStartPoint);
	        this.isExpandedStartPoint = true;
	      } else if ((wrapper == null ? void 0 : wrapper.getAttribute('id')) === 'end-point-selector') {
	        this.datePicker.setInputField(this.$refs.end);
	        this.datePicker.selectDate(this.currentEndPoint);
	        this.isExpandedEndPoint = true;
	      }
	      this.datePicker.show();
	    }
	  },
	  template: `
		<div class="crm-copilot__call-assessment-availability-selector-row">
			<div 
				class="crm-copilot__call-assessment-availability-selector-row-wrapper"
				style="padding-right: 15px;"
			>
				<div class="ui-ctl ui-ctl-w100 ui-ctl-after-icon ui-ctl-dropdown">
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
					<select
						class="ui-ctl-element crm-copilot__call-assessment-availability-selector-input  --weekday-type"
						v-model="currentWeekdayType"
						:value="currentWeekdayType"
					>
						<option
							v-for="typeItem in weekdayTypeList"
							:value="typeItem.value"
							:disabled="currentWeekdayType === typeItem.value"
						>
							{{typeItem.title}}
						</option>
					</select>
				</div>
			</div>
			<div 
				id="start-point-selector"
				class="crm-copilot__call-assessment-availability-selector-row-wrapper"
			>
				<input 
					type="text"
					ref="start"
					class="crm-copilot__call-assessment-availability-selector-input --time"
					autocomplete="off"
					readonly="readonly"
					@click="showPicker"
					:value="startTimeString"
				>
				<Chevron
					:isExpanded="isExpandedStartPoint"
					@click="showPicker"
				/>
			</div>
			<div class="crm-copilot__call-assessment-availability-selector-delimiter"></div>
			<div
				id="end-point-selector"
				class="crm-copilot__call-assessment-availability-selector-row-wrapper"
			>
				<input
					type="text"
					ref="end"
					class="crm-copilot__call-assessment-availability-selector-input --time"
					autocomplete="off"
					readonly="readonly"
					@click="showPicker"
					:value="endTimeString"
				>
				<Chevron
					:isExpanded="isExpandedEndPoint"
					@click="showPicker"
				/>
			</div>
		</div>
	`
	};

	const availabilityType = Object.freeze({
	  always_active: 'always_active',
	  inactive: 'inactive',
	  period: 'period',
	  custom: 'custom'
	});

	const TypeSelector = {
	  components: {
	    Button: Button$1,
	    PeriodRow,
	    CustomRow
	  },
	  props: {
	    availabilityType: {
	      type: String,
	      required: true
	    },
	    readOnly: {
	      type: Boolean
	    }
	  },
	  data() {
	    return {
	      currentAvailabilityType: this.availabilityType,
	      availabilityTypeData: {}
	    };
	  },
	  beforeCreate() {
	    this.availabilityTypeList = availabilityType;
	    this.dateTimeFormat = crm_timeline_tools.DatetimeConverter.getSiteDateTimeFormat(true);
	  },
	  computed: {
	    typeList() {
	      return [{
	        value: availabilityType.always_active,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_AVAILABILITY_SELECTOR_ALWAYS_ACTIVE')
	      }, {
	        value: availabilityType.period,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_AVAILABILITY_SELECTOR_PERIOD')
	      }, {
	        value: availabilityType.custom,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_AVAILABILITY_SELECTOR_CUSTOM')
	      }, {
	        value: availabilityType.inactive,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_AVAILABILITY_SELECTOR_INACTIVE')
	      }];
	    },
	    hasRows() {
	      return main_core.Type.isArrayFilled(this.availabilityTypeData[this.currentAvailabilityType]);
	    },
	    rowsForCurrentType() {
	      var _this$availabilityTyp;
	      return (_this$availabilityTyp = this.availabilityTypeData[this.currentAvailabilityType]) != null ? _this$availabilityTyp : [];
	    },
	    isExtendedAvailabilityType() {
	      return this.currentAvailabilityType !== availabilityType.always_active && this.currentAvailabilityType !== availabilityType.inactive;
	    }
	  },
	  watch: {
	    currentAvailabilityType(availabilityType$$1) {
	      this.$emit('type-change', {
	        availabilityType: availabilityType$$1
	      });
	    }
	  },
	  methods: {
	    onActionButtonClick({
	      action,
	      index
	    }) {
	      if (this.currentAvailabilityType === availabilityType.always_active || this.currentAvailabilityType === availabilityType.inactive) {
	        return;
	      }
	      if (action === 'add') {
	        const item = this.currentAvailabilityType === availabilityType.period ? this.getDefaultDatePeriodItem() : this.getDefaultCustomItem();
	        this.rowsForCurrentType.push(item);
	      } else
	        // remove
	        {
	          this.rowsForCurrentType.splice(index, 1);
	        }
	    },
	    onUpdateCustomRow(row) {
	      this.$emit('data-change', {
	        availabilityType: this.currentAvailabilityType,
	        availabilityTypeData: this.getAvailabilityData(),
	        updatedRow: row
	      });
	    },
	    getDefaultDatePeriodItem() {
	      const startPointDate = this.getRoundedToNearestMinuteDate(new Date(), 15);
	      const endPointDate = this.getRoundedToNearestMinuteDate(new Date(), 15);
	      endPointDate.setDate(endPointDate.getDate() + 7); // 1 week by default

	      return {
	        startPoint: main_date.DateTimeFormat.format(this.dateTimeFormat, startPointDate),
	        endPoint: main_date.DateTimeFormat.format(this.dateTimeFormat, endPointDate)
	      };
	    },
	    getDefaultCustomItem() {
	      const startPointDate = Math.round(new Date().setHours(8, 0, 0, 0) / 1000);
	      const endPointDate = Math.round(new Date().setHours(18, 0, 0, 0) / 1000);
	      return {
	        weekdayType: weekdayType.working,
	        startPoint: main_date.DateTimeFormat.format(this.dateTimeFormat, startPointDate),
	        endPoint: main_date.DateTimeFormat.format(this.dateTimeFormat, endPointDate)
	      };
	    },
	    getRoundedToNearestMinuteDate(date, minutes) {
	      const currentTime = date.getTime();
	      const ms = minutes * 60 * 1000;
	      const roundedTime = Math.round(currentTime / ms) * ms;
	      return new Date(roundedTime);
	    },
	    getAvailabilityData() {
	      var _this$availabilityTyp2;
	      return (_this$availabilityTyp2 = this.availabilityTypeData[this.currentAvailabilityType]) != null ? _this$availabilityTyp2 : [];
	    },
	    setAvailabilityData(availabilityData) {
	      if (main_core.Type.isArrayFilled(availabilityData)) {
	        this.availabilityTypeData[this.currentAvailabilityType] = availabilityData;
	        if (this.currentAvailabilityType === this.availabilityTypeList.period) {
	          this.availabilityTypeData[this.availabilityTypeList.custom] = [this.getDefaultCustomItem()];
	        } else if (this.currentAvailabilityType === this.availabilityTypeList.custom) {
	          this.availabilityTypeData[this.availabilityTypeList.period] = [this.getDefaultDatePeriodItem()];
	        }
	        return;
	      }
	      this.availabilityTypeData = {
	        [this.availabilityTypeList.period]: [this.getDefaultDatePeriodItem()],
	        [this.availabilityTypeList.custom]: [this.getDefaultCustomItem()]
	      };
	    }
	  },
	  template: `
		<div class="crm-copilot__call-assessment-availability-selector">
			<div class="ui-ctl ui-ctl-w100 ui-ctl-after-icon ui-ctl-dropdown">
				<div class="ui-ctl-after ui-ctl-icon-angle"></div>
				<select
					class="ui-ctl-element"
					v-model="currentAvailabilityType"
					:value="currentAvailabilityType"
				>
					<option
						v-for="typeItem in typeList"
						:value="typeItem.value"
						:disabled="readOnly && currentAvailabilityType !== typeItem.value"
					>
						{{typeItem.title}}
					</option>
				</select>
			</div>
			<div 
				class="crm-copilot__call-assessment-availability-rows"
				v-if="isExtendedAvailabilityType"
			>
				<div
					class="crm-copilot__call-assessment-availability-row"
					v-if="currentAvailabilityType === availabilityTypeList.period"
					v-for="(row, index) in rowsForCurrentType"
				>
					<PeriodRow
						:start-point="row.startPoint"
						:end-point="row.endPoint"
						v-model="availabilityTypeData[availabilityTypeList.period][index]"
					/>
					<Button
						:action="(index < rowsForCurrentType.length - 1 ? 'remove' : 'add')"
						:index="index"
						@button-click="onActionButtonClick"
					/>
				</div>
				<div
					class="crm-copilot__call-assessment-availability-row"
					v-if="currentAvailabilityType === availabilityTypeList.custom"
					v-for="(row, index) in rowsForCurrentType"
				>
					<CustomRow
						:weekday-type="row.weekdayType"
						:start-point="row.startPoint"
						:end-point="row.endPoint"
						v-model="availabilityTypeData[availabilityTypeList.custom][index]"
						v-on:update:modelValue="onUpdateCustomRow"
					/>
					<Button
						:action="(index < rowsForCurrentType.length - 1 ? 'remove' : 'add')"
						:index="index"
						@button-click="onActionButtonClick"
					/>
				</div>
			</div>
		</div>
	`
	};

	const SettingsPage = {
	  extends: BasePage,
	  components: {
	    TypeSelector
	  },
	  data() {
	    var _this$settings$baas, _this$data$callTypeId, _this$data$availabili, _this$data$availabili2;
	    let currentAutoCheckTypeId = 0;
	    if ((_this$settings$baas = this.settings.baas) != null && _this$settings$baas.hasPackage) {
	      var _this$data$autoCheckT;
	      currentAutoCheckTypeId = (_this$data$autoCheckT = this.data.autoCheckTypeId) != null ? _this$data$autoCheckT : 1;
	    }
	    return {
	      id: 'settings',
	      callTypeId: (_this$data$callTypeId = this.data.callTypeId) != null ? _this$data$callTypeId : 1,
	      autoCheckTypeId: currentAutoCheckTypeId,
	      availabilityType: (_this$data$availabili = this.data.availabilityType) != null ? _this$data$availabili : availabilityType.always_active,
	      availabilityData: (_this$data$availabili2 = this.data.availabilityData) != null ? _this$data$availabili2 : [],
	      timePeriodValidator: new TimePeriod(),
	      timePeriodError: null
	    };
	  },
	  methods: {
	    getData() {
	      var _this$$refs$typeSelec, _this$$refs$typeSelec2;
	      return {
	        callTypeId: this.callTypeId,
	        autoCheckTypeId: this.autoCheckTypeId,
	        availabilityType: this.availabilityType,
	        availabilityData: (_this$$refs$typeSelec = (_this$$refs$typeSelec2 = this.$refs.typeSelector) == null ? void 0 : _this$$refs$typeSelec2.getAvailabilityData()) != null ? _this$$refs$typeSelec : []
	      };
	    },
	    onAutoCheckTypeIdChange(event) {
	      if (this.isBaasHasPackage) {
	        return;
	      }
	      const {
	        value
	      } = event.target;
	      if (value !== 0) {
	        if (this.packageEmptySliderCode) {
	          ui_infoHelper.InfoHelper.show(this.packageEmptySliderCode);
	        }
	        void this.$nextTick(() => {
	          this.autoCheckTypeId = 0;
	        });
	      }
	    },
	    onAvailabilityTypeChange(data) {
	      this.availabilityType = data.availabilityType;
	      this.timePeriodError = null;
	    },
	    onAvailabilityDataChange(data) {
	      // @todo: setup "availabilityData" data here in the future if needed
	      // const { availabilityType, availabilityTypeData, updatedRow } = data;

	      this.validate();
	      this.onValidationFailed();
	    },
	    validate() {
	      if (this.availabilityType === availabilityType.custom) {
	        var _this$$refs$typeSelec3, _this$$refs$typeSelec4;
	        const data = (_this$$refs$typeSelec3 = (_this$$refs$typeSelec4 = this.$refs.typeSelector) == null ? void 0 : _this$$refs$typeSelec4.getAvailabilityData()) != null ? _this$$refs$typeSelec3 : [];
	        const invalidItems = data.filter(item => {
	          return !this.timePeriodValidator.validate(item.startPoint, item.endPoint);
	        });
	        return invalidItems.length === 0;
	      }
	      return true;
	    },
	    onValidationFailed() {
	      this.timePeriodError = this.timePeriodValidator.getError();
	    }
	  },
	  mounted() {
	    var _this$$refs$typeSelec5;
	    (_this$$refs$typeSelec5 = this.$refs.typeSelector) == null ? void 0 : _this$$refs$typeSelec5.setAvailabilityData(this.availabilityData);
	  },
	  computed: {
	    pageTitle() {
	      return this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_TITLE');
	    },
	    pageDescription() {
	      return this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_DESCRIPTION');
	    },
	    pageSectionSettingsCallType() {
	      return this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CALL_TYPE');
	    },
	    pageSectionSettingsCallTypeDescription() {
	      return this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CALL_TYPE_DESCRIPTION');
	    },
	    pageSectionSettingsCheck() {
	      return this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK');
	    },
	    pageSectionSettingsCheckDescription() {
	      return this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK_DESCRIPTION');
	    },
	    callTypes() {
	      return [{
	        id: 1,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CALL_TYPE_ALL')
	      }, {
	        id: 2,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CALL_TYPE_INCOMING')
	      }, {
	        id: 3,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CALL_TYPE_OUTGOING')
	      }];
	    },
	    checkTypes() {
	      return [{
	        value: 1,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK_FIRST_INCOMING')
	      }, {
	        value: 2,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK_ALL_INCOMING')
	      }, {
	        value: 3,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK_ALL_OUTGOING')
	      }, {
	        value: 4,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK_ALL')
	      }, {
	        value: 0,
	        title: this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_CHECK_DISABLED')
	      }];
	    },
	    isBaasAvailable() {
	      var _this$settings$baas$i, _this$settings$baas2;
	      return (_this$settings$baas$i = (_this$settings$baas2 = this.settings.baas) == null ? void 0 : _this$settings$baas2.isAvailable) != null ? _this$settings$baas$i : false;
	    },
	    isBaasHasPackage() {
	      var _this$settings$baas$h, _this$settings$baas3;
	      return (_this$settings$baas$h = (_this$settings$baas3 = this.settings.baas) == null ? void 0 : _this$settings$baas3.hasPackage) != null ? _this$settings$baas$h : false;
	    },
	    packageEmptySliderCode() {
	      var _this$settings$baas$a, _this$settings$baas4;
	      return (_this$settings$baas$a = (_this$settings$baas4 = this.settings.baas) == null ? void 0 : _this$settings$baas4.aiPackagesEmptySliderCode) != null ? _this$settings$baas$a : null;
	    },
	    pageSectionSettingsAvailability() {
	      return this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_AVAILABILITY');
	    },
	    pageSectionSettingsAvailabilityDescription() {
	      return this.$Bitrix.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_SETTINGS_AVAILABILITY_DESCRIPTION');
	    },
	    isCallAssessmentAvailabilityEnabled() {
	      return main_core.Extension.getSettings('crm.copilot.call-assessment').get('callAssessmentAvailabilityEnabled');
	    }
	  },
	  template: `
		<div v-show="isActive">
			<div class="crm-copilot__call-assessment_page-section">
				<Breadcrumbs :active-tab-id="id" />
				<header class="crm-copilot__call-assessment_page-section-header">
					<div class="crm-copilot__call-assessment_page-section-header-title">
						{{ pageTitle }}
					</div>
					<div class="crm-copilot__call-assessment_page-section-header-description">
						{{ pageDescription }}
					</div>
				</header>

				<div class="crm-copilot__call-assessment_page-section-body">
					<AiDisabledInSettings v-if="!isEnabled" />
					<div :class="this.getBodyFieldClassList(['ui-ctl', 'ui-ctl-after-icon', 'ui-ctl-dropdown', 'ui-ctl-w100'])">
						<label>{{ pageSectionSettingsCallType }}</label>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-after-icon ui-ctl-dropdown">
							<div class="ui-ctl-after ui-ctl-icon-angle"></div>
							<select class="ui-ctl-element" v-model="callTypeId">
								<option 
									v-for="callType in callTypes"
									:value="callType.id"
									:disabled="readOnly && callTypeId !== callType.id"
								>
									{{callType.title}}
								</option>
							</select>
						</div>
						<div class="crm-copilot__call-assessment_page-section-body-field-description">
							{{ pageSectionSettingsCallTypeDescription }}
						</div>
					</div>
				</div>

				<div 
					v-if="isBaasAvailable"
					class="crm-copilot__call-assessment_page-section-body"
				>
					<div :class="this.getBodyFieldClassList(['ui-ctl', 'ui-ctl-after-icon', 'ui-ctl-dropdown', 'ui-ctl-w100'])">
						<label>{{ pageSectionSettingsCheck }}</label>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-after-icon ui-ctl-dropdown">
							<div class="ui-ctl-after ui-ctl-icon-angle"></div>
							<select 
								class="ui-ctl-element"
								v-model="autoCheckTypeId"
								@change="onAutoCheckTypeIdChange"
							>
								<option 
									v-for="checkType in checkTypes"
									:value="checkType.value"
									:disabled="readOnly && autoCheckTypeId !== checkType.value"
								>
									{{checkType.title}}
								</option>
							</select>
						</div>
						<div class="crm-copilot__call-assessment_page-section-body-field-description">
							{{ pageSectionSettingsCheckDescription }}
						</div>
					</div>
				</div>

				<div
					v-if="isCallAssessmentAvailabilityEnabled"
					class="crm-copilot__call-assessment_page-section-body"
				>
					<div class="crm-copilot__call-assessment_page-section-body-field">
						<label>{{ pageSectionSettingsAvailability }}</label>
						<TypeSelector
							ref="typeSelector"
							:availabilityType="availabilityType"
							:readOnly="readOnly"
							@typeChange="onAvailabilityTypeChange"
							@dataChange="onAvailabilityDataChange"
						/>
						<div class="crm-copilot__call-assessment_page-section-body-field-description">
							{{ pageSectionSettingsAvailabilityDescription }}
						</div>
						<div v-if="timePeriodError" class="crm-copilot__call-assessment_page-section-body-field-error">
							{{ timePeriodError }}
						</div>
					</div>
				</div>
			</div>
		</div>
	`
	};

	/**
	 * @see BasePage
	 */

	const CallAssessment = {
	  components: {
	    AboutPage,
	    ClientPage,
	    SettingsPage,
	    ControlPage,
	    Navigation
	  },
	  props: {
	    settings: {
	      type: Object,
	      default: {}
	    },
	    params: {
	      type: Object,
	      default: {}
	    },
	    textEditor: ui_textEditor.TextEditor
	  },
	  data() {
	    var _data$title;
	    const {
	      data
	    } = this.params;
	    let id = null;
	    if (this.settings.isCopy) {
	      id = null;
	    } else {
	      var _data$id;
	      id = (_data$id = data == null ? void 0 : data.id) != null ? _data$id : null;
	    }
	    return {
	      id,
	      pagesData: this.initPagesData(data),
	      activePageId: 'about',
	      title: (_data$title = data.title) != null ? _data$title : null,
	      canShowNextPage: false
	    };
	  },
	  watch: {
	    activePageId(pageId) {
	      this.onPageDataChange({
	        id: pageId,
	        data: this.getPageData(pageId)
	      });
	    }
	  },
	  methods: {
	    initPagesData(data) {
	      var _data$availabilityTyp, _data$availabilityDat, _data$lowBorder, _data$highBorder;
	      return {
	        client: {
	          data: {
	            activeClientTypeIds: data == null ? void 0 : data.clientTypeIds
	          }
	        },
	        settings: {
	          data: {
	            callTypeId: data == null ? void 0 : data.callTypeId,
	            autoCheckTypeId: data == null ? void 0 : data.autoCheckTypeId,
	            availabilityType: (_data$availabilityTyp = data == null ? void 0 : data.availabilityType) != null ? _data$availabilityTyp : availabilityType.always_active,
	            availabilityData: (_data$availabilityDat = data == null ? void 0 : data.availabilityData) != null ? _data$availabilityDat : []
	          }
	        },
	        control: {
	          data: {
	            lowBorder: (_data$lowBorder = data == null ? void 0 : data.lowBorder) != null ? _data$lowBorder : 0,
	            highBorder: (_data$highBorder = data == null ? void 0 : data.highBorder) != null ? _data$highBorder : 100
	          }
	        }
	      };
	    },
	    onNavigationButtonClick({
	      data
	    }) {
	      const {
	        id
	      } = data;
	      if (id === 'cancel' || id === 'close') {
	        this.closeSlider();
	        return;
	      }
	      if (id === 'back') {
	        const currentIndex = this.pagesList.indexOf(this.activePageId);
	        if (currentIndex > 0) {
	          this.activePageId = this.pagesList[currentIndex - 1];
	        }
	        return;
	      }
	      if (!this.isActivePageValid()) {
	        this.onActivePageValidationFailed();
	        return;
	      }
	      if (id === 'continue') {
	        const currentIndex = this.pagesList.indexOf(this.activePageId);
	        if (currentIndex < this.pagesList.length - 1) {
	          this.activePageId = this.pagesList[currentIndex + 1];
	        }
	        return;
	      }
	      if (id === 'submit' || id === 'update') {
	        this.sendData();
	      }
	    },
	    sendData() {
	      let data = {
	        title: this.title
	      };
	      this.pagesList.forEach(pageId => {
	        var _this$$refs$pageId;
	        data = {
	          ...data,
	          ...((_this$$refs$pageId = this.$refs[pageId]) == null ? void 0 : _this$$refs$pageId.getData())
	        };
	      });
	      const dataParams = {
	        id: this.id,
	        data,
	        eventId: pull_queuemanager.QueueManager.registerRandomEventId('crm-copilot-call-assessment')
	      };
	      top.BX.Event.EventEmitter.emit('crm:copilot:callAssessment:beforeSave', dataParams);
	      main_core.ajax.runAction('crm.copilot.callassessment.save', {
	        data: dataParams
	      }).then(response => {
	        top.BX.Event.EventEmitter.emit('crm:copilot:callAssessment:save', {
	          ...dataParams,
	          status: response == null ? void 0 : response.status
	        });
	        if ((response == null ? void 0 : response.status) !== 'success') {
	          ui_notification.UI.Notification.Center.notify({
	            content: main_core.Text.encode(response.errors[0].message),
	            autoHideDelay: 6000
	          });
	          return;
	        }
	        this.onSaveCallback();
	        this.closeSlider();
	      }, response => {
	        var _response$errors$;
	        const messageCode = ((_response$errors$ = response.errors[0]) == null ? void 0 : _response$errors$.code) === 'AI_NOT_AVAILABLE' ? 'CRM_COPILOT_CALL_ASSESSMENT_PAGE_COPILOT_ERROR' : 'CRM_COPILOT_CALL_ASSESSMENT_PAGE_SAVE_ERROR';
	        ui_notification.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage(messageCode),
	          autoHideDelay: 6000
	        });
	      }).catch(response => {
	        ui_notification.UI.Notification.Center.notify({
	          content: main_core.Text.encode(response.errors[0].message),
	          autoHideDelay: 6000
	        });
	        throw response;
	      });
	    },
	    closeSlider() {
	      top.BX.SidePanel.Instance.getSliderByWindow(window).close();
	    },
	    onSaveCallback() {
	      var _this$params, _this$params$events;
	      if (main_core.Type.isFunction((_this$params = this.params) == null ? void 0 : (_this$params$events = _this$params.events) == null ? void 0 : _this$params$events.onSave)) {
	        this.params.events.onSave();
	      }
	    },
	    setActivePageId({
	      data
	    }) {
	      const newPageId = data.itemId;
	      const currentPageIndex = this.pagesList.indexOf(this.activePageId);
	      const newPageIndex = this.pagesList.indexOf(newPageId);
	      if (currentPageIndex === newPageIndex) {
	        return;
	      }
	      if (newPageIndex > currentPageIndex && !this.isActivePageValid()) {
	        this.onActivePageValidationFailed();
	        return;
	      }
	      this.activePageId = newPageId;
	    },
	    isPageActive(id) {
	      return this.activePageId === id;
	    },
	    onPageDataChange({
	      id: pageId,
	      data
	    }) {
	      this.pagesData[pageId] = data;
	      this.canShowNextPage = this.isActivePageReadyToMoveOn();
	    },
	    getPageData(pageId) {
	      if (main_core.Type.isObjectLike(this.pagesData[pageId])) {
	        return this.pagesData[pageId].data;
	      }
	      return {};
	    },
	    getPageSettings(pageId) {
	      if (pageId === 'settings') {
	        var _this$settings$baas;
	        return {
	          baas: (_this$settings$baas = this.settings.baas) != null ? _this$settings$baas : {}
	        };
	      }
	      return {};
	    },
	    setTitle(title) {
	      this.title = title;
	    },
	    isActivePageValid() {
	      return this.getActivePage().validate();
	    },
	    onActivePageValidationFailed() {
	      return this.getActivePage().onValidationFailed();
	    },
	    isActivePageReadyToMoveOn() {
	      return this.getActivePage().isReadyToMoveOn();
	    },
	    getActivePage() {
	      return this.$refs[this.activePageId];
	    }
	  },
	  computed: {
	    pagesList() {
	      return ['about', 'client', 'settings', 'control'];
	    },
	    isNew() {
	      var _this$params$data;
	      return ((_this$params$data = this.params.data) == null ? void 0 : _this$params$data.id) <= 0;
	    },
	    readOnly() {
	      return this.settings.readOnly;
	    },
	    isEnabled() {
	      return this.settings.isEnabled;
	    }
	  },
	  mounted() {
	    this.canShowNextPage = this.isActivePageReadyToMoveOn();
	    this.$Bitrix.eventEmitter.subscribe(BreadcrumbsEvents.itemClick, this.setActivePageId);
	    this.$Bitrix.eventEmitter.subscribe(ButtonEvents.click, this.onNavigationButtonClick);
	  },
	  beforeUnmount() {
	    this.$Bitrix.eventEmitter.unsubscribe(BreadcrumbsEvents.itemClick, this.setActivePageId);
	    this.$Bitrix.eventEmitter.unsubscribe(ButtonEvents.click, this.onNavigationButtonClick);
	  },
	  template: `
		<div class="crm-copilot__call-assessment_container">
			<div class="crm-copilot__call-assessment_page-wrapper">
				<AboutPage 
					:is-active="isPageActive('about')"
					:is-enabled="isEnabled"
					:data="getPageData('about')"
					:text-editor="textEditor"
					@onChange="onPageDataChange"
					ref="about"
				/>
				<ClientPage 
					:is-active="isPageActive('client')"
					:is-enabled="isEnabled"
					:data="getPageData('client')"
					:read-only="readOnly"
					@onChange="onPageDataChange"
					ref="client"
				/>
				<SettingsPage
					:is-active="isPageActive('settings')"
					:is-enabled="isEnabled"
					:data="getPageData('settings')"
					:settings="getPageSettings('settings')"
					:read-only="readOnly"
					@onChange="onPageDataChange"
					ref="settings"
				/>
				<ControlPage
					:is-active="isPageActive('control')"
					:is-enabled="isEnabled"
					:data="getPageData('control')"
					:read-only="readOnly"
					@onChange="onPageDataChange"
					ref="control"
				/>
			</div>
			<Navigation 
				:active-tab-id="activePageId"
				:is-enabled="canShowNextPage"
				:read-only="readOnly"
				:show-save-button="!isNew && !readOnly"
			/>
		</div>
	`
	};

	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _app = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("app");
	var _layoutComponent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layoutComponent");
	var _title = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("title");
	var _textEditor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("textEditor");
	var _isReadOnly = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isReadOnly");
	var _isEnabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isEnabled");
	var _initTitleInlineEditing = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initTitleInlineEditing");
	var _getTextEditor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTextEditor");
	class CallAssessment$1 {
	  constructor(containerId, _params = {}) {
	    var _params$config$readOn, _params$config, _params$config$isEnab, _params$config2, _params$config3, _params$config$isCopy, _params$config4, _params$config$baasSe, _params$config5;
	    Object.defineProperty(this, _getTextEditor, {
	      value: _getTextEditor2
	    });
	    Object.defineProperty(this, _initTitleInlineEditing, {
	      value: _initTitleInlineEditing2
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _app, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _layoutComponent, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _title, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _textEditor, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _isReadOnly, {
	      writable: true,
	      value: true
	    });
	    Object.defineProperty(this, _isEnabled, {
	      writable: true,
	      value: true
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = document.getElementById(containerId);
	    if (!main_core.Type.isDomNode(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container])) {
	      throw new Error('container not found');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _isReadOnly)[_isReadOnly] = (_params$config$readOn = (_params$config = _params.config) == null ? void 0 : _params$config.readOnly) != null ? _params$config$readOn : true;
	    babelHelpers.classPrivateFieldLooseBase(this, _isEnabled)[_isEnabled] = (_params$config$isEnab = (_params$config2 = _params.config) == null ? void 0 : _params$config2.isEnabled) != null ? _params$config$isEnab : true;
	    babelHelpers.classPrivateFieldLooseBase(this, _initTitleInlineEditing)[_initTitleInlineEditing](_params);
	    babelHelpers.classPrivateFieldLooseBase(this, _app)[_app] = ui_vue3.BitrixVue.createApp(CallAssessment, {
	      textEditor: babelHelpers.classPrivateFieldLooseBase(this, _getTextEditor)[_getTextEditor](_params.data, (_params$config3 = _params.config) != null ? _params$config3 : {}),
	      settings: {
	        readOnly: babelHelpers.classPrivateFieldLooseBase(this, _isReadOnly)[_isReadOnly],
	        isEnabled: babelHelpers.classPrivateFieldLooseBase(this, _isEnabled)[_isEnabled],
	        isCopy: (_params$config$isCopy = (_params$config4 = _params.config) == null ? void 0 : _params$config4.isCopy) != null ? _params$config$isCopy : false,
	        baas: (_params$config$baasSe = (_params$config5 = _params.config) == null ? void 0 : _params$config5.baasSettings) != null ? _params$config$baasSe : false
	      },
	      params: {
	        data: _params.data,
	        events: _params.events
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _layoutComponent)[_layoutComponent] = babelHelpers.classPrivateFieldLooseBase(this, _app)[_app].mount(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]);
	  }
	}
	function _initTitleInlineEditing2(params) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isReadOnly)[_isReadOnly]) {
	    return;
	  }
	  const ToolbarManager = main_core.Reflection.getClass('BX.UI.ToolbarManager');
	  const toolbar = ToolbarManager == null ? void 0 : ToolbarManager.getDefaultToolbar();
	  if (toolbar) {
	    const ToolbarEvents = main_core.Reflection.getClass('BX.UI.ToolbarEvents');
	    toolbar.subscribe(ToolbarEvents.finishEditing, ({
	      data
	    }) => {
	      const {
	        updatedTitle
	      } = data;
	      babelHelpers.classPrivateFieldLooseBase(this, _title)[_title] = updatedTitle;
	      babelHelpers.classPrivateFieldLooseBase(this, _layoutComponent)[_layoutComponent].setTitle(babelHelpers.classPrivateFieldLooseBase(this, _title)[_title]);
	    });
	  }
	  const {
	    data
	  } = params;
	  if (main_core.Type.isString(data == null ? void 0 : data.title)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _title)[_title] = data.title;
	  }
	}
	function _getTextEditor2({
	  prompt: content
	}, {
	  copilotSettings
	}) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _textEditor)[_textEditor] !== null) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _textEditor)[_textEditor];
	  }
	  const toolbar = babelHelpers.classPrivateFieldLooseBase(this, _isReadOnly)[_isReadOnly] ? [] : ['bold', 'italic', 'underline', 'strikethrough', '|', 'numbered-list', 'bulleted-list', 'copilot'];
	  babelHelpers.classPrivateFieldLooseBase(this, _textEditor)[_textEditor] = new ui_textEditor.BasicEditor({
	    editable: !babelHelpers.classPrivateFieldLooseBase(this, _isReadOnly)[_isReadOnly],
	    removePlugins: ['BlockToolbar'],
	    minHeight: 250,
	    maxHeight: 400,
	    content,
	    placeholder: main_core.Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PLACEHOLDER'),
	    paragraphPlaceholder: main_core.Loc.getMessage(main_core.Type.isPlainObject(copilotSettings) ? 'CRM_COPILOT_CALL_ASSESSMENT_PLACEHOLDER_WITH_COPILOT' : null),
	    toolbar,
	    floatingToolbar: [],
	    collapsingMode: false,
	    copilot: {
	      copilotOptions: main_core.Type.isPlainObject(copilotSettings) ? copilotSettings : null,
	      triggerBySpace: true
	    }
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _textEditor)[_textEditor];
	}

	exports.CallAssessment = CallAssessment$1;

}((this.BX.Crm.Copilot = this.BX.Crm.Copilot || {}),BX.Vue3,BX.Pull,BX.UI,BX.UI.BBCode,BX.UI.TextEditor,BX,BX,BX.UI.EntitySelector,BX.UI,BX,BX.Main,BX.Crm.Timeline,BX.Event,BX.UI.DatePicker,BX));
//# sourceMappingURL=call-assessment.bundle.js.map
