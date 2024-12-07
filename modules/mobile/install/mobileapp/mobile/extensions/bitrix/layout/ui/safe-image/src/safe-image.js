/**
 * @module layout/ui/safe-image/src/safe-image
 */
jn.define('layout/ui/safe-image/src/safe-image', (require, exports, module) => {
	const { Color } = require('tokens');
	const { PropTypes } = require('utils/validation');
	const { ShimmerView } = require('layout/polyfill');
	const DEFAULT_PLACEHOLDER = '<svg width="173" height="173" viewBox="0 0 173 173" fill="none" xmlns="http://www.w3.org/2000/svg"><circle opacity="0.2" cx="86.4999" cy="86.0688" r="85.0688" stroke="#A8ADB4" stroke-width="2"/><path opacity="0.2" fill-rule="evenodd" clip-rule="evenodd" d="M56.9412 48H115.059C119.997 48 124 52.0031 124 56.9412V115.059C124 119.997 119.997 124 115.059 124H56.9412C52.0031 124 48 119.997 48 115.059V56.9412C48 52.0031 52.0031 48 56.9412 48ZM57.1727 114.828H115.09V110.373L99.6465 92.5509L91.9226 101.463L72.6161 79.1863L57.1727 97.0058V114.828ZM101.943 77.4945C106.07 77.4945 109.415 74.149 109.415 70.022C109.415 65.895 106.07 62.5495 101.943 62.5495C97.8158 62.5495 94.4702 65.895 94.4702 70.022C94.4702 74.149 97.8158 77.4945 101.943 77.4945Z" fill="#525C69"/></svg>';

	// const { isImageInCache } = require('asset-manager');

	/**
	 * @typedef SafeImageProps
	 * @property {string} [testId]
	 * @property {string} uri
	 * @property {Object} [svg]
	 * @property {string} [svg.content]
	 * @property {Object} [style]
	 * @property {Object} [wrapperStyle]
	 * @property {Function} [renderPlaceholder]
	 * @property {Function} [onSuccess]
	 * @property {Function} [onFailure]
	 * @property {Function} [onClick]
	 * @property {resizeMode} [resizeMode]
	 * @property {boolean} [withShimmer]
	 *
	 * @class SafeImage
	 */
	class SafeImage extends LayoutComponent
	{
		/**
		 * @param {SafeImageProps} props
		 */
		constructor(props)
		{
			super(props);

			this.initState();

			this.handleOnSuccess = this.handleOnSuccess.bind(this);
			this.handleOnClick = this.handleOnClick.bind(this);
			this.handleOnFailure = this.handleOnFailure.bind(this);
		}

		componentWillReceiveProps(props)
		{
			if (this.getUri() !== props.uri)
			{
				this.initState();
			}
		}

		initState()
		{
			this.state = {
				failed: false,
				success: false,
			};
		}

		getImageExternalStyle()
		{
			const { style = {} } = this.props;

			return style;
		}

		render()
		{
			const { testId, wrapperStyle = {}, clickable = true } = this.props;

			return View(
				{
					testId,
					clickable,
					style: wrapperStyle,
					onClick: this.handleOnClick,
				},
				this.renderPlaceholder(),
				this.renderImage(),
			);
		}

		renderPlaceholder()
		{
			const { placeholder, renderPlaceholder } = this.props;

			if (this.withShimmer() && Boolean(this.getUri()) && !this.isSuccess() && !this.isFailed())
			{
				return this.renderShimmer();
			}

			if (this.isSuccess() && !this.isFailed())
			{
				return null;
			}

			if (renderPlaceholder)
			{
				return typeof renderPlaceholder === 'function'
					? renderPlaceholder()
					: renderPlaceholder;
			}

			const imagePlaceholder = placeholder || {
				content: DEFAULT_PLACEHOLDER,
			};
			const typeImage = typeof imagePlaceholder === 'string' ? 'uri' : 'svg';

			return View(
				{
					style: this.getImageExternalStyle(),
				},
				Image({
					resizeMode: 'contain',
					style: {
						width: '100%',
						height: '100%',
					},
					[typeImage]: imagePlaceholder,
				}),
			);
		}

		renderImage()
		{
			const { uri, svg, resizeMode } = this.props;

			return Image(
				{
					uri,
					svg,
					resizeMode,
					style: {
						...this.getImageExternalStyle(),
						display: this.isSuccess() ? 'flex' : 'none',
					},
					onSuccess: this.handleOnSuccess,
					onFailure: this.handleOnFailure,
					onSvgContentError: this.handleOnFailure,
				},
			);
		}

		renderShimmer()
		{
			return ShimmerView(
				{
					animating: true,
				},
				View(
					{
						style: {
							...this.getImageExternalStyle(),
							backgroundColor: Color.base6.toHex(),
						},
					},
				),
			);
		}

		handleOnClick()
		{
			const { onClick } = this.props;

			if (onClick)
			{
				onClick();
			}
		}

		handleOnFailure()
		{
			this.setState(
				{ failed: true },
				() => {
					const { onFailure } = this.props;

					if (onFailure)
					{
						onFailure();
					}
				},
			);
		}

		handleOnSuccess()
		{
			if (this.isSuccess())
			{
				return;
			}

			this.setState(
				{ success: true },
				() => {
					const { onSuccess } = this.props;

					if (onSuccess)
					{
						onSuccess();
					}
				},
			);
		}

		getUri()
		{
			const { uri } = this.props;

			return uri;
		}

		isSuccess()
		{
			const { success } = this.state;

			return success;
		}

		isFailed()
		{
			const { failed } = this.state;

			return failed;
		}

		withShimmer()
		{
			const { withShimmer } = this.props;

			return Boolean(withShimmer);
		}
	}

	SafeImage.defaultProps = {
		withShimmer: false,
	};

	SafeImage.propTypes = {
		withShimmer: PropTypes.bool,
		testId: PropTypes.string,
		style: PropTypes.object,
		wrapperStyle: PropTypes.object,
		uri: PropTypes.string,
		svg: PropTypes.shape({
			content: PropTypes.string,
		}),
		resizeMode: PropTypes.string,
		renderPlaceholder: PropTypes.oneOfType([
			PropTypes.func,
			PropTypes.object,
		]),
		placeholder: PropTypes.oneOfType([
			PropTypes.string,
			PropTypes.shape({
				content: PropTypes.string,
			}),
		]),
		onSuccess: PropTypes.func,
		onFailure: PropTypes.func,
		onClick: PropTypes.func,
	};

	module.exports = {
		SafeImage,
	};
});
