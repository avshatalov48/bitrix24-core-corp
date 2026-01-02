/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,booking_component_notePopup,ui_vue3_vuex,booking_component_clientPopup,ui_vue3_directives_hint,ui_iconSet_main,ui_iconSet_api_vue,ui_iconSet_crm,booking_const,booking_lib_limit,booking_lib_dealHelper,main_core,main_popup,booking_component_popup) {
	'use strict';

	// @vue/component
	const BookingBase = {
	  name: 'BookingBase',
	  emits: ['click'],
	  props: {
	    bookingStyle: {
	      type: [String, Object, Array],
	      default: ''
	    },
	    bookingClass: {
	      type: [String, Object, Array],
	      default: ''
	    },
	    disabled: {
	      type: Boolean,
	      default: false
	    },
	    visible: {
	      type: Boolean,
	      default: true
	    },
	    dataAttributes: {
	      type: Object,
	      default: () => ({})
	    }
	  },
	  methods: {
	    onClick(event) {
	      if (this.disabled) {
	        this.$emit('click');
	        event.stopPropagation();
	      }
	    }
	  },
	  template: `
		<div
			class="booking--booking-base"
			:style="bookingStyle"
			:class="bookingClass"
			v-bind="dataAttributes"
			@click.capture="onClick"
		>
			<div v-if="visible" class="booking--booking-base-padding booking-booking-booking-padding">
				<div class="booking--booking-base-inner booking-booking-booking-inner">
					<div class="booking--booking-base-content">
						<div class="booking--booking-base-content-row">
							<slot name="upper-content-row"/>
						</div>
						<div class="booking--booking-base-content-row --lower">
							<slot name="lower-content-row"/>
						</div>
					</div>
					<slot name="actions"></slot>
				</div>
			</div>
			<slot name="end"/>
		</div>
	`
	};

	// @vue/component
	const Name = {
	  name: 'Name',
	  props: {
	    name: {
	      type: String,
	      default: ''
	    },
	    className: {
	      type: [String, Object, Array],
	      default: ''
	    },
	    dataAttributes: {
	      type: Object,
	      default: () => ({})
	    }
	  },
	  template: `
		<div
			class="booking--booking-base-name"
			:title="name"
			:class="className"
			v-bind="$props.dataAttributes"
		>
			{{ name }}
		</div>
	`
	};

	// @vue/component
	const Note = {
	  name: 'BaseNote',
	  components: {
	    NotePopup: booking_component_notePopup.NotePopup
	  },
	  expose: ['showViewPopup', 'closeViewPopup'],
	  props: {
	    id: {
	      type: [Number, String],
	      required: true
	    },
	    bindElement: {
	      type: Function,
	      required: true
	    },
	    note: {
	      type: String,
	      default: ''
	    },
	    dataId: {
	      type: [String, Number],
	      default: ''
	    },
	    dataElementPrefix: {
	      type: String,
	      default: ''
	    },
	    className: {
	      type: [String, Object, Array],
	      default: ''
	    },
	    dataAttributes: {
	      type: Object,
	      default: null
	    }
	  },
	  emits: ['save'],
	  data() {
	    return {
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
	    showViewPopup() {
	      if (this.isPopupShown || !this.hasNote) {
	        return;
	      }
	      this.isEditMode = false;
	      this.isPopupShown = true;
	    },
	    closeViewPopup() {
	      if (this.isEditMode) {
	        return;
	      }
	      this.isPopupShown = false;
	    },
	    showEditPopup() {
	      this.isEditMode = true;
	      this.isPopupShown = true;
	    },
	    closeEditPopup() {
	      if (!this.isEditMode) {
	        return;
	      }
	      this.isPopupShown = false;
	    }
	  },
	  template: `
		<div class="booking--booking-base-note">
			<div
				class="booking--booking-base-note-button"
				:class="[className, {'--has-note': hasNote}].flat(1)"
				v-bind="$props.dataAttributes"
				@click="showEditPopup"
			>
				<div class="ui-icon-set --note"></div>
			</div>
		</div>
		<NotePopup
			v-if="isPopupShown"
			:isEditMode="isEditMode && isFeatureEnabled"
			:id="id"
			:text="note"
			:bindElement
			:dataId
			:dataElementPrefix
			@close="closeEditPopup"
			@save="$emit('save', $event)"
		/>
	`
	};

	// @vue/component
	const Profit = {
	  name: 'Profit',
	  props: {
	    /**
	     * @type {DealData}
	     */
	    deal: {
	      type: Object,
	      default: null
	    },
	    className: {
	      type: [Object, String, Array],
	      default: ''
	    },
	    dataAttributes: {
	      type: Object,
	      default: null
	    }
	  },
	  template: `
		<div
			v-if="deal"
			class="booking--booking-base-profit"
			:class="className"
			:data-profit="deal.data.opportunity"
			v-bind="$props.dataAttributes"
			v-html="deal.data.formattedOpportunity"
		></div>
	`
	};

	// @vue/component
	const AddClient = {
	  name: 'AddClient',
	  components: {
	    ClientPopup: booking_component_clientPopup.ClientPopup
	  },
	  props: {
	    expired: {
	      type: Boolean,
	      default: false
	    },
	    dataAttributes: {
	      type: Object,
	      default: () => ({})
	    },
	    buttonClass: {
	      type: String,
	      default: ''
	    },
	    popupOffsetLeft: {
	      type: Number,
	      default: null
	    }
	  },
	  emits: ['add'],
	  data() {
	    return {
	      showPopup: false
	    };
	  },
	  computed: {
	    ...ui_vue3_vuex.mapGetters({
	      providerModuleId: `${booking_const.Model.Clients}/providerModuleId`,
	      isFeatureEnabled: `${booking_const.Model.Interface}/isFeatureEnabled`
	    })
	  },
	  methods: {
	    clickHandler() {
	      var _PopupManager$getPopu;
	      if (!this.isFeatureEnabled) {
	        booking_lib_limit.limit.show();
	        return;
	      }
	      if (this.showPopup) {
	        return;
	      }
	      (_PopupManager$getPopu = main_popup.PopupManager.getPopupById(booking_component_clientPopup.CLIENT_POPUP_ID)) == null ? void 0 : _PopupManager$getPopu.destroy();
	      this.showPopup = true;
	    },
	    getOffsetLeft() {
	      const {
	        left
	      } = this.$refs.button.getBoundingClientRect();
	      if (window.innerWidth - left < 370) {
	        return -317;
	      }
	      return main_core.Type.isNil(this.popupOffsetLeft) ? this.$refs.button.offsetWidth + 10 : this.popupOffsetLeft;
	    }
	  },
	  template: `
		<div
			v-if="providerModuleId"
			class="booking--booking-base--add-client-button"
			:class="[buttonClass, { '--expired': expired }]"
			v-bind="$props.dataAttributes"
			ref="button"
			@click="clickHandler"
		>
			{{ loc('BOOKING_BOOKING_PLUS_CLIENT') }}
		</div>
		<ClientPopup
			v-if="showPopup"
			:bindElement="this.$refs.button"
			:offset-top="-100"
			:offset-left="getOffsetLeft()"
			@create="$emit('add', $event)"
			@close="showPopup = false"
		/>
	`
	};

	// @vue/component
	const Communication = {
	  name: 'Communication',
	  directives: {
	    hint: ui_vue3_directives_hint.hint
	  },
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  setup() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set
	    };
	  },
	  computed: {
	    soonHint() {
	      return {
	        text: this.loc('BOOKING_BOOKING_SOON_HINT'),
	        popupOptions: {
	          offsetLeft: -60
	        }
	      };
	    }
	  },
	  template: `
		<div v-hint="soonHint" class="booking--booking-base-communication">
			<Icon :name="IconSet.TELEPHONY_HANDSET_1"/>
			<Icon :name="IconSet.CHATS_2"/>
		</div>
	`
	};

	// @vue/component
	const CrmButton = {
	  name: 'CrmButton',
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    dealHelper: {
	      type: booking_lib_dealHelper.DealHelper,
	      required: true
	    },
	    dataAttributes: {
	      type: Object,
	      default: () => ({})
	    }
	  },
	  setup() {
	    return {
	      IconSet: ui_iconSet_api_vue.Set
	    };
	  },
	  computed: {
	    isFeatureEnabled() {
	      return this.$store.getters[`${booking_const.Model.Interface}/isFeatureEnabled`];
	    },
	    hasDeal() {
	      return this.dealHelper.hasDeal();
	    }
	  },
	  methods: {
	    onClick() {
	      if (!this.isFeatureEnabled) {
	        void booking_lib_limit.limit.show();
	        return;
	      }
	      if (this.hasDeal) {
	        this.dealHelper.openDeal();
	      } else {
	        this.dealHelper.createDeal();
	      }
	    }
	  },
	  template: `
		<Icon
			:name="IconSet.CRM_LETTERS"
			class="booking--booking-base-crm-button"
			:class="{'--no-deal': !hasDeal}"
			v-bind="$props.dataAttributes"
			@click="onClick"
		/>
	`
	};

	// @vue/component
	const DisabledPopup = {
	  name: 'DisabledPopup',
	  components: {
	    Popup: booking_component_popup.Popup
	  },
	  emits: ['close'],
	  props: {
	    popupId: {
	      type: String,
	      required: true
	    },
	    bindElement: {
	      type: Function,
	      required: true
	    },
	    contentClass: {
	      type: [String, Object],
	      default: 'booking-booking-disabled-popup-content'
	    }
	  },
	  mounted() {
	    this.adjustPosition();
	    setTimeout(() => this.closePopup(), 3000);
	    main_core.Event.bind(document, 'scroll', this.adjustPosition, true);
	  },
	  beforeUnmount() {
	    main_core.Event.unbind(document, 'scroll', this.adjustPosition, true);
	  },
	  computed: {
	    config() {
	      return {
	        className: 'booking-booking-disabled-popup',
	        bindElement: this.bindElement(),
	        width: this.bindElement().offsetWidth,
	        offsetTop: -10,
	        bindOptions: {
	          forceBindPosition: true,
	          position: 'top'
	        },
	        autoHide: true,
	        darkMode: true
	      };
	    }
	  },
	  methods: {
	    adjustPosition() {
	      this.$refs.popup.adjustPosition();
	    },
	    closePopup() {
	      this.$emit('close');
	    }
	  },
	  template: `
		<Popup
			:id="popupId"
			:config="config"
			ref="popup"
			@close="closePopup"
		>
			<div :class="contentClass">
				{{ loc('BOOKING_BOOKING_YOU_CANNOT_EDIT_THIS_BOOKING') }}
			</div>
		</Popup>
	`
	};

	exports.BookingBase = BookingBase;
	exports.Name = Name;
	exports.Note = Note;
	exports.Profit = Profit;
	exports.AddClient = AddClient;
	exports.Communication = Communication;
	exports.CrmButton = CrmButton;
	exports.DisabledPopup = DisabledPopup;

}((this.BX.Booking.Component = this.BX.Booking.Component || {}),BX.Booking.Component,BX.Vue3.Vuex,BX.Booking.Component,BX.Vue3.Directives,BX,BX.UI.IconSet,BX,BX.Booking.Const,BX.Booking.Lib,BX.Booking.Lib,BX,BX.Main,BX.Booking.Component));
//# sourceMappingURL=booking.bundle.js.map
