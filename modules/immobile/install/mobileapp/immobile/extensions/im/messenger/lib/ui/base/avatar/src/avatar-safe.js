/**
 * @module im/messenger/lib/ui/base/avatar/avatar-safe
 */
jn.define('im/messenger/lib/ui/base/avatar/avatar-safe', (require, exports, module) => {
	/* global WordSeparator */
	const { avatarStyle } = require('im/messenger/lib/ui/base/avatar/style');
	const { clone } = require('utils/object');

	class AvatarSafe extends LayoutComponent
	{
		/**
		 *
		 * @param { Object } props
		 * @param { string } props.uri
		 * @param { object } props.svg
		 * @param { string } props.color
		 * @param { string } props.text
		 * @param { string } props.size 'L','M','XL'. Default 'M'
		 * @param { boolean | undefined } props.isSuperEllipse
		 */
		constructor(props)
		{
			super(props);
			this.state.showImageAvatar = !!props.svg || (!!props.uri && props.uri !== '/bitrix/js/im/images/blank.gif');
			this.state.waitingLoadImage = false;

			if (this.state.showImageAvatar)
			{
				this.state.waitingLoadImage = true;
			}
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
			const style = clone(avatarStyle[this.props.size] || avatarStyle.M);
			const uri = this.props.uri ?? null;

			let defaultIconBorderRadius = style.defaultIcon.borderRadius;
			if (this.props.isSuperEllipse ?? false)
			{
				defaultIconBorderRadius = style.defaultIcon.squareBorderRadius;
				style.icon.borderRadius = style.icon.squareBorderRadius;
			}

			return View(
				{
					style: {
						justifyContent: style.justifyContent,
					},
				},
				Image({
					style: { ...style.icon, display: (this.state.showImageAvatar && !this.state.waitingLoadImage) ? 'flex' : 'none' },
					uri,
					svg: this.props.svg,
					onFailure: () => {
						this.setState({ showImageAvatar: false, waitingLoadImage: false });
					},
					onSuccess: () => {
						this.setState({ showImageAvatar: true, waitingLoadImage: false });
					},
				}),
				this.renderWordAvatar(style, defaultIconBorderRadius),
			);
		}

		renderWordAvatar(style, defaultIconBorderRadius)
		{
			if (this.state.showImageAvatar && !this.state.waitingLoadImage)
			{
				return null;
			}

			return View(
				{
					style: {
						backgroundColor: this.props.color || '#aa79dc',
						width: style.defaultIcon.width,
						height: style.defaultIcon.height,
						borderRadius: defaultIconBorderRadius,
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
						text: this.getFirstLetters(this.props.text)
							.toUpperCase(),
					},
				),
			);
		}
	}

	module.exports = { AvatarSafe };
});
