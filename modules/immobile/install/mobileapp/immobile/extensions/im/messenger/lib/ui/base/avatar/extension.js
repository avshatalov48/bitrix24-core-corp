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
		 * @param { string } props.color
		 * @param { string } props.text
		 * @param { string } props.size 'L' or 'M'. Default 'M'
		 */
		constructor(props)
		{
			super(props);
			this.state.showImageAvatar = !!props.uri;
		}

		componentWillReceiveProps(props)
		{
			this.state.showImageAvatar = !!props.uri;
		}

		/**
		 *
		 * @param {string} text
		 * @return {string}
		 */
		getFirstLetters(text)
		{
			const specialSymbolsPattern = /[#$%^&*@!\[\],.<>`'"~?|\\\/;:(){}-]/;
			let initials = '';
			const words = text.split(/[\s,]/);
			for (let word of words)
			{
				if (initials.length === 2)
				{
					return initials;
				}
				for (let letter of word)
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
			const style = this.props.size === 'L' ? avatarStyle.large : avatarStyle.medium
			return View(
				{
					style: {
						justifyContent: style.justifyContent
					}
				},
				this.state.showImageAvatar
					?
					Image({
						style: style.icon,
						uri: this.props.uri,
						onFailure: () => {
							this.setState({showImageAvatar: false});
						}
					})
					:
					View(
						{
							style: {
								backgroundColor: this.props.color,
								width: style.defaultIcon.width,
								height: style.defaultIcon.height,
								borderRadius: style.defaultIcon.borderRadius,
								alignContent: style.defaultIcon.alignContent,
								justifyContent: style.defaultIcon.justifyContent,
								marginBottom: style.defaultIcon.marginBottom
							}
						},
						Text(
							{
								style: {
									alignSelf: style.defaultIcon.text.alignSelf,
									color: style.defaultIcon.text.color,
									fontSize: style.defaultIcon.text.fontSize,
								},
								text: this.getFirstLetters(this.props.text).toUpperCase(),
							}
						)
					)
			)
		}
	}

	module.exports = { Avatar };
});