/**
 * @module layout/ui/fields/web
 */
jn.define('layout/ui/fields/web', (require, exports, module) => {

	const { StringFieldClass } = require('layout/ui/fields/string');
	const { isValidLink, URL } = require('utils/url');
	const { set } = require('utils/object');

	const DEFAULT = 'default';
	const SUPPORTED = ['work', 'home', 'facebook', 'livejournal', 'twitter', 'vk'];
	const SUPPORTED_ICO = ['work', 'home', 'other'];

	/**
	 * @class WebField
	 */
	class WebField extends StringFieldClass
	{
		constructor(props)
		{
			super(props);

			this.state.imageUri = this.getImageUri(props);
		}

		componentWillReceiveProps(nextProps)
		{
			super.componentWillReceiveProps(nextProps);

			this.state.imageUri = this.getImageUri(nextProps);
		}

		renderLeftIcons()
		{
			const { imageUri } = this.state;

			this.styles = this.getStyles();

			return Image({
				style: this.styles.leftIcon,
				uri: imageUri,
				resizeMode: 'contain',
				onFailure: () => {
					const { valueType } = this.props;
					const defaultImage = WebField.getImage({ valueType });
					if (imageUri !== defaultImage)
					{
						this.setState({
							imageUri: defaultImage,
						});
					}
				},
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
			if (isValidLink(valueLink) && SUPPORTED_ICO.includes(valueType))
			{
				return `${URL(valueLink).origin}/favicon.ico`;
			}

			return WebField.getImage({ valueType });
		}

		static getImage({ valueType })
		{
			if (valueType && SUPPORTED.includes(valueType))
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
				set(styles, ['value', 'color'], '#333333');
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
