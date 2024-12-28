/* eslint-disable */
this.BX = this.BX || {};
this.BX.OpenLines = this.BX.OpenLines || {};
this.BX.OpenLines.v2 = this.BX.OpenLines.v2 || {};
this.BX.OpenLines.v2.Component = this.BX.OpenLines.v2.Component || {};
(function (exports,im_v2_component_message_base) {
	'use strict';

	// @vue/component
	const FeedbackFormMessage = {
	  name: 'FeedbackForm',
	  components: {
	    BaseMessage: im_v2_component_message_base.BaseMessage
	  },
	  props: {
	    item: {
	      type: Object,
	      required: true
	    },
	    dialogId: {
	      type: String,
	      required: true
	    }
	  },
	  methods: {
	    loc(phraseCode) {
	      return this.$Bitrix.Loc.getMessage(phraseCode);
	    }
	  },
	  template: `
		<BaseMessage
			:dialogId="dialogId"
			:item="item"
			:withTitle="false"
			:withBackground="false"
			class="bx-imol-message-feedback-form__container"
		>
			<div class="bx-imol-message-feedback-form__text">
				{{ loc('IMOL_MESSAGE_FEEDBACK_FORM_TEXT') }}
			</div>
		</BaseMessage>
	`
	};

	exports.FeedbackFormMessage = FeedbackFormMessage;

}((this.BX.OpenLines.v2.Component.Message = this.BX.OpenLines.v2.Component.Message || {}),BX.Messenger.v2.Component.Message));
//# sourceMappingURL=feedback-form.bundle.js.map
