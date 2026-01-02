/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,booking_component_popupMaker,main_sidepanel,ui_vue3_directives_lazyload,booking_component_notePopup,booking_component_clientPopup,main_date,booking_lib_dealHelper,booking_provider_service_bookingActionsService,booking_provider_service_bookingService,ui_entitySelector,booking_provider_service_resourceDialogService,booking_component_popup,ui_label,ui_vue3_directives_hint,ui_iconSet_api_core,booking_component_cyclePopup,booking_lib_ahaMoments,ui_vue3,ui_iconSet_main,booking_lib_helpDesk,booking_component_loader,main_core,main_popup,ui_vue3_vuex,ui_iconSet_api_vue,booking_const,booking_lib_limit,booking_component_button) {
	'use strict';

	const ActionsPopup = {
	  name: 'ActionsPopup',
	  emits: ['close'],
	  props: {
	    bindElement: {
	      type: HTMLElement,
	      required: true
	    },
	    popupId: {
	      type: [Number, String],
	      required: true
	    },
	    /**
	     * @type {Array<PopupMakerItem | PopupMakerItem[]>}
	     */
	    contentStructure: {
	      type: Array,
	      required: true
	    },
	    /**
	     * @type {PopupOptions}
	     */
	    popupOptions: {
	      type: Object,
	      default: null
	    }
	  },
	  data() {
	    return {
	      soonTmp: false
	    };
	  },
	  computed: {
	    actionsPopupId() {
	      return `booking-booking-actions-popup-${this.popupId}`;
	    },
	    config() {
	      return {
	        className: 'booking-booking-actions-popup',
	        bindElement: this.bindElement,
	        width: 325,
	        offsetLeft: this.bindElement.offsetWidth,
	        offsetTop: -200,
	        animation: 'fading-slide',
	        ...this.popupOptions
	      };
	    }
	  },
	  beforeCreate() {
	    main_popup.PopupManager.getPopups().filter(popup => /booking-booking-actions-popup/.test(popup.getId())).forEach(popup => popup.destroy());
	  },
	  components: {
	    StickyPopup: booking_component_popup.StickyPopup,
	    PopupMaker: booking_component_popupMaker.PopupMaker
	  },
	  template: `
		<StickyPopup
			v-slot="{freeze, unfreeze}"
			:id="actionsPopupId"
			:config="config"
			@close="$emit('close')"
		>
			<PopupMaker
				:contentStructure="contentStructure"
				@freeze="freeze"
				@unfreeze="unfreeze"
			/>
			<div class="booking-booking-actions-popup-footer">
				<slot name="footer" />
			</div>
		</StickyPopup>
	`
	};

	// @vue/component
	const Note = {
	  props: {
	    id: {
	      type: [Number, String],
	      required: true
	    },
	    note: {
	      type: String,
	      default: ''
	    },
	    dataId: {
	      type: [Number, String],
	      default: ''
	    },
	    dataElementPrefix: {
	      type: String,
	      default: ''
	    },
	    dataAttributes: {
	      type: Object,
	      default: null
	    }
	  },
	  emits: ['popupShown', 'popupClosed', 'updateNote'],
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set,
	      isPopupShown: false,
	      isEditMode: false
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`
	    }),
	    hasNote() {
	      return Boolean(this.note);
	    }
	  },
	  methods: {
	    onMouseEnter() {
	      this.showNoteTimeout = setTimeout(() => this.showViewPopup(), 100);
	    },
	    onMouseLeave() {
	      clearTimeout(this.showNoteTimeout);
	      this.closeViewPopup();
	    },
	    showViewPopup() {
	      if (this.isPopupShown || !this.hasNote) {
	        return;
	      }
	      this.isEditMode = false;
	      this.showPopup();
	    },
	    closeViewPopup() {
	      if (this.isEditMode) {
	        return;
	      }
	      this.closePopup();
	    },
	    showEditPopup() {
	      this.isEditMode = true;
	      this.showPopup();
	    },
	    closeEditPopup() {
	      if (!this.isEditMode) {
	        return;
	      }
	      this.closePopup();
	    },
	    showPopup() {
	      this.isPopupShown = true;
	      this.$emit('popupShown');
	    },
	    closePopup() {
	      this.isPopupShown = false;
	      this.$emit('popupClosed');
	    },
	    saveBookingNote({
	      note
	    }) {
	      this.$emit('updateNote', {
	        id: this.id,
	        note
	      });
	    }
	  },
	  components: {
	    NotePopup: booking_component_notePopup.NotePopup,
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  template: `
		<div
			class="booking-actions-popup__item-client-note"
			:data-element="dataElementPrefix + '-menu-note'"
			:data-has-note="hasNote"
			:class="{'--empty': !hasNote}"
			v-bind="dataAttributes"
			ref="note"
		>
			<div
				class="booking-actions-popup__item-client-note-inner"
				:data-element="dataElementPrefix + '-menu-note-add'"
				v-bind="dataAttributes"
				@mouseenter="onMouseEnter"
				@mouseleave="onMouseLeave"
				@click="() => hasNote ? showViewPopup() : showEditPopup()"
			>
				<template v-if="hasNote">
					<div
						class="booking-actions-popup__item-client-note-text"
						:data-element="dataElementPrefix + '-menu-note-text'"
						v-bind="dataAttributes"
					>
						{{ note }}
					</div>
					<div
						v-if="isFeatureEnabled"
						class="booking-actions-popup__item-client-note-edit"
						:data-element="dataElementPrefix + '-menu-note-edit'"
						v-bind="dataAttributes"
						@click="showEditPopup"
					>
						<Icon :name="IconSet.PENCIL_40"/>
					</div>
				</template>
				<template v-else>
					<Icon :name="IconSet.PLUS_20"/>
					<div class="booking-actions-popup__item-client-note-text">
						{{ loc('BB_ACTIONS_POPUP_ADD_NOTE') }}
					</div>
				</template>
			</div>
		</div>
		<NotePopup
			v-if="isPopupShown"
			:isEditMode="isEditMode && isFeatureEnabled"
			:id="id"
			:text="note"
			:bindElement="() => $refs.note"
			:dataId="dataId"
			:dataElementPrefix="dataElementPrefix"
			@close="closeEditPopup"
			@save="saveBookingNote"
		/>
	`
	};

	const Empty = {
	  emits: ['popupShown', 'popupClosed', 'addClients'],
	  props: {
	    id: {
	      type: [Number, String],
	      required: true
	    }
	  },
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  data() {
	    return {
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonIcon: booking_component_button.ButtonIcon,
	      isLoading: true,
	      shownClientPopup: false
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`
	    }),
	    btnIcon() {
	      return this.isFeatureEnabled ? booking_component_button.ButtonIcon.ADD : booking_component_button.ButtonIcon.LOCK;
	    },
	    userIcon() {
	      return ui_iconSet_api_vue.Set.PERSON;
	    },
	    personSize() {
	      return 26;
	    },
	    callIcon() {
	      return ui_iconSet_api_vue.Set.TELEPHONY_HANDSET_1;
	    },
	    messageIcon() {
	      return ui_iconSet_api_vue.Set.CHATS_1;
	    },
	    iconSize() {
	      return 20;
	    },
	    iconColor() {
	      return 'var(--ui-color-palette-gray-20)';
	    },
	    soonHint() {
	      return {
	        text: this.loc('BOOKING_BOOKING_SOON_HINT'),
	        popupOptions: {
	          offsetLeft: -60
	        }
	      };
	    }
	  },
	  methods: {
	    showClientPopup() {
	      if (!this.isFeatureEnabled) {
	        booking_lib_limit.limit.show();
	        return;
	      }
	      this.shownClientPopup = true;
	      this.$emit('popupShown');
	    },
	    hideClientPopup() {
	      this.shownClientPopup = false;
	      this.$emit('popupClosed');
	    },
	    addClientsToBook(clients) {
	      this.$emit('addClients', {
	        id: this.id,
	        clients
	      });
	    }
	  },
	  components: {
	    Button: booking_component_button.Button,
	    Icon: ui_iconSet_api_vue.BIcon,
	    ClientPopup: booking_component_clientPopup.ClientPopup
	  },
	  template: `
		<div class="booking-actions-popup__item-client-icon-container">
			<div class="booking-actions-popup__item-client-icon">
				<Icon :name="userIcon" :size="personSize" :color="iconColor"/>
			</div>
		</div>
		<div class="booking-actions-popup__item-client-info --empty">
			<div class="booking-actions-popup__item-client-info-label --empty">
				{{ loc('BB_ACTIONS_POPUP_CLIENT_EMPTY_NAME_LABEL') }}
			</div>
			<div class="booking-actions-popup__item-client-info-empty">
				<div></div>
				<div></div>
			</div>
			<div
				class="booking-actions-popup-item-buttons booking-actions-popup__item-client-info-btn"
				ref="clientButton"
			>
				<Button
					:text="loc('BB_ACTIONS_POPUP_CLIENT_BTN_EMPTY_LABEL')"
					:size="ButtonSize.EXTRA_SMALL"
					:color="ButtonColor.PRIMARY"
					:icon="btnIcon"
					:round="true"
					@click="showClientPopup"
				/>
			</div>
			<ClientPopup
				v-if="shownClientPopup"
				:bindElement="this.$refs.clientButton"
				@create="addClientsToBook"
				@close="hideClientPopup"
			/>
		</div>
		<div v-hint="soonHint" class="booking-actions-popup__item-client-action">
			<Icon :name="callIcon" :size="iconSize" :color="iconColor"/>
			<Icon :name="messageIcon" :size="iconSize" :color="iconColor"/>
		</div>
	`
	};

	// @vue/component
	const EditClientButton = {
	  name: 'EditClientButton',
	  components: {
	    ClientPopup: booking_component_clientPopup.ClientPopup,
	    Button: booking_component_button.Button,
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    id: {
	      type: [Number, Array],
	      required: true
	    },
	    /**
	     * @type ClientData
	     */
	    clients: {
	      type: Array,
	      default: () => []
	    },
	    dataElementPrefix: {
	      type: String,
	      default: ''
	    },
	    dataAttributes: {
	      type: Object,
	      default: null
	    }
	  },
	  emits: ['visible', 'invisible', 'updateClients'],
	  setup() {
	    const iconSet = ui_iconSet_api_vue.Set;
	    const buttonSize = booking_component_button.ButtonSize;
	    const buttonColor = booking_component_button.ButtonColor;
	    const buttonIcon = booking_component_button.ButtonIcon;
	    const isClientPopupShowed = ui_vue3.ref(false);
	    return {
	      iconSet,
	      buttonSize,
	      buttonColor,
	      buttonIcon,
	      isClientPopupShowed
	    };
	  },
	  computed: {
	    getClientByClientData() {
	      return this.$store.getters[`${booking_const.Model.Clients}/getByClientData`];
	    },
	    currentClient() {
	      const getByClientData = this.getClientByClientData;
	      const client = {
	        contact: null,
	        company: null
	      };
	      (this.clients || []).map(clientData => getByClientData(clientData)).forEach(clientModel => {
	        if (clientModel.type.code === booking_const.CrmEntity.Contact) {
	          client.contact = clientModel;
	        } else if (clientModel.type.code === booking_const.CrmEntity.Company) {
	          client.company = clientModel;
	        }
	      });
	      return client;
	    }
	  },
	  methods: {
	    updateClient(clients) {
	      this.$emit('updateClients', {
	        id: this.id,
	        clients
	      });
	    },
	    showPopup() {
	      this.isClientPopupShowed = true;
	      this.$emit('visible');
	    },
	    closePopup() {
	      this.isClientPopupShowed = false;
	      this.$emit('invisible');
	    }
	  },
	  template: `
		<Button
			:data-element="dataElementPrefix + '-menu-client-edit'"
			v-bind="dataAttributes"
			:size="buttonSize.EXTRA_SMALL"
			:color="buttonColor.LIGHT"
			:round="true"
			ref="editClientButton"
			@click="showPopup"
		>
			<Icon :name="iconSet.MORE"/>
		</Button>
		<ClientPopup
			v-if="isClientPopupShowed"
			:bind-element="$refs.editClientButton.$el"
			:current-client="currentClient"
			@create="updateClient"
			@close="closePopup"
		/>
	`
	};

	const SidePanel = main_sidepanel.SidePanel || BX.SidePanel;

	// @vue/component
	const Client = {
	  name: 'ActionsPopupClient',
	  directives: {
	    lazyload: ui_vue3_directives_lazyload.lazyload,
	    hint: ui_vue3_directives_hint.hint
	  },
	  components: {
	    Button: booking_component_button.Button,
	    Icon: ui_iconSet_api_vue.BIcon,
	    Loader: booking_component_loader.Loader,
	    Empty,
	    Note,
	    EditClientButton
	  },
	  props: {
	    id: {
	      type: [Number, String],
	      required: true
	    },
	    /**
	     * @type ClientData
	     */
	    primaryClientData: {
	      type: Object,
	      default: null
	    },
	    /**
	     * @type ClientData
	     */
	    clients: {
	      type: Array,
	      default: () => []
	    },
	    note: {
	      type: String,
	      default: ''
	    },
	    dataId: {
	      type: [Number, String],
	      default: ''
	    },
	    dataElementPrefix: {
	      type: String,
	      default: ''
	    },
	    dataAttributes: {
	      type: Object,
	      default: null
	    }
	  },
	  emits: ['freeze', 'unfreeze', 'addClients', 'updateClients', 'updateNote'],
	  data() {
	    return {
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonIcon: booking_component_button.ButtonIcon,
	      isLoading: true
	    };
	  },
	  computed: {
	    client() {
	      const clientData = this.primaryClientData;
	      return clientData ? this.$store.getters['clients/getByClientData'](clientData) : null;
	    },
	    clientPhone() {
	      const client = this.client;
	      return client.phones.length > 0 ? client.phones[0] : this.loc('BB_ACTIONS_POPUP_CLIENT_PHONE_LABEL');
	    },
	    clientAvatar() {
	      const client = this.client;
	      return client.image;
	    },
	    clientStatus() {
	      if (!this.client.isReturning) {
	        return this.loc('BB_ACTIONS_POPUP_CLIENT_STATUS_FIRST');
	      }
	      return this.loc('BB_ACTIONS_POPUP_CLIENT_STATUS_RETURNING');
	    },
	    userIcon() {
	      return ui_iconSet_api_vue.Set.PERSON;
	    },
	    personSize() {
	      return 26;
	    },
	    callIcon() {
	      return ui_iconSet_api_vue.Set.TELEPHONY_HANDSET_1;
	    },
	    messageIcon() {
	      return ui_iconSet_api_vue.Set.CHATS_1;
	    },
	    iconSize() {
	      return 20;
	    },
	    iconColor() {
	      return 'var(--ui-color-palette-gray-20)';
	    },
	    imageTypeClass() {
	      return '--user';
	    },
	    soonHint() {
	      return {
	        text: this.loc('BOOKING_BOOKING_SOON_HINT'),
	        popupOptions: {
	          offsetLeft: -60
	        }
	      };
	    }
	  },
	  async mounted() {
	    this.isLoading = false;
	  },
	  methods: {
	    openClient() {
	      const entity = this.client.type.code.toLowerCase();
	      SidePanel.Instance.open(`/crm/${entity}/details/${this.client.id}/`);
	    }
	  },
	  template: `
		<div class="booking-actions-popup__item booking-actions-popup__item-client">
			<div class="booking-actions-popup__item-client-client">
				<Loader v-if="isLoading" class="booking-actions-popup__item-client-loader"/>
				<template v-else-if="client">
					<div class="booking-actions-popup__item-client-icon-container">
						<div
							v-if="clientAvatar"
							class="booking-actions-popup-user__avatar"
							:class="imageTypeClass"
						>
							<img
								v-lazyload
								:data-lazyload-src="clientAvatar"
								class="booking-actions-popup-user__source"
								alt="user avatar"
							/>
						</div>
						<div v-else class="booking-actions-popup__item-client-icon">
							<Icon :name="userIcon" :size="personSize" :color="iconColor"/>
						</div>
					</div>
					<div class="booking-actions-popup__item-client-info">
						<div class="booking-actions-popup__item-client-info-label" :title="client.name">
							{{ client.name }}
						</div>
						<div class="booking-actions-popup-item-info">
							<div class="booking-actions-popup-item-subtitle">
								{{ clientStatus }}
							</div>
							<div class="booking-actions-popup-item-subtitle">
								{{ clientPhone }}
							</div>
						</div>
						<div class="booking-actions-popup-item-buttons booking-actions-popup__item-client-info-btn">
							<Button
								:data-element="dataElementPrefix + '-menu-client-open'"
								v-bind="dataAttributes"
								class="booking-actions-popup-item-client-open-button"
								:text="loc('BB_ACTIONS_POPUP_CLIENT_BTN_LABEL')"
								:size="ButtonSize.EXTRA_SMALL"
								:color="ButtonColor.LIGHT_BORDER"
								:round="true"
								@click="openClient"
							/>
							<EditClientButton
								:id
								:clients
								:dataElementPrefix
								:dataAttributes
								@visible="$emit('freeze')"
								@invisible="$emit('unfreeze')"
								@updateClients="$emit('updateClients', $event)"
							/>
						</div>
					</div>
					<div v-hint="soonHint" class="booking-actions-popup__item-client-action">
						<Icon :name="callIcon" :size="iconSize" :color="iconColor"/>
						<Icon :name="messageIcon" :size="iconSize" :color="iconColor"/>
					</div>
				</template>
				<template v-else>
					<Empty
						:id
						@popupShown="$emit('freeze')"
						@popupClosed="$emit('unfreeze')"
						@addClients="$emit('addClients', $event)"
					/>
				</template>
			</div>
			<Note
				:id
				:dataId
				:dataElementPrefix
				:note
				:dataAttributes
				@popupShown="$emit('freeze')"
				@popupClosed="$emit('unfreeze')"
				@updateNote="$emit('updateNote', $event)"
			/>
		</div>
	`
	};

	const ConfirmationMenu = {
	  name: 'ConfirmationMenu',
	  emits: ['popupShown', 'popupClosed', 'updateConfirmationStatus'],
	  props: {
	    id: {
	      type: [Number, String],
	      required: true
	    },
	    isConfirmed: {
	      type: Boolean,
	      required: true
	    },
	    disabled: {
	      type: Boolean,
	      default: false
	    },
	    dataId: {
	      type: [Number, String],
	      default: ''
	    },
	    dataElementPrefix: {
	      type: String,
	      default: ''
	    }
	  },
	  setup() {
	    const iconSet = ui_iconSet_api_vue.Set;
	    const buttonSize = booking_component_button.ButtonSize;
	    const buttonColor = booking_component_button.ButtonColor;
	    const buttonIcon = booking_component_button.ButtonIcon;
	    const menuPopup = null;
	    return {
	      iconSet,
	      buttonSize,
	      buttonColor,
	      buttonIcon,
	      menuPopup
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`
	    }),
	    popupId() {
	      return `booking-confirmation-menu-${this.id}`;
	    }
	  },
	  unmounted() {
	    if (this.menuPopup) {
	      this.destroy();
	    }
	  },
	  methods: {
	    updateConfirmStatus(isConfirmed) {
	      this.$emit('updateConfirmationStatus', {
	        id: this.id,
	        isConfirmed
	      });
	    },
	    openMenu() {
	      var _this$menuPopup, _this$menuPopup$popup;
	      if (!this.isFeatureEnabled) {
	        booking_lib_limit.limit.show();
	        return;
	      }
	      if (this.disabled) {
	        return;
	      }
	      if ((_this$menuPopup = this.menuPopup) != null && (_this$menuPopup$popup = _this$menuPopup.popupWindow) != null && _this$menuPopup$popup.isShown()) {
	        this.destroy();
	        return;
	      }
	      const menuButton = this.$refs.button.$el;
	      this.menuPopup = main_popup.MenuManager.create(this.popupId, menuButton, this.getMenuItems(), {
	        className: 'booking-confirmation-menu-popup',
	        closeByEsc: true,
	        autoHide: true,
	        offsetTop: 0,
	        offsetLeft: menuButton.offsetWidth - menuButton.offsetWidth / 2,
	        angle: true,
	        cacheable: true,
	        events: {
	          onClose: () => this.destroy(),
	          onDestroy: () => this.unbindScrollEvent()
	        }
	      });
	      this.menuPopup.show();
	      this.bindScrollEvent();
	      this.$emit('popupShown');
	    },
	    getMenuItems() {
	      const text = this.isConfirmed ? this.loc('BB_ACTIONS_POPUP_CONFIRMATION_MENU_NOT_CONFIRMED') : this.loc('BB_ACTIONS_POPUP_CONFIRMATION_MENU_CONFIRMED');
	      return [{
	        text,
	        onclick: () => {
	          this.updateConfirmStatus(!this.isConfirmed);
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
	    Icon: ui_iconSet_api_vue.BIcon,
	    Button: booking_component_button.Button
	  },
	  template: `
		<Button
			:data-booking-id="dataId"
			:data-element="dataElementPrefix + '-menu-confirmation-button'"
			class="booking-actions-popup-button-with-chevron"
			:class="{'--lock': !isFeatureEnabled || disabled}"
			buttonClass="ui-btn-shadow"
			:disabled="disabled || !isFeatureEnabled"
			:text="loc('BB_ACTIONS_POPUP_CONFIRMATION_BTN_LABEL')"
			:size="buttonSize.EXTRA_SMALL"
			:color="buttonColor.LIGHT"
			:round="true"
			ref="button"
			@click="openMenu"
		>
			<Icon v-if="isFeatureEnabled" :name="iconSet.CHEVRON_DOWN"/>
			<Icon v-else :name="iconSet.LOCK"/>
		</Button>
	`
	};

	const Confirmation = {
	  name: 'ActionsPopupConfirmation',
	  emits: ['open', 'close', 'updateConfirmationStatus'],
	  props: {
	    id: {
	      type: [Number, String],
	      required: true
	    },
	    isConfirmed: {
	      type: Boolean,
	      required: true
	    },
	    /**
	     * @type BookingCounter[]
	     */
	    counters: {
	      type: Array,
	      default: () => []
	    },
	    disabled: {
	      type: Boolean,
	      default: false
	    },
	    dataId: {
	      type: [Number, String],
	      default: ''
	    },
	    dataElementPrefix: {
	      type: String,
	      default: ''
	    }
	  },
	  setup() {
	    const iconSet = ui_iconSet_api_vue.Set;
	    const isLoading = ui_vue3.ref(true);
	    return {
	      iconSet,
	      isLoading
	    };
	  },
	  async mounted() {
	    this.isLoading = false;
	  },
	  computed: {
	    delayedCounter() {
	      var _this$counters$find;
	      return (_this$counters$find = this.counters.find(counter => counter.type === booking_const.BookingCounterType.Delayed)) == null ? void 0 : _this$counters$find.value;
	    },
	    unconfirmedCounter() {
	      var _this$counters$find2;
	      return (_this$counters$find2 = this.counters.find(counter => counter.type === booking_const.BookingCounterType.Unconfirmed)) == null ? void 0 : _this$counters$find2.value;
	    },
	    iconColor() {
	      if (this.isConfirmed === false && !this.unconfirmedCounter && !this.delayedCounter) {
	        return '#BDC1C6';
	      }
	      return '#ffffff';
	    },
	    stateClass() {
	      if (this.isConfirmed) {
	        return '--confirmed';
	      }
	      if (this.unconfirmedCounter) {
	        return '--not-confirmed';
	      }
	      if (this.delayedCounter) {
	        return '--delayed';
	      }
	      return '--awaiting';
	    },
	    stateText() {
	      if (this.isConfirmed) {
	        return this.loc('BB_ACTIONS_POPUP_CONFIRMATION_CONFIRMED');
	      }
	      if (this.unconfirmedCounter) {
	        return this.loc('BB_ACTIONS_POPUP_CONFIRMATION_NOT_CONFIRMED');
	      }
	      if (this.delayedCounter) {
	        return this.loc('BB_ACTIONS_POPUP_CONFIRMATION_DELAYED');
	      }
	      return this.loc('BB_ACTIONS_POPUP_CONFIRMATION_AWAITING');
	    },
	    hasBtnCounter() {
	      if (this.isConfirmed) {
	        return false;
	      }
	      return Boolean(this.unconfirmedCounter || this.delayedCounter);
	    }
	  },
	  methods: {
	    showHelpDesk() {
	      booking_lib_helpDesk.helpDesk.show(booking_const.HelpDesk.BookingActionsConfirmation.code, booking_const.HelpDesk.BookingActionsConfirmation.anchorCode);
	    }
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon,
	    Loader: booking_component_loader.Loader,
	    ConfirmationMenu
	  },
	  template: `
		<div
			class="booking-actions-popup__item booking-actions-popup__item-confirmation-content"
			:class="{ '--disabled': disabled }"
		>
			<Loader v-if="isLoading" class="booking-actions-popup__item-confirmation-loader"/>
			<template v-else>
				<div :class="['booking-actions-popup-item-icon', stateClass]">
					<Icon :name="iconSet.CHECK" :color="iconColor"/>
				</div>
				<div class="booking-actions-popup-item-info">
					<div class="booking-actions-popup-item-title">
						<span>{{ loc('BB_ACTIONS_POPUP_CONFIRMATION_LABEL') }}</span>
						<Icon :name="iconSet.HELP" @click="showHelpDesk"/>
					</div>
					<div
						:class="['booking-actions-popup-item-subtitle', stateClass]"
						data-element="booking-menu-confirmation-status"
						:data-booking-id="dataId"
						:data-confirmed="isConfirmed"
					>
						{{ stateText }}
					</div>
				</div>
				<div class="booking-actions-popup-item-buttons">
					<ConfirmationMenu
						:id
						:isConfirmed
						:disabled
						:dataId
						:dataElementPrefix
						@popupShown="$emit('open')"
						@popupClosed="$emit('close')"
						@updateConfirmationStatus="$emit('updateConfirmationStatus', $event)"
					/>
					<div
						v-if="hasBtnCounter"
						class="booking-actions-popup-item-buttons-counter"
					></div>
				</div>
			</template>
		</div>
	`
	};

	// @vue/component
	const Deal = {
	  name: 'ActionsPopupDeal',
	  components: {
	    Button: booking_component_button.Button,
	    Icon: ui_iconSet_api_vue.BIcon,
	    Loader: booking_component_loader.Loader
	  },
	  props: {
	    /**
	     * @type DealData
	     */
	    deal: {
	      type: Object,
	      default: null
	    },
	    dealHelper: {
	      type: booking_lib_dealHelper.DealHelper,
	      required: true
	    },
	    disabled: {
	      type: Boolean,
	      default: false
	    },
	    dataId: {
	      type: [Number, String],
	      default: ''
	    },
	    dataElementPrefix: {
	      type: String,
	      default: ''
	    },
	    dataAttributes: {
	      type: Object,
	      default: null
	    }
	  },
	  emits: ['freeze', 'unfreeze'],
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set,
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonIcon: booking_component_button.ButtonIcon,
	      isLoading: false,
	      saveDealDebounce: main_core.Runtime.debounce(this.saveDeal, 10, this)
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`
	    }),
	    menuId() {
	      return `${this.dataElementPrefix}-actions-popup-deal-menu-${this.dataId}`;
	    },
	    dateFormatted() {
	      if (!this.deal.data.createdTimestamp) {
	        return '';
	      }
	      const format = main_date.DateTimeFormat.getFormat('DAY_MONTH_FORMAT');
	      return main_date.DateTimeFormat.format(format, this.deal.data.createdTimestamp);
	    }
	  },
	  mounted() {
	    this.dialog = new ui_entitySelector.Dialog({
	      context: 'BOOKING',
	      multiple: false,
	      targetNode: this.getDialogButton(),
	      width: 340,
	      height: 340,
	      enableSearch: true,
	      dropdownMode: true,
	      preselectedItems: this.deal ? [[booking_const.EntitySelectorEntity.Deal, this.deal.value]] : [],
	      entities: [{
	        id: booking_const.EntitySelectorEntity.Deal,
	        dynamicLoad: true,
	        dynamicSearch: true
	      }],
	      events: {
	        onShow: this.freeze,
	        onHide: this.unfreeze,
	        'Item:onSelect': this.itemChange,
	        'Item:onDeselect': this.itemChange
	      }
	    });
	    main_core.Event.bind(document, 'scroll', this.adjustPosition, true);
	  },
	  beforeUnmount() {
	    main_core.Event.unbind(document, 'scroll', this.adjustPosition, true);
	  },
	  methods: {
	    freeze() {
	      this.$emit('freeze');
	    },
	    unfreeze() {
	      var _this$dialog, _this$getMenu;
	      if ((_this$dialog = this.dialog) != null && _this$dialog.isOpen() || (_this$getMenu = this.getMenu()) != null && _this$getMenu.getPopupWindow().isShown()) {
	        return;
	      }
	      this.$emit('unfreeze');
	    },
	    createDeal() {
	      if (!this.isFeatureEnabled) {
	        void booking_lib_limit.limit.show();
	        return;
	      }
	      this.dealHelper.createDeal();
	    },
	    showMenu() {
	      if (!this.isFeatureEnabled) {
	        void booking_lib_limit.limit.show();
	        return;
	      }
	      const bindElement = this.$refs.moreButton.$el;
	      main_popup.MenuManager.destroy(this.menuId);
	      main_popup.MenuManager.show({
	        id: this.menuId,
	        bindElement,
	        items: this.getMenuItems(),
	        offsetLeft: bindElement.offsetWidth / 2,
	        angle: true,
	        events: {
	          onShow: this.freeze,
	          onAfterClose: this.unfreeze,
	          onDestroy: this.unfreeze
	        }
	      });
	    },
	    getMenuItems() {
	      return [{
	        text: this.loc('BB_ACTIONS_POPUP_DEAL_CHANGE'),
	        onclick: () => {
	          this.showDealDialog();
	          this.getMenu().close();
	        }
	      }, {
	        text: this.loc('BB_ACTIONS_POPUP_DEAL_CLEAR'),
	        onclick: () => {
	          var _this$dialog2;
	          (_this$dialog2 = this.dialog) == null ? void 0 : _this$dialog2.deselectAll();
	          this.saveDealDebounce(null);
	          this.getMenu().close();
	        }
	      }];
	    },
	    showDealDialog() {
	      if (!this.isFeatureEnabled) {
	        void booking_lib_limit.limit.show();
	        return;
	      }
	      this.dialog.setTargetNode(this.getDialogButton());
	      this.dialog.show();
	    },
	    adjustPosition() {
	      var _this$getMenu2;
	      this.dialog.setTargetNode(this.getDialogButton());
	      this.dialog.adjustPosition();
	      (_this$getMenu2 = this.getMenu()) == null ? void 0 : _this$getMenu2.getPopupWindow().adjustPosition();
	    },
	    getMenu() {
	      return main_popup.MenuManager.getMenuById(this.menuId);
	    },
	    openDeal() {
	      this.dealHelper.openDeal();
	    },
	    itemChange() {
	      const dealData = this.getDealData();
	      this.saveDealDebounce(dealData);
	      this.dialog.hide();
	    },
	    getDealData() {
	      const item = this.dialog.getSelectedItems()[0];
	      if (!item) {
	        return null;
	      }
	      return this.dealHelper.mapEntityInfoToDeal(item.getCustomData().get('entityInfo'));
	    },
	    saveDeal(dealData) {
	      this.dealHelper.saveDeal(dealData);
	    },
	    getDialogButton() {
	      return this.deal ? this.$refs.moreButton.$el : this.$refs.addButton.$el;
	    },
	    showHelpDesk() {
	      booking_lib_helpDesk.helpDesk.show(booking_const.HelpDesk.BookingActionsDeal.code, booking_const.HelpDesk.BookingActionsDeal.anchorCode);
	    }
	  },
	  template: `
		<div
			class="booking-actions-popup__item booking-actions-popup__item-deal-content"
			:class="{ '--active': deal }"
		>
			<Loader v-if="isLoading" class="booking-actions-popup__item-deal-loader" />
			<template v-else>
				<div class="booking-actions-popup__item-deal">
					<div class="booking-actions-popup-item-icon">
						<Icon :name="IconSet.DEAL"/>
					</div>
					<div class="booking-actions-popup-item-info">
						<div class="booking-actions-popup-item-title">
							<span>{{ loc('BB_ACTIONS_POPUP_DEAL_LABEL') }}</span>
							<Icon :name="IconSet.HELP" @click="showHelpDesk" />
						</div>
						<template v-if="deal">
							<div
								class="booking-actions-popup__item-deal-profit"
								:data-element="dataElementPrefix + '-menu-deal-profit'"
								:data-profit="deal.data.opportunity"
								v-bind="dataAttributes"
								v-html="deal.data.formattedOpportunity"
							></div>
							<div
								class="booking-actions-popup-item-subtitle"
								:data-element="dataElementPrefix + '-menu-deal-ts'"
								:data-ts="deal.data.createdTimestamp * 1000"
								v-bind="dataAttributes"
							>
								{{ dateFormatted }}
							</div>
						</template>
						<template v-else>
							<div class="booking-actions-popup-item-subtitle">
								{{ loc('BB_ACTIONS_POPUP_DEAL_ADD_LABEL') }}
							</div>
						</template>
					</div>
				</div>
				<div class="booking-actions-popup-item-buttons">
					<template v-if="deal">
						<Button
							:data-element="dataElementPrefix + '-menu-deal-open-button'"
							v-bind="dataAttributes"
							buttonClass="ui-btn-shadow"
							:text="loc('BB_ACTIONS_POPUP_DEAL_OPEN')"
							:size="ButtonSize.EXTRA_SMALL"
							:color="ButtonColor.LIGHT"
							:round="true"
							@click="openDeal"
						/>
						<Button
							:data-element="dataElementPrefix + '-menu-deal-more-button'"
							v-bind="dataAttributes"
							buttonClass="ui-btn-shadow"
							:size="ButtonSize.EXTRA_SMALL"
							:color="ButtonColor.LIGHT"
							:round="true"
							ref="moreButton"
							@click="showMenu"
						>
							<Icon :name="IconSet.MORE"/>
						</Button>
					</template>
					<template v-else>
						<Button
							:data-element="dataElementPrefix + '-menu-deal-create-button'"
							v-bind="dataAttributes"
							class="booking-actions-popup-plus-button"
							:class="{'--lock': !isFeatureEnabled}"
							buttonClass="ui-btn-shadow"
							:size="ButtonSize.EXTRA_SMALL"
							:color="ButtonColor.LIGHT"
							:round="true"
							@click="createDeal"
						>
							<Icon v-if="isFeatureEnabled" :name="IconSet.PLUS_30"/>
							<Icon v-else :name="IconSet.LOCK"/>
						</Button>
						<Button
							class="booking-menu-deal-add-button"
							:class="{'--lock': !isFeatureEnabled}"
							:data-element="dataElementPrefix + '-menu-deal-add-button'"
							v-bind="dataAttributes"
							buttonClass="ui-btn-shadow"
							:text="loc('BB_ACTIONS_POPUP_DEAL_BTN_LABEL')"
							:size="ButtonSize.EXTRA_SMALL"
							:color="ButtonColor.LIGHT"
							:round="true"
							ref="addButton"
							@click="showDealDialog"
						>
							<Icon v-if="!isFeatureEnabled" :name="IconSet.LOCK"/>
						</Button>
					</template>
				</div>
			</template>
		</div>
	`
	};

	const Document = {
	  name: 'ActionsPopupDocument',
	  emits: ['link'],
	  props: {
	    id: {
	      type: [Number, String],
	      required: true
	    },
	    loading: {
	      type: Boolean,
	      default: false
	    },
	    disabled: {
	      type: Boolean,
	      default: false
	    },
	    dataId: {
	      type: [Number, String],
	      default: ''
	    },
	    dataElementPrefix: {
	      type: String,
	      default: ''
	    }
	  },
	  setup() {
	    const iconSet = ui_iconSet_api_vue.Set;
	    const buttonSize = booking_component_button.ButtonSize;
	    const buttonColor = booking_component_button.ButtonColor;
	    const buttonIcon = booking_component_button.ButtonIcon;
	    return {
	      iconSet,
	      buttonSize,
	      buttonColor,
	      buttonIcon
	    };
	  },
	  async mounted() {
	    await booking_provider_service_bookingActionsService.bookingActionsService.getDocData();
	    this.isLoading = false;
	  },
	  methods: {
	    linkDoc() {
	      this.$emit('link');
	    }
	  },
	  components: {
	    Button: booking_component_button.Button,
	    Icon: ui_iconSet_api_vue.BIcon,
	    Loader: booking_component_loader.Loader
	  },
	  template: `
		<div class="booking-actions-popup__item booking-actions-popup__item-doc-content --disabled">
			<Loader v-if="loading" class="booking-actions-popup__item-doc-loader"/>
			<template v-else>
				<div class="booking-actions-popup__item-doc">
					<div class="booking-actions-popup-item-icon">
						<Icon :name="iconSet.DOCUMENT"/>
					</div>
					<div class="booking-actions-popup-item-info">
						<div class="booking-actions-popup-item-title">
							<span>{{ loc('BB_ACTIONS_POPUP_DOC_LABEL') }}</span>
							<Icon :name="iconSet.HELP"/>
						</div>
						<div class="booking-actions-popup-item-subtitle">
							{{ loc('BB_ACTIONS_POPUP_DOC_ADD_LABEL') }}
						</div>
					</div>
				</div>
				<div class="booking-actions-popup-item-buttons">
					<Button
						class="booking-actions-popup-plus-button"
						buttonClass="ui-btn-shadow"
						:size="buttonSize.EXTRA_SMALL"
						:color="buttonColor.LIGHT"
						:disabled="disabled"
						:round="true"
					>
						<Icon :name="iconSet.PLUS_30"/>
					</Button>
					<Button
						buttonClass="ui-btn-shadow"
						:text="loc('BB_ACTIONS_POPUP_DOC_BTN_LABEL')"
						:size="buttonSize.EXTRA_SMALL"
						:color="buttonColor.LIGHT"
						:disabled="disabled"
						:round="true"
						@click="linkDoc"
					/>
				</div>
			</template>
			<div class="booking-booking-actions-popup-label">
				{{ loc('BB_ACTIONS_POPUP_LABEL_SOON') }}
			</div>
		</div>
	`
	};

	// @vue/component
	const ExtraResourcesDialog = {
	  name: 'ExtraResourcesDialog',
	  props: {
	    /**
	     * @type {BookingModel}
	     */
	    booking: {
	      type: Object,
	      required: true
	    },
	    resourceId: {
	      type: Number,
	      required: true
	    }
	  },
	  emits: ['save'],
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      resources: `${booking_const.Model.Resources}/get`,
	      selectedDateTs: `${booking_const.Model.Interface}/selectedDateTs`,
	      getByInterval: `${booking_const.Model.Bookings}/getByInterval`
	    }),
	    extraResourcesIds() {
	      return this.booking.resourcesIds.filter(resourceId => resourceId !== this.resourceId);
	    },
	    bookings() {
	      return this.getByInterval(this.booking.dateFromTs, this.booking.dateToTs);
	    },
	    resourceBookingsMap() {
	      const map = new Map();
	      const bookingId = this.booking.id;
	      this.bookings.filter(booking => booking.id !== bookingId).forEach(booking => {
	        booking.resourcesIds.forEach(resourceId => {
	          const resourceBookingIds = map.get(resourceId) || [];
	          resourceBookingIds.push(booking.id);
	          map.set(resourceId, resourceBookingIds);
	        });
	      });
	      return map;
	    },
	    excludedResourceIds() {
	      return new Set([this.resourceId]);
	    }
	  },
	  watch: {
	    resources(resources) {
	      this.addItems(resources);
	    }
	  },
	  created() {
	    void this.loadResources();
	  },
	  mounted() {
	    this.dialog = new ui_entitySelector.Dialog({
	      id: `booking-booking-extra-resources-selector-${this.booking.id}`,
	      targetNode: this.$refs.dialog,
	      preselectedItems: this.extraResourcesIds.map(id => [booking_const.EntitySelectorEntity.Resource, id]),
	      width: 340,
	      height: Math.min(window.innerHeight - 380, 400),
	      dropdownMode: true,
	      entities: [{
	        id: booking_const.EntitySelectorEntity.Resource,
	        dynamicLoad: true,
	        dynamicSearch: true
	      }],
	      popupOptions: {
	        id: `booking-actions-popup-extra-resources-dialog-${this.booking.id}-${this.resourceId}`,
	        className: 'booking--booking--actions-popup--extra-resources-info--extra-resources-dialog'
	      },
	      enableSearch: true,
	      searchOptions: {
	        allowCreateItem: false
	      },
	      events: {
	        onLoad: () => {
	          this.addItems(this.resources);
	        },
	        onHide: () => {
	          const selectedItemIds = this.dialog.getSelectedItems().map(item => item.id);
	          selectedItemIds.push(this.resourceId);
	          this.$emit('save', selectedItemIds);
	        },
	        'Item:onBeforeSelect': event => {
	          const item = event.data.item;
	          if ((this.resourceBookingsMap.get(item.id) || []).length > 1) {
	            // eslint-disable-next-line no-param-reassign
	            event.defaultPrevented = true;
	          }
	        }
	      }
	    });
	    this.dialog.show();
	  },
	  unmounted() {
	    var _this$dialog;
	    (_this$dialog = this.dialog) == null ? void 0 : _this$dialog.destroy == null ? void 0 : _this$dialog.destroy();
	  },
	  methods: {
	    async loadResources() {
	      await booking_provider_service_resourceDialogService.resourceDialogService.fillDialog(this.selectedDateTs / 1000);
	    },
	    getItemOptions(resource) {
	      return {
	        id: resource.id,
	        entityId: booking_const.EntitySelectorEntity.Resource,
	        title: resource.name,
	        subtitle: '',
	        avatar: '/bitrix/js/booking/component/actions-popup/images/extra-resource-icon.svg',
	        avatarOptions: {
	          bgImage: '/bitrix/js/booking/component/actions-popup/images/extra-resource-icon.svg',
	          bgColor: 'rgba(230, 244, 255, 1)',
	          borderRadius: '50%'
	        },
	        tabs: booking_const.EntitySelectorTab.Recent,
	        selected: this.isItemSelected(resource.id),
	        deselectable: !this.excludedResourceIds.has(resource.id),
	        nodeAttributes: {
	          'data-id': `${this.booking.id}-${resource.id}`,
	          'data-element': 'booking-extra-resources-dialog-item'
	        },
	        badges: [this.getItemBadge(resource)],
	        badgesOptions: {
	          justifyContent: 'right'
	        }
	      };
	    },
	    isItemSelected(id) {
	      return this.extraResourcesIds.includes(id);
	    },
	    getItemBadge(resource) {
	      const resourceBookingsCount = (this.resourceBookingsMap.get(resource.id) || []).length;
	      if (resourceBookingsCount >= 2) {
	        return {
	          title: this.loc('BOOKING_ACTIONS_POPUP_EXTRA_RESOURCES_INFO_RESOURCE_SELECTOR_BADGE_BUSY'),
	          textColor: 'rgba(255, 255, 255, 1)',
	          bgColor: '#e92f2a'
	        };
	      }
	      if (resourceBookingsCount > 0) {
	        return {
	          title: this.loc('BOOKING_ACTIONS_POPUP_EXTRA_RESOURCES_INFO_RESOURCE_SELECTOR_BADGE_OVERBOOKING'),
	          textColor: 'rgba(255, 255, 255, 1)',
	          bgColor: 'rgba(250, 167, 44, 1)'
	        };
	      }
	      return {};
	    },
	    addItems(resources) {
	      const itemsOptions = resources.filter(({
	        id
	      }) => !this.excludedResourceIds.has(id)).reduce((acc, resource) => ({
	        ...acc,
	        [resource.id]: this.getItemOptions(resource)
	      }), {});
	      Object.values(itemsOptions).forEach(itemOptions => this.dialog.addItem(itemOptions));
	      const itemsIds = this.dialog.getItems().map(item => item.getId()).filter(id => itemsOptions[id]);
	      this.dialog.removeItems();
	      itemsIds.forEach(id => this.dialog.addItem(itemsOptions[id]));
	      const tab = this.dialog.getActiveTab();
	      if (tab) {
	        tab.getContainer().append(tab.getRootNode().getChildrenContainer());
	        tab.render();
	      }
	    }
	  },
	  template: `
		<div ref="dialog"></div>
	`
	};

	// @vue/component
	const ExtraResourcesInfoPopupItem = {
	  name: 'ExtraResourcesInfoPopupItem',
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    title: {
	      type: String,
	      required: true
	    },
	    overbooking: {
	      type: Boolean,
	      required: true
	    }
	  },
	  setup() {
	    const iconColor = 'var(--ui-color-accent-main-primary)';
	    const iconSize = 16;
	    return {
	      Outline: ui_iconSet_api_vue.Outline,
	      iconColor,
	      iconSize
	    };
	  },
	  computed: {
	    labelHTML() {
	      const label = new ui_label.Label({
	        color: ui_label.LabelColor.WARNING,
	        size: ui_label.LabelSize.SM,
	        text: this.loc('BOOKING_ACTIONS_POPUP_EXTRA_RESOURCES_INFO_OVERBOOKING'),
	        fill: true
	      });
	      return label.render().outerHTML;
	    }
	  },
	  template: `
		<div class="booking--extra-resources-info_element">
			<div class="booking--extra-resources-info_element-icon —ui-context-content-light">
				<Icon :name="Outline.PRODUCT" :size="iconSize" :color="iconColor"/>
			</div>

			<div class="booking--extra-resources-info_element-text" :title="title">
				{{ title }}
			</div>

			<div 
				v-if="overbooking"
				class="booking--extra-resources-info_element-overbooking"
				 v-html="labelHTML"
			></div>
		</div>
	`
	};

	// @vue/component
	const ExtraResourcesInfoPopup = {
	  name: 'ExtraResourcesInfoPopup',
	  components: {
	    Popup: booking_component_popup.Popup,
	    ExtraResourcesInfoPopupItem
	  },
	  props: {
	    visible: {
	      type: Boolean,
	      default: false
	    },
	    bindElement: {
	      type: HTMLElement,
	      required: true
	    },
	    resourceId: {
	      type: Number,
	      required: true
	    },
	    /**
	     * @type {BookingModel}
	     */
	    booking: {
	      type: Object,
	      required: true
	    }
	  },
	  emits: ['update:visible'],
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      getResourcesByIds: `${booking_const.Model.Resources}/getByIds`,
	      getByInterval: `${booking_const.Model.Bookings}/getByInterval`
	    }),
	    config() {
	      return {
	        bindElement: this.bindElement,
	        offsetTop: 10,
	        offsetLeft: -65,
	        padding: 14,
	        contentPadding: 30,
	        height: 300,
	        width: 340,
	        minWidth: 340,
	        maxWidth: 400,
	        bindOptions: {
	          forceBindPosition: true,
	          position: 'bottom'
	        }
	      };
	    },
	    resources() {
	      const extraResourceIds = this.booking.resourcesIds.filter(resourceId => resourceId !== this.resourceId);
	      return this.getResourcesByIds(extraResourceIds);
	    },
	    bookings() {
	      return this.getByInterval(this.booking.dateFromTs, this.booking.dateToTs);
	    },
	    resourceBookingsMap() {
	      const map = new Map();
	      this.bookings.forEach(booking => {
	        booking.resourcesIds.forEach(resourceId => {
	          const resourceBookingIds = map.get(resourceId) || [];
	          resourceBookingIds.push(booking.id);
	          map.set(resourceId, resourceBookingIds);
	        });
	      });
	      return map;
	    }
	  },
	  methods: {
	    closePopup() {
	      this.$emit('update:visible', false);
	    },
	    isOverbooking(resource) {
	      const resourceBookingsCount = (this.resourceBookingsMap.get(resource.id) || []).length;
	      return resourceBookingsCount > 1;
	    }
	  },
	  template: `
		<Popup
			ref="popup"
			id="booking--booking--extra-resources-info"
			:config
			@close="closePopup"
		>
			<div class="booking--extra-resources-info_container">
				<div class="booking--extra-resources-info_content">
					<template v-for="resource in resources" :key="resource.id">
						<ExtraResourcesInfoPopupItem
							:title="resource.name"
							:overbooking="isOverbooking(resource)"
						/>
					</template>
				</div>
			</div>
		</Popup>
	`
	};

	// @vue/component
	const ExtraResourcesInfo = {
	  name: 'ExtraResourceInfo',
	  components: {
	    UiButton: booking_component_button.Button,
	    UiIcon: ui_iconSet_api_vue.BIcon,
	    ExtraResourcesDialog,
	    ExtraResourcesInfoPopup
	  },
	  props: {
	    id: {
	      type: [Number, String],
	      required: true
	    },
	    resourceId: {
	      type: Number,
	      required: true
	    }
	  },
	  emits: ['freeze', 'unfreeze'],
	  setup() {
	    const iconProductName = ui_iconSet_api_vue.Outline.PRODUCT;
	    const iconEditName = ui_iconSet_api_vue.Outline.EDIT_M;
	    const iconHelpName = ui_iconSet_api_vue.Set.HELP;
	    return {
	      iconProductName,
	      iconEditName,
	      iconHelpName,
	      AirButtonStyle: booking_component_button.AirButtonStyle,
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonStyle: booking_component_button.ButtonStyle
	    };
	  },
	  data() {
	    return {
	      shownResourcesSelector: false,
	      showDialogInfo: false
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      getBookingById: `${booking_const.Model.Bookings}/getById`
	    }),
	    booking() {
	      return this.getBookingById(this.id);
	    },
	    subtitle() {
	      if (this.hasExtraResources) {
	        const count = this.extraResourcesIds.length;
	        return main_core.Loc.getMessagePlural('BOOKING_ACTIONS_POPUP_EXTRA_RESOURCES_INFO_SUBTITLE', count, {
	          '#COUNT#': count
	        });
	      }
	      return this.loc('BOOKING_ACTIONS_POPUP_EXTRA_RESOURCES_INFO_SUBTITLE_EMPTY');
	    },
	    extraResourcesIds() {
	      return this.booking.resourcesIds.slice(1);
	    },
	    hasExtraResources() {
	      return this.extraResourcesIds.length > 0;
	    },
	    iconProductColor() {
	      return this.hasExtraResources ? 'rgba(0, 117, 255, 1)' : 'rgba(201, 204, 208, 1)';
	    }
	  },
	  methods: {
	    toggleResourcesSelector() {
	      this.shownResourcesSelector = true;
	      this.$emit('freeze');
	    },
	    toggleResourcesInfo() {
	      this.showDialogInfo = this.hasExtraResources ? !this.showDialogInfo : this.showDialogInfo;
	    },
	    hideResourcesSelector() {
	      this.shownResourcesSelector = false;
	      this.$emit('unfreeze');
	    },
	    saveBookingExtraResources(extraResources) {
	      this.hideResourcesSelector();
	      if (!this.hasExtraResourcesChanged(extraResources)) {
	        return;
	      }
	      const resourcesIds = new Set([this.booking.resourcesIds[0], ...extraResources]);
	      void booking_provider_service_bookingService.bookingService.update({
	        id: this.booking.id,
	        resourcesIds: [...resourcesIds]
	      });
	    },
	    hasExtraResourcesChanged(extraResources) {
	      const bookingExtraResources = this.booking.resourcesIds.slice(1).sort();
	      if (bookingExtraResources.length !== extraResources.length) {
	        return true;
	      }
	      return [...bookingExtraResources].sort().join(',') !== [...extraResources].sort().join(',');
	    }
	  },
	  template: `
		<div class="booking-actions-popup__item booking--actions-popup--extra-resources-info">
			<div class="booking--actions-popup--extra-resources-info__row">
				<div
					:class="[
						'booking-actions-popup-item-icon',
						'booking--actions-popup--extra-resources-info__icon-product-bg',
						{
							'--active': hasExtraResources
						}
					]"
				>
					<UiIcon
						:name="iconProductName"
						:color="iconProductColor"
					/>
				</div>
				<div class="booking--actions-popup--extra-resources-info__content">
					<div class="booking--actions-popup--extra-resources-info__title">
						<span>{{ loc('BOOKING_ACTIONS_POPUP_EXTRA_RESOURCES_INFO_TITLE') }}</span>
					</div>
					<div class="booking--actions-popup--extra-resources-info__subtitle">
						<span
							ref="button"
							data-element="amount-additional-resources"
							:class="{ '--fill': hasExtraResources }"
							@click="toggleResourcesInfo"
						>
							{{ subtitle }}
						</span>
						<ExtraResourcesInfoPopup
							v-if="showDialogInfo"
							v-model:visible="showDialogInfo"
							:bindElement="$refs.button"
							:booking
							:resourceId
						/>
					</div>
				</div>
				<div ref="edit" class="booking--actions-popup--extra-resources-info__icon-edit">
					<UiButton
						data-element="btn-toggle-resources-selector"
						:icon="iconEditName"
						:buttonClass="['--air', ButtonStyle.NO_CAPS, AirButtonStyle.OUTLINE_NO_ACCENT]"
						:color="ButtonColor.LIGHT_BORDER"
						:size="ButtonSize.SMALL"
						@click="toggleResourcesSelector"
					/>
					<ExtraResourcesDialog
						v-if="shownResourcesSelector"
						:booking
						:resourceId
						@save="saveBookingExtraResources"
					/>
				</div>
			</div>
		</div>
	`
	};

	// @vue/component
	const FullForm = {
	  name: 'ActionsPopupFullForm',
	  components: {
	    UiIcon: ui_iconSet_api_vue.BIcon
	  },
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  props: {
	    bookingId: {
	      type: Number,
	      required: true
	    }
	  },
	  computed: {
	    arrowIcon() {
	      return ui_iconSet_api_vue.Set.CHEVRON_RIGHT;
	    },
	    arrowIconSize() {
	      return 12;
	    },
	    arrowIconColor() {
	      return 'var(--ui-color-palette-gray-40)';
	    },
	    soonHint() {
	      return {
	        text: this.loc('BOOKING_BOOKING_SOON_HINT'),
	        popupOptions: {
	          offsetLeft: 60
	        }
	      };
	    }
	  },
	  template: `
		<div
			v-hint="soonHint"
			class="booking-actions-popup__item booking-actions-popup__item-full-form-content --disabled"
			role="button"
			tabindex="0"
		>
			<div class="booking-actions-popup__item-full-form-label">
				{{ loc('BB_ACTIONS_POPUP_FULL_FORM_LABEL') }}
			</div>
			<div class="booking-actions-popup__item-full-form-icon">
				<UiIcon :name="arrowIcon" :size="arrowIconSize" :color="arrowIconColor"/>
			</div>
		</div>
	`
	};

	// @vue/component
	const Info = {
	  components: {
	    BIcon: ui_iconSet_api_vue.BIcon,
	    CyclePopup: booking_component_cyclePopup.CyclePopup
	  },
	  props: {
	    bookingId: {
	      type: [Number, String],
	      default: 0
	    },
	    waitListItemId: {
	      type: [Number, String],
	      default: 0
	    }
	  },
	  emits: ['freeze', 'unfreeze'],
	  setup() {
	    return {
	      Main: ui_iconSet_api_core.Main
	    };
	  },
	  data() {
	    return {
	      nowTs: Date.now(),
	      isPopupShown: false
	    };
	  },
	  computed: {
	    booking() {
	      return this.$store.getters[`${booking_const.Model.Bookings}/getById`](this.bookingId);
	    },
	    waitListItem() {
	      return this.$store.getters[`${booking_const.Model.WaitList}/getById`](this.waitListItemId);
	    },
	    scrollToCard() {
	      return {
	        [this.isUnconfirmed]: booking_component_cyclePopup.CardId.Unconfirmed,
	        [this.isConfirmed]: booking_component_cyclePopup.CardId.Confirmed,
	        [this.isLate]: booking_component_cyclePopup.CardId.Late,
	        [this.isWaitListItem]: booking_component_cyclePopup.CardId.Waitlist,
	        [this.isOverbooking]: booking_component_cyclePopup.CardId.Overbooking
	      }.true;
	    },
	    isUnconfirmed() {
	      if (!this.booking || this.isConfirmed) {
	        return false;
	      }
	      return this.booking.counter > 0;
	    },
	    isConfirmed() {
	      if (!this.booking) {
	        return false;
	      }
	      return this.booking.isConfirmed;
	    },
	    isLate() {
	      if (!this.booking) {
	        return false;
	      }
	      const started = this.nowTs > this.booking.dateFromTs;
	      const statusUnknown = this.booking.visitStatus === booking_const.VisitStatus.Unknown;
	      const statusNotVisited = this.booking.visitStatus === booking_const.VisitStatus.NotVisited;
	      return started && statusUnknown || statusNotVisited;
	    },
	    isWaitListItem() {
	      return Boolean(this.waitListItem);
	    },
	    isOverbooking() {
	      return this.$store.getters[`${booking_const.Model.Bookings}/overbookingMap`].has(this.bookingId);
	    }
	  },
	  mounted() {
	    this.interval = setInterval(() => {
	      this.nowTs = Date.now();
	    }, 5 * 1000);
	    void this.tryShowAhaMoment();
	  },
	  beforeUnmount() {
	    clearInterval(this.interval);
	  },
	  methods: {
	    showPopup() {
	      if (booking_lib_ahaMoments.ahaMoments.shouldShow(booking_const.AhaMoment.CyclePopup)) {
	        booking_lib_ahaMoments.ahaMoments.setShown(booking_const.AhaMoment.CyclePopup);
	      }
	      this.isPopupShown = true;
	      this.$emit('freeze');
	    },
	    hidePopup() {
	      this.isPopupShown = false;
	      this.$emit('unfreeze');
	    },
	    async tryShowAhaMoment() {
	      if (booking_lib_ahaMoments.ahaMoments.shouldShow(booking_const.AhaMoment.CyclePopup)) {
	        await booking_lib_ahaMoments.ahaMoments.show({
	          id: 'booking-cycle-popup',
	          ahaMoment: booking_const.AhaMoment.CyclePopup,
	          title: this.loc('BOOKING_ACTIONS_POPUP_INFO_AHA_TITLE'),
	          text: this.loc('BOOKING_ACTIONS_POPUP_INFO_AHA_TEXT'),
	          target: this.$refs.container,
	          top: true,
	          isPulsarTransparent: true
	        });
	        booking_lib_ahaMoments.ahaMoments.setShown(booking_const.AhaMoment.CyclePopup);
	      }
	    }
	  },
	  template: `
		<div class="booking-actions-popup-info" ref="container" @click="showPopup">
			<BIcon :name="Main.INFO_1"/>
		</div>
		<CyclePopup v-if="isPopupShown" :scrollToCard="scrollToCard" @close="hidePopup"/>
	`
	};

	const Message = {
	  name: 'ActionsPopupMessage',
	  emits: ['open', 'close', 'updateNotificationType'],
	  props: {
	    id: {
	      type: [Number, String],
	      required: true
	    },
	    /**
	     * @type ClientData
	     */
	    clientData: {
	      type: Object,
	      default: null
	    },
	    loading: {
	      type: Boolean,
	      default: false
	    },
	    disabled: {
	      type: Boolean,
	      default: false
	    },
	    dataId: {
	      type: [Number, String],
	      default: ''
	    },
	    dataElementPrefix: {
	      type: String,
	      default: ''
	    }
	  },
	  components: {
	    Button: booking_component_button.Button,
	    Icon: ui_iconSet_api_vue.BIcon,
	    Loader: booking_component_loader.Loader
	  },
	  setup() {
	    const iconSet = ui_iconSet_api_vue.Set;
	    const buttonSize = booking_component_button.ButtonSize;
	    const buttonColor = booking_component_button.ButtonColor;
	    const buttonIcon = booking_component_button.ButtonIcon;
	    return {
	      iconSet,
	      buttonSize,
	      buttonColor,
	      buttonIcon
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      dictionary: `${booking_const.Model.Dictionary}/getNotifications`,
	      isCurrentSenderAvailable: `${booking_const.Model.Interface}/isCurrentSenderAvailable`,
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`
	    }),
	    menuId() {
	      return `booking-message-menu-${this.id}`;
	    },
	    client() {
	      const clientData = this.clientData;
	      return clientData ? this.$store.getters['clients/getByClientData'](clientData) : null;
	    },
	    status() {
	      return this.$store.getters[`${booking_const.Model.MessageStatus}/getById`](this.id);
	    },
	    semantic() {
	      var _this$status;
	      return ((_this$status = this.status) == null ? void 0 : _this$status.semantic) || '';
	    },
	    iconColor() {
	      const colorMap = {
	        success: '#ffffff',
	        primary: '#ffffff',
	        failure: '#ffffff'
	      };
	      return colorMap[this.semantic] || '';
	    },
	    failure() {
	      return this.semantic === 'failure';
	    }
	  },
	  methods: {
	    openMenu() {
	      var _this$getMenu, _this$getMenu$getPopu;
	      if (!this.isFeatureEnabled) {
	        booking_lib_limit.limit.show();
	        return;
	      }
	      if (this.disabled || this.status.isDisabled && this.isCurrentSenderAvailable) {
	        return;
	      }
	      if ((_this$getMenu = this.getMenu()) != null && (_this$getMenu$getPopu = _this$getMenu.getPopupWindow()) != null && _this$getMenu$getPopu.isShown()) {
	        this.destroyMenu();
	        return;
	      }
	      const menuButton = this.$refs.button.$el;
	      main_popup.MenuManager.create(this.menuId, menuButton, this.getMenuItems(), {
	        autoHide: true,
	        offsetTop: 0,
	        offsetLeft: menuButton.offsetWidth - menuButton.offsetWidth / 2,
	        angle: true,
	        events: {
	          onClose: this.destroyMenu,
	          onDestroy: this.destroyMenu
	        }
	      }).show();
	      this.$emit('freeze');
	      main_core.Event.bind(document, 'scroll', this.adjustPosition, {
	        capture: true
	      });
	    },
	    getMenuItems() {
	      return Object.values(this.dictionary).map(({
	        name,
	        value
	      }) => ({
	        text: name,
	        onclick: () => this.sendMessage(value),
	        disabled: value === this.dictionary.Feedback.value
	      }));
	    },
	    sendMessage(notificationType) {
	      this.destroyMenu();
	      this.$emit('updateNotificationType', {
	        id: this.id,
	        notificationType
	      });
	    },
	    destroyMenu() {
	      main_popup.MenuManager.destroy(this.menuId);
	      this.$emit('close');
	      main_core.Event.unbind(document, 'scroll', this.adjustPosition, {
	        capture: true
	      });
	    },
	    adjustPosition() {
	      var _this$getMenu2, _this$getMenu2$getPop;
	      (_this$getMenu2 = this.getMenu()) == null ? void 0 : (_this$getMenu2$getPop = _this$getMenu2.getPopupWindow()) == null ? void 0 : _this$getMenu2$getPop.adjustPosition();
	    },
	    getMenu() {
	      return main_popup.MenuManager.getMenuById(this.menuId);
	    },
	    showHelpDesk() {
	      booking_lib_helpDesk.helpDesk.show(booking_const.HelpDesk.BookingActionsMessage.code, booking_const.HelpDesk.BookingActionsMessage.anchorCode);
	    }
	  },
	  template: `
		<div
			class="booking-actions-popup__item booking-actions-popup__item-message-content"
			:class="{'--disabled': disabled || !isCurrentSenderAvailable}"
		>
			<Loader v-if="loading" class="booking-actions-popup__item-message-loader"/>
			<template v-else>
				<div
					class="booking-actions-popup-item-icon"
					:class="'--' + semantic || 'none'"
				>
					<Icon
						:name="iconSet.SMS"
						:color="iconColor"
					/>
				</div>
				<div class="booking-actions-popup-item-info">
					<div class="booking-actions-popup-item-title">
						<span :title="status?.title">{{ status?.title || '' }}</span>
						<Icon :name="iconSet.HELP" @click="showHelpDesk"/>
					</div>
					<div
						class="booking-actions-popup-item-subtitle"
						:class="'--' + semantic || 'none'"
					>
						{{ status?.description || '' }}
					</div>
				</div>
				<div class="booking-actions-popup-item-buttons">
					<Button
						:data-element="dataElementPrefix + '-menu-message-button'"
						:data-booking-id="dataId"
						:disabled="disabled || (status?.isDisabled && isCurrentSenderAvailable)"
						class="booking-actions-popup-button-with-chevron"
						:class="{
							'--lock': !isFeatureEnabled,
							'--disabled': disabled || (status?.isDisabled && isCurrentSenderAvailable)
						}"
						buttonClass="ui-btn-shadow"
						:text="loc('BB_ACTIONS_POPUP_MESSAGE_BUTTON_SEND')"
						:size="buttonSize.EXTRA_SMALL"
						:color="buttonColor.LIGHT"
						:round="true"
						ref="button"
						@click="openMenu"
					>
						<Icon v-if="isFeatureEnabled" :name="iconSet.CHEVRON_DOWN"/>
						<Icon v-else :name="iconSet.LOCK"/>
					</Button>
					<div
						v-if="failure"
						class="booking-actions-popup-item-buttons-counter"
					></div>
				</div>
			</template>
			<div
				v-if="!isCurrentSenderAvailable"
				class="booking-booking-actions-popup-label"
			>
				{{ loc('BB_ACTIONS_POPUP_LABEL_SOON') }}
			</div>
		</div>
	`
	};

	// @vue/component
	const RemoveButton = {
	  name: 'RemoveButton',
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    showLabel: {
	      type: Boolean,
	      default: false
	    },
	    dataAttributes: {
	      type: Object,
	      default: () => ({})
	    }
	  },
	  emits: ['remove'],
	  setup() {
	    const iconSet = ui_iconSet_api_vue.Set;
	    return {
	      iconSet
	    };
	  },
	  template: `
		<div
			class="booking-actions-popup__item-remove-button"
			:title="loc('BB_ACTIONS_POPUP_OVERBOOKING_REMOVE')"
			v-bind="dataAttributes"
			@click="$emit('remove')"
		>
			<Icon :name="iconSet.TRASH_BIN"/>
			<div v-if="showLabel" class="booking-actions-popup__item-overbooking-label">
				{{ loc('BB_ACTIONS_POPUP_OVERBOOKING_REMOVE') }}
			</div>
		</div>
	`
	};

	const VisitMenu = {
	  name: 'VisitMenu',
	  emits: ['popupShown', 'popupClosed', 'update:status'],
	  props: {
	    id: {
	      type: Number,
	      required: true
	    },
	    disabled: {
	      type: Boolean,
	      default: false
	    },
	    dataId: {
	      type: [Number, String],
	      required: true
	    },
	    dataElementPrefix: {
	      type: String,
	      default: ''
	    }
	  },
	  data() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set,
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonIcon: booking_component_button.ButtonIcon,
	      menuPopup: null
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      dictionary: `${booking_const.Model.Dictionary}/getBookingVisitStatuses`,
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`
	    }),
	    popupId() {
	      return `booking-visit-menu-${this.id}`;
	    }
	  },
	  unmounted() {
	    if (this.menuPopup) {
	      this.destroy();
	    }
	  },
	  methods: {
	    updateVisitStatus(status) {
	      this.$emit('update:status', {
	        id: this.id,
	        visitStatus: status
	      });
	    },
	    openMenu() {
	      var _this$menuPopup, _this$menuPopup$popup;
	      if (this.disabled) {
	        return;
	      }
	      if (!this.isFeatureEnabled) {
	        booking_lib_limit.limit.show();
	        return;
	      }
	      if ((_this$menuPopup = this.menuPopup) != null && (_this$menuPopup$popup = _this$menuPopup.popupWindow) != null && _this$menuPopup$popup.isShown()) {
	        this.destroy();
	        return;
	      }
	      const menuButton = this.$refs.button.$el;
	      this.menuPopup = main_popup.MenuManager.create(this.popupId, menuButton, this.getMenuItems(), {
	        autoHide: true,
	        offsetTop: 0,
	        offsetLeft: menuButton.offsetWidth - menuButton.offsetWidth / 2,
	        angle: true,
	        events: {
	          onClose: () => this.destroy(),
	          onDestroy: () => this.unbindScrollEvent()
	        }
	      });
	      this.menuPopup.show();
	      this.bindScrollEvent();
	      this.$emit('popupShown');
	    },
	    getMenuItems() {
	      return [{
	        text: this.loc('BB_ACTIONS_POPUP_VISIT_BTN_LABEL_UNKNOWN'),
	        onclick: () => this.setVisitStatus(this.dictionary.Unknown)
	      }, {
	        text: this.loc('BB_ACTIONS_POPUP_VISIT_BTN_LABEL_VISITED'),
	        onclick: () => this.setVisitStatus(this.dictionary.Visited)
	      }, {
	        text: this.loc('BB_ACTIONS_POPUP_VISIT_BTN_LABEL_NOT_VISITED'),
	        onclick: () => this.setVisitStatus(this.dictionary.NotVisited)
	      }];
	    },
	    setVisitStatus(status) {
	      this.updateVisitStatus(status);
	      this.destroy();
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
	    Icon: ui_iconSet_api_vue.BIcon,
	    Button: booking_component_button.Button
	  },
	  template: `
		<Button
			:data-element="dataElementPrefix + '-menu-visit-button'"
			:data-booking-id="dataId"
			:disabled="disabled || !isFeatureEnabled"
			class="booking-actions-popup-button-with-chevron"
			:class="{'--lock': !isFeatureEnabled || disabled}"
			buttonClass="ui-btn-shadow"
			:text="loc('BB_ACTIONS_POPUP_VISIT_BTN_LABEL')"
			:size="ButtonSize.EXTRA_SMALL"
			:color="ButtonColor.LIGHT"
			:round="true"
			ref="button"
			@click="openMenu"
		>
			<Icon v-if="isFeatureEnabled" :name="IconSet.CHEVRON_DOWN"/>
			<Icon v-else :name="IconSet.LOCK"/>
		</Button>
	`
	};

	const Visit = {
	  name: 'ActionsPopupVisit',
	  emits: ['freeze', 'unfreeze', 'update:visitStatus'],
	  props: {
	    id: {
	      type: [Number, String],
	      required: true
	    },
	    visitStatus: {
	      type: String,
	      required: true
	    },
	    hasClients: {
	      type: Boolean,
	      default: false
	    },
	    disabled: {
	      type: Boolean,
	      default: false
	    },
	    dataId: {
	      type: [Number, String],
	      required: true
	    },
	    dataElementPrefix: {
	      type: String,
	      default: ''
	    }
	  },
	  setup() {
	    const iconSet = ui_iconSet_api_vue.Set;
	    const isLoading = ui_vue3.ref(true);
	    return {
	      iconSet,
	      isLoading
	    };
	  },
	  async mounted() {
	    this.isLoading = false;
	  },
	  methods: {
	    showHelpDesk() {
	      booking_lib_helpDesk.helpDesk.show(booking_const.HelpDesk.BookingActionsVisit.code, booking_const.HelpDesk.BookingActionsVisit.anchorCode);
	    }
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      dictionary: `${booking_const.Model.Dictionary}/getBookingVisitStatuses`
	    }),
	    getLocVisitStatus() {
	      switch (this.visitStatus) {
	        case this.dictionary.Visited:
	          return this.loc('BB_ACTIONS_POPUP_VISIT_BTN_LABEL_VISITED');
	        case this.dictionary.NotVisited:
	          return this.loc('BB_ACTIONS_POPUP_VISIT_BTN_LABEL_NOT_VISITED');
	        default:
	          return this.hasClients ? this.loc('BB_ACTIONS_POPUP_VISIT_BTN_LABEL_UNKNOWN') : this.loc('BB_ACTIONS_POPUP_VISIT_ADD_LABEL');
	      }
	    },
	    getVisitInfoStyles() {
	      switch (this.visitStatus) {
	        case this.dictionary.Visited:
	          return '--visited';
	        case this.dictionary.NotVisited:
	          return '--not-visited';
	        default:
	          return '--unknown';
	      }
	    },
	    cardIconColor() {
	      switch (this.visitStatus) {
	        case this.dictionary.NotVisited:
	        case this.dictionary.Visited:
	          return 'var(--ui-color-palette-white-base)';
	        default:
	          return 'var(--ui-color-palette-gray-20)';
	      }
	    },
	    iconClass() {
	      switch (this.visitStatus) {
	        case this.dictionary.Visited:
	          return '--visited';
	        case this.dictionary.NotVisited:
	          return '--not-visited';
	        default:
	          return '';
	      }
	    }
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon,
	    Loader: booking_component_loader.Loader,
	    VisitMenu
	  },
	  template: `
		<div
			class="booking-actions-popup__item booking-actions-popup__item-visit-content"
			:class="{'--disabled': disabled}"
		>
			<Loader v-if="isLoading" class="booking-actions-popup__item-visit-loader"/>
			<template v-else>
				<div :class="['booking-actions-popup-item-icon', iconClass]">
					<Icon :name="iconSet.CUSTOMER_CARD" :color="cardIconColor"/>
				</div>
				<div class="booking-actions-popup-item-info">
					<div class="booking-actions-popup-item-title">
						<span>{{ loc('BB_ACTIONS_POPUP_VISIT_LABEL') }}</span>
						<Icon :name="iconSet.HELP" @click="showHelpDesk"/>
					</div>
					<div
						:class="['booking-actions-popup-item-subtitle', getVisitInfoStyles]"
						:data-element="dataElementPrefix + '-menu-visit-status'"
						:data-booking-id="id"
						:data-visit-status="visitStatus"
					>
						{{ getLocVisitStatus }}
					</div>
				</div>
				<div class="booking-actions-popup-item-buttons">
					<VisitMenu
						:id
						:dataId
						:dataElementPrefix
						:disabled
						@popupShown="$emit('freeze')"
						@popupClosed="$emit('unfreeze')"
						@update:status="$emit('update:visitStatus', $event)"
					/>
				</div>
			</template>
		</div>
	`
	};

	exports.ActionsPopup = ActionsPopup;
	exports.Client = Client;
	exports.Confirmation = Confirmation;
	exports.Deal = Deal;
	exports.Document = Document;
	exports.ExtraResourcesInfo = ExtraResourcesInfo;
	exports.FullForm = FullForm;
	exports.Info = Info;
	exports.Message = Message;
	exports.RemoveButton = RemoveButton;
	exports.Visit = Visit;

}((this.BX.Booking.Component = this.BX.Booking.Component || {}),BX.Booking.Component,BX.SidePanel,BX.Vue3.Directives,BX.Booking.Component,BX.Booking.Component,BX.Main,BX.Booking.Lib,BX.Booking.Provider.Service,BX.Booking.Provider.Service,BX.UI.EntitySelector,BX.Booking.Provider.Service,BX.Booking.Component,BX.UI,BX.Vue3.Directives,BX.UI.IconSet,BX.Booking.Component,BX.Booking.Lib,BX.Vue3,BX,BX.Booking.Lib,BX.Booking.Component,BX,BX.Main,BX.Vue3.Vuex,BX.UI.IconSet,BX.Booking.Const,BX.Booking.Lib,BX.Booking.Component));
//# sourceMappingURL=actions-popup.bundle.js.map
