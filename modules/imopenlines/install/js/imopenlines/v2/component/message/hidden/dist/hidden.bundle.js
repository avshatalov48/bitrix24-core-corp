/* eslint-disable */
this.BX = this.BX || {};
this.BX.OpenLines = this.BX.OpenLines || {};
this.BX.OpenLines.v2 = this.BX.OpenLines.v2 || {};
this.BX.OpenLines.v2.Component = this.BX.OpenLines.v2.Component || {};
(function (exports,ui_vue3_directives_hint,im_v2_component_message_base,im_v2_lib_dateFormatter,im_v2_component_message_elements,im_v2_lib_parser) {
	'use strict';

	// @vue/component
	const HiddenMessage = {
	  name: 'HiddenMessage',
	  components: {
	    BaseMessage: im_v2_component_message_base.BaseMessage,
	    MessageAttach: im_v2_component_message_elements.MessageAttach,
	    ReactionList: im_v2_component_message_elements.ReactionList
	  },
	  directives: {
	    hint: ui_vue3_directives_hint.hint
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
	  computed: {
	    message() {
	      return this.item;
	    },
	    formattedText() {
	      return im_v2_lib_parser.Parser.decodeMessage(this.item);
	    },
	    formattedDate() {
	      return im_v2_lib_dateFormatter.DateFormatter.formatByCode(this.message.date, im_v2_lib_dateFormatter.DateCode.shortTimeFormat);
	    },
	    hintAvailable() {
	      return {
	        text: this.loc('IMOL_MESSAGE_HIDDEN_TOOLTIP_TEXT'),
	        popupOptions: {
	          angle: true,
	          targetContainer: document.body,
	          offsetTop: -10,
	          offsetLeft: 5,
	          bindOptions: {
	            position: 'top'
	          }
	        }
	      };
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
			:withContextMenu="false"
			:withReactions="false"
			class="bx-imol-message-hidden__container"
		>
			<div class="bx-imol-message-hidden__content">
				<div class="bx-imol-message-hidden-content__text" v-html="formattedText"></div>
				<div v-if="message.attach.length > 0" class="bx-imol-message-hidden-content__attach">
					<MessageAttach :item="message" :dialogId="dialogId" />
				</div>
				<div class="bx-imol-message-hidden-content__bottom-panel">
					<div class="bx-imol-message-hidden-content__container-date">
						<div class="bx-imol-message-hidden-content__date">
							{{ formattedDate }}
						</div>
						<div
							v-hint="hintAvailable"
							class="bx-imol-message-hidden-content__lock"
						>
							<i class="fa-solid fa-lock"></i>
						</div>
					</div>
				</div>
			</div>
		</BaseMessage>
	`
	};

	exports.HiddenMessage = HiddenMessage;

}((this.BX.OpenLines.v2.Component.Message = this.BX.OpenLines.v2.Component.Message || {}),BX.Vue3.Directives,BX.Messenger.v2.Component.Message,BX.Messenger.v2.Lib,BX.Messenger.v2.Component.Message,BX.Messenger.v2.Lib));
//# sourceMappingURL=hidden.bundle.js.map
