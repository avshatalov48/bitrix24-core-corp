/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,ui_vue3,crm_integration_analytics,ui_analytics,ui_bbcode_parser,ui_infoHelper,ui_notification,ui_promoVideoPopup,ui_switcher,ui_textEditor,ui_buttons,main_core,main_core_events,ui_entitySelector) {
	'use strict';

	const AdditionalInfoComponent = {
	  props: {
	    title: {
	      type: String,
	      required: true
	    }
	  },
	  mounted() {
	    this.popup = null;
	    this.region = main_core.Extension.getSettings('crm.repeat-sale.segment').get('region');
	    main_core.Event.EventEmitter.subscribe('UI.PromoVideoPopup:accept', event => {
	      var _this$popup;
	      (_this$popup = this.popup) == null ? void 0 : _this$popup.hide();
	    });
	  },
	  methods: {
	    onClick() {
	      if (this.popup === null) {
	        this.popup = new ui_promoVideoPopup.PromoVideoPopup({
	          angleOptions: null,
	          targetOptions: '1',
	          useOverlay: true,
	          videoSrc: this.videoSrc,
	          videoContainerMinHeight: 255,
	          title: main_core.Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_HOW_IT_WORK_TITLE'),
	          colors: {
	            title: 'var(--ui-color-palette-black-base)'
	          }
	        });
	      }
	      this.popup.show();
	    }
	  },
	  computed: {
	    videoSrc() {
	      let name = 'how-it-work-en';
	      if (['kz', 'ru', 'by', 'uz'].includes(this.region)) {
	        name = 'how-it-work-ru';
	      }
	      return `/bitrix/js/crm/repeat-sale/segment/video/${name}.webm`;
	    }
	  },
	  // language=Vue
	  template: `
		<div class="crm-repeat-sale__segment-field-info">
			<span @click="onClick">
				{{title}}
			</span>
		</div>
	`
	};

	const AiSwitcherComponent = {
	  emits: ['change'],
	  components: {
	    AdditionalInfoComponent
	  },
	  props: {
	    checked: {
	      type: Boolean,
	      required: true
	    },
	    readOnly: {
	      type: Boolean,
	      default: false
	    }
	  },
	  mounted() {
	    this.renderSwitcher();
	  },
	  methods: {
	    renderSwitcher() {
	      if (!this.switcher) {
	        this.switcher = new ui_switcher.Switcher({
	          checked: this.checked,
	          disabled: this.readOnly,
	          size: ui_switcher.SwitcherSize.small,
	          showStateTitle: false,
	          handlers: {
	            checked: event => {
	              this.emitChange(false);
	            },
	            unchecked: event => {
	              this.emitChange(true);
	            }
	          }
	        });
	        this.switcher.renderTo(this.$refs.switcher);
	      }
	    },
	    emitChange(value) {
	      this.$emit('change', value);
	    }
	  },
	  computed: {
	    badgeClassList() {
	      return {
	        '--enabled': this.checked
	      };
	    },
	    badgeTitle() {
	      if (this.checked) {
	        return this.$Bitrix.Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_COPILOT_ENABLED');
	      }
	      return this.$Bitrix.Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_COPILOT_DISABLED');
	    }
	  },
	  watch: {
	    checked(newValue) {
	      this.switcher.check(newValue, false);
	    }
	  },
	  // language=Vue
	  template: `
		<div class="crm-repeat-sale__segment-ai-switcher-wrapper">
			<div>
				<div class="crm-repeat-sale__segment-ai-switcher-title">
					{{this.$Bitrix.Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_COPILOT_TITLE')}}
					<span :class="badgeClassList">{{badgeTitle}}</span>
				</div>
				<div class="crm-repeat-sale__segment-ai-switcher-description">
					{{this.$Bitrix.Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_COPILOT_DESCRIPTION')}}
				</div>
			</div>
			<div class="crm-repeat-sale__segment-ai-switcher" ref="switcher"></div>
		</div>
	`
	};

	const TextEditorWrapperComponent = {
	  components: {
	    TextEditorComponent: ui_textEditor.TextEditorComponent,
	    AiSwitcherComponent
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
	      const navigationClassName = '.crm-repeat-sale__segment_navigation-container';
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
	  // language=Vue
	  template: `
		<TextEditorComponent
			:events="textEditorEvents"
			:editor-instance="textEditor"
		/>
	`
	};

	const ButtonEvents = {
	  click: 'crm:repeat-sale:segment:navigation-button-click'
	};
	const Button = {
	  props: {
	    id: {
	      type: String,
	      required: true
	    }
	  },
	  mounted() {
	    this.initButton();
	  },
	  methods: {
	    initButton() {
	      this.button = new ui_buttons.Button({
	        useAirDesign: true,
	        text: main_core.Loc.getMessage(`CRM_REPEAT_SALE_SEGMENT_NAVIGATION_BUTTON_${this.id.toUpperCase()}`),
	        round: true,
	        style: this.buttonStyle,
	        onclick: () => {
	          this.emitClickEvent();
	        }
	      });
	      this.button.setDataSet({
	        id: `crm-repeat-sale-segment-buttons-${this.id.toLowerCase()}`
	      });
	      if (this.$refs.button) {
	        this.button.renderTo(this.$refs.button);
	      }
	    },
	    emitClickEvent() {
	      this.$Bitrix.eventEmitter.emit(ButtonEvents.click, {
	        id: this.id
	      });
	    }
	  },
	  computed: {
	    buttonStyle() {
	      if (this.id === 'update') {
	        return ui_buttons.AirButtonStyle.FILLED;
	      }
	      return ui_buttons.AirButtonStyle.OUTLINE;
	    }
	  },
	  // language=Vue
	  template: `
		<div ref="button" class="crm-repeat-sale__segment_button"></div>
	`
	};

	const CallAssessmentSelector = {
	  props: {
	    callAssessments: {
	      type: Object,
	      required: true
	    },
	    currentCallAssessmentId: {
	      type: Number,
	      required: true
	    },
	    readOnly: {
	      type: Boolean,
	      default: false
	    }
	  },
	  created() {
	    this.dialog = null;
	    this.tabs = [{
	      id: 'call_assessments',
	      title: ''
	    }];
	    this.items = [];
	    this.callAssessments.forEach((callAssessment, index) => {
	      this.items.push({
	        id: callAssessment.id,
	        title: callAssessment.name,
	        entityId: 'call_assessment',
	        tabs: 'call_assessments',
	        selected: callAssessment.id === this.currentCallAssessmentId
	      });
	    });
	  },
	  methods: {
	    toggleDialog() {
	      const dialog = this.getDialog();
	      if (dialog.isOpen()) {
	        dialog.hide();
	      } else {
	        dialog.show();
	      }
	    },
	    getDialog() {
	      if (this.dialog === null) {
	        const targetNode = this.$refs.dialog;
	        const parentPopupContainer = targetNode.closest('body');
	        this.dialog = new ui_entitySelector.Dialog({
	          targetNode,
	          context: 'CRM_REPEAT_SALE_CALL_ASSESSMENT',
	          multiple: false,
	          dropdownMode: true,
	          showAvatars: false,
	          enableSearch: true,
	          width: 450,
	          zIndex: 2500,
	          tabs: this.tabs,
	          items: this.items,
	          searchOptions: {
	            allowCreateItem: false
	          },
	          events: {
	            'Item:onSelect': event => {
	              const {
	                item: {
	                  id
	                }
	              } = event.getData();
	              this.emitSelectItem(id);
	            },
	            onShow: event => {
	              main_core.Event.bindOnce(parentPopupContainer, 'click', this.onPopupContainerClick.bind(this));
	            }
	          }
	        });
	      }
	      return this.dialog;
	    },
	    emitSelectItem(itemId) {
	      this.$emit('onSelectItem', itemId);
	    },
	    onPopupContainerClick() {
	      this.getDialog().hide();
	    }
	  },
	  // language=Vue
	  template: `
		<div
			ref="dialog"
			@click="toggleDialog"
			class="crm-repeat-sale__segment_call-assessment"
		>
			{{ this.$Bitrix.Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_CALL_ASSESSMENT_SELECTOR') }}
		</div>
	`
	};

	const DialogWrapperComponent = {
	  emits: ['change', 'onSelectItem', 'onDeselectItem'],
	  props: {
	    items: {
	      type: Array,
	      default: []
	    },
	    tabs: {
	      type: Array,
	      default: []
	    },
	    entities: {
	      type: Array,
	      default: []
	    },
	    showAvatars: {
	      type: Boolean
	    },
	    multiple: {
	      type: Boolean
	    },
	    context: {
	      type: String,
	      default: ''
	    },
	    events: {
	      type: Object,
	      default: {}
	    },
	    readOnly: {
	      type: Boolean
	    }
	  },
	  methods: {
	    getDialog() {
	      if (!this.dialog) {
	        const targetNode = this.$refs.dialog;
	        const parentPopupContainer = targetNode.closest('body');
	        this.dialog = new ui_entitySelector.Dialog({
	          targetNode,
	          context: this.context,
	          multiple: this.multiple,
	          dropdownMode: true,
	          showAvatars: this.showAvatars,
	          enableSearch: true,
	          width: 450,
	          zIndex: 2500,
	          items: this.items,
	          entities: this.entities,
	          tabs: this.tabs,
	          searchOptions: {
	            allowCreateItem: false
	          },
	          events: {
	            'Item:onSelect': event => {
	              const {
	                item: selectedItem
	              } = event.getData();
	              this.emitSelectItem(selectedItem);
	            },
	            'Item:onDeselect': event => {
	              const {
	                item: deselectedItem
	              } = event.getData();
	              this.emitDeselectItem(deselectedItem);
	            },
	            onShow: event => {
	              main_core.Event.bindOnce(parentPopupContainer, 'click', this.onPopupContainerClick.bind(this));
	            }
	          }
	        });
	      }
	      return this.dialog;
	    },
	    emitSelectItem(selectedItem) {
	      this.$emit('onSelectItem', selectedItem);
	    },
	    emitDeselectItem(deselectedItem) {
	      this.$emit('onDeselectItem', deselectedItem);
	    },
	    selectItem(itemId) {
	      const item = this.dialog.getItem(itemId);
	      item == null ? void 0 : item.select();
	    },
	    onPopupContainerClick() {
	      this.getDialog().hide();
	    },
	    show() {
	      this.getDialog().show();
	    },
	    toggleDialog() {
	      if (this.readOnly) {
	        return;
	      }
	      const dialog = this.getDialog();
	      if (dialog.isOpen()) {
	        dialog.hide();
	      } else {
	        dialog.show();
	      }
	    },
	    destroy() {
	      var _this$dialog;
	      (_this$dialog = this.dialog) == null ? void 0 : _this$dialog.destroy();
	      this.dialog = null;
	    }
	  },
	  computed: {
	    elementTitle() {
	      var _this$items$find$titl, _this$items$find;
	      return (_this$items$find$titl = (_this$items$find = this.items.find(item => item.selected)) == null ? void 0 : _this$items$find.title) != null ? _this$items$find$titl : '';
	    },
	    customData() {
	      var _this$items$find$cust, _this$items$find2;
	      return (_this$items$find$cust = (_this$items$find2 = this.items.find(item => item.selected)) == null ? void 0 : _this$items$find2.customData) != null ? _this$items$find$cust : '';
	    },
	    controlClassList() {
	      return ['ui-ctl', 'ui-ctl-after-icon', {
	        'ui-ctl-dropdown': !this.readOnly
	      }];
	    },
	    iconClassList() {
	      var _this$customData;
	      return ['crm-repeat-sale__segment-dialog-field-icon', {
	        '--color': Boolean((_this$customData = this.customData) == null ? void 0 : _this$customData.color)
	      }];
	    },
	    iconStyleList() {
	      var _this$customData2;
	      if (((_this$customData2 = this.customData) == null ? void 0 : _this$customData2.color) === null) {
	        return {};
	      }
	      return {
	        backgroundColor: this.customData.color
	      };
	    }
	  },
	  // language=Vue
	  template: `
		<div
			:class="controlClassList"
			ref="dialog"
			@click="toggleDialog"
		>
    		<div 
				v-if="!readOnly"
				class="ui-ctl-after ui-ctl-icon-angle"
			></div>
			<div class="ui-ctl-element">
				<span class="crm-repeat-sale__segment-dialog-field">
					<span 
						:class="iconClassList"
						:style="iconStyleList"
					></span>
					<span class="crm-repeat-sale__segment-dialog-field-value">
						{{elementTitle}}
					</span>
				</span>
			</div>
		</div>
	`
	};

	const TagSelectorWrapperComponent = {
	  emits: ['change', 'onSelectItem', 'onDeselectItem'],
	  props: {
	    items: {
	      type: Array,
	      default: []
	    },
	    preselectedItems: {
	      type: Array,
	      default: []
	    },
	    tabs: {
	      type: Array,
	      default: []
	    },
	    entities: {
	      type: Array,
	      default: []
	    },
	    showAvatars: {
	      type: Boolean,
	      default: false
	    },
	    multiple: {
	      type: Boolean,
	      default: false
	    },
	    context: {
	      type: String,
	      default: ''
	    },
	    events: {
	      type: Object,
	      default: {}
	    },
	    readOnly: {
	      type: Boolean,
	      default: false
	    }
	  },
	  mounted() {
	    this.initDialog();
	  },
	  watch: {
	    items(items) {
	      this.tagSelector.getDialog().destroy();
	      main_core.Dom.clean(this.$refs.dialog);
	      this.initDialog();
	    }
	  },
	  methods: {
	    initDialog() {
	      const targetNode = this.$refs.dialog;
	      const parentPopupContainer = targetNode.closest('body');
	      this.tagSelector = new ui_entitySelector.TagSelector({
	        multiple: this.multiple,
	        readonly: this.readOnly,
	        hideOnSelect: false,
	        dialogOptions: {
	          targetNode,
	          context: this.context,
	          multiple: this.multiple,
	          hideOnSelect: false,
	          dropdownMode: true,
	          showAvatars: this.showAvatars,
	          enableSearch: true,
	          width: 450,
	          zIndex: 2500,
	          items: this.items,
	          preselectedItems: this.preselectedItems,
	          entities: this.entities,
	          tabs: this.tabs,
	          searchOptions: {
	            allowCreateItem: false
	          },
	          events: {
	            'Item:onSelect': event => {
	              const {
	                item: selectedItem
	              } = event.getData();
	              this.emitSelectItem(selectedItem);
	            },
	            'Item:onDeselect': event => {
	              const {
	                item: deselectedItem
	              } = event.getData();
	              this.emitDeselectItem(deselectedItem);
	            }
	          }
	        }
	      });
	      this.tagSelector.renderTo(this.$refs.dialog);
	    },
	    emitSelectItem(selectedItem) {
	      this.$emit('onSelectItem', selectedItem);
	    },
	    emitDeselectItem(deselectedItem) {
	      this.$emit('onDeselectItem', deselectedItem);
	    },
	    selectItem(itemId) {
	      const item = this.tagSelector.getDialog().getItem(itemId);
	      item == null ? void 0 : item.select();
	    }
	  },
	  // language=Vue
	  template: `
		<div ref="dialog"></div>
	`
	};

	const CategorySelector = {
	  components: {
	    TagSelectorWrapperComponent,
	    DialogWrapperComponent
	  },
	  props: {
	    categories: {
	      type: Object,
	      required: true
	    },
	    currentCategoryId: {
	      type: Number,
	      required: true
	    },
	    readOnly: {
	      type: Boolean,
	      default: false
	    }
	  },
	  data() {
	    const items = [];
	    this.categories.forEach((category, index) => {
	      items.push({
	        id: category.id,
	        title: category.name,
	        entityId: 'category',
	        tabs: 'categories',
	        selected: category.id === this.currentCategoryId
	      });
	    });
	    return {
	      items
	    };
	  },
	  created() {
	    this.tabs = [{
	      id: 'categories',
	      title: ''
	    }];
	  },
	  watch: {
	    currentCategoryId(categoryId) {
	      this.items.forEach(item => {
	        // eslint-disable-next-line no-param-reassign
	        item.selected = item.id === categoryId;
	      });
	    }
	  },
	  // language=Vue
	  template: `
		<DialogWrapperComponent
			:items="items"
			:tabs="tabs"
			:read-only="readOnly"
		/>
	`
	};

	const StageSelector = {
	  components: {
	    DialogWrapperComponent
	  },
	  props: {
	    category: {
	      type: Object
	    },
	    currentStageId: {
	      type: String,
	      required: true
	    },
	    readOnly: {
	      type: Boolean,
	      default: false
	    }
	  },
	  data() {
	    return {
	      items: this.getPreparedItems()
	    };
	  },
	  created() {
	    this.tabs = [{
	      id: 'stages',
	      title: ''
	    }];
	  },
	  methods: {
	    destroy() {
	      this.$refs.dialog.destroy();
	    },
	    getPreparedItems(setSelectedFirst = false) {
	      const items = [];
	      if (this.category === null) {
	        return items;
	      }
	      let hasSelected = true;
	      Object.values(this.category.items).forEach(item => {
	        items.push({
	          id: item.id,
	          title: item.name,
	          entityId: 'stage',
	          tabs: 'stages',
	          selected: setSelectedFirst ? hasSelected : item.id === this.currentStageId,
	          avatarOptions: {
	            bgColor: item.color,
	            borderRadius: 6,
	            bgSize: 20,
	            bgImage: 'none'
	          },
	          customData: {
	            color: item.color
	          }
	        });
	        hasSelected = false;
	      });
	      if (!items.some(item => item.selected === true)) {
	        items.find(Boolean).selected = true;
	      }
	      return items;
	    }
	  },
	  watch: {
	    category(category) {
	      this.items = this.getPreparedItems(true);
	    },
	    currentStageId(categoryId) {
	      this.items.forEach(item => {
	        // eslint-disable-next-line no-param-reassign
	        item.selected = item.id === categoryId;
	      });
	    }
	  },
	  // language=Vue
	  template: `
		<DialogWrapperComponent
			ref="dialog"
			:items="items"
			:tabs="tabs"
			:showAvatars="true"
			:read-only="readOnly"
		/>
	`
	};

	const UserSelector = {
	  components: {
	    TagSelectorWrapperComponent
	  },
	  props: {
	    userIds: {
	      type: Array,
	      required: true
	    },
	    readOnly: {
	      type: Boolean,
	      default: false
	    }
	  },
	  data() {
	    return {
	      preselectedItems: this.userIds.map(userId => {
	        return ['user', userId];
	      })
	    };
	  },
	  created() {
	    this.entities = [{
	      id: 'user',
	      options: {
	        inviteEmployeeLink: false,
	        emailUsers: false,
	        inviteGuestLink: false,
	        intranetUsersOnly: true
	      }
	    }, {
	      id: 'structure-node',
	      options: {
	        selectMode: 'usersOnly'
	      }
	    }];
	  },
	  // language=Vue
	  template: `
		<TagSelectorWrapperComponent 
			:entities="entities"
			:preselected-items="preselectedItems"
			:show-avatars="true"
			:multiple="true"
			:read-only="readOnly"
		/>
	`
	};

	const Segment = {
	  components: {
	    Button,
	    AdditionalInfoComponent,
	    AiSwitcherComponent,
	    TextEditorWrapperComponent,
	    CallAssessmentSelector,
	    CategorySelector,
	    StageSelector,
	    UserSelector
	  },
	  props: {
	    settings: {
	      type: Object,
	      default: {}
	    },
	    segment: {
	      type: Object,
	      required: true
	    },
	    categories: {
	      type: Object,
	      required: true
	    },
	    callAssessments: {
	      type: Object,
	      required: true
	    },
	    events: {
	      type: Object,
	      default: {}
	    },
	    analytics: {
	      type: Object,
	      default: {}
	    },
	    textEditor: ui_textEditor.TextEditor
	  },
	  data() {
	    var _segment$id, _segment$isEnabled, _settings$ai, _settings$ai2, _settings$baas, _segment$entityCatego, _segment$entityStageI, _segment$assignmentUs, _segment$entityTitleP, _segment$callAssessme;
	    const {
	      segment,
	      textEditor,
	      categories,
	      settings
	    } = this;
	    const id = (_segment$id = segment == null ? void 0 : segment.id) != null ? _segment$id : null;
	    const isEnabled = (_segment$isEnabled = segment == null ? void 0 : segment.isEnabled) != null ? _segment$isEnabled : null;
	    const firstCategory = categories[0];
	    let isAiEnabled = false;
	    if ((_settings$ai = settings.ai) != null && _settings$ai.isAvailable && ((_settings$ai2 = settings.ai) != null && _settings$ai2.isSponsored || (_settings$baas = settings.baas) != null && _settings$baas.hasPackage)) {
	      var _segment$isAiEnabled;
	      isAiEnabled = (_segment$isAiEnabled = segment.isAiEnabled) != null ? _segment$isAiEnabled : true;
	    }
	    return {
	      id,
	      isEnabled,
	      text: textEditor.getText(),
	      parser: new ui_bbcode_parser.BBCodeParser(),
	      currentCategoryId: (_segment$entityCatego = segment.entityCategoryId) != null ? _segment$entityCatego : firstCategory.id,
	      currentStageId: (_segment$entityStageI = segment.entityStageId) != null ? _segment$entityStageI : this.getFirstAvailableCategoryStageId(firstCategory),
	      assignmentUserIds: new Set((_segment$assignmentUs = segment.assignmentUserIds) != null ? _segment$assignmentUs : []),
	      currentEntityTitlePattern: (_segment$entityTitleP = segment.entityTitlePattern) != null ? _segment$entityTitleP : null,
	      currentCallAssessmentId: (_segment$callAssessme = segment.callAssessmentId) != null ? _segment$callAssessme : null,
	      currentIsAiEnabled: isAiEnabled
	    };
	  },
	  mounted() {
	    this.$Bitrix.eventEmitter.subscribe(ButtonEvents.click, this.onNavigationButtonClick);
	    this.sendViewAnalytics();
	  },
	  beforeUnmount() {
	    this.$Bitrix.eventEmitter.unsubscribe(ButtonEvents.click, this.onNavigationButtonClick);
	  },
	  methods: {
	    onSaveCallback() {
	      var _this$events;
	      if (main_core.Type.isFunction((_this$events = this.events) == null ? void 0 : _this$events.onSave)) {
	        this.events.onSave();
	      }
	    },
	    onNavigationButtonClick({
	      data
	    }) {
	      const {
	        id
	      } = data;
	      if (id === 'cancel' || id === 'close') {
	        this.sendCancelAnalytics();
	        this.closeSlider();
	        return;
	      }
	      this.sendData();
	    },
	    sendData() {
	      const data = {
	        entityTypeId: 2,
	        // temporary only deal
	        entityCategoryId: this.currentCategoryId,
	        entityStageId: this.currentStageId,
	        assignmentUserIds: [...this.assignmentUserIds.values()],
	        entityTitlePattern: this.currentEntityTitlePattern,
	        callAssessmentId: this.currentCallAssessmentId,
	        isAiEnabled: this.currentIsAiEnabled
	      };
	      if (!this.currentIsAiEnabled) {
	        data.prompt = this.textEditor.getText();
	      }
	      if (!this.validate(data)) {
	        return;
	      }
	      const dataParams = {
	        id: this.id,
	        data
	      };
	      top.BX.Event.EventEmitter.emit('crm:repeatSale:segment:beforeSave', dataParams);
	      main_core.ajax.runAction('crm.repeatsale.segment.save', {
	        json: dataParams
	      }).then(response => {
	        top.BX.Event.EventEmitter.emit('crm:repeatSale.segment:save', {
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
	        this.sendEditAnalytics();
	        this.closeSlider();
	      }, response => {
	        const messageCode = 'CRM_REPEAT_SALE_SEGMENT_SAVE_ERROR';
	        ui_notification.UI.Notification.Center.notify({
	          content: this.$Bitrix.Loc.getMessage(messageCode),
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
	    validate(data) {
	      if (!main_core.Type.isArrayFilled(data.assignmentUserIds)) {
	        ui_notification.UI.Notification.Center.notify({
	          content: this.$Bitrix.Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_VALIDATE_ASSIGNMENT_USERS_ERROR'),
	          autoHideDelay: 6000
	        });
	        return false;
	      }
	      if (!this.currentIsAiEnabled && !main_core.Type.isStringFilled(this.getPlainText())) {
	        ui_notification.UI.Notification.Center.notify({
	          content: this.$Bitrix.Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_VALIDATE_TEXT_ERROR'),
	          autoHideDelay: 6000
	        });
	        return false;
	      }
	      return true;
	    },
	    closeSlider() {
	      top.BX.SidePanel.Instance.getSliderByWindow(window).close();
	    },
	    getPlainText() {
	      return this.parser.parse(this.textEditor.getText()).toPlainText().trim();
	    },
	    onSelectCategory(category) {
	      if (this.currentCategoryId === category.id) {
	        return;
	      }
	      this.$refs.stageSelector.destroy();
	      this.currentCategoryId = category.id;
	      void this.$nextTick(() => {
	        const currentCategory = this.getCategoryById(this.currentCategoryId);
	        this.currentStageId = this.getFirstAvailableCategoryStageId(currentCategory);
	      });
	    },
	    onSelectStage(stage) {
	      this.currentStageId = stage.id;
	    },
	    getCategoryById(id) {
	      return this.categories.find(category => category.id === id);
	    },
	    getFirstAvailableCategoryStageId(category) {
	      return category.items[0].id;
	    },
	    onSelectAssignmentUser(user) {
	      this.assignmentUserIds.add(user.id);
	    },
	    onDeselectAssignmentUser(user) {
	      this.assignmentUserIds.delete(user.id);
	    },
	    setCurrentCallAssessmentId(id) {
	      this.currentCallAssessmentId = id;
	    },
	    getMessageByCode(code) {
	      return this.$Bitrix.Loc.getMessage(code);
	    },
	    setCurrentIsAiEnabled(value) {
	      this.currentIsAiEnabled = value;
	    },
	    sendViewAnalytics() {
	      var _this$analytics$secti;
	      const section = (_this$analytics$secti = this.analytics.section) != null ? _this$analytics$secti : '';
	      const viewEvent = crm_integration_analytics.Builder.RepeatSale.Segment.ViewEvent.createDefault(section);
	      ui_analytics.sendData(viewEvent.buildData());
	    },
	    sendCancelAnalytics() {
	      var _this$analytics$secti2;
	      const section = (_this$analytics$secti2 = this.analytics.section) != null ? _this$analytics$secti2 : '';
	      const viewEvent = crm_integration_analytics.Builder.RepeatSale.Segment.CancelEvent.createDefault(section);
	      ui_analytics.sendData(viewEvent.buildData());
	    },
	    sendEditAnalytics() {
	      var _this$analytics$secti3;
	      const section = (_this$analytics$secti3 = this.analytics.section) != null ? _this$analytics$secti3 : '';
	      const editEvent = crm_integration_analytics.Builder.RepeatSale.Segment.EditEvent.createDefault(section);
	      if (!this.currentIsAiEnabled && this.getPlainPromptText() !== this.getPlainSegmentText()) {
	        editEvent.setIsActivityTextChanged(true);
	      }
	      if (this.segment.entityTitlePattern !== this.currentEntityTitlePattern) {
	        editEvent.setIsEntityTitlePatternChanged(true);
	      }
	      if (this.segment.isAiEnabled !== this.currentIsAiEnabled) {
	        editEvent.setIsCopilotEnabled(this.currentIsAiEnabled);
	      }
	      editEvent.setSegmentCode(this.segment.code);
	      ui_analytics.sendData(editEvent.buildData());
	    },
	    getPlainPromptText() {
	      return this.parseText(this.textEditor.getText());
	    },
	    getPlainSegmentText() {
	      return this.parseText(this.segment.prompt);
	    },
	    parseText(text) {
	      return this.parser.parse(text).toPlainText().trim();
	    }
	  },
	  computed: {
	    readOnly() {
	      return this.settings.isReadOnly;
	    },
	    isSponsored() {
	      var _this$settings$ai$isS, _this$settings$ai;
	      return (_this$settings$ai$isS = (_this$settings$ai = this.settings.ai) == null ? void 0 : _this$settings$ai.isSponsored) != null ? _this$settings$ai$isS : false;
	    },
	    isAiAvailable() {
	      var _this$settings$ai$isA, _this$settings$ai2;
	      return (_this$settings$ai$isA = (_this$settings$ai2 = this.settings.ai) == null ? void 0 : _this$settings$ai2.isAvailable) != null ? _this$settings$ai$isA : false;
	    },
	    aiDisabledSliderCode() {
	      var _this$settings$ai$aiD, _this$settings$ai3;
	      return (_this$settings$ai$aiD = (_this$settings$ai3 = this.settings.ai) == null ? void 0 : _this$settings$ai3.aiDisabledSliderCode) != null ? _this$settings$ai$aiD : null;
	    },
	    isBaasAvailable() {
	      var _this$settings$baas$i, _this$settings$baas;
	      return (_this$settings$baas$i = (_this$settings$baas = this.settings.baas) == null ? void 0 : _this$settings$baas.isAvailable) != null ? _this$settings$baas$i : false;
	    },
	    isBaasHasPackage() {
	      var _this$settings$baas$h, _this$settings$baas2;
	      return (_this$settings$baas$h = (_this$settings$baas2 = this.settings.baas) == null ? void 0 : _this$settings$baas2.hasPackage) != null ? _this$settings$baas$h : false;
	    },
	    packageEmptySliderCode() {
	      var _this$settings$baas$a, _this$settings$baas3;
	      return (_this$settings$baas$a = (_this$settings$baas3 = this.settings.baas) == null ? void 0 : _this$settings$baas3.aiPackagesEmptySliderCode) != null ? _this$settings$baas$a : null;
	    },
	    aiCallEnabled() {
	      return this.settings.isAiCallEnabled;
	    },
	    repeatSaleSegmentSection() {
	      return ['crm-repeat-sale__segment-section'];
	    },
	    title() {
	      const code = this.readOnly ? 'CRM_REPEAT_SALE_SEGMENT_TITLE_READ_ONLY' : 'CRM_REPEAT_SALE_SEGMENT_TITLE';
	      return this.$Bitrix.Loc.getMessage(code);
	    },
	    currentCategory() {
	      var _this$categories$find;
	      return (_this$categories$find = this.categories.find(category => category.id === this.currentCategoryId)) != null ? _this$categories$find : null;
	    },
	    messages() {
	      return {
	        textAreaTitle: this.getMessageByCode('CRM_REPEAT_SALE_SEGMENT_MANUAL_TEXTAREA_TITLE'),
	        dealHelp: this.getMessageByCode('CRM_REPEAT_SALE_SEGMENT_DEAL_HELP'),
	        sectionTitle: this.getMessageByCode('CRM_REPEAT_SALE_SEGMENT_MANUAL_SECTION_TITLE'),
	        stageTitle: this.getMessageByCode('CRM_REPEAT_SALE_SEGMENT_MANUAL_STAGE_TITLE'),
	        dealAssignedTitle: this.getMessageByCode('CRM_REPEAT_SALE_SEGMENT_DEAL_ASSIGNED_TITLE'),
	        dealTitlePattern: this.getMessageByCode('CRM_REPEAT_SALE_SEGMENT_DEAL_NAME_PATTERN_TITLE'),
	        assessmentTitle: this.getMessageByCode('CRM_REPEAT_SALE_SEGMENT_CALL_ASSESSMENT_TITLE'),
	        assessmentDescription: this.getMessageByCode('CRM_REPEAT_SALE_SEGMENT_CALL_ASSESSMENT_DESCRIPTION')
	      };
	    }
	  },
	  watch: {
	    currentIsAiEnabled(value) {
	      if (this.isAiAvailable && (this.isSponsored || this.isBaasHasPackage)) {
	        this.currentIsAiEnabled = value;
	        return;
	      }
	      if (value === true) {
	        if (!this.isAiAvailable && this.aiDisabledSliderCode) {
	          ui_infoHelper.InfoHelper.show(this.aiDisabledSliderCode);
	        } else if (!this.isBaasHasPackage && this.packageEmptySliderCode) {
	          ui_infoHelper.InfoHelper.show(this.packageEmptySliderCode);
	        }
	        void this.$nextTick(() => {
	          this.currentIsAiEnabled = false;
	        });
	      }
	    }
	  },
	  // language=Vue
	  template: `
		<div class="crm-repeat-sale__segment_container">
			<div class="crm-repeat-sale__segment-wrapper">
				<header class="crm-repeat-sale__segment-section-header">
					<div class="crm-repeat-sale__segment-section-header-title">
						<span>{{title}}</span>
					</div>
				</header>
				<div class="crm-repeat-sale__segment-section-body">
					<section class="crm-repeat-sale__segment-section --main --active">
						<h1 class="crm-repeat-sale__segment-section-title">
							{{segment.title}}
						</h1>
						<div class="crm-repeat-sale__segment-section-description">
							{{segment.description}}
						</div>
						<AdditionalInfoComponent
							:title="messages.dealHelp"
						/>
					</section>
					
					
					<section class="crm-repeat-sale__segment-section --active">
						<div
							class="crm-repeat-sale__segment-fields-row"
							v-if="isBaasAvailable || isSponsored"
						>
							<div class="crm-repeat-sale__segment-field">
								<AiSwitcherComponent
									ref="aiSwitcher"
									:checked="currentIsAiEnabled"
									:read-only="readOnly"
									@change="setCurrentIsAiEnabled"
								/>
							</div>
						</div>
						
						<div
							v-if="!currentIsAiEnabled"
							class="crm-repeat-sale__segment-fields-row"
						>
							<div class="crm-repeat-sale__segment-field">
								<div class="crm-repeat-sale__segment-field-title">
									{{messages.textAreaTitle}}
								</div>
								<TextEditorWrapperComponent
									:textEditor="textEditor"
								/>
							</div>
						</div>
						
						<div class="crm-repeat-sale__segment-fields-row">
							<div class="crm-repeat-sale__segment-field">
								<div class="crm-repeat-sale__segment-field-title">
									{{messages.sectionTitle}}
								</div>
								<CategorySelector 
									:current-category-id="currentCategoryId"
									:categories="categories"
									:read-only="readOnly"
									@onSelectItem="onSelectCategory"
								/>
							</div>
							<div class="crm-repeat-sale__segment-field">
								<div class="crm-repeat-sale__segment-field-title">
									{{messages.stageTitle}}
								</div>
								<StageSelector 
									ref="stageSelector"
									:current-stage-id="currentStageId"
									:category="currentCategory"
									:read-only="readOnly"
									@onSelectItem="onSelectStage"
								/>
							</div>
						</div>

						<div class="crm-repeat-sale__segment-fields-row">
							<div class="crm-repeat-sale__segment-field">
								<div class="crm-repeat-sale__segment-field-title">
									{{messages.dealAssignedTitle}}
								</div>
								<UserSelector
									:user-ids="[...assignmentUserIds.values()]"
									:read-only="readOnly"
									@onSelectItem="onSelectAssignmentUser"
									@onDeselectItem="onDeselectAssignmentUser"
								/>
							</div>
						</div>
						
						<div class="crm-repeat-sale__segment-fields-row">
							<div class="crm-repeat-sale__segment-field">
								<div class="crm-repeat-sale__segment-field-title">
									{{messages.dealTitlePattern}}
								</div>
								<input
									class="ui-ctl-element"
									type="text"
									v-model="currentEntityTitlePattern"
									:readonly="readOnly"
								>
							</div>
						</div>
					</section>

					<section 
						:class="repeatSaleSegmentSection"
						v-if="aiCallEnabled && false"
					>
						<h1 class="crm-repeat-sale__segment-section-title --level2">
							{{messages.assessmentTitle}}
						</h1>
						<div class="crm-repeat-sale__segment-section-description">
							{{messages.assessmentDescription}}
						</div>

						<div class="crm-repeat-sale__segment-field">
							<CallAssessmentSelector 
								:call-assessments="callAssessments"
								:current-call-assessment-id="currentCallAssessmentId"
								:read-only="readOnly"
								@onSelectItem="setCurrentCallAssessmentId"
							/>
							{{currentCallAssessmentId}}
						</div>
					</section>
				</div>
			</div>
			<div class="crm-repeat-sale__segment_navigation-container">
				<div class="crm-repeat-sale__segment_navigation-buttons-wrapper">
					<Button v-if="!readOnly" id="update" />
					<Button v-if="!readOnly" id="cancel" />
					<Button v-if="readOnly" id="close" />
				</div>
			</div>
		</div>
	`
	};

	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _app = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("app");
	var _textEditor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("textEditor");
	var _isReadOnly = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isReadOnly");
	var _getTextEditor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTextEditor");
	class Segment$1 {
	  constructor(containerId, params = {}) {
	    var _params$config$readOn, _params$config, _params$config2, _params$config$title, _params$config3, _params$config$isCopy, _params$config4;
	    Object.defineProperty(this, _getTextEditor, {
	      value: _getTextEditor2
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _app, {
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
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = document.getElementById(containerId);
	    if (!main_core.Type.isDomNode(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container])) {
	      throw new Error('container not found');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _isReadOnly)[_isReadOnly] = (_params$config$readOn = (_params$config = params.config) == null ? void 0 : _params$config.readOnly) != null ? _params$config$readOn : true;
	    babelHelpers.classPrivateFieldLooseBase(this, _app)[_app] = ui_vue3.BitrixVue.createApp(Segment, {
	      textEditor: babelHelpers.classPrivateFieldLooseBase(this, _getTextEditor)[_getTextEditor](params.data.segment, (_params$config2 = params.config) != null ? _params$config2 : {}),
	      title: (_params$config$title = (_params$config3 = params.config) == null ? void 0 : _params$config3.title) != null ? _params$config$title : '',
	      settings: {
	        isReadOnly: babelHelpers.classPrivateFieldLooseBase(this, _isReadOnly)[_isReadOnly],
	        isCopy: (_params$config$isCopy = (_params$config4 = params.config) == null ? void 0 : _params$config4.isCopy) != null ? _params$config$isCopy : false,
	        ai: params.data.aiSettings,
	        baas: params.data.baasSettings,
	        isAiCallEnabled: params.data.isAiCallEnabled
	      },
	      segment: params.data.segment,
	      categories: params.data.categories,
	      callAssessments: params.data.callAssessments,
	      events: params.events,
	      analytics: params.analytics
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _app)[_app].mount(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]);
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
	  const floatingToolbar = babelHelpers.classPrivateFieldLooseBase(this, _isReadOnly)[_isReadOnly] ? [] : ['bold', 'italic', 'underline', 'strikethrough', '|', 'numbered-list', 'bulleted-list', 'copilot'];
	  babelHelpers.classPrivateFieldLooseBase(this, _textEditor)[_textEditor] = new ui_textEditor.BasicEditor({
	    editable: !babelHelpers.classPrivateFieldLooseBase(this, _isReadOnly)[_isReadOnly],
	    removePlugins: ['BlockToolbar'],
	    minHeight: 250,
	    maxHeight: 400,
	    content,
	    placeholder: main_core.Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_PLACEHOLDER'),
	    paragraphPlaceholder: main_core.Loc.getMessage(main_core.Type.isPlainObject(copilotSettings) ? 'CRM_REPEAT_SALE_SEGMENT_PLACEHOLDER_WITH_COPILOT' : null),
	    toolbar: [],
	    floatingToolbar,
	    collapsingMode: false,
	    copilot: {
	      copilotOptions: main_core.Type.isPlainObject(copilotSettings) ? copilotSettings : null,
	      triggerBySpace: true
	    }
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _textEditor)[_textEditor];
	}

	exports.Segment = Segment$1;

}((this.BX.Crm.RepeatSale = this.BX.Crm.RepeatSale || {}),BX.Vue3,BX.Crm.Integration.Analytics,BX.UI.Analytics,BX.UI.BBCode,BX.UI,BX,BX.UI,BX.UI,BX.UI.TextEditor,BX.UI,BX,BX.Event,BX.UI.EntitySelector));
//# sourceMappingURL=segment.bundle.js.map
