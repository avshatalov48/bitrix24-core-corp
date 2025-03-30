/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,ui_vue3,ui_alerts,ui_entitySelector,ui_avatar,ui_iconSet_api_core,main_popup,ui_iconSet_actions,im_v2_lib_dateFormatter,ui_buttons,ui_iconSet_api_vue,humanresources_hcmlink_api,main_core,main_core_events,ui_sidepanel_layout) {
	'use strict';

	const Separator = {
	  name: 'Separator',
	  props: {
	    hasLink: {
	      required: true,
	      type: Boolean
	    },
	    mode: {
	      type: String,
	      required: true
	    }
	  },
	  computed: {
	    styleObject() {
	      return {
	        '--ui-icon-set__icon-color': this.hasLink ? '#FFC34D' : '#D5D7DB'
	      };
	    },
	    iconClasses() {
	      return {
	        '--arrow-right': this.hasLink,
	        '--delete-hyperlink': !this.hasLink,
	        '--color-orange': this.hasLink && this.mode === 'direct',
	        '--color-blue': this.hasLink && this.mode === 'reverse'
	      };
	    }
	  },
	  template: `
		<div class="hr-hcmlink-separator__container" ref="container">
			<div
				class="ui-icon-set hr-hcmlink-separator__container-icon"
				:class="iconClasses"
			></div>
		</div>
	`
	};

	const PersonItem = {
	  name: 'PersonItem',
	  props: {
	    config: {
	      required: true,
	      type: {
	        companyId: Number,
	        mode: String,
	        isHideInfoAlert: Boolean
	      }
	    },
	    mappedUserIds: {
	      required: true,
	      type: Array
	    },
	    suggestId: {
	      required: false,
	      type: [Number, null]
	    }
	  },
	  data() {
	    return {
	      isBorderedEmployee: this.config.mode === 'direct'
	    };
	  },
	  emits: ['addEntity', 'removeEntity'],
	  mounted() {
	    const selector = this.config.mode === 'direct' ? this.getPersonTagSelector() : this.getUserTagSelector();
	    selector.renderTo(this.$refs.container);
	  },
	  methods: {
	    getUserTagSelector() {
	      let preselectedItem = [];
	      if (main_core.Type.isNumber(this.suggestId)) {
	        preselectedItem = ['user', this.suggestId];
	        this.$emit('addEntity', {
	          id: this.suggestId
	        });
	      }
	      const selector = new ui_entitySelector.TagSelector({
	        multiple: false,
	        events: {
	          onTagRemove: event => {
	            const {
	              tag
	            } = event.getData();
	            this.handleItemRemove(tag);
	          },
	          onTagAdd: event => {
	            const {
	              tag
	            } = event.getData();
	            this.handleItemSelect(tag);
	          }
	        },
	        addButtonCaption: main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SELECTOR_BUTTON_CAPTION'),
	        showCreateButton: false,
	        dialogOptions: {
	          id: 'hcmlink-user-dialog',
	          width: 380,
	          searchOptions: {
	            allowCreateItem: false
	          },
	          entities: [{
	            id: 'user',
	            options: {
	              '!userId': this.mappedUserIds,
	              inviteEmployeeLink: false,
	              intranetUsersOnly: true
	            }
	          }],
	          preselectedItems: [preselectedItem],
	          tabs: [{
	            id: 'user',
	            title: main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_DIALOG_TAB_TITLE_USER')
	          }],
	          recentTabOptions: {
	            visible: false
	          }
	        }
	      });
	      main_core.Dom.addClass(selector.getOuterContainer(), 'hr-hcmlink-item-employee__user-container');
	      return selector;
	    },
	    getPersonTagSelector() {
	      let preselectedItem = [];
	      if (main_core.Type.isNumber(this.suggestId)) {
	        preselectedItem = ['hcmlink-person-data', this.suggestId];
	        this.$emit('addEntity', {
	          id: this.suggestId
	        });
	      }
	      const selector = new ui_entitySelector.TagSelector({
	        multiple: false,
	        events: {
	          onTagRemove: event => {
	            const {
	              tag
	            } = event.getData();
	            this.handleItemRemove(tag);
	          },
	          onTagAdd: event => {
	            const {
	              tag
	            } = event.getData();
	            this.handleItemSelect(tag);
	          }
	        },
	        addButtonCaption: main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SELECTOR_BUTTON_CAPTION'),
	        showCreateButton: false,
	        tagTextColor: '#333',
	        tagBgColor: '#FFF1D6',
	        tagFontWeight: '400',
	        dialogOptions: {
	          id: 'hcmlink-person-dialog',
	          enableSearch: true,
	          width: 380,
	          searchOptions: {
	            allowCreateItem: false
	          },
	          entities: [{
	            id: 'hcmlink-person-data',
	            options: {
	              companyId: this.config.companyId,
	              inviteEmployeeLink: false
	            },
	            dynamicLoad: true,
	            dynamicSearch: true,
	            enableSearch: true
	          }],
	          preselectedItems: [preselectedItem],
	          tabs: [{
	            id: 'persons',
	            title: main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_DIALOG_TAB_TITLE_PERSON')
	          }],
	          recentTabOptions: {
	            visible: false
	          }
	        }
	      });
	      main_core.Dom.addClass(selector.getOuterContainer(), 'hr-hcmlink-item-employee__person-container');
	      return selector;
	    },
	    handleItemRemove(tag) {
	      this.$emit('removeEntity', {
	        id: tag.id
	      });
	    },
	    handleItemSelect(tag) {
	      this.$emit('addEntity', {
	        id: tag.id
	      });
	    }
	  },
	  template: `
		<div 
			class="hr-hcmlink-item-employee__container"
			ref="container"
		></div>
	`
	};

	const UserItem = {
	  name: 'UserItem',
	  props: {
	    item: {
	      type: Object,
	      required: true
	    },
	    mode: {
	      type: String,
	      required: true
	    }
	  },
	  mounted() {
	    if (this.mode === 'direct') {
	      this.getUserAvatarEntity().renderTo(this.$refs.avatarContainer);
	    }
	  },
	  methods: {
	    getUserAvatarEntity() {
	      return new ui_avatar.AvatarRound({
	        size: 36,
	        userName: this.item.name,
	        baseColor: '#FF7C78',
	        userpicPath: this.item.avatarLink
	      });
	    }
	  },
	  template: `
		<div 
			class="hr-hcmlink-item-user__container"
			:class="{'hr-hcmlink-item-user__container_person': mode === 'reverse'}"
			ref="container"
		>
			<div v-if="this.mode === 'direct'" class="hr-hcmlink-item-user__avatar" ref="avatarContainer"></div>
			<div class="hr-hcmlink-item-user_info">
				<div class="hr-hcmlink-item-user__info-name">{{ item.name }}</div>
				<div class="hr-hcmlink-item-user__info-position">{{ item.position }}</div>
			</div>
		</div>
	`
	};

	const MoreOptions = {
	  name: 'MoreOption',
	  methods: {
	    showMenu() {
	      const popupMenu = main_popup.PopupMenu.create({
	        id: 'humanresources-mapper-menu-line-option',
	        autoHide: true,
	        bindElement: this.$refs.container,
	        items: [{
	          text: 'first',
	          onclick: (event, item) => {
	            item.menuWindow.close();
	          }
	        }, {
	          text: 'second',
	          onclick: (event, item) => {
	            item.menuWindow.close();
	          }
	        }]
	      });
	      popupMenu.show();
	    }
	  },
	  mounted() {
	    new ui_iconSet_api_core.Icon({
	      icon: ui_iconSet_api_core.Main.MORE_INFORMATION,
	      size: 24,
	      color: getComputedStyle(document.body).getPropertyValue('--ui-color-palette-gray-30')
	    }).renderTo(this.$refs.container);
	  },
	  template: `
		<div class="hr-hcmlink-more-options__container">
			<span 
				ref="container"
				@click="showMenu"
			></span>
		</div>
	`
	};

	const Line = {
	  name: 'Line',
	  props: {
	    item: {
	      type: Object,
	      required: true
	    },
	    mappedUserIds: {
	      type: Array,
	      required: true
	    },
	    config: {
	      required: true,
	      type: {
	        companyId: Number,
	        mode: String,
	        isHideInfoAlert: Boolean
	      }
	    }
	  },
	  data() {
	    return {
	      hasLink: false
	    };
	  },
	  emits: ['createLink', 'removeLink'],
	  components: {
	    PersonItem,
	    UserItem,
	    Separator,
	    MoreOptions
	  },
	  methods: {
	    onAddEntity(options) {
	      if (this.config.mode === 'direct') {
	        this.$emit('createLink', {
	          userId: this.item.id,
	          personId: options.id
	        });
	        this.hasLink = true;
	      } else {
	        this.$emit('createLink', {
	          userId: options.id,
	          personId: this.item.id
	        });
	        this.hasLink = true;
	      }
	    },
	    onRemoveEntity(options) {
	      const userId = this.config.mode === 'direct' ? this.item.id : options.id;
	      this.$emit('removeLink', {
	        userId
	      });
	      this.hasLink = false;
	    }
	  },
	  template: `
		<div class="hr-hcmlink-sync__line-container">
			<div class="hr-hcmlink-sync__line-left-container">
				<UserItem
					:item=item
				    :mode="config.mode"
				></UserItem>
				<Separator
					:hasLink=hasLink
					:mode="config.mode"
				></Separator>
			</div>
			<div class="hr-hcmlink-sync__line-right-container" :class="this.config.mode === 'direct' ? '--person' : '--user'">
				<PersonItem
					:config=config
					:suggestId="item.suggestId"
					:mappedUserIds=mappedUserIds
					@addEntity="onAddEntity"
					@removeEntity="onRemoveEntity"
				></PersonItem>
			</div>
		</div>
	`
	};

	const ColumnTitle = {
	  name: 'ColumnTitle',
	  props: {
	    mode: {
	      type: String,
	      required: true
	    }
	  },
	  template: `
		<template v-if="mode === 'direct'">
			<div class="hr-hcmlink-sync__column-title-container">
				<div>
					{{ $Bitrix.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_COLUMN_TITLE_BITRIX') }}
				</div>
				<div>
					{{ $Bitrix.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_COLUMN_TITLE_ZUP') }}
				</div>
			</div>
		</template>
		<template v-else>
			<div class="hr-hcmlink-sync__column-title-container">
				<div>
					{{ $Bitrix.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_COLUMN_TITLE_ZUP') }}
				</div>
				<div>
					{{ $Bitrix.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_COLUMN_TITLE_BITRIX') }}
				</div>
			</div>
		</template>
	`
	};

	const EventList = Object.freeze({
	  HR_DATA_MAPPER_FOOTER_DISPLAY: 'hr-data-mapper-footer-display',
	  HR_DATA_MAPPER_FORCE_SYNC: 'hr-data-mapper-force-sync',
	  HR_DATA_MAPPER_DATA_WAS_SAVED: 'hr-data-mapper-data-was-saved',
	  HR_DATA_MAPPER_CLEAR_SEARCH_INPUT: 'hr-data-mapper-clear-search-input'
	});
	const Status = Object.freeze({
	  done: 'done',
	  pending: 'pending',
	  loading: 'loading',
	  salaryDone: 'salaryDone',
	  searchNotFound: 'searchNotFound'
	});

	const Counter = {
	  name: 'Counter',
	  props: {
	    countAllPersonsForMap: {
	      required: true,
	      type: Number
	    },
	    countMappedPersons: {
	      required: true,
	      type: Number
	    },
	    countUnmappedPersons: {
	      required: true,
	      type: Number
	    },
	    mode: {
	      required: true,
	      type: String
	    },
	    lastJobFinishedAt: {
	      required: false,
	      type: Date,
	      default: null
	    }
	  },
	  computed: {
	    leftCounterPhrase() {
	      return main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_PAGE_UNMAPPED_TITLE_MSGVER_1', {
	        '[SPAN]': '<span class="hr-hcmlink-sync__counter_count-accent">',
	        '[/SPAN]': '</span>',
	        '#COUNT#': main_core.Text.encode(this.countUnmappedPersons)
	      });
	    },
	    formatDate() {
	      if (this.lastJobFinishedAt) {
	        return main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_COUNTER_RIGHT', {
	          '#FORMATTED_DATE#': im_v2_lib_dateFormatter.DateFormatter.formatByTemplate(this.lastJobFinishedAt, im_v2_lib_dateFormatter.DateTemplate.messageReadStatus)
	        });
	      }
	      return main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_COUNTER_RIGHT', {
	        '#FORMATTED_DATE#': main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_COUNTER_RIGHT_DATE_NEVER')
	      });
	    }
	  },
	  methods: {
	    forceSync() {
	      main_core_events.EventEmitter.emit(EventList.HR_DATA_MAPPER_FORCE_SYNC);
	    }
	  },
	  template: `
		<div v-html="leftCounterPhrase" class="hr-hcmlink-sync__toolbar-bubble hr-hcmlink-sync__counter_container-left"/>
		<div class="hr-hcmlink-sync__toolbar-bubble hr-hcmlink-sync__counter_container-right">
			<div class="hr-hcmlink-sync__toolbar-format-date">{{formatDate}}</div>
			<div class="hr-hcmlink-sync__toolbar-separator"></div>
			<div 
				class="hr-hcmlink-sync__toolbar-update-button"
				@click="forceSync"
			>
				{{ $Bitrix.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_COUNTER_RIGHT_UPDATE_BUTTON') }}
			</div>
		</div>
	`
	};

	const StateScreen = {
	  name: 'StateScreen',
	  props: {
	    status: {
	      required: true,
	      type: String
	    },
	    isBlock: {
	      type: Boolean,
	      default: false
	    },
	    mode: {
	      required: true,
	      type: String
	    }
	  },
	  emits: ['completeMapping', 'abortSync'],
	  mounted() {
	    this.getCloseButton().renderTo(this.$refs.buttonContainer);
	    this.getAbortSyncButton().renderTo(this.$refs.abortSyncButtonContainer);
	  },
	  methods: {
	    getCloseButton() {
	      const text = this.status === Status.done ? main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_BUTTON_CLOSE') : main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_BUTTON_SALARY_CLOSE');
	      return new ui_buttons.Button({
	        color: ui_buttons.Button.Color.LIGHT_BORDER,
	        round: true,
	        size: ui_buttons.Button.Size.LARGE,
	        text,
	        onclick: () => {
	          this.$emit('completeMapping');
	          BX.SidePanel.Instance.getTopSlider().close();
	        }
	      });
	    },
	    getAbortSyncButton() {
	      return new ui_buttons.Button({
	        color: ui_buttons.Button.Color.LIGHT_BORDER,
	        round: true,
	        size: ui_buttons.Button.Size.LARGE,
	        text: main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_ABORT_LOAD_BUTTON'),
	        onclick: () => {
	          this.$emit('abortSync');
	        }
	      });
	    }
	  },
	  computed: {
	    title() {
	      switch (this.status) {
	        case Status.loading:
	          return main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_TITLE_STATUS_LOADING');
	        case Status.pending:
	          return main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_TITLE_STATUS_PENDING');
	        case Status.done:
	          return main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_TITLE_STATUS_DONE');
	        case Status.salaryDone:
	          return main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_TITLE_STATUS_SALARY_DONE');
	        case Status.searchNotFound:
	          return this.mode === 'direct' ? main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_TITLE_STATUS_SEARCH_NOT_FOUND_DIRECT') : main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_TITLE_STATUS_SEARCH_NOT_FOUND_REVERSE');
	        default:
	          return '';
	      }
	    },
	    description() {
	      switch (this.status) {
	        case Status.loading:
	          return main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_DESCRIPTION_STATUS_LOADING');
	        case Status.pending:
	          return main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_DESCRIPTION_STATUS_PENDING_MSGVER_1');
	        case Status.done:
	          return main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_DESCRIPTION_STATUS_DONE');
	        case Status.salaryDone:
	          return main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_DESCRIPTION_STATUS_SALARY_DONE');
	        case Status.searchNotFound:
	          return main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_DESCRIPTION_STATUS_SEARCH_NOT_FOUND');
	        default:
	          return '';
	      }
	    },
	    stateClassList() {
	      return {
	        '--done': this.isDoneState,
	        '--pending': this.isPendingState,
	        '--search-not-found': this.status === Status.searchNotFound,
	        '--block': this.isBlock,
	        '--flex': !this.isBlock
	      };
	    },
	    isDoneState() {
	      return [Status.done, Status.salaryDone].includes(this.status);
	    },
	    isPendingState() {
	      return [Status.pending, Status.loading].includes(this.status);
	    }
	  },
	  template: `
		<div class="hr-hcmlink-mapping-person__state-screen" :class="stateClassList">
			<div class="hr-hcmlink-mapping-person__state-screen_icon"></div>
			<div 
				class="hr-hcmlink-mapping-person__state-screen_title"
				v-html="title"
			>
			</div>
			<div 
				class="hr-hcmlink-mapping-person__state-screen_desc"
				v-html="description"
			>
			</div>
			<div 
				v-if="isDoneState"
				class="hr-hcmlink-mapping-person__state-screen_close-button" ref="closeButtonContainer"
			></div>
			<div 
				v-if="status === 'pending'"
				class="hr-hcmlink-mapping-person__state-screen_abort-sync-button" ref="abortSyncButtonContainer"
			></div>
		</div>
	`
	};

	const SearchBar = {
	  name: 'SearchBar',
	  props: {
	    placeholder: {
	      required: false,
	      type: String
	    },
	    // how many milliseconds passes before emitting 'onSearch' event after input
	    debounceWait: {
	      required: false,
	      default: 0
	    }
	  },
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  directives: {
	    focus: {
	      mounted(el) {
	        el.focus();
	      }
	    }
	  },
	  data() {
	    return {
	      showSearchBar: false,
	      searchQuery: '',
	      debounceTimer: null
	    };
	  },
	  mounted() {
	    this.$Bitrix.eventEmitter.subscribe(EventList.HR_DATA_MAPPER_DATA_WAS_SAVED, this.clearInput);
	    this.$Bitrix.eventEmitter.subscribe(EventList.HR_DATA_MAPPER_CLEAR_SEARCH_INPUT, this.clearInput);
	  },
	  unmounted() {
	    this.$Bitrix.eventEmitter.unsubscribe(EventList.HR_DATA_MAPPER_DATA_WAS_SAVED, this.clearInput);
	    this.$Bitrix.eventEmitter.unsubscribe(EventList.HR_DATA_MAPPER_CLEAR_SEARCH_INPUT, this.clearInput);
	  },
	  emits: ['search'],
	  computed: {
	    IconSet() {
	      return ui_iconSet_api_vue.Set;
	    }
	  },
	  methods: {
	    onBlur() {
	      if (this.searchQuery.length === 0) {
	        this.searchQuery = '';
	        this.hideSearchbar();
	      }
	    },
	    clearInput() {
	      this.searchQuery = '';
	    },
	    toggleSearchbar() {
	      if (this.showSearchBar) {
	        this.showSearchBar = false;
	        this.searchQuery = '';
	        return;
	      }
	      this.showSearchBar = true;
	    },
	    onAfterEnter() {
	      if (this.$refs.searchNameInput) {
	        this.$refs.searchNameInput.focus();
	      }
	    },
	    hideSearchbar() {
	      this.showSearchBar = false;
	    },
	    clearSearch() {
	      if (this.$refs.searchNameInput) {
	        this.searchQuery = '';
	        this.$refs.searchNameInput.focus();
	      }
	    }
	  },
	  watch: {
	    searchQuery(query) {
	      if (this.debounceTimer) {
	        clearTimeout(this.debounceTimer);
	      }
	      this.debounceTimer = setTimeout(() => {
	        this.$emit('search', query);
	      }, this.debounceWait);
	    }
	  },
	  template: `
		<div
			class="hr-hcmlink-sync__content-search-container"
		>
			<transition
				name="hr-hcmlink-sync__search-transition"
				@after-enter="onAfterEnter"
				mode="out-in"
			>
				<div
					class="hr-hcmlink-sync__content-search-block__search"
					@click="toggleSearchbar"
					key="searchIcon"
					v-if="!showSearchBar"
				>
					<BIcon :name="IconSet.SEARCH_2" :size="24" class="hr-hcmlink-sync__search-icon"></BIcon>
				</div>
				<div
					class="hr-hcmlink-sync__content-search-block__search-bar"
					key="searchBar"
					v-else
				>
					<input
						ref="searchNameInput"
						v-model="searchQuery"
						v-focus
						type="text"
						:placeholder="!searchQuery ? placeholder : ''"
						class="hr-hcmlink-sync__content-search-block__search-input"
						@blur="onBlur"
					>
					<div
						@click="clearSearch"
						class="hr-hcmlink-sync__content-search-block__search-reset"
					>
						<div class="hr-hcmlink-sync__content-search-block__search-cursor"></div>
						<BIcon
							:name="IconSet.CROSS_30"
							:size="24"
							color="#959ca4"
						></BIcon>
					</div>
				</div>
			</transition>
		</div>
	`
	};

	let _ = t => t,
	  _t;
	const HELPDESK_CODE = '23343056';
	const Page = {
	  name: 'Page',
	  props: {
	    collection: {
	      required: true,
	      type: Array
	    },
	    mappedUserIds: {
	      required: true,
	      type: Array
	    },
	    config: {
	      required: true,
	      type: {
	        companyId: Number,
	        isHideInfoAlert: Boolean,
	        mode: 'direct' | 'reverse'
	      }
	    },
	    searchActive: {
	      required: true,
	      type: Boolean
	    },
	    dataLoading: {
	      required: true,
	      type: Boolean
	    }
	  },
	  components: {
	    Line,
	    ColumnTitle,
	    Counter,
	    StateScreen,
	    SearchBar
	  },
	  emits: ['createLink', 'removeLink', 'closeAlert', 'search'],
	  mounted() {
	    if (!this.config.isHideInfoAlert) {
	      this.createAlert();
	    }
	  },
	  computed: {
	    isSearchResultEmpty() {
	      return this.collection.length === 0 && this.searchActive;
	    },
	    searchPlaceholder() {
	      return this.config.mode === 'direct' ? main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_USERS_SEARCH_PLACEHOLDER_DIRECT') : main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_USERS_SEARCH_PLACEHOLDER_REVERSE');
	    }
	  },
	  methods: {
	    createAlert() {
	      const moreButton = main_core.Tag.render(_t || (_t = _`<span class="hr-hcmlink-mapping-alert-container__more-button">${0}</span>`), main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SHOW_MORE_BUTTON'));
	      const alert = new ui_alerts.Alert({
	        text: main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_ALERT_INFO'),
	        color: ui_alerts.Alert.Color.PRIMARY,
	        size: ui_alerts.Alert.Size.MD,
	        closeBtn: true,
	        animated: true,
	        customClass: 'hr-hcmlink-mapping-alert-container',
	        afterMessageHtml: moreButton
	      });
	      alert.renderTo(this.$refs.alertContainer);
	      main_core.Event.bind(alert.getCloseBtn(), 'click', this.onCloseAlertButton);
	      main_core.Event.bind(moreButton, 'click', this.showDocumentation);
	    },
	    showDocumentation(event) {
	      if (top.BX.Helper) {
	        event.preventDefault();
	        top.BX.Helper.show(`redirect=detail&code=${HELPDESK_CODE}`);
	      }
	    },
	    onCloseAlertButton() {
	      this.$emit('closeAlert');
	    },
	    onCreateLink(options) {
	      this.$emit('createLink', options);
	    },
	    onRemoveLink(options) {
	      this.$emit('removeLink', options);
	    },
	    onSearchPersonName(query) {
	      this.$emit('search', query);
	    }
	  },
	  template: `
		<div 
			class="hr-hcmlink-sync__page-subtitle-box"
			:class="{'--alert-hidden': config.isHideInfoAlert}"
		>
			<div class="hr-hcmlink-sync__page-subtitle">
				{{ $Bitrix.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_DIALOG_PAGE_TITLE') }}
			</div>
			<div class="hr-hcmlink-sync__search-container">
				<SearchBar
					:placeholder="searchPlaceholder"
					:debounceWait="500"
					@search="onSearchPersonName"
				/>
			</div>
		</div>
		<div
			ref="alertContainer"
			class="hr-hcmlink-mapping-alert"
			:class="{'--hide': config.isHideInfoAlert}"
		></div>
		<div  v-if="isSearchResultEmpty" class="hr-hcmlink-mapping-page-state-container">
			<StateScreen
				status="searchNotFound"
				:isBlock="true"
				:mode=config.mode
			></StateScreen>
		</div>
		<div v-if="!isSearchResultEmpty && !dataLoading" class="hr-hcmlink-mapping-page-container" ref="container">
			<div class="hr-hcmlink-mapping-page-container__wrapper">
				<ColumnTitle
					:mode=config.mode
				></ColumnTitle>
				<div
					v-for="item in collection"
					:key="item.id"
				>
					<Line
						:item=item
						:config=config
						:mappedUserIds=mappedUserIds
						@createLink="onCreateLink"
						@removeLink="onRemoveLink"
					></Line>
				</div>
			</div>
			<div class="hr-hcmlink-mapping-page-person-wrapper hr-hcmlink-mapping-page-person-wrapper_right"
				 ref="person_wrapper" :class="[this.config.mode === 'direct' ? '--person' : '--user']"></div>
			<div class="hr-hcmlink-mapping-page-person-wrapper hr-hcmlink-mapping-page-person-wrapper_left"
				 ref="person_wrapper" :class="[this.config.mode === 'direct' ? '--user' : '--person']"></div>
		</div>
	`
	};

	const Loader = {
	  name: 'Loader',
	  props: {
	    size: {
	      type: Number,
	      default: 70
	    },
	    color: {
	      type: String,
	      default: '#2fc6f6'
	    },
	    offset: {
	      type: Object,
	      default: null
	    },
	    mode: {
	      type: String,
	      default: ''
	    }
	  },
	  created() {
	    this.loader = null;
	  },
	  async mounted() {
	    // eslint-disable-next-line no-shadow
	    const {
	      Loader
	    } = await main_core.Runtime.loadExtension('main.loader');
	    this.loader = new Loader({
	      target: this.$refs.container,
	      size: this.size,
	      color: this.color,
	      offset: this.offset,
	      mode: this.mode
	    });
	    this.loader.show();
	  },
	  beforeUnmount() {
	    if (this.loader) {
	      this.loader.destroy();
	      this.loader = null;
	    }
	  },
	  template: '<span ref="container"></span>'
	};

	const Toolbar = {
	  name: 'Toolbar',
	  components: {
	    Counter
	  },
	  emits: ['search'],
	  props: {
	    isMappingReady: {
	      required: true,
	      type: Boolean
	    },
	    mode: {
	      required: true,
	      type: String
	    },
	    countAllPersonsForMap: {
	      required: true,
	      type: Number
	    },
	    countMappedPersons: {
	      required: true,
	      type: Number
	    },
	    countUnmappedPersons: {
	      required: true,
	      type: Number
	    },
	    lastJobFinishedAt: {
	      required: false,
	      type: Date,
	      default: null
	    }
	  },
	  computed: {
	    searchPlaceholder() {
	      return this.mode === 'direct' ? main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_USERS_SEARCH_PLACEHOLDER_DIRECT') : main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_USERS_SEARCH_PLACEHOLDER_REVERSE');
	    },
	    mappedPercent() {
	      return Math.round(this.countMappedPersons / (this.countMappedPersons + this.countUnmappedPersons) * 100);
	    },
	    isDone() {
	      return this.mappedPercent === 100;
	    }
	  },
	  methods: {
	    onSearchPersonName(query) {
	      this.$emit('search', query);
	    }
	  },
	  template: `
		<div class="hr-hcmlink-sync__toolbar-row">
			<div class="hr-hcmlink-sync__title-wrapper">
				<div class="hr-hcmlink-sync__title-box">
					<span class="hr-hcmlink-sync__title-item">${main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_TITLE')}</span>
				</div>
			</div>
			<div class="hr-hcmlink-sync__search-container">
				<div v-if="isMappingReady"
					class="hr-hcmlink-sync__toolbar-bubble hr-hcmlink-sync__toolbar-bubble-right"
					:class="[isDone ? '--done' : '--not-done']"
				>
					{{
						$Bitrix.Loc.getMessage(
							'HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_PAGE_MAPPED_TITLE',
							{ '#PERCENT#': mappedPercent }
						)
					}}
				</div>
			</div>
		</div>
		<div v-if="isMappingReady" class="hr-hcmlink-sync__toolbar-row hr-hcmlink-sync__toolbar-row-counter">
			<Counter
				:countAllPersonsForMap=countAllPersonsForMap
				:countMappedPersons=countMappedPersons
				:countUnmappedPersons=countUnmappedPersons
				:lastJobFinishedAt=lastJobFinishedAt
				:mode=mode
			></Counter>
		</div>
	`
	};

	const HumanresourcesHcmlinkMapper = {
	  name: 'HumanresourcesHcmlinkMapper',
	  props: {
	    companyId: {
	      required: true,
	      type: Number
	    },
	    mode: {
	      required: true,
	      type: String
	    },
	    userIdCollection: {
	      required: true,
	      type: Array
	    },
	    toolbarContainer: {
	      required: true,
	      type: String
	    },
	    api: {
	      required: true,
	      type: humanresources_hcmlink_api.Api
	    }
	  },
	  components: {
	    Page,
	    Loader,
	    StateScreen,
	    Toolbar
	  },
	  data() {
	    return {
	      loading: false,
	      isHideInfoAlert: true,
	      pageCount: 0,
	      mappingEntityCollection: [],
	      userMappingSet: {},
	      isJobResolved: false,
	      isDone: false,
	      countAllPersonsForMap: 0,
	      countMappedPersons: 0,
	      countUnmappedPersons: 0,
	      isReadyToolbar: false,
	      mappedUserIds: [],
	      searchName: null,
	      searchActive: false,
	      lastJobFinishedAt: null
	    };
	  },
	  created() {
	    this.jobId = null;
	    this.updateJobStatusInterval = null;
	    this.forceSyncPointer = this.forceSync.bind(this); // for correct sub/unsub
	    this.pullUnsubscrubeCallback = null; // BX.PULL unsubscribe function

	    this.createUpdateEmployeeListJob();
	    main_core_events.EventEmitter.subscribe(EventList.HR_DATA_MAPPER_FORCE_SYNC, this.forceSyncPointer);
	  },
	  computed: {
	    isJobPending() {
	      return !this.isJobResolved && !this.isDone;
	    },
	    isMappingReady() {
	      return this.isJobResolved && !this.isDone;
	    },
	    isMappingDone() {
	      return this.isJobResolved && this.isDone;
	    },
	    isSearchEmpty() {
	      return this.mappingEntityCollection.length === 0 && Boolean(this.searchName);
	    },
	    completedStatus() {
	      return this.mode === 'direct' ? Status.done : Status.salaryDone;
	    }
	  },
	  watch: {
	    pageCount() {
	      this.loadConfig();
	    },
	    isMappingReady(value) {
	      if (value) {
	        this.footerDisplay(true);
	      }
	    },
	    isMappingDone(value) {
	      if (value) {
	        this.footerDisplay(false);
	      }
	    },
	    isSearchEmpty(value) {
	      this.footerDisplay(!value);
	    }
	  },
	  mounted() {
	    this.countAllPersonsForMap = this.userIdCollection.length;
	    this.$nextTick(() => {
	      this.isReadyToolbar = true;
	    });
	  },
	  unmounted() {
	    this.clearJobListeners();
	    main_core_events.EventEmitter.unsubscribe(EventList.HR_DATA_MAPPER_FORCE_SYNC, this.forceSyncPointer);
	  },
	  methods: {
	    // <editor-fold desc="External functions. Called by Mapper">
	    prepareNextUsers() {
	      this.$Bitrix.eventEmitter.emit(EventList.HR_DATA_MAPPER_DATA_WAS_SAVED);
	      this.searchName = null;
	      this.userMappingSet = {};
	      this.pageCount++;
	    },
	    getUserMappingSet() {
	      return this.userMappingSet;
	    },
	    // </editor-fold>
	    onCreateLink(options) {
	      this.userMappingSet[options.userId] = options;
	    },
	    onRemoveLink(options) {
	      if (this.userMappingSet[options.userId] !== undefined) {
	        delete this.userMappingSet[options.userId];
	      }
	    },
	    onCloseAlert() {
	      this.api.closeInfoAlert();
	    },
	    onCompleteMapping() {
	      this.api.createCompleteMappingEmployeeListJob({
	        companyId: this.companyId
	      });
	    },
	    onSearchPersonName(query) {
	      if (!this.isDone && query !== this.searchName) {
	        this.searchName = query || null;
	        this.userMappingSet = {};
	        this.searchActive = Boolean(query);
	        this.loadConfig();
	      }
	    },
	    /**
	     * On abort sync from state screen
	     *
	     * @returns {Promise<void>}
	     */
	    async onAbortSync() {
	      this.loading = true;
	      const jobData = await this.api.getLastJob({
	        companyId: this.companyId
	      });
	      await this.syncJobDone(jobData);
	    },
	    async loadConfig() {
	      this.loading = true;
	      const {
	        items,
	        countMappedPersons,
	        countUnmappedPersons,
	        isHideInfoAlert,
	        mappedUserIds
	      } = await this.api.loadMapperConfig({
	        companyId: this.companyId,
	        userIds: this.userIdCollection,
	        mode: this.mode,
	        searchName: this.searchName
	      });
	      this.isHideInfoAlert = isHideInfoAlert;
	      this.countUnmappedPersons = countUnmappedPersons;
	      this.countMappedPersons = countMappedPersons;
	      this.mappingEntityCollection = main_core.Type.isArray(items) ? items : [];
	      this.mappedUserIds = mappedUserIds;
	      this.isDone = this.mappingEntityCollection.length === 0 && !this.searchName;
	      this.loading = false;
	    },
	    async forceSync() {
	      this.$Bitrix.eventEmitter.emit(EventList.HR_DATA_MAPPER_CLEAR_SEARCH_INPUT);
	      this.searchName = null;
	      this.footerDisplay(false);
	      this.isJobResolved = false;
	      await this.api.cancelJob({
	        jobId: this.jobId,
	        companyId: this.companyId
	      });
	      this.createUpdateEmployeeListJob(true);
	    },
	    async syncJobDone(jobData) {
	      this.clearJobListeners();
	      this.lastJobFinishedAt = jobData.finishedAt ? new Date(jobData.finishedAt) : null;
	      await this.loadConfig();
	      this.isJobResolved = true;
	    },
	    async createUpdateEmployeeListJob(isForced = false) {
	      this.footerDisplay(false);
	      this.isJobResolved = false;
	      const data = await this.api.createUpdateEmployeeListJob({
	        companyId: this.companyId,
	        isForced
	      });
	      this.jobId = data.jobId;
	      if (data.status === 3) {
	        // if we got a job with status 'DONE', load data immediately
	        await this.syncJobDone(data);
	        return;
	      }
	      this.clearJobListeners();
	      this.updateJobStatusInterval = setInterval(this.updateJobStatus.bind(this), 30000);
	      if (BX.PULL) {
	        this.pullUnsubscrubeCallback = BX.PULL.subscribe({
	          type: BX.PullClient.SubscriptionType.Server,
	          moduleId: 'humanresources',
	          command: 'external_employee_list_updated',
	          callback: async function (params) {
	            if (params.jobId === this.jobId) {
	              await this.processJobStatus(params);
	            }
	          }.bind(this)
	        });
	        BX.PULL.extendWatch('humanresources_person_mapping');
	      }
	    },
	    async updateJobStatus() {
	      const {
	        params
	      } = await this.api.getJobStatus({
	        jobId: this.jobId
	      });
	      await this.processJobStatus(params);
	    },
	    footerDisplay(show) {
	      main_core_events.EventEmitter.emit(EventList.HR_DATA_MAPPER_FOOTER_DISPLAY, show);
	    },
	    async processJobStatus(params) {
	      if (params.status === 3) {
	        // load data if job is complete
	        await this.syncJobDone(params);
	      } else if (params.status === 5 || params.status === 4) {
	        // make a new job if last job was canceled or expired
	        this.clearJobListeners();
	        this.jobId = null;
	        this.createUpdateEmployeeListJob();
	      }
	    },
	    clearJobListeners() {
	      clearInterval(this.updateJobStatusInterval);
	      if (this.pullUnsubscrubeCallback) {
	        this.pullUnsubscrubeCallback();
	      }
	      this.pullUnsubscrubeCallback = null;
	    }
	  },
	  template: `
		<Teleport v-if="isReadyToolbar" :to="toolbarContainer">
			<Toolbar
				:isMappingReady="isMappingReady"
				:countAllPersonsForMap=countAllPersonsForMap
				:countMappedPersons=countMappedPersons
				:countUnmappedPersons=countUnmappedPersons
				:lastJobFinishedAt=lastJobFinishedAt
				:mode=mode
			/>
		</Teleport>
		<template v-if="isJobPending">
			<StateScreen
				:status="loading ? 'loading' : 'pending'"
				:mode=mode
				@abortSync="onAbortSync"
			></StateScreen>
		</template>
		<template v-if="isMappingReady">
			<Loader v-if="loading"></Loader>
			<Page
				:dataLoading=loading
				:collection=mappingEntityCollection
				:mappedUserIds=mappedUserIds
				:searchActive=searchActive
				:config="{ mode, isHideInfoAlert, companyId }"
				@createLink="onCreateLink"
				@removeLink="onRemoveLink"
				@closeAlert="onCloseAlert"
				@search="onSearchPersonName"
			></Page>
		</template>
		<template v-if="isMappingDone">
			<StateScreen
				:status=completedStatus
				:mode=mode
				@completeMapping='onCompleteMapping'
			></StateScreen>
		</template>
	`
	};

	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _application = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("application");
	/**
	 * An entry point of data-mapper
	 */
	class Mapper {
	  constructor(options) {
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _application, {
	      writable: true,
	      value: null
	    });
	    this.api = new humanresources_hcmlink_api.Api();
	    this.options = options;
	    this.footerDisplayPointer = this.footerDisplay.bind(this); // for correct sub/unsub

	    if (main_core.Type.isNil(this.options.userIds)) {
	      this.options.userIds = new Set();
	    }
	  }
	  static openSlider(options, sliderOptions) {
	    let closure = null;
	    BX.SidePanel.Instance.open('humanresources:mapper', {
	      width: 800,
	      loader: 'default-loader',
	      cacheable: false,
	      contentCallback: () => {
	        return top.BX.Runtime.loadExtension('humanresources.hcmlink.data-mapper').then(exports => {
	          closure = new exports.Mapper(options);
	          return closure.getLayout();
	        });
	      },
	      events: {
	        onClose: () => {
	          sliderOptions == null ? void 0 : sliderOptions.onCloseHandler();
	          closure.unmount();
	        },
	        onLoad: () => {
	          // Here we need to get rid of title to replace the entire toolbar with our own markup
	          // Why we just don't pass the title at all? If we don't pass it, then toolbar will not render too
	          main_core.Dom.remove(closure.layout.getContainer().querySelector('.ui-sidepanel-layout-title'));
	          // Add a class to differentiate this layout from other layouts
	          main_core.Dom.addClass(closure.layout.getContainer().querySelector('.ui-sidepanel-layout-header'), 'hr-hcmlink-sync__toolbar');
	        }
	      }
	    });
	  }
	  renderTo(container) {
	    main_core.Dom.append(this.render(), container);
	  }
	  render() {
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = document.createElement('div');
	    if (babelHelpers.classPrivateFieldLooseBase(this, _application)[_application] === null) {
	      babelHelpers.classPrivateFieldLooseBase(this, _application)[_application] = ui_vue3.BitrixVue.createApp(HumanresourcesHcmlinkMapper, {
	        companyId: this.options.companyId,
	        mode: this.options.mode,
	        userIdCollection: [...this.options.userIds],
	        toolbarContainer: '.hr-hcmlink-sync__toolbar .ui-sidepanel-layout-toolbar',
	        api: this.api
	      });
	      main_core_events.EventEmitter.subscribe(EventList.HR_DATA_MAPPER_FOOTER_DISPLAY, this.footerDisplayPointer);
	      main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], 'height', '100%');
	      this.component = babelHelpers.classPrivateFieldLooseBase(this, _application)[_application].mount(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]);
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
	  }
	  unmount() {
	    main_core_events.EventEmitter.unsubscribe(EventList.HR_DATA_MAPPER_FOOTER_DISPLAY, this.footerDisplayPointer);
	    babelHelpers.classPrivateFieldLooseBase(this, _application)[_application].unmount();
	  }
	  async getLayout() {
	    const getContentLayout = function () {
	      return this.render();
	    }.bind(this);
	    const saveAction = async function () {
	      const collection = Object.values(this.component.getUserMappingSet());
	      return this.api.saveMapping({
	        collection,
	        companyId: this.options.companyId
	      });
	    }.bind(this);
	    const prepareNextUsers = async function () {
	      this.component.prepareNextUsers();
	    }.bind(this);
	    this.layout = await ui_sidepanel_layout.Layout.createLayout({
	      extensions: ['humanresources.hcmlink.data-mapper', 'ui.entity-selector', 'ui.icon-set.actions', 'ui.select', 'popup'],
	      title: main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_TITLE'),
	      toolbar() {
	        // We need to pass at least empty array for ui-sidepanel-layout-toolbar to appear
	        return [];
	      },
	      content() {
	        return getContentLayout();
	      },
	      buttons({
	        cancelButton,
	        SaveButton
	      }) {
	        return [new SaveButton({
	          text: main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_BUTTON_SAVE_AND_CONTINUE'),
	          async onclick() {
	            const result = await saveAction();
	            if (result) {
	              await prepareNextUsers();
	            }
	          },
	          round: true
	        }), cancelButton];
	      }
	    });
	    return this.layout.render();
	  }
	  footerDisplay(showEvent) {
	    var _this$layout$getConta;
	    if (!this.layout) {
	      return;
	    }
	    if (this.layout.getFooterContainer()) {
	      main_core.Dom.style(this.layout.getFooterContainer(), 'display', showEvent.data ? 'block' : 'none');
	    }
	    const footerAnchor = (_this$layout$getConta = this.layout.getContainer()) == null ? void 0 : _this$layout$getConta.getElementsByClassName('ui-sidepanel-layout-footer-anchor')[0];
	    if (footerAnchor) {
	      main_core.Dom.style(footerAnchor, 'display', showEvent.data ? 'block' : 'none');
	    }
	  }
	}
	Mapper.MODE_DIRECT = 'direct';
	Mapper.MODE_REVERSE = 'reverse';

	exports.Mapper = Mapper;

}((this.BX.Humanresources.Hcmlink = this.BX.Humanresources.Hcmlink || {}),BX.Vue3,BX.UI,BX.UI.EntitySelector,BX.UI,BX.UI.IconSet,BX.Main,BX,BX.Messenger.v2.Lib,BX.UI,BX.UI.IconSet,BX.Humanresources.Hcmlink,BX,BX.Event,BX.UI.SidePanel));
//# sourceMappingURL=index.bundle.js.map
