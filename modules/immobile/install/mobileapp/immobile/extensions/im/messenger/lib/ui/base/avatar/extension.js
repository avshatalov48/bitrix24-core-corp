/**
 * @module im/messenger/lib/ui/base/avatar
 */
jn.define('im/messenger/lib/ui/base/avatar', (require, exports, module) => {
	const { avatarStyle } = require('im/messenger/lib/ui/base/avatar/style');

	class Avatar extends LayoutComponent
	{
		/**
		 *
		 * @param { Object } props
		 * @param { string } props.uri
		 * @param { object } props.svg
		 * @param { string } props.color
		 * @param { string } props.text
		 * @param { string } props.size 'L','M','XL'. Default 'M'
		 */
		constructor(props)
		{
			super(props);
			this.state.showImageAvatar = !!props.svg || (!!props.uri && props.uri !== '/bitrix/js/im/images/blank.gif');
		}

		componentWillReceiveProps(props)
		{
			this.state.showImageAvatar = !!props.svg || (!!props.uri && props.uri !== '/bitrix/js/im/images/blank.gif');
		}

		/**
		 *
		 * @param {string} text
		 * @return {string}
		 */
		getFirstLetters(text)
		{
			const specialSymbolsPattern = /[!"#$%&'()*,./:;<>?@[\\\]^`{|}~-]/;
			let initials = '';
			const words = text.split(/[\s,]/);
			for (const word of words)
			{
				if (initials.length === 2)
				{
					return initials;
				}

				for (const letter of word)
				{
					if (
						!specialSymbolsPattern.test(letter)
						&& !WordSeparator.hasEmojiWord(letter)
					)
					{
						initials += letter;
						break;
					}
				}
			}

			return initials;
		}

		render()
		{
			const style = avatarStyle[this.props.size] || avatarStyle.M;
			const uri = this.props.uri ? this.props.uri : null;

			return View(
				{
					style: {
						justifyContent: style.justifyContent,
					},
				},
				this.state.showImageAvatar
					? Image({
						style: style.icon,
						uri,
						svg: this.props.svg,
						onFailure: () => {
							this.setState({ showImageAvatar: false });
						},
					})
					: View(
						{
							style: {
								backgroundColor: this.props.color || '#aa79dc',
								width: style.defaultIcon.width,
								height: style.defaultIcon.height,
								borderRadius: style.defaultIcon.borderRadius,
								alignContent: style.defaultIcon.alignContent,
								justifyContent: style.defaultIcon.justifyContent,
								marginBottom: style.defaultIcon.marginBottom,
							},
						},
						Text(
							{
								style: {
									alignSelf: style.defaultIcon.text.alignSelf,
									color: style.defaultIcon.text.color,
									fontSize: style.defaultIcon.text.fontSize,
								},
								text: this.getFirstLetters(this.props.text).toUpperCase(),
							},
						),
					),
			);
		}
	}

	module.exports = { Avatar };
});
