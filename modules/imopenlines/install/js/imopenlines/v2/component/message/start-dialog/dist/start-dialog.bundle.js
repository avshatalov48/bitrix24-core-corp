/* eslint-disable */
this.BX = this.BX || {};
this.BX.OpenLines = this.BX.OpenLines || {};
this.BX.OpenLines.v2 = this.BX.OpenLines.v2 || {};
this.BX.OpenLines.v2.Component = this.BX.OpenLines.v2.Component || {};
(function (exports,im_v2_lib_parser,im_v2_component_message_base) {
	'use strict';

	// @vue/component
	const StartDialogMessage = {
	  name: 'StartDialogMessage',
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
	  computed: {
	    formattedText() {
	      return im_v2_lib_parser.Parser.decodeMessage(this.item);
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
			class="bx-imol-message-start-dialog__container"
		>
			<div class="bx-imol-message-start-dialog__text" v-html="formattedText"></div>
		</BaseMessage>
	`
	};

	exports.StartDialogMessage = StartDialogMessage;

}((this.BX.OpenLines.v2.Component.Message = this.BX.OpenLines.v2.Component.Message || {}),BX.Messenger.v2.Lib,BX.Messenger.v2.Component.Message));
//# sourceMappingURL=start-dialog.bundle.js.map
