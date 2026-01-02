/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_vue3_components_popup,booking_const,ui_iconSet_api_vue,ui_iconSet_api_core,ui_iconSet_main,ui_iconSet_outline,booking_component_counter,main_date,main_core,main_popup,ui_vue3,ui_vue3_mixins_locMixin) {
	'use strict';

	const CardId = Object.freeze({
	  Default: 'default',
	  Unconfirmed: 'unconfirmed',
	  Confirmed: 'confirmed',
	  Late: 'late',
	  Waitlist: 'waitlist',
	  Overbooking: 'overbooking'
	});

	// @vue/component
	const SegmentButton = {
	  props: {
	    /** @type SegmentOptions[] */
	    segments: {
	      type: Array,
	      required: true
	    },
	    selectedSegmentId: {
	      type: String,
	      required: true
	    }
	  },
	  emits: ['update:selectedSegmentId'],
	  template: `
		<div class="booking-cycle-popup-segment-button">
			<template v-for="segment of segments" :key="segment.title">
				<div
					class="booking-cycle-popup-segment"
					:class="{ '--selected': segment.id === selectedSegmentId }"
					@click="$emit('update:selectedSegmentId', segment.id)"
				>
					{{ segment.title }}
				</div>
			</template>
		</div>
	`
	};

	// @vue/component
	const BookingCard = {
	  template: `
		<div class="booking-cycle-popup-preview-booking">
			<div class="booking-cycle-popup-preview-booking-title">{{ loc('BOOKING_CYCLE_POPUP_CLIENT') }}</div>
			<slot/>
		</div>
	`
	};

	// @vue/component
	const Booking = {
	  components: {
	    BookingCard,
	    BIcon: ui_iconSet_api_vue.BIcon,
	    UiCounter: booking_component_counter.Counter
	  },
	  props: {
	    type: {
	      type: String,
	      required: true
	    }
	  },
	  setup() {
	    return {
	      Main: ui_iconSet_api_core.Main,
	      Outline: ui_iconSet_api_core.Outline,
	      CounterSize: booking_component_counter.CounterSize,
	      CounterColor: booking_component_counter.CounterColor,
	      CardId
	    };
	  },
	  template: `
		<div>
			<template v-if="type === CardId.Overbooking">
				<BookingCard class="--left"/>
				<BookingCard class="--right"/>
			</template>
			<BookingCard v-else :class="'--' + type">
				<div class="booking-cycle-popup-preview-booking-row">
					<div class="booking-cycle-popup-preview-booking-row-line"></div>
					<div class="booking-cycle-popup-preview-booking-icons">
						<BIcon :name="Outline.PHONE_UP"/>
						<BIcon :name="Outline.CHATS"/>
						<BIcon :name="Outline.CRM_LETTERS"/>
					</div>
				</div>
				<div class="booking-cycle-popup-preview-counter">
					<UiCounter
						v-if="[CardId.Unconfirmed, CardId.Late].includes(type)"
						:value="1"
						:color="CounterColor.DANGER"
						:size="CounterSize.MEDIUM"
						:border="true"
					/>
					<div v-if="type === CardId.Confirmed" class="booking-cycle-popup-preview-counter-icon">
						<BIcon :name="Main.CHECK"/>
					</div>
				</div>
				<div v-if="type === CardId.Late" class="booking-cycle-popup-preview-line"></div>
			</BookingCard>
		</div>
	`
	};

	// @vue/component
	const Crm = {
	  props: {
	    type: {
	      type: String,
	      required: true
	    }
	  },
	  setup() {
	    return {
	      CardId
	    };
	  },
	  computed: {
	    title() {
	      var _CardId$Waitlist$Card;
	      return (_CardId$Waitlist$Card = {
	        [CardId.Waitlist]: this.loc('BOOKING_CYCLE_POPUP_BOOKING_WAITLIST'),
	        [CardId.Overbooking]: this.loc('BOOKING_CYCLE_POPUP_BOOKING_OVERBOOKING')
	      }[this.type]) != null ? _CardId$Waitlist$Card : this.loc('BOOKING_CYCLE_POPUP_BOOKING');
	    },
	    fields() {
	      if (this.type === CardId.Waitlist) {
	        return [this.loc('BOOKING_CYCLE_POPUP_CLIENT')];
	      }
	      return [this.loc('BOOKING_CYCLE_POPUP_DATE_TIME'), this.loc('BOOKING_CYCLE_POPUP_RESOURCE')];
	    },
	    day() {
	      return main_date.DateTimeFormat.format('d');
	    }
	  },
	  template: `
		<div class="booking-cycle-popup-preview-crm" :class="'--' + type">
			<div class="booking-cycle-popup-preview-crm-header">
				<div class="booking-cycle-popup-preview-crm-checkbox"></div>
				<div class="booking-cycle-popup-preview-crm-title">{{ title }}</div>
				<div v-if="[CardId.Unconfirmed, CardId.Late].includes(type)" class="booking-cycle-popup-preview-crm-pill"></div>
			</div>
			<div class="booking-cycle-popup-preview-crm-main">
				<div class="booking-cycle-popup-preview-calendar-container">
					<div class="booking-cycle-popup-preview-calendar">
						<div class="booking-cycle-popup-preview-calendar-header"></div>
						<div class="booking-cycle-popup-preview-calendar-content">
							<div v-if="type === CardId.Waitlist" class="booking-cycle-popup-preview-calendar-days"></div>
							<template v-else>
								<div class="booking-cycle-popup-preview-calendar-day">{{ day }}</div>
								<div class="booking-cycle-popup-preview-calendar-line"></div>
							</template>
						</div>
					</div>
				</div>
				<div class="booking-cycle-popup-preview-crm-fields">
					<template v-for="field of fields" :key="field">
						<div class="booking-cycle-popup-preview-crm-field-title">{{ field }}</div>
						<div class="booking-cycle-popup-preview-crm-field-value"></div>
					</template>
				</div>
			</div>
		</div>
	`
	};

	// @vue/component
	const Preview = {
	  components: {
	    Booking,
	    Crm
	  },
	  props: {
	    mode: {
	      type: String,
	      required: true
	    },
	    type: {
	      type: String,
	      required: true
	    }
	  },
	  computed: {
	    isBooking() {
	      return this.mode === booking_const.Module.Booking;
	    }
	  },
	  template: `
		<div class="booking-cycle-popup-card-preview">
			<Booking v-if="isBooking" :type="type"/>
			<Crm v-else :type="type"/>
		</div>
	`
	};

	// @vue/component
	const Card = {
	  components: {
	    SegmentButton,
	    Preview
	  },
	  inject: ['context'],
	  props: {
	    id: {
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
	    }
	  },
	  data() {
	    var _this$context;
	    return {
	      previewMode: (_this$context = this.context) != null ? _this$context : booking_const.Module.Booking
	    };
	  },
	  computed: {
	    segments() {
	      return [{
	        id: booking_const.Module.Booking,
	        title: this.loc('BOOKING_CYCLE_POPUP_SEE_BOOKING')
	      }, {
	        id: booking_const.Module.Crm,
	        title: this.loc('BOOKING_CYCLE_POPUP_SEE_CRM')
	      }];
	    }
	  },
	  template: `
		<div class="booking-cycle-popup-card" :data-card-id="id">
			<div class="booking-cycle-popup-card-main">
				<div class="booking-cycle-popup-card-title">{{ title }}</div>
				<div class="booking-cycle-popup-description">{{ description }}</div>
				<div class="booking-cycle-popup-choose-preview">
					<div class="booking-cycle-popup-see-how">{{ loc('BOOKING_CYCLE_POPUP_SEE_HOW') }}</div>
					<SegmentButton v-model:selectedSegmentId="previewMode" :segments="segments"/>
				</div>
			</div>
			<Preview :mode="previewMode" :type="id"/>
		</div>
	`
	};

	// @vue/component
	const Step = {
	  components: {
	    Card
	  },
	  props: {
	    id: {
	      type: String,
	      required: true
	    },
	    ordinal: {
	      type: Number,
	      required: true
	    },
	    title: {
	      type: String,
	      required: true
	    },
	    /** @type CardOptions[] */
	    cards: {
	      type: Array,
	      required: true
	    }
	  },
	  template: `
		<div class="booking-cycle-popup-step" :data-step-id="id">
			<div class="booking-cycle-popup-step-header">
				<div class="booking-cycle-popup-step-number">{{ ordinal }}</div>
				<div class="booking-cycle-popup-step-title">{{ title }}</div>
			</div>
			<template v-for="(card, index) of cards" :key="index">
				<Card
					:id="card.id"
					:title="card.title"
					:description="card.description"
				/>
			</template>
		</div>
	`
	};

	// @vue/component
	const CyclePopupContent = {
	  components: {
	    Step
	  },
	  provide() {
	    return {
	      context: this.context
	    };
	  },
	  props: {
	    context: {
	      type: String,
	      default: null
	    },
	    scrollToCard: {
	      type: String,
	      default: null
	    }
	  },
	  emits: ['close'],
	  computed: {
	    steps() {
	      return [{
	        id: 'booked',
	        title: this.loc('BOOKING_CYCLE_POPUP_BOOKED'),
	        cards: [{
	          id: CardId.Default,
	          title: this.loc('BOOKING_CYCLE_POPUP_NEW_TITLE'),
	          description: this.loc('BOOKING_CYCLE_POPUP_NEW_DESCRIPTION')
	        }, {
	          id: CardId.Unconfirmed,
	          title: this.loc('BOOKING_CYCLE_POPUP_UNCONFIRMED_TITLE'),
	          description: this.loc('BOOKING_CYCLE_POPUP_UNCONFIRMED_DESCRIPTION')
	        }]
	      }, {
	        id: 'confirmed',
	        title: this.loc('BOOKING_CYCLE_POPUP_CONFIRMED_MSGVER1'),
	        cards: [{
	          id: CardId.Confirmed,
	          title: this.loc('BOOKING_CYCLE_POPUP_CONFIRMED_TITLE'),
	          description: this.loc('BOOKING_CYCLE_POPUP_CONFIRMED_DESCRIPTION')
	        }]
	      }, {
	        id: 'late',
	        title: this.loc('BOOKING_CYCLE_POPUP_LATE_MSGVER1'),
	        cards: [{
	          id: CardId.Late,
	          title: this.loc('BOOKING_CYCLE_POPUP_LATE_TITLE_MSGVER1'),
	          description: this.loc('BOOKING_CYCLE_POPUP_LATE_DESCRIPTION')
	        }]
	      }, {
	        id: 'waitlist',
	        title: this.loc('BOOKING_CYCLE_POPUP_WAITLIST'),
	        cards: [{
	          id: CardId.Waitlist,
	          title: this.loc('BOOKING_CYCLE_POPUP_WAITLIST_TITLE_MSGVER1'),
	          description: this.loc('BOOKING_CYCLE_POPUP_WAITLIST_DESCRIPTION')
	        }]
	      }, {
	        id: 'overbooking',
	        title: this.loc('BOOKING_CYCLE_POPUP_OVERBOOKING'),
	        cards: [{
	          id: CardId.Overbooking,
	          title: this.loc('BOOKING_CYCLE_POPUP_OVERBOOKING_TITLE_MSGVER1'),
	          description: this.loc('BOOKING_CYCLE_POPUP_OVERBOOKING_DESCRIPTION_MSGVER1')
	        }]
	      }];
	    }
	  },
	  mounted() {
	    setTimeout(() => {
	      var _this$getScrollElemen;
	      this.$refs.scrollable.scroll({
	        top: (_this$getScrollElemen = this.getScrollElement(this.scrollToCard)) == null ? void 0 : _this$getScrollElemen.offsetTop,
	        behavior: 'smooth'
	      });
	    });
	  },
	  methods: {
	    getScrollElement(cardId) {
	      const step = this.steps.find(it => it.cards.some(card => card.id === cardId));
	      if ((step == null ? void 0 : step.cards.length) === 1) {
	        return this.$refs.container.querySelector(`[data-step-id="${step.id}"]`);
	      }
	      return this.$refs.container.querySelector(`[data-card-id="${cardId}"]`);
	    }
	  },
	  template: `
		<div class="booking-cycle-popup" ref="container">
			<div class="booking-cycle-popup-header">
				<div class="booking-cycle-popup-title">{{ loc('BOOKING_CYCLE_POPUP_TITLE') }}</div>
				<div class="booking-cycle-popup-description">{{ loc('BOOKING_CYCLE_POPUP_DESCRIPTION') }}</div>
			</div>
			<div class="booking-cycle-popup-main" ref="scrollable">
				<template v-for="(step, index) of steps" :key="index">
					<Step
						:id="step.id"
						:ordinal="index + 1"
						:title="step.title"
						:cards="step.cards"
					/>
				</template>
			</div>
		</div>
	`
	};

	const meta = Object.freeze({
	  popupOptions: {
	    id: 'booking-cycle-popup',
	    width: 540,
	    height: 500,
	    padding: 20,
	    overlay: true,
	    cacheable: false,
	    autoHide: true,
	    closeByEsc: true,
	    animation: 'fading',
	    closeIcon: {
	      top: '14px',
	      right: '14px'
	    }
	  }
	});

	// @vue/component
	const CyclePopup = {
	  components: {
	    Popup: ui_vue3_components_popup.Popup,
	    CyclePopupContent
	  },
	  props: {
	    scrollToCard: {
	      type: String,
	      default: null
	    }
	  },
	  emits: ['close'],
	  setup() {
	    return {
	      meta
	    };
	  },
	  template: `
		<Popup :options="meta.popupOptions" @close="$emit('close')">
			<CyclePopupContent :scrollToCard="scrollToCard"/>
		</Popup>
	`
	};

	var _application = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("application");
	class CyclePopupOpener {
	  constructor() {
	    Object.defineProperty(this, _application, {
	      writable: true,
	      value: null
	    });
	  }
	  show(params) {
	    new main_popup.Popup({
	      ...meta.popupOptions,
	      events: {
	        onAfterPopupShow: popup => {
	          babelHelpers.classPrivateFieldLooseBase(this, _application)[_application] = ui_vue3.BitrixVue.createApp(CyclePopupContent, params);
	          babelHelpers.classPrivateFieldLooseBase(this, _application)[_application].mixin(ui_vue3_mixins_locMixin.locMixin);
	          const {
	            $el
	          } = babelHelpers.classPrivateFieldLooseBase(this, _application)[_application].mount(main_core.Dom.create('div'));
	          popup.getContentContainer().replaceWith($el);
	        },
	        onPopupAfterClose: () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _application)[_application].unmount();
	        }
	      }
	    }).show();
	  }
	}
	const cyclePopupOpener = new CyclePopupOpener();

	exports.CyclePopup = CyclePopup;
	exports.CardId = CardId;
	exports.cyclePopupOpener = cyclePopupOpener;

}((this.BX.Booking.Component = this.BX.Booking.Component || {}),BX.UI.Vue3.Components,BX.Booking.Const,BX.UI.IconSet,BX.UI.IconSet,BX,BX,BX.Booking.Component,BX.Main,BX,BX.Main,BX.Vue3,BX.Vue3.Mixins));
//# sourceMappingURL=cycle-popup.bundle.js.map
