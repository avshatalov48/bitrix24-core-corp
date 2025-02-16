/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_vue3,booking_model_bookings,booking_component_mixin_locMixin,ui_iconSet_main,main_date,ui_iconSet_api_vue,main_core,main_popup,booking_component_button,booking_component_popup) {
	'use strict';

	const Mixin = {
	  computed: {
	    isBookingCanceled() {
	      return this.booking.isDeleted === true;
	    }
	  }
	};

	const Header = {
	  name: 'Header',
	  mixins: [Mixin],
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon
	  },
	  props: {
	    booking: {
	      type: Object,
	      required: true
	    },
	    company: {
	      type: String,
	      required: true
	    },
	    context: {
	      type: String,
	      required: true
	    }
	  },
	  data() {
	    return {};
	  },
	  computed: {
	    iconColor() {
	      if (this.context === 'delayed.pub.page') {
	        return 'var(--ui-color-palette-gray-60)';
	      }
	      return this.isBookingCanceled ? 'var(--ui-color-palette-red-60)' : 'var(--ui-color-palette-green-60)';
	    },
	    iconSize() {
	      return 45;
	    },
	    iconName() {
	      if (this.context === 'delayed.pub.page') {
	        return ui_iconSet_api_vue.Set.HOURGLASS_SANDGLASS;
	      }
	      return this.isBookingCanceled ? ui_iconSet_api_vue.Set.CROSS_CIRCLE_70 : ui_iconSet_api_vue.Set.CIRCLE_CHECK;
	    },
	    titleClass() {
	      if (this.context === 'delayed.pub.page') {
	        return '--delayed';
	      }
	      return this.isBookingCanceled ? '--canceled' : '';
	    },
	    title() {
	      if (this.context === 'delayed.pub.page') {
	        return this.loc('BOOKING_CONFIRM_PAGE_BOOKING_CONFIRMATION_WAITING');
	      }
	      return this.isBookingCanceled ? this.loc('BOOKING_CONFIRM_PAGE_BOOKING_CANCELED') : this.loc('BOOKING_CONFIRM_PAGE_BOOKING_CONFIRMED');
	    }
	  },
	  template: `
		<div class="confirm-page-header">
			<div class="confirm-page-header-status">
				<div :class="['confirm-page-header-status-icon', titleClass]">
					<Icon :name="iconName" :size="iconSize" :color="iconColor" />
				</div>
				<div :class="['confirm-page-header-status-title', titleClass]">{{ title }}</div>
			</div>
			<div class="confirm-page-header-company">
				<div class="confirm-page-header-company-title">{{ company }}</div>
			</div>
		</div>
	`
	};

	const BookingTime = {
	  name: 'BookingTime',
	  mixins: [Mixin],
	  props: {
	    booking: {
	      type: Object,
	      required: true
	    }
	  },
	  data() {
	    return {};
	  },
	  computed: {
	    getBookingDateFrom() {
	      return new Date(this.booking.datePeriod.from.timestamp * 1000);
	    },
	    getBookingDateTo() {
	      return new Date(this.booking.datePeriod.to.timestamp * 1000);
	    },
	    getTimeTo() {
	      const bookingDateTo = new Date(this.booking.datePeriod.to.timestamp);
	      return `${bookingDateTo.getHours()}:${bookingDateTo.getMinutes()}`;
	    },
	    getMonth() {
	      return main_date.DateTimeFormat.format('F', this.getBookingDateFrom);
	    },
	    getDayOfWeek() {
	      const weekDay = this.getBookingDateFrom.getDay();
	      return this.loc(`BOOKING_CONFIRM_PAGE_CALENDAR_WEEK_DAY_${weekDay}`);
	    },
	    timeFromFormatted() {
	      const timeFormat = main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
	      return main_date.DateTimeFormat.format(timeFormat, this.getBookingDateFrom);
	    },
	    timeFormatted() {
	      const timeFormat = main_date.DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
	      return this.loc('BOOKING_CONFIRM_PAGE_TIME_RANGE', {
	        '#FROM#': main_date.DateTimeFormat.format(timeFormat, this.getBookingDateFrom),
	        '#TO#': main_date.DateTimeFormat.format(timeFormat, this.getBookingDateTo)
	      });
	    },
	    timeDetailFormatted() {
	      const timeFormat = main_date.DateTimeFormat.getFormat('SHORT_DAY_OF_WEEK_MONTH_FORMAT');
	      return main_date.DateTimeFormat.format(timeFormat, this.getBookingDateFrom);
	    },
	    timeZoneFormatted() {
	      const offset = this.getBookingDateFrom.getTimezoneOffset();
	      const hours = Math.floor(Math.abs(offset) / 60);
	      const minutes = Math.abs(offset) % 60;
	      const sign = offset > 0 ? '-' : '+';
	      return `GMT${sign}${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}, ${this.booking.datePeriod.from.timezone}`;
	    }
	  },
	  template: `
		<div class="confirm-page-content-border">
			<div class="confirm-page-content-booking-time">
				<div class="confirm-page-content-booking-time-calendar">
					<div class="confirm-page-content-booking-time-calendar-container">
						<div class="confirm-page-content-booking-time-calendar-container-border"></div>
						<div class="confirm-page-content-booking-time-calendar-container-header"></div>
						<div class="confirm-page-content-booking-time-calendar-container-date">{{ getBookingDateFrom.getDate() }}</div>
						<div class="confirm-page-content-booking-time-calendar-container-month">{{ getMonth }}</div>
						<div class="confirm-page-content-booking-time-calendar-container-time">{{ timeFromFormatted }}</div>
					</div>
				</div>
				<div class="confirm-page-content-booking-time-detail">
					<div class="confirm-page-content-booking-time-detail-date">{{ timeDetailFormatted }}</div>
					<div class="confirm-page-content-booking-time-detail-time">{{ timeFormatted }}</div>
					<div class="confirm-page-content-booking-time-detail-timezone">{{ timeZoneFormatted }}</div>
				</div>
			</div>
		</div>
	`
	};

	const Item = {
	  name: 'BookingDetailItem',
	  props: {
	    item: {
	      type: Object,
	      required: true
	    }
	  },
	  data() {
	    return {};
	  },
	  template: `
		<div class="booking-confirm-page-content-booking-detail-line"></div>
		<div class="booking-confirm-page-content-booking-detail-service">
			<div class="booking-confirm-page-content-booking-detail-service-item">
				<div class="booking-confirm-page-content-booking-detail-service-item-summary">
					<div class="booking-confirm-page-content-booking-detail-service-item-summary-title">Массаж расслабл…</div>
					<div class="booking-confirm-page-content-booking-detail-service-item-summary-duration">1 час</div>
				</div>
				<div class="booking-confirm-page-content-booking-detail-service-item-price">1800 ₽</div>
			</div>
		</div>
	`
	};

	const Total = {
	  name: 'BookingDetailTotal',
	  props: {
	    booking: {
	      type: Object,
	      required: true
	    }
	  },
	  data() {
	    return {};
	  },
	  template: `
		<div class="booking-confirm-page-content-booking-detail-line"></div>
		<div class="booking-confirm-page-content-booking-detail-service">
			<div class="booking-confirm-page-content-booking-detail-service-item">
				<div class="booking-confirm-page-content-booking-detail-service-item-total-title">Итого</div>
				<div class="booking-confirm-page-content-booking-detail-service-item-total-price">3800 ₽</div>
			</div>
		</div>
	`
	};

	const Avatar = {
	  name: 'BookingDetailAvatar',
	  props: {
	    booking: {
	      type: Object,
	      required: true
	    }
	  },
	  data() {
	    return {};
	  },
	  template: `
		
	`
	};

	const BookingDetail = {
	  name: 'BookingDetail',
	  mixins: [Mixin],
	  components: {
	    Item,
	    Total,
	    Avatar
	  },
	  props: {
	    booking: {
	      type: Object,
	      required: true
	    }
	  },
	  data() {
	    return {};
	  },
	  template: `
		<div class="booking-confirm-page-content-booking-detail-border">
			<div class="booking-confirm-page-content-booking-detail-title">{{ loc('BOOKING_CONFIRM_PAGE_BOOKING_DETAILS') }}</div>
			<div class="booking-confirm-page-content-booking-detail-line"></div>
			<div class="booking-confirm-page-content-booking-detail-master">
				<Avatar :booking="booking" />
				<div class="booking-confirm-page-content-booking-detail-master-info">
					<div class="booking-confirm-page-content-booking-detail-master-name">{{ booking.resources[0].name }}</div>
					<div class="booking-confirm-page-content-booking-detail-master-title">{{ booking.resources[0].type.name }}</div>
				</div>
			</div>
		</div>
	`
	};

	const Content = {
	  name: 'Content',
	  components: {
	    BookingTime,
	    BookingDetail
	  },
	  props: {
	    booking: {
	      type: Object,
	      required: true
	    }
	  },
	  data() {
	    return {};
	  },
	  template: `
		<div class="confirm-page-content">
			<BookingTime :booking="booking"/>
			<BookingDetail :booking="booking"/>
		</div>
	`
	};

	const ConfirmPopup = {
	  name: 'ConfirmPopup',
	  emits: ['bookingConfirmed', 'bookingCanceled', 'closePopup'],
	  components: {
	    Popup: booking_component_popup.Popup,
	    Button: booking_component_button.Button
	  },
	  props: {
	    booking: {
	      type: Object,
	      required: true
	    },
	    showPopup: {
	      type: Boolean,
	      required: true
	    }
	  },
	  data() {
	    return {
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      btnWaiting: false
	    };
	  },
	  methods: {
	    confirmBookingHandler() {
	      this.btnWaiting = true;
	      this.$emit('bookingConfirmed');
	    },
	    cancelBookingHandler() {
	      this.btnWaiting = true;
	      this.$emit('bookingCanceled');
	    }
	  },
	  computed: {
	    popupId() {
	      return `booking-confirm-page-popup-${this.booking.id}`;
	    },
	    popupConfig() {
	      return {
	        className: 'booking-confirm-page-popup',
	        offsetLeft: 0,
	        offsetTop: 0,
	        overlay: true,
	        borderRadius: '5px',
	        autoHide: false
	      };
	    }
	  },
	  watch: {
	    booking: {
	      handler(booking) {
	        if (booking.isConfirmed === true) {
	          this.btnWaiting = false;
	          this.$emit('closePopup');
	        }
	      },
	      deep: true
	    }
	  },
	  template: `
		<Popup
			v-if="showPopup"
			:id="popupId"
			:config="popupConfig"
			@close="closePopup"
		>
			<div class="cancel-booking-popup-content">
				<div class="cancel-booking-popup-content-title">{{ loc('BOOKING_CONFIRM_PAGE_MESSAGEBOX_CONFIRM_TILE') }}</div>
				<div class="cancel-booking-popup-content-text">{{ loc('BOOKING_CONFIRM_PAGE_MESSAGEBOX_CONFIRM_TEXT') }}</div>
				<div class="cancel-booking-popup-content-buttons">
					<Button
						:text="loc('BOOKING_CONFIRM_PAGE_MESSAGEBOX_BTN_NOT_CONFIRM')"
						:size="ButtonSize.EXTRA_SMALL"
						:color="ButtonColor.LIGHT_BORDER"
						:buttonClass="'cancel-booking-popup-content-buttons-no'"
						@click="cancelBookingHandler"
					/>
					<Button
						:text="loc('BOOKING_CONFIRM_PAGE_MESSAGEBOX_BTN_CONFIRM')"
						:size="ButtonSize.EXTRA_SMALL"
						:buttonClass="'cancel-booking-popup-content-buttons-yes --confirm'"
						:waiting="btnWaiting"
						@click="confirmBookingHandler"
					/>
				</div>
			</div>
		</Popup>
	`
	};

	const CancelPopup = {
	  name: 'CancelPopup',
	  emits: ['bookingCanceled', 'popupClosed'],
	  components: {
	    Popup: booking_component_popup.Popup,
	    Button: booking_component_button.Button
	  },
	  props: {
	    booking: {
	      type: Object,
	      required: true
	    },
	    showPopup: {
	      type: Boolean,
	      required: true
	    }
	  },
	  data() {
	    return {
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      btnWaiting: false
	    };
	  },
	  methods: {
	    cancelBookingHandler() {
	      this.btnWaiting = true;
	      this.$emit('bookingCanceled');
	    },
	    closePopup() {
	      this.$emit('popupClosed');
	    }
	  },
	  computed: {
	    popupId() {
	      return `booking-confirm-page-popup-${this.booking.id}`;
	    },
	    popupConfig() {
	      return {
	        className: 'booking-confirm-page-popup',
	        offsetLeft: 0,
	        offsetTop: 0,
	        overlay: true,
	        borderRadius: '5px'
	      };
	    }
	  },
	  watch: {
	    booking: {
	      handler(booking) {
	        if (booking.isDeleted === true) {
	          this.btnWaiting = false;
	          this.closePopup();
	        }
	      },
	      deep: true
	    }
	  },
	  template: `
		<Popup
			v-if="showPopup"
			:id="popupId"
			:config="popupConfig"
			@close="closePopup"
		>
			<div class="cancel-booking-popup-content">
				<div class="cancel-booking-popup-content-title">{{ loc('BOOKING_CONFIRM_PAGE_MESSAGEBOX_TILE') }}</div>
				<div class="cancel-booking-popup-content-text">{{ loc('BOOKING_CONFIRM_PAGE_MESSAGEBOX_TEXT') }}</div>
				<div class="cancel-booking-popup-content-buttons">
					<Button
						:text="loc('BOOKING_CONFIRM_PAGE_MESSAGEBOX_BTN_NO')"
						:size="ButtonSize.EXTRA_SMALL"
						:color="ButtonColor.LIGHT_BORDER"
						:buttonClass="'cancel-booking-popup-content-buttons-no'"
						@click="closePopup"
					/>
					<Button
						:text="loc('BOOKING_CONFIRM_PAGE_MESSAGEBOX_BTN_YES')"
						:size="ButtonSize.EXTRA_SMALL"
						:buttonClass="'cancel-booking-popup-content-buttons-yes'"
						:waiting="btnWaiting"
						@click="cancelBookingHandler"
					/>
				</div>
			</div>
		</Popup>
	`
	};

	const Cancel = {
	  name: 'Cancel',
	  emits: ['bookingCanceled', 'bookingConfirmed'],
	  components: {
	    Icon: ui_iconSet_api_vue.BIcon,
	    Button: booking_component_button.Button,
	    CancelPopup,
	    ConfirmPopup
	  },
	  props: {
	    booking: {
	      type: Object,
	      required: true
	    },
	    context: {
	      type: String,
	      required: true
	    }
	  },
	  data() {
	    return {
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      ButtonIcon: booking_component_button.ButtonIcon,
	      showPopup: this.context === 'delayed.pub.page',
	      btnWaiting: false,
	      showLink: true
	    };
	  },
	  methods: {
	    cancelBookingHandler() {
	      this.btnWaiting = true;
	      this.$emit('bookingCanceled');
	    },
	    confirmBookingHandler() {
	      this.btnWaiting = true;
	      this.$emit('bookingConfirmed');
	    },
	    cancelBtnHandler() {
	      if (this.booking.isDeleted === true) {
	        return;
	      }
	      this.showPopup = true;
	    },
	    closePopup() {
	      this.showPopup = false;
	    }
	  },
	  computed: {
	    icon() {
	      return ui_iconSet_api_vue.Set.UNDO_1;
	    },
	    iconSize() {
	      return 17;
	    },
	    iconColor() {
	      return '#A8ADB4';
	    },
	    popupId() {
	      return `booking-confirm-page-popup-${this.booking.id}`;
	    },
	    popupConfig() {
	      return {
	        className: 'booking-confirm-page-popup',
	        offsetLeft: 0,
	        offsetTop: 0,
	        overlay: true,
	        borderRadius: '5px'
	      };
	    },
	    showCancelBtn() {
	      return this.context !== 'manager.view.details';
	    }
	  },
	  watch: {
	    booking: {
	      handler(booking) {
	        if (booking.isDeleted === true) {
	          this.showLink = false;
	          this.btnWaiting = false;
	          this.showPopup = false;
	        }
	        if (booking.isConfirmed === true) {
	          this.btnWaiting = false;
	          this.showPopup = false;
	        }
	      },
	      deep: true
	    }
	  },
	  template: `
		<div v-if="showLink" class="cancel-booking">
			<Icon :name="icon" :size="iconSize" :color="iconColor" v-if="showCancelBtn"/>
			<a class="cancel-booking-link" @click="cancelBtnHandler" v-if="showCancelBtn">
				{{ loc('BOOKING_CONFIRM_PAGE_CANCEL_BTN') }}
			</a>
		</div>
		<CancelPopup 
			v-if="context === 'cancel.pub.page'"
			:showPopup="showPopup" 
			:booking="booking"
			@bookingCanceled="cancelBookingHandler"
			@popupClosed="closePopup"
		/>
		<ConfirmPopup
			v-if="context === 'delayed.pub.page'"
			:showPopup="showPopup"
			:booking="booking"
			@bookingCanceled="cancelBookingHandler"
			@bookingConfirmed="confirmBookingHandler"
			@closePopup="closePopup"
		/>
	`
	};

	const Footer = {
	  name: 'Footer',
	  emits: ['bookingCanceled', 'bookingConfirmed'],
	  components: {
	    Cancel
	  },
	  props: {
	    booking: {
	      type: Object,
	      required: true
	    },
	    context: {
	      type: String,
	      required: true
	    }
	  },
	  data() {
	    return {};
	  },
	  template: `
		<div>
			<Cancel 
				:booking="booking"
				:context="context"
				@bookingCanceled="$emit('bookingCanceled')"
				@bookingConfirmed="$emit('bookingConfirmed')"
			/>
		</div>
	`
	};

	const App = {
	  name: 'ConfirmPageApp',
	  components: {
	    Header,
	    Content,
	    Footer
	  },
	  props: {
	    booking: {
	      type: Object,
	      required: true
	    },
	    hash: {
	      type: String,
	      required: true
	    },
	    company: {
	      type: String,
	      required: true
	    },
	    context: {
	      type: String,
	      required: true
	    }
	  },
	  data() {
	    return {
	      confirmedBooking: this.booking,
	      confirmedContext: this.context
	    };
	  },
	  methods: {
	    async bookingCancelHandler() {
	      try {
	        await main_core.ajax.runComponentAction('bitrix:booking.pub.confirm', 'cancel', {
	          mode: 'class',
	          data: {
	            hash: this.hash
	          }
	        });
	        this.confirmedBooking.isDeleted = true;
	        if (this.confirmedContext === 'delayed.pub.page') {
	          this.confirmedContext = 'cancel.pub.page';
	        }
	      } catch (error) {
	        console.error('Confirm page: cancel error', error);
	      }
	    },
	    async bookingConfirmHandler() {
	      try {
	        await main_core.ajax.runComponentAction('bitrix:booking.pub.confirm', 'confirm', {
	          mode: 'class',
	          data: {
	            hash: this.hash
	          }
	        });
	        this.confirmedBooking.isConfirmed = true;
	        if (this.confirmedContext === 'delayed.pub.page') {
	          this.confirmedBooking.confirmedByDelayed = true;
	          this.confirmedContext = 'cancel.pub.page';
	        }
	      } catch (error) {
	        console.error('Confirm page: confirm error', error);
	      }
	    }
	  },
	  template: `
		<div class="confirm-page-container">
			<div class="confirm-page-body">
				<Header 
					:booking="confirmedBooking"
					:company="company"
					:context="confirmedContext"
				/>
				<Content :booking="confirmedBooking"/>
				<Footer 
					:booking="confirmedBooking"
					:context="confirmedContext"
					@bookingCanceled="bookingCancelHandler"
					@bookingConfirmed="bookingConfirmHandler"
				/>
			</div>
		</div>
	`
	};

	class ConfirmPagePublic {
	  constructor(params) {
	    const app = ui_vue3.BitrixVue.createApp(App, params);
	    app.mixin(booking_component_mixin_locMixin.locMixin);
	    app.mount(params.container);
	  }
	}

	exports.ConfirmPagePublic = ConfirmPagePublic;

}((this.BX.Booking = this.BX.Booking || {}),BX.Vue3,BX.Booking.Model,BX.Booking.Component.Mixin,BX,BX.Main,BX.UI.IconSet,BX,BX.Main,BX.Booking.Component,BX.Booking.Component));
//# sourceMappingURL=confirm-page-public.bundle.js.map
