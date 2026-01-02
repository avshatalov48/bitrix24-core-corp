/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_vue3,booking_component_mixin_locMixin,booking_model_notifications,booking_model_resourceCreationWizard,booking_lib_sidePanelInstance,main_loader,booking_core,ui_notificationManager,crm_messagesender,booking_provider_service_resourcesService,booking_lib_analytics,booking_model_resourceTypes,booking_provider_service_resourcesTypeService,booking_component_reminder,ui_entitySelector,main_core_events,booking_lib_timezone,ui_buttons,booking_lib_duration,ui_forms,ui_layoutForm,booking_lib_ahaMoments,ui_vue3_components_menu,ui_vue3_directives_hint,ui_iconSet_actions,ui_iconSet_crm,ui_hint,booking_component_switcher,booking_provider_service_resourceCreationWizardService,main_popup,main_core,main_date,booking_component_popup,booking_component_button,booking_component_helpDeskLoc,ui_iconSet_api_vue,ui_iconSet_api_core,ui_vue3_components_richLoc,ui_vue3_vuex,ui_iconSet_main,booking_const,booking_component_cyclePopup) {
	'use strict';

	const UiLoader = {
	  name: 'UiLoader',
	  props: {
	    show: Boolean
	  },
	  data() {
	    return {
	      loader: new main_loader.Loader()
	    };
	  },
	  mounted() {
	    void this.loader.show(this.$refs.loader);
	  },
	  template: `
		<div ref="loader"></div>
	`
	};

	const ResourceCreationWizardLayout = {
	  name: 'ResourceCreationWizardLayout',
	  props: {
	    step: {
	      type: Number,
	      required: true
	    },
	    title: {
	      type: String,
	      default: ''
	    },
	    loading: Boolean
	  },
	  watch: {
	    step() {
	      var _this$$refs$wrapper;
	      (_this$$refs$wrapper = this.$refs.wrapper) == null ? void 0 : _this$$refs$wrapper.scrollTo(0, 0);
	    }
	  },
	  components: {
	    UiLoader
	  },
	  template: `
		<div class="resource-creation-wizard-layout">
			<div ref="wrapper" class="resource-creation-wizard__wrapper">
				<div class="resource-creation-wizard__header">
					<slot name="header">
						<h4 class="resource-creation-wizard__header-title">
							{{ title }}
						</h4>
					</slot>
				</div>
				<div v-show="!loading" class="resource-creation-wizard__content">
					<slot/>
				</div>
				<UiLoader v-if="loading"/>
			</div>
			<slot name="footer"/>
		</div>
	`
	};

	const ResourceCreationWizardHeader = {
	  name: 'ResourceCreationWizardHeader',
	  computed: {
	    title() {
	      return this.$store.state['resource-creation-wizard'].resourceName;
	    }
	  },
	  template: `
		<span class="resource-creation-wizard__header-title">
			{{ title }}
		</span>
	`
	};

	const NextButton = {
	  name: 'NextButton',
	  components: {
	    UiButton: booking_component_button.Button
	  },
	  props: {
	    step: {
	      type: Number,
	      required: true
	    },
	    steps: {
	      type: Array,
	      required: true
	    },
	    disabled: Boolean,
	    waiting: Boolean
	  },
	  computed: {
	    currentStep() {
	      return this.steps[this.step - 1];
	    },
	    size() {
	      return booking_component_button.ButtonSize.SMALL;
	    },
	    color() {
	      return booking_component_button.ButtonColor.SUCCESS;
	    }
	  },
	  template: `
		<UiButton
			:text="currentStep.labelNext"
			:title="currentStep.labelNext"
			:size
			:color
			:disabled
			:waiting
			@click="currentStep.next()"
		/>
	`
	};

	const BackButton = {
	  name: 'BackButton',
	  props: {
	    step: {
	      type: Number,
	      required: true
	    },
	    steps: {
	      type: Array,
	      required: true
	    }
	  },
	  computed: {
	    buttonSize() {
	      return booking_component_button.ButtonSize.SMALL;
	    },
	    buttonColor() {
	      return booking_component_button.ButtonColor.LINK;
	    },
	    currentStep() {
	      return this.steps[this.step - 1];
	    }
	  },
	  components: {
	    UiButton: booking_component_button.Button
	  },
	  template: `
		<UiButton
			class="resource-creation-wizard__back-button"
			:text="currentStep.labelBack"
			:title="currentStep.labelBack"
			:size="buttonSize"
			:color="buttonColor"
			@click="currentStep.back()"
		/>
	`
	};

	/**
	 * @abstract
	 */
	class Step {
	  constructor(hidden = false) {
	    this.hidden = false;
	    this.hidden = hidden;
	  }
	  get store() {
	    return booking_core.Core.getStore();
	  }
	  get labelNext() {
	    return main_core.Loc.getMessage('BRCW_BUTTON_CONTINUE');
	  }
	  get labelBack() {
	    return main_core.Loc.getMessage('BRCW_BUTTON_CANCEL');
	  }
	  async next() {
	    await this.store.dispatch('resource-creation-wizard/nextStep');
	  }
	  async back() {
	    await this.store.dispatch('resource-creation-wizard/prevStep');
	  }
	}

	class ChooseResourceStep extends Step {
	  constructor() {
	    super(true);
	  }
	  get labelBack() {
	    return main_core.Loc.getMessage('BRCW_BUTTON_CANCEL');
	  }
	  async next() {
	    await super.next();
	  }
	  back() {
	    main_core.Event.EventEmitter.emit(booking_const.EventName.CloseWizard);
	  }
	}

	var _resource = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resource");
	var _entityCalendar = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityCalendar");
	var _isBitrix24Approved = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isBitrix24Approved");
	var _isBitrix24SenderAvailable = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isBitrix24SenderAvailable");
	var _prepareCompanySlotRanges = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareCompanySlotRanges");
	var _prepareResourceTypeNotifications = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareResourceTypeNotifications");
	var _upsertResource = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("upsertResource");
	var _prepareNotificationOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareNotificationOptions");
	var _checkEntityCalendar = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("checkEntityCalendar");
	var _disabledCalendarIntegration = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disabledCalendarIntegration");
	class ResourceNotificationStep extends Step {
	  constructor() {
	    super();
	    Object.defineProperty(this, _disabledCalendarIntegration, {
	      value: _disabledCalendarIntegration2
	    });
	    Object.defineProperty(this, _checkEntityCalendar, {
	      value: _checkEntityCalendar2
	    });
	    Object.defineProperty(this, _prepareNotificationOptions, {
	      value: _prepareNotificationOptions2
	    });
	    Object.defineProperty(this, _upsertResource, {
	      value: _upsertResource2
	    });
	    Object.defineProperty(this, _prepareResourceTypeNotifications, {
	      value: _prepareResourceTypeNotifications2
	    });
	    Object.defineProperty(this, _prepareCompanySlotRanges, {
	      value: _prepareCompanySlotRanges2
	    });
	    Object.defineProperty(this, _isBitrix24SenderAvailable, {
	      value: _isBitrix24SenderAvailable2
	    });
	    Object.defineProperty(this, _isBitrix24Approved, {
	      value: _isBitrix24Approved2
	    });
	    Object.defineProperty(this, _entityCalendar, {
	      get: _get_entityCalendar,
	      set: void 0
	    });
	    Object.defineProperty(this, _resource, {
	      get: _get_resource,
	      set: void 0
	    });
	    this.step = 3;
	  }
	  get labelNext() {
	    var _babelHelpers$classPr;
	    return main_core.Type.isNull((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _resource)[_resource].id) != null ? _babelHelpers$classPr : null) ? main_core.Loc.getMessage('BRCW_BUTTON_CREATE_RESOURCE') : main_core.Loc.getMessage('BRCW_BUTTON_UPDATE_RESOURCE');
	  }
	  async next() {
	    this.store.commit(`${booking_const.Model.ResourceCreationWizard}/setSaving`, true);
	    booking_lib_analytics.RcwAnalytics.sendAddResourceFinish();
	    const isApproved = await babelHelpers.classPrivateFieldLooseBase(this, _isBitrix24Approved)[_isBitrix24Approved]();
	    if (!isApproved) {
	      this.store.commit(`${booking_const.Model.ResourceCreationWizard}/setSaving`, false);
	      return;
	    }
	    if (this.store.getters[`${booking_const.Model.ResourceCreationWizard}/isGlobalSchedule`]) {
	      await this.store.dispatch(`${booking_const.Model.ResourceCreationWizard}/updateResource`, {
	        slotRanges: babelHelpers.classPrivateFieldLooseBase(this, _prepareCompanySlotRanges)[_prepareCompanySlotRanges](babelHelpers.classPrivateFieldLooseBase(this, _resource)[_resource])
	      });
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _checkEntityCalendar)[_checkEntityCalendar]();
	    const isSuccess = await babelHelpers.classPrivateFieldLooseBase(this, _upsertResource)[_upsertResource](babelHelpers.classPrivateFieldLooseBase(this, _resource)[_resource]);
	    if (!isSuccess) {
	      this.store.commit(`${booking_const.Model.ResourceCreationWizard}/setSaving`, false);
	      return;
	    }
	    await booking_provider_service_resourcesTypeService.resourceTypeService.update({
	      id: babelHelpers.classPrivateFieldLooseBase(this, _resource)[_resource].typeId,
	      ...babelHelpers.classPrivateFieldLooseBase(this, _prepareResourceTypeNotifications)[_prepareResourceTypeNotifications](babelHelpers.classPrivateFieldLooseBase(this, _resource)[_resource])
	    });
	    main_core.Event.EventEmitter.emit(booking_const.EventName.CloseWizard);
	  }
	}
	function _get_resource() {
	  return this.store.getters[`${booking_const.Model.ResourceCreationWizard}/getResource`];
	}
	function _get_entityCalendar() {
	  return this.store.getters[`${booking_const.Model.ResourceCreationWizard}/entityCalendar`];
	}
	function _isBitrix24Approved2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isBitrix24SenderAvailable)[_isBitrix24SenderAvailable]()) {
	    return Promise.resolve(true);
	  }
	  return crm_messagesender.ConditionChecker.checkIsApproved({
	    senderType: crm_messagesender.Types.bitrix24
	  });
	}
	function _isBitrix24SenderAvailable2() {
	  const bitrix24Sender = this.store.getters[`${booking_const.Model.Notifications}/getSenders`].find(sender => sender.moduleId === booking_const.Module.Crm && sender.code === crm_messagesender.Types.bitrix24);
	  if (!bitrix24Sender) {
	    return false;
	  }
	  return bitrix24Sender.canUse;
	}
	function _prepareCompanySlotRanges2(resource) {
	  var _resource$slotRanges$, _resource$slotRanges$2;
	  const slotSize = (_resource$slotRanges$ = (_resource$slotRanges$2 = resource.slotRanges[0]) == null ? void 0 : _resource$slotRanges$2.slotSize) != null ? _resource$slotRanges$ : 60;
	  const scheduleSlots = this.store.getters[`${booking_const.Model.ResourceCreationWizard}/getCompanyScheduleSlots`];
	  const timezone = this.store.getters[`${booking_const.Model.Interface}/timezone`];
	  return scheduleSlots.map(slotRange => ({
	    ...slotRange,
	    slotSize,
	    timezone
	  }));
	}
	function _prepareResourceTypeNotifications2(resource) {
	  const resourceType = this.store.getters[`${booking_const.Model.ResourceTypes}/getById`](resource.typeId);
	  return Object.values(this.store.getters[`${booking_const.Model.Dictionary}/getNotifications`]).map(({
	    value
	  }) => value).reduce((acc, type) => {
	    const notificationOnField = booking_const.NotificationFieldsMap.NotificationOn[type];
	    const templateTypeField = booking_const.NotificationFieldsMap.TemplateType[type];
	    const settingsFields = booking_const.NotificationFieldsMap.Settings[type];
	    const isCheckedForAll = this.store.getters[`${booking_const.Model.ResourceCreationWizard}/isCheckedForAll`](type);
	    return {
	      ...acc,
	      [notificationOnField]: isCheckedForAll ? resource[notificationOnField] : resourceType[notificationOnField],
	      [templateTypeField]: isCheckedForAll ? resource[templateTypeField] : resourceType[templateTypeField],
	      ...settingsFields.reduce((fields, field) => ({
	        ...fields,
	        [field]: isCheckedForAll ? resource[field] : resourceType[field]
	      }), {})
	    };
	  }, {});
	}
	async function _upsertResource2(resource) {
	  const isUpdate = Boolean(resource.id);
	  const result = await (isUpdate ? booking_provider_service_resourcesService.resourceService.update(resource) : booking_provider_service_resourcesService.resourceService.add(resource));
	  let text = main_core.Loc.getMessage(isUpdate ? 'BRCW_UPDATE_SUCCESS_MESSAGE' : 'BRCW_CREATE_SUCCESS_MESSAGE');
	  if (main_core.Type.isArrayFilled(result.errors)) {
	    text = result.errors[0].message;
	  }
	  ui_notificationManager.Notifier.notify(babelHelpers.classPrivateFieldLooseBase(this, _prepareNotificationOptions)[_prepareNotificationOptions](text));
	  return !main_core.Type.isArrayFilled(result.errors);
	}
	function _prepareNotificationOptions2(text) {
	  return {
	    id: main_core.Text.getRandom(),
	    text
	  };
	}
	async function _checkEntityCalendar2() {
	  var _babelHelpers$classPr2, _babelHelpers$classPr3;
	  if (!this.store.getters[`${booking_const.Model.ResourceCreationWizard}/isIntegrationCalendarEnabled`] || ((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _entityCalendar)[_entityCalendar].data) == null ? void 0 : (_babelHelpers$classPr3 = _babelHelpers$classPr2.userIds) == null ? void 0 : _babelHelpers$classPr3.length) === 0) {
	    await babelHelpers.classPrivateFieldLooseBase(this, _disabledCalendarIntegration)[_disabledCalendarIntegration]();
	  }
	}
	async function _disabledCalendarIntegration2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _entityCalendar)[_entityCalendar] === null) {
	    return;
	  }
	  await this.store.dispatch(`${booking_const.Model.ResourceCreationWizard}/updateResourceEntityCalendar`, {
	    userIds: [],
	    locationId: null,
	    checkAvailability: false,
	    reminders: []
	  });
	}

	var _isFirstStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isFirstStep");
	var _validateIntegrationCalendarUser = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("validateIntegrationCalendarUser");
	class ResourceSettingsStep extends Step {
	  constructor() {
	    super();
	    Object.defineProperty(this, _validateIntegrationCalendarUser, {
	      value: _validateIntegrationCalendarUser2
	    });
	    Object.defineProperty(this, _isFirstStep, {
	      get: _get_isFirstStep,
	      set: void 0
	    });
	    this.step = 2;
	  }
	  get labelBack() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _isFirstStep)[_isFirstStep] ? super.labelBack : main_core.Loc.getMessage('BRCW_BUTTON_BACK');
	  }
	  async next() {
	    const store = this.store;
	    if (!store.state[booking_const.Model.ResourceCreationWizard].resource.name) {
	      if (!main_core.Type.isNull(store.state[booking_const.Model.ResourceCreationWizard].resourceId)) {
	        await store.dispatch(`${booking_const.Model.ResourceCreationWizard}/setInvalidResourceName`, true);
	        return;
	      }
	      await store.dispatch(`${booking_const.Model.ResourceCreationWizard}/updateResource`, {
	        name: main_core.Loc.getMessage('BRCW_DEFAULT_RESOURCE_NAME')
	      });
	    }
	    if (!store.state[booking_const.Model.ResourceCreationWizard].resource.typeId) {
	      await store.dispatch(`${booking_const.Model.ResourceCreationWizard}/setInvalidResourceType`, true);
	      return;
	    }
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _validateIntegrationCalendarUser)[_validateIntegrationCalendarUser]()) {
	      return;
	    }
	    await super.next();
	    booking_lib_analytics.RcwAnalytics.sendAddResourceStep2();
	  }
	  async back() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isFirstStep)[_isFirstStep]) {
	      main_core.Event.EventEmitter.emit(booking_const.EventName.CloseWizard);
	    } else {
	      await super.back();
	    }
	  }
	}
	function _get_isFirstStep() {
	  return this.store.getters[`${booking_const.Model.ResourceCreationWizard}/startStep`] === this.step;
	}
	function _validateIntegrationCalendarUser2() {
	  const isIntegrationCalendarEnabled = this.store.getters[`${booking_const.Model.ResourceCreationWizard}/isIntegrationCalendarEnabled`];
	  const calendarEntity = this.store.getters[`${booking_const.Model.ResourceCreationWizard}/entityCalendar`];
	  if (!isIntegrationCalendarEnabled || !calendarEntity || calendarEntity.data.userIds.length > 0) {
	    return true;
	  }
	  if (!calendarEntity.data.locationId && calendarEntity.data.reminders.length === 0) {
	    return true;
	  }
	  void this.store.dispatch(`${booking_const.Model.ResourceCreationWizard}/setInvalidIntegrationCalendarUser`, true);
	  return false;
	}

	const ResourceCreationWizardFooter = {
	  name: 'ResourceCreationWizardFooter',
	  props: {
	    step: {
	      type: Number,
	      required: true
	    },
	    disabled: Boolean
	  },
	  data() {
	    return {
	      steps: [new ChooseResourceStep(), new ResourceSettingsStep(), new ResourceNotificationStep()]
	    };
	  },
	  computed: ui_vue3_vuex.mapGetters({
	    isSaving: `${booking_const.Model.ResourceCreationWizard}/isSaving`
	  }),
	  components: {
	    BackButton,
	    NextButton
	  },
	  template: `
		<div v-if="!steps[step - 1].hidden" class="resource-creation-wizard__footer">
			<NextButton :step :steps :disabled :waiting="isSaving"/>
			<BackButton :step :steps :disabled="isSaving"/>
		</div>
	`
	};

	const ResourceType = {
	  name: 'ResourceType',
	  emits: ['selected'],
	  props: {
	    resourceType: {
	      type: Object,
	      required: true
	    }
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set
	    };
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  template: `
		<div class="booking--rcw--resource-type-item" @click="$emit('selected', resourceType)">
			<div class="rcw__resource-type">
				<div :class="['booking--rcw--resource-type__icon', 'booking--rcw--resource-type__icon--' + resourceType.code]">
				</div>
				<div class="rcw__resource-type__row">
					<div class="rcw__resource-type__label">
						<div class="rcw__resource-type__label-title">{{ resourceType.name }}</div>
						<div class="rcw__resource-type__label-description">{{ resourceType.description }}</div>
					</div>
					<Icon :name="IconSet.ARROW_RIGHT"/>
				</div>
			</div>
		</div>
	`
	};

	const ResourceCategoryCard = {
	  name: 'ResourceCategoryCard',
	  setup() {
	    return {
	      code: booking_const.HelpDesk.ResourceType.code,
	      anchorCode: booking_const.HelpDesk.ResourceType.anchorCode
	    };
	  },
	  computed: {
	    resourceTypes() {
	      return this.$store.state[booking_const.Model.ResourceCreationWizard].advertisingResourceTypes;
	    }
	  },
	  methods: {
	    async selectResourceType({
	      relatedResourceTypeId
	    }) {
	      await this.$store.dispatch(`${booking_const.Model.ResourceCreationWizard}/updateResource`, {
	        typeId: relatedResourceTypeId
	      });
	      await this.$store.dispatch(`${booking_const.Model.ResourceCreationWizard}/nextStep`);
	      booking_lib_analytics.RcwAnalytics.sendAddResourceStep1();
	    }
	  },
	  components: {
	    HelpDeskLoc: booking_component_helpDeskLoc.HelpDeskLoc,
	    ResourceType
	  },
	  template: `
		<div class="resource-category-card">
			<div class="resource-category-card__header">
				<div class="resource-category-card__header__title">
					{{ loc('BRCW_CHOOSE_CATEGORY_MSGVER_1') }}
				</div>
				<HelpDeskLoc
					:message="loc('BRCW_CHOOSE_CATEGORY_DESCRIPTION_MSGVER_2')"
					:code="code"
					:anchor="anchorCode"
				/>
			</div>
			<div class="resource-category-card__content resource-creation-wizard__form">
				<ResourceType
					v-for="resourceType in resourceTypes"
					:key="resourceType.code"
					:resource-type
					:data-id="'brcw-resource-type-list-' + resourceType.code"
					@selected="selectResourceType"
				/>
			</div>
		</div>
	`
	};

	const ErrorMessage = {
	  name: 'ErrorMessage',
	  props: {
	    message: {
	      type: String,
	      default: ''
	    }
	  },
	  template: `
		<div class="booking--rcw--error-message-container">
			<div class="booking--rcw--error-message">
				<span class="ui-icon-set --warning"></span>
				<span>{{ message }}</span>
			</div>
		</div>
	`
	};

	const BaseFields = {
	  name: 'ResourceSettingsCardBaseFields',
	  emits: ['nameUpdate', 'typeUpdate'],
	  props: {
	    initialResourceName: {
	      type: String,
	      default: ''
	    },
	    initialResourceType: {
	      type: Object,
	      required: true
	    }
	  },
	  data() {
	    return {
	      entityId: booking_const.EntitySelectorEntity.ResourceType,
	      typeSelectorId: `booking-resource-creation-types${main_core.Text.getRandom()}`,
	      typeName: this.initialResourceType.typeName
	    };
	  },
	  computed: {
	    resourceName: {
	      get() {
	        return this.initialResourceName;
	      },
	      set(name = '') {
	        this.$emit('nameUpdate', name);
	      }
	    },
	    invalidResourceName() {
	      return this.$store.state[booking_const.Model.ResourceCreationWizard].invalidResourceName;
	    },
	    invalidResourceType() {
	      return this.$store.state[booking_const.Model.ResourceCreationWizard].invalidResourceType;
	    },
	    errorMessage() {
	      return this.loc('BRCW_SETTINGS_CARD_REQUIRED_FIELD');
	    }
	  },
	  methods: {
	    showTypeSelector() {
	      const dialog = this.getTypeSelectorDialog(this.$refs.typeSelectorAngle);
	      dialog.show();
	    },
	    getTypeSelectorDialog(bindElement) {
	      var _this$initialResource;
	      const typeSelectorDialog = ui_entitySelector.Dialog.getById(this.typeSelectorId);
	      if (typeSelectorDialog) {
	        typeSelectorDialog.setTargetNode(bindElement);
	        return typeSelectorDialog;
	      }
	      return new ui_entitySelector.Dialog({
	        id: this.typeSelectorId,
	        targetNode: bindElement,
	        width: 300,
	        height: 400,
	        enableSearch: true,
	        dropdownMode: true,
	        context: 'bookingResourceCreationType',
	        multiple: false,
	        cacheable: true,
	        preselectedItems: [[this.entityId, (_this$initialResource = this.initialResourceType) == null ? void 0 : _this$initialResource.typeId]],
	        entities: [{
	          id: this.entityId,
	          dynamicLoad: true,
	          dynamicSearch: true
	        }],
	        popupOptions: {
	          targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper')
	        },
	        searchOptions: {
	          allowCreateItem: true,
	          footerOptions: {
	            label: this.loc('BRCW_SETTINGS_CARD_TYPE_SELECTOR_CREATE_BTN')
	          }
	        },
	        events: {
	          'Search:onItemCreateAsync': baseEvent => {
	            return new Promise(resolve => {
	              const {
	                searchQuery
	              } = baseEvent.getData();
	              const dialog = baseEvent.getTarget();
	              this.createType(searchQuery.getQuery()).then(resourceType => {
	                this.updateResourceType(resourceType.id, resourceType.name);
	                dialog.addItem(this.prepareTypeToDialog(resourceType));
	                dialog.hide();
	                resolve();
	              }).catch(() => {
	                resolve();
	              });
	            });
	          },
	          'Item:onSelect': baseEvent => {
	            const selectedItem = baseEvent.getData().item;
	            this.updateResourceType(selectedItem.getId(), selectedItem.getTitle());
	          }
	        }
	      });
	    },
	    async createType(typeName) {
	      return booking_provider_service_resourcesTypeService.resourceTypeService.add({
	        moduleId: 'booking',
	        name: typeName
	      });
	    },
	    prepareTypeToDialog(type) {
	      return {
	        id: type.id,
	        entityId: this.entityId,
	        title: type.name,
	        sort: 1,
	        selected: true,
	        tabs: 'recents',
	        supertitle: this.loc('BRCW_SETTINGS_CARD_TYPE_SELECTOR_SUPER_TITLE'),
	        avatar: '/bitrix/js/booking/images/entity-selector/resource-type.svg'
	      };
	    },
	    updateResourceType(typeId, typeName) {
	      this.typeName = typeName;
	      this.$emit('typeUpdate', typeId);
	    },
	    scrollToBaseFieldsForm() {
	      var _this$$refs$baseField;
	      (_this$$refs$baseField = this.$refs.baseFieldsForm) == null ? void 0 : _this$$refs$baseField.scrollIntoView(true, {
	        behavior: 'smooth',
	        block: 'center'
	      });
	    }
	  },
	  watch: {
	    invalidResourceName(invalid) {
	      if (invalid) {
	        this.scrollToBaseFieldsForm();
	      }
	    },
	    invalidResourceType(invalid) {
	      if (invalid) {
	        this.scrollToBaseFieldsForm();
	      }
	    }
	  },
	  components: {
	    ErrorMessage,
	    EmptyRichLoc: booking_component_helpDeskLoc.EmptyRichLoc
	  },
	  template: `
		<div ref="baseFieldsForm" class="ui-form resource-creation-wizard__form-settings --base">
			<div class="ui-form-row-inline booking--rcw--form-row-align">
				<div class="ui-form-row">
					<div class="ui-form-label">
						<label class="ui-ctl-label-text" for="brcw-settings-resource-name">
							{{ loc('BRCW_SETTINGS_CARD_NAME_LABEL') }}
						</label>
					</div>
					<div class="ui-form-content booking--rcw--field-with-validation">
						<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
							<input
								v-model.trim="resourceName"
								id="brcw-settings-resource-name"
								data-id="brcw-settings-resource-name-input"
								type="text"
								class="ui-ctl-element"
								:class="{ '--error': invalidResourceName }"
								:placeholder="loc('BRCW_SETTINGS_CARD_NAME_LABEL')"
							/>
						</div>
						<ErrorMessage
							v-if="invalidResourceName"
							:message="errorMessage"
						/>
					</div>
					<div class="ui-form-line">
						<div class="booking--rcw--resource-name-description">
							<EmptyRichLoc
								:message="loc('BRCW_SETTINGS_CARD_NAME_DESCRIPTION')"
								:rules="['nowrap']"
							/>
						</div>
					</div>
				</div>
				<div class="ui-form-row">
					<div class="ui-form-label">
						<div class="ui-ctl-label-text">
							{{ loc('BRCW_SETTINGS_CARD_TYPE_LABEL') }}
						</div>
					</div>
					<div class="ui-form-content booking--rcw--field-with-validation">
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
							<div
								ref="typeSelectorAngle"
								class="ui-ctl-after ui-ctl-icon-angle"
							></div>
							<div
								ref="typeSelectorElement"
								data-id="brcw-settings-resource-type-selector"
								class="ui-ctl-element resource-creation-wizard__form-settings-element"
								:class="{
									'--placeholder': !typeName,
									'--error': invalidResourceType,
								}"
								@click="showTypeSelector"
							>
								<template v-if="typeName">
									{{ typeName }}
								</template>
								<template v-else>
									{{ loc('BRCW_SETTINGS_CARD_TYPE_PLACEHOLDER') }}
								</template>
							</div>
						</div>
						<ErrorMessage
							v-if="invalidResourceType"
							:message="errorMessage"
						/>
					</div>
				</div>
			</div>
		</div>
	`
	};

	const TitleLayout = {
	  name: 'ResourceSettingsCardTitleLayout',
	  props: {
	    title: {
	      type: String,
	      required: true
	    },
	    iconType: {
	      type: String,
	      required: true
	    }
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  template: `
		<div class="resource-creation-wizard__form-settings-title-row">
			<Icon
				:name="iconType"
				:color="'var(--ui-color-primary)'"
				:size="24"
			/>
			<div class="resource-creation-wizard__form-settings-title">
				{{ title }}
			</div>
		</div>
	`
	};

	const ScheduleItem = {
	  name: 'ScheduleItem',
	  emits: ['update:model-value'],
	  props: {
	    modelValue: {
	      type: Boolean,
	      required: true
	    },
	    itemClass: {
	      type: String,
	      required: true
	    },
	    title: {
	      type: String,
	      required: true
	    },
	    description: {
	      type: String,
	      required: true
	    },
	    value: {
	      type: Boolean,
	      required: true
	    }
	  },
	  setup() {
	    return {
	      code: booking_const.HelpDesk.ResourceSchedule.code,
	      anchorCode: booking_const.HelpDesk.ResourceSchedule.anchorCode
	    };
	  },
	  computed: {
	    isSelected() {
	      return this.modelValue.toString() === this.value.toString();
	    }
	  },
	  methods: {
	    selectOption() {
	      this.$emit('update:model-value', this.value);
	    }
	  },
	  components: {
	    HelpDeskLoc: booking_component_helpDeskLoc.HelpDeskLoc
	  },
	  template: `
		<div
			:class="['booking--rcw--schedule-item', { '--selected': isSelected }]"
			@click="selectOption"
		>
			<div class="booking--rcw--schedule-item-radio ui-ctl-radio">
				<input
					:id="itemClass"
					:checked="isSelected"
					:value="value.toString()"
					type="radio"
					class="ui-ctl-element"
					@input="$emit('update:model-value', $event.target.value === 'true')"
				/>
			</div>
			<div class="booking--rcw--schedule-item-text">
				<label
					:for="itemClass"
					class="booking--rcw--schedule-item-text-title">
					{{ title }}
				</label>
				<HelpDeskLoc
					:message="description"
					:code="code"
					:anchor="anchorCode"
					:rules="['nowrap']"
					class="booking--rcw--schedule-item-text-description"
					link-class="booking--rcw--more booking--rcw--schedule-item-text-description-more"
				/>
			</div>
			<div class="booking--rcw--schedule-item-view" :class="itemClass"></div>
		</div>
	`
	};

	const ScheduleTypes = {
	  name: 'ResourceSettingsCardScheduleTypes',
	  emits: ['update:model-value'],
	  props: {
	    modelValue: {
	      type: Boolean,
	      default: true
	    }
	  },
	  setup() {
	    const title = main_core.Loc.getMessage('BRCW_SETTINGS_CARD_SCHEDULE_TITLE_MSGVER_1');
	    const titleIconType = ui_iconSet_api_vue.Set.OPENED_EYE;
	    const items = [{
	      id: 'common',
	      itemClass: 'resource-creation-wizard__form-settings-schedule-view-common',
	      title: main_core.Loc.getMessage('BRCW_SETTINGS_CARD_SCHEDULE_COLUMNS_TITLE_MSGVER_1'),
	      description: main_core.Loc.getMessage('BRCW_SETTINGS_CARD_SCHEDULE_COLUMNS_DESCRIPTION_MSGVER_2'),
	      value: true
	    }, {
	      id: 'extra',
	      itemClass: 'resource-creation-wizard__form-settings-schedule-view-extra',
	      title: main_core.Loc.getMessage('BRCW_SETTINGS_CARD_SCHEDULE_CROSS_RESOURCING_TITLE_MSGVER_1'),
	      description: main_core.Loc.getMessage('BRCW_SETTINGS_CARD_SCHEDULE_CROSS_RESOURCING_DESCRIPTION_MSGVER_2'),
	      value: false
	    }];
	    return {
	      items,
	      title,
	      titleIconType
	    };
	  },
	  components: {
	    ScheduleItem,
	    TitleLayout
	  },
	  template: `
		<div class="ui-form resource-creation-wizard__form-settings --schedule">
			<TitleLayout
				:title="title"
				:iconType="titleIconType"
			/>
			<div class="resource-creation-wizard__form-settings-schedule-view">
				<ScheduleItem
					v-for="item in items"
					:key="item.id"
					:data-id="'brcw-resource-schedule-view-' + item.id"
					:model-value="modelValue"
					:item-class="item.itemClass"
					:title="item.title"
					:description="item.description"
					:value="item.value"
					@update:model-value="$emit('update:model-value', $event)"
				/>
			</div>
		</div>
	`
	};

	const TextLayout = {
	  name: 'ResourceSettingsCardTextLayout',
	  props: {
	    type: {
	      type: String,
	      required: true
	    },
	    text: {
	      type: String,
	      required: true
	    }
	  },
	  setup(props) {
	    return {
	      code: booking_const.HelpDesk[`Resource${props.type}`].code,
	      anchorCode: booking_const.HelpDesk[`Resource${props.type}`].anchorCode
	    };
	  },
	  components: {
	    HelpDeskLoc: booking_component_helpDeskLoc.HelpDeskLoc
	  },
	  template: `
		<div class="resource-creation-wizard__form-settings-text-row">
			<HelpDeskLoc
				:message="text"
				:code="code"
				:anchor="anchorCode"
				class="resource-creation-wizard__form-settings-text"
			/>
		</div>
	`
	};

	// @vue/component
	const SettingsItem = {
	  name: 'IntegrationSettingsItem',
	  components: {
	    ErrorMessage
	  },
	  props: {
	    title: {
	      type: String,
	      required: true
	    },
	    description: {
	      type: String,
	      required: true
	    },
	    errorMessage: {
	      type: String,
	      default: null
	    }
	  },
	  template: `
		<div class="resource-creation-wizard__integration-block-settings-item">
			<div class="resource-creation-wizard__integration-block-settings-item-info">
				<div class="resource-creation-wizard__integration-block-settings-item-info-title">
					{{ title }}
				</div>
				<div class="resource-creation-wizard__integration-block-settings-item-info-description">
					{{ description }}
				</div>
			</div>
			<slot/>
			<ErrorMessage v-if="errorMessage" :message="errorMessage"/>
		</div>
	`
	};

	// @vue/component
	const SettingsSelector = {
	  name: 'SettingsSelector',
	  props: {
	    values: {
	      type: Array,
	      required: true
	    },
	    entitiesId: {
	      type: String,
	      required: true
	    },
	    multiple: {
	      type: Boolean,
	      default: true
	    },
	    disabled: {
	      type: Boolean,
	      default: false
	    },
	    tab: {
	      type: Boolean,
	      default: false
	    }
	  },
	  emits: ['change'],
	  watch: {
	    disabled: {
	      handler(disabled) {
	        var _this$selector;
	        (_this$selector = this.selector) == null ? void 0 : _this$selector.setLocked(disabled);
	      },
	      immediate: true
	    }
	  },
	  created() {
	    this.selector = this.createSelector();
	  },
	  mounted() {
	    this.mountSelector();
	  },
	  beforeUnmount() {
	    this.destroySelector();
	  },
	  methods: {
	    createSelector() {
	      const tagSelectionOptions = {
	        multiple: this.multiple,
	        addButtonCaption: this.loc('BRCW_SETTINGS_CARD_INTEGRATION_SELECTOR_BTN'),
	        showCreateButton: false,
	        maxHeight: 40,
	        dialogOptions: {
	          context: 'bookingResourceIntersection',
	          width: 290,
	          height: 340,
	          dropdownMode: true,
	          compactView: true,
	          enableSearch: true,
	          cacheable: true,
	          showAvatars: false,
	          popupOptions: {
	            targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper')
	          },
	          entities: [{
	            id: this.entitiesId,
	            dynamicLoad: true,
	            dynamicSearch: true
	          }],
	          preselectedItems: this.values.map(id => [this.entitiesId, id]),
	          events: {
	            'Item:onSelect': event => {
	              this.select(event.getData().item.id);
	            },
	            'Item:onDeselect': event => {
	              this.deselect(event.getData().item.id);
	            }
	          }
	        }
	      };
	      if (this.tab) {
	        tagSelectionOptions.dialogOptions.tabs = [{
	          id: this.entitiesId
	        }];
	      }
	      return new ui_entitySelector.TagSelector(tagSelectionOptions);
	    },
	    select(itemId) {
	      const itemsSet = new Set(this.values);
	      itemsSet.add(itemId);
	      this.$emit('change', [...itemsSet]);
	    },
	    deselect(itemId) {
	      this.$emit('change', this.values.filter(id => id !== itemId));
	    },
	    mountSelector() {
	      this.selector.renderTo(this.$refs.settingsSelector);
	      if (this.disabled) {
	        this.selector.setLocked(this.disabled);
	      }
	    },
	    destroySelector() {
	      this.selector.getDialog().destroy();
	      this.selector = null;
	      this.$refs.settingsSelector.innerHTML = '';
	    }
	  },
	  template: `
		<div
			ref="settingsSelector"
			class="resource-creation-wizard__integration-block-settings-selector"
		></div>
	`
	};

	const {
	  mapGetters: mapResourceGetters,
	  mapActions
	} = ui_vue3_vuex.createNamespacedHelpers(booking_const.Model.ResourceCreationWizard);

	// @vue/component
	const IntegrationCalendar = {
	  name: 'IntegrationCalendar',
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon,
	    Switcher: booking_component_switcher.Switcher,
	    SettingsItem,
	    SettingsSelector,
	    UiReminder: booking_component_reminder.Reminder
	  },
	  setup() {
	    const iconName = ui_iconSet_api_vue.Main.CALENDAR_1;
	    const iconColor = 'var(--ui-color-primary)';
	    const iconSize = 22;
	    const entityRoomId = booking_const.EntitySelectorEntity.Room;
	    const entityUserId = booking_const.EntitySelectorEntity.User;
	    return {
	      iconName,
	      iconColor,
	      iconSize,
	      entityRoomId,
	      entityUserId
	    };
	  },
	  data() {
	    return {
	      isVisible: null
	    };
	  },
	  computed: {
	    ...mapResourceGetters({
	      entityCalendar: 'entityCalendar',
	      isIntegrationCalendarEnabled: 'isIntegrationCalendarEnabled',
	      invalidIntegrationCalendarUser: 'invalidIntegrationCalendarUser'
	    }),
	    isEnabled: {
	      get() {
	        return this.$store.getters[`${booking_const.Model.ResourceCreationWizard}/isIntegrationCalendarEnabled`];
	      },
	      set(isIntegrationCalendarEnabled) {
	        this.$store.dispatch(`${booking_const.Model.ResourceCreationWizard}/setIsIntegrationCalendarEnabled`, isIntegrationCalendarEnabled);
	      }
	    },
	    reminders: {
	      get() {
	        var _this$entityCalendar, _this$entityCalendar$;
	        return (((_this$entityCalendar = this.entityCalendar) == null ? void 0 : (_this$entityCalendar$ = _this$entityCalendar.data) == null ? void 0 : _this$entityCalendar$.reminders) || []).map(r => r.count);
	      },
	      set(reminders) {
	        this.updateResourceEntityCalendar({
	          reminders: reminders.map(r => {
	            return {
	              type: 'min',
	              count: r
	            };
	          })
	        });
	      }
	    },
	    isDisabled() {
	      var _this$entityCalendar2;
	      if (!this.isEnabled) {
	        return true;
	      }
	      return !this.isEnabled || !this.entityCalendar || ((_this$entityCalendar2 = this.entityCalendar) == null ? void 0 : _this$entityCalendar2.data.userIds.length) === 0;
	    },
	    userErrorMessage() {
	      if (!this.isEnabled || !this.invalidIntegrationCalendarUser) {
	        return null;
	      }
	      return this.loc('BRCW_SETTINGS_CARD_INTEGRATION_CALENDAR_SETTING_USER_ERROR_MESSAGE');
	    }
	  },
	  watch: {
	    invalidIntegrationCalendarUser(invalid) {
	      if (invalid) {
	        this.scrollToUserField();
	      }
	    }
	  },
	  beforeMount() {
	    this.isVisible = this.$store.getters[`${booking_const.Model.ResourceCreationWizard}/isIntegrationCalendarEnabled`];
	  },
	  methods: {
	    ...mapActions(['updateResource', 'updateResourceEntityCalendar', 'createResourceEntityCalendar', 'setInvalidIntegrationCalendarUser']),
	    async toggleEnabled() {
	      if (!this.entityCalendar) {
	        await this.createResourceEntityCalendar();
	      }
	      this.isEnabled = !this.isEnabled;
	      if (this.isEnabled && !this.isVisible) {
	        this.isVisible = true;
	      }
	      if (!this.isEnabled) {
	        await this.setInvalidIntegrationCalendarUser(false);
	      }
	    },
	    async updateUsers(userIds) {
	      await this.updateResourceEntityCalendar({
	        userIds: userIds.map(id => parseInt(id, 10))
	      });
	      if (userIds.length > 0) {
	        await this.setInvalidIntegrationCalendarUser(false);
	      }
	    },
	    async updateLocationId(locationIds) {
	      var _parseInt;
	      await this.updateResourceEntityCalendar({
	        locationId: locationIds.length > 0 ? (_parseInt = parseInt(locationIds[0], 10)) != null ? _parseInt : null : null
	      });
	    },
	    scrollToUserField() {
	      var _this$$refs$user$$el;
	      (_this$$refs$user$$el = this.$refs.user.$el) == null ? void 0 : _this$$refs$user$$el.scrollIntoView(true, {
	        behavior: 'smooth',
	        block: 'center'
	      });
	    }
	  },
	  template: `
		<div class="resource-creation-wizard__integration-block">
			<div class="resource-creation-wizard__integration-block-header">
				<Icon :name="iconName" :size="iconSize" :color="iconColor"/>
				<div class="resource-creation-wizard__integration-block-title">
					{{ loc('BRCW_SETTINGS_CARD_INTEGRATION_CALENDAR_TITLE') }}
				</div>
				<Switcher
					class="resource-creation-wizard__integration-block-switcher"
					data-id="resource-creation-wizard__integration-block-switcher-calendar"
					:hiddenText="true"
					:model-value="isEnabled"
					@toggle="toggleEnabled"
				/>
			</div>
			<div class="resource-creation-wizard__integration-block-description">
				{{ loc('BRCW_SETTINGS_CARD_INTEGRATION_CALENDAR_TEXT') }}
			</div>
			<div
				v-if="isVisible"
				class="resource-creation-wizard__integration-block-settings"
			>
				<SettingsItem
					ref="user"
					:title="loc('BRCW_SETTINGS_CARD_INTEGRATION_CALENDAR_SETTING_USER_TITLE')"
					:description="loc('BRCW_SETTINGS_CARD_INTEGRATION_CALENDAR_SETTING_USER_TEXT')"
					:errorMessage="userErrorMessage"
				>
					<SettingsSelector
						:class="{ '--warning': entityCalendar?.data?.userIds.length === 0 && entityCalendar?.data?.locationId}"
						id="resource-creation-wizard__integration-block-users"
						data-element="resource-creation-wizard__integration-block-users"
						:entitiesId="entityUserId"
						:values="entityCalendar?.data.userIds ?? []"
						:disabled="!isEnabled"
						@change="updateUsers"
					/>
				</SettingsItem>
				<SettingsItem
					:title="loc('BRCW_SETTINGS_CARD_INTEGRATION_CALENDAR_SETTING_ROOM_TITLE')"
					:description="loc('BRCW_SETTINGS_CARD_INTEGRATION_CALENDAR_SETTING_ROOM_TEXT')"
				>
					<SettingsSelector
						id="resource-creation-wizard__integration-block-resource"
						data-element="resource-creation-wizard__integration-block-resource"
						:entitiesId="entityRoomId"
						:multiple="false"
						:values="entityCalendar?.data.locationId ? [String(entityCalendar.data.locationId)] : []"
						:disabled="isDisabled"
						tab
						@change="updateLocationId"
					/>
				</SettingsItem>
				<SettingsItem
					:title="loc('BRCW_SETTINGS_CARD_INTEGRATION_CALENDAR_SETTING_REMINDER_TITLE')"
					:description="loc('BRCW_SETTINGS_CARD_INTEGRATION_CALENDAR_SETTING_REMINDER_TEXT')"
				>
					<UiReminder
						v-model="reminders"
						:disabled="isDisabled"
					/>
				</SettingsItem>
			</div>
		</div>
	`
	};

	const Integration = {
	  name: 'Integration',
	  components: {
	    TitleLayout,
	    TextLayout,
	    IntegrationCalendar
	  },
	  setup() {
	    const title = main_core.Loc.getMessage('BRCW_SETTINGS_CARD_INTEGRATION_TITLE');
	    const titleIconType = ui_iconSet_api_vue.Set.COLLABORATION;
	    return {
	      title,
	      titleIconType
	    };
	  },
	  template: `
		<div class="ui-form resource-creation-wizard__form-settings">
			<TitleLayout
				:title="title"
				:iconType="titleIconType"
			/>
			<TextLayout
				type="IntegrationSettings"
				:text="loc('BRCW_SETTINGS_CARD_INTEGRATION_DESCRIPTION')"
			/>
			<IntegrationCalendar/>
		</div>
	`
	};

	const {
	  mapGetters: mapResourceGetters$1
	} = ui_vue3_vuex.createNamespacedHelpers('resource-creation-wizard');
	const WorkTimeMixin = {
	  data() {
	    return {
	      selected: {
	        Mon: false,
	        Tue: false,
	        Wed: false,
	        Thu: false,
	        Fri: false,
	        Sat: false,
	        Sun: false
	      },
	      daysLabel: ''
	    };
	  },
	  created() {
	    this.initialDays.forEach(day => {
	      if (Object.prototype.hasOwnProperty.call(this.selected, day)) {
	        this.selected[day] = true;
	      }
	    });
	    this.daysLabel = this.formatDaysLabel();
	  },
	  methods: {
	    formatDaysLabel() {
	      const defaultString = this.loc('BRCW_SETTINGS_CARD_WORK_TIME_DAYS');
	      const weekDays = this.companyScheduleSlots[0].weekDays;
	      if (this.isArraysEqual(this.selectedDays, weekDays)) {
	        return defaultString;
	      }
	      const orderedDays = Object.keys(this.daysLabelMap);
	      const sortedSelectedDays = this.selectedDays.sort((a, b) => {
	        return orderedDays.indexOf(a) - orderedDays.indexOf(b);
	      });
	      const formattedDays = sortedSelectedDays.map(day => this.daysLabelMap[day]);
	      return String(formattedDays.join(', '));
	    },
	    getDayDefaultIndex(dayKey) {
	      const dayIndices = {
	        Sun: 0,
	        Mon: 1,
	        Tue: 2,
	        Wed: 3,
	        Thu: 4,
	        Fri: 5,
	        Sat: 6
	      };
	      return dayIndices[dayKey];
	    },
	    isArraysEqual(first, second) {
	      return first.length === second.length && first.every((value, index) => value === second[index]);
	    }
	  },
	  computed: {
	    ...mapResourceGetters$1({
	      companyScheduleSlots: 'getCompanyScheduleSlots',
	      weekStart: 'weekStart'
	    }),
	    selectedDays() {
	      return Object.keys(this.selected).filter(day => this.selected[day]);
	    },
	    daysLabelMap() {
	      const weekdays = [];
	      const format = 'D';
	      const allDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
	      const startIndex = allDays.indexOf(this.weekStart);
	      const orderedDays = [...allDays.slice(startIndex), ...allDays.slice(0, startIndex)];
	      orderedDays.forEach((dayKey, index) => {
	        const currentDayIndex = new Date().getDay();
	        const targetDayIndex = this.getDayDefaultIndex(dayKey);
	        const dayDifference = (targetDayIndex - currentDayIndex + 7) % 7;
	        const dayDate = new Date();
	        dayDate.setDate(dayDate.getDate() + dayDifference);
	        weekdays[index] = main_date.DateTimeFormat.format(format, dayDate);
	      });
	      return orderedDays.reduce((result, dayKey, index) => {
	        // eslint-disable-next-line no-param-reassign
	        result[dayKey] = weekdays[index];
	        return result;
	      }, {});
	    }
	  }
	};

	const WeekDaysPopup = {
	  name: 'ResourceSettingsCardWeekDaysPopup',
	  emits: ['select', 'close'],
	  props: {
	    id: {
	      type: String,
	      required: true
	    },
	    bindElement: {
	      type: HTMLElement,
	      required: true
	    },
	    initialDays: {
	      type: Array,
	      required: true
	    }
	  },
	  components: {
	    Popup: booking_component_popup.Popup
	  },
	  mixins: [WorkTimeMixin],
	  methods: {
	    close() {
	      this.$emit('close');
	    },
	    click(day) {
	      const trueKeys = Object.keys(this.selected).filter(key => this.selected[key] === true);
	      if (trueKeys.length === 1 && trueKeys[0] === day) {
	        return;
	      }
	      this.selected[day] = !this.selected[day];
	      this.$emit('select', this.selectedDays, this.formatDaysLabel());
	    }
	  },
	  computed: {
	    popupId() {
	      return `booking-work-time-popup-${this.id}`;
	    },
	    config() {
	      return {
	        bindElement: this.bindElement,
	        targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper'),
	        offsetRight: this.bindElement.offsetWidth,
	        angle: false,
	        bindOptions: {
	          forceBindPosition: true,
	          forceLeft: true
	        }
	      };
	    }
	  },
	  template: `
		<Popup
			:id="popupId"
			:config="config"
			@close="close"
			ref="popup"
		>
			<div class="resource-creation-wizard__form-week-days-popup">
				<div
					v-for="(day, index) in daysLabelMap"
					:key="index"
					:data-id="'brcw-resource-work-time-week-day-' + index + '-' + id"
					class="resource-creation-wizard__form-week-days-popup-weekday"
					:class="{ '--selected': selected[index] }"
					@click="() => click(index)"
				>
					<div class="resource-creation-wizard__form-week-days-popup-weekday-text">
						{{ day }}
					</div>
					<div class="resource-creation-wizard__form-week-days-popup-weekday-icon"></div>
				</div>
			</div>
		</Popup>
	`
	};

	const WorkTimeSlotRange = {
	  name: 'ResourceSettingsCardWorkTimeSlotRange',
	  emits: ['add', 'update', 'remove'],
	  props: {
	    id: {
	      type: Number,
	      required: true
	    },
	    isLastRange: {
	      type: Boolean,
	      required: true
	    },
	    initialSlotRange: {
	      type: Object,
	      required: true
	    }
	  },
	  components: {
	    WeekDaysPopup
	  },
	  mixins: [WorkTimeMixin],
	  data() {
	    return {
	      fromId: 'from',
	      toId: 'to',
	      fromTs: 0,
	      toTs: 0,
	      duration: 0,
	      isShownDays: false,
	      arrow: {
	        days: false,
	        from: false,
	        to: false
	      },
	      removeSlot: !this.isLastRange,
	      localSlotRange: {
	        ...this.initialSlotRange
	      },
	      initialDays: [...this.initialSlotRange.weekDays]
	    };
	  },
	  created() {
	    this.fromTs = this.slotRange.from;
	    this.toTs = this.slotRange.to;
	    this.duration = this.toTs - this.fromTs;
	  },
	  watch: {
	    'slotRange.from': function (value) {
	      this.fromTs = value;
	      this.toTs = this.fromTs + this.duration;
	      this.update();
	    },
	    'slotRange.to': function (value) {
	      this.toTs = value;
	      if (this.toTs <= this.fromTs) {
	        this.fromTs = this.toTs - this.duration;
	      }
	      this.duration = this.toTs - this.fromTs;
	      this.update();
	    }
	  },
	  methods: {
	    showDays() {
	      this.isShownDays = true;
	      this.arrow.days = true;
	    },
	    hideDays() {
	      this.isShownDays = false;
	      this.arrow.days = false;
	    },
	    selectDays(weekDays, daysLabel) {
	      this.slotRange.weekDays = weekDays;
	      this.daysLabel = daysLabel;
	      this.update();
	    },
	    show(bindElement, id) {
	      if (this.arrow[id] === true) {
	        return;
	      }
	      const menuId = `booking-work-time-popup-${this.id}-${id}`;
	      main_popup.MenuManager.destroy(menuId);
	      const timeList = main_popup.MenuManager.create({
	        id: menuId,
	        className: 'resource-creation-wizard__form-work-time-menu',
	        bindElement,
	        targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper'),
	        autoHide: true,
	        closeByEsc: true,
	        events: {
	          onShow: () => {
	            this.arrow[id] = true;
	            const selectedItem = timeList.getMenuItems().find(item => main_core.Dom.hasClass(item.getLayout().item, '--selected'));
	            const itemHeight = 36;
	            const offset = 2 * itemHeight;
	            const scrollContainer = timeList.getPopupWindow().getContentContainer();
	            const topPosition = main_core.Dom.getRelativePosition(selectedItem.getContainer(), scrollContainer).top;
	            scrollContainer.scrollTo({
	              top: scrollContainer.scrollTop + topPosition - offset,
	              behavior: 'instant'
	            });
	          },
	          onClose: () => {
	            this.arrow[id] = false;
	          }
	        },
	        maxHeight: 200,
	        minWidth: bindElement.offsetWidth
	      });
	      for (const [key, value] of Object.entries(this.generateTimeMap(id))) {
	        const minutes = parseInt(key, 10);
	        const defaultClass = 'menu-popup-item menu-popup-no-icon';
	        timeList.addMenuItem({
	          text: value,
	          className: this.slotRange[id] === minutes ? `${defaultClass} --selected` : defaultClass,
	          dataset: {
	            id,
	            minutes
	          },
	          onclick: () => {
	            this.slotRange[id] = parseInt(minutes, 10);
	            timeList.close();
	          }
	        });
	      }
	      timeList.show();
	    },
	    generateTimeMap(id) {
	      const timeMap = {};
	      const timeFormat = main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
	      const interval = 30;
	      const from = id === this.toId ? this.fromTs + interval : 0;
	      const to = id === this.fromId ? this.toTs : 1440;
	      for (let minutes = from; minutes < to; minutes += interval) {
	        const timestamp = new Date().setHours(0, minutes, 0, 0) / 1000;
	        timeMap[minutes] = main_date.DateTimeFormat.format(timeFormat, timestamp);
	      }
	      if (id === this.toId) {
	        const midnightTimestamp = new Date().setHours(0, 0, 0, 0) / 1000;
	        timeMap[to] = main_date.DateTimeFormat.format(timeFormat, midnightTimestamp);
	      }
	      return timeMap;
	    },
	    clickBtn() {
	      if (this.removeSlot === false) {
	        this.removeSlot = true;
	        this.$emit('add');
	      } else {
	        this.removeSlot = false;
	        this.$emit('remove', this.id);
	      }
	    },
	    update() {
	      this.$emit('update', this.id, this.slotRange);
	    }
	  },
	  computed: {
	    daysId() {
	      return `days-${this.id}`;
	    },
	    days() {
	      return this.$refs.days;
	    },
	    slotRange() {
	      return this.localSlotRange;
	    },
	    fromLabel() {
	      const timeMap = this.generateTimeMap(this.fromId);
	      return timeMap[this.slotRange.from];
	    },
	    toLabel() {
	      const timeMap = this.generateTimeMap(this.toId);
	      return timeMap[this.slotRange.to];
	    }
	  },
	  template: `
		<div class="ui-form-content resource-creation-wizard__form-work-time-selector-widget">
			<div class="ui-form-row resource-creation-wizard__form-work-time-selector-widget-days">
				<div
					ref="days"
					:data-id="'brcw-resource-work-time-slot-range-days-' + id"
					class="rcw-ui-ctl ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-sm ui-ctl-round"
					@click="showDays"
				>
					<div
						class="ui-ctl-after ui-ctl-icon-angle"
						:class="{ '--active': arrow.days }"
					></div>
					<div class="ui-ctl-element">
						{{ daysLabel }}
					</div>
					<WeekDaysPopup
						v-if="isShownDays"
						:id="daysId"
						:bindElement="days"
						:initialDays="slotRange.weekDays"
						@select="selectDays"
						@close="hideDays"
					/>
				</div>
			</div>
			<div class="ui-form-row resource-creation-wizard__form-work-time-selector-widget-time">
				<div class="ui-form-content">
					<div class="ui-form-row">
						<div
							ref="from"
							:data-id="'brcw-resource-work-time-slot-range-from-' + id"
							class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-sm ui-ctl-round"
							@click="() => show($refs.from, fromId)"
						>
							<div
								class="ui-ctl-after ui-ctl-icon-angle"
								:class="{ '--active': arrow.from }"
							></div>
							<div class="ui-ctl-element">{{ fromLabel }}</div>
						</div>
					</div>
					<div class="ui-form-row">
						<div class="resource-creation-wizard__form-work-time-selector-dash"></div>
					</div>
					<div class="ui-form-row">
						<div
							ref="to"
							:data-id="'brcw-resource-work-time-slot-range-to-' + id"
							class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-sm ui-ctl-round"
							@click="() => show($refs.to, toId)"
						>
							<div
								class="ui-ctl-after ui-ctl-icon-angle"
								:class="{ '--active': arrow.to }"
							></div>
							<div class="ui-ctl-element">{{ toLabel }}</div>
						</div>
					</div>
				</div>
			</div>
			<div class="ui-form-row resource-creation-wizard__form-work-time-selector-btn-row">
				<div
					:data-id="'brcw-resource-work-time-slot-range-add-' + id"
					class="resource-creation-wizard__form-work-time-selector-btn"
					:class="{ '--remove': removeSlot }"
					@click="clickBtn"
				></div>
			</div>
		</div>
	`
	};

	const WorkTimeSelector = {
	  name: 'ResourceSettingsCardWorkTimeSelector',
	  emits: ['update', 'updateGlobalSchedule', 'getGlobalSchedule'],
	  props: {
	    initialSlotRanges: {
	      type: Array,
	      required: true
	    },
	    isGlobalSchedule: {
	      type: Boolean,
	      required: true
	    },
	    defaultSlotRange: {
	      type: Object,
	      required: true
	    },
	    initialTimezone: {
	      type: String,
	      required: true
	    },
	    currentTimezone: {
	      type: String,
	      required: true
	    },
	    isCompanyScheduleAccess: {
	      type: Boolean,
	      required: true
	    },
	    companyScheduleUrl: {
	      type: String,
	      required: true
	    }
	  },
	  components: {
	    WorkTimeSlotRange,
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  data() {
	    return {
	      localSlotRanges: this.processSlotRanges(this.initialSlotRanges),
	      isChecked: this.isGlobalSchedule,
	      hasTimezonesDifference: false,
	      formattedInitialOffset: '',
	      formattedCurrentOffset: ''
	    };
	  },
	  created() {
	    this.calculateDifferenceBetweenTimezones(this.initialTimezone, this.currentTimezone);
	  },
	  watch: {
	    initialSlotRanges: {
	      handler(newSlotRanges) {
	        this.localSlotRanges = this.processSlotRanges(newSlotRanges);
	      },
	      deep: true
	    },
	    isChecked(checked) {
	      this.$emit('updateGlobalSchedule', checked);
	    }
	  },
	  methods: {
	    openCompanyWorkTime(event) {
	      const isTextClick = event.target === event.currentTarget;
	      if (!isTextClick && this.isCompanyScheduleAccess && this.companyScheduleUrl !== '') {
	        top.BX.Event.EventEmitter.subscribeOnce(top.BX.Event.EventEmitter.GLOBAL_TARGET, 'SidePanel.Slider:onLoad', baseEvent => {
	          const slider = baseEvent.getTarget();
	          slider.getWindow().BX.Event.EventEmitter.subscribeOnce(slider.getWindow().BX.Event.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:onSuccessSave', innerBaseEvent => {
	            const extraSettings = innerBaseEvent.getData();
	            extraSettings.reloadAfterClose = false;
	            this.$emit('getGlobalSchedule');
	          });
	        });
	        BX.SidePanel.Instance.open(this.companyScheduleUrl, {
	          cacheable: false
	        });
	        event.preventDefault();
	      }
	    },
	    updateSlotRange(index, slotRange) {
	      this.slotRanges[index] = slotRange;
	      this.$emit('update', this.slotRanges);
	    },
	    addSlotRange() {
	      this.slotRanges.push(this.defaultSlotRange);
	      this.$emit('update', this.slotRanges);
	    },
	    removeSlotRange(index) {
	      this.slotRanges.splice(index, 1);
	      this.$emit('update', this.slotRanges);
	    },
	    processSlotRanges(slotRanges) {
	      return slotRanges.map(slotRange => {
	        if (slotRange.id === null) {
	          return {
	            ...slotRange,
	            id: main_core.Text.getRandom()
	          };
	        }
	        return slotRange;
	      });
	    },
	    getTimezoneOffset(timeZone) {
	      return booking_lib_timezone.Timezone.getOffset(Date.now(), timeZone) / -60;
	    },
	    calculateDifferenceBetweenTimezones(initialTimezone, currentTimezone) {
	      const initialOffset = this.getTimezoneOffset(initialTimezone);
	      const currentOffset = this.getTimezoneOffset(currentTimezone);
	      this.hasTimezonesDifference = initialOffset !== currentOffset;
	      if (this.hasTimezonesDifference) {
	        const formatOffset = offset => {
	          const sign = offset >= 0 ? '+' : '-';
	          const hours = Math.floor(Math.abs(offset) / 60);
	          const minutes = Math.abs(offset) % 60;
	          return `${sign}${hours}:${minutes.toString().padStart(2, '0')}`;
	        };
	        this.formattedInitialOffset = `UTC ${formatOffset(-initialOffset)} ${initialTimezone}`;
	        const totalDifferenceMinutes = currentOffset - initialOffset;
	        this.formattedCurrentOffset = formatOffset(totalDifferenceMinutes);
	      }
	    },
	    onSlotClick() {
	      this.isChecked = false;
	    }
	  },
	  computed: {
	    companyWorkTimeOptionLabel() {
	      return this.loc('BRCW_SETTINGS_CARD_WORK_TIME_COMPANY_OPTION');
	    },
	    slotRanges() {
	      return this.localSlotRanges;
	    },
	    isSlotsDisabled() {
	      return this.isChecked;
	    },
	    timezoneIconType() {
	      return ui_iconSet_api_vue.Set.EARTH_TIME;
	    },
	    formattedTimezonesText() {
	      return this.loc('BRCW_SETTINGS_CARD_WORK_TIME_TIMEZONES').replace('#utc#', this.formattedInitialOffset).replace('#difference#', this.formattedCurrentOffset);
	    }
	  },
	  template: `
		<div class="ui-form resource-creation-wizard__form-work-time-selector">
			<div class="ui-form-row-inline">
				<div class="ui-form-row">
					<label
						for="brcw-settings-work-time-company-option"
						class="ui-ctl ui-ctl-checkbox"
					>
						<input
							id="brcw-settings-work-time-company-option"
							data-id="brcw-settings-work-time-company-option"
							type="checkbox"
							class="ui-ctl-element"
							v-model="isChecked"
						>
						<span
							class="ui-ctl-label-text work-time-selector-label-text"
							:class="{'--disabled': !isCompanyScheduleAccess || companyScheduleUrl === ''}"
							@click="openCompanyWorkTime"
							v-html="companyWorkTimeOptionLabel"
						></span>
					</label>
				</div>
			</div>
			<div
				class="resource-creation-wizard__form-work-time-selector-slots"
				:class="{'--disabled': isSlotsDisabled }"
			>
				<div
					v-for="(slotRange, index) in slotRanges"
					:key="slotRange.id"
					:data-id="'brcw-resource-work-time-slot-' + slotRange.id"
					class="ui-form-row-inline"
					@click="onSlotClick"
				>
					<WorkTimeSlotRange
						:id="index"
						:initialSlotRange="slotRange"
						:isLastRange="index === slotRanges.length - 1"
						@add="addSlotRange"
						@update="updateSlotRange"
						@remove="removeSlotRange"
					/>
				</div>
				<div
					v-if="hasTimezonesDifference"
					class="resource-creation-wizard__form-work-time-selector-slots-timezone"
				>
					<div class="resource-creation-wizard__form-work-time-selector-slots-timezone-icon">
						<Icon
							:name="timezoneIconType"
							:color="'var(--ui-color-palette-gray-50)'"
							:size="24"
						/>
					</div>
					<div
						class="resource-creation-wizard__form-work-time-selector-slots-timezone-text"
						v-html="formattedTimezonesText"
					></div>
				</div>
			</div>
		</div>
	`
	};

	const WorkTime = {
	  name: 'ResourceSettingsCardWorkTime',
	  emits: ['update', 'updateGlobalSchedule', 'getGlobalSchedule'],
	  props: {
	    initialSlotRanges: {
	      type: Array,
	      required: true
	    },
	    isGlobalSchedule: {
	      type: Boolean,
	      required: true
	    },
	    defaultSlotRange: {
	      type: Object,
	      required: true
	    },
	    initialTimezone: {
	      type: String,
	      required: true
	    },
	    currentTimezone: {
	      type: String,
	      required: true
	    },
	    isCompanyScheduleAccess: {
	      type: Boolean,
	      required: true
	    },
	    companyScheduleUrl: {
	      type: String,
	      required: true
	    }
	  },
	  components: {
	    TitleLayout,
	    TextLayout,
	    WorkTimeSelector
	  },
	  methods: {
	    update(slotRanges) {
	      this.$emit('update', slotRanges);
	    },
	    updateGlobalSchedule(checked) {
	      this.$emit('updateGlobalSchedule', checked);
	    },
	    getGlobalSchedule() {
	      this.$emit('getGlobalSchedule');
	    }
	  },
	  computed: {
	    title() {
	      return this.loc('BRCW_SETTINGS_CARD_WORK_TIME_TITLE_MSGVER_1');
	    },
	    titleIconType() {
	      return ui_iconSet_api_vue.Set.CLOCK_2;
	    }
	  },
	  template: `
		<div class="ui-form resource-creation-wizard__form-settings">
			<TitleLayout
				:title="title"
				:iconType="titleIconType"
			/>
			<TextLayout
				type="WorkTime"
				:text="loc('BRCW_SETTINGS_CARD_WORK_TIME_TEXT_MSGVER_2')"
			/>
			<WorkTimeSelector
				:initialSlotRanges="initialSlotRanges"
				:defaultSlotRange="defaultSlotRange"
				:isGlobalSchedule="isGlobalSchedule"
				:initialTimezone="initialTimezone"
				:currentTimezone="currentTimezone"
				:isCompanyScheduleAccess="isCompanyScheduleAccess"
				:companyScheduleUrl="companyScheduleUrl"
				@update="update"
				@updateGlobalSchedule="updateGlobalSchedule"
				@getGlobalSchedule="getGlobalSchedule"
			/>
		</div>
	`
	};

	const SlotLengthPrecisionSelection = {
	  name: 'ResourceSettingsCardSlotLengthSlotLengthPrecisionSelection',
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  emits: ['input'],
	  components: {},
	  props: {
	    initialValue: {
	      type: Number,
	      default: 0
	    }
	  },
	  data() {
	    return {
	      days: 0,
	      hours: 0,
	      minutes: 0,
	      minutesFocused: false
	    };
	  },
	  created() {
	    this.distributeInitialValue();
	  },
	  methods: {
	    distributeInitialValue() {
	      let remainingMinutes = this.initialValue;
	      this.days = Math.floor(remainingMinutes / (24 * 60));
	      remainingMinutes %= 24 * 60;
	      this.hours = Math.floor(remainingMinutes / 60);
	      remainingMinutes %= 60;
	      this.minutes = remainingMinutes;
	    },
	    calculateTotalMinutes() {
	      const totalMinutes = this.days * 24 * 60 + this.hours * 60 + this.minutes;
	      this.$emit('input', totalMinutes);
	    },
	    validateHours() {
	      this.hours = parseInt(this.hours, 10);
	      if (!main_core.Type.isNumber(this.hours)) {
	        this.hours = 0;
	      }
	      if (this.hours > 12) {
	        this.hours = 12;
	      }
	      if (this.hours === 0) {
	        // delay calculation to let time to determine if minutes focused before
	        setTimeout(() => {
	          if (!this.minutesFocused) {
	            this.calculateTotalMinutes();
	          }
	        }, 0);
	      } else {
	        this.calculateTotalMinutes();
	      }
	    },
	    validateMinutes() {
	      this.minutesFocused = false;
	      this.minutes = parseInt(this.minutes, 10);
	      if (!main_core.Type.isNumber(this.minutes)) {
	        this.minutes = 0;
	      }
	      if (this.minutes > 59) {
	        this.minutes = 59;
	      }
	      this.calculateTotalMinutes();
	    },
	    handleEnterKey(event) {
	      if (event.key === 'Enter') {
	        event.target.blur();
	      }
	    }
	  },
	  computed: {
	    hourHint() {
	      return {
	        text: this.loc('BRCW_SETTINGS_CARD_SLOT_LENGTH_PRECISION_LIMIT_HOUR'),
	        popupOptions: {
	          targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper')
	        }
	      };
	    },
	    minutesHint() {
	      return {
	        text: this.loc('BRCW_SETTINGS_CARD_SLOT_LENGTH_PRECISION_LIMIT_MINUTES'),
	        popupOptions: {
	          targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper')
	        }
	      };
	    }
	  },
	  template: `
		<div class="ui-form resource-creation-wizard__form-slot-length-precision-selection">
			<div class="ui-form-row-inline">
				<div class="ui-form-row --disabled">
					<div class="ui-form-content">
						<div class="ui-form-row">
							<div class="ui-ctl ui-ctl-time ui-ctl-sm ui-ctl-round">
								<input
									:data-id="'brcw-resource-slot-length-precision-days'"
									v-model="days"
									type="text"
									class="ui-ctl-element"
									disabled
								>
							</div>
						</div>
						<div class="ui-form-row">
							<div>
								{{ loc('BRCW_SETTINGS_CARD_SLOT_LENGTH_PRECISION_1') }}
							</div>
						</div>
					</div>
				</div>
				<div class="ui-form-row">
					<div class="ui-form-content">
						<div class="ui-form-row">
							<div 
								class="ui-ctl ui-ctl-time ui-ctl-sm ui-ctl-round"
								v-hint="hourHint"
							>
								<input
									:data-id="'brcw-resource-slot-length-precision-hours'"
									v-model="hours"
									type="text"
									class="ui-ctl-element"
									@blur="validateHours"
									@keydown="handleEnterKey"
								>
							</div>
						</div>
						<div class="ui-form-row">
							<div>
								{{ loc('BRCW_SETTINGS_CARD_SLOT_LENGTH_PRECISION_2') }}
							</div>
						</div>
					</div>
				</div>
				<div class="ui-form-row">
					<div class="ui-form-content">
						<div class="ui-form-row">
							<div 
								class="ui-ctl ui-ctl-time ui-ctl-sm ui-ctl-round"
								v-hint="minutesHint"
							>
								<input
									:data-id="'brcw-resource-slot-length-precision-minutes'"
									v-model="minutes"
									type="text"
									class="ui-ctl-element"
									@focus="minutesFocused = true"
									@blur="validateMinutes"
									@keydown="handleEnterKey"
								>
							</div>
						</div>
						<div class="ui-form-row">
							<div>
								{{ loc('BRCW_SETTINGS_CARD_SLOT_LENGTH_PRECISION_3') }}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	`
	};

	// @vue/component
	const unitDurations = booking_lib_duration.Duration.getUnitDurations();
	const units = Object.fromEntries(Object.entries(unitDurations).map(([unit, value]) => [unit, value / unitDurations.i]));
	const disabledLengths = new Set([units.H * 24, units.d * 7]);
	const SlotLengthSelector = {
	  name: 'ResourceSettingsCardSlotLengthSelector',
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  emits: ['select'],
	  components: {
	    SlotLengthPrecisionSelection
	  },
	  props: {
	    initialSelectedValue: {
	      type: Number,
	      required: true
	    }
	  },
	  data() {
	    return {
	      selectedPrecisionValue: this.initialSelectedValue,
	      precisionMode: false
	    };
	  },
	  computed: {
	    selectedValue: {
	      get() {
	        return this.$store.state[booking_const.Model.ResourceCreationWizard].slotLengthId;
	      },
	      set(slotLengthId) {
	        this.$store.dispatch(`${booking_const.Model.ResourceCreationWizard}/setSlotLengthId`, {
	          slotLengthId
	        });
	      }
	    },
	    durations() {
	      return [{
	        label: new booking_lib_duration.Duration(unitDurations.H).format('H'),
	        value: units.H
	      }, {
	        label: new booking_lib_duration.Duration(unitDurations.H * 2).format('H'),
	        value: units.H * 2
	      }, {
	        label: new booking_lib_duration.Duration(unitDurations.H * 24).format('H'),
	        value: units.H * 24
	      }, {
	        label: new booking_lib_duration.Duration(unitDurations.d * 7).format('d'),
	        value: units.d * 7
	      }, {
	        label: this.loc('BRCW_SETTINGS_CARD_SLOT_LENGTH_SELECTOR_CUSTOM'),
	        value: 0
	      }];
	    }
	  },
	  created() {
	    const templateValues = new Set([units.H, units.H * 2, units.H * 24, units.d * 7]);
	    this.selectedValue = this.initialSelectedValue;
	    if (!templateValues.has(this.selectedValue)) {
	      this.selectedValue = 0;
	      this.precisionMode = true;
	    }
	  },
	  methods: {
	    select(value) {
	      if (disabledLengths.has(value)) {
	        return;
	      }
	      this.selectedValue = parseInt(value, 10);
	      if (value === 0) {
	        this.precisionMode = true;
	      } else {
	        this.precisionMode = false;
	        this.$emit('select', this.selectedValue);
	      }
	    },
	    selectPrecision(value) {
	      this.selectedPrecisionValue = value === 0 ? units.H : parseInt(value, 10);
	      if (value === 0) {
	        this.selectedValue = units.H;
	        this.precisionMode = false;
	      }
	      this.$emit('select', this.selectedPrecisionValue);
	    },
	    getClass(value) {
	      return {
	        'ui-btn-primary': this.selectedValue === value,
	        'ui-btn-light': this.selectedValue !== value,
	        'ui-btn-disabled': disabledLengths.has(value)
	      };
	    },
	    getSoonHintContent(value) {
	      if (disabledLengths.has(value)) {
	        return {
	          text: this.loc('BRCW_SOON_HINT'),
	          popupOptions: {
	            targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper'),
	            bindOptions: {
	              position: 'bottom'
	            },
	            offsetLeft: 0,
	            offsetTop: 0
	          }
	        };
	      }
	      return null;
	    }
	  },
	  template: `
		<div class="resource-creation-wizard__form-slot-length-selector">
			<div
				v-for="(duration, index) in durations"
				:key="index"
				:data-id="'brcw-resource-slot-length-selector-size-' + index"
				class="ui-btn ui-btn-xs"
				:class="getClass(duration.value)"
				@click="select(duration.value)"
				v-hint="getSoonHintContent(duration.value)"
			>
				{{ duration.label }}
			</div>
		</div>
		<transition name="fade">
			<div
				v-if="precisionMode"
				class="resource-creation-wizard__form-slot-length-precision"
			>
				<SlotLengthPrecisionSelection
					:initialValue="selectedPrecisionValue"
					@input="selectPrecision"
				/>
			</div>
		</transition>
	`
	};

	const SlotLength = {
	  name: 'ResourceSettingsCardSlotLength',
	  emits: ['select'],
	  components: {
	    TitleLayout,
	    TextLayout,
	    SlotLengthSelector
	  },
	  props: {
	    initialSelectedValue: {
	      type: Number,
	      default: 60
	    }
	  },
	  data() {
	    return {
	      selectedSlotLength: this.initialSelectedValue
	    };
	  },
	  methods: {
	    updateSelectedValue(value) {
	      this.selectedSlotLength = value;
	      this.$emit('select', value);
	    }
	  },
	  computed: {
	    title() {
	      return this.loc('BRCW_SETTINGS_CARD_SLOT_LENGTH_TITLE_MSGVER_1');
	    },
	    titleIconType() {
	      return ui_iconSet_api_vue.Set.GANTT_GRAPHS;
	    }
	  },
	  template: `
		<div class="ui-form resource-creation-wizard__form-settings">
			<TitleLayout
				:title="title"
				:iconType="titleIconType"
			/>
			<TextLayout
				type="SlotLength"
				:text="loc('BRCW_SETTINGS_CARD_SLOT_LENGTH_TEXT_MSGVER_2')"
			/>
			<div>
				<SlotLengthSelector
					:initialSelectedValue="initialSelectedValue"
					@select="updateSelectedValue"
				/>
			</div>
		</div>
	`
	};

	const {
	  mapGetters: mapResourceGetters$2,
	  mapActions: mapActions$1,
	  mapMutations
	} = ui_vue3_vuex.createNamespacedHelpers('resource-creation-wizard');
	const ResourceSettingsCard = {
	  name: 'ResourceSettingsCard',
	  components: {
	    BaseFields,
	    ScheduleTypes,
	    WorkTime,
	    SlotLength,
	    Integration
	  },
	  created() {
	    var _this$resource$slotRa, _this$resource$slotRa2, _this$resource$slotRa3;
	    this.initialTimezone = this.getInitialSlotTimeZone();
	    this.selectedSlotLength = (_this$resource$slotRa = (_this$resource$slotRa2 = this.resource.slotRanges) == null ? void 0 : (_this$resource$slotRa3 = _this$resource$slotRa2[0]) == null ? void 0 : _this$resource$slotRa3.slotSize) != null ? _this$resource$slotRa : 60;
	    const slotRanges = main_core.Type.isArrayFilled(this.resource.slotRanges) ? this.resource.slotRanges : this.companyScheduleSlots;
	    this.updateSlotRanges(slotRanges);
	  },
	  data() {
	    return {
	      selectedSlotLength: 60,
	      initialTimezone: ''
	    };
	  },
	  methods: {
	    ...mapActions$1(['updateResource', 'setInvalidResourceName', 'setInvalidResourceType']),
	    ...mapMutations(['setGlobalSchedule']),
	    updateResourceName(name) {
	      this.updateResource({
	        name
	      });
	      if (name) {
	        this.setInvalidResourceName(false);
	      }
	    },
	    updateResourceType(typeId) {
	      this.updateResource({
	        typeId
	      });
	      if (typeId) {
	        this.setInvalidResourceType(false);
	      }
	    },
	    updateSlotRanges(slotRanges) {
	      this.updateResource({
	        slotRanges: this.updateSlotDataForAllRanges(slotRanges)
	      });
	    },
	    updateSlotLength(value) {
	      this.selectedSlotLength = value;
	      if (this.resource.slotRanges.length === 0) {
	        return;
	      }
	      this.updateSlotRanges(this.resource.slotRanges);
	    },
	    updateSlotDataForAllRanges(inputSlotRanges) {
	      const slotRanges = inputSlotRanges || this.resource.slotRanges;
	      return slotRanges.map(slotRange => {
	        return {
	          ...slotRange,
	          slotSize: this.selectedSlotLength,
	          timezone: this.timezone
	        };
	      });
	    },
	    updateGlobalSchedule(checked) {
	      this.setGlobalSchedule(checked);
	    },
	    async fetchData() {
	      await booking_provider_service_resourceCreationWizardService.resourceCreationWizardService.fetchData();
	      if (!this.isEditForm) {
	        this.updateSlotRanges(this.companyScheduleSlots);
	      }
	    },
	    getInitialSlotTimeZone() {
	      var _slotRanges$;
	      const slotRanges = this.isEditForm ? this.resource.slotRanges : this.companyScheduleSlots;
	      return (_slotRanges$ = slotRanges[0]) == null ? void 0 : _slotRanges$.timezone;
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      timezone: `${booking_const.Model.Interface}/timezone`
	    }),
	    ...mapResourceGetters$2({
	      resource: 'getResource',
	      companyScheduleSlots: 'getCompanyScheduleSlots',
	      isCompanyScheduleAccess: 'isCompanyScheduleAccess',
	      companyScheduleUrl: 'companyScheduleUrl',
	      isGlobalSchedule: 'isGlobalSchedule'
	    }),
	    resourceName() {
	      return this.resource.name;
	    },
	    resourceType() {
	      const resourceType = this.$store.getters[`${booking_const.Model.ResourceTypes}/getById`](this.resource.typeId);
	      return {
	        typeId: this.resource.typeId,
	        typeName: resourceType == null ? void 0 : resourceType.name
	      };
	    },
	    slotRanges() {
	      return this.resource.slotRanges;
	    },
	    defaultSlotRange() {
	      return this.companyScheduleSlots[0];
	    },
	    slotSize() {
	      var _this$resource$slotRa4, _slotRange$slotSize;
	      const slotRange = (_this$resource$slotRa4 = this.resource.slotRanges) == null ? void 0 : _this$resource$slotRa4[0];
	      return (_slotRange$slotSize = slotRange == null ? void 0 : slotRange.slotSize) != null ? _slotRange$slotSize : 60;
	    },
	    isMain: {
	      get() {
	        return this.resource.isMain;
	      },
	      set(isMain) {
	        this.updateResource({
	          isMain
	        });
	      }
	    },
	    isEditForm() {
	      return this.resource.id !== null;
	    }
	  },
	  template: `
		<div class="resource-settings-card">
			<BaseFields
				data-id="brcw-resource-settings-base"
				:initialResourceName="resourceName"
				:initialResourceType="resourceType"
				@nameUpdate="updateResourceName"
				@typeUpdate="updateResourceType"
			/>
			<ScheduleTypes
				data-id="brcw-resource-settings-schedule-types"
				v-model="isMain"
			/>
			<Integration/>
			<WorkTime
				data-id="brcw-resource-settings-work-time"
				:initialSlotRanges="slotRanges"
				:defaultSlotRange="defaultSlotRange"
				:isGlobalSchedule="isGlobalSchedule"
				:initialTimezone="initialTimezone"
				:currentTimezone="timezone"
				:isCompanyScheduleAccess="isCompanyScheduleAccess"
				:companyScheduleUrl="companyScheduleUrl"
				@update="updateSlotRanges"
				@updateGlobalSchedule="updateGlobalSchedule"
				@getGlobalSchedule="fetchData"
			/>
			<SlotLength
				data-id="brcw-resource-settings-slot-length"
				:initialSelectedValue="slotSize"
				@select="updateSlotLength"
			/>
		</div>
	`
	};

	// @vue/component
	const LabelDropdown = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    BMenu: ui_vue3_components_menu.BMenu
	  },
	  props: {
	    value: {
	      type: Number,
	      required: true
	    },
	    /** @type Item[] */
	    items: {
	      type: Array,
	      required: true
	    }
	  },
	  setup() {
	    return {
	      Actions: ui_iconSet_api_core.Actions
	    };
	  },
	  data() {
	    return {
	      isMenuShown: false
	    };
	  },
	  computed: {
	    text() {
	      return this.items.find(({
	        value
	      }) => value === this.value).name;
	    },
	    menuOptions() {
	      return {
	        id: 'booking-resource-creation-wizard-label-dropdown-menu',
	        bindElement: this.$refs.container,
	        offsetTop: 8,
	        items: this.items.map(({
	          name,
	          value
	        }) => ({
	          title: name,
	          onClick: () => this.$emit('update:value', value)
	        })),
	        targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper')
	      };
	    }
	  },
	  methods: {
	    handleClick() {
	      this.isMenuShown = true;
	    }
	  },
	  template: `
		<div class="booking-resource-creation-wizard-label-dropdown" ref="container" @click="handleClick">
			<span>{{ text }}</span>
			<BIcon :name="Actions.CHEVRON_DOWN"/>
		</div>
		<BMenu v-if="isMenuShown" :options="menuOptions" @close="isMenuShown = false"/>
	`
	};

	const ChannelMenu = {
	  name: 'ChannelMenu',
	  emits: ['popupShown', 'popupClosed', 'updateChannel'],
	  props: {
	    currentChannel: {
	      type: String,
	      default: null
	    }
	  },
	  data() {
	    return {
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonIcon: booking_component_button.ButtonIcon,
	      menuPopup: null,
	      channel: this.loc('BRCW_NOTIFICATION_CARD_MESSAGE_SELECT_WHA')
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      isChannelChoiceAvailable: `${booking_const.Model.ResourceCreationWizard}/isChannelChoiceAvailable`
	    }),
	    popupId() {
	      return 'booking-choose-channel-menu';
	    },
	    notificationChannelOptions() {
	      return [{
	        label: this.loc('BRCW_NOTIFICATION_CARD_MESSAGE_SELECT_WHA'),
	        value: booking_const.NotificationChannel.WhatsApp
	      }, {
	        label: this.loc('BRCW_NOTIFICATION_CARD_TEMPLATE_POPUP_SELECT_SMS_MSGVER_1'),
	        value: booking_const.NotificationChannel.Sms
	      }];
	    }
	  },
	  mounted() {
	    var _this$notificationCha;
	    this.channel = ((_this$notificationCha = this.notificationChannelOptions.find(({
	      value
	    }) => value === this.currentChannel)) == null ? void 0 : _this$notificationCha.label) || this.loc('BRCW_NOTIFICATION_CARD_MESSAGE_SELECT_WHA');
	  },
	  unmounted() {
	    if (this.menuPopup) {
	      this.destroy();
	    }
	  },
	  methods: {
	    openMenu() {
	      var _this$menuPopup, _this$menuPopup$popup;
	      if ((_this$menuPopup = this.menuPopup) != null && (_this$menuPopup$popup = _this$menuPopup.popupWindow) != null && _this$menuPopup$popup.isShown()) {
	        this.destroy();
	        return;
	      }
	      const menuButton = this.$refs['menu-button'];
	      this.menuPopup = main_popup.MenuManager.create(this.popupId, menuButton, this.getMenuItems(), {
	        className: 'booking-choose-channel-menu',
	        closeByEsc: true,
	        autoHide: true,
	        offsetTop: 0,
	        offsetLeft: menuButton.offsetWidth - menuButton.offsetWidth / 2,
	        angle: true,
	        cacheable: true,
	        targetContainer: document.querySelector('div.resource-creation-wizard__wrapper'),
	        events: {
	          onClose: () => this.destroy(),
	          onDestroy: () => this.destroy()
	        }
	      });
	      this.menuPopup.show();
	      this.bindScrollEvent();
	      this.$emit('popupShown');
	    },
	    getMenuItems() {
	      return [{
	        text: this.loc('BRCW_NOTIFICATION_CARD_MESSAGE_SELECT_WHA'),
	        onclick: () => {
	          this.channel = this.loc('BRCW_NOTIFICATION_CARD_MESSAGE_SELECT_WHA');
	          this.$emit('updateChannel', booking_const.NotificationChannel.WhatsApp);
	          this.destroy();
	        }
	      }, {
	        text: this.loc('BRCW_NOTIFICATION_CARD_TEMPLATE_POPUP_SELECT_SMS_MSGVER_1'),
	        onclick: () => {
	          this.channel = this.loc('BRCW_NOTIFICATION_CARD_TEMPLATE_POPUP_SELECT_SMS_MSGVER_1');
	          this.$emit('updateChannel', booking_const.NotificationChannel.Sms);
	          this.destroy();
	        }
	      }];
	    },
	    destroy() {
	      main_popup.MenuManager.destroy(this.popupId);
	      this.unbindScrollEvent();
	      this.$emit('popupClosed');
	    },
	    bindScrollEvent() {
	      main_core.Event.bind(document, 'scroll', this.adjustPosition, {
	        capture: true
	      });
	    },
	    unbindScrollEvent() {
	      main_core.Event.unbind(document, 'scroll', this.adjustPosition, {
	        capture: true
	      });
	    },
	    adjustPosition() {
	      var _this$menuPopup2, _this$menuPopup2$popu;
	      (_this$menuPopup2 = this.menuPopup) == null ? void 0 : (_this$menuPopup2$popu = _this$menuPopup2.popupWindow) == null ? void 0 : _this$menuPopup2$popu.adjustPosition();
	    }
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  template: `
		<span
			class="booking-resource-creation-wizard-channel-menu-button"
			:class="{'--disabled': !isChannelChoiceAvailable}"
			ref="menu-button"
			@click="isChannelChoiceAvailable && openMenu()"
		>
			{{ channel }}
		</span>
	`
	};

	const ChooseTemplatePopup = {
	  name: 'ResourceNotificationChooseTemplatePopup',
	  emits: ['close', 'templateTypeSelected'],
	  props: {
	    bindElement: {
	      type: HTMLElement,
	      required: true
	    },
	    model: {
	      type: Object,
	      required: true
	    },
	    currentChannel: {
	      type: String,
	      required: true
	    },
	    currentTemplateType: {
	      type: String,
	      required: true
	    },
	    buttonSize: {
	      type: String,
	      default: booking_component_button.ButtonSize.EXTRA_SMALL
	    },
	    buttonColors: {
	      type: Object,
	      default: () => ({
	        selected: booking_component_button.ButtonColor.BASE_LIGHT,
	        default: booking_component_button.ButtonColor.PRIMARY
	      })
	    }
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set,
	      messenger: booking_const.NotificationChannel.WhatsApp,
	      selectedTemplateType: this.currentTemplateType
	    };
	  },
	  components: {
	    Popup: booking_component_popup.Popup,
	    ChannelMenu,
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  beforeMount() {
	    this.messenger = this.currentChannel;
	  },
	  mounted() {
	    this.adjustPosition();
	    main_core.Event.bind(document, 'scroll', this.adjustPosition, {
	      capture: true
	    });
	  },
	  beforeUnmount() {
	    main_core.Event.unbind(document, 'scroll', this.adjustPosition, {
	      capture: true
	    });
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      templateTypes: `${booking_const.Model.Dictionary}/getNotificationTemplates`
	    }),
	    popupId() {
	      return `booking-resource-wizard-choose-template-popup-${main_core.Text.getRandom(4)}`;
	    },
	    config() {
	      var _this$bindElement$chi, _this$bindElement$chi2;
	      return {
	        className: 'booking-resource-wizard-choose-template-popup',
	        bindElement: this.bindElement,
	        bindOptions: {
	          forceBindPosition: true,
	          forceTop: true
	        },
	        width: 350,
	        offsetLeft: ((_this$bindElement$chi = (_this$bindElement$chi2 = this.bindElement.childNodes[0]) == null ? void 0 : _this$bindElement$chi2.offsetWidth) != null ? _this$bindElement$chi : 146) + 10,
	        offsetTop: -149,
	        animation: 'fading-slide',
	        angle: {
	          offset: 120,
	          position: 'left'
	        },
	        angleBorderRadius: '4px 0',
	        padding: 15,
	        targetContainer: document.querySelector('div.resource-creation-wizard__wrapper')
	      };
	    },
	    getFormattedTime() {
	      return main_date.DateTimeFormat.format(main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT'), Date.now() / 1000);
	    }
	  },
	  methods: {
	    getMessageTemplate(messenger, templateType) {
	      const templateModel = this.model.templates.find(template => {
	        return template.type === templateType;
	      });
	      if (!templateModel) {
	        return '';
	      }
	      switch (messenger) {
	        case booking_const.NotificationChannel.WhatsApp:
	          return templateModel.text;
	        case booking_const.NotificationChannel.Sms:
	          return templateModel.textSms;
	        default:
	          return '';
	      }
	    },
	    handleChannelChange(selectedChannel) {
	      this.messenger = selectedChannel;
	    },
	    chooseType(selectedType) {
	      this.selectedTemplateType = selectedType;
	      this.$emit('templateTypeSelected', this.selectedTemplateType);
	    },
	    adjustPosition() {
	      this.$refs.popup.adjustPosition({
	        forceBindPosition: true,
	        forceTop: true
	      });
	    },
	    getButtonText(templateType) {
	      return this.selectedTemplateType === templateType ? this.loc('BRCW_NOTIFICATION_CARD_TEMPLATE_POPUP_BTN_SELECTED') : this.loc('BRCW_NOTIFICATION_CARD_TEMPLATE_POPUP_BTN_SELECT_TYPE');
	    },
	    getButtonColor(templateType) {
	      return this.selectedTemplateType === templateType ? this.buttonColors.selected : this.buttonColors.default;
	    },
	    getTemplateTitle(templateType) {
	      const typeDictionary = Object.values(this.templateTypes).find(templateDictionary => {
	        return templateDictionary.value === templateType;
	      });
	      return typeDictionary ? typeDictionary.name : '';
	    }
	  },
	  template: `
		<Popup
			v-slot="{freeze, unfreeze}"
			:id="popupId"
			:config="config"
			ref="popup"
			@close="$emit('close')"
		>
			<div class="booking-resource-wizard-choose-template-popup">
				<div class="booking-resource-wizard-choose-template-popup-header">
					<div class="booking-resource-wizard-choose-template-popup-header-title">
						{{ loc('BRCW_NOTIFICATION_CARD_TEMPLATE_POPUP_HEADER_TITLE') }}
						<ChannelMenu
							:current-channel="messenger"
							@popupShown="freeze"
							@popupClosed="unfreeze"
							@updateChannel="handleChannelChange"
						/>
						<Icon
							:class="['--close-btn']"
							:name="IconSet.CROSS_25"
							:color="'var(--ui-color-palette-gray-20)'"
							@click="$emit('close')"
						/>
					</div>
					<div class="booking-resource-wizard-choose-template-popup-header-close-btn"></div>
					<div class="booking-resource-wizard-choose-template-popup-header-line"></div>
				</div>
				<div class="booking-resource-wizard-choose-template-popup-container">
					<div v-for="template in this.model.templates" :key="template.type"
						 class="booking-resource-wizard-choose-template-popup-content">
						<div class="booking-resource-wizard-choose-template-popup-content-desc">
							{{ getTemplateTitle(template.type) }}
						</div>
						<div class="booking-resource-wizard-choose-template-popup-content-msg-example">
							<div
								:class="['booking-resource-wizard-choose-template-popup-content-msg-example-body', messenger]">
								<div
									:class="['booking-resource-wizard-choose-template-popup-content-msg-example-body-text', messenger]">
									<div v-html="getMessageTemplate(messenger, template.type)"></div>
									<div
										:class="['booking-resource-wizard-choose-template-popup-content-msg-example-body-text-time', messenger]">
										{{ getFormattedTime }}
									</div>
								</div>
								<div
									:class="['booking-resource-wizard-choose-template-popup-content-msg-example-body-text-tail', messenger]"></div>
							</div>
							<div class="booking-resource-wizard-choose-template-popup-content-msg-example-actions">
								<Icon
									:name="IconSet.CIRCLE_CHECK"
									:color="'var(--ui-color-palette-green-55)'"
									:size="24"
									v-if="selectedTemplateType === template.type"
								/>
								<button
									:class="['ui-btn ui-btn-round', buttonSize, getButtonColor(template.type), {'--selected': selectedTemplateType === template.type}]"
									type="button"
									@click="chooseType(template.type)"
								>
									{{ getButtonText(template.type) }}
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</Popup>
	`
	};

	const TemplateEmpty = {
	  template: `
		<div class="booking-resource-creation-wizard-message-template-empty">
			<div class="booking-resource-creation-wizard-message-template-empty-icon"></div>
			<div class="booking-resource-creation-wizard-message-template-empty-text">
				{{ loc('BRCW_MESSAGE_TEMPLATE_SOON_TEXT') }}
			</div>
		</div>
	`
	};

	const ResourceNotificationCheckBoxRow = {
	  name: 'ResourceNotificationCheckBoxRow',
	  emits: ['update:checked'],
	  props: {
	    checked: {
	      type: Boolean,
	      required: true
	    },
	    disabled: {
	      type: Boolean,
	      required: false,
	      default: false
	    }
	  },
	  template: `
		<label class="resource-creation-wizard__form-notification-text-row">
			<span class="resource-creation-wizard__form-notification-text-row-checkbox">
				<input
					type="checkbox"
					:disabled="disabled"
					:checked="checked"
					@input="$emit('update:checked', $event.target.checked)"
				/>
			</span>
			<slot/>
		</label>
	`
	};

	const CheckedForAll = {
	  props: {
	    type: {
	      type: String,
	      required: true
	    },
	    disabled: {
	      type: Boolean,
	      required: true
	    }
	  },
	  computed: {
	    isCheckedForAll: {
	      get() {
	        return this.$store.getters[`${booking_const.Model.ResourceCreationWizard}/isCheckedForAll`](this.type);
	      },
	      set(isChecked) {
	        this.$store.dispatch(`${booking_const.Model.ResourceCreationWizard}/setCheckedForAll`, {
	          type: this.type,
	          isChecked
	        });
	      }
	    }
	  },
	  components: {
	    ResourceNotificationCheckBoxRow
	  },
	  template: `
		<ResourceNotificationCheckBoxRow
			v-model:checked="isCheckedForAll"
			:disabled="disabled"
		>
			<div class="resource-creation-wizard__form-notification-text">
				{{ loc('BRCW_NOTIFICATION_CARD_HELPER_TEXT_APPLY_FOR_ALL') }}
			</div>
		</ResourceNotificationCheckBoxRow>
	`
	};

	const ResourceNotificationTextRow = {
	  name: 'ResourceNotificationTextRow',
	  props: {
	    icon: {
	      type: String,
	      required: true
	    }
	  },
	  template: `
		<div class="resource-creation-wizard__form-notification-text-row">
			<div :class="[icon, 'ui-icon-set', 'resource-creation-wizard__form-notification-text-row-icon']"></div>
			<div class="resource-creation-wizard__form-notification-text">
				<slot/>
			</div>
		</div>
	`
	};

	const Description = {
	  props: {
	    description: {
	      type: String,
	      required: true
	    },
	    helpDesk: {
	      type: Object,
	      required: true
	    }
	  },
	  components: {
	    HelpDeskLoc: booking_component_helpDeskLoc.HelpDeskLoc,
	    ResourceNotificationTextRow
	  },
	  template: `
		<ResourceNotificationTextRow icon="--info-1">
			<HelpDeskLoc 
				:message="description"
				:code="helpDesk.code"
				:anchor="helpDesk.anchorCode"
			/>
		</ResourceNotificationTextRow>
	`
	};

	// @vue/component
	const MessageTemplate = {
	  components: {
	    RichLoc: ui_vue3_components_richLoc.RichLoc
	  },
	  props: {
	    text: {
	      type: String,
	      required: true
	    }
	  },
	  computed: {
	    preparedTemplate() {
	      return this.text.replaceAll('\n', '[br/]').replaceAll(/#(.+?)#/g, '[accent][[bold]$1[/bold]][/accent]');
	    }
	  },
	  template: `
		<RichLoc
			class="resource-creation-wizard__form-notification-info-template"
			:placeholder="['[accent]', '[br/]']"
			:text="preparedTemplate"
		>
			<template #accent="{ text: accentText }">
				<RichLoc tag="span" class="--accent" placeholder="[bold]" :text="accentText">
					<template #bold="{ text: boldText }">
						<span class="--bold">{{ boldText }}</span>
					</template>
				</RichLoc>
			</template>
			<template #br><br/></template>
		</RichLoc>
	`
	};

	const ManagerNotification = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    Description,
	    CyclePopup: booking_component_cyclePopup.CyclePopup,
	    RichLoc: ui_vue3_components_richLoc.RichLoc
	  },
	  props: {
	    text: {
	      type: String,
	      required: true
	    },
	    description: {
	      type: String,
	      required: true
	    },
	    helpDesk: {
	      type: Object,
	      required: true
	    },
	    scrollToCard: {
	      type: String,
	      default: null
	    }
	  },
	  setup() {
	    return {
	      Main: ui_iconSet_api_core.Main
	    };
	  },
	  data() {
	    return {
	      isPopupShown: false
	    };
	  },
	  computed: {
	    notificationText() {
	      return this.text.replaceAll('\n', '[br/]');
	    }
	  },
	  template: `
		<div class="booking-resource-creation-wizard-notification --manager">
			<div class="booking-resource-creation-wizard-notification-main">
				<div class="resource-creation-wizard__form-notification-info-title-row">
					<BIcon :name="Main.BELL_1"/>
					<div class="resource-creation-wizard__form-notification-info-title">
						{{ loc('BRCW_NOTIFICATION_CARD_MANAGER') }}
					</div>
					<div class="booking-resource-creation-wizard-manager-see" @click="isPopupShown = true">
						{{ loc('BRCW_NOTIFICATION_CARD_MANAGER_SEE') }}
					</div>
				</div>
				<div class="resource-creation-wizard__form-notification-info">
					<div class="resource-creation-wizard__form-notification-info-text-row">
						{{ loc('BRCW_NOTIFICATION_CARD_MANAGER_TEXT') }}
					</div>
					<RichLoc
						class="resource-creation-wizard__form-notification-info-template"
						:placeholder="'[br/]'"
						:text="notificationText"
					>
						<template #br><br/></template>
					</RichLoc>
				</div>
				<Description :description="description" :helpDesk="helpDesk"/>
				<slot/>
			</div>
			<CyclePopup v-if="isPopupShown" :scrollToCard="scrollToCard" @close="isPopupShown = false"/>
		</div>
	`
	};

	// eslint-disable-next-line no-promise-executor-return
	const sleep = timeout => new Promise(resolve => setTimeout(resolve, timeout));

	// @vue/component
	const ResourceNotification = {
	  name: 'ResourceNotification',
	  components: {
	    Switcher: booking_component_switcher.Switcher,
	    BIcon: ui_iconSet_api_vue.BIcon,
	    UiButton: booking_component_button.Button,
	    Description,
	    ChannelMenu,
	    ChooseTemplatePopup,
	    TemplateEmpty,
	    CheckedForAll,
	    MessageTemplate,
	    ManagerNotification
	  },
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  props: {
	    type: {
	      type: String,
	      required: true
	    },
	    title: {
	      type: String,
	      required: true
	    },
	    description: {
	      type: String,
	      required: true
	    },
	    helpDesk: {
	      type: Object,
	      required: true
	    },
	    checked: {
	      type: Boolean,
	      default: false
	    },
	    disabled: {
	      type: Boolean,
	      default: false
	    },
	    managerDescription: {
	      type: String,
	      default: ''
	    },
	    scrollToCard: {
	      type: String,
	      default: null
	    }
	  },
	  emits: ['update:checked'],
	  setup() {
	    return {
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      Actions: ui_iconSet_api_core.Actions,
	      CRM: ui_iconSet_api_core.CRM
	    };
	  },
	  data() {
	    return {
	      messenger: booking_const.NotificationChannel.WhatsApp,
	      showTemplatePopup: false
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      resource: `${booking_const.Model.ResourceCreationWizard}/getResource`,
	      isCurrentSenderAvailable: `${booking_const.Model.Notifications}/isCurrentSenderAvailable`
	    }),
	    model() {
	      return this.$store.getters[`${booking_const.Model.Notifications}/getByType`](this.type);
	    },
	    template() {
	      const templateName = this.resource[this.templateTypeField];
	      return this.model.templates.find(template => template.type === templateName);
	    },
	    messageTemplate() {
	      var _NotificationChannel$, _this$template$text, _this$template, _this$template$textSm, _this$template2;
	      return (_NotificationChannel$ = {
	        [booking_const.NotificationChannel.WhatsApp]: (_this$template$text = (_this$template = this.template) == null ? void 0 : _this$template.text) != null ? _this$template$text : '',
	        [booking_const.NotificationChannel.Sms]: (_this$template$textSm = (_this$template2 = this.template) == null ? void 0 : _this$template2.textSms) != null ? _this$template$textSm : ''
	      }[this.messenger]) != null ? _NotificationChannel$ : '';
	    },
	    hasTemplate() {
	      return this.isCurrentSenderAvailable && this.messageTemplate;
	    },
	    disableSwitcher() {
	      return this.disabled || !this.isCurrentSenderAvailable || !this.template;
	    },
	    templateTypeField() {
	      return booking_const.NotificationFieldsMap.TemplateType[this.type];
	    },
	    ordinal() {
	      return booking_const.NotificationFieldsMap.Ordinal[this.type];
	    },
	    soonHint() {
	      return {
	        text: this.loc('BRCW_BOOKING_SOON_HINT'),
	        popupOptions: {
	          offsetLeft: 60,
	          targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper')
	        }
	      };
	    }
	  },
	  created() {
	    this.hintManager = BX.UI.Hint.createInstance({
	      id: `brwc-notification-hint-${main_core.Text.getRandom(5)}`,
	      popupParameters: {
	        targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper')
	      }
	    });
	  },
	  mounted() {
	    this.hintManager.init(this.$el);
	    void this.animateHeight(false);
	  },
	  updated() {
	    this.hintManager.init(this.$el);
	    void this.animateHeight(true);
	  },
	  methods: {
	    handleChannelChange(selectedChannel) {
	      this.messenger = selectedChannel;
	    },
	    handleTemplateTypeSelected(selectedType) {
	      void this.$store.dispatch(`${booking_const.Model.ResourceCreationWizard}/updateResource`, {
	        [this.templateTypeField]: selectedType
	      });
	    },
	    getChooseTemplateButton() {
	      return this.$refs.chooseTemplateBtn || null;
	    },
	    expand() {
	      void booking_provider_service_resourceCreationWizardService.resourceCreationWizardService.updateNotificationExpanded(this.type, !this.model.isExpanded);
	    },
	    async animateHeight(withAnimation) {
	      var _this$$refs$manager2;
	      main_core.Dom.style(this.$el, 'transition', null);
	      if (withAnimation) {
	        await sleep(10);
	      }
	      const prevHeight = this.$el.offsetHeight;
	      main_core.Dom.style(this.$el, 'height', null);
	      if (!this.model.isExpanded) {
	        var _this$$refs$manager;
	        main_core.Dom.style(this.$refs.main, 'display', 'none');
	        main_core.Dom.style((_this$$refs$manager = this.$refs.manager) == null ? void 0 : _this$$refs$manager.$el, 'display', 'none');
	      }
	      const height = this.$el.offsetHeight;
	      main_core.Dom.style(this.$refs.main, 'display', null);
	      main_core.Dom.style((_this$$refs$manager2 = this.$refs.manager) == null ? void 0 : _this$$refs$manager2.$el, 'display', null);
	      main_core.Dom.style(this.$el, 'height', `${prevHeight}px`);
	      if (withAnimation) {
	        main_core.Dom.style(this.$el, 'transition', 'height 0.2s');
	        await sleep(10);
	        main_core.Dom.style(this.$el, 'height', `${height}px`);
	      } else {
	        main_core.Dom.style(this.$el, 'height', `${height}px`);
	      }
	    }
	  },
	  template: `
		<div class="booking-resource-creation-wizard-notification-container" :class="{'--disabled': !checked}">
			<div class="booking-resource-creation-wizard-notification">
				<div class="booking-resource-creation-wizard-notification-header" @click="expand">
					<div class="booking-resource-creation-wizard-notification-number">{{ ordinal }}</div>
					<div class="booking-resource-creation-wizard-notification-title">{{ title }}</div>
					<BIcon :name="model.isExpanded ? Actions.CHEVRON_UP : Actions.CHEVRON_DOWN"/>
				</div>
				<div class="booking-resource-creation-wizard-notification-main" ref="main">
					<div class="resource-creation-wizard__form-notification-info-title-row --main">
						<BIcon :name="CRM.CHAT_LINE"/>
						<div class="resource-creation-wizard__form-notification-info-title">
							{{ loc('BRCW_NOTIFICATION_CARD_MESSAGE') }}
						</div>
						<Switcher
							v-hint="disableSwitcher && soonHint"
							class="resource-creation-wizard__form-notification-info-switcher"
							:data-id="'brcw-resource-notification-info-switcher-' + type"
							:model-value="checked"
							:disabled="disableSwitcher"
							@update:model-value="$emit('update:checked', $event)"
						/>
					</div>
					<div class="resource-creation-wizard__form-notification-info --message">
						<div class="resource-creation-wizard__form-notification-info-text-row">
							{{ loc('BRCW_NOTIFICATION_CARD_MESSAGE_TEXT') }}
							<ChannelMenu
								:current-channel="messenger"
								@updateChannel="handleChannelChange"
							/>
						</div>
						<template v-if="hasTemplate">
							<MessageTemplate :text="messageTemplate"/>
							<div class="resource-creation-wizard__form-notification-info-template-choose-buttons">
								<div class="booking-resource-creation-wizard-choose-template-button" ref="chooseTemplateBtn">
									<UiButton
										:disabled="!checked"
										:text="loc('BRCW_NOTIFICATION_CARD_CHOOSE_TEMPLATE_TYPE')"
										:size="ButtonSize.EXTRA_SMALL"
										:color="ButtonColor.LIGHT_BORDER"
										:round="true"
										@click="showTemplatePopup = true"
									/>
								</div>
							</div>
						</template>
						<TemplateEmpty v-else/>
						<ChooseTemplatePopup
							v-if="showTemplatePopup"
							:bindElement="$refs.chooseTemplateBtn"
							:model="model"
							:current-channel="messenger"
							:currentTemplateType="resource[templateTypeField]"
							@templateTypeSelected="handleTemplateTypeSelected"
							@close="showTemplatePopup = false"
						/>
					</div>
					<Description :description="description" :helpDesk="helpDesk"/>
					<slot name="client"/>
					<CheckedForAll :type="type" :disabled="!checked"/>
				</div>
			</div>
			<ManagerNotification
				v-if="$slots.manager"
				:description="managerDescription"
				:text="model.managerNotification"
				:helpDesk="helpDesk"
				:scrollToCard="scrollToCard"
				ref="manager"
			>
				<slot name="manager"/>
			</ManagerNotification>
		</div>
	`
	};

	// @vue/component
	const BaseInfo = {
	  name: 'ResourceNotificationCardBaseInfo',
	  components: {
	    ResourceNotification,
	    ResourceNotificationTextRow,
	    LabelDropdown,
	    RichLoc: ui_vue3_components_richLoc.RichLoc
	  },
	  props: {
	    /** @type {NotificationsModel} */
	    model: {
	      type: Object,
	      required: true
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      /** @type {ResourceModel} */
	      resource: `${booking_const.Model.ResourceCreationWizard}/getResource`,
	      isCurrentSenderAvailable: `${booking_const.Model.Notifications}/isCurrentSenderAvailable`
	    }),
	    isInfoNotificationOn: {
	      get() {
	        return this.isCurrentSenderAvailable && this.resource.isInfoNotificationOn;
	      },
	      set(isInfoNotificationOn) {
	        void this.$store.dispatch(`${booking_const.Model.ResourceCreationWizard}/updateResource`, {
	          isInfoNotificationOn
	        });
	      }
	    },
	    infoNotificationDelay: {
	      get() {
	        return this.resource.infoNotificationDelay;
	      },
	      set(infoNotificationDelay) {
	        void this.$store.dispatch(`${booking_const.Model.ResourceCreationWizard}/updateResource`, {
	          infoNotificationDelay
	        });
	        this.$refs.card.$forceUpdate();
	      }
	    },
	    locInfoTimeSend() {
	      return this.loc('BRCW_NOTIFICATION_CARD_BASE_INFO_HELPER_TEXT_SECOND').replace('#time#', '[delay/]');
	    },
	    helpDesk() {
	      return booking_const.HelpDesk.ResourceNotificationInfo;
	    }
	  },
	  mounted() {
	    if (booking_lib_ahaMoments.ahaMoments.shouldShow(booking_const.AhaMoment.MessageTemplate)) {
	      setTimeout(() => this.showAhaMoment(), 500);
	    }
	  },
	  methods: {
	    async showAhaMoment() {
	      const target = this.$refs.card.getChooseTemplateButton();
	      if (main_core.Type.isNull(target)) {
	        return;
	      }
	      await booking_lib_ahaMoments.ahaMoments.showGuide({
	        id: 'booking-message-template',
	        title: this.loc('BOOKING_AHA_MESSAGE_TEMPLATE_TITLE'),
	        text: this.loc('BOOKING_AHA_MESSAGE_TEMPLATE_TEXT'),
	        article: booking_const.HelpDesk.AhaMessageTemplate,
	        target: this.$refs.card.getChooseTemplateButton(),
	        targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper')
	      });
	      booking_lib_ahaMoments.ahaMoments.setShown(booking_const.AhaMoment.MessageTemplate);
	    }
	  },
	  template: `
		<ResourceNotification
			v-model:checked="isInfoNotificationOn"
			:type="model.type"
			:title="loc('BRCW_NOTIFICATION_CARD_BASE_INFO_TITLE_MSGVER_1')"
			:description="loc('BRCW_NOTIFICATION_CARD_BASE_INFO_HELPER_TEXT_FIRST_MSGVER_2')"
			:helpDesk="helpDesk"
			ref="card"
		>
			<template #client>
				<ResourceNotificationTextRow icon="--clock-2">
					<RichLoc :text="locInfoTimeSend" :placeholder="'[delay/]'">
						<template #delay>
							<LabelDropdown v-model:value="infoNotificationDelay" :items="model.settings.notification.delayValues"/>
						</template>
					</RichLoc>
				</ResourceNotificationTextRow>
			</template>
		</ResourceNotification>
	`
	};

	// @vue/component
	const Confirmation = {
	  name: 'ResourceNotificationCardConfirmation',
	  components: {
	    ResourceNotification,
	    ResourceNotificationTextRow,
	    LabelDropdown,
	    RichLoc: ui_vue3_components_richLoc.RichLoc
	  },
	  props: {
	    /** @type {NotificationsModel} */
	    model: {
	      type: Object,
	      required: true
	    }
	  },
	  setup() {
	    return {
	      HelpDesk: booking_const.HelpDesk,
	      CardId: booking_component_cyclePopup.CardId
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      /** @type {ResourceModel} */
	      resource: `${booking_const.Model.ResourceCreationWizard}/getResource`,
	      isCurrentSenderAvailable: `${booking_const.Model.Notifications}/isCurrentSenderAvailable`
	    }),
	    isConfirmationNotificationOn: {
	      get() {
	        return this.isCurrentSenderAvailable && this.resource.isConfirmationNotificationOn;
	      },
	      set(isConfirmationNotificationOn) {
	        void this.$store.dispatch(`${booking_const.Model.ResourceCreationWizard}/updateResource`, {
	          isConfirmationNotificationOn
	        });
	      }
	    },
	    confirmationNotificationDelay: {
	      get() {
	        return this.resource.confirmationNotificationDelay;
	      },
	      set(confirmationNotificationDelay) {
	        void this.$store.dispatch(`${booking_const.Model.ResourceCreationWizard}/updateResource`, {
	          confirmationNotificationDelay
	        });
	        this.$refs.card.$forceUpdate();
	      }
	    },
	    confirmationNotificationRepetitions: {
	      get() {
	        return this.resource.confirmationNotificationRepetitions;
	      },
	      set(confirmationNotificationRepetitions) {
	        void this.$store.dispatch(`${booking_const.Model.ResourceCreationWizard}/updateResource`, {
	          confirmationNotificationRepetitions
	        });
	        this.$refs.card.$forceUpdate();
	      }
	    },
	    confirmationNotificationRepetitionsInterval: {
	      get() {
	        return this.resource.confirmationNotificationRepetitionsInterval;
	      },
	      set(confirmationNotificationRepetitionsInterval) {
	        void this.$store.dispatch(`${booking_const.Model.ResourceCreationWizard}/updateResource`, {
	          confirmationNotificationRepetitionsInterval
	        });
	        this.$refs.card.$forceUpdate();
	      }
	    },
	    confirmationCounterDelay: {
	      get() {
	        return this.resource.confirmationCounterDelay;
	      },
	      set(confirmationCounterDelay) {
	        void this.$store.dispatch(`${booking_const.Model.ResourceCreationWizard}/updateResource`, {
	          confirmationCounterDelay
	        });
	        this.$refs.card.$forceUpdate();
	      }
	    },
	    locSendMessageBefore() {
	      return this.loc('BRCW_NOTIFICATION_CARD_CONFIRMATION_HELPER_TEXT_SECOND').replace('#days_before#', '[delay/]');
	    },
	    locRetryMessage() {
	      return this.loc('BRCW_NOTIFICATION_CARD_CONFIRMATION_HELPER_TEXT_THIRD').replace('#times#', '[repeat/]').replace('#time_delay#', '[repeatInterval/]');
	    },
	    locManagerRemindTime() {
	      return this.loc('BRCW_NOTIFICATION_CARD_CONFIRMATION_MANAGER_REMIND_TIME').replace('#time#', '[delay/]');
	    }
	  },
	  template: `
		<ResourceNotification
			v-model:checked="isConfirmationNotificationOn"
			:type="model.type"
			:title="loc('BRCW_NOTIFICATION_CARD_CONFIRMATION_TITLE_MSGVER_2')"
			:description="loc('BRCW_NOTIFICATION_CARD_CONFIRMATION_HELPER_TEXT_FIRST_MSGVER_2')"
			:helpDesk="HelpDesk.ResourceNotificationConfirmation"
			:managerDescription="loc('BRCW_NOTIFICATION_CARD_CONFIRMATION_MANAGER_HELPER')"
			:scrollToCard="CardId.Unconfirmed"
			ref="card"
		>
			<template #client>
				<ResourceNotificationTextRow icon="--clock-2">
					<RichLoc :text="locSendMessageBefore" :placeholder="'[delay/]'">
						<template #delay>
							<LabelDropdown v-model:value="confirmationNotificationDelay" :items="model.settings.notification.delayValues"/>
						</template>
					</RichLoc>
				</ResourceNotificationTextRow>
				<ResourceNotificationTextRow icon="--undo-1">
					<RichLoc :text="locRetryMessage" :placeholder="['[repeat/]', '[repeatInterval/]']">
						<template #repeat>
							<LabelDropdown v-model:value="confirmationNotificationRepetitions" :items="model.settings.notification.repeatValues"/>
						</template>
						<template #repeatInterval>
							<LabelDropdown v-model:value="confirmationNotificationRepetitionsInterval" :items="model.settings.notification.repeatIntervalValues"/>
						</template>
					</RichLoc>
				</ResourceNotificationTextRow>
			</template>
			<template #manager>
				<ResourceNotificationTextRow icon="--clock-2">
					<RichLoc :text="locManagerRemindTime" :placeholder="'[delay/]'">
						<template #delay>
							<LabelDropdown v-model:value="confirmationCounterDelay" :items="model.settings.counter.delayValues"/>
						</template>
					</RichLoc>
				</ResourceNotificationTextRow>
			</template>
		</ResourceNotification>
	`
	};

	// @vue/component
	const Reminder = {
	  name: 'ResourceNotificationCardReminder',
	  components: {
	    ResourceNotification,
	    ResourceNotificationTextRow,
	    LabelDropdown,
	    RichLoc: ui_vue3_components_richLoc.RichLoc
	  },
	  props: {
	    /** @type {NotificationsModel} */
	    model: {
	      type: Object,
	      required: true
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      /** @type {ResourceModel} */
	      resource: `${booking_const.Model.ResourceCreationWizard}/getResource`,
	      isCurrentSenderAvailable: `${booking_const.Model.Notifications}/isCurrentSenderAvailable`
	    }),
	    isReminderNotificationOn: {
	      get() {
	        return this.isCurrentSenderAvailable && this.resource.isReminderNotificationOn;
	      },
	      set(isReminderNotificationOn) {
	        void this.$store.dispatch(`${booking_const.Model.ResourceCreationWizard}/updateResource`, {
	          isReminderNotificationOn
	        });
	      }
	    },
	    reminderNotificationDelay: {
	      get() {
	        return this.resource.reminderNotificationDelay;
	      },
	      set(reminderNotificationDelay) {
	        void this.$store.dispatch(`${booking_const.Model.ResourceCreationWizard}/updateResource`, {
	          reminderNotificationDelay
	        });
	        this.$refs.card.$forceUpdate();
	      }
	    },
	    locSendReminderTime() {
	      return this.loc('BRCW_NOTIFICATION_CARD_REMINDER_HELPER_TEXT_SECOND').replace('#time#', '[delay/]');
	    },
	    helpDesk() {
	      return booking_const.HelpDesk.ResourceNotificationReminder;
	    }
	  },
	  template: `
		<ResourceNotification
			v-model:checked="isReminderNotificationOn"
			:type="model.type"
			:title="loc('BRCW_NOTIFICATION_CARD_REMINDER_TITLE')"
			:description="loc('BRCW_NOTIFICATION_CARD_REMINDER_HELPER_TEXT_FIRST_MSGVER_1')"
			:helpDesk="helpDesk"
			ref="card"
		>
			<template #client>
				<ResourceNotificationTextRow icon="--clock-2">
					<RichLoc :text="locSendReminderTime" :placeholder="'[delay/]'">
						<template #delay>
							<LabelDropdown v-model:value="reminderNotificationDelay" :items="model.settings.notification.delayValues"/>
						</template>
					</RichLoc>
				</ResourceNotificationTextRow>
			</template>
		</ResourceNotification>
	`
	};

	// @vue/component
	const Feedback = {
	  name: 'ResourceNotificationCardFeedback',
	  components: {
	    ResourceNotification,
	    ResourceNotificationTextRow,
	    LabelDropdown,
	    RichLoc: ui_vue3_components_richLoc.RichLoc
	  },
	  props: {
	    /** @type {NotificationsModel} */
	    model: {
	      type: Object,
	      required: true
	    }
	  },
	  data() {
	    return {
	      labelValue: this.model.settings.notification.delayValues[0].value
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      /** @type {ResourceModel} */
	      resource: `${booking_const.Model.ResourceCreationWizard}/getResource`,
	      isCurrentSenderAvailable: `${booking_const.Model.Notifications}/isCurrentSenderAvailable`
	    }),
	    isFeedbackNotificationOn: {
	      get() {
	        return false;
	      },
	      set(isFeedbackNotificationOn) {
	        void this.$store.dispatch(`${booking_const.Model.ResourceCreationWizard}/updateResource`, {
	          isFeedbackNotificationOn
	        });
	      }
	    },
	    locSendFeedbackTime() {
	      return this.loc('BRCW_NOTIFICATION_CARD_FEEDBACK_HELPER_TEXT_SECOND').replace('#time#', '[delay/]');
	    },
	    helpDesk() {
	      return booking_const.HelpDesk.ResourceNotificationFeedback;
	    }
	  },
	  template: `
		<ResourceNotification
			v-model:checked="isFeedbackNotificationOn"
			:type="model.type"
			:title="loc('BRCW_NOTIFICATION_CARD_FEEDBACK_TITLE')"
			:description="loc('BRCW_NOTIFICATION_CARD_FEEDBACK_HELPER_TEXT_FIRST_MSGVER_2')"
			:helpDesk="helpDesk"
			:disabled="true"
		>
			<template #client>
				<ResourceNotificationTextRow icon="--clock-2">
					<RichLoc :text="locSendFeedbackTime" :placeholder="'[delay/]'">
						<template #delay>
							<LabelDropdown v-model:value="labelValue" :items="model.settings.notification.delayValues"/>
						</template>
					</RichLoc>
				</ResourceNotificationTextRow>
			</template>
		</ResourceNotification>
	`
	};

	// @vue/component
	const Late = {
	  name: 'ResourceNotificationCardLate',
	  components: {
	    ResourceNotification,
	    ResourceNotificationTextRow,
	    LabelDropdown,
	    RichLoc: ui_vue3_components_richLoc.RichLoc
	  },
	  props: {
	    /** @type {NotificationsModel} */
	    model: {
	      type: Object,
	      required: true
	    }
	  },
	  setup() {
	    return {
	      HelpDesk: booking_const.HelpDesk,
	      CardId: booking_component_cyclePopup.CardId
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      /** @type {ResourceModel} */
	      resource: `${booking_const.Model.ResourceCreationWizard}/getResource`,
	      isCurrentSenderAvailable: `${booking_const.Model.Notifications}/isCurrentSenderAvailable`
	    }),
	    isDelayedNotificationOn: {
	      get() {
	        return this.isCurrentSenderAvailable && this.resource.isDelayedNotificationOn;
	      },
	      set(isDelayedNotificationOn) {
	        void this.$store.dispatch(`${booking_const.Model.ResourceCreationWizard}/updateResource`, {
	          isDelayedNotificationOn
	        });
	      }
	    },
	    delayedNotificationDelay: {
	      get() {
	        return this.resource.delayedNotificationDelay;
	      },
	      set(delayedNotificationDelay) {
	        void this.$store.dispatch(`${booking_const.Model.ResourceCreationWizard}/updateResource`, {
	          delayedNotificationDelay
	        });
	        this.$refs.card.$forceUpdate();
	      }
	    },
	    delayedCounterDelay: {
	      get() {
	        return this.resource.delayedCounterDelay;
	      },
	      set(delayedCounterDelay) {
	        void this.$store.dispatch(`${booking_const.Model.ResourceCreationWizard}/updateResource`, {
	          delayedCounterDelay
	        });
	        this.$refs.card.$forceUpdate();
	      }
	    },
	    locSendMessageAfter() {
	      return this.loc('BRCW_NOTIFICATION_CARD_LATE_HELPER_TEXT_SECOND_MSGVER_1').replace('#time#', '[delay/]');
	    },
	    locNotifyManagerIn() {
	      return this.loc('BRCW_NOTIFICATION_CARD_LATE_MANAGER_NOTIFY_IN').replace('#time#', '[delay/]');
	    }
	  },
	  template: `
		<ResourceNotification
			v-model:checked="isDelayedNotificationOn"
			:type="model.type"
			:title="loc('BRCW_NOTIFICATION_CARD_LATE_TITLE_MSGVER_1')"
			:description="loc('BRCW_NOTIFICATION_CARD_LATE_HELPER_TEXT_FIRST_MSGVER_2')"
			:helpDesk="HelpDesk.ResourceNotificationLate"
			:managerDescription="loc('BRCW_NOTIFICATION_CARD_LATE_MANAGER_HELPER')"
			:scrollToCard="CardId.Late"
			ref="card"
		>
			<template #client>
				<ResourceNotificationTextRow icon="--clock-2">
					<RichLoc :text="locSendMessageAfter" :placeholder="'[delay/]'">
						<template #delay>
							<LabelDropdown v-model:value="delayedNotificationDelay" :items="model.settings.notification.delayValues"/>
						</template>
					</RichLoc>
				</ResourceNotificationTextRow>
			</template>
			<template #manager>
				<ResourceNotificationTextRow icon="--clock-2">
					<RichLoc :text="locNotifyManagerIn" :placeholder="'[delay/]'">
						<template #delay>
							<LabelDropdown v-model:value="delayedCounterDelay" :items="model.settings.counter.delayValues"/>
						</template>
					</RichLoc>
				</ResourceNotificationTextRow>
			</template>
		</ResourceNotification>
	`
	};

	// @vue/component
	const ResourceNotificationCard = {
	  name: 'ResourceNotificationCard',
	  computed: {
	    notificationViews() {
	      return this.notifications.map(model => {
	        var _this$dictionary$Info;
	        return (_this$dictionary$Info = {
	          [this.dictionary.Info.value]: {
	            view: BaseInfo,
	            model
	          },
	          [this.dictionary.Confirmation.value]: {
	            view: Confirmation,
	            model
	          },
	          [this.dictionary.Reminder.value]: {
	            view: Reminder,
	            model
	          },
	          [this.dictionary.Delayed.value]: {
	            view: Late,
	            model
	          },
	          [this.dictionary.Feedback.value]: {
	            view: Feedback,
	            model
	          }
	        }[model.type]) != null ? _this$dictionary$Info : {};
	      });
	    },
	    ...ui_vue3_vuex.mapGetters({
	      notifications: `${booking_const.Model.Notifications}/get`,
	      dictionary: `${booking_const.Model.Dictionary}/getNotifications`
	    })
	  },
	  template: `
		<div class="resource-notification-card">
			<slot v-for="notification of notificationViews" :key="notification.view">
				<component
					:is="notification.view"
					:model="notification.model"
					:data-id="'brcw-resource-notification-view-' + notification.view"
				/>
			</slot>
		</div>
	`
	};

	const App = {
	  name: 'ResourceCreationWizardApp',
	  components: {
	    ResourceCreationWizardLayout,
	    ResourceCreationWizardHeader,
	    ResourceCreationWizardFooter,
	    ResourceCategoryCard,
	    ResourceSettingsCard,
	    ResourceNotificationCard
	  },
	  data() {
	    return {
	      init: true
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapState({
	      step: state => state['resource-creation-wizard'].step,
	      fetching: state => state['resource-creation-wizard'].fetching
	    }),
	    ...ui_vue3_vuex.mapGetters('resource-creation-wizard', ['invalidCurrentCard', 'resourceId']),
	    currentView() {
	      const step = this.step || 1;
	      switch (step) {
	        case 2:
	          return 'ResourceSettingsCard';
	        case 3:
	          return 'ResourceNotificationCard';
	        default:
	          return 'ResourceCategoryCard';
	      }
	    },
	    isEditForm() {
	      return this.resourceId !== null;
	    }
	  },
	  async beforeMount() {
	    await this.initApp();
	  },
	  methods: {
	    ...ui_vue3_vuex.mapActions('resource-creation-wizard', ['initState']),
	    ...ui_vue3_vuex.mapMutations('resource-creation-wizard', ['prevStep', 'updateFetching', 'setGlobalSchedule']),
	    async initApp() {
	      await this.initState();
	      await this.loadWizardData();
	      this.init = false;
	    },
	    async loadWizardData() {
	      try {
	        this.updateFetching(true);
	        await booking_provider_service_resourceCreationWizardService.resourceCreationWizardService.fetchData();
	      } catch (error) {
	        console.error('Loading wizard data error', error);
	      } finally {
	        this.updateFetching(false);
	        if (!this.isEditForm) {
	          this.setGlobalSchedule(true);
	        }
	      }
	    }
	  },
	  template: `
		<div id="booking-resource-creation-wizard" class="booking-resource-creation-wizard">
			<ResourceCreationWizardLayout :loading="fetching" :step>
				<template #header>
					<ResourceCreationWizardHeader/>
				</template>
				<template v-if="!init">
					<component :is="currentView"/>
				</template>
				<template #footer>
					<ResourceCreationWizardFooter :step/>
				</template>
			</ResourceCreationWizardLayout>
		</div>
	`
	};

	let _ = t => t,
	  _t;
	var _width = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("width");
	var _application = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("application");
	var _mountContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mountContent");
	var _initCore = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initCore");
	var _makeContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("makeContainer");
	class ResourceCreationWizard {
	  constructor() {
	    Object.defineProperty(this, _makeContainer, {
	      value: _makeContainer2
	    });
	    Object.defineProperty(this, _initCore, {
	      value: _initCore2
	    });
	    Object.defineProperty(this, _mountContent, {
	      value: _mountContent2
	    });
	    Object.defineProperty(this, _application, {
	      writable: true,
	      value: null
	    });
	  }
	  static makeName(resourceId = 'new') {
	    return `booking:resource-creation-wizard:${resourceId || 'new'}`;
	  }
	  open(resourceId = null) {
	    booking_lib_sidePanelInstance.SidePanelInstance.open(ResourceCreationWizard.makeName(resourceId), {
	      width: babelHelpers.classPrivateFieldLooseBase(ResourceCreationWizard, _width)[_width],
	      cacheable: false,
	      events: {
	        onClose: this.closeSidePanel.bind(this)
	      },
	      contentCallback: async () => {
	        await babelHelpers.classPrivateFieldLooseBase(this, _initCore)[_initCore](resourceId);
	        this.subscribe();
	        const container = babelHelpers.classPrivateFieldLooseBase(this, _makeContainer)[_makeContainer]();
	        babelHelpers.classPrivateFieldLooseBase(this, _mountContent)[_mountContent](container);
	        return container;
	      }
	    });
	  }
	  async closeSidePanel() {
	    this.unsubscribe();
	    babelHelpers.classPrivateFieldLooseBase(this, _application)[_application].unmount();
	    babelHelpers.classPrivateFieldLooseBase(this, _application)[_application] = null;
	    await booking_core.Core.removeDynamicModule(booking_const.Model.ResourceCreationWizard);
	    await booking_core.Core.removeDynamicModule(booking_const.Model.Notifications);
	  }
	  subscribe() {
	    main_core.Event.EventEmitter.subscribe(booking_const.EventName.CloseWizard, this.close);
	  }
	  unsubscribe() {
	    main_core.Event.EventEmitter.unsubscribe(booking_const.EventName.CloseWizard, this.close);
	  }
	  close() {
	    booking_lib_sidePanelInstance.SidePanelInstance.close();
	  }
	}
	function _mountContent2(container) {
	  const application = ui_vue3.BitrixVue.createApp(App, booking_core.Core.getParams());
	  application.mixin(booking_component_mixin_locMixin.locMixin);
	  application.use(booking_core.Core.getStore());
	  application.mount(container);
	  babelHelpers.classPrivateFieldLooseBase(this, _application)[_application] = application;
	}
	async function _initCore2(resourceId) {
	  try {
	    await booking_core.Core.init();
	    await booking_core.Core.addDynamicModule(booking_model_resourceCreationWizard.ResourceCreationWizardModel.create().setVariables({
	      resourceId
	    }));
	    await booking_core.Core.addDynamicModule(booking_model_notifications.Notifications.create());
	  } catch (error) {
	    console.error('Init Resource creation wizard error', error);
	  }
	}
	function _makeContainer2() {
	  return main_core.Tag.render(_t || (_t = _`
			<div id="booking-resource-creation-wizard-app" class="booking-resource-creation-wizard-app"></div>
		`));
	}
	Object.defineProperty(ResourceCreationWizard, _width, {
	  writable: true,
	  value: 600
	});

	exports.ResourceCreationWizard = ResourceCreationWizard;

}((this.BX.Booking = this.BX.Booking || {}),BX.Vue3,BX.Booking.Component.Mixin,BX.Booking.Model,BX.Booking.Model,BX.Booking.Lib,BX,BX.Booking,BX.UI.NotificationManager,BX.Crm.MessageSender,BX.Booking.Provider.Service,BX.Booking.Lib,BX.Booking.Model,BX.Booking.Provider.Service,BX.Booking.Component,BX.UI.EntitySelector,BX.Event,BX.Booking.Lib,BX.UI,BX.Booking.Lib,BX,BX.UI,BX.Booking.Lib,BX.UI.Vue3.Components,BX.Vue3.Directives,BX,BX,BX,BX.Booking.Component,BX.Booking.Provider.Service,BX.Main,BX,BX.Main,BX.Booking.Component,BX.Booking.Component,BX.Booking.Component,BX.UI.IconSet,BX.UI.IconSet,BX.UI.Vue3.Components,BX.Vue3.Vuex,BX,BX.Booking.Const,BX.Booking.Component));
//# sourceMappingURL=resource-creation-wizard.bundle.js.map
