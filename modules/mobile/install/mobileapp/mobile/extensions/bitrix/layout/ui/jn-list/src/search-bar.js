/**
 * @module layout/ui/jn-list/src/search-bar
 */
jn.define('layout/ui/jn-list/src/search-bar', (require, exports, module) => {
	const { transparent } = require('utils/color');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Color, Indent, Corner } = require('tokens');
	const { Loc } = require('loc');
	const { TextField } = require('ui-system/typography/text-field');

	/**
	 * @typedef {Object} JnListSearchBarProps
	 * @property {string} testId
	 * @property {string} searchInputPlaceholder
	 * @property {string} searchString
	 * @property {function} onSearchTextFieldChange
	 * @property {function} onSearchTextFieldSubmit
	 * @property {function} onClearSearchButtonClick

	 * @class JnListSearchBar
	 */
	class JnListSearchBar extends LayoutComponent
	{
		getTestId(suffix)
		{
			const { testId } = this.props;

			return [testId, suffix].join('-').trim();
		}

		render()
		{
			const {
				searchInputPlaceholder = Loc.getMessage('JNLIST_SEARCH_INPUT_PLACEHOLDER'),
				searchString,
				onSearchTextFieldChange,
				onSearchTextFieldSubmit,
				onClearSearchButtonClick,
			} = this.props;
			const isClearButtonVisible = searchString !== '';

			return View(
				{
					testId: this.getTestId('container'),
					style: {
						width: '100%',
						paddingHorizontal: Indent.XL2.toNumber(),
						paddingVertical: Indent.XS.toNumber(),
					},
				},
				View(
					{
						style: {
							backgroundColor: transparent(Color.base5.toHex(), 0.25),
							height: 36,
							borderRadius: Corner.M.toNumber(),
							width: '100%',
							flexDirection: 'row',
							paddingVertical: Indent.M.toNumber(),
							paddingHorizontal: Indent.L.toNumber(),
							alignItems: 'center',
						},
					},
					IconView({
						size: 22,
						color: Color.base4,
						icon: Icon.SEARCH,
						style: {
							marginRight: Indent.S.toNumber(),
						},
					}),
					TextField({
						testId: this.getTestId('text-field'),
						placeholder: searchInputPlaceholder,
						placeholderTextColor: Color.base4.toHex(),
						size: 4,
						color: Color.base1,
						style: {
							flex: 1,
						},
						value: searchString,
						onChangeText: onSearchTextFieldChange,
						onSubmitEditing: onSearchTextFieldSubmit,
					}),
					isClearButtonVisible && IconView({
						testId: this.getTestId('clear-button'),
						size: 22,
						color: Color.base4,
						icon: Icon.CROSS,
						onClick: onClearSearchButtonClick,
					}),
				),
			);
		}
	}

	module.exports = {
		/**
		 * @param {JnListSearchBarProps} props
		 * @returns {JnListSearchBar}
		 */
		JnListSearchBar: (props) => new JnListSearchBar(props),
	};
});
