/**
 * @module layout/ui/fields/im
 */
jn.define('layout/ui/fields/im', (require, exports, module) => {

	const { isOpenLine, getOpenLineTitle } = require('communication/connection');
	const { StringFieldClass } = require('layout/ui/fields/string');

	const DEFAULT = 'default';
	const SUPPORTED = [
		'bitrix24',
		'facebook',
		'icq',
		'imol',
		'instagram',
		'jabber',
		'livejournal',
		'messenger',
		'msn',
		'openline',
		'skype',
		'telegram',
		'viber',
		'vk',
		'whatsapp',
	];

	/**
	 * @class ImField
	 */
	class ImField extends StringFieldClass
	{
		constructor(props)
		{
			super(props);

			this.state.imageUri = ImField.getImage(props);
		}

		componentWillReceiveProps(nextProps)
		{
			super.componentWillReceiveProps(nextProps);

			this.state.imageUri = ImField.getImage(nextProps);
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				items: BX.prop.getArray(config, 'items', []),
			};
		}

		renderReadOnlyContent()
		{
			const value = this.getValue();

			if (isOpenLine(value))
			{
				return Text({
					style: this.styles.value,
					text: getOpenLineTitle(value),
				});
			}

			return super.renderReadOnlyContent();
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
					const defaultImage = ImField.getDefaultImage();
					if (imageUri !== defaultImage)
					{
						this.setState({
							imageUri: defaultImage,
						});
					}
				},
			});
		}

		static getImage({ valueType })
		{
			if (SUPPORTED.includes(valueType))
			{
				return `${this.getExtensionPath()}/im/images/${valueType}.png`;
			}

			return this.getDefaultImage();
		}

		static getDefaultImage()
		{
			return `${this.getExtensionPath()}/im/images/${DEFAULT}.png`;
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				leftIcon: {
					width: 22,
					height: 18,
					marginRight: 10,
					alignSelf: 'center',
					alignItems: 'center',
				},
			};
		}

	}

	module.exports = {
		ImType: 'im',
		ImFieldClass: ImField,
		ImField: (props) => new ImField(props),
	};

});
