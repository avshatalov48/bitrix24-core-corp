/**
 * @module crm/document/qr-code/shimmed-image
 */
jn.define('crm/document/qr-code/shimmed-image', (require, exports, module) => {
	const AppTheme = require('apptheme');

	class ShimmedImage extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				useFallback: typeof ShimmerView === 'undefined',
			};

			this.shimmerRef = null;
			this.imageRef = null;
		}

		get width()
		{
			return this.props.width;
		}

		get height()
		{
			return this.props.height;
		}

		get uri()
		{
			return this.props.uri;
		}

		render()
		{
			return View(
				{
					style: {
						width: this.width,
						height: this.height,
					},
				},
				this.state.useFallback && this.renderFallbackImage(),
				!this.state.useFallback && this.renderImage(),
				!this.state.useFallback && this.renderShimmer(),
			);
		}

		renderFallbackImage()
		{
			return Image({
				uri: this.uri,
				style: {
					width: this.width,
					height: this.height,
					backgroundColor: AppTheme.colors.base6,
				},
				onFailure: () => this.onFailure(),
			});
		}

		renderImage()
		{
			return Image({
				ref: (ref) => this.imageRef = ref,
				uri: this.uri,
				style: {
					width: this.width,
					height: this.height,
					opacity: 0,
					position: 'absolute',
					top: 0,
					left: 0,
				},
				onSuccess: () => this.onLoad(),
				onFailure: () => this.onFailure(),
			});
		}

		renderShimmer()
		{
			return ShimmerView(
				{
					animating: true,
					ref: (ref) => this.shimmerRef = ref,
					style: {
						position: 'absolute',
						top: 0,
						left: 0,
					},
				},
				View(
					{
						style: {
							width: this.width,
							height: this.height,
							backgroundColor: AppTheme.colors.base6,
						},
					},
				),
			);
		}

		onLoad()
		{
			if (this.shimmerRef && this.imageRef)
			{
				this.shimmerRef.animate({ opacity: 0, duration: 100 }, () => {
					this.imageRef.animate({ opacity: 1, duration: 100 });
				});
			}
			else
			{
				this.setState({ useFallback: true });
			}
		}

		onFailure()
		{
			if (this.props.onFailure)
			{
				this.props.onFailure();
			}
		}
	}

	module.exports = { ShimmedImage };
});
