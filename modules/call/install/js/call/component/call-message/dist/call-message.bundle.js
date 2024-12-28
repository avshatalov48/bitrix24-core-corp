/* eslint-disable */
this.BX = this.BX || {};
this.BX.Call = this.BX.Call || {};
(function (exports,ui_vue3_directives_hint,main_core,im_v2_component_message_elements,im_v2_component_message_base,im_v2_lib_dateFormatter,im_v2_lib_call,im_public,im_v2_const,call_lib_analytics) {
	'use strict';

	const MESSAGE_TYPE = {
	  start: 'START',
	  finish: 'FINISH',
	  declined: 'DECLINED',
	  busy: 'BUSY',
	  missed: 'MISSED'
	};

	// @vue/component
	const CallMessage = {
	  name: 'CallMessage',
	  components: {
	    BaseMessage: im_v2_component_message_base.BaseMessage,
	    MessageHeader: im_v2_component_message_elements.MessageHeader
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
	    },
	    withTitle: {
	      type: Boolean,
	      default: true
	    }
	  },
	  data() {
	    return {
	      showHint: false
	    };
	  },
	  created() {
	    this.hintTimeout = null;
	  },
	  computed: {
	    message() {
	      return this.item;
	    },
	    componentParams() {
	      return this.item.componentParams;
	    },
	    messageIconClasses() {
	      const result = ['bx-call-message__icon'];
	      switch (this.componentParams.messageType) {
	        case MESSAGE_TYPE.start:
	          result.push('bx-call-message__icon--secondary');
	          break;
	        case MESSAGE_TYPE.finish:
	          result.push('bx-call-message__icon--primary');
	          break;
	        case MESSAGE_TYPE.declined:
	        case MESSAGE_TYPE.busy:
	        case MESSAGE_TYPE.missed:
	          result.push('bx-call-message__icon--danger');
	          break;
	        default:
	          result.push('bx-call-message__icon--secondary');
	          break;
	      }
	      return result;
	    },
	    messageText() {
	      return this.componentParams.messageText;
	    },
	    formattedDate() {
	      return im_v2_lib_dateFormatter.DateFormatter.formatByCode(this.message.date, im_v2_lib_dateFormatter.DateCode.shortTimeFormat);
	    },
	    currentCall() {
	      return im_v2_lib_call.CallManager.getInstance().getCurrentCall();
	    },
	    hasActiveCurrentCall() {
	      return im_v2_lib_call.CallManager.getInstance().hasActiveCurrentCall(this.dialogId);
	    },
	    hasActiveAnotherCall() {
	      return im_v2_lib_call.CallManager.getInstance().hasActiveAnotherCall(this.dialogId);
	    },
	    dialog() {
	      return this.$store.getters['chats/get'](this.dialogId, true);
	    },
	    isConference() {
	      return this.dialog.type === im_v2_const.ChatType.videoconf;
	    },
	    hintContent() {
	      if (!this.showHint) {
	        return null;
	      }
	      return {
	        text: this.loc('CALL_MESSAGE_HAS_ACTIVE_CALL_HINT'),
	        popupOptions: {
	          bindOptions: {
	            position: 'top'
	          },
	          angle: {
	            position: 'bottom'
	          },
	          targetContainer: document.body,
	          offsetLeft: 65,
	          offsetTop: 0
	        }
	      };
	    }
	  },
	  methods: {
	    loc(phraseCode, replacements = {}) {
	      return main_core.Loc.getMessage(phraseCode, replacements);
	    },
	    onMessageClick() {
	      if (this.hasActiveAnotherCall) {
	        this.showHint = true;
	        clearTimeout(this.hintTimeout);
	        this.hintTimeout = setTimeout(() => this.showHint = false, 10000);
	        return;
	      }
	      this.componentParams.messageType === MESSAGE_TYPE.start ? call_lib_analytics.Analytics.getInstance().onStartCallMessageClick({
	        dialog: this.dialog
	      }) : call_lib_analytics.Analytics.getInstance().onFinishCallMessageClick({
	        dialog: this.dialog
	      });
	      this.isConference ? im_public.Messenger.openConference({
	        code: this.dialog.public.code
	      }) : im_public.Messenger.startVideoCall(this.dialogId);
	    }
	  },
	  template: `
		<BaseMessage
			:dialogId="dialogId"
			:item="item"
			:withContextMenu="false"
			:withBackground="true"
			:withReactions="false"
			class="bx-call-message__scope"
		>
			<div class="bx-call-message__container">
				<div class="bx-call-message__content-wrapper">
					<MessageHeader :withTitle="withTitle" :item="item" />
					<div :key="showHint" class="bx-call-message__content" v-hint="hintContent" @click="onMessageClick">
						<div :class="messageIconClasses"></div>
						<div class="bx-call-message__text-container">
							<div class="bx-call-message__text">{{ messageText }}</div>
							<div class="bx-im-message-status__date bx-call-message__date">
								{{ formattedDate }}
							</div>
						</div>
					</div>
				</div>
			</div>
		</BaseMessage>
	`
	};

	exports.CallMessage = CallMessage;

}((this.BX.Call.Component = this.BX.Call.Component || {}),BX.Vue3.Directives,BX,BX.Messenger.v2.Component.Message,BX.Messenger.v2.Component.Message,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Messenger.v2.Const,BX.Call.Lib));
//# sourceMappingURL=call-message.bundle.js.map
