this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,ui_cnt,ui_label,main_popup,main_core_events,crm_timeline_item,ui_vue3_components_audioplayer,ui_buttons,currency_currencyCore,ui_vue3,ui_alerts,main_date,crm_router,ui_dialogs_messagebox,crm_timeline_tools,ui_notification,main_core) {
	'use strict';

	let Item = /*#__PURE__*/function () {
	  function Item() {
	    babelHelpers.classCallCheck(this, Item);
	    this._id = '';
	    this._isTerminated = false;
	    this._wrapper = null;
	  }

	  babelHelpers.createClass(Item, [{
	    key: "getId",
	    value: function getId() {
	      return this._id;
	    }
	  }, {
	    key: "_setId",
	    value: function _setId(id) {
	      this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
	    }
	    /**
	     * @abstract
	     */

	  }, {
	    key: "setData",
	    value: function setData(data) {
	      throw new Error('Item.setData() must be overridden');
	    }
	    /**
	     * @abstract
	     */

	  }, {
	    key: "layout",
	    value: function layout(options) {
	      throw new Error('Item.layout() must be overridden');
	    }
	  }, {
	    key: "refreshLayout",
	    value: function refreshLayout() {
	      const anchor = this._wrapper.previousSibling;
	      this.clearLayout();
	      this.layout({
	        anchor: anchor
	      });
	    }
	  }, {
	    key: "clearLayout",
	    value: function clearLayout() {
	      main_core.Dom.remove(this._wrapper);
	      this._wrapper = undefined;
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      this.clearLayout();
	    }
	  }, {
	    key: "getWrapper",
	    value: function getWrapper() {
	      return this._wrapper;
	    }
	  }, {
	    key: "setWrapper",
	    value: function setWrapper(wrapper) {
	      this._wrapper = wrapper;
	    }
	  }, {
	    key: "addWrapperClass",
	    value: function addWrapperClass(className, timeout) {
	      if (!this._wrapper) {
	        return;
	      }

	      main_core.Dom.addClass(this._wrapper, className);

	      if (main_core.Type.isNumber(timeout) && timeout >= 0) {
	        window.setTimeout(this.removeWrapperClass.bind(this, className), timeout);
	      }
	    }
	  }, {
	    key: "removeWrapperClass",
	    value: function removeWrapperClass(className, timeout) {
	      if (!this._wrapper) {
	        return;
	      }

	      main_core.Dom.removeClass(this._wrapper, className);

	      if (main_core.Type.isNumber(timeout) && timeout >= 0) {
	        window.setTimeout(this.addWrapperClass.bind(this, className), timeout);
	      }
	    }
	  }, {
	    key: "isTerminated",
	    value: function isTerminated() {
	      return this._isTerminated;
	    }
	  }, {
	    key: "markAsTerminated",
	    value: function markAsTerminated(terminated) {
	      terminated = !!terminated;

	      if (this._isTerminated === terminated) {
	        return;
	      }

	      this._isTerminated = terminated;

	      if (!this._wrapper) {
	        return;
	      }

	      if (terminated) {
	        main_core.Dom.addClass(this._wrapper, 'crm-entity-stream-section-last');
	      } else {
	        main_core.Dom.removeClass(this._wrapper, 'crm-entity-stream-section-last');
	      }
	    }
	  }, {
	    key: "getAssociatedEntityTypeId",
	    value: function getAssociatedEntityTypeId() {
	      return null;
	    }
	  }, {
	    key: "getAssociatedEntityId",
	    value: function getAssociatedEntityId() {
	      return null;
	    }
	  }]);
	  return Item;
	}();

	let IconBackgroundColor = function IconBackgroundColor() {
	  babelHelpers.classCallCheck(this, IconBackgroundColor);
	};
	babelHelpers.defineProperty(IconBackgroundColor, "PRIMARY", 'primary');
	babelHelpers.defineProperty(IconBackgroundColor, "PRIMARY_ALT", 'primary_alt');
	babelHelpers.defineProperty(IconBackgroundColor, "FAILURE", 'failure');

	const Icon = {
	  props: {
	    code: {
	      type: String,
	      required: false,
	      default: 'none'
	    },
	    counterType: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    backgroundColorToken: {
	      type: String,
	      required: false,
	      default: IconBackgroundColor.PRIMARY
	    }
	  },
	  inject: ['isLogMessage'],
	  computed: {
	    className() {
	      return {
	        'crm-timeline__card_icon': true,
	        [`--bg-${this.backgroundColorToken}`]: !!this.backgroundColorToken,
	        [`--code-${this.code}`]: !!this.code,
	        ['--muted']: this.isLogMessage
	      };
	    },

	    counterNodeContainer() {
	      return this.$refs.counter;
	    }

	  },
	  methods: {
	    renderCounter() {
	      if (!this.counterType) {
	        return;
	      }

	      const counter = new ui_cnt.Counter({
	        value: 1,
	        border: true,
	        color: ui_cnt.Counter.Color[this.counterType.toUpperCase()]
	      });
	      counter.renderTo(this.counterNodeContainer);
	    }

	  },

	  mounted() {
	    this.renderCounter();
	  },

	  template: `
		<div :class="className">
			<i></i>
			<div ref="counter" v-show="!!counterType" class="crm-timeline__card_icon_counter"></div>
		</div>
	`
	};

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }

	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	var _menuOptions = /*#__PURE__*/new WeakMap();

	var _vueComponent = /*#__PURE__*/new WeakMap();

	let Menu = /*#__PURE__*/function () {
	  function Menu(vueComponent, menuItems, menuOptions) {
	    babelHelpers.classCallCheck(this, Menu);

	    _classPrivateFieldInitSpec(this, _menuOptions, {
	      writable: true,
	      value: {}
	    });

	    _classPrivateFieldInitSpec(this, _vueComponent, {
	      writable: true,
	      value: {}
	    });

	    babelHelpers.classPrivateFieldSet(this, _vueComponent, vueComponent);
	    babelHelpers.classPrivateFieldSet(this, _menuOptions, menuOptions || {});
	    babelHelpers.classPrivateFieldSet(this, _menuOptions, {
	      angle: false,
	      cacheable: false,
	      ...babelHelpers.classPrivateFieldGet(this, _menuOptions)
	    });
	    babelHelpers.classPrivateFieldGet(this, _menuOptions).items = [];

	    for (const item of menuItems) {
	      babelHelpers.classPrivateFieldGet(this, _menuOptions).items.push(this.createMenuItem(item));
	    }
	  }

	  babelHelpers.createClass(Menu, [{
	    key: "show",
	    value: function show() {
	      main_popup.MenuManager.show(babelHelpers.classPrivateFieldGet(this, _menuOptions));
	    }
	  }, {
	    key: "createMenuItem",
	    value: function createMenuItem(item) {
	      if (item.hasOwnProperty('delimiter') && item.delimiter) {
	        return {
	          text: item.title || '',
	          delimiter: true
	        };
	      }

	      const result = {
	        text: item.title,
	        value: item.title
	      };

	      if (item.icon) {
	        result.className = 'menu-popup-item-' + item.icon;
	      }

	      if (item.menu) {
	        result.items = [];

	        for (const subItem of Object.values(item.menu.items || {})) {
	          result.items.push(this.createMenuItem(subItem));
	        }
	      } else if (item.action) {
	        if (item.action.type === 'redirect') {
	          result.href = item.action.value;
	        } else if (item.action.type === 'jsCode') {
	          result.onclick = item.action.value;
	        } else {
	          result.onclick = () => {
	            this.onMenuItemClick(item);
	          };
	        }
	      }

	      return result;
	    }
	  }, {
	    key: "onMenuItemClick",
	    value: function onMenuItemClick(item) {
	      const menu = main_popup.MenuManager.getCurrentMenu();

	      if (menu) {
	        menu.close();
	      }

	      new Action(item.action).execute(babelHelpers.classPrivateFieldGet(this, _vueComponent));
	    }
	  }], [{
	    key: "showMenu",
	    value: function showMenu(vueComponent, menuItems, menuOptions) {
	      const menu = new Menu(vueComponent, menuItems, menuOptions);
	      menu.show();
	    }
	  }]);
	  return Menu;
	}();

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }

	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }

	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _type = /*#__PURE__*/new WeakMap();

	var _value = /*#__PURE__*/new WeakMap();

	var _actionParams = /*#__PURE__*/new WeakMap();

	var _prepareMenuItems = /*#__PURE__*/new WeakSet();

	let Action = /*#__PURE__*/function () {
	  function Action(params) {
	    babelHelpers.classCallCheck(this, Action);

	    _classPrivateMethodInitSpec(this, _prepareMenuItems);

	    _classPrivateFieldInitSpec$1(this, _type, {
	      writable: true,
	      value: null
	    });

	    _classPrivateFieldInitSpec$1(this, _value, {
	      writable: true,
	      value: null
	    });

	    _classPrivateFieldInitSpec$1(this, _actionParams, {
	      writable: true,
	      value: null
	    });

	    babelHelpers.classPrivateFieldSet(this, _type, params.type);
	    babelHelpers.classPrivateFieldSet(this, _value, params.value);
	    babelHelpers.classPrivateFieldSet(this, _actionParams, params.actionParams);
	  }

	  babelHelpers.createClass(Action, [{
	    key: "execute",
	    value: function execute(vueComponent) {
	      if (this.isJsEvent()) {
	        vueComponent.$Bitrix.eventEmitter.emit('crm:timeline:item:action:' + babelHelpers.classPrivateFieldGet(this, _value), babelHelpers.classPrivateFieldGet(this, _actionParams));
	        vueComponent.$Bitrix.eventEmitter.emit('crm:timeline:item:action', {
	          action: babelHelpers.classPrivateFieldGet(this, _value),
	          actionParams: babelHelpers.classPrivateFieldGet(this, _actionParams)
	        });
	      }

	      if (this.isJsCode()) {
	        eval(babelHelpers.classPrivateFieldGet(this, _value));
	      }

	      if (this.isAjaxAction()) {
	        main_core.ajax.runAction(babelHelpers.classPrivateFieldGet(this, _value), {
	          data: babelHelpers.classPrivateFieldGet(this, _actionParams)
	        }).then(() => {// do something
	        }, response => {
	          ui_notification.UI.Notification.Center.notify({
	            content: response.errors[0].message,
	            autoHideDelay: 5000
	          });
	        });
	      }

	      if (this.isRedirect()) {
	        location.href = babelHelpers.classPrivateFieldGet(this, _value);
	      }

	      if (this.isShowMenu()) {
	        Menu.showMenu(vueComponent, _classPrivateMethodGet(this, _prepareMenuItems, _prepareMenuItems2).call(this, babelHelpers.classPrivateFieldGet(this, _value).items, vueComponent), {
	          id: 'actionMenu',
	          bindElement: vueComponent.$el
	        });
	      }
	    }
	  }, {
	    key: "isJsEvent",
	    value: function isJsEvent() {
	      return babelHelpers.classPrivateFieldGet(this, _type) === 'jsEvent';
	    }
	  }, {
	    key: "isJsCode",
	    value: function isJsCode() {
	      return babelHelpers.classPrivateFieldGet(this, _type) === 'jsCode';
	    }
	  }, {
	    key: "isAjaxAction",
	    value: function isAjaxAction() {
	      return babelHelpers.classPrivateFieldGet(this, _type) === 'runAjaxAction';
	    }
	  }, {
	    key: "isRedirect",
	    value: function isRedirect() {
	      return babelHelpers.classPrivateFieldGet(this, _type) === 'redirect';
	    }
	  }, {
	    key: "isShowMenu",
	    value: function isShowMenu() {
	      return babelHelpers.classPrivateFieldGet(this, _type) === 'showMenu';
	    }
	  }, {
	    key: "getValue",
	    value: function getValue() {
	      return babelHelpers.classPrivateFieldGet(this, _value);
	    }
	  }]);
	  return Action;
	}();

	function _prepareMenuItems2(items, vueComponent) {
	  return Object.values(items).filter(item => item.state !== 'hidden' && item.scope !== 'mobile' && (!vueComponent.isReadOnly || !item.hideIfReadonly)).sort((a, b) => a.sort - b.sort);
	}

	const ChangeStreamButton = {
	  props: {
	    disableIfReadonly: Boolean,
	    type: String,
	    title: String,
	    action: Object
	  },
	  inject: ['isReadOnly'],
	  computed: {
	    isShowPinButton() {
	      return this.type === 'pin' && !this.isReadOnly;
	    },

	    isShowUnpinButton() {
	      return this.type === 'unpin' && !this.isReadOnly;
	    }

	  },
	  methods: {
	    executeAction() {
	      if (!this.action) {
	        return;
	      }

	      const action = new Action(this.action);
	      action.execute(this);
	    },

	    onClick() {
	      if (this.action) {
	        const action = new Action(this.action);
	        action.execute(this);
	      }
	    }

	  },
	  template: `
		<div class="crm-timeline__card-top_controller">
			<input
				v-if="type === 'complete'"
				@click="executeAction"
				type="checkbox"
				class="crm-timeline__card-top_checkbox"
			/>
			<div
				v-else-if="isShowPinButton"
				:title="title"
				@click="executeAction"
				class="crm-timeline__card-top_icon --pin"
			></div>
			<div
				v-else-if="isShowUnpinButton"
				:title="title"
				@click="executeAction"
				class="crm-timeline__card-top_icon --unpin"
			></div>
		</div>
	`
	};

	const Title = {
	  props: {
	    title: String,
	    action: Object
	  },
	  inject: ['isLogMessage'],
	  computed: {
	    className() {
	      return ['crm-timeline__card-title', {
	        '--light': this.isLogMessage,
	        '--action': !!this.action
	      }];
	    },

	    href() {
	      if (!this.action) {
	        return null;
	      }

	      const action = new Action(this.action);

	      if (action.isRedirect()) {
	        return action.getValue();
	      }

	      return null;
	    }

	  },
	  methods: {
	    executeAction() {
	      if (!this.action) {
	        return;
	      }

	      const action = new Action(this.action);
	      action.execute(this);
	    }

	  },
	  template: `
		<a
			v-if="href"
			:href="href"
			:class="className"
			tabindex="0"
			:title="title"
		>
			{{title}}
		</a>
		<span
			v-else
			@click="executeAction"
			:class="className"
			tabindex="0"
			:title="title"
		>
			{{title}}
		</span>`
	};

	let TagType = function TagType() {
	  babelHelpers.classCallCheck(this, TagType);
	};
	babelHelpers.defineProperty(TagType, "PRIMARY", 'primary');
	babelHelpers.defineProperty(TagType, "SECONDARY", 'secondary');
	babelHelpers.defineProperty(TagType, "SUCCESS", 'success');
	babelHelpers.defineProperty(TagType, "WARNING", 'warning');
	babelHelpers.defineProperty(TagType, "FAILURE", 'failure');

	const Tag = {
	  props: {
	    title: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    action: {
	      type: Object,
	      required: false,
	      default: null
	    },
	    type: {
	      type: String,
	      required: false,
	      default: TagType.SECONDARY
	    },
	    state: String
	  },
	  computed: {
	    className() {
	      return {
	        'crm-timeline__card-status': true,
	        '--clickable': !!this.action
	      };
	    },

	    tagTypeToLabelColorDict() {
	      return {
	        [TagType.PRIMARY]: ui_label.Label.Color.LIGHT_BLUE,
	        [TagType.SECONDARY]: ui_label.Label.Color.LIGHT,
	        [TagType.SUCCESS]: ui_label.Label.Color.LIGHT_GREEN,
	        [TagType.WARNING]: ui_label.Label.Color.LIGHT_YELLOW,
	        [TagType.FAILURE]: ui_label.Label.Color.LIGHT_RED
	      };
	    },

	    tagContainerRef() {
	      return this.$refs.tag;
	    }

	  },
	  methods: {
	    getLabelColorFromTagType(tagType) {
	      const lowerCaseTagType = tagType ? tagType.toLowerCase() : '';
	      const labelColor = this.tagTypeToLabelColorDict[lowerCaseTagType];
	      return labelColor ? labelColor : ui_label.Label.Color.LIGHT;
	    },

	    renderTag(tagOptions) {
	      if (!tagOptions || !this.tagContainerRef) {
	        return null;
	      }

	      const {
	        title,
	        type
	      } = tagOptions;
	      const uppercaseTitle = title && typeof title === 'string' ? title.toUpperCase() : '';
	      const label = new ui_label.Label({
	        text: uppercaseTitle,
	        color: this.getLabelColorFromTagType(type),
	        fill: true
	      });
	      this.tagContainerRef.appendChild(label.render());
	    },

	    executeAction() {
	      if (!this.action) {
	        return;
	      }

	      const action = new Action(this.action);
	      action.execute(this);
	    }

	  },

	  mounted() {
	    this.renderTag({
	      title: this.title,
	      type: this.type
	    });
	  },

	  template: `
		<div ref="tag" :class="className" @click="executeAction"></div>
	`
	};

	const User = {
	  props: {
	    title: String,
	    detailUrl: String,
	    imageUrl: String
	  },
	  inject: ['isLogMessage'],
	  computed: {
	    styles() {
	      if (!this.imageUrl) {
	        return {};
	      }

	      return {
	        backgroundImage: "url('" + main_core.Text.encode(this.imageUrl) + "')",
	        backgroundSize: '21px'
	      };
	    },

	    className() {
	      return ['ui-icon', 'ui-icon-common-user', 'crm-timeline__user-icon', {
	        '--muted': this.isLogMessage
	      }];
	    }

	  },
	  // language=Vue
	  template: `<a :class="className" :href="detailUrl"
				  target="_blank" :title="title"><i :style="styles"></i></a>`
	};

	const FormatDate = {
	  name: 'FormatDate',
	  props: {
	    timestamp: {
	      type: Number,
	      required: true,
	      default: 0
	    },
	    datePlaceholder: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    useShortTimeFormat: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    class: {
	      type: [Array, Object, String],
	      required: false,
	      default: ''
	    }
	  },
	  computed: {
	    formattedDate() {
	      if (!this.timestamp) {
	        return this.datePlaceholder;
	      }

	      const converter = crm_timeline_tools.DatetimeConverter.createFromServerTimestamp(this.timestamp).toUserTime();
	      return this.useShortTimeFormat ? converter.toTimeString() : converter.toDatetimeString({
	        delimiter: ', '
	      });
	    }

	  },
	  template: `
		<div :class="$props.class">{{ formattedDate }}</div>
	`
	};

	const Header = {
	  components: {
	    ChangeStreamButton,
	    Title,
	    Tag,
	    User,
	    FormatDate
	  },
	  props: {
	    title: String,
	    titleAction: Object,
	    date: Number,
	    datePlaceholder: String,
	    useShortTimeFormat: Boolean,
	    changeStreamButton: Object | null,
	    tags: Object,
	    user: Object
	  },
	  inject: ['isReadOnly'],
	  computed: {
	    visibleTags() {
	      return this.tags ? Object.values(this.tags).filter(this.isVisibleTagFilter) : [];
	    },

	    visibleAndAscSortedTags() {
	      const tagsCopy = main_core.Runtime.clone(this.visibleTags);
	      return tagsCopy.sort(this.tagsAscSorter);
	    },

	    isShowDate() {
	      return this.date || this.datePlaceholder;
	    }

	  },
	  methods: {
	    isVisibleTagFilter(tag) {
	      return tag.state !== 'hidden' && tag.scope !== 'mobile' && (!this.isReadOnly || !tag.hideIfReadonly);
	    },

	    tagsAscSorter(tagA, tagB) {
	      return tagA.sort - tagB.sort;
	    }

	  },
	  template: `
		<div class="crm-timeline__card-top">
			<div class="crm-timeline__card-top_info">
				<div class="crm-timeline__card-top_info_left">
					<ChangeStreamButton v-if="changeStreamButton" v-bind="changeStreamButton"></ChangeStreamButton>
					<Title :title="title" :action="titleAction"></Title>
				</div>
				<div ref="tags" class="crm-timeline__card-top_info_right">
					<Tag
						v-for="(tag, index) in visibleAndAscSortedTags"
						:key="index"
						v-bind="tag"
					/>
					<FormatDate
						v-if="isShowDate"
						:timestamp="date"
						:use-short-time-format="useShortTimeFormat"
						:date-placeholder="datePlaceholder"
						class="crm-timeline__card-time"
					/>
				</div>
			</div>
			<div class="crm-timeline__card-top_user">
				<User v-bind="user"></User>
			</div>
		</div>
	`
	};

	const Logo = {
	  props: {
	    type: String,
	    addIcon: String,
	    addIconType: String,
	    icon: String,
	    iconType: String,
	    inCircle: {
	      type: Boolean,
	      required: false,
	      default: false
	    },
	    action: Object
	  },
	  computed: {
	    className() {
	      return ['crm-timeline__card-logo', `--${this.type}`, {
	        '--clickable': this.action
	      }];
	    },

	    iconClassname() {
	      return ['crm-timeline__card-logo_icon', `--${this.type || this.icon}`, {
	        '--in-circle': this.inCircle
	      }];
	    },

	    addIconClassname() {
	      return ['crm-timeline__card-logo_add-icon', `--type-${this.addIconType}`, `--icon-${this.addIcon}`];
	    }

	  },
	  methods: {
	    executeAction() {
	      if (!this.action) {
	        return;
	      }

	      const action = new Action(this.action);
	      action.execute(this);
	    }

	  },
	  template: `
		<div :class="className" @click="executeAction">
			<div class="crm-timeline__card-logo_content">
				<div :class="iconClassname">
					<i></i>
				</div>
				<div :class="addIconClassname" v-if="addIcon">
					<i></i>
				</div>
			</div>
<!--			<div v-if="action" @click="executeAction" class="crm-timeline__card-icon_action-btn"></div>-->
		</div>
	`
	};

	const CalendarIcon = {
	  props: {
	    timestamp: {
	      type: Number,
	      required: true,
	      default: 0
	    }
	  },
	  computed: {
	    date() {
	      return main_date.DateTimeFormat.format('d', this.timestamp);
	    },

	    month() {
	      return main_date.DateTimeFormat.format('F', this.timestamp);
	    },

	    dayWeek() {
	      return main_date.DateTimeFormat.format('D', this.timestamp);
	    },

	    time() {
	      return main_date.DateTimeFormat.format('H:i', this.timestamp);
	    }

	  },
	  template: `
		<div class="crm-timeline__calendar-icon">
			<header class="crm-timeline__calendar-icon_top">
				<div class="crm-timeline__calendar-icon_bullets">
					<div class="crm-timeline__calendar-icon_bullet"></div>
					<div class="crm-timeline__calendar-icon_bullet"></div>
				</div>
			</header>
			<main class="crm-timeline__calendar-icon_content">
				<div class="crm-timeline__calendar-icon_day">{{ date }}</div>
				<div class="crm-timeline__calendar-icon_month">{{ month }}</div>
				<div class="crm-timeline__calendar-icon_date">
					<span class="crm-timeline__calendar-icon_day-week">{{ dayWeek }}</span>
					<span class="crm-timeline__calendar-icon_time">{{ time }}</span>
				</div>
			</main>
		</div>
	`
	};

	const LogoCalendar = ui_vue3.BitrixVue.cloneComponent(Logo, {
	  components: {
	    CalendarIcon
	  },
	  props: {
	    timestamp: {
	      type: Number,
	      required: false,
	      default: 0
	    }
	  },
	  template: `
		<div :class="className" @click="executeAction">
			<div class="crm-timeline__card-logo_content">
				<CalendarIcon :timestamp="timestamp" />
			</div>
		</div>
	`
	});

	const Body = {
	  components: {
	    Logo,
	    LogoCalendar
	  },
	  props: {
	    logo: Object,
	    blocks: Object
	  },

	  mounted() {
	    const blocks = this.$refs.blocks;

	    if (!blocks || !this.visibleBlocks) {
	      return;
	    }

	    this.visibleBlocks.forEach((block, index) => {
	      if (main_core.Type.isDomNode(blocks[index].$el)) {
	        blocks[index].$el.setAttribute('data-id', block.id);
	      } else {
	        throw new Error('Vue component "' + block.rendererName + '" was not found');
	      }
	    });
	  },

	  computed: {
	    visibleBlocks() {
	      return Object.keys(this.blocks).map(id => ({
	        id,
	        ...this.blocks[id]
	      })).filter(item => item.scope !== 'mobile');
	    }

	  },
	  template: `
		<div class="crm-timeline__card-body">
			<div v-if="logo" class="crm-timeline__card-logo_container">
				<LogoCalendar v-if="logo.icon === 'calendar'" v-bind="logo"></LogoCalendar>
				<Logo v-else v-bind="logo"></Logo>
			</div>
			<div class="crm-timeline__card-container">
				<div
					v-for="block in visibleBlocks"
					:key="block.id"
					class="crm-timeline__card-container_block"
				>
					<component
						:is="block.rendererName"
						v-bind="block.properties"
						ref="blocks"
					/>
				</div>
			</div>
		</div>
	`
	};

	const MenuId = 'timeline-more-button-menu';
	const Menu$1 = {
	  props: {
	    buttons: Array,
	    // buttons that didn't fit into footer
	    items: Object // real menu items

	  },
	  inject: ['isReadOnly'],
	  computed: {
	    itemsArray() {
	      if (!this.items) {
	        return [];
	      }

	      return Object.values(this.items).filter(item => item.state !== 'hidden' && item.scope !== 'mobile' && (!this.isReadOnly || !item.hideIfReadonly)).sort((a, b) => a.sort - b.sort);
	    },

	    menuItems() {
	      let result = this.buttons;

	      if (this.buttons.length && this.itemsArray.length) {
	        result.push({
	          delimiter: true
	        });
	      }

	      result = [...result, ...this.itemsArray];
	      return result;
	    }

	  },

	  beforeUnmount() {
	    const menu = main_popup.MenuManager.getMenuById(MenuId);

	    if (menu) {
	      menu.destroy();
	    }
	  },

	  methods: {
	    showMenu() {
	      Menu.showMenu(this, this.menuItems, {
	        id: MenuId,
	        className: 'crm-timeline__card_more-menu',
	        width: 230,
	        angle: false,
	        cacheable: false,
	        bindElement: this.$el
	      });
	    }

	  },
	  // language=Vue
	  template: `<div class="crm-timeline__card-action_menu-item --dotted" @click="showMenu"><i></i></div>`
	};

	let ButtonType = function ButtonType() {
	  babelHelpers.classCallCheck(this, ButtonType);
	};
	babelHelpers.defineProperty(ButtonType, "ICON", 'icon');
	babelHelpers.defineProperty(ButtonType, "PRIMARY", 'primary');
	babelHelpers.defineProperty(ButtonType, "SECONDARY", 'secondary');
	babelHelpers.defineProperty(ButtonType, "LIGHT", 'light');

	let ButtonState = function ButtonState() {
	  babelHelpers.classCallCheck(this, ButtonState);
	};
	babelHelpers.defineProperty(ButtonState, "DEFAULT", '');
	babelHelpers.defineProperty(ButtonState, "LOADING", 'loading');
	babelHelpers.defineProperty(ButtonState, "DISABLED", 'disabled');
	babelHelpers.defineProperty(ButtonState, "HIDDEN", 'hidden');

	const Button = {
	  props: {
	    title: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    type: {
	      type: String,
	      required: false,
	      default: ButtonType.SECONDARY
	    },
	    state: {
	      type: String,
	      required: false,
	      default: ButtonState.DEFAULT
	    },
	    iconName: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    size: {
	      type: String,
	      required: false,
	      default: 'extra_small'
	    },
	    action: Object
	  },

	  data() {
	    return {
	      popup: null
	    };
	  },

	  computed: {
	    buttonOptions() {
	      const upperCaseIconName = main_core.Type.isString(this.iconName) ? this.iconName.toUpperCase() : '';
	      const upperCaseButtonSize = main_core.Type.isString(this.size) ? this.size.toUpperCase() : 'extra_small';
	      const color = this.itemTypeToButtonColorDict[this.type] || ui_buttons.Button.Color.LIGHT_BORDER;
	      const text = this.type === ButtonType.ICON ? '' : this.title;
	      return {
	        round: true,
	        dependOnTheme: false,
	        size: ui_buttons.Button.Size[upperCaseButtonSize],
	        text: text,
	        color: color,
	        state: this.itemStateToButtonStateDict[this.state],
	        icon: ui_buttons.Button.Icon[upperCaseIconName]
	      };
	    },

	    itemTypeToButtonColorDict() {
	      return {
	        [ButtonType.PRIMARY]: ui_buttons.Button.Color.PRIMARY,
	        [ButtonType.SECONDARY]: ui_buttons.Button.Color.LIGHT_BORDER,
	        [ButtonType.LIGHT]: ui_buttons.Button.Color.LIGHT,
	        [ButtonType.ICON]: ui_buttons.Button.Color.LINK
	      };
	    },

	    itemStateToButtonStateDict() {
	      return {
	        [ButtonState.LOADING]: ui_buttons.Button.State.WAITING,
	        [ButtonState.DISABLED]: ui_buttons.Button.State.DISABLED
	      };
	    },

	    buttonContainerRef() {
	      return this.$refs.buttonContainer;
	    }

	  },
	  methods: {
	    renderButton() {
	      if (!this.buttonContainerRef) {
	        return;
	      }

	      this.buttonContainerRef.innerHTML = '';
	      const button = new ui_buttons.Button(this.buttonOptions);
	      button.renderTo(this.buttonContainerRef);
	    },

	    executeAction() {
	      if (this.action && this.state !== ButtonState.DISABLED && this.state !== ButtonState.LOADING) {
	        const action = new Action(this.action);
	        action.execute(this);
	      }
	    }

	  },

	  mounted() {
	    this.renderButton();
	  },

	  updated() {
	    this.renderButton();
	  },

	  template: `
		<div
			:class="$attrs.class"
			ref="buttonContainer"
			@click="executeAction">
		</div>
	`
	};

	const Buttons = {
	  components: {
	    Button
	  },
	  props: {
	    items: {
	      type: Array,
	      required: false,
	      default: () => []
	    }
	  },
	  template: `
			<div class="crm-timeline__card-action_buttons">
				<Button class="crm-timeline__card-action-btn" v-for="item in items" v-bind="item" />
			</div>
		`
	};

	let ButtonScope = function ButtonScope() {
	  babelHelpers.classCallCheck(this, ButtonScope);
	};
	babelHelpers.defineProperty(ButtonScope, "MOBILE", 'mobile');

	const Footer = {
	  components: {
	    Buttons,
	    Menu: Menu$1,
	    Button
	  },
	  props: {
	    buttons: Object,
	    menu: Object,
	    additionalButtons: {
	      type: Object,
	      required: false,
	      default: () => ({})
	    },
	    maxBaseButtonsCount: {
	      type: Number,
	      required: false,
	      default: 3
	    }
	  },
	  inject: ['isReadOnly'],
	  computed: {
	    baseButtons() {
	      return this.visibleAndSortedButtons.slice(0, this.maxBaseButtonsCount);
	    },

	    moreButtons() {
	      return this.visibleAndSortedButtons.slice(this.maxBaseButtonsCount);
	    },

	    visibleAndSortedButtons() {
	      return this.visibleButtons.sort(this.buttonsSorter);
	    },

	    visibleAndSortedAdditionalButtons() {
	      return this.visibleAdditionalButtons.sort(this.buttonsSorter);
	    },

	    visibleButtons() {
	      return this.buttons ? Object.values(this.buttons).filter(this.visibleButtonsFilter) : [];
	    },

	    visibleAdditionalButtons() {
	      return this.additionalButtonsArray ? Object.values(this.additionalButtonsArray).filter(this.visibleButtonsFilter) : [];
	    },

	    additionalButtonsArray() {
	      return Object.entries(this.additionalButtons).map(([id, button]) => {
	        return {
	          id,
	          type: ButtonType.ICON,
	          ...button
	        };
	      });
	    },

	    hasMenu() {
	      return this.moreButtons.length || main_core.Type.isPlainObject(this.menu) && Object.keys(this.menu).length;
	    }

	  },
	  methods: {
	    visibleButtonsFilter(buttonItem) {
	      return buttonItem.state !== ButtonState.HIDDEN && buttonItem.scope !== ButtonScope.MOBILE && (!this.isReadOnly || !buttonItem.hideIfReadonly);
	    },

	    buttonsSorter(buttonA, buttonB) {
	      return (buttonA === null || buttonA === void 0 ? void 0 : buttonA.sort) - (buttonB === null || buttonB === void 0 ? void 0 : buttonB.sort);
	    }

	  },
	  template: `
		<div class="crm-timeline__card-action">
			<Buttons :items="baseButtons" />
			<div class="crm-timeline__card-action_menu">
				<Button
					v-for="button in visibleAndSortedAdditionalButtons"
					:key="button.id"
					v-bind="button"
					class="crm-timeline__card-action_menu-item"
				/>
				<Menu v-if="hasMenu" :buttons="moreButtons" v-bind="menu" />
			</div>
		</div>
	`
	};

	const MarketPanel = {
	  props: {
	    text: String,
	    detailsText: String,
	    detailsTextAction: Object
	  },
	  computed: {
	    needShowDetailsText() {
	      return main_core.Type.isStringFilled(this.detailsText);
	    },

	    href() {
	      if (!this.detailsTextAction) {
	        return null;
	      }

	      const action = new Action(this.detailsTextAction);

	      if (action.isRedirect()) {
	        return action.getValue();
	      }

	      return null;
	    }

	  },
	  methods: {
	    executeAction() {
	      if (this.detailsTextAction) {
	        const action = new Action(this.detailsTextAction);
	        action.execute(this);
	      }
	    }

	  },
	  template: `
		<div class="crm-timeline__card-bottom">
		<div class="crm-timeline__card-market">
			<div class="crm-timeline__card-market_container">
				<span class="crm-timeline__card-market_logo"></span>
				<span class="crm-timeline__card-market_text">{{ text }}</span>
				<a
					v-if="href && needShowDetailsText"
					:href="href"
					class="crm-timeline__card-market_more"
				>
					{{detailsText}}
				</a>
				<span
					v-if="!href && needShowDetailsText"
					@click="executeAction"
					class="crm-timeline__card-market_more"
				>
				{{detailsText}}
				</span>
			</div>
			<div class="crm-timeline__card-market_cross"><i></i></div>
		</div>
		</div>
	`
	};

	const Note = {
	  components: {
	    User,
	    Button
	  },
	  props: {
	    text: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    saveNoteAction: Object,
	    deleteNoteAction: Object,
	    user: Object
	  },

	  data() {
	    return {
	      isEdit: false,
	      note: this.text,
	      oldNote: this.text
	    };
	  },

	  computed: {
	    ButtonType() {
	      return ButtonType;
	    },

	    saveButtonState() {
	      return this.note.trim().length ? ButtonState.DEFAULT : ButtonState.DISABLED;
	    },

	    encodedText() {
	      return main_core.Text.encode(this.text);
	    }

	  },
	  methods: {
	    startEditing() {
	      this.isEdit = true;
	      this.setCaretToEnd();
	    },

	    setCaretToEnd() {
	      const el = this.$refs.noteText;
	      const selection = window.getSelection();
	      const range = document.createRange();
	      selection.removeAllRanges();
	      range.selectNodeContents(el);
	      range.collapse(false);
	      selection.addRange(range);
	      el.focus();
	    },

	    onEdit(e) {
	      this.note = e.target.innerText;
	    },

	    onEnterHandle(e) {
	      if (e.ctrlKey) {
	        this.finishEditing();
	      }
	    },

	    finishEditing() {
	      if (this.saveButtonState === ButtonState.DISABLED || this.state === ButtonState.LOADING) {
	        return;
	      }

	      const note = this.$refs.noteText.innerText.trim();
	      this.$refs.noteText.innerText = note;
	      this.note = note;
	      this.oldNote = note;
	      this.isEdit = false;
	      this.saveNote();
	    },

	    cancelEditing() {
	      this.note = this.oldNote;
	      this.$refs.noteText.innerText = this.oldNote;
	      this.isEdit = false;
	    },

	    deleteNote() {
	      this.executeAction(this.deleteNoteAction);
	    },

	    saveNote() {
	      this.executeAction({ ...this.saveNoteAction,
	        value: this.note
	      });
	    },

	    executeAction(actionObject) {
	      if (!actionObject) {
	        return;
	      }

	      const action = new Action(actionObject);
	      action.execute(this);
	    }

	  },
	  template: `
		<div class="crm-timeline__card-note">
			<div class="crm-timeline__card-note_user">
				<User v-bind="user"></User>
			</div>
			<div class="crm-timeline__card-note_area">
				<div class="crm-timeline__card-note_value">
					<span
						ref="noteText"
						@input="onEdit"
						@keydown.esc="cancelEditing"
						@keydown.enter="onEnterHandle"
						v-html="encodedText"
						:contenteditable="isEdit"
						tabindex="0"
						class="crm-timeline__card-note_text"
					></span>
					<span
						v-if="!note"
						@click.prevent="setCaretToEnd"
						class="crm-timeline__card-note_placeholder"
					>
						{{ $Bitrix.Loc.getMessage('CRM_TIMELINE_USER_NOTE_PLACEHOLDER') }}
					</span>
					<span
						v-if="!isEdit"
						class="crm-timeline__card-note_edit"
						@click.prevent.stop="startEditing"
					>
						<i></i>
					</span>
				</div>
				<div v-if="isEdit" class="crm-timeline__card-note__controls">
					<div class="crm-timeline__card-note__control --save">
						<Button @click="finishEditing" :state="saveButtonState" :type="ButtonType.PRIMARY" :title="$Bitrix.Loc.getMessage('CRM_TIMELINE_USER_NOTE_SAVE')" />
					</div>
					<div class="crm-timeline__card-note__control --cancel">
						<Button @click="cancelEditing" :type="ButtonType.LIGHT" :title="$Bitrix.Loc.getMessage('CRM_TIMELINE_USER_NOTE_CANCEL')" />
					</div>
				</div>
			</div>
			<div class="crm-timeline__card-note_cross" @click="deleteNote">
				<i></i>
			</div>
		</div>
		`
	};

	const UserPick = {
	  template: `
		<div class="ui-icon ui-icon-common-user crm-timeline__card-top_user-icon">
			<i></i>
		</div>
	`
	};

	const StreamType = {
	  history: 0,
	  scheduled: 1,
	  pinned: 2
	};

	const Item$1 = {
	  components: {
	    Icon,
	    Header,
	    Body,
	    Footer,
	    MarketPanel,
	    Note,
	    UserPick
	  },
	  props: {
	    initialLayout: Object,
	    id: String,
	    useShortTimeFormat: Boolean,
	    isLogMessage: Boolean,
	    isReadOnly: Boolean,
	    onAction: Function,
	    streamType: {
	      type: Number,
	      required: false,
	      default: StreamType.history
	    }
	  },

	  data() {
	    return {
	      layout: this.initialLayout
	    };
	  },

	  provide() {
	    var _this$initialLayout;

	    return {
	      isLogMessage: !!((_this$initialLayout = this.initialLayout) !== null && _this$initialLayout !== void 0 && _this$initialLayout.isLogMessage),
	      isReadOnly: this.isReadOnly
	    };
	  },

	  created() {
	    this.$Bitrix.eventEmitter.subscribe('crm:timeline:item:action', this.onActionEvent);
	  },

	  beforeDestroy() {
	    this.$Bitrix.eventEmitter.unsubscribe('crm:timeline:item:action', this.onActionEvent);
	  },

	  methods: {
	    onActionEvent(event) {
	      const eventData = event.getData();
	      this.onAction(eventData.action, eventData.actionParams);
	    },

	    setLayout(newLayout) {
	      this.layout = newLayout;
	    }

	  },
	  computed: {
	    timelineCardClassname() {
	      return {
	        'crm-timeline__card': true,
	        'crm-timeline__card-scope': true,
	        '--stream-type-history': this.streamType === StreamType.history,
	        '--stream-type-scheduled': this.streamType === StreamType.scheduled,
	        '--stream-type-pinned': this.streamType === StreamType.pinned,
	        '--log-message': !!this.layout.isLogMessage
	      };
	    }

	  },
	  template: `
		<div :data-id="id" :class="timelineCardClassname">
			<div class="crm-timeline__card_icon_container">
				<Icon v-bind="layout.icon"></Icon>
			</div>
			<Header v-if="layout.header" v-bind="layout.header" :use-short-time-format="useShortTimeFormat"></Header>
			<Body v-if="layout.body" v-bind="layout.body"></Body>
			<Footer v-if="layout.footer" v-bind="layout.footer"></Footer>
			<MarketPanel v-if="layout.marketPanel" v-bind="layout.marketPanel"></MarketPanel>
		</div>
	`
	};

	function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }

	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	var _layout = /*#__PURE__*/new WeakMap();

	let Layout = /*#__PURE__*/function () {
	  function Layout(layout) {
	    babelHelpers.classCallCheck(this, Layout);

	    _classPrivateFieldInitSpec$2(this, _layout, {
	      writable: true,
	      value: null
	    });

	    babelHelpers.classPrivateFieldSet(this, _layout, layout);
	  }

	  babelHelpers.createClass(Layout, [{
	    key: "asPlainObject",
	    value: function asPlainObject() {
	      return Object.assign({}, babelHelpers.classPrivateFieldGet(this, _layout));
	    }
	  }, {
	    key: "getFooterMenuItemById",
	    value: function getFooterMenuItemById(id) {
	      var _babelHelpers$classPr, _babelHelpers$classPr2, _babelHelpers$classPr3, _babelHelpers$classPr4;

	      const items = (_babelHelpers$classPr = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldGet(this, _layout)) === null || _babelHelpers$classPr2 === void 0 ? void 0 : (_babelHelpers$classPr3 = _babelHelpers$classPr2.footer) === null || _babelHelpers$classPr3 === void 0 ? void 0 : (_babelHelpers$classPr4 = _babelHelpers$classPr3.menu) === null || _babelHelpers$classPr4 === void 0 ? void 0 : _babelHelpers$classPr4.items) !== null && _babelHelpers$classPr !== void 0 ? _babelHelpers$classPr : {};
	      return items.hasOwnProperty(id) ? items.id : null;
	    }
	  }, {
	    key: "addFooterMenuItem",
	    value: function addFooterMenuItem(menuItem) {
	      babelHelpers.classPrivateFieldGet(this, _layout).footer = babelHelpers.classPrivateFieldGet(this, _layout).footer || {};
	      babelHelpers.classPrivateFieldGet(this, _layout).footer.menu = babelHelpers.classPrivateFieldGet(this, _layout).footer.menu || {};
	      babelHelpers.classPrivateFieldGet(this, _layout).footer.menu.items = babelHelpers.classPrivateFieldGet(this, _layout).footer.menu.items || {};
	      babelHelpers.classPrivateFieldGet(this, _layout).footer.menu.items[menuItem.id] = menuItem;
	    }
	  }]);
	  return Layout;
	}();

	function _classPrivateFieldInitSpec$3(obj, privateMap, value) { _checkPrivateRedeclaration$3(obj, privateMap); privateMap.set(obj, value); }

	function _checkPrivateRedeclaration$3(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "get"); return _classApplyDescriptorGet(receiver, descriptor); }

	function _classCheckPrivateStaticFieldDescriptor(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }

	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }

	function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }

	var _id = /*#__PURE__*/new WeakMap();

	let ControllerManager = /*#__PURE__*/function () {
	  function ControllerManager(id) {
	    babelHelpers.classCallCheck(this, ControllerManager);

	    _classPrivateFieldInitSpec$3(this, _id, {
	      writable: true,
	      value: null
	    });

	    babelHelpers.classPrivateFieldSet(this, _id, id);
	  }

	  babelHelpers.createClass(ControllerManager, [{
	    key: "getItemControllers",
	    value: function getItemControllers(item) {
	      const foundControllers = [];

	      for (const controller of ControllerManager.getRegisteredControllers()) {
	        if (controller.isItemSupported(item)) {
	          foundControllers.push(new controller());
	        }
	      }

	      return foundControllers;
	    }
	  }], [{
	    key: "getInstance",
	    value: function getInstance(timelineId) {
	      if (!_classStaticPrivateFieldSpecGet(this, ControllerManager, _instances).hasOwnProperty(timelineId)) {
	        _classStaticPrivateFieldSpecGet(this, ControllerManager, _instances)[timelineId] = new ControllerManager(timelineId);
	      }

	      return _classStaticPrivateFieldSpecGet(this, ControllerManager, _instances)[timelineId];
	    }
	  }, {
	    key: "registerController",
	    value: function registerController(controller) {
	      _classStaticPrivateFieldSpecGet(this, ControllerManager, _availableControllers).push(controller);
	    }
	  }, {
	    key: "getRegisteredControllers",
	    value: function getRegisteredControllers() {
	      return _classStaticPrivateFieldSpecGet(this, ControllerManager, _availableControllers);
	    }
	  }]);
	  return ControllerManager;
	}();

	var _instances = {
	  writable: true,
	  value: {}
	};
	var _availableControllers = {
	  writable: true,
	  value: []
	};

	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$4(obj, privateSet); privateSet.add(obj); }

	function _classPrivateFieldInitSpec$4(obj, privateMap, value) { _checkPrivateRedeclaration$4(obj, privateMap); privateMap.set(obj, value); }

	function _checkPrivateRedeclaration$4(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _container = /*#__PURE__*/new WeakMap();

	var _itemClassName = /*#__PURE__*/new WeakMap();

	var _type$1 = /*#__PURE__*/new WeakMap();

	var _timelineId = /*#__PURE__*/new WeakMap();

	var _timestamp = /*#__PURE__*/new WeakMap();

	var _useShortTimeFormat = /*#__PURE__*/new WeakMap();

	var _isReadOnly = /*#__PURE__*/new WeakMap();

	var _controllers = /*#__PURE__*/new WeakMap();

	var _layoutComponent = /*#__PURE__*/new WeakMap();

	var _layoutApp = /*#__PURE__*/new WeakMap();

	var _layout$1 = /*#__PURE__*/new WeakMap();

	var _streamType = /*#__PURE__*/new WeakMap();

	var _initLayoutApp = /*#__PURE__*/new WeakSet();

	var _getLayoutAppProps = /*#__PURE__*/new WeakSet();

	var _onLayoutAppAction = /*#__PURE__*/new WeakSet();

	var _getContentBlockComponents = /*#__PURE__*/new WeakSet();

	let ConfigurableItem = /*#__PURE__*/function (_TimelineItem) {
	  babelHelpers.inherits(ConfigurableItem, _TimelineItem);

	  function ConfigurableItem(...args) {
	    var _this;

	    babelHelpers.classCallCheck(this, ConfigurableItem);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ConfigurableItem).call(this, ...args));

	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getContentBlockComponents);

	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _onLayoutAppAction);

	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _getLayoutAppProps);

	    _classPrivateMethodInitSpec$1(babelHelpers.assertThisInitialized(_this), _initLayoutApp);

	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _container, {
	      writable: true,
	      value: null
	    });

	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _itemClassName, {
	      writable: true,
	      value: null
	    });

	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _type$1, {
	      writable: true,
	      value: null
	    });

	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _timelineId, {
	      writable: true,
	      value: null
	    });

	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _timestamp, {
	      writable: true,
	      value: null
	    });

	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _useShortTimeFormat, {
	      writable: true,
	      value: false
	    });

	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _isReadOnly, {
	      writable: true,
	      value: false
	    });

	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _controllers, {
	      writable: true,
	      value: null
	    });

	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _layoutComponent, {
	      writable: true,
	      value: null
	    });

	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _layoutApp, {
	      writable: true,
	      value: null
	    });

	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _layout$1, {
	      writable: true,
	      value: null
	    });

	    _classPrivateFieldInitSpec$4(babelHelpers.assertThisInitialized(_this), _streamType, {
	      writable: true,
	      value: null
	    });

	    return _this;
	  }

	  babelHelpers.createClass(ConfigurableItem, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this._setId(id);

	      settings = settings || {};
	      babelHelpers.classPrivateFieldSet(this, _timelineId, settings.timelineId || '');
	      this.setContainer(settings.container || null);
	      babelHelpers.classPrivateFieldSet(this, _itemClassName, settings.itemClassName || '');

	      if (main_core.Type.isPlainObject(settings.data)) {
	        this.setData(settings.data);
	        babelHelpers.classPrivateFieldSet(this, _useShortTimeFormat, settings.useShortTimeFormat || false);
	        babelHelpers.classPrivateFieldSet(this, _isReadOnly, settings.isReadOnly || false);
	        babelHelpers.classPrivateFieldSet(this, _streamType, settings.streamType || crm_timeline_item.StreamType.history);
	      }

	      babelHelpers.classPrivateFieldSet(this, _controllers, ControllerManager.getInstance(babelHelpers.classPrivateFieldGet(this, _timelineId)).getItemControllers(this));
	    }
	  }, {
	    key: "setData",
	    value: function setData(data) {
	      babelHelpers.classPrivateFieldSet(this, _type$1, data.type || null);
	      babelHelpers.classPrivateFieldSet(this, _timestamp, data.timestamp || null);
	      babelHelpers.classPrivateFieldSet(this, _layout$1, new Layout(data.layout || {}));
	    }
	  }, {
	    key: "getLayout",
	    value: function getLayout() {
	      return babelHelpers.classPrivateFieldGet(this, _layout$1);
	    }
	  }, {
	    key: "getType",
	    value: function getType() {
	      return babelHelpers.classPrivateFieldGet(this, _type$1);
	    }
	  }, {
	    key: "layout",
	    value: function layout(options) {
	      let needBindToContainer = true;
	      let bindTo = null;

	      if (main_core.Type.isPlainObject(options)) {
	        needBindToContainer = BX.prop.getBoolean(options, 'add', true);
	        bindTo = main_core.Type.isElementNode(options['anchor']) ? options['anchor'] : null;
	      }

	      this.setWrapper(main_core.Dom.create({
	        tag: 'div',
	        attrs: {
	          className: babelHelpers.classPrivateFieldGet(this, _itemClassName)
	        }
	      }));

	      _classPrivateMethodGet$1(this, _initLayoutApp, _initLayoutApp2).call(this);

	      if (needBindToContainer) {
	        if (bindTo && bindTo.nextSibling) {
	          main_core.Dom.insertBefore(this.getWrapper(), bindTo.nextSibling);
	        } else {
	          main_core.Dom.append(this.getWrapper(), babelHelpers.classPrivateFieldGet(this, _container));
	        }
	      }
	    }
	  }, {
	    key: "refreshLayout",
	    value: function refreshLayout() {
	      // try to refresh layout via vue reactivity, if possible:
	      if (babelHelpers.classPrivateFieldGet(this, _layoutComponent)) {
	        babelHelpers.classPrivateFieldGet(this, _layoutComponent).setLayout(this.getLayout().asPlainObject());
	      } else {
	        babelHelpers.get(babelHelpers.getPrototypeOf(ConfigurableItem.prototype), "refreshLayout", this).call(this);
	      }
	    }
	  }, {
	    key: "clearLayout",
	    value: function clearLayout() {
	      for (const controller of babelHelpers.classPrivateFieldGet(this, _controllers)) {
	        controller.onBeforeItemClearLayout(this);
	      }

	      babelHelpers.classPrivateFieldGet(this, _layoutApp).unmount();
	      babelHelpers.get(babelHelpers.getPrototypeOf(ConfigurableItem.prototype), "clearLayout", this).call(this);
	    }
	  }, {
	    key: "getCreatedDate",
	    value: function getCreatedDate() {
	      const serverTimezoneDate = babelHelpers.classPrivateFieldGet(this, _timestamp) ? new Date(babelHelpers.classPrivateFieldGet(this, _timestamp) * 1000) : new Date();
	      return BX.prop.extractDate(new crm_timeline_tools.DatetimeConverter(serverTimezoneDate).toUserTime().getValue());
	    }
	  }, {
	    key: "getSourceId",
	    value: function getSourceId() {
	      let id = this.getId();

	      if (!main_core.Type.isInteger(id)) {
	        // id is like ACTIVITY_12
	        id = main_core.Text.toInteger(id.replace(/^\D+/g, ''));
	      }

	      return id;
	    }
	  }, {
	    key: "setContainer",
	    value: function setContainer(container) {
	      babelHelpers.classPrivateFieldSet(this, _container, container);
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      return babelHelpers.classPrivateFieldGet(this, _container);
	    }
	  }, {
	    key: "getDeadline",
	    value: function getDeadline() {
	      if (!babelHelpers.classPrivateFieldGet(this, _timestamp)) {
	        return null;
	      }

	      return new crm_timeline_tools.DatetimeConverter(new Date(babelHelpers.classPrivateFieldGet(this, _timestamp) * 1000)).toUserTime().getValue();
	    }
	  }, {
	    key: "isReadOnly",
	    value: function isReadOnly() {
	      return babelHelpers.classPrivateFieldGet(this, _isReadOnly);
	    }
	  }, {
	    key: "clone",
	    value: function clone() {
	      return ConfigurableItem.create(this.getId(), {
	        timelineId: babelHelpers.classPrivateFieldGet(this, _timelineId),
	        container: this.getContainer(),
	        itemClassName: babelHelpers.classPrivateFieldGet(this, _itemClassName),
	        useShortTimeFormat: babelHelpers.classPrivateFieldGet(this, _useShortTimeFormat),
	        isReadOnly: babelHelpers.classPrivateFieldGet(this, _isReadOnly),
	        streamType: babelHelpers.classPrivateFieldGet(this, _streamType),
	        data: {
	          type: babelHelpers.classPrivateFieldGet(this, _type$1),
	          timestamp: babelHelpers.classPrivateFieldGet(this, _timestamp),
	          layout: this.getLayout().asPlainObject()
	        }
	      });
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      const self = new ConfigurableItem();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return ConfigurableItem;
	}(Item);

	function _initLayoutApp2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _layoutApp)) {
	    babelHelpers.classPrivateFieldSet(this, _layoutApp, ui_vue3.BitrixVue.createApp(Item$1, _classPrivateMethodGet$1(this, _getLayoutAppProps, _getLayoutAppProps2).call(this)));

	    const contentBlockComponents = _classPrivateMethodGet$1(this, _getContentBlockComponents, _getContentBlockComponents2).call(this);

	    for (const componentName in contentBlockComponents) {
	      babelHelpers.classPrivateFieldGet(this, _layoutApp).component(componentName, contentBlockComponents[componentName]);
	    }

	    babelHelpers.classPrivateFieldSet(this, _layoutComponent, babelHelpers.classPrivateFieldGet(this, _layoutApp).mount(this.getWrapper()));
	  }
	}

	function _getLayoutAppProps2() {
	  return {
	    initialLayout: this.getLayout().asPlainObject(),
	    id: String(this.getId()),
	    useShortTimeFormat: babelHelpers.classPrivateFieldGet(this, _useShortTimeFormat),
	    isReadOnly: babelHelpers.classPrivateFieldGet(this, _isReadOnly),
	    streamType: babelHelpers.classPrivateFieldGet(this, _streamType),
	    onAction: _classPrivateMethodGet$1(this, _onLayoutAppAction, _onLayoutAppAction2).bind(this)
	  };
	}

	function _onLayoutAppAction2(action, actionParams) {
	  for (const controller of babelHelpers.classPrivateFieldGet(this, _controllers)) {
	    controller.onItemAction(this, action, actionParams);
	  }
	}

	function _getContentBlockComponents2() {
	  let components = {};

	  for (const controller of babelHelpers.classPrivateFieldGet(this, _controllers)) {
	    components = Object.assign(components, controller.getContentBlockComponents(this));
	  }

	  return components;
	}

	let Base = /*#__PURE__*/function () {
	  function Base() {
	    babelHelpers.classCallCheck(this, Base);
	  }

	  babelHelpers.createClass(Base, [{
	    key: "onItemAction",
	    value: function onItemAction(item, action, actionData) {}
	  }, {
	    key: "getContentBlockComponents",
	    value: function getContentBlockComponents(item) {
	      return {};
	    }
	    /**
	     * Will be executed before item node deleted from DOM
	     * @param item
	     */

	  }, {
	    key: "onBeforeItemClearLayout",
	    value: function onBeforeItemClearLayout(item) {}
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return false;
	    }
	  }]);
	  return Base;
	}();

	function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$5(obj, privateSet); privateSet.add(obj); }

	function _checkPrivateRedeclaration$5(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _viewActivity = /*#__PURE__*/new WeakSet();

	var _editActivity = /*#__PURE__*/new WeakSet();

	var _deleteActivity = /*#__PURE__*/new WeakSet();

	var _getActivityEditor = /*#__PURE__*/new WeakSet();

	let Activity = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Activity, _Base);

	  function Activity(...args) {
	    var _this;

	    babelHelpers.classCallCheck(this, Activity);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Activity).call(this, ...args));

	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getActivityEditor);

	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _deleteActivity);

	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _editActivity);

	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _viewActivity);

	    return _this;
	  }

	  babelHelpers.createClass(Activity, [{
	    key: "onItemAction",
	    value: function onItemAction(item, action, actionData) {
	      if (action === 'Activity:Edit' && actionData && actionData.activityId) {
	        _classPrivateMethodGet$2(this, _editActivity, _editActivity2).call(this, actionData.activityId);
	      }

	      if (action === 'Activity:View' && actionData && actionData.activityId) {
	        _classPrivateMethodGet$2(this, _viewActivity, _viewActivity2).call(this, actionData.activityId);
	      }

	      if (action === 'Activity:Delete' && actionData && actionData.activityId) {
	        var _actionData$confirmat;

	        const confirmationText = (_actionData$confirmat = actionData.confirmationText) !== null && _actionData$confirmat !== void 0 ? _actionData$confirmat : '';

	        if (confirmationText) {
	          ui_dialogs_messagebox.MessageBox.show({
	            message: confirmationText,
	            modal: true,
	            buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_NO,
	            onYes: () => {
	              return _classPrivateMethodGet$2(this, _deleteActivity, _deleteActivity2).call(this, actionData.activityId, actionData.ownerTypeId, actionData.ownerId);
	            },
	            onNo: messageBox => {
	              messageBox.close();
	            }
	          });
	        } else {
	          _classPrivateMethodGet$2(this, _deleteActivity, _deleteActivity2).call(this, actionData.activityId, actionData.ownerTypeId, actionData.ownerId);
	        }
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      const itemType = item.getType();
	      return itemType.indexOf('Activity:') === 0; // for items with type started from `Activity:`
	    }
	  }]);
	  return Activity;
	}(Base);

	function _viewActivity2(id) {
	  const editor = _classPrivateMethodGet$2(this, _getActivityEditor, _getActivityEditor2).call(this);

	  if (editor && id) {
	    editor.viewActivity(id);
	  }
	}

	function _editActivity2(id) {
	  const editor = _classPrivateMethodGet$2(this, _getActivityEditor, _getActivityEditor2).call(this);

	  if (editor && id) {
	    editor.editActivity(id);
	  }
	}

	function _deleteActivity2(activityId, ownerTypeId, ownerId) {
	  return main_core.ajax.runAction('crm.timeline.activity.delete', {
	    data: {
	      activityId,
	      ownerTypeId,
	      ownerId
	    }
	  }).then(() => {
	    return true;
	  }, response => {
	    ui_notification.UI.Notification.Center.notify({
	      content: response.errors[0].message,
	      autoHideDelay: 5000
	    });
	    return true;
	  });
	}

	function _getActivityEditor2() {
	  return BX.CrmActivityEditor.getDefault();
	}

	let TextColor = function TextColor() {
	  babelHelpers.classCallCheck(this, TextColor);
	};
	babelHelpers.defineProperty(TextColor, "GREEN", 'green');
	babelHelpers.defineProperty(TextColor, "BASE_50", 'base-50');
	babelHelpers.defineProperty(TextColor, "BASE_70", 'base-70');
	babelHelpers.defineProperty(TextColor, "BASE_90", 'base-90');

	let TextWeight = function TextWeight() {
	  babelHelpers.classCallCheck(this, TextWeight);
	};
	babelHelpers.defineProperty(TextWeight, "NORMAL", 'normal');
	babelHelpers.defineProperty(TextWeight, "MEDIUM", 'medium');
	babelHelpers.defineProperty(TextWeight, "BOLD", 'bold');

	let TextSize = function TextSize() {
	  babelHelpers.classCallCheck(this, TextSize);
	};
	babelHelpers.defineProperty(TextSize, "XS", 'xs');
	babelHelpers.defineProperty(TextSize, "SM", 'sm');
	babelHelpers.defineProperty(TextSize, "MD", 'md');

	var Text = {
	  props: {
	    value: String,
	    color: {
	      type: String,
	      required: false,
	      default: ''
	    },
	    weight: {
	      type: String,
	      required: false,
	      default: 'normal'
	    },
	    size: {
	      type: String,
	      required: false,
	      default: 'md'
	    }
	  },
	  computed: {
	    className() {
	      return ['crm-timeline__text-block', this.colorClassname, this.weightClassname, this.sizeClassname];
	    },

	    colorClassname() {
	      const upperCaseColorProp = this.color ? this.color.toUpperCase() : '';
	      const color = TextColor[upperCaseColorProp] ? TextColor[upperCaseColorProp] : '';
	      return `--color-${color}`;
	    },

	    weightClassname() {
	      const upperCaseWeightProp = this.weight ? this.weight.toUpperCase() : '';
	      const weight = TextWeight[upperCaseWeightProp] ? TextWeight[upperCaseWeightProp] : TextWeight.NORMAL;
	      return `--weight-${weight}`;
	    },

	    sizeClassname() {
	      const upperCaseWeightProp = this.size ? this.size.toUpperCase() : '';
	      const size = TextSize[upperCaseWeightProp] ? TextSize[upperCaseWeightProp] : TextSize.SM;
	      return `--size-${size}`;
	    }

	  },
	  template: `
		<span
			:title="value"
			:class="className"
		>
			{{value}}
		</span>`
	};

	var Link = {
	  props: {
	    text: String,
	    action: Object,
	    bold: {
	      type: Boolean,
	      required: false,
	      default: false
	    }
	  },
	  computed: {
	    href() {
	      if (!this.action) {
	        return null;
	      }

	      const action = new Action(this.action);

	      if (action.isRedirect()) {
	        return action.getValue();
	      }

	      return null;
	    },

	    className() {
	      return {
	        'crm-timeline__card_link': true,
	        '--bold': this.bold
	      };
	    }

	  },
	  methods: {
	    executeAction() {
	      if (this.action) {
	        const action = new Action(this.action);
	        action.execute(this);
	      }
	    }

	  },
	  template: `
			<a
				v-if="href"
				:href="href"
				:class="className"
			>
			{{text}}
			</a>
			<span
				v-else
				@click="executeAction"
				:class="className"
			>
				{{text}}
			</span>
		`
	};

	var WithTitle = {
	  props: {
	    title: String,
	    inline: Boolean,
	    contentBlock: Object
	  },
	  computed: {
	    className() {
	      return {
	        'crm-timeline__card-container_info': true,
	        '--inline': this.inline
	      };
	    }

	  },
	  template: `
			<div :class="className">
				<div class="crm-timeline__card-container_info-title">{{ title }}</div>
				<div class="crm-timeline__card-container_info-value">
					<component :is="contentBlock.rendererName" v-bind="contentBlock.properties"></component>
				</div>
			</div>
		`
	};

	var LineOfTextBlocks = {
	  props: {
	    blocks: Object
	  },

	  mounted() {
	    const blocks = this.$refs.blocks;
	    this.visibleBlocks.forEach((block, index) => {
	      if (main_core.Type.isDomNode(blocks[index].$el)) {
	        blocks[index].$el.setAttribute('data-id', block.id);
	      } else {
	        throw new Error('Vue component "' + block.rendererName + '" was not found');
	      }
	    });
	  },

	  computed: {
	    visibleBlocks() {
	      return Object.keys(this.blocks).map(id => ({
	        id,
	        ...this.blocks[id]
	      })).filter(item => item.scope !== 'mobile');
	    }

	  },
	  // language=Vue
	  template: `
		<span class="crm-timeline-block-line-of-texts">
			<span
				v-for="(block) in visibleBlocks"
				:key="block.id"
			>
				<component :is="block.rendererName"
						   v-bind="block.properties"
						   ref="blocks"/>
			<span>&nbsp;</span>
			</span>
		</span>`
	};

	const defaultPlaybackRateValues = [0.5, 1, 1.25, 1.5, 1.75, 2];
	const TimelineAudio = ui_vue3.BitrixVue.cloneComponent(ui_vue3_components_audioplayer.AudioPlayer, {
	  props: {
	    playbackRateValues: {
	      type: Array,
	      required: false,
	      default: () => defaultPlaybackRateValues
	    },
	    isShowPlaybackRateMenu: {
	      type: Boolean,
	      required: false,
	      default: true
	    }
	  },

	  data() {
	    return { ...this.parentData(),
	      playbackRate: 1
	    };
	  },

	  computed: {
	    containerClassname() {
	      return ['crm-timeline__audioplayer-contianer', 'ui-vue-audioplayer-container', {
	        'ui-vue-audioplayer-container-dark': this.isDark,
	        'ui-vue-audioplayer-container-mobile': this.isMobile
	      }];
	    },

	    controlClassname() {
	      return ['ui-vue-audioplayer-control', 'ui-btn-icon-start', {
	        'ui-vue-audioplayer-control-loader': this.loading,
	        'ui-vue-audioplayer-control-play': !this.loading && this.state !== this.State.play,
	        'ui-vue-audioplayer-control-pause': !this.loading && this.state === this.State.play
	      }];
	    },

	    timeCurrentClassname() {
	      return ['ui-vue-audioplayer-time', 'ui-vue-audioplayer-time-current', {
	        '--is-playing': this.state === this.State.play
	      }];
	    },

	    totalTimeClassname() {
	      return ['ui-vue-audioplayer-total-time', {
	        '--hidden': this.isTotalTimeHidden
	      }];
	    },

	    progressPosition() {
	      return `width: ${this.progressInPixel}px;`;
	    },

	    seekPosition() {
	      var _this$$refs$seek;

	      const minSeekWidth = 20;
	      const seekWidth = (_this$$refs$seek = this.$refs.seek) !== null && _this$$refs$seek !== void 0 && _this$$refs$seek.offsetWidth ? this.$refs.seek.offsetWidth : minSeekWidth;
	      return `left: ${this.progressInPixel - seekWidth / 2}px;`;
	    },

	    formatTimeCurrent() {
	      return this.formatTime(this.timeCurrent);
	    },

	    formatTimeTotal() {
	      return this.formatTime(this.timeTotal);
	    },

	    isTotalTimeHidden() {
	      const totalTimeRef = this.$refs.totalTime;
	      const seekRef = this.$refs.seek;
	      if (!this.loaded || !totalTimeRef || !seekRef) return true;
	      const seekWidth = seekRef.offsetWidth;
	      return this.progressInPixel + seekWidth / 2 >= totalTimeRef.offsetLeft;
	    },

	    playbackRateMenuItems() {
	      return this.playbackRateValues.map(rate => {
	        return this.createPlaybackRateMenuItem({
	          text: this.getPlaybackRateOptionText(rate),
	          rate,
	          isActive: rate === this.playbackRate
	        });
	      });
	    }

	  },
	  methods: {
	    changePlaybackRate(playbackRate) {
	      const audio = this.$refs.source;
	      this.playbackRate = playbackRate;
	      audio.playbackRate = playbackRate;
	    },

	    createPlaybackRateMenuItem(options = {}) {
	      const rate = options.rate || 1;
	      const text = options.text || '';
	      const isActive = options.isActive || false;
	      const className = `playback-speed-menu-item ${isActive ? 'menu-popup-item-accept-sm' : ''}`;
	      return {
	        text: text,
	        className,
	        onclick: (event, item) => {
	          this.changePlaybackRate(rate);
	          item.menuWindow.popupWindow.close();
	          return true;
	        }
	      };
	    },

	    getPlaybackRateOptionText(rate) {
	      return `${rate}x`;
	    },

	    renderPlaybackRateButton() {
	      const playbackRateButtonContainer = this.$refs.playbackRateButtonContainer;
	      playbackRateButtonContainer.innerHTML = '';
	      const btn = new ui_buttons.Button({
	        text: this.getPlaybackRateOptionText(this.playbackRate),
	        round: true,
	        dropdown: true,
	        color: ui_buttons.Button.Color.LIGHT_BORDER,
	        size: ui_buttons.Button.Size.EXTRA_SMALL,
	        noCaps: true,
	        className: 'playback-speed-button crm-timeline__playback-speed-menu_scope',
	        menu: {
	          className: 'crm-timeline__playback-speed-menu_scope',
	          width: 100,
	          events: {
	            onPopupShow: () => {
	              const btnContainerWidth = btn.getContainer().offsetWidth;
	              const popupWindow = btn.getMenuWindow().getPopupWindow();
	              popupWindow.setWidth(btnContainerWidth * 1.8);
	              popupWindow.setOffset({
	                offsetLeft: btnContainerWidth - 8
	              });
	              popupWindow.adjustPosition();
	            }
	          },
	          angle: {
	            position: 'top'
	          },
	          offsetLeft: 0,
	          items: this.playbackRateMenuItems
	        }
	      });
	      btn.renderTo(playbackRateButtonContainer);
	    }

	  },
	  watch: {
	    playbackRate() {
	      this.renderPlaybackRateButton();
	    }

	  },

	  mounted() {
	    this.renderPlaybackRateButton();
	  },

	  template: `
		<div
			:class="containerClassname"
			ref="body"
		>
			<div class="ui-vue-audioplayer-controls-container">
				<button :class="controlClassname" @click="clickToButton">
					<svg v-if="state !== State.play" class="ui-vue-audioplayer-control-icon" width="9" height="12" viewBox="0 0 9 12" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M8.52196 5.40967L1.77268 0.637568C1.61355 0.523473 1.40621 0.510554 1.23498 0.604066C1.06375 0.697578 0.957151 0.881946 0.958524 1.0822V10.6259C0.956507 10.8265 1.06301 11.0114 1.23449 11.105C1.40597 11.1987 1.61368 11.1854 1.77268 11.0706L8.52196 6.29847C8.66466 6.19871 8.75016 6.0322 8.75016 5.85407C8.75016 5.67593 8.66466 5.50942 8.52196 5.40967Z"/>
					</svg>
					<svg v-else width="8" height="10" viewBox="0 0 8 10" xmlns="http://www.w3.org/2000/svg">
						<path d="M2.5625 0.333008H0.375V9.66634H2.5625V0.333008Z" fill="inherit" />
						<path d="M7.25 0.333008H5.0625V9.66634H7.25V0.333008Z" fill="inherit" />
					</svg>
				</button>
			</div>
			<div class="ui-vue-audioplayer-timeline-container">
				<div class="ui-vue-audioplayer-track-container" @mousemove="seeking" @click="setPosition" ref="track">
					<div class="ui-vue-audioplayer-track-mask"></div>
					<div class="ui-vue-audioplayer-track" :style="progressPosition"></div>
					<div @click.stop class="ui-vue-audioplayer-track-seek" :style="seekPosition">
						<div ref="seek" class="ui-vue-audioplayer-track-seek-icon"></div>
						<div :class="timeCurrentClassname">{{formatTimeCurrent}}</div>
					</div>
<!--					<div class="ui-vue-audioplayer-track-event" @mousemove="seeking"></div>-->
				</div>
				<div :class="totalTimeClassname">
					<div ref="totalTime" class="ui-vue-audioplayer-time">{{formatTimeTotal}}</div>
				</div>
			</div>
			<div
				v-if="isShowPlaybackRateMenu"
				ref="playbackRateButtonContainer"
				class="ui-vue-audioplayer_playback-speed-menu-container">
			</div>
			<audio
				v-if="src" 
				:src="src"
				class="ui-vue-audioplayer-source"
				ref="source"
				:preload="preload"
				@abort="audioEventRouter('abort', $event)"
				@error="audioEventRouter('error', $event)"
				@suspend="audioEventRouter('suspend', $event)"
				@canplay="audioEventRouter('canplay', $event)"
				@canplaythrough="audioEventRouter('canplaythrough', $event)"
				@durationchange="audioEventRouter('durationchange', $event)"
				@loadeddata="audioEventRouter('loadeddata', $event)"
				@loadedmetadata="audioEventRouter('loadedmetadata', $event)"
				@timeupdate="audioEventRouter('timeupdate', $event)"
				@play="audioEventRouter('play', $event)"
				@playing="audioEventRouter('playing', $event)"
				@pause="audioEventRouter('pause', $event)"
			></audio>
		</div>
	`
	});

	let ClientMark = function ClientMark() {
	  babelHelpers.classCallCheck(this, ClientMark);
	};
	babelHelpers.defineProperty(ClientMark, "POSITIVE", 'positive');
	babelHelpers.defineProperty(ClientMark, "NEUTRAL", 'neutral');
	babelHelpers.defineProperty(ClientMark, "NEGATIVE", 'negative');

	const ClientMark$1 = {
	  props: {
	    mark: {
	      type: String,
	      required: false,
	      default: ClientMark.POSITIVE
	    },
	    text: {
	      type: String,
	      required: false,
	      default: ''
	    }
	  },
	  computed: {
	    className() {
	      return {
	        'crm-timeline__client-mark': true,
	        '--positive-mark': this.mark === ClientMark.POSITIVE,
	        '--neutral-mark': this.mark === ClientMark.NEUTRAL,
	        '--negative-mark': this.mark === ClientMark.NEGATIVE
	      };
	    },

	    iconClassname() {
	      return {
	        'crm-timeline__client-mark_icon': true,
	        '--flipped': this.mark === ClientMark.NEGATIVE
	      };
	    }

	  },
	  template: `
		<div :class="className">
			<i class="crm-timeline__client-mark_icon-wrapper">
				<svg :class="iconClassname" width="11.35" height="11.36" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path fill-rule="evenodd" clip-rule="evenodd" d="M14.8367 6.7166L11.2351 6.71731C11.1314 6.71731 11.0313 6.65735 10.9925 6.56213C10.8303 6.15654 10.8366 5.69947 11.0144 5.29671C11.3127 4.42841 11.3466 3.49029 11.1124 2.60224C10.8676 1.9914 10.8028 0.875521 9.67347 0.82403C9.32009 0.876226 9.01184 1.08854 8.8341 1.39819C8.80235 1.45321 8.78895 1.5181 8.78895 1.58088C8.78895 1.58088 8.84085 2.6644 8.78895 3.41764C8.73706 4.17087 7.29571 6.08954 6.37945 7.30769C6.32161 7.38528 6.24049 7.43607 6.14456 7.45088C5.80104 7.50232 5.14581 7.58943 4.90962 7.62062C4.85434 7.62792 4.82201 7.69735 4.82201 7.72994C4.82201 9.2527 4.82201 11.5306 4.82201 14.5636C4.82201 14.5887 4.85234 14.6435 4.90623 14.6524C5.09126 14.6829 5.55408 14.7676 6.02112 14.9178C6.60798 15.1061 7.0968 15.5519 8.07513 15.882C8.12662 15.8997 8.18375 15.9088 8.23807 15.9088H12.8321C13.4253 15.803 13.8668 15.2959 13.8972 14.6872C13.9056 14.3479 13.8379 14.0121 13.699 13.7039C13.6792 13.6594 13.7053 13.6129 13.7533 13.6037C14.3422 13.4965 15.0786 12.3785 14.1715 11.4778C14.1476 11.4545 14.1518 11.4228 14.1842 11.4143C14.6843 11.2866 15.0674 10.8874 15.1844 10.3894C15.2296 10.199 15.2134 10.0022 15.1569 9.81524C15.0906 9.59235 14.9848 9.38356 14.8445 9.19735C14.8092 9.15079 14.8289 9.09013 14.8854 9.07179C15.3834 8.90321 15.7262 8.4271 15.7226 7.88538C15.7798 7.35424 15.353 6.71731 14.8367 6.7166ZM3.412 7.00439H0.808518C0.673089 7.00439 0.570107 7.1243 0.593384 7.25479L2.10567 15.7508C2.12895 15.8813 2.24392 15.9766 2.37865 15.9766H3.34146C3.49029 15.9766 3.61091 15.8588 3.61091 15.7135L3.63207 7.22023C3.63207 7.10102 3.53403 7.00439 3.412 7.00439Z"/>
				</svg>
			</i>
			<span class="crm-timeline__client-mark_text">{{ text }}</span>
		</div>
	`
	};

	var Money = {
	  props: {
	    opportunity: Number,
	    currencyId: String
	  },
	  computed: {
	    moneyHtml() {
	      if (!main_core.Type.isNumber(this.opportunity) || !main_core.Type.isStringFilled(this.currencyId)) {
	        return null;
	      }

	      return currency_currencyCore.CurrencyCore.currencyFormat(this.opportunity, this.currencyId, true);
	    }

	  },
	  template: `<span v-if="moneyHtml" v-html="moneyHtml"></span>`
	};

	var EditableText = ui_vue3.BitrixVue.cloneComponent(Text, {
	  components: {
	    Text
	  },
	  props: {
	    action: Object
	  },

	  data() {
	    return {
	      isEdit: false,
	      currentValue: this.value,
	      initialValue: this.value,
	      actionTimeoutId: null
	    };
	  },

	  computed: {
	    textProps() {
	      return { ...this.$props,
	        value: this.currentValue
	      };
	    }

	  },
	  methods: {
	    enableEdit() {
	      this.cancelScheduledActionExecution();
	      this.isEdit = true;
	      this.$nextTick(() => {
	        this.$refs.input.focus();
	      });
	    },

	    disableEdit() {
	      this.isEdit = false;
	      this.scheduleActionExecution();
	    },

	    scheduleActionExecution() {
	      this.cancelScheduledActionExecution();
	      this.actionTimeoutId = setTimeout(this.executeAction.bind(this), 3 * 1000);
	    },

	    cancelScheduledActionExecution() {
	      if (this.actionTimeoutId) {
	        clearTimeout(this.actionTimeoutId);
	        this.actionTimeoutId = null;
	      }
	    },

	    executeAction() {
	      var _actionDescription$ac;

	      if (!this.action || this.currentValue === this.initialValue) {
	        return;
	      } // to avoid unintended props mutation


	      const actionDescription = main_core.Runtime.clone(this.action);
	      (_actionDescription$ac = actionDescription.actionParams) !== null && _actionDescription$ac !== void 0 ? _actionDescription$ac : actionDescription.actionParams = {};
	      actionDescription.actionParams.value = this.currentValue;
	      const action = new Action(actionDescription);
	      action.execute(this);
	      this.initialValue = this.currentValue;
	    }

	  },
	  template: `
			<input
				v-if="isEdit"
				ref="input"
				type="text"
				v-model.trim="currentValue"
				@focusout="disableEdit"
			>
			<Text
				v-else
				v-bind="textProps"
				@click="enableEdit"
			/>
		`
	});

	var EditableDate = {
	  components: {
	    Link
	  },
	  props: {
	    date: Number,
	    action: Object
	  },

	  data() {
	    return {
	      currentDate: this.date,
	      initialDate: this.date,
	      actionTimeoutId: null
	    };
	  },

	  computed: {
	    currentDateObject() {
	      return this.currentDate ? new Date(this.currentDate * 1000) : null;
	    },

	    currentDateInSiteFormat() {
	      if (!this.currentDateObject) {
	        return null;
	      }

	      return main_date.DateTimeFormat.format(crm_timeline_tools.DatetimeConverter.getSiteDateFormat(), this.currentDateObject);
	    },

	    textProps() {
	      return {
	        text: this.currentDateInSiteFormat
	      };
	    }

	  },
	  methods: {
	    openCalendar(event) {
	      this.cancelScheduledActionExecution(); // eslint-disable-next-line bitrix-rules/no-bx

	      BX.calendar({
	        node: event.target,
	        value: this.currentDateInSiteFormat,
	        bTime: false,
	        bHideTime: true,
	        bSetFocus: false,
	        callback_after: newDate => {
	          this.currentDate = Math.round(newDate.getTime() / 1000);
	          this.scheduleActionExecution();
	        }
	      });
	    },

	    scheduleActionExecution() {
	      this.cancelScheduledActionExecution();
	      this.actionTimeoutId = setTimeout(this.executeAction.bind(this), 3 * 1000);
	    },

	    cancelScheduledActionExecution() {
	      if (this.actionTimeoutId) {
	        clearTimeout(this.actionTimeoutId);
	        this.actionTimeoutId = null;
	      }
	    },

	    executeAction() {
	      var _actionDescription$ac;

	      if (!this.action) {
	        return;
	      }

	      if (this.currentDate === this.initialDate) {
	        return;
	      } // to avoid unintended props mutation


	      const actionDescription = main_core.Runtime.clone(this.action);
	      (_actionDescription$ac = actionDescription.actionParams) !== null && _actionDescription$ac !== void 0 ? _actionDescription$ac : actionDescription.actionParams = {};
	      actionDescription.actionParams.value = this.currentDateObject;
	      const action = new Action(actionDescription);
	      action.execute(this);
	      this.initialDate = this.currentDate;
	    }

	  },
	  template: `<Link @click="openCalendar" v-bind="textProps"></Link>`
	};

	const PlayerAlert = {
	  components: {
	    LineOfTextBlocks
	  },
	  props: {
	    blocks: {
	      type: Object,
	      required: false,
	      default: () => ({})
	    },
	    color: {
	      type: String,
	      required: false,
	      default: ui_alerts.AlertColor.DEFAULT
	    },
	    icon: {
	      type: String,
	      required: false,
	      default: ui_alerts.AlertIcon.NONE
	    }
	  },
	  computed: {
	    containerClassname() {
	      return ['crm-timeline__player-alert', 'ui-alert', 'ui-alert-xs', 'ui-alert-text-center', this.color, this.icon];
	    }

	  },
	  template: `
		<div :class="containerClassname">
			<div class="ui-alert-message">
				<LineOfTextBlocks :blocks="blocks"></LineOfTextBlocks>
			</div>
		</div>
	`
	};

	var DatePill = {
	  props: {
	    date: Number,
	    action: Object
	  },

	  data() {
	    return {
	      currentDate: this.date,
	      initialDate: this.date,
	      actionTimeoutId: null
	    };
	  },

	  computed: {
	    currentFormatDate() {
	      if (!this.currentDateObject) {
	        return null;
	      }

	      return main_date.DateTimeFormat.format(this.dateFormat, this.currentDateObject);
	    },

	    dateFormat() {
	      return 'D, j M, H:i';
	    },

	    currentDateObject() {
	      return this.currentDate ? new Date(this.currentDate * 1000) : null;
	    },

	    calendarParams() {
	      return {
	        value: this.currentFormatDate,
	        bTime: true,
	        bHideTime: false,
	        bSetFocus: false
	      };
	    }

	  },
	  methods: {
	    openCalendar(event) {
	      this.cancelScheduledActionExecution(); // eslint-disable-next-line bitrix-rules/no-bx

	      BX.calendar({
	        node: event.target,
	        callback_after: newDate => {
	          this.currentDate = Math.round(newDate.getTime() / 1000);
	          this.scheduleActionExecution();
	        },
	        ...this.calendarParams
	      });
	    },

	    scheduleActionExecution() {
	      this.cancelScheduledActionExecution();
	      this.actionTimeoutId = setTimeout(this.executeAction.bind(this), 3 * 1000);
	    },

	    cancelScheduledActionExecution() {
	      if (this.actionTimeoutId) {
	        clearTimeout(this.actionTimeoutId);
	        this.actionTimeoutId = null;
	      }
	    },

	    executeAction() {
	      var _actionDescription$ac;

	      if (!this.action) {
	        return;
	      }

	      if (this.currentDate === this.initialDate) {
	        return;
	      } // to avoid unintended props mutation


	      const actionDescription = main_core.Runtime.clone(this.action);
	      (_actionDescription$ac = actionDescription.actionParams) !== null && _actionDescription$ac !== void 0 ? _actionDescription$ac : actionDescription.actionParams = {};
	      actionDescription.actionParams.value = this.currentDateObject;
	      const action = new Action(actionDescription);
	      action.execute(this);
	      this.initialDate = this.currentDate;
	    }

	  },
	  template: `
		<span class="crm-timeline__date-pill" @click="openCalendar">
			<span>
				{{ currentFormatDate }}
			</span>
			<span class="crm-timeline__date-pill_caret"></span>
		</span>`
	};

	function _classPrivateMethodInitSpec$3(obj, privateSet) { _checkPrivateRedeclaration$6(obj, privateSet); privateSet.add(obj); }

	function _checkPrivateRedeclaration$6(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classPrivateMethodGet$3(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _openEntityDetailTab = /*#__PURE__*/new WeakSet();

	let CommonContentBlocks = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(CommonContentBlocks, _Base);

	  function CommonContentBlocks(...args) {
	    var _this;

	    babelHelpers.classCallCheck(this, CommonContentBlocks);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CommonContentBlocks).call(this, ...args));

	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _openEntityDetailTab);

	    return _this;
	  }

	  babelHelpers.createClass(CommonContentBlocks, [{
	    key: "getContentBlockComponents",
	    value: function getContentBlockComponents(Item) {
	      return {
	        TextBlock: Text,
	        LinkBlock: Link,
	        WithTitle,
	        LineOfTextBlocks,
	        TimelineAudio,
	        ClientMark: ClientMark$1,
	        Money,
	        EditableText,
	        EditableDate,
	        PlayerAlert,
	        DatePill,
	        Note
	      };
	    }
	    /**
	     * Process common events that aren't bound to specific item type
	     */

	  }, {
	    key: "onItemAction",
	    value: function onItemAction(item, action, actionData) {
	      if (action === 'Item:OpenEntityDetailTab' && main_core.Type.isStringFilled(actionData === null || actionData === void 0 ? void 0 : actionData.tabId)) {
	        _classPrivateMethodGet$3(this, _openEntityDetailTab, _openEntityDetailTab2).call(this, actionData.tabId);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return true; // common blocks can be used anywhere
	    }
	  }]);
	  return CommonContentBlocks;
	}(Base);

	function _openEntityDetailTab2(tabId) {
	  // the event is handled by compatible code, it's a pain to use EventEmitter in this case
	  // eslint-disable-next-line bitrix-rules/no-bx
	  BX.onCustomEvent(window, 'OpenEntityDetailTab', [tabId]);
	}

	var ValueChange = {
	  props: {
	    from: String,
	    to: String
	  },
	  // language=Vue
	  template: `<div class="crm-entity-stream-content-detail-info">
	<span class="crm-entity-stream-content-detain-info-status" v-if="from">{{from}}</span>
	<span class="crm-entity-stream-content-detail-info-separator-icon" v-if="from"></span>
	<span class="crm-entity-stream-content-detain-info-status" v-if="to">{{to}}</span>
	</div>`
	};

	let Modification = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Modification, _Base);

	  function Modification() {
	    babelHelpers.classCallCheck(this, Modification);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Modification).apply(this, arguments));
	  }

	  babelHelpers.createClass(Modification, [{
	    key: "getContentBlockComponents",
	    value: function getContentBlockComponents(Item) {
	      return {
	        ValueChange
	      };
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'Modification';
	    }
	  }]);
	  return Modification;
	}(Base);

	var ChatMessage = {
	  props: {
	    messageHtml: String,
	    isIncoming: Boolean
	  },
	  computed: {
	    className() {
	      return 'crm-entity-stream-content-detail-IM-message-' + (this.isIncoming ? 'incoming' : 'outgoing');
	    }

	  },
	  // language=Vue
	  template: `<div class="crm-entity-stream-content-detail-IM"><div :class="[className]" v-html="messageHtml"></div></div>`
	};

	function _classPrivateMethodInitSpec$4(obj, privateSet) { _checkPrivateRedeclaration$7(obj, privateSet); privateSet.add(obj); }

	function _checkPrivateRedeclaration$7(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classPrivateMethodGet$4(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _openChat = /*#__PURE__*/new WeakSet();

	let OpenLines = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(OpenLines, _Base);

	  function OpenLines(...args) {
	    var _this;

	    babelHelpers.classCallCheck(this, OpenLines);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(OpenLines).call(this, ...args));

	    _classPrivateMethodInitSpec$4(babelHelpers.assertThisInitialized(_this), _openChat);

	    return _this;
	  }

	  babelHelpers.createClass(OpenLines, [{
	    key: "getContentBlockComponents",
	    value: function getContentBlockComponents(Item) {
	      return {
	        ChatMessage
	      };
	    }
	  }, {
	    key: "onItemAction",
	    value: function onItemAction(item, action, actionData) {
	      if (action === 'Openline:OpenChat' && actionData && actionData.dialogId) {
	        _classPrivateMethodGet$4(this, _openChat, _openChat2).call(this, actionData.dialogId);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'Activity:OpenLine';
	    }
	  }]);
	  return OpenLines;
	}(Base);

	function _openChat2(dialogId) {
	  if (window.top['BXIM']) {
	    window.top['BXIM'].openMessengerSlider(dialogId, {
	      RECENT: 'N',
	      MENU: 'N'
	    });
	  }
	}

	function _classPrivateMethodInitSpec$5(obj, privateSet) { _checkPrivateRedeclaration$8(obj, privateSet); privateSet.add(obj); }

	function _checkPrivateRedeclaration$8(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classPrivateMethodGet$5(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _deleteEntry = /*#__PURE__*/new WeakSet();

	var _openDocument = /*#__PURE__*/new WeakSet();

	var _modifyDocument = /*#__PURE__*/new WeakSet();

	var _updateActivityDeadline = /*#__PURE__*/new WeakSet();

	var _resendDocument = /*#__PURE__*/new WeakSet();

	var _touchSigner = /*#__PURE__*/new WeakSet();

	var _download = /*#__PURE__*/new WeakSet();

	let SignDocument = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(SignDocument, _Base);

	  function SignDocument(...args) {
	    var _this;

	    babelHelpers.classCallCheck(this, SignDocument);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SignDocument).call(this, ...args));

	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _download);

	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _touchSigner);

	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _resendDocument);

	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _updateActivityDeadline);

	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _modifyDocument);

	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _openDocument);

	    _classPrivateMethodInitSpec$5(babelHelpers.assertThisInitialized(_this), _deleteEntry);

	    return _this;
	  }

	  babelHelpers.createClass(SignDocument, [{
	    key: "onItemAction",
	    value: function onItemAction(item, action, actionData) {
	      const documentId = main_core.Text.toInteger(actionData === null || actionData === void 0 ? void 0 : actionData.documentId);
	      const activityId = main_core.Text.toInteger(actionData === null || actionData === void 0 ? void 0 : actionData.activityId);

	      if ((action === 'SignDocument:Open' || action === 'Activity:SignDocument:Open') && documentId > 0) {
	        _classPrivateMethodGet$5(this, _openDocument, _openDocument2).call(this, actionData);
	      } else if ((action === 'SignDocument:Modify' || action === 'Activity:SignDocument:Modify') && documentId > 0) {
	        _classPrivateMethodGet$5(this, _modifyDocument, _modifyDocument2).call(this, actionData);
	      } else if ((action === 'SignDocument:UpdateActivityDeadline' || action === 'Activity:SignDocument:UpdateActivityDeadline') && activityId > 0) {
	        _classPrivateMethodGet$5(this, _updateActivityDeadline, _updateActivityDeadline2).call(this, activityId, actionData === null || actionData === void 0 ? void 0 : actionData.value);
	      } else if (action === 'SignDocument:Resend' && documentId > 0 && actionData !== null && actionData !== void 0 && actionData.recipientHash) {
	        _classPrivateMethodGet$5(this, _resendDocument, _resendDocument2).call(this, actionData);
	      } else if (action === 'SignDocument:TouchSigner' && documentId > 0) {
	        _classPrivateMethodGet$5(this, _touchSigner, _touchSigner2).call(this, actionData);
	      } else if (action === 'SignDocument:Download' && documentId > 0) {
	        _classPrivateMethodGet$5(this, _download, _download2).call(this, actionData);
	      } else if (action === 'SignDocumentEntry:Delete' && actionData !== null && actionData !== void 0 && actionData.entryId) {
	        ui_dialogs_messagebox.MessageBox.show({
	          message: (actionData === null || actionData === void 0 ? void 0 : actionData.confirmationText) || '',
	          modal: true,
	          buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_NO,
	          onYes: () => {
	            return _classPrivateMethodGet$5(this, _deleteEntry, _deleteEntry2).call(this, actionData.entryId);
	          },
	          onNo: messageBox => {
	            messageBox.close();
	          }
	        });
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'SignDocument' || item.getType() === 'Activity:SignDocument';
	    }
	  }]);
	  return SignDocument;
	}(Base);

	function _deleteEntry2(entryId) {
	  console.log('delete entry' + entryId);
	}

	function _openDocument2({
	  documentId,
	  memberHash
	}) {
	  return crm_router.Router.Instance.openSignDocumentSlider(documentId, memberHash);
	}

	function _modifyDocument2({
	  documentId
	}) {
	  return crm_router.Router.Instance.openSignDocumentModifySlider(documentId);
	}

	async function _updateActivityDeadline2(activityId, value) {
	  var _response$data$docume;

	  const valueInSiteFormat = main_date.DateTimeFormat.format(crm_timeline_tools.DatetimeConverter.getSiteDateFormat(), value);
	  let response;

	  try {
	    response = await main_core.ajax.runAction('crm.timeline.signdocument.updateActivityDeadline', {
	      data: {
	        activityId: activityId,
	        activityDeadline: valueInSiteFormat
	      }
	    });
	  } catch (responseWithError) {
	    console.error(responseWithError);
	    return;
	  }

	  const newCreateDate = (_response$data$docume = response.data.document) === null || _response$data$docume === void 0 ? void 0 : _response$data$docume.activityDeadline;

	  if (valueInSiteFormat !== newCreateDate) {
	    console.error("Updated document create date without errors, but for some reason date from the backend doesn't match sent value");
	  }
	}

	function _resendDocument2({
	  documentId,
	  recipientHash
	}) {
	  main_core.ajax.runAction('sign.document.resendFile', {
	    data: {
	      memberHash: recipientHash,
	      documentId: documentId
	    }
	  }).then(() => {
	    ui_notification.UI.Notification.Center.notify({
	      content: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_SIGN_DOCUMENT_RESEND_SUCCESS'),
	      autoHideDelay: 5000
	    });
	  }, response => {
	    ui_notification.UI.Notification.Center.notify({
	      content: response.errors[0].message,
	      autoHideDelay: 5000
	    });
	  });
	  console.log('resend document ' + documentId + ' for ' + recipientHash);
	}

	function _touchSigner2({
	  documentId
	}) {
	  console.log('touch signer document ' + documentId);
	}

	function _download2({
	  documentHash,
	  memberHash
	}) {
	  console.log('download ' + documentHash);
	  const req = main_core.ajax.xhr();
	  req.open("GET", '/bitrix/services/main/ajax.php?action=sign.document.getFileForSrc' + '&memberHash=' + memberHash + '&documentHash=' + documentHash, true);
	  req.responseType = "blob";

	  req.onload = oEvent => {
	    const blob = req.response;
	    const url = window.URL.createObjectURL(new Blob([blob]));
	    const link = document.createElement('a');
	    link.href = url;
	    link.setAttribute('download', 'doc.pdf');
	    document.body.appendChild(link);
	    link.click();
	    document.body.removeChild(link);
	  };

	  req.send();
	}

	function _classPrivateMethodInitSpec$6(obj, privateSet) { _checkPrivateRedeclaration$9(obj, privateSet); privateSet.add(obj); }

	function _checkPrivateRedeclaration$9(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classPrivateMethodGet$6(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	const ITEM_TYPE = 'Activity:Document';
	const ACTION_NAMESPACE = ITEM_TYPE + ':';

	var _openDocument$1 = /*#__PURE__*/new WeakSet();

	var _copyPublicLink = /*#__PURE__*/new WeakSet();

	var _createPublicUrl = /*#__PURE__*/new WeakSet();

	var _printDocument = /*#__PURE__*/new WeakSet();

	var _downloadPdf = /*#__PURE__*/new WeakSet();

	var _downloadDocx = /*#__PURE__*/new WeakSet();

	var _updateTitle = /*#__PURE__*/new WeakSet();

	var _updateCreateDate = /*#__PURE__*/new WeakSet();

	let Document = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Document, _Base);

	  function Document(...args) {
	    var _this;

	    babelHelpers.classCallCheck(this, Document);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Document).call(this, ...args));

	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _updateCreateDate);

	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _updateTitle);

	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _downloadDocx);

	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _downloadPdf);

	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _printDocument);

	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _createPublicUrl);

	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _copyPublicLink);

	    _classPrivateMethodInitSpec$6(babelHelpers.assertThisInitialized(_this), _openDocument$1);

	    return _this;
	  }

	  babelHelpers.createClass(Document, [{
	    key: "onItemAction",
	    value: function onItemAction(item, action, actionData) {
	      const documentId = main_core.Text.toInteger(actionData === null || actionData === void 0 ? void 0 : actionData.documentId);

	      if (documentId <= 0) {
	        return;
	      }

	      if (action === ACTION_NAMESPACE + 'Open') {
	        _classPrivateMethodGet$6(this, _openDocument$1, _openDocument2$1).call(this, documentId);
	      } else if (action === ACTION_NAMESPACE + 'CopyPublicLink') {
	        // todo block button while loading
	        _classPrivateMethodGet$6(this, _copyPublicLink, _copyPublicLink2).call(this, documentId, actionData === null || actionData === void 0 ? void 0 : actionData.publicUrl);
	      } else if (action === ACTION_NAMESPACE + 'Print') {
	        _classPrivateMethodGet$6(this, _printDocument, _printDocument2).call(this, actionData === null || actionData === void 0 ? void 0 : actionData.printUrl);
	      } else if (action === ACTION_NAMESPACE + 'DownloadPdf') {
	        _classPrivateMethodGet$6(this, _downloadPdf, _downloadPdf2).call(this, actionData === null || actionData === void 0 ? void 0 : actionData.pdfUrl);
	      } else if (action === ACTION_NAMESPACE + 'DownloadDocx') {
	        _classPrivateMethodGet$6(this, _downloadDocx, _downloadDocx2).call(this, actionData === null || actionData === void 0 ? void 0 : actionData.docxUrl);
	      } else if (action === ACTION_NAMESPACE + 'UpdateTitle') {
	        _classPrivateMethodGet$6(this, _updateTitle, _updateTitle2).call(this, documentId, actionData === null || actionData === void 0 ? void 0 : actionData.value);
	      } else if (action === ACTION_NAMESPACE + 'UpdateCreateDate') {
	        _classPrivateMethodGet$6(this, _updateCreateDate, _updateCreateDate2).call(this, documentId, actionData === null || actionData === void 0 ? void 0 : actionData.value);
	      } else {
	        console.info(`Unknown action ${action} in ${ITEM_TYPE}`);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === ITEM_TYPE;
	    }
	  }]);
	  return Document;
	}(Base);

	function _openDocument2$1(documentId) {
	  crm_router.Router.Instance.openDocumentSlider(documentId);
	}

	async function _copyPublicLink2(documentId, publicUrl) {
	  if (!main_core.Type.isStringFilled(publicUrl)) {
	    try {
	      publicUrl = await _classPrivateMethodGet$6(this, _createPublicUrl, _createPublicUrl2).call(this, documentId);
	    } catch (error) {
	      ui_dialogs_messagebox.MessageBox.alert(error.message);
	      return;
	    }
	  }

	  const isSuccess = BX.clipboard.copy(publicUrl);

	  if (isSuccess) {
	    ui_notification.UI.Notification.Center.notify({
	      content: main_core.Loc.getMessage('CRM_TIMELINE_ITEM_LINK_IS_COPIED'),
	      autoHideDelay: 5000
	    });
	  } else {
	    ui_dialogs_messagebox.MessageBox.alert(main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DOCUMENT_COPY_PUBLIC_LINK_ERROR'));
	  }
	}

	async function _createPublicUrl2(documentId) {
	  let response;

	  try {
	    response = await main_core.ajax.runAction('crm.documentgenerator.document.enablePublicUrl', {
	      analyticsLabel: 'enablePublicUrl',
	      data: {
	        status: 1,
	        id: documentId
	      }
	    });
	  } catch (responseWithError) {
	    console.error(responseWithError);
	    throw new Error(main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DOCUMENT_CREATE_PUBLIC_LINK_ERROR'));
	  }

	  const publicUrl = response.data.publicUrl;

	  if (!main_core.Type.isStringFilled(publicUrl)) {
	    throw new Error(main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DOCUMENT_CREATE_PUBLIC_LINK_ERROR'));
	  }

	  return publicUrl;
	}

	function _printDocument2(printUrl) {
	  if (main_core.Type.isStringFilled(printUrl)) {
	    window.open(printUrl, '_blank');
	  } else {
	    ui_dialogs_messagebox.MessageBox.alert(main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DOCUMENT_PRINT_NOT_READY'));
	  }
	}

	function _downloadPdf2(pdfUrl) {
	  if (main_core.Type.isStringFilled(pdfUrl)) {
	    window.open(pdfUrl, '_blank');
	  } else {
	    ui_dialogs_messagebox.MessageBox.alert(main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DOCUMENT_PDF_NOT_READY'));
	  }
	}

	function _downloadDocx2(docxUrl) {
	  if (main_core.Type.isStringFilled(docxUrl)) {
	    window.open(docxUrl, '_blank');
	  } else {
	    console.error('Docx download url is not found. This should be an impossible case, something went wrong');
	  }
	}

	async function _updateTitle2(documentId, value) {
	  var _response$data$docume, _response$data$docume2;

	  let response;

	  try {
	    response = await main_core.ajax.runAction('crm.documentgenerator.document.update', {
	      data: {
	        id: documentId,
	        values: {
	          DocumentTitle: value
	        }
	      }
	    });
	  } catch (responseWithError) {
	    console.error(responseWithError);
	    ui_dialogs_messagebox.MessageBox.alert(main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DOCUMENT_UPDATE_DOCUMENT_ERROR'));
	    return;
	  }

	  const newTitle = (_response$data$docume = response.data.document) === null || _response$data$docume === void 0 ? void 0 : (_response$data$docume2 = _response$data$docume.values) === null || _response$data$docume2 === void 0 ? void 0 : _response$data$docume2.DocumentTitle;

	  if (newTitle !== value) {
	    console.error("Updated document title without errors, but for some reason title from the backend doesn't match sent value");
	  }
	}

	async function _updateCreateDate2(documentId, value) {
	  var _response$data$docume3, _response$data$docume4;

	  const valueInSiteFormat = main_date.DateTimeFormat.format(crm_timeline_tools.DatetimeConverter.getSiteDateFormat(), value);
	  let response;

	  try {
	    response = await main_core.ajax.runAction('crm.documentgenerator.document.update', {
	      data: {
	        id: documentId,
	        values: {
	          DocumentCreateTime: valueInSiteFormat
	        }
	      }
	    });
	  } catch (responseWithError) {
	    console.error(responseWithError);
	    ui_dialogs_messagebox.MessageBox.alert(main_core.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DOCUMENT_UPDATE_DOCUMENT_ERROR'));
	    return;
	  }

	  const newCreateDate = (_response$data$docume3 = response.data.document) === null || _response$data$docume3 === void 0 ? void 0 : (_response$data$docume4 = _response$data$docume3.values) === null || _response$data$docume4 === void 0 ? void 0 : _response$data$docume4.DocumentCreateTime;

	  if (valueInSiteFormat !== newCreateDate) {
	    console.error("Updated document create date without errors, but for some reason date from the backend doesn't match sent value");
	  }
	}

	function _classPrivateMethodInitSpec$7(obj, privateSet) { _checkPrivateRedeclaration$a(obj, privateSet); privateSet.add(obj); }

	function _checkPrivateRedeclaration$a(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classPrivateMethodGet$7(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _makeCall = /*#__PURE__*/new WeakSet();

	var _scheduleCall = /*#__PURE__*/new WeakSet();

	var _openTranscript = /*#__PURE__*/new WeakSet();

	let Call = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Call, _Base);

	  function Call(...args) {
	    var _this;

	    babelHelpers.classCallCheck(this, Call);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Call).call(this, ...args));

	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this), _openTranscript);

	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this), _scheduleCall);

	    _classPrivateMethodInitSpec$7(babelHelpers.assertThisInitialized(_this), _makeCall);

	    return _this;
	  }

	  babelHelpers.createClass(Call, [{
	    key: "onItemAction",
	    value: function onItemAction(item, action, actionData) {
	      if (action === 'Call:MakeCall' && actionData) {
	        _classPrivateMethodGet$7(this, _makeCall, _makeCall2).call(this, actionData);
	      }

	      if (action === 'Call:Schedule' && actionData && actionData.activityId) {
	        _classPrivateMethodGet$7(this, _scheduleCall, _scheduleCall2).call(this, actionData.activityId);
	      }

	      if (action === 'Call:OpenTranscript' && actionData && actionData.callId) {
	        _classPrivateMethodGet$7(this, _openTranscript, _openTranscript2).call(this, actionData.callId);
	      }
	    }
	  }], [{
	    key: "isItemSupported",
	    value: function isItemSupported(item) {
	      return item.getType() === 'Activity:Call';
	    }
	  }]);
	  return Call;
	}(Base);

	function _makeCall2(actionData) {
	  if (!main_core.Type.isStringFilled(actionData.phone)) {
	    return;
	  }

	  const params = {
	    ENTITY_TYPE_NAME: BX.CrmEntityType.resolveName(actionData.entityTypeId),
	    ENTITY_ID: actionData.entityId,
	    AUTO_FOLD: true
	  };

	  if (actionData.ownerTypeId !== actionData.entityTypeId || actionData.ownerId !== actionData.entityId) {
	    params.BINDINGS = {
	      OWNER_TYPE_NAME: BX.CrmEntityType.resolveName(actionData.ownerTypeId),
	      OWNER_ID: actionData.ownerId
	    };
	  }

	  if (actionData.activityId > 0) {
	    params.SRC_ACTIVITY_ID = actionData.activityId;
	  }

	  window.top['BXIM'].phoneTo(actionData.phone, params);
	}

	function _scheduleCall2(activityId) {
	  console.warn('Not implemented yet');
	}

	function _openTranscript2(callId) {
	  if (BX.Voximplant && BX.Voximplant.Transcript) {
	    BX.Voximplant.Transcript.create({
	      callId: callId
	    }).show();
	  }
	}

	ControllerManager.registerController(Activity);
	ControllerManager.registerController(CommonContentBlocks);
	ControllerManager.registerController(OpenLines);
	ControllerManager.registerController(Modification);
	ControllerManager.registerController(SignDocument);
	ControllerManager.registerController(Document);
	ControllerManager.registerController(Call);

	exports.Item = Item;
	exports.ConfigurableItem = ConfigurableItem;
	exports.StreamType = StreamType;

}((this.BX.Crm.Timeline = this.BX.Crm.Timeline || {}),BX.UI,BX.UI,BX.Main,BX.Event,BX.Crm.Timeline,BX.Vue3.Components,BX.UI,BX.Currency,BX.Vue3,BX.UI,BX.Main,BX.Crm,BX.UI.Dialogs,BX.Crm.Timeline,BX,BX));
//# sourceMappingURL=index.bundle.js.map
