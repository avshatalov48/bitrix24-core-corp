/* eslint-disable */
this.BX = this.BX || {};
this.BX.Booking = this.BX.Booking || {};
(function (exports,main_core,main_popup,ui_vue3,booking_lib_resolvable,booking_component_popup,booking_component_button) {
	'use strict';

	const NotePopup = {
	  name: 'NotePopup',
	  emits: ['save', 'close'],
	  props: {
	    id: {
	      type: [Number, String],
	      required: true
	    },
	    text: {
	      type: String,
	      default: ''
	    },
	    bindElement: {
	      type: Function,
	      required: true
	    },
	    isEditMode: {
	      type: Boolean,
	      required: true
	    },
	    dataId: {
	      type: [String, Number],
	      default: ''
	    },
	    dataElementPrefix: {
	      type: String,
	      default: ''
	    }
	  },
	  setup() {
	    const buttonSize = booking_component_button.ButtonSize;
	    const buttonColor = booking_component_button.ButtonColor;
	    const note = ui_vue3.ref('');
	    const mountedPromise = new booking_lib_resolvable.Resolvable();
	    return {
	      buttonColor,
	      buttonSize,
	      mountedPromise,
	      note
	    };
	  },
	  beforeCreate() {
	    const popupId = `booking-booking-note-popup-${this.id}`;
	    main_popup.PopupManager.getPopups().filter(popup => popup.getId() === popupId).forEach(popup => popup.destroy());
	  },
	  created() {
	    this.note = this.text;
	  },
	  mounted() {
	    this.mountedPromise.resolve();
	    this.adjustPosition();
	    this.focusOnTextarea();
	    main_core.Event.bind(document, 'scroll', this.adjustPosition, true);
	  },
	  beforeUnmount() {
	    main_core.Event.unbind(document, 'scroll', this.adjustPosition, true);
	  },
	  computed: {
	    popupId() {
	      return `booking-booking-note-popup-${this.id}`;
	    },
	    config() {
	      return {
	        className: 'booking-booking-note-popup',
	        bindElement: this.bindElement(),
	        minWidth: this.bindElement().offsetWidth,
	        height: 120,
	        offsetTop: -10,
	        background: 'var(--ui-color-background-note)',
	        bindOptions: {
	          forceBindPosition: true,
	          position: 'top'
	        },
	        autoHide: this.isEditMode
	      };
	    }
	  },
	  methods: {
	    closePopup() {
	      this.$emit('close');
	    },
	    saveNote() {
	      if (this.text !== this.note) {
	        this.$emit('save', {
	          id: this.id,
	          note: this.note
	        });
	      }
	      this.closePopup();
	    },
	    onMouseDown() {
	      main_core.Event.unbind(window, 'mouseup', this.onMouseUp);
	      main_core.Event.bind(window, 'mouseup', this.onMouseUp);
	      this.setAutoHide(false);
	    },
	    onMouseUp() {
	      main_core.Event.unbind(window, 'mouseup', this.onMouseUp);
	      setTimeout(() => this.setAutoHide(this.isEditMode), 0);
	    },
	    setAutoHide(autoHide) {
	      var _this$$refs$popup, _this$$refs$popup$get;
	      (_this$$refs$popup = this.$refs.popup) == null ? void 0 : (_this$$refs$popup$get = _this$$refs$popup.getPopupInstance()) == null ? void 0 : _this$$refs$popup$get.setAutoHide(autoHide);
	    },
	    adjustPosition() {
	      this.$refs.popup.adjustPosition();
	    },
	    focusOnTextarea() {
	      setTimeout(() => {
	        if (this.isEditMode) {
	          this.$refs.textarea.focus();
	        }
	      }, 0);
	    }
	  },
	  watch: {
	    isEditMode(isEditMode) {
	      this.setAutoHide(isEditMode);
	      this.focusOnTextarea();
	    },
	    async note() {
	      await this.mountedPromise;
	      this.$refs.popup.getPopupInstance().setHeight(0);
	      const minHeight = 120;
	      const maxHeight = 280;
	      const height = this.$refs.textarea.scrollHeight + 45;
	      const popupHeight = Math.min(maxHeight, Math.max(minHeight, height));
	      this.$refs.popup.getPopupInstance().setHeight(popupHeight);
	      this.adjustPosition();
	    }
	  },
	  components: {
	    Button: booking_component_button.Button,
	    Popup: booking_component_popup.Popup
	  },
	  template: `
		<Popup
			:id="popupId"
			:config="config"
			ref="popup"
			@close="closePopup"
		>
			<div
				class="booking-booking-note-popup-content"
				:data-element="dataElementPrefix + '-note-popup'"
				:data-id="dataId"
				@mousedown="onMouseDown"
			>
				<div
					class="booking-booking-note-popup-title"
					:data-element="dataElementPrefix + '-note-popup-title'"
					:data-id="dataId"
				>
					{{ loc('BOOKING_BOOKING_NOTE_TITLE') }}
				</div>
				<textarea
					v-model.trim="note"
					class="booking-booking-note-popup-textarea"
					:placeholder="loc('BOOKING_BOOKING_NOTE_HINT')"
					:disabled="!isEditMode"
					:data-element="dataElementPrefix + '-note-popup-textarea'"
					:data-id="dataId"
					:data-disabled="!isEditMode"
					ref="textarea"
				></textarea>
				<div v-if="isEditMode" class="booking-booking-note-popup-buttons">
					<slot name="buttons">
						<Button
							:dataset="{id: dataId, element: dataElementPrefix + '-note-popup-save'}"
							:text="loc('BOOKING_BOOKING_NOTE_SAVE')"
							:size="buttonSize.EXTRA_SMALL"
							:color="buttonColor.PRIMARY"
							@click="saveNote"
						/>
						<Button
							:dataset="{id: dataId, element: dataElementPrefix + '-note-popup-cancel'}"
							:text="loc('BOOKING_BOOKING_NOTE_CANCEL')"
							:size="buttonSize.EXTRA_SMALL"
							:color="buttonColor.LINK"
							@click="closePopup"
						/>
					</slot>
				</div>
			</div>
		</Popup>
	`
	};

	exports.NotePopup = NotePopup;

}((this.BX.Booking.Component = this.BX.Booking.Component || {}),BX,BX.Main,BX.Vue3,BX.Booking.Lib,BX.Booking.Component,BX.Booking.Component));
//# sourceMappingURL=note-popup.bundle.js.map
