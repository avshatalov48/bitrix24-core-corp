/**
 * @module call/calls-card/card-content/elements
 */
jn.define('call/calls-card/card-content/elements', (require, exports, module) => {
	const { Overlay } = require('call/calls-card/card-content/elements/overlay');
	const { CollapseButton } = require('call/calls-card/card-content/elements/collapse-button');
	const { Avatar } = require('call/calls-card/card-content/elements/avatar');
	const { Status } = require('call/calls-card/card-content/elements/status');
	const { CrmButton } = require('call/calls-card/card-content/elements/crm-button');
	const { Button }  = require('call/calls-card/card-content/elements/button');
	const { Timer } = require('call/calls-card/card-content/elements/timer');
	const { CallControls } = require('call/calls-card/card-content/elements/call-controls');

	module.exports = {
		Overlay,
		CollapseButton,
		Avatar,
		Status,
		CrmButton,
		Button,
		Timer,
		CallControls,
	};
});