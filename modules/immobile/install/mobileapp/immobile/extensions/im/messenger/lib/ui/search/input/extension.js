/**
 * @module im/messenger/lib/ui/search/input
 */
jn.define('im/messenger/lib/ui/search/input', (require, exports, module) => {
	const { lens, cross } = require('im/messenger/assets/common');
	const { Loc } = require('loc');
	const { Theme } = require('im/lib/theme');
	const { transparent } = require('utils/color');

	class SearchInput extends LayoutComponent
	{
		/**
		 *
		 * @param {Object} props
		 * @param {Function} props.onChangeText
		 * @param {Function} props.onSearchShow
		 * @param {Function} [props.ref]
		 */
		constructor(props = {})
		{
			super(props);
			this.value = '';
			this.state.isTextEmpty = true;

			if (props.ref)
			{
				props.ref(this);
			}
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: transparent(Theme.colors.base5, 0.25),
						flexDirection: 'row',
						padding: 8,
						borderRadius: 8,
						alignItems: 'center',
						justifyContent: 'space-between',
						flexGrow: 1,
					},
				},
				View(
					{},
					Image({
						style: {
							height: 21,
							width: 21,
						},
						resizeMode: 'contain',
						svg: {
							content: lens(),
						},
					}),
				),
				View(
					{
						style: {
							flexGrow: 2,
							paddingLeft: 5,
							paddingRight: 5,
						},
					},
					TextInput({
						testId: 'search_field',
						placeholder: Loc.getMessage('IMMOBILE_MESSENGER_UI_SEARCH_INPUT_PLACEHOLDER_TEXT'),
						placeholderTextColor: Theme.colors.base4,
						multiline: false,
						style: {
							color: Theme.colors.base1,
							fontSize: 18,
							backgroundColor: '#00000000',
						},
						onChangeText: (text) => {
							if (text !== '' && this.state.isTextEmpty)
							{
								this.setState({ isTextEmpty: false });
							}

							if (text === '' && !this.state.isTextEmpty)
							{
								this.setState({ isTextEmpty: true });
							}

							clearTimeout(this.timeout);
							this.timeout = setTimeout(() => {
								this.props.onChangeText(text);
							}, 200);
						},
						onSubmitEditing: () => this.textRef.blur(),
						onFocus: () => this.props.onSearchShow(),
						ref: (ref) => this.textRef = ref,
					}),
				),
				View(
					{
						testId: 'search_field_clear',
						clickable: true,
						onClick: () => {
							this.textRef.clear();
						},
					},
					Image({
						style: {
							height: 26,
							width: 26,
							opacity: this.state.isTextEmpty ? 0 : 1,
						},
						resizeMode: 'contain',
						svg: {
							content: cross({ strokeWight: 0 }),
						},
					}),
				),

			);
		}
	}

	module.exports = { SearchInput };
});
