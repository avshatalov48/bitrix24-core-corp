/**
 * @module layout/ui/banners/backdrop-header
 */
jn.define('layout/ui/banners/backdrop-header', (require, exports, module) => {
	const AppTheme = require('apptheme');

	/**
	 * @function BackdropHeader
	 * @param {String} title
	 * @param {String} description
	 * @param {Object} additionalInfo
	 * @param {String} image
	 * @param {String} position
	 * @return {View}
	 */
	const BackdropHeader = ({ title, description, additionalInfo = null, image, position = 'center' }) => View(
		{
			style: {
				borderRadius: 12,
				backgroundColor: AppTheme.colors.accentSoftBlue2,
				padding: 16,
				flexDirection: 'row',
				alignItems: position,
			},
		},
		...TransparentCircles,
		MainImage(image),
		BannerBody(title, description, additionalInfo),
	);

	/**
	 * @function MainImage
	 * @param {string|View} backgroundImage
	 * @return {View}
	 */
	const MainImage = (backgroundImage) => {
		if (typeof backgroundImage === 'string')
		{
			return View(
				{
					style: {
						width: 82,
						height: 82,
						backgroundImage,
						backgroundResizeMode: 'cover',
					},
				},
			);
		}

		return backgroundImage;
	};

	/**
	 * @function CreateBannerImage
	 * @param {object} props
	 * @param {object} props.image
	 * @return {View}
	 */
	const CreateBannerImage = (props) => {
		const { image } = props;

		return View(
			{
				style: {
					width: 82,
					height: 82,
					borderWidth: 2,
					borderRadius: 82,
					borderColor: AppTheme.colors.accentBrandBlue,
					backgroundColor: AppTheme.colors.bgContentPrimary,
				},
			},
			Image({
				style: {
					width: '100%',
					height: '100%',
				},
				...image,
			}),
		);
	};

	const BannerBody = (title, description, additionalInfo = null) => View(
		{
			style: {
				marginLeft: 20,
				marginRight: 10,
				flex: 1,
			},
		},
		additionalInfo,
		title && Text({
			style: {
				color: AppTheme.colors.base1,
				fontSize: 15,
				numberOfLines: 1,
				ellipsize: 'end',
				marginBottom: 8,
			},
			text: title,
		}),
		description && Text({
			style: {
				color: AppTheme.colors.base3,
				fontSize: 14,
				numberOfLines: 2,
				ellipsize: 'end',
				lineHeightMultiple: 1.2,
			},
			text: description,
		}),
	);

	const TransparentCircles = [
		View(
			{
				style: {
					position: 'absolute',
					top: 49,
					left: -140,
					width: 260,
					height: 260,
					borderRadius: 130,
					borderWidth: 37,
					borderColor: AppTheme.colors.bgContentPrimary,
					opacity: 0.5,
				},
			},
		),
		View(
			{
				style: {
					position: 'absolute',
					top: -110,
					right: -100,
					width: 198,
					height: 198,
					borderRadius: 99,
					borderWidth: 26,
					borderColor: AppTheme.colors.bgContentPrimary,
					opacity: 0.4,
				},
			},
		),
	];

	module.exports = { BackdropHeader, CreateBannerImage };
});
