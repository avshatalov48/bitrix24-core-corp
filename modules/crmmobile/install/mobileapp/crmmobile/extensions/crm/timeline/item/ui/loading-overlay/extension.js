/**
 * @module crm/timeline/item/ui/loading-overlay
 */
jn.define('crm/timeline/item/ui/loading-overlay', (require, exports, module) => {
	/**
	 * @class TimelineItemLoadingOverlay
	 */
	class TimelineItemLoadingOverlay extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				visible: false,
			};

			this.nodeRef = null;
		}

		componentWillReceiveProps(props)
		{
			this.state.visible = false;
		}

		render()
		{
			const { visible } = this.state;

			return View(
				{
					style: {
						position: visible ? 'absolute' : 'relative',
						display: visible ? 'flex' : 'none',
						opacity: 0,
						top: 0,
						left: 0,
						right: 0,
						bottom: 0,
						backgroundColor: '#ffffff',
						flexDirection: 'column',
						justifyContent: 'center',
						alignItems: 'center',
					},
					clickable: true,
					ref: (ref) => this.nodeRef = ref,
				},
				Loader({
					style: {
						width: 50,
						height: 50,
					},
					tintColor: '#828b95',
					animating: true,
					size: 'large',
				}),
			);
		}

		/**
		 * @public
		 */
		show()
		{
			if (!this.nodeRef)
			{
				return;
			}

			this.setState({ visible: true }, () => {
				this.nodeRef.animate({
					duration: 300,
					opacity: 0.6,
				});
			});
		}

		/**
		 * @public
		 */
		hide()
		{
			if (!this.nodeRef)
			{
				return;
			}

			this.nodeRef.animate({
				duration: 300,
				opacity: 0,
			}, () => {
				this.setState({ visible: false });
			});
		}
	}

	module.exports = { TimelineItemLoadingOverlay };
});
