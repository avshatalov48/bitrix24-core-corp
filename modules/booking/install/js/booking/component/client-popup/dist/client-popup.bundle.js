/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,booking_component_popup,ui_notificationManager,booking_provider_service_clientService,booking_component_button,main_core_events,main_popup,ui_iconSet_api_vue,ui_iconSet_main,ui_iconSet_actions,ui_iconSet_crm,ui_dropdown,main_core,ui_vue3,booking_const,phone_number,crm_entityEditor_field_phoneNumberInput) {
	'use strict';

	const InputField = {
	  props: {
	    name: {
	      type: String,
	      required: true
	    },
	    dataElement: {
	      type: String,
	      required: true
	    }
	  },
	  template: `
		<div
			class="booking-booking-client-popup-field"
			:data-element="dataElement"
		>
			<div class="booking-booking-client-popup-field-name">
				{{ name }}
			</div>
			<div class="booking-booking-client-popup-field-input-container">
				<slot></slot>
			</div>
		</div>
	`
	};

	function getEmptyClient(item, code) {
	  let name = '';
	  const phones = [];
	  if (BX.validation.checkIfPhone(item.title)) {
	    phones.push(item.title);
	  } else {
	    name = item.title;
	  }
	  return {
	    name,
	    type: {
	      module: booking_const.Module.Crm,
	      code
	    },
	    phones,
	    emails: []
	  };
	}
	function clientToItem(client) {
	  return {
	    id: client.id,
	    module: client.type.module,
	    type: client.type.code,
	    title: client.name,
	    attributes: {
	      phone: client.phones.map(value => ({
	        value
	      })),
	      email: client.emails.map(value => ({
	        value
	      }))
	    }
	  };
	}
	function itemToClient(item) {
	  var _item$attributes$phon, _item$attributes, _item$attributes$phon2, _item$attributes$emai, _item$attributes2, _item$attributes2$ema;
	  return {
	    id: item.id,
	    name: item.title,
	    type: {
	      module: item.module,
	      code: item.type
	    },
	    phones: (_item$attributes$phon = (_item$attributes = item.attributes) == null ? void 0 : (_item$attributes$phon2 = _item$attributes.phone) == null ? void 0 : _item$attributes$phon2.map(({
	      value
	    }) => value)) != null ? _item$attributes$phon : [],
	    emails: (_item$attributes$emai = (_item$attributes2 = item.attributes) == null ? void 0 : (_item$attributes2$ema = _item$attributes2.email) == null ? void 0 : _item$attributes2$ema.map(({
	      value
	    }) => value)) != null ? _item$attributes$emai : []
	  };
	}
	function deepToRawClientModel(clientModel) {
	  const modelIterator = data => {
	    if (Array.isArray(data)) {
	      return data.map(item => modelIterator(item));
	    }
	    if (ui_vue3.isRef(data) || ui_vue3.isReactive(data) || ui_vue3.isProxy(data)) {
	      return modelIterator(ui_vue3.toRaw(data));
	    }
	    if (main_core.Type.isObject(data)) {
	      return Object.keys(data).reduce((acc, key) => {
	        acc[key] = modelIterator(data[key]);
	        return acc;
	      }, {});
	    }
	    return data;
	  };
	  return modelIterator(clientModel);
	}

	const ClientInput = {
	  emits: ['setClient'],
	  props: {
	    client: {
	      type: Object,
	      default: null
	    },
	    code: {
	      type: String,
	      required: true
	    },
	    isWarning: {
	      type: Boolean,
	      default: false
	    }
	  },
	  mounted() {
	    this.dropdown = new BX.UI.Dropdown({
	      targetElement: this.$refs.clientInput,
	      searchAction: 'crm.api.entity.search',
	      searchOptions: {
	        types: [this.code],
	        scope: 'index'
	      },
	      enableCreation: true,
	      autocompleteDelay: 200,
	      items: this.clientsItems,
	      messages: {
	        creationLegend: this.creationLegend
	      },
	      events: {
	        onSelect: (dropdown, item) => this.setClient(this.itemToClient(item)),
	        onAdd: (dropdown, item) => this.setClient(this.itemToClient(item))
	      }
	    });
	    this.updateValue();
	    this.onInput();
	    main_core_events.EventEmitter.subscribe(this.dropdown, 'BX.UI.Dropdown:onSearchComplete', this.onDropdownContactsLoaded);
	  },
	  beforeUnmount() {
	    this.dropdown.destroyPopupWindow();
	    main_core_events.EventEmitter.unsubscribe(this.dropdown, 'BX.UI.Dropdown:onSearchComplete', this.onDropdownContactsLoaded);
	  },
	  computed: {
	    clientId() {
	      var _this$client;
	      return (_this$client = this.client) == null ? void 0 : _this$client.id;
	    },
	    clientsItems() {
	      switch (this.code) {
	        case booking_const.CrmEntity.Contact:
	          return this.$store.getters[`${booking_const.Model.Clients}/getContacts`].map(it => clientToItem(it));
	        case booking_const.CrmEntity.Company:
	          return this.$store.getters[`${booking_const.Model.Clients}/getCompanies`].map(it => clientToItem(it));
	        default:
	          return [];
	      }
	    },
	    fieldName() {
	      switch (this.code) {
	        case booking_const.CrmEntity.Contact:
	          return this.loc('BOOKING_BOOKING_ADD_CLIENT_FIELD_CONTACT');
	        case booking_const.CrmEntity.Company:
	          return this.loc('BOOKING_BOOKING_ADD_CLIENT_FIELD_COMPANY');
	        default:
	          return '';
	      }
	    },
	    inputPlaceholder() {
	      switch (this.code) {
	        case booking_const.CrmEntity.Contact:
	          return this.loc('BOOKING_BOOKING_ADD_CLIENT_CONTACT_PLACEHOLDER');
	        case booking_const.CrmEntity.Company:
	          return this.loc('BOOKING_BOOKING_ADD_CLIENT_COMPANY_PLACEHOLDER');
	        default:
	          return '';
	      }
	    },
	    creationLegend() {
	      switch (this.code) {
	        case booking_const.CrmEntity.Contact:
	          return this.loc('BOOKING_BOOKING_ADD_CLIENT_DROPDOWN_CREATE_NEW_CONTACT');
	        case booking_const.CrmEntity.Company:
	          return this.loc('BOOKING_BOOKING_ADD_CLIENT_DROPDOWN_CREATE_NEW_COMPANY');
	        default:
	          return '';
	      }
	    },
	    inputLabel() {
	      if (this.isWarning) {
	        return this.loc('BOOKING_BOOKING_ADD_CLIENT_CLIENT_REQUIRED');
	      }
	      if (this.isNew) {
	        switch (this.code) {
	          case booking_const.CrmEntity.Contact:
	            return this.loc('BOOKING_BOOKING_ADD_CLIENT_CONTACT_NEW');
	          case booking_const.CrmEntity.Company:
	            return this.loc('BOOKING_BOOKING_ADD_CLIENT_COMPANY_NEW');
	          default:
	            return '';
	        }
	      }
	      switch (this.code) {
	        case booking_const.CrmEntity.Contact:
	          return this.loc('BOOKING_BOOKING_ADD_CLIENT_CLIENT_EDIT');
	        case booking_const.CrmEntity.Company:
	          return this.loc('BOOKING_BOOKING_ADD_CLIENT_CLIENT_EDIT');
	        default:
	          return '';
	      }
	    },
	    isNew() {
	      return this.hasClient && !this.isEditing;
	    },
	    isEditing() {
	      var _this$client2;
	      return this.hasClient && Boolean((_this$client2 = this.client) == null ? void 0 : _this$client2.id);
	    },
	    hasClient() {
	      return Boolean(this.client);
	    },
	    clearHint() {
	      switch (this.code) {
	        case booking_const.CrmEntity.Contact:
	          return this.loc('BOOKING_BOOKING_ADD_CLIENT_CHOOSE_ANOTHER_CONTACT');
	        case booking_const.CrmEntity.Company:
	          return this.loc('BOOKING_BOOKING_ADD_CLIENT_CHOOSE_ANOTHER_COMPANY');
	        default:
	          return '';
	      }
	    },
	    leftIcon() {
	      switch (this.code) {
	        case booking_const.CrmEntity.Contact:
	          return ui_iconSet_api_vue.Set.PERSON;
	        case booking_const.CrmEntity.Company:
	          return ui_iconSet_api_vue.Set.COMPANY;
	        default:
	          return '';
	      }
	    },
	    searchIcon() {
	      return ui_iconSet_api_vue.Set.SEARCH_2;
	    },
	    arrowsIcon() {
	      return ui_iconSet_api_vue.Set.SWAP;
	    }
	  },
	  methods: {
	    onInput() {
	      if (this.client) {
	        this.setClient({
	          ...this.client,
	          name: this.getValue()
	        });
	      }
	    },
	    getValue() {
	      return this.$refs.clientInput.value;
	    },
	    getPopup() {
	      return this.dropdown.popupWindow;
	    },
	    onDropdownContactsLoaded(event) {
	      const [, results] = event.getData();
	      this.$store.dispatch(`${booking_const.Model.Clients}/upsertMany`, results.map(it => this.itemToClient(it)));
	    },
	    getClient(item) {
	      const client = this.$store.getters[`${booking_const.Model.Clients}/getByClientData`]({
	        id: item.id,
	        type: {
	          module: item.module,
	          code: item.type
	        }
	      });
	      return client != null ? client : this.itemToClient(item);
	    },
	    itemToClient(item) {
	      if (item.id) {
	        return itemToClient(item);
	      }
	      return this.getEmptyClient(item);
	    },
	    getEmptyClient(item) {
	      return getEmptyClient(item, this.code);
	    },
	    clear() {
	      this.setClient(null);
	    },
	    setClient(client) {
	      this.$emit('setClient', client);
	    },
	    updateValue() {
	      if (this.client) {
	        this.$refs.clientInput.value = this.client.name;
	        this.dropdown.isDisabled = true;
	        this.dropdown.destroyPopupWindow();
	        this.dropdown.popupAlertContainer = null;
	      } else {
	        this.$refs.clientInput.value = '';
	        this.dropdown.isDisabled = false;
	      }
	    }
	  },
	  watch: {
	    client() {
	      this.updateValue();
	    },
	    clientId() {
	      this.onInput();
	    },
	    clientsItems(clientsItems) {
	      this.dropdown.setDefaultItems(clientsItems);
	    }
	  },
	  components: {
	    InputField,
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  template: `
		<InputField
			:name="fieldName"
			:data-element="'booking-client-field-' + code"
		>
			<input
				class="booking-booking-client-popup-field-input --left-icon --right-icon"
				:class="{'--warning': isWarning}"
				:placeholder="inputPlaceholder"
				data-element="booking-client-input"
				:data-id="client?.id || 0"
				:data-code="code"
				:data-new="isNew"
				:data-editing="isEditing"
				ref="clientInput"
				@input="onInput"
			/>
			<div class="booking-booking-client-popup-field-input-icon">
				<div class="booking-booking-client-popup-field-input-avatar-icon">
					<Icon :name="leftIcon"/>
				</div>
			</div>
			<div
				v-if="hasClient"
				class="booking-booking-client-popup-field-input-label"
				:class="{'--warning': isWarning}"
				data-element="booking-client-input-label"
			>
				{{ inputLabel }}
			</div>
			<div
				v-else
				class="booking-booking-client-popup-field-input-icon-right"
				data-element="booking-client-search-icon"
			>
				<Icon :name="searchIcon"/>
			</div>
			<div
				v-if="hasClient"
				class="booking-booking-client-popup-field-input-icon-right --clickable"
				:title="clearHint"
				data-element="booking-client-clear-icon"
				@click="clear"
			>
				<Icon :name="arrowsIcon"/>
			</div>
		</InputField>
	`
	};

	const PhoneInput = {
	  emits: ['update:modelValue'],
	  props: {
	    modelValue: String,
	    clientId: {
	      type: Number,
	      required: true
	    },
	    code: {
	      type: String,
	      required: true
	    }
	  },
	  async mounted() {
	    new crm_entityEditor_field_phoneNumberInput.PhoneNumberInput({
	      node: this.$refs.input,
	      flagNode: this.$refs.flag
	    });
	  },
	  components: {
	    InputField
	  },
	  template: `
		<InputField
			:name="loc('BOOKING_BOOKING_ADD_CLIENT_FIELD_PHONE')"
			:data-element="'booking-client-field-phone'"
		>
			<input
				class="booking-booking-client-popup-field-input --left-icon"
				:placeholder="loc('BOOKING_BOOKING_ADD_CLIENT_FIELD_PLACEHOLDER')"
				:value="modelValue"
				data-element="booking-client-phone-input"
				:data-id="clientId"
				:data-code="code"
				ref="input"
				@input="$emit('update:modelValue', $refs.input.value)"
			/>
			<div class="booking-booking-client-popup-field-input-icon --no-border" ref="flag"></div>
		</InputField>
	`
	};

	const EmailInput = {
	  emits: ['update:modelValue'],
	  props: {
	    modelValue: String,
	    clientId: {
	      type: Number,
	      required: true
	    },
	    code: {
	      type: String,
	      required: true
	    }
	  },
	  components: {
	    InputField
	  },
	  template: `
		<InputField
			:name="loc('BOOKING_BOOKING_ADD_CLIENT_FIELD_EMAIL')"
			:data-element="'booking-client-field-email'"
		>
			<input
				class="booking-booking-client-popup-field-input"
				:placeholder="loc('BOOKING_BOOKING_ADD_CLIENT_FIELD_PLACEHOLDER')"
				:value="modelValue"
				data-element="booking-client-email-input"
				:data-id="clientId"
				:data-code="code"
				ref="input"
				@input="$emit('update:modelValue', $refs.input.value)"
			/>
		</InputField>
	`
	};

	const ClientPopupContent = {
	  name: 'ClientPopupContent',
	  emits: ['create', 'close'],
	  props: {
	    adjustPosition: {
	      type: Function,
	      required: true
	    },
	    currentClient: {
	      type: Object,
	      default: null
	    }
	  },
	  data() {
	    return {
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      CrmEntity: booking_const.CrmEntity,
	      contact: null,
	      company: null,
	      isSaving: false
	    };
	  },
	  computed: {
	    hasClient() {
	      return this.hasContact || this.hasCompany;
	    },
	    hasContact() {
	      return Boolean(this.contact);
	    },
	    hasCompany() {
	      return Boolean(this.company);
	    },
	    cannotSave() {
	      return this.clients.length > 0 && this.filledClients.length === 0;
	    },
	    filledClients() {
	      return this.clients.filter(client => {
	        var _client$name;
	        return (_client$name = client.name) == null ? void 0 : _client$name.trim();
	      });
	    },
	    clients() {
	      const clients = [];
	      if (this.contact) {
	        var _this$$refs$contactIn, _this$$refs$contactIn2;
	        clients.push({
	          ...this.contact,
	          name: (_this$$refs$contactIn = (_this$$refs$contactIn2 = this.$refs.contactInput) == null ? void 0 : _this$$refs$contactIn2.getValue()) != null ? _this$$refs$contactIn : ''
	        });
	      }
	      if (this.company) {
	        var _this$$refs$companyIn, _this$$refs$companyIn2;
	        clients.push({
	          ...this.company,
	          name: (_this$$refs$companyIn = (_this$$refs$companyIn2 = this.$refs.companyInput) == null ? void 0 : _this$$refs$companyIn2.getValue()) != null ? _this$$refs$companyIn : ''
	        });
	      }
	      return clients;
	    }
	  },
	  beforeMount() {
	    const currentClient = this.currentClient;
	    if (!currentClient) {
	      return;
	    }
	    if (currentClient.contact) {
	      this.setContact(deepToRawClientModel(currentClient.contact));
	    }
	    if (currentClient.company) {
	      this.company = deepToRawClientModel(currentClient.company);
	    }
	  },
	  methods: {
	    setContact(contact) {
	      this.contact = contact;
	    },
	    async setCompany(company) {
	      var _this$company;
	      const previousCompanyId = (_this$company = this.company) == null ? void 0 : _this$company.id;
	      const newCompanyId = company == null ? void 0 : company.id;
	      this.company = company;
	      if (!newCompanyId || previousCompanyId === newCompanyId) {
	        return;
	      }
	      const linkedContact = await booking_provider_service_clientService.clientService.getLinkedContactByCompany(this.company);
	      if (linkedContact) {
	        this.contact = linkedContact;
	      }
	    },
	    async saveClients() {
	      this.isSaving = true;
	      const {
	        clients,
	        error
	      } = await booking_provider_service_clientService.clientService.saveMany(this.filledClients);
	      this.isSaving = false;
	      if (error) {
	        ui_notificationManager.Notifier.notify({
	          id: 'booking-client-popup-save-error',
	          text: error.message
	        });
	        return;
	      }
	      this.$emit('create', clients);
	      this.closePopup();
	    },
	    getClientsPopup() {
	      const contactsPopup = this.$refs.contactInput.getPopup();
	      const companiesPopup = this.$refs.companyInput.getPopup();
	      if (contactsPopup != null && contactsPopup.isShown()) {
	        return contactsPopup;
	      }
	      if (companiesPopup != null && companiesPopup.isShown()) {
	        return companiesPopup;
	      }
	      return null;
	    },
	    closePopup() {
	      this.$emit('close');
	    }
	  },
	  watch: {
	    isNew() {
	      void this.$nextTick(() => this.adjustPosition());
	    }
	  },
	  components: {
	    Button: booking_component_button.Button,
	    ClientInput,
	    PhoneInput,
	    EmailInput
	  },
	  template: `
		<div class="booking-booking-client-popup-header">
			<div class="booking-booking-client-popup-header-text">
				{{ loc('BOOKING_BOOKING_ADD_CLIENT_POPUP_HEADER') }}
			</div>
			<div
				class="ui-icon-set --cross-45"
				data-element="booking-client-popup-close"
				@click="closePopup"
			></div>
		</div>
		<div class="booking-booking-client-popup-contact">
			<ClientInput
				:code="CrmEntity.Contact"
				:client="contact"
				:isWarning="contact && cannotSave"
				ref="contactInput"
				@setClient="setContact"
			/>
			<template v-if="hasContact">
				<PhoneInput v-model="contact.phones[0]" :clientId="contact.id || 0" :code="CrmEntity.Contact"/>
				<EmailInput v-model="contact.emails[0]" :clientId="contact.id || 0" :code="CrmEntity.Contact"/>
			</template>
			<ClientInput
				:code="CrmEntity.Company"
				:client="company"
				:isWarning="company && cannotSave"
				ref="companyInput"
				@setClient="setCompany"
			/>
			<template v-if="hasCompany">
				<PhoneInput v-model="company.phones[0]" :clientId="company.id || 0" :code="CrmEntity.Company"/>
				<EmailInput v-model="company.emails[0]" :clientId="company.id || 0" :code="CrmEntity.Company"/>
			</template>
		</div>
		<div v-if="hasClient" class="booking-booking-client-popup-buttons">
			<Button
				:dataset="{element: 'booking-client-popup-save'}"
				:text="loc('BOOKING_BOOKING_ADD_CLIENT_SAVE')"
				:size="ButtonSize.EXTRA_SMALL"
				:color="ButtonColor.PRIMARY"
				:round="true"
				:disabled="cannotSave"
				:waiting="isSaving"
				@click="saveClients"
			/>
			<Button
				:dataset="{element: 'booking-client-popup-cancel'}"
				:text="loc('BOOKING_BOOKING_ADD_CLIENT_CANCEL')"
				:size="ButtonSize.EXTRA_SMALL"
				:color="ButtonColor.LINK"
				:round="true"
				@click="closePopup"
			/>
		</div>
		<div v-else class="booking-booking-client-popup-hint">
			{{ loc('BOOKING_BOOKING_ADD_CLIENT_POPUP_HINT') }}
		</div>
	`
	};

	const ClientPopup = {
	  name: 'ClientPopup',
	  emits: ['create', 'close'],
	  props: {
	    bindElement: {
	      type: HTMLElement,
	      required: true
	    },
	    currentClient: {
	      type: Object,
	      default: null
	    },
	    offsetTop: {
	      type: Number,
	      default: null
	    },
	    offsetLeft: {
	      type: Number,
	      default: null
	    }
	  },
	  computed: {
	    popupId() {
	      return 'booking-booking-client-popup';
	    },
	    config() {
	      var _this$offsetLeft, _this$offsetTop;
	      return {
	        bindElement: this.bindElement,
	        width: 305,
	        offsetLeft: (_this$offsetLeft = this.offsetLeft) != null ? _this$offsetLeft : 0 - this.bindElement.offsetWidth,
	        offsetTop: (_this$offsetTop = this.offsetTop) != null ? _this$offsetTop : this.bindElement.offsetHeight,
	        autoHideHandler: ({
	          target
	        }) => {
	          var _content$getClientsPo, _content$getClientsPo2;
	          const content = this.$refs.content;
	          const isClickInside = this.$refs.popup.contains(target);
	          const isDropdownClick = (_content$getClientsPo = content.getClientsPopup()) == null ? void 0 : (_content$getClientsPo2 = _content$getClientsPo.getPopupContainer()) == null ? void 0 : _content$getClientsPo2.contains(target);
	          const isFillingTheContact = content.hasClient;
	          return !isDropdownClick && !isClickInside && !isFillingTheContact;
	        }
	      };
	    }
	  },
	  mounted() {
	    this.onAdjustPosition();
	  },
	  methods: {
	    onAdjustPosition() {
	      var _this$$refs$content$g;
	      (_this$$refs$content$g = this.$refs.content.getClientsPopup()) == null ? void 0 : _this$$refs$content$g.adjustPosition();
	    },
	    closePopup() {
	      this.$emit('close');
	    }
	  },
	  components: {
	    StickyPopup: booking_component_popup.StickyPopup,
	    ClientPopupContent
	  },
	  template: `
		<StickyPopup
			v-slot="{adjustPosition}"
			:id="popupId"
			:config="config"
			ref="popup"
			@close="closePopup"
			@adjustPosition="onAdjustPosition"
		>
			<ClientPopupContent
				:adjust-position
				:current-client
				ref="content"
				@create="$emit('create', $event)"
				@close="closePopup"
			/>
		</StickyPopup>
	`
	};

	exports.ClientPopup = ClientPopup;

}((this.BX.Booking.Component = this.BX.Booking.Component || {}),BX.Booking.Component,BX.UI.NotificationManager,BX.Booking.Provider.Service,BX.Booking.Component,BX.Event,BX.Main,BX.UI.IconSet,BX,BX,BX,BX,BX,BX.Vue3,BX.Booking.Const,BX,BX.Crm));
//# sourceMappingURL=client-popup.bundle.js.map
