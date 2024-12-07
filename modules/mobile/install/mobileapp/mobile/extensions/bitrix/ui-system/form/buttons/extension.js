/**
 * @module ui-system/form/buttons
 */
jn.define('ui-system/form/buttons', (require, exports, module) => {
	const { Button, ButtonDesign, ButtonSize, IconTypes, Icon } = require('ui-system/form/buttons/button');
	const { FloatingActionButton, FloatingActionButtonMode } = require('ui-system/form/buttons/floating-action-button');

	module.exports = {
		Button,
		Icon,
		ButtonDesign,
		ButtonSize,
		IconTypes,
		FloatingActionButton,
		FloatingActionButtonMode,
	};
});
