/**
 * @module im/messenger/lib/ui/search/input
 */
jn.define('im/messenger/lib/ui/search/input', (require, exports, module) => {

	const { lens, cross } = require('im/messenger/assets/common');
	const { Loc } = require('loc');

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
						backgroundColor: '#e6e7e9',
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
						placeholderTextColor: '#8e8d92',
						multiline: false,
						style: {
							color: '#333333',
							fontSize: 18,
							backgroundColor: '#00000000',
						},
						onChangeText: (text) => {
							if (text !== '')
							{
								this.setState({isTextEmpty: false});
							}
							if (text === '')
							{
								this.setState({isTextEmpty: true});
							}
							this.props.onChangeText(text);
						},
						onSubmitEditing: () => this.textRef.blur(),
						onFocus: () => this.props.onSearchShow(),
						ref: ref => this.textRef = ref,
					})
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