/**
 * @module im/calls-card/card-content/elements
 */
jn.define('im/calls-card/card-content/elements', (require, exports, module) => {
	const { Overlay } = require('im/calls-card/card-content/elements/overlay');
	const { CollapseButton } = require('im/calls-card/card-content/elements/collapse-button');
	const { Avatar } = require('im/calls-card/card-content/elements/avatar');
	const { Status } = require('im/calls-card/card-content/elements/status');
	const { CrmButton } = require('im/calls-card/card-content/elements/crm-button');
	const { Button }  = require('im/calls-card/card-content/elements/button');
	const { Timer } = require('im/calls-card/card-content/elements/timer');
	const { CallControls } = require('im/calls-card/card-content/elements/call-controls');

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