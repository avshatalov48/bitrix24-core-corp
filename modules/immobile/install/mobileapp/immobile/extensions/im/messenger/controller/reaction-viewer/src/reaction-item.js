/**
 * @module im/messenger/controller/reaction-viewer/reaction-item
 */
jn.define('im/messenger/controller/reaction-viewer/reaction-item', (require, exports, module) => {
	/**
	 * @class ReactionItem
	 * @typedef {LayoutComponent<ReactionItemProps, ReactionItemState>} ReactionItem
	 */
	class ReactionItem extends LayoutComponent
	{
		/**
		 * @param {ReactionItemProps} props
		 */
		constructor(props)
		{
			super(props);
			this.state.isCurrent = props.isCurrent;

			this.canselSelectionHandler = this.canselSelection.bind(this);
		}

		componentDidMount()
		{
			this.props.eventEmitter.on('canselSelection', this.canselSelectionHandler);
		}

		componentWillUnmount()
		{
			this.props.eventEmitter.off('canselSelection', this.canselSelectionHandler);
		}

		render()
		{
			return View(
				{
					style: {
						minHeight: 55,
						minWidth: 70,
						marginRight: 20,
						// backgroundColor: '#a9b',
						flexDirection: 'column',
						justifyContent: 'center',
					},
					clickable: true,
					onClick: () => {
						if (this.state.isCurrent)
						{
							return;
						}

						this.props.eventEmitter.emit('canselSelection', [{ enableReaction: this.props.reactionType }]);
						this.setState({ isCurrent: true });
						this.props.onClick(this.props.reactionType);
					},
				},
				View(
					{
						style: {
							justifyContent: 'center',
							alignItems: 'center',
							flexDirection: 'row',
							alignSelf: 'center',
							flex: 1,
							borderBottomColor: '#0163c6',
							borderBottomWidth: this.state.isCurrent ? 2 : 0,
						},
					},
					Image({
						style: {
							height: 28,
							width: 28,
							marginRight: 3,
						},
						resizeMode: 'stretch',
						uri: this.props.imageUrl,
					}),
					Text({
						style: {
							color: '#A8ADB4',
							fontSize: 14,
							fontWeight: '500',
						},
						text: this.props.counter.toString(),
					}),
				),
			);
		}

		/**
		 * @param {{enableReaction: ReactionType}} prop
		 */
		canselSelection(prop)
		{
			if (this.state.isCurrent === true && prop.enableReaction !== this.props.reactionType)
			{
				this.setState({ isCurrent: false });
			}
		}
	}

	module.exports = { ReactionItem };
});
