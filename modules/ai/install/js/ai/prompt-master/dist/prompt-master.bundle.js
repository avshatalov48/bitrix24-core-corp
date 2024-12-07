/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_vue3,main_loader,ui_vue3_components_hint,ui_vue3_directives_hint,ui_alerts,ui_iconSet_main,ui_iconSet_crm,main_popup,main_core,main_core_events,ui_entitySelector,ui_hint,ui_buttons,ui_iconSet_api_vue,ui_iconSet_api_core,ui_analytics) {
	'use strict';

	const promptTypes = [{
	  id: 'DEFAULT',
	  title: main_core.Loc.getMessage('PROMPT_MASTER_TYPE_FIRST_TITLE'),
	  description: main_core.Loc.getMessage('PROMPT_MASTER_TYPE_FIRST_DESCRIPTION'),
	  example: main_core.Loc.getMessage('PROMPT_MASTER_TYPE_FIRST_EXAMPLE'),
	  active: false,
	  icon: 'stars'
	}, {
	  id: 'SIMPLE_TEMPLATE',
	  title: main_core.Loc.getMessage('PROMPT_MASTER_TYPE_SECOND_TITLE'),
	  description: main_core.Loc.getMessage('PROMPT_MASTER_TYPE_SECOND_DESCRIPTION'),
	  example: main_core.Loc.getMessage('PROMPT_MASTER_TYPE_SECOND_EXAMPLE', {
	    '#accent#': '<strong>',
	    '#/accent#': '</strong>'
	  }),
	  active: false,
	  icon: 'stars-question'
	}];
	const PromptMasterPromptTypes = {
	  components: {
	    Hint: ui_vue3_components_hint.Hint
	  },
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  props: {
	    activePromptType: {
	      type: String,
	      required: false,
	      default: ''
	    }
	  },
	  computed: {
	    promptTypes() {
	      return promptTypes.map(type => {
	        const isActive = type.id === this.activePromptType;
	        return {
	          ...type,
	          active: isActive
	        };
	      });
	    }
	  },
	  methods: {
	    getPromptTypeClass(isActive) {
	      return {
	        'ai__prompt-master_prompt-type': true,
	        '--active': isActive
	      };
	    },
	    getPromptTypeIconClassname(iconName) {
	      return ['ai__prompt-master_prompt-type_icon', `--icon-${iconName}`];
	    },
	    selectPromptType(type) {
	      this.$emit('select', type);
	    }
	  },
	  template: `
		<div class="ai__prompt-master_prompt-types-step">
			<ul class="ai__prompt-master_prompt-types">
				<li
					v-for="promptType in promptTypes"
					class="ai__prompt-master_prompt-types_type"
					@click="selectPromptType(promptType.id)"
				>
					<div :class="getPromptTypeClass(promptType.active)">
						<div :class="getPromptTypeIconClassname(promptType.icon)"></div>
						<div class="ai__prompt-master_prompt-type_title">
							{{ promptType.title }}
						</div>
						<p class="ai__prompt-master_prompt-type_description">
							{{ promptType.description }}
						</p>
						<i class="ai__prompt-master_prompt-type_example" v-html="promptType.example"></i>
					</div>
				</li>
			</ul>
		</div>
	`
	};

	const PromptMasterProgress = {
	  props: {
	    stepsCount: {
	      type: Number,
	      required: true,
	      default: 1
	    },
	    currentStep: {
	      type: Number,
	      required: true,
	      default: 1
	    }
	  },
	  methods: {
	    getProgressStepClassname(isPassedStep) {
	      return {
	        'ai__prompt-master-progress_step': true,
	        '--passed': isPassedStep
	      };
	    }
	  },
	  template: `
		<div class="ai__prompt-master-progress">
			<div
				v-for="(_, step) in stepsCount"
				:class="getProgressStepClassname(step < currentStep)"
			>
			</div>
		</div>
	`
	};

	const PromptMasterAlertMessage = {
	  props: {
	    text: {
	      type: String,
	      required: true,
	      default: ''
	    }
	  },
	  computed: {
	    alertHtml() {
	      const alert = new ui_alerts.Alert({
	        icon: ui_alerts.AlertIcon.INFO,
	        color: ui_alerts.AlertColor.WARNING,
	        size: ui_alerts.AlertSize.XS,
	        text: this.text
	      });
	      return alert.render().outerHTML;
	    }
	  },
	  template: `
		<div v-html="alertHtml"></div>
	`
	};

	const PromptMasterStep = {
	  components: {
	    PromptMasterProgress,
	    PromptMasterAlertMessage
	  },
	  props: {
	    suptitle: {
	      type: String,
	      required: true,
	      default: ''
	    },
	    title: {
	      type: String,
	      required: true,
	      default: ''
	    },
	    stepIndex: {
	      type: Number,
	      required: true,
	      default: 0
	    },
	    stepsCount: {
	      type: Number,
	      required: true,
	      default: 3
	    },
	    alertMessage: {
	      type: String,
	      required: false,
	      default: ''
	    }
	  },
	  template: `
		<div class="ai__prompt-master-step">
			<header class="ai__prompt-master-step_header">
				<span class="ai__prompt-master-step_suptitle">{{ suptitle }}</span>
				<h4 class="ai__prompt-master-step_title">{{ title }}</h4>
				<div class="ai__prompt-master-step__progress">
					<PromptMasterProgress :current-step="stepIndex" :steps-count="stepsCount" />
				</div>
				<div v-if="alertMessage" class="ai__prompt-master-step__alert-message">
					<PromptMasterAlertMessage :text="alertMessage"></PromptMasterAlertMessage>
				</div>
			</header>
			<main class="ai__prompt-master-step_content">
				<slot name="content"></slot>
			</main>
			<footer
				class="ai__prompt-master-step_footer"
			>
				<slot name="footer"></slot>
			</footer>
		</div>
	`
	};

	// eslint-disable-next-line sonarjs/cognitive-complexity
	const createRangeWithPosition = (node, targetPosition) => {
	  const range = document.createRange();
	  range.selectNode(node);
	  range.setStart(node, 0);
	  let pos = 0;
	  const stack = [node];
	  while (stack.length > 0) {
	    const current = stack.pop();
	    if (current.nodeType === Node.TEXT_NODE) {
	      const len = current.textContent.length;
	      if (pos + len >= targetPosition) {
	        range.setStart(current, targetPosition - pos);
	        range.setEnd(current, targetPosition - pos);
	        return range;
	      }
	      pos += len;
	    } else if (current.nodeType === Node.ELEMENT_NODE && current.childNodes.length === 0) {
	      if (pos === targetPosition) {
	        range.setStart(current, 0);
	        range.setEnd(current, 0);
	        return range;
	      }
	    } else if (current.childNodes && current.childNodes.length > 0) {
	      if (current.nodeName === 'DIV' && current !== node && current !== node.childNodes[0]) {
	        pos += 1;
	      }
	      for (let i = current.childNodes.length - 1; i >= 0; i--) {
	        stack.push(current.childNodes[i]);
	      }
	    }
	  }
	  range.setStart(node, node.childNodes.length);
	  range.setEnd(node, node.childNodes.length);
	  return range;
	};
	const setCursorPosition = (node, targetPosition) => {
	  const range = createRangeWithPosition(node, targetPosition);
	  const selection = window.getSelection();
	  selection.removeAllRanges();
	  selection.addRange(range);
	};
	const getCursorPosition = node => {
	  var _node$firstChild, _node$childNodes$;
	  const selection = window.getSelection();
	  if (!selection.rangeCount) {
	    return 0;
	  }
	  const range = selection.getRangeAt(0);
	  const clonedRange = range.cloneRange();
	  clonedRange.selectNodeContents(node);
	  clonedRange.setEnd(range.endContainer, range.endOffset);
	  let cursorPosition = clonedRange.toString().length;
	  const div = document.createElement('div');
	  main_core.Dom.append(clonedRange.cloneContents(), div);
	  const lineBreakElements = div.querySelectorAll('div');
	  cursorPosition += lineBreakElements.length;
	  if (((_node$firstChild = node.firstChild) == null ? void 0 : _node$firstChild.nodeName) === 'DIV' && ((_node$childNodes$ = node.childNodes[1]) == null ? void 0 : _node$childNodes$.nodeType) !== Node.TEXT_NODE) {
	    cursorPosition -= 1;
	  }
	  return cursorPosition;
	};

	const PromptMasterEditor = {
	  props: {
	    text: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    placeholder: {
	      Type: String,
	      required: false,
	      default: ''
	    },
	    maxSymbolsCount: {
	      type: Number,
	      required: true,
	      default: 2500
	    },
	    useClarification: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    isShown: {
	      type: Boolean,
	      required: false,
	      default: false
	    }
	  },
	  data() {
	    return {
	      selection: null,
	      symbolsCount: this.text.length,
	      editorInFocus: false,
	      cursorPosition: 0
	    };
	  },
	  computed: {
	    textContent() {
	      return this.convertTextToHtml(this.text);
	    },
	    symbolCounterClassname() {
	      return {
	        'ai__prompt-master-editor_symbols-counter': true,
	        '--error': this.symbolsCount > this.maxSymbolsCount
	      };
	    },
	    clarificationBtnClassname() {
	      return {
	        'ai__prompt-master-editor_clarification-btn': true,
	        '--disabled': this.editorInFocus === false
	      };
	    }
	  },
	  methods: {
	    formatEditorContent() {
	      this.$refs.editor.innerHTML = this.textContent;
	    },
	    convertTextToHtml(str) {
	      if (main_core.Type.isStringFilled(str) === false) {
	        return '';
	      }
	      const stringWithoutDoubleLineBreaks = main_core.Text.encode(str);
	      const lines = stringWithoutDoubleLineBreaks.split('\n');
	      if (lines.every(line => line === '')) {
	        lines.shift();
	      }
	      let resultHtmlString = lines.map((line, index) => {
	        if (index === 0 && line !== '') {
	          return line;
	        }
	        return `<div>${line === '' ? '<br>' : line}</div>`;
	      }).join('');
	      if (this.useClarification) {
	        resultHtmlString = resultHtmlString.replaceAll('<strong>', '').replaceAll('</strong>', '').replaceAll('[', '<strong>[').replaceAll(']', ']</strong>');
	      }
	      return resultHtmlString;
	    },
	    checkSelection() {
	      requestAnimationFrame(() => {
	        if (window.getSelection().toString()) {
	          this.selection = window.getSelection();
	        } else {
	          this.selection = null;
	        }
	      });
	    },
	    addClarification() {
	      if (this.editorInFocus === false) {
	        return;
	      }
	      const selection = window.getSelection();
	      if (!selection.rangeCount) {
	        return;
	      }
	      const cursorPosition = this.getEditorCursorPosition();
	      const range = selection.getRangeAt(0);
	      const strong = document.createElement('strong');
	      strong.textContent = `[ ${this.$Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_EDITOR_CLARIFICATION')} ]`;
	      if (range.startContainer.nodeName === 'BR') {
	        const parent = range.startContainer.parentNode;
	        parent.children[0].remove();
	        range.setStart(parent, 0);
	        range.setEnd(parent, 0);
	      }
	      range.deleteContents();
	      range.insertNode(strong);
	      main_core.bindOnce(window, 'mouseup', () => {
	        this.focusEditor();
	        this.setEditorCursorPosition(cursorPosition + strong.textContent.length);
	      });
	      this.$emit('input-text', this.getEditorContent());
	    },
	    handleInput(e) {
	      // hack for :empty with dynamic content in Safari
	      main_core.Dom.style(this.$refs.placeholder, 'display', 'initial');
	      main_core.Dom.style(this.$refs.placeholder, 'display', null);
	      if (e.inputType !== 'insertCompositionText') {
	        this.$emit('input-text', this.getEditorContent());
	      }
	      return true;
	    },
	    getEditorContent() {
	      if (main_core.Browser.isSafari()) {
	        return this.$refs.editor.innerText.trimEnd();
	      }
	      return this.$refs.editor.innerText.replaceAll('\r\n', '\n\n').replaceAll('\n\n', '\n');
	    },
	    focusEditor() {
	      this.setEditorCursorPosition(999);
	      this.cursorPosition = 999;
	      this.$refs.editor.focus();
	    },
	    handlePaste(event) {
	      event.preventDefault();
	      let text = (event.clipboardData || window.clipboardData).getData('text/plain');
	      text = text.replaceAll('\r\n', '\n').replaceAll('\n', '\n\n');
	      const selection = window.getSelection();
	      if (!selection.rangeCount) {
	        return false;
	      }
	      selection.deleteFromDocument();
	      const textNode = document.createTextNode(text);
	      const range = selection.getRangeAt(0);
	      if (range.startContainer.nodeName === 'BR') {
	        const parent = range.startContainer.parentNode;
	        parent.children[0].remove();
	        range.setStart(parent, 0);
	        range.setEnd(parent, 0);
	      }
	      range.insertNode(textNode);
	      range.setStartAfter(textNode);
	      range.setEndAfter(textNode);
	      selection.removeAllRanges();
	      selection.addRange(range);
	      this.cursorPosition = this.getEditorCursorPosition();
	      this.$emit('input-text', this.getEditorContent());
	      return true;
	    },
	    updateSymbolsCounter() {
	      this.symbolsCount = this.getEditorContent().length;
	    },
	    handleFocus() {
	      this.editorInFocus = true;
	    },
	    handleBlur() {
	      this.editorInFocus = false;
	    },
	    handleKeyDown(e) {
	      if (e.shiftKey && e.code === 'Enter') {
	        e.preventDefault();
	        return false;
	      }
	      if (e.metaKey || e.ctrlKey) {
	        const allowedKeysWithCtrlAndMeta = ['KeyA', 'KeyZ', 'KeyC', 'KeyV', 'KeyR', 'KeyX', 'KeyR', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Backspace'];
	        const key = e.code;
	        if (allowedKeysWithCtrlAndMeta.includes(key) === false) {
	          e.preventDefault();
	          return false;
	        }
	      }
	      return true;
	    },
	    handleCompositionEndEvent() {
	      this.$emit('input-text', this.getEditorContent());
	    },
	    getEditorCursorPosition() {
	      return getCursorPosition(this.$refs.editor);
	    },
	    setEditorCursorPosition(position) {
	      return setCursorPosition(this.$refs.editor, position);
	    }
	  },
	  watch: {
	    text() {
	      this.cursorPosition = this.getEditorCursorPosition();
	      this.formatEditorContent();
	      if (this.editorInFocus === false) {
	        this.focusEditor();
	      }
	      this.setEditorCursorPosition(this.cursorPosition);
	      this.updateSymbolsCounter();
	    },
	    isShown(newValue, oldValue) {
	      if (newValue === true && oldValue === false) {
	        requestAnimationFrame(() => {
	          this.formatEditorContent();
	          this.focusEditor();
	        });
	      }
	    }
	  },
	  mounted() {
	    this.focusEditor();
	  },
	  template: `
		<div
			class="ai__prompt-master-editor-wrapper"
		>
			<div class="ai__prompt-master-editor-inner">
				<div
					v-once
					@mouseup="checkSelection"
					class="ai__prompt-master-editor"
					contenteditable="true"
					ref="editor"
					@input="handleInput"
					@compositionend="handleCompositionEndEvent"
					@paste="handlePaste"
					@focus="handleFocus"
					@blur="handleBlur"
					@keydown="handleKeyDown"
					v-html="textContent"
				>
				</div>
				<span ref="placeholder" class="ai__prompt-master-editor-inner_placeholder">
					{{ placeholder ? placeholder : $Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_EDITOR_PLACEHOLDER') }}
				</span>
				<div
					v-if="useClarification"
					:aria-disabled="editorInFocus"
					@mousedown="addClarification"
					:class="clarificationBtnClassname"
				>
					<span class="ai__prompt-master-editor_clarification-btn-icon"></span>
					<span class="ai__prompt-master-editor_clarification-btn-text">
					{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_EDITOR_ADD_CLARIFICATION') }}
				</span>
				</div>
			</div>

			<div :class="symbolCounterClassname">
				<span class="ai__prompt-master-editor_symbols-counter-current">
					{{ symbolsCount }}
				</span>
				/
				<span class="ai__prompt-master-editor_symbols-counter-total">
					{{ maxSymbolsCount }}
				</span>
			</div>
		</div>
	`
	};

	var _Extension$getSetting;
	const language = (_Extension$getSetting = main_core.Extension.getSettings('ai.prompt-master').language) != null ? _Extension$getSetting : 'en';
	const PromptMasterEditorStep = {
	  props: {
	    promptText: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    useClarification: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    maxSymbolsCount: {
	      type: Number,
	      required: true,
	      default: 2500
	    },
	    isShown: {
	      type: Boolean,
	      required: false,
	      default: false
	    }
	  },
	  components: {
	    PromptMasterEditor,
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  data() {
	    return {
	      isInfoSliderShown: false,
	      animation: null
	    };
	  },
	  computed: {
	    closeInfoSliderIcon() {
	      return ui_iconSet_api_core.Actions.CROSS_30;
	    },
	    instructionSecondStepText() {
	      return this.$Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_STEP_ABOUT_STEP_2', {
	        '#ICON#': '<span class="ai__prompt-master_about-editor-slider-step-clarification-icon"></span>'
	      });
	    },
	    editorPlaceholder() {
	      return this.useClarification ? this.$Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_EDITOR_PLACEHOLDER_FOR_TEMPLATE_PROMPT') : this.$Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_EDITOR_PLACEHOLDER_FOR_DEFAULT_PROMPT');
	    }
	  },
	  methods: {
	    openInfoSlider() {
	      this.isInfoSliderShown = true;
	    },
	    closeInfoSlider() {
	      this.isInfoSliderShown = false;
	    },
	    handleInput(text) {
	      this.$emit('input-text', text);
	    },
	    async loadLottieAnimation() {
	      const {
	        Lottie
	      } = await main_core.Runtime.loadExtension('ui.lottie');
	      const path = language.toLowerCase() === 'ru' ? '/bitrix/js/ai/prompt-master/lottie/insert-text-guide-ru.json' : '/bitrix/js/ai/prompt-master/lottie/insert-text-guide-en.json';
	      this.animation = Lottie.loadAnimation({
	        path,
	        container: this.$refs.guideContainer,
	        loop: true,
	        autoplay: true,
	        renderer: 'svg',
	        rendererSettings: {
	          viewBoxOnly: true
	        }
	      });
	    }
	  },
	  watch: {
	    async isInfoSliderShown() {
	      if (this.isInfoSliderShown) {
	        if (this.animation) {
	          this.animation.play();
	          return;
	        }
	        await this.loadLottieAnimation();
	      } else {
	        var _this$animation;
	        (_this$animation = this.animation) == null ? void 0 : _this$animation.stop();
	      }
	    }
	  },
	  template: `
		<div class="ai__prompt-master_prompt-step">
			<div class="ai__prompt-master_prompt-step-editor">
				<PromptMasterEditor
					:is-shown="isShown"
					:text="promptText"
					:use-clarification="useClarification"
					:max-symbols-count="maxSymbolsCount"
					:placeholder="editorPlaceholder"
					@input-text="handleInput"
				/>
			</div>
			<div
				v-if="useClarification"
				class="ai__prompt-master_prompt-step-more-details"
			>
				<span @click="openInfoSlider" class="ai__prompt-master_more-details">
					{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_STEP_MORE') }}
				</span>
			</div>
			<transition>
				<div
					v-show="isInfoSliderShown"
					@click="closeInfoSlider"
					class="ai__prompt-master_about-editor-slider-wrapper"
				>
					<div class="ai__prompt-master_about-editor-slider">
						<header class="ai__prompt-master_about-editor-slider__header">
							<h4 class="ai__prompt-master_about-editor-slider__title">
								{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_STEP_ABOUT_TITLE') }}
							</h4>
							<div
								@click="closeInfoSlider"
								class="ai__prompt-master_about-editor-slider__close-icon"
							>
								<BIcon :name="closeInfoSliderIcon" :size="20"></BIcon>
							</div>
						</header>
						<main class="ai__prompt-master_about-editor-slider__main">
							<div ref="guideContainer" class="ai__prompt-master_about-editor-slider__video-wrapper"
							></div>
							<ul class="ai__prompt-master_about-editor-slider-steps">
								<li class="ai__prompt-master_about-editor-slider-step">
									{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_STEP_ABOUT_STEP_1') }}
								</li>
								<li class="ai__prompt-master_about-editor-slider-step">
									<span v-html="instructionSecondStepText"></span>
								</li>
								<li class="ai__prompt-master_about-editor-slider-step">
									{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_STEP_ABOUT_STEP_3') }}
								</li>
							</ul>
						</main>
					</div>
				</div>
			</transition>
		</div>
	`
	};

	const availablePromptIcons = [ui_iconSet_api_core.Main.ROCKET, ui_iconSet_api_core.Main.SHINING, ui_iconSet_api_core.Main.CHAT_MESSAGE, ui_iconSet_api_core.Main.INFO_CIRCLE, ui_iconSet_api_core.Main.WARNING, ui_iconSet_api_core.Main.PERSON, ui_iconSet_api_core.Main.BLACK_CLOCK, ui_iconSet_api_core.Main.CAMERA, ui_iconSet_api_core.Main.MICROPHONE_ON, ui_iconSet_api_core.Main.PICTURE, ui_iconSet_api_core.Main.BELL, ui_iconSet_api_core.Main.HEART, ui_iconSet_api_core.Main.CROWN_1, ui_iconSet_api_core.Main.HOME, ui_iconSet_api_core.Main.SHIELD, ui_iconSet_api_core.Main.SUITCASE, ui_iconSet_api_core.Main.CIRCLE_CHECK, ui_iconSet_api_core.Main.FIRE, ui_iconSet_api_core.Main.CRM, ui_iconSet_api_core.Main.LOCATION_1, ui_iconSet_api_core.Main.PRINT_1, ui_iconSet_api_core.CRM.DEAL, ui_iconSet_api_core.Main.CITY, ui_iconSet_api_core.Main.GIFT, ui_iconSet_api_core.Main.STOP_HAND, ui_iconSet_api_core.Main.ROBOT, ui_iconSet_api_core.Main.SITES_STORES, ui_iconSet_api_core.Main.CLOCK_BLACK_WHITE, ui_iconSet_api_core.Main.CASH_TERMINAL];

	let _ = t => t,
	  _t,
	  _t2;
	const PromptMasterIconSelector = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    selectedIcon: {
	      type: String,
	      required: false,
	      default: ''
	    }
	  },
	  data() {
	    return {
	      isPopupShown: false,
	      popup: null
	    };
	  },
	  computed: {
	    selectedIconColor() {
	      return getComputedStyle(document.body).getPropertyValue('--ui-color-copilot-primary');
	    }
	  },
	  methods: {
	    toggleIconsPopup() {
	      if (this.isPopupShown) {
	        this.closeIconsPopup();
	      } else {
	        this.showIconsPopup();
	      }
	    },
	    closeIconsPopup() {
	      var _this$popup;
	      this.isPopupShown = false;
	      (_this$popup = this.popup) == null ? void 0 : _this$popup.close();
	      this.popup = null;
	    },
	    showIconsPopup() {
	      if (this.isPopupShown) {
	        return;
	      }
	      if (!this.popup) {
	        this.initIconsPopup();
	      }
	      this.isPopupShown = true;
	      this.popup.show();
	    },
	    initIconsPopup() {
	      const bindElementPosition = main_core.Dom.getPosition(this.$el);
	      this.popup = new main_popup.Popup({
	        content: this.getPopupContent(),
	        bindElement: {
	          top: bindElementPosition.bottom,
	          left: bindElementPosition.left - 210
	        },
	        autoHide: true,
	        closeByEsc: true,
	        cacheable: false,
	        maxWidth: 299,
	        maxHeight: 208,
	        angle: {
	          offset: 253,
	          position: 'top'
	        },
	        events: {
	          onPopupClose: () => {
	            this.isPopupShown = false;
	            this.popup = null;
	          }
	        }
	      });
	    },
	    getPopupContent() {
	      const container = main_core.Tag.render(_t || (_t = _`<div class="ai__prompt-master-icon-selector_popup-content"></div>`));
	      availablePromptIcons.forEach(iconCode => {
	        const icon = new ui_iconSet_api_core.Icon({
	          icon: iconCode,
	          size: 24
	        });
	        const iconContainer = main_core.Tag.render(_t2 || (_t2 = _`<div class="ai__prompt-master-icon-selector_popup-content-icon"></div>`));
	        if (iconCode === this.selectedIcon) {
	          main_core.Dom.addClass(iconContainer, '--selected');
	        }
	        icon.renderTo(iconContainer);
	        main_core.Event.bind(iconContainer, 'click', () => {
	          this.selectIcon(iconCode);
	          this.closeIconsPopup();
	        });
	        main_core.Dom.append(iconContainer, container);
	      });
	      return container;
	    },
	    selectIcon(iconCode) {
	      this.$emit('select', iconCode);
	    }
	  },
	  unmounted() {
	    var _this$popup2;
	    (_this$popup2 = this.popup) == null ? void 0 : _this$popup2.destroy();
	    this.popup = null;
	  },
	  template: `
		<button @click="toggleIconsPopup" class="ai__prompt-master-icon-selector">
			<span ref="selectedIcon" class="ai__prompt-master-icon-selector_selected-icon">
				<BIcon :name="selectedIcon" :size="28" :color="selectedIconColor" />
			</span>
		</button>
	`
	};

	const clickableHint = {
	  beforeMount(bindElement, bindings) {
	    let popup = null;
	    let isMouseOnHintPopup = false;
	    const destroyPopup = () => {
	      var _popup;
	      (_popup = popup) == null ? void 0 : _popup.destroy();
	      popup = null;
	      isMouseOnHintPopup = false;
	    };
	    main_core.Event.bind(bindElement, 'mouseenter', () => {
	      if (popup === null) {
	        popup = createHintPopup(bindElement, bindings.value);
	        popup.show();
	        main_core.Event.bind(popup.getPopupContainer(), 'mouseenter', () => {
	          isMouseOnHintPopup = true;
	        });
	      }
	    });
	    main_core.Event.bind(bindElement, 'mouseleave', () => {
	      var _popup2;
	      const popupContainer = (_popup2 = popup) == null ? void 0 : _popup2.getPopupContainer();
	      setTimeout(() => {
	        if (isMouseOnHintPopup) {
	          main_core.bind(popupContainer, 'mouseleave', e => {
	            if (bindElement.contains(e.relatedTarget) === false) {
	              destroyPopup();
	            }
	          });
	        } else {
	          destroyPopup();
	        }
	      }, 100);
	    });
	  }
	};
	function createHintPopup(bindElement, html) {
	  const bindElementPosition = main_core.Dom.getPosition(bindElement);
	  return new main_popup.Popup({
	    bindElement: {
	      top: bindElementPosition.top + 10,
	      left: bindElementPosition.left + bindElementPosition.width / 2
	    },
	    className: 'ai__prompt-master_hint-popup',
	    darkMode: true,
	    content: html,
	    maxWidth: 266,
	    maxHeight: 300,
	    animation: 'fading-slide',
	    angle: true,
	    bindOptions: {
	      position: 'top'
	    }
	  });
	}

	const PromptMasterUserSelector = {
	  directives: {
	    clickableHint
	  },
	  props: {
	    selectedItems: {
	      type: Array,
	      required: false,
	      default: () => {
	        return [];
	      }
	    },
	    maxCirclesInInput: {
	      type: Number,
	      required: false,
	      default: 8
	    },
	    undeselectedItems: {
	      type: Array,
	      required: false,
	      default: () => {
	        return [];
	      }
	    }
	  },
	  data() {
	    return {
	      etcItemHint: null,
	      cursorOnEtcItem: false,
	      selectedItemsWithData: [],
	      dataIsLoaded: false
	    };
	  },
	  computed: {
	    preselectedItems() {
	      return this.typedSelectedItems.map(item => {
	        return item;
	      });
	    },
	    typedSelectedItems() {
	      return this.selectedItems;
	    },
	    etcItemHintContent() {
	      const titles = this.selectedItemsWithData.slice(this.maxCirclesInInput).map(item => this.getEncodedString(item.title));
	      const titlesText = titles.join('<br>');
	      return `<div>${titlesText}</div>`;
	    },
	    etcSelectedItemsCount() {
	      return this.selectedItems.slice(this.maxCirclesInInput).length;
	    },
	    etcSelectedItemsCircleNumber() {
	      return this.etcSelectedItemsCount < 100 ? this.etcSelectedItemsCount : 99;
	    }
	  },
	  methods: {
	    updateSelectedItemsWithData() {
	      const selectedItems = this.getUserSelectorDialog().getSelectedItems();
	      if (selectedItems.length === this.selectedItemsWithData.length) {
	        return;
	      }
	      this.selectedItemsWithData = selectedItems.map(item => {
	        return this.getSelectedItemsWithDataFromDialogItem(item);
	      });
	    },
	    getSelectedItemsWithDataFromDialogItem(item) {
	      return {
	        id: item.id,
	        avatar: item.avatar,
	        entityId: item.entityId,
	        title: item.title.text
	      };
	    },
	    getUserSelectorDialog() {
	      const existingDialog = ui_entitySelector.Dialog.getById('ai-prompt-master-user-selector');
	      if (existingDialog) {
	        existingDialog.setTargetNode(this.$refs.userSelector);
	        return existingDialog;
	      }
	      return new ui_entitySelector.Dialog({
	        id: 'ai-prompt-master-user-selector',
	        targetNode: this.$refs.userSelector,
	        width: 400,
	        height: 300,
	        dropdownMode: false,
	        showAvatars: true,
	        compactView: true,
	        multiple: true,
	        preload: true,
	        enableSearch: true,
	        entities: [{
	          id: 'user',
	          options: {
	            inviteEmployeeLink: false
	          }
	        }, {
	          id: 'department',
	          options: {
	            selectMode: 'usersAndDepartments'
	          }
	        }, {
	          id: 'meta-user',
	          options: {
	            'all-users': true
	          }
	        }, {
	          id: 'project'
	        }],
	        preselectedItems: this.preselectedItems,
	        undeselectedItems: this.undeselectedItems,
	        events: {
	          'Item:onSelect': event => {
	            this.selectItem(event.getData().item);
	          },
	          'Item:onDeselect': event => {
	            this.deselectItem(event.getData().item);
	          },
	          onLoad: () => {
	            this.dataIsLoaded = true;
	            this.updateSelectedItemsWithData();
	          }
	        }
	      });
	    },
	    showUserSelector() {
	      const dialog = this.getUserSelectorDialog();
	      dialog.show();
	    },
	    selectItem(item) {
	      this.$emit('select-item', {
	        id: item.id,
	        entityId: item.entityId
	      });
	    },
	    deselectItem(item) {
	      this.$emit('deselect-item', {
	        id: item.id,
	        entityId: item.entityId
	      });
	    },
	    getSelectedItemStyle(item, index) {
	      const backgroundImage = `url('${this.getAvatarFromItem(item)}')`;
	      return {
	        backgroundImage,
	        left: `${24 * index - 8}px`
	      };
	    },
	    getAvatarFromItem(item) {
	      if (item.avatar) {
	        return item.avatar;
	      }
	      if (item.entityId === 'user') {
	        return '/bitrix/js/socialnetwork/entity-selector/src/images/default-user.svg';
	      }
	      if (item.entityId === 'meta-user') {
	        return '/bitrix/js/socialnetwork/entity-selector/src/images/meta-user-all.svg';
	      }
	      return '';
	    },
	    getDepartmentFirstLetter(title) {
	      return title.split(' ')[0][0].toUpperCase();
	    },
	    showEtcItemsHint() {
	      this.cursorOnEtcItem = true;
	      if (this.etcItemHint) {
	        return;
	      }
	      this.etcItemHint = new main_popup.Popup({
	        bindElement: this.$refs.etcItem,
	        darkMode: true,
	        content: this.etcItemHintContent,
	        autoHide: true,
	        maxHeight: 300,
	        bindOptions: {
	          position: 'top'
	        },
	        animation: 'fading-slide',
	        angle: true
	      });
	      this.etcItemHint.setOffset({
	        offsetTop: -10,
	        offsetLeft: 16
	      });
	      this.etcItemHint.show();
	    },
	    closeEtcItemsHint() {
	      this.cursorOnEtcItem = false;
	      setTimeout(() => {
	        const hoveredItems = document.querySelectorAll(':hover');
	        const lastHoveredItem = hoveredItems[hoveredItems.length - 1];
	        const popupContainer = this.etcItemHint.getPopupContainer();
	        const isHintPopupUnderCursor = popupContainer.contains(lastHoveredItem);
	        if (isHintPopupUnderCursor === false) {
	          this.destroyEtcItemsHint();
	          return;
	        }
	        main_core.bind(popupContainer, 'mouseleave', () => {
	          setTimeout(() => {
	            if (this.cursorOnEtcItem === false) {
	              this.destroyEtcItemsHint();
	            }
	          }, 100);
	        });
	      }, 100);
	    },
	    destroyEtcItemsHint() {
	      var _this$etcItemHint;
	      (_this$etcItemHint = this.etcItemHint) == null ? void 0 : _this$etcItemHint.destroy();
	      this.etcItemHint = null;
	    },
	    getEncodedString(str) {
	      return main_core.Text.encode(str);
	    }
	  },
	  watch: {
	    'selectedItems.length': function () {
	      this.updateSelectedItemsWithData();
	    }
	  },
	  mounted() {
	    this.updateSelectedItemsWithData();
	  },
	  unmounted() {
	    this.getUserSelectorDialog().destroy();
	  },
	  template: `
		<div class="ai__prompt-master_user-selector">
			<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
				<div
					type="text"
					class="ui-ctl-element"
				>
					<div ref="userSelector" class="ai__prompt-master_user-selector_inner">
						<ul class="ai__prompt-master-user-selector_users">
							<li
								v-for="(item, index) in selectedItemsWithData.slice(0, maxCirclesInInput)"
								:style="getSelectedItemStyle(item, index)"
								v-clickable-hint="getEncodedString(item.title)"
								class="ai__prompt-master-user-selector_user"
							>
								<span v-if="item.entityId === 'department'">
									{{ getDepartmentFirstLetter(item.title) }}
								</span>
							</li>
							<li
								v-if="etcSelectedItemsCount > 0"
								ref="etcItem"
								class="ai__prompt-master-user-selector_etc-item"
								@mouseenter="showEtcItemsHint"
								@mouseleave="closeEtcItemsHint"
								:style="{left: 24 * this.maxCirclesInInput - 8 + 'px'}"
							>
								<span class="ai__prompt-master-user-selector_etc-item-plus">+</span>
								<span>{{ etcSelectedItemsCircleNumber }}</span>
							</li>
						</ul>
						<button @click="showUserSelector" class="ai__prompt-master-user-selector_add">
							<span class="ai__prompt-master-user-selector_add-text">
								{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_USER_SELECTOR_ADD_BTN') }}
							</span>
						</button>
					</div>
				</div>
			</div>
			<div v-if="getUserSelectorDialog().getItems().length === 0" class="ai__prompt-master_user-selector-loader">
				<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
					<div
						type="text"
						class="ui-ctl-element"
					>
						<div class="ai__prompt-master_user-selector_inner">
							<ul class="ai__prompt-master-user-selector_users">
								<li
									v-for="(item, index) in selectedItems.slice(0, maxCirclesInInput)"
									:style="getSelectedItemStyle(item, index)"
									class="ai__prompt-master-user-selector_user"
								>
								<span v-if="item.entityId === 'department'">
									{{ getDepartmentFirstLetter(item.title) }}
								</span>
								</li>
								<li
									v-if="etcSelectedItemsCount > 0"
									class="ai__prompt-master-user-selector_etc-item"
									:style="{left: 24 * this.maxCirclesInInput - 8 + 'px'}"
								>
									<span class="ai__prompt-master-user-selector_etc-item-plus">+</span>
									<span>{{ etcSelectedItemsCircleNumber }}</span>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
	`
	};

	const PromptMasterCategoriesSelector = {
	  props: {
	    selectedCategoryIds: {
	      type: Array,
	      required: false,
	      default() {
	        return [];
	      }
	    }
	  },
	  data() {
	    return {
	      allCategories: [],
	      tagSelector: null
	    };
	  },
	  computed: {
	    dialogItemOptions() {
	      return this.allCategories.map(category => {
	        return this.getTagSelectorItemFromCategory(category);
	      });
	    }
	  },
	  methods: {
	    async getAllCategories() {
	      const result = await main_core.ajax.runAction('ai.prompt.getCategoriesListWithTranslations');
	      return result.data.list;
	    },
	    getTagSelectorItemFromCategory(category) {
	      return {
	        id: category.code,
	        title: category.name,
	        tabs: ['prompt-category'],
	        entityId: 'prompt-category'
	      };
	    },
	    selectCategory(item) {
	      this.$emit('select', item.id);
	    },
	    deselectCategory(item) {
	      this.$emit('deselect', item.id);
	    },
	    initTagSelector() {
	      const tagSelector = new ui_entitySelector.TagSelector({
	        items: [],
	        tagMaxWidth: 300,
	        dialogOptions: {
	          id: 'ai-prompt-master-categories-selector-dialog',
	          recentItemsLimit: 0,
	          compactView: true,
	          dropdownMode: true,
	          width: 400,
	          items: [],
	          tabs: [{
	            id: 'prompt-category'
	          }],
	          events: {
	            'Item:onSelect': event => {
	              this.selectCategory(event.getData().item);
	            },
	            'Item:onDeselect': event => {
	              this.deselectCategory(event.getData().item);
	            }
	          }
	        }
	      });
	      tagSelector.renderTo(this.$el);
	      return tagSelector;
	    }
	  },
	  async mounted() {
	    const tagSelector = this.initTagSelector();
	    this.allCategories = await this.getAllCategories();
	    this.dialogItemOptions.forEach(item => {
	      var _tagSelector$getDialo;
	      const selected = this.selectedCategoryIds.length > 0 ? this.selectedCategoryIds.includes(item.id) : true;
	      (_tagSelector$getDialo = tagSelector.getDialog()) == null ? void 0 : _tagSelector$getDialo.addItem({
	        ...item,
	        selected
	      });
	    });
	    if (this.selectedCategoryIds.length === 0) {
	      this.allCategories.forEach(item => {
	        this.$emit('select', item.code);
	      });
	    }
	  },
	  unmounted() {
	    var _Dialog$getById;
	    (_Dialog$getById = ui_entitySelector.Dialog.getById('ai-prompt-master-categories-selector-dialog')) == null ? void 0 : _Dialog$getById.destroy();
	  },
	  template: `
		<div class="ai__prompt-master_prompt-categories-selector"></div>
	`
	};

	const PromptMasterHint = {
	  directives: {
	    clickableHint
	  },
	  props: {
	    html: String
	  },
	  template: `
		<span class="ui-hint" v-clickable-hint="html">
			<span class="ui-hint-icon"/>
		</span>
	`
	};

	let _$1 = t => t,
	  _t$1;
	const PromptMasterAccessStep = {
	  directives: {
	    hoverHint: clickableHint
	  },
	  components: {
	    PromptMasterIconSelector,
	    PromptMasterUserSelector,
	    PromptMasterCategoriesSelector,
	    PromptMasterHint
	  },
	  props: {
	    promptTitle: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    promptIcon: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    promptAuthorId: {
	      type: String,
	      required: false,
	      default: '-1'
	    },
	    selectedItems: {
	      type: Array,
	      required: false,
	      default: () => {
	        return [];
	      }
	    },
	    selectedCategories: {
	      type: Array,
	      required: false,
	      default: () => {
	        return [];
	      }
	    },
	    isShown: {
	      type: Boolean,
	      required: false,
	      default: false
	    }
	  },
	  computed: {
	    accessPointsHintHtml() {
	      const htmlMessage = this.$Bitrix.Loc.getMessage('PROMPT_MASTER_ACCESS_STEP_POINTS_HINT', {
	        '<link>': '<a style="display: inline-block;" href="#">',
	        '</link>': '</a>'
	      });
	      const elem = main_core.Tag.render(_t$1 || (_t$1 = _$1`<div style="font-size: 14px;">${0}</div>`), htmlMessage);
	      const link = elem.querySelector('a');
	      main_core.bind(link, 'click', () => {
	        const articleCode = '21979776';
	        const Helper = main_core.Reflection.getClass('top.BX.Helper');
	        if (Helper) {
	          Helper.show(`redirect=detail&code=${articleCode}`);
	        }
	      });
	      return elem;
	    }
	  },
	  mounted() {
	    this.$refs.promptTitleInput.focus();
	  },
	  methods: {
	    selectIcon(iconCode) {
	      this.$emit('select-icon', iconCode);
	    },
	    selectItem(item) {
	      this.$emit('select-item', item);
	    },
	    deselectItem(item) {
	      this.$emit('deselect-item', item);
	    },
	    handleNameInput(e) {
	      this.$emit('input-name', e.target.value);
	    },
	    selectCategory(categoryId) {
	      this.$emit('select-category', categoryId);
	    },
	    deselectCategory(categoryId) {
	      this.$emit('deselect-category', categoryId);
	    }
	  },
	  watch: {
	    isShown(newValue, oldValue) {
	      if (newValue === true && oldValue === false) {
	        requestAnimationFrame(() => {
	          this.$refs.promptTitleInput.focus();
	        });
	      }
	    }
	  },
	  template: `
		<div class="ai__prompt-master-access-step">
			<div class="ai__prompt-master-access-step_section">
				<div class="ai__prompt-master-access-step_section-title">
					{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_ACCESS_STEP_NAME_AND_ICON') }}
				</div>
				<div class="ai__prompt-master-access-step_section-content --row">
					<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
						<input
							ref="promptTitleInput"
							maxlength="70"
							type="text"
							:value="promptTitle"
							@input="handleNameInput"
							class="ui-ctl-element"
							:placeholder="$Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_TITLE_PLACEHOLDER')"
						/>
					</div>
					<div class="ai__prompt-master-access-step_icon-selector">
						<PromptMasterIconSelector @select="selectIcon" :selected-icon="promptIcon"/>
					</div>
				</div>
			</div>
			<div class="ai__prompt-master-access-step_section">
				<div class="ai__prompt-master-access-step_section-title">
					{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_ACCESS_STEP_GENERAL_ACCESS') }}
				</div>
				<div
					class="ai__prompt-master-access-step_section-content"
				>
					<PromptMasterUserSelector
						:selected-items="selectedItems"
						:undeselected-items="[['user', this.promptAuthorId]]"
						@select-item="selectItem"
						@deselect-item="deselectItem"
					/>
				</div>
			</div>
			<div class="ai__prompt-master-access-step_section">
				<div class="ai__prompt-master-access-step_section-title">
					<span>{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_ACCESS_STEP_POINTS') }}</span>
					<PromptMasterHint :html="accessPointsHintHtml"></PromptMasterHint>
				</div>
				<div class="ai__prompt-master-access-step_section-content">
					<div
						class="ai__prompt-master-access-step_section-content"
					>
						<PromptMasterCategoriesSelector
							:selected-category-ids="selectedCategories"
							@select="selectCategory"
							@deselect="deselectCategory"
						/>
					</div>
				</div>
			</div>
		</div>
	`
	};

	const PromptMasterBtn = {
	  props: {
	    text: {
	      type: String,
	      required: true,
	      default: ''
	    },
	    state: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    disabled: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    color: {
	      type: String,
	      required: false,
	      default: ui_buttons.Button.Color.AI
	    }
	  },
	  computed: {
	    buttonState() {
	      return this.disabled ? ui_buttons.Button.State.DISABLED : this.state;
	    },
	    buttonHtml() {
	      const btn = new ui_buttons.Button({
	        color: this.color,
	        size: ui_buttons.Button.Size.MEDIUM,
	        text: this.text,
	        round: true,
	        state: this.buttonState
	      });
	      btn.setDisabled(this.disabled);
	      return btn.render().outerHTML;
	    }
	  },
	  template: `
		<div v-html="buttonHtml"></div>
	`
	};

	const PromptMasterBackBtn = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  computed: {
	    iconColor() {
	      return getComputedStyle(document.body).getPropertyValue('--ui-color-copilot-secondary');
	    },
	    chevronLeftIconCode() {
	      return ui_iconSet_api_core.Actions.CHEVRON_LEFT;
	    }
	  },
	  template: `
		<button class="ai__prompt-master_back-btn">
			<BIcon :name="chevronLeftIconCode" :size="24"></BIcon>
			<span>{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_BTN_PREV') }}</span>
		</button>
	`
	};

	const PromptMasterSaveErrorScreen = {
	  components: {
	    PromptMasterBtn,
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  computed: {
	    errorIcon() {
	      return ui_iconSet_api_core.Main.NOTE_CIRCLE;
	    },
	    errorIconColor() {
	      return getComputedStyle(document.body).getPropertyValue('--ui-color-text-alert');
	    },
	    backBtnIconName() {
	      return ui_iconSet_api_core.Actions.CHEVRON_LEFT;
	    },
	    repeatBtnColor() {
	      return ui_buttons.Button.Color.LIGHT_BORDER;
	    }
	  },
	  methods: {
	    emitRepeatRequest() {
	      this.$emit('click-repeat-btn');
	    },
	    emitBackBtnClick() {
	      this.$emit('click-back-btn');
	    }
	  },
	  template: `
		<div class="ai__prompt-master_error-screen">
			<div class="ai__prompt-master_error-screen-icon">
				<BIcon
					:name="errorIcon"
					:color="errorIconColor"
					:size="66"
				/>
			</div>
			<span class="ai__prompt-master_error-screen-error">
				{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_SAVE_ERROR') }}
			</span>
			<div class="ai__prompt-master_error-screen-repeat-btn">
				<PromptMasterBtn
					@click="emitRepeatRequest"
					:color="repeatBtnColor"
					:text="$Bitrix.Loc.getMessage('PROMPT_MASTER_SAVE_REPEAT_BTN')"
				/>
			</div>
			<p class="ai__prompt-master_error-screen-warning-message">
				{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_ERROR_WARNING_MESSAGE') }}
			</p>
			<div @click="emitBackBtnClick" class="ai__prompt-master_error-screen-back-btn">
				<BIcon :name="backBtnIconName" :size="16"  />
				<span class="ai__prompt-master_error-screen-back-btn-text">
					{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_SAVE_ERROR_BACK_BTN') }}
				</span>
			</div>
		</div>
	`
	};

	const promptTypeName = {
	  DEFAULT: main_core.Loc.getMessage('PROMPT_MASTER_PROMPT_TYPE_FIRST_NAME'),
	  SIMPLE_TEMPLATE: main_core.Loc.getMessage('PROMPT_MASTER_PROMPT_TYPE_SECOND_NAME')
	};
	const currentUserId = main_core.Extension.getSettings('ai.prompt-master').get('userId');
	const PromptMaster = {
	  props: {
	    code: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    type: String,
	    title: String,
	    text: String,
	    icon: String,
	    categories: Array,
	    accessCodes: Array,
	    analyticCategory: String,
	    authorId: String
	  },
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  components: {
	    PromptMasterStep,
	    PromptMasterPromptTypes,
	    PromptMasterEditorStep,
	    PromptMasterAccessStep,
	    PromptMasterBtn,
	    PromptMasterBackBtn,
	    PromptMasterProgress,
	    PromptMasterAlertMessage,
	    PromptMasterSaveErrorScreen,
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  data() {
	    return {
	      currentStepIndex: 0,
	      promptType: this.type || '',
	      promptTitle: this.title || '',
	      promptText: this.text || '',
	      promptIcon: this.icon || ui_iconSet_api_core.Main.ROCKET,
	      selectedItems: this.accessCodes || [['user', currentUserId]],
	      promptCategories: this.categories || [],
	      saveButtonState: '',
	      isPromptSaving: false,
	      isPromptSaved: false,
	      isPromptSavedError: false
	    };
	  },
	  computed: {
	    isPromptEditing() {
	      return !this.isPromptSaving && !this.isPromptSavedError && !this.isPromptSaved;
	    },
	    isSecondStepEnabled() {
	      return Boolean(this.promptType);
	    },
	    promptTypeName() {
	      return promptTypeName[this.promptType];
	    },
	    chevronLeftIcon() {
	      return ui_iconSet_api_core.Actions.CHEVRON_LEFT;
	    },
	    checkIcon() {
	      return ui_iconSet_api_core.Main.CHECK;
	    },
	    isEditPromptMaster() {
	      return Boolean(this.code);
	    },
	    alertMessage() {
	      if (this.isEditPromptMaster && this.accessCodes.length > 1) {
	        return this.$Bitrix.Loc.getMessage('PROMPT_MASTER_TYPE_ALERT');
	      }
	      return '';
	    },
	    useClarificationInEditor() {
	      return this.promptType === promptTypes[1].id;
	    },
	    maxSymbolsCount() {
	      return 2500;
	    },
	    isPromptCanBeSave() {
	      const isTitleValid = this.promptTitle.length > 1 && this.promptTitle.length <= 70;
	      const isPromptCategoriesValid = this.promptCategories.length > 0;
	      const isAccessPointsValid = this.selectedItems.length > 0;
	      return isTitleValid && isPromptCategoriesValid && isAccessPointsValid && !this.isPromptSaving;
	    },
	    closeMasterBtnColor() {
	      return ui_buttons.Button.Color.LIGHT_BORDER;
	    },
	    promptEditorStepTitle() {
	      if (this.promptType === 'DEFAULT') {
	        return this.text ? this.$Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_STEP_TITLE_FOR_EDIT_SIMPLE_PROMPT') : this.$Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_STEP_TITLE_FOR_ADD_SIMPLE_PROMPT');
	      }
	      return this.$Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_STEP_TITLE');
	    }
	  },
	  methods: {
	    handlePromptTypeSelect(selectedPromptType) {
	      this.promptType = selectedPromptType;
	      // this.currentStepIndex += 1;
	    },

	    handlePromptIconSelect(selectedIcon) {
	      this.promptIcon = selectedIcon;
	    },
	    async savePrompt() {
	      if (this.isPromptCanBeSave === false) {
	        return;
	      }
	      let isLoadFinished = false;
	      try {
	        setTimeout(() => {
	          if (isLoadFinished === false) {
	            this.isPromptSavedError = false;
	            this.saveButtonState = ui_buttons.Button.State.WAITING;
	            this.isPromptSaving = true;
	          }
	        }, 100);
	        const action = this.code ? 'change' : 'create';
	        const data = {
	          promptCode: this.code,
	          analyticCategory: this.analyticCategory,
	          promptType: this.promptType,
	          promptTitle: this.promptTitle,
	          promptDescription: this.promptText,
	          promptIcon: this.promptIcon,
	          accessCodes: this.selectedItems,
	          categoriesForSave: this.promptCategories
	        };
	        await main_core.ajax.runAction(`ai.prompt.${action}`, {
	          data
	        });
	        main_core.Event.EventEmitter.emit('AI.prompt-master-app:save-success', data);
	        this.isPromptSaved = true;
	      } catch (e) {
	        console.error(e);
	        this.isPromptSavedError = true;
	        main_core.Event.EventEmitter.emit('AI.prompt-master-app:save-failed');
	      } finally {
	        this.isPromptSaving = false;
	        isLoadFinished = true;
	        this.saveButtonState = '';
	      }
	    },
	    selectItem(user) {
	      this.selectedItems.push([user.entityId, user.id]);
	    },
	    deselectItem(item) {
	      const removingUserIndex = this.selectedItems.findIndex(currentItem => {
	        return currentItem[0] === item.entityId && String(currentItem[1]) === String(item.id);
	      });
	      if (removingUserIndex > -1) {
	        this.selectedItems.splice(removingUserIndex, 1);
	      }
	    },
	    handlePromptNameInput(name) {
	      this.promptTitle = name;
	    },
	    handlePromptTextInput(text) {
	      this.promptText = text;
	    },
	    selectCategory(categoryId) {
	      this.promptCategories.push(categoryId);
	    },
	    deselectCategory(categoryId) {
	      const removingCategoryIndex = this.promptCategories.indexOf(categoryId);
	      this.promptCategories.splice(removingCategoryIndex, 1);
	    },
	    handleBackBtnClick() {
	      this.isPromptSavedError = false;
	    },
	    handleRepeatRequestBtnClick() {
	      this.savePrompt();
	    },
	    openArticleAboutPromptMaster() {
	      const articleCode = '21979776';
	      const Helper = main_core.Reflection.getClass('top.BX.Helper');
	      if (Helper) {
	        Helper.show(`redirect=detail&code=${articleCode}`);
	      }
	    },
	    emitCloseMasterEvent() {
	      main_core.Event.EventEmitter.emit('AI.prompt-master-app:close-master');
	    }
	  },
	  watch: {
	    isPromptSaving(isSaving) {
	      if (isSaving === false) {
	        return;
	      }
	      const copilotPrimaryColor = getComputedStyle(document.body).getPropertyValue('--ui-color-copilot-primary');
	      const loader = new main_loader.Loader({
	        size: 80,
	        strokeWidth: 4,
	        target: this.$refs.loaderScreen,
	        color: copilotPrimaryColor
	      });
	      loader.show(this.$refs.loaderScreen);
	    }
	  },
	  template: `
		<div class="ai__prompt-master">
			<transition-group>
				<PromptMasterStep
					v-show="currentStepIndex === 0 && isPromptEditing"
					:suptitle="$Bitrix.Loc.getMessage('PROMPT_MASTER_SELECT_TYPE_STEP_SUPTITLE')"
					:title="$Bitrix.Loc.getMessage('PROMPT_MASTER_SELECT_TYPE_STEP_TITLE')"
					:steps-count="3"
					:step-index="1"
					:alert-message="alertMessage"
				>
					<template #content>
						<PromptMasterPromptTypes @select="handlePromptTypeSelect" :active-prompt-type="promptType"/>
					</template>
					<template #footer>
						<div class="ai__prompt-master_navigation">
							<div class="ai__prompt-master_prompt-types-step-more">
									<span
										@click="openArticleAboutPromptMaster"
										class="ai__prompt-master_more-details"
									>
										{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_SELECT_TYPE_STEP_MORE') }}
									</span>
							</div>
							<PromptMasterBtn
								@click="currentStepIndex += 1"
								:disabled="isSecondStepEnabled === false"
								:text="$Bitrix.Loc.getMessage('PROMPT_MASTER_BTN_NEXT')"
							/>
						</div>
					</template>
				</PromptMasterStep>
				<PromptMasterStep
					v-show="currentStepIndex === 1 && isPromptEditing"
					:suptitle="promptTypeName"
					:title="promptEditorStepTitle"
					:steps-count="3"
					:step-index="2"
				>
					<template #content>
						<PromptMasterEditorStep
							:is-shown="currentStepIndex === 1 && isPromptEditing"
							:use-clarification="useClarificationInEditor"
							:prompt-text="promptText"
							:max-symbols-count="maxSymbolsCount"
							@input-text="handlePromptTextInput"
						/>
					</template>
					<template #footer>
						<div class="ai__prompt-master_navigation">
							<PromptMasterBackBtn @click="currentStepIndex -= 1"></PromptMasterBackBtn>
							<PromptMasterBtn
								:disabled="promptText.length > maxSymbolsCount || promptText.length < 6"
								@click="currentStepIndex += 1"
								:text="$Bitrix.Loc.getMessage('PROMPT_MASTER_BTN_NEXT')"
							/>
						</div>
					</template>
				</PromptMasterStep>
				<PromptMasterStep
					v-show="currentStepIndex === 2 && isPromptEditing"
					:suptitle="promptTypeName"
					:title="$Bitrix.Loc.getMessage('PROMPT_MASTER_ACCESS_STEP_TITLE')"
					:steps-count="3"
					:step-index="3"
				>
					<template #content>
						<PromptMasterAccessStep
							:is-shown="currentStepIndex === 2 && isPromptEditing"
							:prompt-icon="promptIcon"
							:prompt-title="promptTitle"
							:selected-items="selectedItems"
							:selected-categories="promptCategories"
							:prompt-author-id="authorId"
							@select-icon="handlePromptIconSelect"
							@input-name="handlePromptNameInput"
							@select-item="selectItem"
							@deselect-item="deselectItem"
							@select-category="selectCategory"
							@deselect-category="deselectCategory"
						/>
					</template>
					<template #footer>
						<div class="ai__prompt-master_navigation">
							<PromptMasterBackBtn @click="currentStepIndex -= 1"></PromptMasterBackBtn>
							<PromptMasterBtn
								@click="savePrompt"
								:text="$Bitrix.Loc.getMessage('PROMPT_MASTER_BTN_SAVE')"
								:disabled="isPromptCanBeSave === false"
								:state="saveButtonState"
							/>
						</div>
					</template>
				</PromptMasterStep>
				<div v-show="isPromptSaving" class="ai__prompt-master_loader">
					<div ref="loaderScreen" class="ai__prompt-master_loader-loader"></div>
					<div class="ai__prompt-master_loader-text">
						{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_SAVE_IN_PROCESS') }}
					</div>
				</div>
				<div v-if="isPromptSaved" class="ai__prompt-master_success">
					<div class="ai__prompt-master_success-icon">
						<BIcon :name="checkIcon" size="58" color="#8E52EC"></BIcon>
					</div>
					<div class="ai__prompt-master_success-text">
						{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_SAVE_SUCCESS') }}
					</div>
					<div class="ai__prompt-master_success-close-btn">
						<PromptMasterBtn
							@click="emitCloseMasterEvent"
							:color="closeMasterBtnColor"
							:text="$Bitrix.Loc.getMessage('PROMPT_MASTER_SAVE_CLOSE_BTN')"
						/>
					</div>
				</div>
				<div v-if="isPromptSavedError" class="ai__prompt-master_error">
					<PromptMasterSaveErrorScreen
						@click-repeat-btn="handleRepeatRequestBtnClick"
						@click-back-btn="handleBackBtnClick"
					/>
				</div>
			</transition-group>
		</div>
	`
	};

	let _$2 = t => t,
	  _t$2;
	const PromptMasterEvents = {
	  SAVE_SUCCESS: 'save-success',
	  SAVE_FAILED: 'save-failed',
	  CLOSE_MASTER: 'close-master'
	};
	var _promptOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("promptOptions");
	var _app = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("app");
	var _successPromptSavingEventHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("successPromptSavingEventHandler");
	var _closeBtnClickHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closeBtnClickHandler");
	var _handleSuccessPromptSaving = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleSuccessPromptSaving");
	var _handleClickOnCloseBtn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleClickOnCloseBtn");
	class PromptMaster$1 extends main_core.Event.EventEmitter {
	  constructor(options) {
	    super(options);
	    Object.defineProperty(this, _handleClickOnCloseBtn, {
	      value: _handleClickOnCloseBtn2
	    });
	    Object.defineProperty(this, _handleSuccessPromptSaving, {
	      value: _handleSuccessPromptSaving2
	    });
	    Object.defineProperty(this, _promptOptions, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _app, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _successPromptSavingEventHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _closeBtnClickHandler, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('AI.prompt-master');
	    babelHelpers.classPrivateFieldLooseBase(this, _promptOptions)[_promptOptions] = options || {};
	    babelHelpers.classPrivateFieldLooseBase(this, _successPromptSavingEventHandler)[_successPromptSavingEventHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleSuccessPromptSaving)[_handleSuccessPromptSaving].bind(this);
	    babelHelpers.classPrivateFieldLooseBase(this, _closeBtnClickHandler)[_closeBtnClickHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleClickOnCloseBtn)[_handleClickOnCloseBtn].bind(this);
	  }
	  render() {
	    const container = main_core.Tag.render(_t$2 || (_t$2 = _$2`<div class="ai__prompt-master-container"></div>`));
	    const currentUserId = main_core.Extension.getSettings('ai.prompt-master').get('userId');
	    let authorId = babelHelpers.classPrivateFieldLooseBase(this, _promptOptions)[_promptOptions].authorId;
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _promptOptions)[_promptOptions].code && currentUserId) {
	      authorId = currentUserId;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _app)[_app] = ui_vue3.BitrixVue.createApp(PromptMaster, {
	      authorId,
	      code: babelHelpers.classPrivateFieldLooseBase(this, _promptOptions)[_promptOptions].code,
	      text: babelHelpers.classPrivateFieldLooseBase(this, _promptOptions)[_promptOptions].prompt,
	      type: babelHelpers.classPrivateFieldLooseBase(this, _promptOptions)[_promptOptions].type,
	      title: babelHelpers.classPrivateFieldLooseBase(this, _promptOptions)[_promptOptions].name,
	      accessCodes: babelHelpers.classPrivateFieldLooseBase(this, _promptOptions)[_promptOptions].accessCodes,
	      categories: babelHelpers.classPrivateFieldLooseBase(this, _promptOptions)[_promptOptions].categories,
	      icon: babelHelpers.classPrivateFieldLooseBase(this, _promptOptions)[_promptOptions].icon,
	      analyticCategory: babelHelpers.classPrivateFieldLooseBase(this, _promptOptions)[_promptOptions].analyticCategory
	    });
	    main_core.Event.EventEmitter.subscribe('AI.prompt-master-app:save-success', babelHelpers.classPrivateFieldLooseBase(this, _successPromptSavingEventHandler)[_successPromptSavingEventHandler]);
	    main_core.Event.EventEmitter.subscribe('AI.prompt-master-app:close-master', babelHelpers.classPrivateFieldLooseBase(this, _closeBtnClickHandler)[_closeBtnClickHandler]);
	    babelHelpers.classPrivateFieldLooseBase(this, _app)[_app].mount(container);
	    return container;
	  }
	  destroy() {
	    babelHelpers.classPrivateFieldLooseBase(this, _app)[_app].unmount();
	    main_core.Event.EventEmitter.unsubscribe('AI.prompt-master-app:save-success', babelHelpers.classPrivateFieldLooseBase(this, _successPromptSavingEventHandler)[_successPromptSavingEventHandler]);
	    main_core.Event.EventEmitter.unsubscribe('AI.prompt-master-app:close-master', babelHelpers.classPrivateFieldLooseBase(this, _closeBtnClickHandler)[_closeBtnClickHandler]);
	  }
	}
	function _handleSuccessPromptSaving2(event) {
	  this.emit(PromptMasterEvents.SAVE_SUCCESS, event.getData());
	}
	function _handleClickOnCloseBtn2() {
	  this.emit(PromptMasterEvents.CLOSE_MASTER);
	}

	const PromptMasterPopupEvents = Object.freeze({
	  SAVE_SUCCESS: 'save-success',
	  SAVE_FAILED: 'save-success'
	});
	var _masterOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("masterOptions");
	var _popupEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popupEvents");
	var _analyticFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("analyticFields");
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _successPromptSavingHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("successPromptSavingHandler");
	var _closeMasterHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closeMasterHandler");
	var _isPromptWasSaved = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isPromptWasSaved");
	var _initPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initPopup");
	var _handleSuccessPromptSaving$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleSuccessPromptSaving");
	var _handleCloseMasterEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleCloseMasterEvent");
	var _sendAnalyticOpenLabel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticOpenLabel");
	var _sendAnalyticCancelLabel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendAnalyticCancelLabel");
	class PromptMasterPopup extends main_core.Event.EventEmitter {
	  constructor(options) {
	    super(options);
	    Object.defineProperty(this, _sendAnalyticCancelLabel, {
	      value: _sendAnalyticCancelLabel2
	    });
	    Object.defineProperty(this, _sendAnalyticOpenLabel, {
	      value: _sendAnalyticOpenLabel2
	    });
	    Object.defineProperty(this, _handleCloseMasterEvent, {
	      value: _handleCloseMasterEvent2
	    });
	    Object.defineProperty(this, _handleSuccessPromptSaving$1, {
	      value: _handleSuccessPromptSaving2$1
	    });
	    Object.defineProperty(this, _initPopup, {
	      value: _initPopup2
	    });
	    Object.defineProperty(this, _masterOptions, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _popupEvents, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _analyticFields, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _successPromptSavingHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _closeMasterHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isPromptWasSaved, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('AI.prompt-master-popup');
	    babelHelpers.classPrivateFieldLooseBase(this, _masterOptions)[_masterOptions] = options.masterOptions || {};
	    babelHelpers.classPrivateFieldLooseBase(this, _popupEvents)[_popupEvents] = options.popupEvents || {};
	    babelHelpers.classPrivateFieldLooseBase(this, _analyticFields)[_analyticFields] = options.analyticFields || {};
	    babelHelpers.classPrivateFieldLooseBase(this, _isPromptWasSaved)[_isPromptWasSaved] = false;
	  }
	  show() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = babelHelpers.classPrivateFieldLooseBase(this, _initPopup)[_initPopup]();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	  }
	  hide() {
	    var _babelHelpers$classPr;
	    (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr.close();
	  }
	}
	function _initPopup2() {
	  const masterOptions = {
	    analyticCategory: babelHelpers.classPrivateFieldLooseBase(this, _analyticFields)[_analyticFields].c_section,
	    ...babelHelpers.classPrivateFieldLooseBase(this, _masterOptions)[_masterOptions]
	  };
	  const promptMaster = new PromptMaster$1(masterOptions);
	  babelHelpers.classPrivateFieldLooseBase(this, _successPromptSavingHandler)[_successPromptSavingHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleSuccessPromptSaving$1)[_handleSuccessPromptSaving$1].bind(this);
	  babelHelpers.classPrivateFieldLooseBase(this, _closeMasterHandler)[_closeMasterHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleCloseMasterEvent)[_handleCloseMasterEvent].bind(this);
	  promptMaster.subscribe(PromptMasterEvents.SAVE_SUCCESS, babelHelpers.classPrivateFieldLooseBase(this, _successPromptSavingHandler)[_successPromptSavingHandler]);
	  promptMaster.subscribe(PromptMasterEvents.CLOSE_MASTER, babelHelpers.classPrivateFieldLooseBase(this, _closeMasterHandler)[_closeMasterHandler]);
	  return new main_popup.Popup({
	    id: 'prompt-master-popup',
	    content: promptMaster.render(),
	    width: 360,
	    cacheable: false,
	    closeIcon: true,
	    autoHide: false,
	    closeByEsc: false,
	    className: 'ai__prompt-master-popup',
	    padding: 0,
	    borderRadius: '12px',
	    overlay: true,
	    events: {
	      ...babelHelpers.classPrivateFieldLooseBase(this, _popupEvents)[_popupEvents],
	      onAfterShow: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].setHeight(babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].getPopupContainer().offsetHeight);
	      },
	      onPopupDestroy: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = null;
	        promptMaster.unsubscribe(PromptMasterEvents.SAVE_SUCCESS, babelHelpers.classPrivateFieldLooseBase(this, _successPromptSavingHandler)[_successPromptSavingHandler]);
	        promptMaster.unsubscribe(PromptMasterEvents.CLOSE_MASTER, babelHelpers.classPrivateFieldLooseBase(this, _closeMasterHandler)[_closeMasterHandler]);
	        promptMaster.destroy();
	        if (babelHelpers.classPrivateFieldLooseBase(this, _popupEvents)[_popupEvents].onPopupDestroy) {
	          babelHelpers.classPrivateFieldLooseBase(this, _popupEvents)[_popupEvents].onPopupDestroy();
	        }
	        if (babelHelpers.classPrivateFieldLooseBase(this, _isPromptWasSaved)[_isPromptWasSaved] === false) {
	          babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticCancelLabel)[_sendAnalyticCancelLabel]();
	        }
	      },
	      onPopupShow: () => {
	        if (babelHelpers.classPrivateFieldLooseBase(this, _popupEvents)[_popupEvents].onPopupShow) {
	          babelHelpers.classPrivateFieldLooseBase(this, _popupEvents)[_popupEvents].onPopupShow();
	        }
	        babelHelpers.classPrivateFieldLooseBase(this, _sendAnalyticOpenLabel)[_sendAnalyticOpenLabel]();
	      }
	    }
	  });
	}
	function _handleSuccessPromptSaving2$1(event) {
	  this.emit(PromptMasterPopupEvents.SAVE_SUCCESS, event.getData());
	  babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].setAutoHide(true);
	  babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].setClosingByEsc(true);
	  babelHelpers.classPrivateFieldLooseBase(this, _isPromptWasSaved)[_isPromptWasSaved] = true;
	}
	function _handleCloseMasterEvent2() {
	  this.hide();
	}
	function _sendAnalyticOpenLabel2() {
	  ui_analytics.sendData({
	    tool: 'ai',
	    category: 'prompt_saving',
	    event: 'open',
	    c_section: babelHelpers.classPrivateFieldLooseBase(this, _analyticFields)[_analyticFields].c_section,
	    status: 'success'
	  });
	}
	function _sendAnalyticCancelLabel2() {
	  ui_analytics.sendData({
	    tool: 'ai',
	    category: 'prompt_saving',
	    event: 'cancel',
	    c_section: babelHelpers.classPrivateFieldLooseBase(this, _analyticFields)[_analyticFields].c_section,
	    status: 'success'
	  });
	}

	exports.PromptMasterPopup = PromptMasterPopup;
	exports.PromptMasterPopupEvents = PromptMasterPopupEvents;

}((this.BX.AI = this.BX.AI || {}),BX.Vue3,BX,BX.Vue3.Components,BX.Vue3.Directives,BX.UI,BX,BX,BX.Main,BX,BX.Event,BX.UI.EntitySelector,BX,BX.UI,BX.UI.IconSet,BX.UI.IconSet,BX.UI.Analytics));
//# sourceMappingURL=prompt-master.bundle.js.map
