/**
 * @module layout/ui/safe-image/src/safe-image
 */
jn.define('layout/ui/safe-image/src/safe-image', (require, exports, module) => {
	const { PropTypes } = require('utils/validation');
	const DEFAULT_PLACEHOLDER = '<svg width="173" height="173" viewBox="0 0 173 173" fill="none" xmlns="http://www.w3.org/2000/svg"><circle opacity="0.2" cx="86.4999" cy="86.0688" r="85.0688" stroke="#A8ADB4" stroke-width="2"/><path opacity="0.2" fill-rule="evenodd" clip-rule="evenodd" d="M56.9412 48H115.059C119.997 48 124 52.0031 124 56.9412V115.059C124 119.997 119.997 124 115.059 124H56.9412C52.0031 124 48 119.997 48 115.059V56.9412C48 52.0031 52.0031 48 56.9412 48ZM57.1727 114.828H115.09V110.373L99.6465 92.5509L91.9226 101.463L72.6161 79.1863L57.1727 97.0058V114.828ZM101.943 77.4945C106.07 77.4945 109.415 74.149 109.415 70.022C109.415 65.895 106.07 62.5495 101.943 62.5495C97.8158 62.5495 94.4702 65.895 94.4702 70.022C94.4702 74.149 97.8158 77.4945 101.943 77.4945Z" fill="#525C69"/></svg>';
	const { isImageInCache } = require('asset-manager');

	/**
	 * @class SafeImage
	 */
	class SafeImage extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				success: false,
			};

			// this.tryGetImageFromCache(props.uri);

			this.onSuccess = this.handleOnSuccess.bind(this);
		}

		componentWillReceiveProps(props)
		{
			if (this.props.uri !== props.uri)
			{
				this.state.success = false;

				// this.tryGetImageFromCache(props.uri);
			}
		}

		tryGetImageFromCache(imageUri)
		{
			isImageInCache(imageUri).then((imageCached) => {
				if (imageCached === true)
				{
					this.setState({ success: imageCached });
				}
			});
		}

		handleOnSuccess()
		{
			const { success } = this.state;
			const { onSuccess } = this.props;

			if (!success)
			{
				this.setState(
					{
						success: true,
					},
					() => {
						if (onSuccess)
						{
							onSuccess();
						}
					},
				);
			}
		}

		renderPlaceholder()
		{
			const { style, placeholder, renderPlaceholder } = this.props;
			const { success } = this.state;

			if (success)
			{
				return null;
			}

			if (renderPlaceholder)
			{
				const placeholderComponent = renderPlaceholder();
				if (placeholderComponent)
				{
					return placeholderComponent;
				}
			}

			const imagePlaceholder = placeholder || {
				content: DEFAULT_PLACEHOLDER,
			};
			const typeImage = typeof imagePlaceholder === 'string' ? 'uri' : 'svg';

			return View(
				{
					style,
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
			const { style, placeholder, ...imageProps } = this.props;
			const { success } = this.state;

			return Image(
				{
					...imageProps,
					style: {
						...style,
						display: success ? 'flex' : 'none',
					},
					onSuccess: this.onSuccess,
				},
			);
		}

		render()
		{
			const { testId, wrapperStyle } = this.props;

			return View(
				{
					testId,
					style: wrapperStyle,
				},
				this.renderPlaceholder(),
				this.renderImage(),
			);
		}
	}

	SafeImage.propTypes = {
		style: PropTypes.object,
		uri: PropTypes.string,
		svg: PropTypes.shape({
			content: PropTypes.string,
		}),
		resizeMode: PropTypes.string,
		placeholder: PropTypes.oneOfType([
			PropTypes.string,
			PropTypes.shape({
				content: PropTypes.string,
			}),
		]),
	};

	module.exports = {
		SafeImage,
	};
});
