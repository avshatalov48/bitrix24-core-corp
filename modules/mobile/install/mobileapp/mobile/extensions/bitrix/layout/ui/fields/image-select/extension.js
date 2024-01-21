/**
 * @module layout/ui/fields/image-select
 */
jn.define('layout/ui/fields/image-select', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { BaseField } = require('layout/ui/fields/base');

	/**
	 * @class ImageSelectField
	 */
	class ImageSelectField extends BaseField
	{
		static get types()
		{
			return {
				default: 'default',
				loaded: 'loaded',
			};
		}

		constructor(props)
		{
			super(props);

			props.readOnly = false;
		}

		canFocusTitle()
		{
			return BX.prop.getBoolean(this.props, 'canFocusTitle', false);
		}

		renderContent()
		{
			const images = [];

			Object.keys(this.props.images.default).forEach((id) => images.push(this.renderImage(id)));

			if (this.props.images.loaded)
			{
				images.push(this.renderImage(ImageSelectField.types.loaded));
			}

			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
						justifyContent: 'space-between',
					},
				},
				ScrollView(
					{
						style: {
							flex: 1,
							height: 48,
						},
						horizontal: true,
					},
					View(
						{
							style: {
								flexGrow: 1,
								flexDirection: 'row',
								alignSelf: 'center',
							},
						},
						...images,
					),
				),
				ImageButton({
					style: {
						width: 40,
						height: 40,
						alignSelf: 'center',
					},
					svg: {
						content: `<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M20 40C31.0457 40 40 31.0457 40 20C40 8.95431 31.0457 0 20 0C8.95431 0 0 8.95431 0 20C0 31.0457 8.95431 40 20 40Z" fill="${AppTheme.colors.base2}" fill-opacity="0.12"/><path fill-rule="evenodd" clip-rule="evenodd" d="M21.3805 13.3687H18.6203V18.6198H13.3691V21.3801H18.6203V26.6313H21.3805V21.3801H26.6318V18.6198H21.3805V13.3687Z" fill="${AppTheme.colors.base4}"/></svg>`,
					},
					onClick: () => this.showImagePicker(),
				}),
			);
		}

		renderImage(id)
		{
			return View(
				{
					style: this.styles.image(this.getValue() === id),
				},
				Image({
					style: {
						width: 40,
						height: 40,
						alignSelf: 'center',
						borderRadius: 20,
					},
					uri: encodeURI(this.props.images.default[id] || this.props.images.loaded),
					onClick: () => this.handleChange(id, null, null),
				}),
				(
					this.props.isLoading
					&& !this.props.images.default[id]
					&& View(
						{
							style: {
								position: 'absolute',
								left: 0,
								top: 0,
								width: '100%',
								height: '100%',
								backgroundColor: AppTheme.colors.bgContentPrimary,
								opacity: 0.5,
							},
							onClick: () => this.handleChange(id, null, null),
						},
						Loader({
							style: {
								width: 48,
								height: 48,
							},
							tintColor: AppTheme.colors.base0,
							animating: true,
							size: 'small',
						}),
					)
				),
			);
		}

		showImagePicker()
		{
			const items = [
				{
					id: 'mediateka',
					name: BX.message('MOBILE_LAYOUT_UI_FIELDS_IMAGE_SELECT_MEDIATEKA'),
				},
				{
					id: 'camera',
					name: BX.message('MOBILE_LAYOUT_UI_FIELDS_IMAGE_SELECT_CAMERA'),
				},
			];
			const config = this.getConfig();

			if (!config.hideDisk && config.fileAttachPath)
			{
				items.push({
					id: 'disk',
					name: BX.message('MOBILE_LAYOUT_UI_FIELDS_IMAGE_SELECT_B24_DISK_MSGVER_1'),
					dataSource: {
						multiple: false,
						url: config.fileAttachPath,
					},
				});
			}

			dialogs.showImagePicker(
				{
					settings: {
						resize: {
							targetWidth: -1,
							targetHeight: -1,
							sourceType: 1,
							encodingType: 0,
							mediaType: 0,
							allowsEdit: true,
							saveToPhotoAlbum: true,
							cameraDirection: 0,
						},
						maxAttachedFilesCount: 1,
						previewMaxWidth: 640,
						previewMaxHeight: 640,
						attachButton: { items },
					},
				},
				(data) => this.onImageSelectFielded(data),
			);
		}

		onImageSelectFielded(data)
		{
			const image = data[0];

			if (image)
			{
				this.handleChange(
					ImageSelectField.types.loaded,
					(image.dataAttributes ? `${currentDomain}${image.dataAttributes.IMAGE}` : image.previewUrl),
					image,
				);
			}
		}

		getDefaultStyles()
		{
			return {
				...super.getDefaultStyles(),
				image: (isSelected) => {
					const style = {
						width: 48,
						height: 48,
						backgroundColor: AppTheme.colors.bgContentPrimary,
						justifyContent: 'center',
						borderWidth: 0,
					};
					const styleSelected = {
						borderWidth: 2,
						borderColor: AppTheme.colors.accentBrandBlue,
						borderRadius: 24,
					};

					return (isSelected ? { ...style, ...styleSelected } : style);
				},
			};
		}
	}

	module.exports = {
		ImageSelectType: 'image-select',
		ImageSelectField: (props) => new ImageSelectField(props),
	};
});
