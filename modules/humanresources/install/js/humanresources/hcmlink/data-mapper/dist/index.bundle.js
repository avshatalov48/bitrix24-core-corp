/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,ui_alerts,ui_entitySelector,main_core_events,ui_avatar,ui_iconSet_api_core,main_popup,ui_iconSet_actions,ui_vue3,ui_buttons,humanresources_hcmlink_api,main_core,ui_sidepanel_layout) {
	'use strict';

	const Separator = {
	  name: 'Separator',
	  props: {
	    hasLink: {
	      required: true,
	      type: Boolean
	    }
	  },
	  computed: {
	    styleObject() {
	      return {
	        '--ui-icon-set__icon-color': this.hasLink ? '#FFC34D' : '#D5D7DB'
	      };
	    }
	  },
	  template: `
		<div class="hr-hcmlink-separator__container" ref="container">
            <div 
	            style="--ui-icon-set__icon-size: 24px;"
	            :style="styleObject"
                class="ui-icon-set"
	            :class="[ hasLink ? '--arrow-right' : '--delete-hyperlink']"
            ></div>
		</div>
	`
	};

	const PersonItem = {
	  name: 'PersonItem',
	  props: {
	    config: {
	      required: true,
	      type: Object
	    },
	    mappedUserIds: {
	      required: true,
	      type: Array
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
	          tabs: [{
	            id: 'user',
	            title: main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_DIALOG_TAB_TITLE_USER')
	          }],
	          recentTabOptions: {
	            visible: false
	          }
	        }
	      });
	      selector.getOuterContainer().style.width = '100%';
	      return selector;
	    },
	    getPersonTagSelector() {
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
	          tabs: [{
	            id: 'persons',
	            title: main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_DIALOG_TAB_TITLE_PERSON')
	          }],
	          recentTabOptions: {
	            visible: false
	          }
	        }
	      });
	      selector.getOuterContainer().style.border = 'none';
	      selector.getOuterContainer().style.width = '100%';
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
			:class="{'hr-hcmlink-selector-entity__border': isBorderedEmployee}"
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
	    this.getUserAvatarEntity().renderTo(this.$refs.avatarContainer);
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
			<div class="hr-hcmlink-item-user__avatar" ref="avatarContainer"></div>
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
	      type: Object,
	      required: true
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
					:item = item
				    :mode="config.mode"
				></UserItem>
				<Separator
					:hasLink = hasLink
				></Separator>
			</div>
			<div class="hr-hcmlink-sync__line-right-container">
				<PersonItem
					:config = config
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
		<template v-if="mode=='direct'">
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
	    config: {
	      required: true,
	      type: Object
	    }
	  },
	  template: `
        <div class="hr-hcmlink-sync__page_counter_container">
			<template v-if="config.mode === 'direct'">
				<span class="hr-hcmlink-sync__page_count-title">{{ $Bitrix.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_PAGE_MAPPED_TITLE') }}: </span>
				<span class="hr-hcmlink-sync__page_mapped-persons-count">{{ countMappedPersons }} </span>
				<span class="hr-hcmlink-sync__page_all-persons-count"> / {{ countAllPersonsForMap }} </span>
			</template>
			<template v-else>
				<span class="hr-hcmlink-sync__page_count-title">{{ $Bitrix.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_PAGE_UNMAPPED_TITLE') }}: </span>
				<span class="hr-hcmlink-sync__page_mapped-persons-count">{{ countUnmappedPersons }} </span>
			</template>
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
	        mode: String,
	        isHideInfoAlert: Boolean
	      }
	    }
	  },
	  components: {
	    Line,
	    ColumnTitle,
	    Counter
	  },
	  emits: ['createLink', 'removeLink', 'closeAlert'],
	  mounted() {
	    if (!this.config.isHideInfoAlert) {
	      this.showAlert();
	    }
	  },
	  methods: {
	    showAlert() {
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
	    }
	  },
	  template: `
		<div>
			<div ref="alertContainer" v-if="!config.isHideInfoAlert"></div>
			<div class="hr-hcmlink-mapping-page-container" ref="container">
				<div style="z-index: 100">
					<ColumnTitle
						:mode = config.mode
					></ColumnTitle>
					<div
						v-for="item in collection"
						:key="item.id"
					>
						<Line
							:item = item
							:config = config
							:mappedUserIds=mappedUserIds
							@createLink="onCreateLink"
							@removeLink="onRemoveLink"
						></Line>
					</div>
				</div>
				<div class="hr-hcmlink-mapping-page-person-wrapper hr-hcmlink-mapping-page-person-wrapper_right" ref="person_wrapper" v-if="config.mode == 'direct'"></div>
				<div class="hr-hcmlink-mapping-page-person-wrapper hr-hcmlink-mapping-page-person-wrapper_left" ref="person_wrapper" v-if="config.mode == 'reverse'"></div>
			</div>
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

	const StateScreen = {
	  name: 'StateScreen',
	  props: {
	    status: {
	      required: true,
	      type: String
	    }
	  },
	  data() {
	    return {
	      state: this.isDoneState() ? 'done' : 'pending'
	    };
	  },
	  emits: ['completeMapping'],
	  methods: {
	    isDoneState() {
	      return ['done', 'salaryDone'].includes(this.status);
	    },
	    getButton() {
	      const text = this.status === 'done' ? main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_BUTTON_CLOSE') : main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_BUTTON_SALARY_CLOSE');
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
	    }
	  },
	  mounted() {
	    this.getButton().renderTo(this.$refs.buttonContainer);
	  },
	  computed: {
	    title() {
	      switch (this.status) {
	        case 'pending':
	          return main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_TITLE_STATUS_PENDING');
	        case 'done':
	          return main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_TITLE_STATUS_DONE');
	        case 'salaryDone':
	          return main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_TITLE_STATUS_SALARY_DONE');
	        default:
	          return '';
	      }
	    },
	    description() {
	      switch (this.status) {
	        case 'pending':
	          return main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_DESCRIPTION_STATUS_PENDING');
	        case 'done':
	          return main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_DESCRIPTION_STATUS_DONE');
	        case 'salaryDone':
	          return main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_DESCRIPTION_STATUS_SALARY_DONE');
	        default:
	          return '';
	      }
	    },
	    stateClassList() {
	      return {
	        '--done': this.isDoneState(),
	        '--pending': this.status === 'pending'
	      };
	    }
	  },
	  template: `
		<div 
			class="hr-hcmlink-mapping-person__state-screen"
            :class="stateClassList"
		>
			<div class="hr-hcmlink-mapping-person__state-screen_icon"></div>
			<div class="hr-hcmlink-mapping-person__state-screen_title">{{ title }}</div>
			<div 
				class="hr-hcmlink-mapping-person__state-screen_desc"
				v-html="description"
			>
			</div>
			<div 
				v-if="state === 'done'"
				class="hr-hcmlink-mapping-person__state-screen_close-button" ref="buttonContainer"
			></div>
		</div>
	`
	};

	let _$1 = t => t,
	  _t$1;
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _application = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("application");
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
	    if (main_core.Type.isNil(this.options.userIds)) {
	      this.options.userIds = new Set();
	    }
	  }
	  static openSlider(options, sliderOptions) {
	    var _sliderOptions$onClos;
	    BX.SidePanel.Instance.open('humanresources:mapper', {
	      width: 800,
	      loader: 'default-loader',
	      cacheable: false,
	      contentCallback: () => {
	        return top.BX.Runtime.loadExtension('humanresources.hcmlink.data-mapper').then(exports => {
	          return new exports.Mapper(options).getLayout();
	        });
	      },
	      events: {
	        onClose: (_sliderOptions$onClos = sliderOptions == null ? void 0 : sliderOptions.onCloseHandler) != null ? _sliderOptions$onClos : () => {}
	      }
	    });
	  }
	  renderTo(container) {
	    main_core.Dom.append(this.render(), container);
	  }
	  render() {
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = document.createElement('div');
	    if (babelHelpers.classPrivateFieldLooseBase(this, _application)[_application] === null) {
	      babelHelpers.classPrivateFieldLooseBase(this, _application)[_application] = ui_vue3.BitrixVue.createApp(this.makeRootVueComponent());
	      this.component = babelHelpers.classPrivateFieldLooseBase(this, _application)[_application].mount(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]);
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
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
	        return [main_core.Tag.render(_t$1 || (_t$1 = _$1`<div id="hr-hcmlink-toolbar-container"></div>`))];
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
	  makeRootVueComponent() {
	    const context = this;
	    return {
	      name: 'HumanresourcesHcmlinkMapper',
	      components: {
	        Page,
	        Loader,
	        Counter,
	        StateScreen
	      },
	      data() {
	        var _context$options$mode;
	        return {
	          loading: false,
	          config: {
	            companyId: context.options.companyId,
	            mode: (_context$options$mode = context.options.mode) != null ? _context$options$mode : 'direct',
	            isHideInfoAlert: true
	          },
	          pageCount: 0,
	          mappingEntityCollection: [],
	          userMappingSet: {},
	          userIdCollection: [...context.options.userIds],
	          isJobResolved: false,
	          jobId: null,
	          isDone: false,
	          countAllPersonsForMap: 0,
	          countMappedPersons: 0,
	          countUnmappedPersons: 0,
	          counterContainer: '#hr-hcmlink-toolbar-container',
	          isReadyToolbar: false,
	          completedStatus: context.options.mode === 'direct' ? 'done' : 'salaryDone',
	          jobResolverInterval: null,
	          mappedUserIds: []
	        };
	      },
	      created() {
	        this.footerDisplay(false);
	        this.createUpdateEmployeeListJob();
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
	        }
	      },
	      mounted() {
	        this.countAllPersonsForMap = this.userIdCollection.length;
	        this.$nextTick(() => {
	          this.isReadyToolbar = true;
	        });
	      },
	      unmounted() {
	        clearInterval(this.jobResolverInterval);
	      },
	      methods: {
	        prepareNextUsers() {
	          this.userMappingSet = {};
	          this.pageCount++;
	        },
	        getUserMappingSet() {
	          return this.userMappingSet;
	        },
	        onCreateLink(options) {
	          this.userMappingSet[options.userId] = options;
	        },
	        onRemoveLink(options) {
	          if (this.userMappingSet[options.userId] !== undefined) {
	            delete this.userMappingSet[options.userId];
	          }
	        },
	        onCloseAlert() {
	          context.api.closeInfoAlert();
	        },
	        onCompleteMapping() {
	          context.api.createCompleteMappingEmployeeListJob({
	            companyId: this.config.companyId
	          });
	        },
	        async loadConfig() {
	          this.loading = true;
	          const {
	            items,
	            countMappedPersons,
	            countUnmappedPersons,
	            isHideInfoAlert,
	            mappedUserIds
	          } = await context.api.loadMapperConfig({
	            companyId: this.config.companyId,
	            userIds: this.userIdCollection,
	            mode: this.config.mode
	          });
	          this.config.isHideInfoAlert = isHideInfoAlert;
	          this.countUnmappedPersons = countUnmappedPersons;
	          this.countMappedPersons = countMappedPersons;
	          this.mappingEntityCollection = main_core.Type.isArray(items) ? items : [];
	          this.mappedUserIds = mappedUserIds;
	          this.isDone = this.mappingEntityCollection.length === 0;
	          this.loading = false;
	        },
	        async createUpdateEmployeeListJob() {
	          const data = await context.api.createUpdateEmployeeListJob({
	            companyId: this.config.companyId
	          });
	          this.jobId = data.jobId;
	          this.jobResolverInterval = setInterval(this.jobResolver.bind(this), 30000);
	          BX.PULL.subscribe({
	            type: BX.PullClient.SubscriptionType.Server,
	            moduleId: 'humanresources',
	            command: 'external_employee_list_updated',
	            callback: async function (params) {
	              if (params.jobId === this.jobId && params.status === 3) {
	                clearInterval(this.jobResolverInterval);
	                await this.loadConfig(params);
	                this.isJobResolved = true;
	              }
	            }.bind(this)
	          });
	          BX.PULL.extendWatch('humanresources_person_mapping');
	        },
	        async jobResolver() {
	          const {
	            params
	          } = await context.api.getJobStatus({
	            jobId: this.jobId
	          });
	          if (params.status === 3) {
	            clearInterval(this.jobResolverInterval);
	            await this.loadConfig(params);
	            this.isJobResolved = true;
	          }
	        },
	        footerDisplay(show) {
	          var _context$layout$getCo;
	          if (!context.layout) {
	            return;
	          }
	          if (context.layout.getFooterContainer()) {
	            main_core.Dom.style(context.layout.getFooterContainer(), 'display', show ? 'block' : 'none');
	          }
	          const footerAnchor = (_context$layout$getCo = context.layout.getContainer()) == null ? void 0 : _context$layout$getCo.getElementsByClassName('ui-sidepanel-layout-footer-anchor')[0];
	          if (footerAnchor) {
	            main_core.Dom.style(footerAnchor, 'display', show ? 'block' : 'none');
	          }
	        }
	      },
	      template: `
                <template v-if="isJobPending">
                    <StateScreen
                        status='pending'
                    ></StateScreen>
                </template>
                <template v-if="isMappingReady">
                    <Loader v-if="loading"></Loader>
                    <Page
                        :collection=mappingEntityCollection
						:mappedUserIds=mappedUserIds
                        :config=config
                        @createLink="onCreateLink"
                        @removeLink="onRemoveLink"
						@closeAlert="onCloseAlert"
                    ></Page>
                </template>
                <template v-if="isMappingDone">
                    <StateScreen
	                    :status=completedStatus
						@completeMapping='onCompleteMapping'
                    ></StateScreen>
                </template>
				<Teleport v-if="isReadyToolbar && isMappingReady" :to="counterContainer">
					<Counter
						:countAllPersonsForMap=countAllPersonsForMap
						:countMappedPersons=countMappedPersons
						:countUnmappedPersons=countUnmappedPersons
						:config=config
					></Counter>
				</Teleport>
			`
	    };
	  }
	}
	Mapper.MODE_DIRECT = 'direct';
	Mapper.MODE_REVERSE = 'reverse';

	exports.Mapper = Mapper;

}((this.BX.Humanresources.Hcmlink = this.BX.Humanresources.Hcmlink || {}),BX.UI,BX.UI.EntitySelector,BX.Event,BX.UI,BX.UI.IconSet,BX.Main,BX,BX.Vue3,BX.UI,BX.Humanresources.Hcmlink,BX,BX.UI.SidePanel));
//# sourceMappingURL=index.bundle.js.map
