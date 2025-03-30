/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_vue3,booking_component_mixin_locMixin,booking_model_notifications,booking_model_resourceCreationWizard,booking_lib_sidePanelInstance,main_loader,booking_core,ui_notificationManager,crm_messagesender,booking_provider_service_resourcesService,ui_iconSet_actions,booking_provider_service_resourceCreationWizardService,ui_entitySelector,booking_model_resourceTypes,booking_provider_service_resourcesTypeService,main_core_events,ui_buttons,booking_lib_duration,ui_forms,ui_layoutForm,booking_lib_ahaMoments,ui_vue3_directives_hint,ui_iconSet_crm,ui_hint,booking_component_switcher,main_popup,main_core,main_date,ui_iconSet_api_vue,booking_component_popup,booking_component_button,booking_component_helpDeskLoc,ui_label,ui_vue3_vuex,ui_iconSet_main,booking_const) {
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
	  next() {
	    return super.next();
	  }
	  back() {
	    main_core.Event.EventEmitter.emit(booking_const.EventName.CloseWizard);
	  }
	}

	var _resource = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resource");
	var _isBitrix24Approved = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isBitrix24Approved");
	var _isBitrix24SenderAvailable = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isBitrix24SenderAvailable");
	var _prepareCompanySlotRanges = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareCompanySlotRanges");
	var _prepareResourceTypeNotifications = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareResourceTypeNotifications");
	var _upsertResource = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("upsertResource");
	var _prepareNotificationOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareNotificationOptions");
	class ResourceNotificationStep extends Step {
	  constructor() {
	    super();
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
	    const isCheckedForAll = this.store.getters[`${booking_const.Model.ResourceCreationWizard}/isCheckedForAll`](type);
	    return {
	      ...acc,
	      [notificationOnField]: isCheckedForAll ? resource[notificationOnField] : resourceType[notificationOnField],
	      [templateTypeField]: isCheckedForAll ? resource[templateTypeField] : resourceType[templateTypeField]
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

	var _isFirstStep = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isFirstStep");
	class ResourceSettingsStep extends Step {
	  constructor() {
	    super();
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
	    return super.next();
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
	      void this.$store.dispatch(`${booking_const.Model.ResourceCreationWizard}/nextStep`);
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
					{{ loc('BRCW_CHOOSE_CATEGORY') }}
				</div>
				<HelpDeskLoc
					:message="loc('BRCW_CHOOSE_CATEGORY_DESCRIPTION_MSGVER_1')"
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
	    ErrorMessage
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
								:placeholder="this.loc('BRCW_SETTINGS_CARD_NAME_PLACEHOLDER')"
							/>
						</div>
						<ErrorMessage
							v-if="invalidResourceName"
							:message="errorMessage"
						/>
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
	    const title = main_core.Loc.getMessage('BRCW_SETTINGS_CARD_SCHEDULE_TITLE');
	    const titleIconType = ui_iconSet_api_vue.Set.COLLABORATION;
	    const items = [{
	      id: 'common',
	      itemClass: 'resource-creation-wizard__form-settings-schedule-view-common',
	      title: main_core.Loc.getMessage('BRCW_SETTINGS_CARD_SCHEDULE_COLUMNS_TITLE'),
	      description: main_core.Loc.getMessage('BRCW_SETTINGS_CARD_SCHEDULE_COLUMNS_DESCRIPTION_MSGVER_1'),
	      value: true
	    }, {
	      id: 'extra',
	      itemClass: 'resource-creation-wizard__form-settings-schedule-view-extra',
	      title: main_core.Loc.getMessage('BRCW_SETTINGS_CARD_SCHEDULE_CROSS_RESOURCING_TITLE'),
	      description: main_core.Loc.getMessage('BRCW_SETTINGS_CARD_SCHEDULE_CROSS_RESOURCING_DESCRIPTION_MSGVER_1'),
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

	const {
	  mapGetters: mapResourceGetters
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
	    ...mapResourceGetters({
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
	      if (!isTextClick && this.isCompanyScheduleAccess) {
	        top.BX.Event.EventEmitter.subscribeOnce(top.BX.Event.EventEmitter.GLOBAL_TARGET, 'SidePanel.Slider:onLoad', baseEvent => {
	          const slider = baseEvent.getTarget();
	          slider.getWindow().BX.Event.EventEmitter.subscribeOnce(slider.getWindow().BX.Event.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:onSuccessSave', innerBaseEvent => {
	            const extraSettings = innerBaseEvent.getData();
	            extraSettings.reloadAfterClose = false;
	            this.$emit('getGlobalSchedule');
	          });
	        });
	        BX.SidePanel.Instance.open('/settings/configs/?page=schedule', {
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
	      const now = new Date();
	      const utcDate = new Date(now.toLocaleString('en-US', {
	        timeZone: 'UTC'
	      }));
	      const tzDate = new Date(now.toLocaleString('en-US', {
	        timeZone
	      }));
	      return (utcDate.getTime() - tzDate.getTime()) / (1000 * 60);
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
							:class="{'--disabled': !isCompanyScheduleAccess }"
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
	      return this.loc('BRCW_SETTINGS_CARD_WORK_TIME_TITLE');
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
				:text="loc('BRCW_SETTINGS_CARD_WORK_TIME_TEXT_MSGVER_1')"
			/>
			<WorkTimeSelector
				:initialSlotRanges="initialSlotRanges"
				:defaultSlotRange="defaultSlotRange"
				:isGlobalSchedule="isGlobalSchedule"
				:initialTimezone="initialTimezone"
				:currentTimezone="currentTimezone"
				:isCompanyScheduleAccess="isCompanyScheduleAccess"
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
	      minutes: 0
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
	      this.calculateTotalMinutes();
	    },
	    validateMinutes() {
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
	      selectedValue: this.initialSelectedValue,
	      selectedPrecisionValue: this.initialSelectedValue,
	      precisionMode: false
	    };
	  },
	  created() {
	    const templateValues = new Set([units.H, units.H * 2, units.H * 24, units.d * 7]);
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
	  computed: {
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
	      return this.loc('BRCW_SETTINGS_CARD_SLOT_LENGTH_TITLE');
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
				:text="loc('BRCW_SETTINGS_CARD_SLOT_LENGTH_TEXT_MSGVER_1')"
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
	  mapGetters: mapResourceGetters$1,
	  mapActions,
	  mapMutations
	} = ui_vue3_vuex.createNamespacedHelpers('resource-creation-wizard');
	const ResourceSettingsCard = {
	  name: 'ResourceSettingsCard',
	  components: {
	    BaseFields,
	    ScheduleTypes,
	    WorkTime,
	    SlotLength
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
	    ...mapActions(['updateResource', 'setInvalidResourceName', 'setInvalidResourceType']),
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
	    ...mapResourceGetters$1({
	      resource: 'getResource',
	      companyScheduleSlots: 'getCompanyScheduleSlots',
	      isCompanyScheduleAccess: 'isCompanyScheduleAccess',
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
			<WorkTime
				data-id="brcw-resource-settings-work-time"
				:initialSlotRanges="slotRanges"
				:defaultSlotRange="defaultSlotRange"
				:isGlobalSchedule="isGlobalSchedule"
				:initialTimezone="initialTimezone"
				:currentTimezone="timezone"
				:isCompanyScheduleAccess="isCompanyScheduleAccess"
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

	const replaceLabelMixin = {
	  methods: {
	    getLabel(text, isChecked, hint = '') {
	      const uiLabelStyle = isChecked ? 'ui-label ui-label-tag-secondary notification-label --active' : 'ui-label ui-label-tag-light ui-label-fill notification-label';
	      return `
				<div data-hint="${hint}" data-hint-no-icon class="${uiLabelStyle}">
					<div class="ui-label-status"></div>
					<span class="ui-label-inner">${text}</span>
				</div>
			`;
	    }
	  }
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
	    popupId() {
	      return 'booking-choose-channel-menu';
	    },
	    notificationChannelOptions() {
	      return [{
	        label: this.loc('BRCW_NOTIFICATION_CARD_MESSAGE_SELECT_WHA'),
	        value: booking_const.NotificationChannel.WhatsApp
	      }, {
	        label: this.loc('BRCW_NOTIFICATION_CARD_TEMPLATE_POPUP_SELECT_SMS'),
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
	        text: this.loc('BRCW_NOTIFICATION_CARD_TEMPLATE_POPUP_SELECT_SMS'),
	        onclick: () => {
	          this.channel = this.loc('BRCW_NOTIFICATION_CARD_TEMPLATE_POPUP_SELECT_SMS');
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
			ref="menu-button"
			@click="openMenu"
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
			<slot/>
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
				class="resource-creation-wizard__form-notification-text"
			/>
		</ResourceNotificationTextRow>
	`
	};

	const ResourceNotification = {
	  name: 'ResourceNotification',
	  emits: ['update:checked'],
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
	    }
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set,
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      messenger: booking_const.NotificationChannel.WhatsApp,
	      showTemplatePopup: false
	    };
	  },
	  components: {
	    Switcher: booking_component_switcher.Switcher,
	    Icon: ui_iconSet_api_vue.BIcon,
	    Button: booking_component_button.Button,
	    Description,
	    ChannelMenu,
	    ChooseTemplatePopup,
	    TemplateEmpty,
	    CheckedForAll
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
	  },
	  updated() {
	    this.hintManager.init(this.$el);
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
	    }
	  },
	  template: `
		<div class="ui-form resource-creation-wizard__form-notification" :class="{'--disabled': !checked}">
			<div class="resource-creation-wizard__form-notification-info">
				<div class="resource-creation-wizard__form-notification-info-title-row">
					<Icon :name="IconSet.CHAT_LINE"/>
					<div class="resource-creation-wizard__form-notification-info-title">
						{{ title }}
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
				<div class="resource-creation-wizard__form-notification-info-text-row">
					<div class="resource-creation-wizard__form-notification__info-text-row-message-text">
						{{ loc('BRCW_NOTIFICATION_CARD_MESSAGE_TEXT') }}
						<ChannelMenu
							:current-channel="messenger"
							@updateChannel="handleChannelChange"
						/>
					</div>
				</div>
				<template v-if="hasTemplate">
					<div
						v-html="messageTemplate"
						class="resource-creation-wizard__form-notification-info-template"
					></div>
					<div class="resource-creation-wizard__form-notification-info-template-choose-buttons">
						<div class="booking-resource-creation-wizard-choose-template-button" ref="chooseTemplateBtn">
							<Button
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
			<slot/>
			<CheckedForAll :type="type" :disabled="!checked"/>
		</div>
	`
	};

	const BaseInfo = {
	  name: 'ResourceNotificationCardBaseInfo',
	  mixins: [replaceLabelMixin],
	  props: {
	    /** @type {NotificationsModel} */
	    model: {
	      type: Object,
	      required: true
	    }
	  },
	  mounted() {
	    if (booking_lib_ahaMoments.ahaMoments.shouldShow(booking_const.AhaMoment.MessageTemplate)) {
	      setTimeout(() => this.showAhaMoment(), 500);
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
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
	    locInfoTimeSend() {
	      const hint = this.loc('BRCW_BOOKING_SOON_HINT');
	      const immediately = this.loc('BRCW_NOTIFICATION_CARD_LABEL_IMMEDIATELY');
	      return this.loc('BRCW_NOTIFICATION_CARD_BASE_INFO_HELPER_TEXT_SECOND', {
	        '#time#': this.getLabel(immediately, this.isInfoNotificationOn, hint)
	      });
	    },
	    helpDesk() {
	      return booking_const.HelpDesk.ResourceNotificationInfo;
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
	  components: {
	    ResourceNotification,
	    ResourceNotificationTextRow
	  },
	  template: `
		<ResourceNotification
			v-model:checked="isInfoNotificationOn"
			:type="model.type"
			:title="loc('BRCW_NOTIFICATION_CARD_BASE_INFO_TITLE')"
			:description="loc('BRCW_NOTIFICATION_CARD_BASE_INFO_HELPER_TEXT_FIRST_MSGVER_1')"
			:helpDesk="helpDesk"
			ref="card"
		>
			<ResourceNotificationTextRow icon="--clock-2">
				<div class="resource-creation-wizard__form-notification-text" v-html="locInfoTimeSend"></div>
			</ResourceNotificationTextRow>
		</ResourceNotification>
	`
	};

	const Confirmation = {
	  name: 'ResourceNotificationCardConfirmation',
	  mixins: [replaceLabelMixin],
	  props: {
	    /** @type {NotificationsModel} */
	    model: {
	      type: Object,
	      required: true
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
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
	    locSendMessageBefore() {
	      const hint = this.loc('BRCW_BOOKING_SOON_HINT');
	      const daysBefore = this.loc('BRCW_NOTIFICATION_CARD_LABEL_DAYS_BEFORE');
	      return this.loc('BRCW_NOTIFICATION_CARD_CONFIRMATION_HELPER_TEXT_SECOND', {
	        '#days_before#': this.getLabel(daysBefore, this.isConfirmationNotificationOn, hint)
	      });
	    },
	    locRetryMessage() {
	      const hint = this.loc('BRCW_BOOKING_SOON_HINT');
	      const times = this.loc('BRCW_NOTIFICATION_CARD_LABEL_TIMES');
	      const timeDelay = this.loc('BRCW_NOTIFICATION_CARD_LABEL_TIME_DELAY');
	      return this.loc('BRCW_NOTIFICATION_CARD_CONFIRMATION_HELPER_TEXT_THIRD', {
	        '#times#': this.getLabel(times, this.isConfirmationNotificationOn, hint),
	        '#time_delay#': this.getLabel(timeDelay, this.isConfirmationNotificationOn, hint)
	      });
	    },
	    helpDesk() {
	      return booking_const.HelpDesk.ResourceNotificationConfirmation;
	    }
	  },
	  components: {
	    ResourceNotification,
	    ResourceNotificationTextRow
	  },
	  template: `
		<ResourceNotification 
			v-model:checked="isConfirmationNotificationOn"
			:type="model.type"
			:title="loc('BRCW_NOTIFICATION_CARD_CONFIRMATION_TITLE')"
			:description="loc('BRCW_NOTIFICATION_CARD_CONFIRMATION_HELPER_TEXT_FIRST_MSGVER_1')"
			:helpDesk="helpDesk"
		>
			<ResourceNotificationTextRow icon="--clock-2">
				<div class="resource-creation-wizard__form-notification-text" v-html="locSendMessageBefore"></div>
			</ResourceNotificationTextRow>
			<ResourceNotificationTextRow icon="--undo-1">
				<div class="resource-creation-wizard__form-notification-text" v-html="locRetryMessage"></div>
			</ResourceNotificationTextRow>
		</ResourceNotification>
	`
	};

	const Reminder = {
	  name: 'ResourceNotificationCardReminder',
	  mixins: [replaceLabelMixin],
	  props: {
	    /** @type {NotificationsModel} */
	    model: {
	      type: Object,
	      required: true
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
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
	    locSendReminderTime() {
	      const hint = this.loc('BRCW_BOOKING_SOON_HINT');
	      const chooseTime = this.loc('BRCW_NOTIFICATION_CARD_LABEL_CHOOSE_TIME');
	      return this.loc('BRCW_NOTIFICATION_CARD_REMINDER_HELPER_TEXT_SECOND', {
	        '#time#': this.getLabel(chooseTime, this.isReminderNotificationOn, hint)
	      });
	    },
	    helpDesk() {
	      return booking_const.HelpDesk.ResourceNotificationReminder;
	    }
	  },
	  components: {
	    ResourceNotification,
	    ResourceNotificationTextRow
	  },
	  template: `
		<ResourceNotification
			v-model:checked="isReminderNotificationOn"
			:type="model.type"
			:title="loc('BRCW_NOTIFICATION_CARD_REMINDER_TITLE')"
			:description="loc('BRCW_NOTIFICATION_CARD_REMINDER_HELPER_TEXT_FIRST_MSGVER_1')"
			:helpDesk="helpDesk"
		>
			<ResourceNotificationTextRow icon="--clock-2">
				<div class="resource-creation-wizard__form-notification-text" v-html="locSendReminderTime"></div>
			</ResourceNotificationTextRow>
		</ResourceNotification>
	`
	};

	const Feedback = {
	  name: 'ResourceNotificationCardFeedback',
	  mixins: [replaceLabelMixin],
	  props: {
	    /** @type {NotificationsModel} */
	    model: {
	      type: Object,
	      required: true
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
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
	      const hint = this.loc('BRCW_BOOKING_SOON_HINT');
	      const immediately = this.loc('BRCW_NOTIFICATION_CARD_LABEL_IMMEDIATELY');
	      return this.loc('BRCW_NOTIFICATION_CARD_FEEDBACK_HELPER_TEXT_SECOND', {
	        '#time#': this.getLabel(immediately, this.isFeedbackNotificationOn, hint)
	      });
	    },
	    helpDesk() {
	      return booking_const.HelpDesk.ResourceNotificationFeedback;
	    }
	  },
	  components: {
	    ResourceNotification,
	    ResourceNotificationTextRow
	  },
	  template: `
		<ResourceNotification
			v-model:checked="isFeedbackNotificationOn"
			:type="model.type"
			:title="loc('BRCW_NOTIFICATION_CARD_FEEDBACK_TITLE')"
			:description="loc('BRCW_NOTIFICATION_CARD_FEEDBACK_HELPER_TEXT_FIRST_MSGVER_1')"
			:helpDesk="helpDesk"
			:disabled="true"
		>
			<ResourceNotificationTextRow icon="--clock-2">
				<div class="resource-creation-wizard__form-notification-text" v-html="locSendFeedbackTime"></div>
			</ResourceNotificationTextRow>
		</ResourceNotification>
	`
	};

	const Late = {
	  name: 'ResourceNotificationCardLate',
	  mixins: [replaceLabelMixin],
	  props: {
	    /** @type {NotificationsModel} */
	    model: {
	      type: Object,
	      required: true
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
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
	    locSendMessageAfter() {
	      const hint = this.loc('BRCW_BOOKING_SOON_HINT');
	      const minutes = this.loc('BRCW_NOTIFICATION_CARD_LABEL_TIME_AFTER_MINUTES');
	      return this.loc('BRCW_NOTIFICATION_CARD_LATE_HELPER_TEXT_SECOND', {
	        '#time#': this.getLabel(minutes, this.isDelayedNotificationOn, hint)
	      });
	    },
	    helpDesk() {
	      return booking_const.HelpDesk.ResourceNotificationLate;
	    }
	  },
	  components: {
	    ResourceNotification,
	    ResourceNotificationTextRow
	  },
	  template: `
		<ResourceNotification
			v-model:checked="isDelayedNotificationOn"
			:type="model.type"
			:title="loc('BRCW_NOTIFICATION_CARD_LATE_TITLE')"
			:description="loc('BRCW_NOTIFICATION_CARD_LATE_HELPER_TEXT_FIRST_MSGVER_1')"
			:helpDesk="helpDesk"
		>
			<ResourceNotificationTextRow icon="--clock-2">
				<div class="resource-creation-wizard__form-notification-text" v-html="locSendMessageAfter"></div>
			</ResourceNotificationTextRow>
		</ResourceNotification>
	`
	};

	const ResourceNotificationCard = {
	  name: 'ResourceNotificationCard',
	  components: {
	    BaseInfo,
	    Confirmation,
	    Reminder,
	    Late,
	    Feedback
	  },
	  computed: {
	    notificationViews() {
	      return this.notifications.map(notificationsModel => {
	        switch (notificationsModel.type) {
	          case this.dictionary.Info.value:
	            return {
	              view: 'BaseInfo',
	              model: notificationsModel
	            };
	          case this.dictionary.Confirmation.value:
	            return {
	              view: 'Confirmation',
	              model: notificationsModel
	            };
	          case this.dictionary.Reminder.value:
	            return {
	              view: 'Reminder',
	              model: notificationsModel
	            };
	          case this.dictionary.Delayed.value:
	            return {
	              view: 'Late',
	              model: notificationsModel
	            };
	          case this.dictionary.Feedback.value:
	            return {
	              view: 'Feedback',
	              model: notificationsModel
	            };
	          default:
	            return '';
	        }
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
		<div id="booking-resource-creation-wizard">
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

	var _width = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("width");
	var _application = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("application");
	var _makeName = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("makeName");
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
	  open(resourceId = null) {
	    booking_lib_sidePanelInstance.SidePanelInstance.open(babelHelpers.classPrivateFieldLooseBase(ResourceCreationWizard, _makeName)[_makeName](resourceId), {
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
	function _makeName2(resourceId = 'new') {
	  return `booking:resource-creation-wizard:${resourceId || 'new'}`;
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
	  const container = document.createElement('div');
	  container.id = 'booking-resource-creation-wizard-app';
	  return container;
	}
	Object.defineProperty(ResourceCreationWizard, _makeName, {
	  value: _makeName2
	});
	Object.defineProperty(ResourceCreationWizard, _width, {
	  writable: true,
	  value: 600
	});

	exports.ResourceCreationWizard = ResourceCreationWizard;

}((this.BX.Booking = this.BX.Booking || {}),BX.Vue3,BX.Booking.Component.Mixin,BX.Booking.Model,BX.Booking.Model,BX.Booking.Lib,BX,BX.Booking,BX.UI.NotificationManager,BX.Crm.MessageSender,BX.Booking.Provider.Service,BX,BX.Booking.Provider.Service,BX.UI.EntitySelector,BX.Booking.Model,BX.Booking.Provider.Service,BX.Event,BX.UI,BX.Booking.Lib,BX,BX.UI,BX.Booking.Lib,BX.Vue3.Directives,BX,BX,BX.Booking.Component,BX.Main,BX,BX.Main,BX.UI.IconSet,BX.Booking.Component,BX.Booking.Component,BX.Booking.Component,BX.UI,BX.Vue3.Vuex,BX,BX.Booking.Const));
//# sourceMappingURL=resource-creation-wizard.bundle.js.map
