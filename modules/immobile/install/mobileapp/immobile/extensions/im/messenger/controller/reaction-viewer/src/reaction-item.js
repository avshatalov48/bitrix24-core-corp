/**
 * @module im/messenger/controller/reaction-viewer/reaction-item
 */
jn.define('im/messenger/controller/reaction-viewer/reaction-item', (require, exports, module) => {
	/* global JNEventEmitter */
	const { Theme } = require('im/lib/theme');
	const { Loc } = require('loc');

	const sharedEmitter = new JNEventEmitter();
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
			sharedEmitter.on('canselSelection', this.canselSelectionHandler);
		}

		componentWillUnmount()
		{
			sharedEmitter.off('canselSelection', this.canselSelectionHandler);
		}

		render()
		{
			return View(
				{
					style: {
						minHeight: 46,
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

						sharedEmitter.emit('canselSelection', [{ enableReaction: this.props.reactionType }]);
						this.setState({ isCurrent: true });
						this.props.onClick(this.props.reactionType);
					},
				},
				this.props.reactionType === 'all'
					? this.renderSummary()
					: this.renderReaction(),
			);
		}

		renderReaction()
		{
			return View(
				{
					style: {
						justifyContent: 'center',
						alignItems: 'center',
						flexDirection: 'row',
						alignSelf: 'center',
						flex: 1,
						borderBottomColor: this.state.isCurrent ? Theme.colors.accentMainPrimary : Theme.colors.bgNavigation,
						borderBottomWidth: 2,
					},
				},
				Image({
					style: {
						height: 24,
						width: 24,
						marginRight: 6,
					},
					resizeMode: 'contain',
					uri: this.props.imageUrl,
				}),
				Text({
					style: {
						color: this.state.isCurrent ? Theme.colors.base1 : Theme.colors.base4,
						fontSize: 14,
						fontWeight: '500',
					},
					text: this.props.counter.toString(),
				}),
			);
		}

		renderSummary()
		{
			return View(
				{
					style: {
						justifyContent: 'center',
						alignItems: 'center',
						flexDirection: 'row',
						alignSelf: 'center',
						flex: 1,
						borderBottomColor: this.state.isCurrent ? Theme.colors.accentMainPrimary : Theme.colors.bgNavigation,
						borderBottomWidth: 2,
					},
				},
				Text({
					style: {
						color: this.state.isCurrent ? Theme.colors.base1 : Theme.colors.base4,
						fontSize: 14,
						fontWeight: '500',
						marginRight: 6,
					},
					text: Loc.getMessage('IMMOBILE_REACTION_VIEWER_TAB_ALL'),
				}),
				Text({
					style: {
						color: this.state.isCurrent ? Theme.colors.base1 : Theme.colors.base4,
						fontSize: 14,
						fontWeight: '500',
					},
					text: this.props.counter.toString(),
				}),
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
