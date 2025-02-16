/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,ui_datePicker,booking_component_button) {
	'use strict';

	const Drum = {
	  components: {
	    Button: booking_component_button.Button
	  },
	  emits: ['onSave', 'onCancel'],
	  props: {
	    selectedTime: {
	      type: Date,
	      default: new Date()
	    }
	  },
	  data() {
	    return {
	      ButtonSize: booking_component_button.ButtonSize,
	      ButtonColor: booking_component_button.ButtonColor,
	      selectedTimeLocal: this.selectedTime
	    };
	  },
	  mounted() {
	    this.getDatePicker().selectDate(this.selectedTime);
	    this.getDatePicker().show();
	  },
	  watch: {
	    selectedTime(newVal) {
	      this.getDatePicker().selectDate(newVal);
	    }
	  },
	  beforeUnmount() {
	    var _this$datePicker;
	    (_this$datePicker = this.datePicker) == null ? void 0 : _this$datePicker.destroy();
	    this.datePicker = null;
	  },
	  methods: {
	    getDatePicker() {
	      if (!this.datePicker) {
	        this.datePicker = new ui_datePicker.DatePicker({
	          targetNode: this.$refs.datepicker,
	          inline: true,
	          type: 'time',
	          timePickerStyle: 'wheel'
	        });
	      }
	      return this.datePicker;
	    },
	    save() {
	      const selectedDate = this.getDatePicker().getSelectedDate();
	      if (selectedDate === null) {
	        this.$emit('onCancel');
	      } else {
	        const localTime = new Date(selectedDate.toLocaleString('en-US', {
	          timeZone: 'UTC'
	        }));
	        this.$emit('onSave', localTime);
	      }
	    },
	    cancel() {
	      this.$emit('onCancel');
	    }
	  },
	  template: `
		<div class="booking-drum">
			<p class="booking-drum-header">
				{{ loc('BOOKING_COMPONENT_DRUM_HEADER') }}
			</p>
			<div ref="datepicker" class="booking-drum-datepicker"></div>
			<div class="booking-drum-controls">
				<div class="booking-drum-controls-btn-save">
					<Button
						:text="loc('BOOKING_COMPONENT_DRUM_BTN_SAVE')"
						:size="ButtonSize.EXTRA_SMALL"
						:color="ButtonColor.PRIMARY"
						:round="true"
						@click="save"
					/>
				</div>
				<div class="booking-drum-controls-btn-cancel">
					<Button
						:text="loc('BOOKING_COMPONENT_DRUM_BTN_CANCEL')"
						:size="ButtonSize.EXTRA_SMALL"
						:color="ButtonColor.LINK"
						:round="true"
						@click="cancel"
					/>
				</div>
			</div>
		</div>
	`
	};

	exports.Drum = Drum;

}((this.BX.Booking.Component = this.BX.Booking.Component || {}),BX.UI.DatePicker,BX.Booking.Component));
//# sourceMappingURL=drum.bundle.js.map
