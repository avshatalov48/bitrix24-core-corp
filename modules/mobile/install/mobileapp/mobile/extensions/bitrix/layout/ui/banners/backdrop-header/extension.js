/**
 * @module layout/ui/banners/backdrop-header
 */
jn.define('layout/ui/banners/backdrop-header', (require, exports, module) => {

	/**
	 * @function BackdropHeader
	 * @param {String} title
	 * @param {String} description
	 * @param {Object} additionalInfo
	 * @param {String} image
	 * @param {String} position
	 */
	const BackdropHeader = ({ title, description, additionalInfo = null, image, position = 'center' }) =>
		View(
			{
				style: {
					borderRadius: 12,
					backgroundColor: '#e5f9ff',
					padding: 16,
					flexDirection: 'row',
					alignItems: position,
				},
			},
			...TransparentCircles,
			MainImage(image),
			BannerBody(title, description, additionalInfo),
		);

	const MainImage = (backgroundImage) =>
		View(
			{
				style: {
					width: 82,
					height: 82,
					backgroundImage,
					backgroundResizeMode: 'cover',
				},
			},
		);

	const BannerBody = (title, description, additionalInfo = null) =>
		View(
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
					color: '#333333',
					fontSize: 15,
					numberOfLines: 1,
					ellipsize: 'end',
					marginBottom: 8,
				},
				text: title,
			}),
			description && Text({
				style: {
					color: '#525C69',
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
					borderColor: '#ffffff',
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
					borderColor: '#ffffff',
					opacity: 0.4,
				},
			},
		)];

	module.exports = { BackdropHeader };
});