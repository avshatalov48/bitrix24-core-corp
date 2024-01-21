/**
 * @module layout/ui/fields/web
 */
jn.define('layout/ui/fields/web', (require, exports, module) => {
	const { StringFieldClass } = require('layout/ui/fields/string');
	const { SafeImage } = require('layout/ui/safe-image');
	const { isValidLink, URL } = require('utils/url');
	const { set } = require('utils/object');
	const AppTheme = require('apptheme');

	const DEFAULT = 'default';
	const SUPPORTED = new Set(['work', 'home', 'facebook', 'livejournal', 'twitter', 'vk']);
	const SUPPORTED_ICO = new Set(['work', 'home', 'other']);

	/**
	 * @class WebField
	 */
	class WebField extends StringFieldClass
	{
		renderLeftIcons()
		{
			const { valueType, valueLink } = this.props;
			this.styles = this.getStyles();

			return SafeImage({
				style: this.styles.leftIcon,
				uri: this.getImageUri({ valueType, valueLink }),
				resizeMode: 'contain',
				placeholder: WebField.getImage({ valueType }),
			});
		}

		getConfig()
		{
			return {
				...super.getConfig(),
				keyboardType: Application.getPlatform() === 'ios' ? 'url' : 'default',
				autoCapitalize: 'none',
			};
		}

		getImageUri({ valueLink, valueType })
		{
			if (isValidLink(valueLink) && SUPPORTED_ICO.has(valueType))
			{
				return `${URL(valueLink).origin}/favicon.ico`;
			}

			return WebField.getImage({ valueType });
		}

		static getImage({ valueType })
		{
			if (valueType && SUPPORTED.has(valueType))
			{
				return `${this.getExtensionPath()}/web/images/${valueType}.png`;
			}

			return WebField.getDefaultImage();
		}

		static getDefaultImage()
		{
			return `${this.getExtensionPath()}/web/images/${DEFAULT}.png`;
		}

		isValidUrl()
		{
			return this.isReadOnly() && isValidLink(this.getValue());
		}

		getStyles()
		{
			const styles = {
				...super.getStyles(),
				leftIcon: {
					width: 22,
					height: 18,
					marginRight: 10,
					alignSelf: 'center',
					alignItems: 'center',
				},
			};

			if (!this.isValidUrl())
			{
				set(styles, ['value', 'color'], AppTheme.colors.base1);
			}

			return styles;
		}
	}

	module.exports = {
		WebType: 'web',
		WebFieldClass: WebField,
		WebField: (props) => new WebField(props),
	};
});
