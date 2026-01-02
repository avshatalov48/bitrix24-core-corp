/**
 * @module im/messenger/controller/selector/dialog/opener
 */
jn.define('im/messenger/controller/selector/dialog/opener', (require, exports, module) => {
	const { EntitySelectorWidget } = require('selector/widget');
	const { DialogSelectorProvider } = require('im/messenger/controller/selector/dialog/provider');
	const { Loc } = require('im/messenger/loc');

	/**
	 * @param {Object} options
	 * @param {string} options.title
	 * @param {Object} [options.providerOptions]
	 * @param {boolean} [options.allowMultipleSelection]
	 * @param {boolean} [options.integrateSelectorToParentLayout=false]
	 * @param {Function} [options.onItemSelected]
	 * @param {Function} [options.onClose]
	 * @param {boolean} [options.closeOnSelect=true]
	 * @param {PageManager} [parentWidget]
	 */
	function openDialogSelector({
		title,
		providerOptions,
		allowMultipleSelection = false,
		integrateSelectorToParentLayout = false,
		closeOnSelect = true,
		onItemSelected,
		onClose,
		initSelectedIds,
	}, parentWidget)
	{
		const entitySelectorWidget = new EntitySelectorWidget({
			widgetParams: {
				titleParams: {
					text: title ?? Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_SELECTOR_DEFAULT_TITLE'),
					type: 'dialog',
				},
				backdrop: {
					mediumPositionPercent: 85,
					horizontalSwipeAllowed: false,
					onlyMediumPosition: true,
				},
			},
			events: {
				onItemSelected,
				onClose,
			},
			provider: {
				class: DialogSelectorProvider,
				options: providerOptions ?? {},
			},
			sectionTitles: {
				recent: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_SELECTOR_SEARCH_PLACEHOLDER'),
			},
			entityIds: ['dialog'],
			initSelectedIds,
			allowMultipleSelection,
			closeOnSelect,
			integrateSelectorToParentLayout,
		});

		return entitySelectorWidget.show({}, parentWidget);
	}

	module.exports = { openDialogSelector };
});
