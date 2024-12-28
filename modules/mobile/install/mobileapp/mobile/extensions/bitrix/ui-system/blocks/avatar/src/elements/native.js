/**
 * @module ui-system/blocks/avatar/src/elements/native
 */
jn.define('ui-system/blocks/avatar/src/elements/native', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Icon } = require('assets/icons');
	const { isEmpty } = require('utils/object');
	const { AvatarBase } = require('ui-system/blocks/avatar/src/elements/base');

	/**
	 * @class AvatarNative
	 */
	class AvatarNative extends AvatarBase
	{
		render()
		{
			if (this.shouldRenderOutline())
			{
				return this.renderOutlineWrapper(this.renderAvatar());
			}

			return this.renderAvatar();
		}

		renderAvatar()
		{
			return Avatar(this.getAvatarProps());
		}

		getAvatarProps()
		{
			return {
				ref: this.handleForwardRef,
				onAvatarClick: this.handleOnClick,
				onUriLoadFailure: () => {
					console.error('Avatar image loading failed');
				},
				...this.getAvatarNativeProps(),
			};
		}

		getAvatarNativeProps()
		{
			return {
				testId: this.getTestId(),
				title: this.getUserName(),
				uri: this.getUri(),
				type: this.getShape().getValue(),
				radius: this.getBorderRadius(),
				placeholder: this.getPlaceholder(),
				backBorderWidth: this.getBackBorderWidth(),
				style: this.getContainerStyle(),
				hideOutline: !this.isAccent(),
				...this.getAccent(),
			};
		}

		getAccent()
		{
			const accent = {};

			if (!this.isAccent())
			{
				return accent;
			}

			accent.accentType = this.getAccentType();

			return accent;
		}

		getPlaceholder()
		{
			const placeholderType = this.getPlaceholderType();
			const placeholderParams = {
				type: placeholderType.getValue(),
				...this.getPlaceholderBackgroundColorParams(),
			};

			if (placeholderType.isSvg())
			{
				placeholderParams.svg = this.getPlaceholderSvgParams();
			}

			return placeholderParams;
		}

		getPlaceholderSvgParams()
		{
			const icon = this.getIcon();
			let placeholderSvgParams = {};

			if (icon)
			{
				placeholderSvgParams = this.getIconParams();
			}
			else
			{
				placeholderSvgParams = {
					uri: this.getEmptyAvatar(),
				};
			}

			return placeholderSvgParams;
		}

		getIconParams()
		{
			const icon = this.getIcon();

			if (isEmpty(icon.props))
			{
				return null;
			}

			const { color, icon: iconEnum, size } = icon.props;

			if (!Icon.hasIcon(iconEnum))
			{
				return null;
			}

			const tintColor = Color.resolve(color, Color.base0);

			return {
				size,
				tintColor: tintColor.toHex(),
				named: iconEnum.getIconName(),
			};
		}

		getAccentColorGradient()
		{
			const accentGradients = super.getAccentColorGradient();
			const start = accentGradients[0];
			const middle = accentGradients[1] || start;
			const end = accentGradients[2] || middle;

			return { start, middle, end };
		}

		getAccentType()
		{
			return this.getAvatarAccentGradient().getName().toLowerCase();
		}
	}

	module.exports = {
		/**
		 * @param {AvatarBaseProps} props
		 */
		AvatarNative: (props) => new AvatarNative(props),
		AvatarNativeClass: AvatarNative,
	};
});
