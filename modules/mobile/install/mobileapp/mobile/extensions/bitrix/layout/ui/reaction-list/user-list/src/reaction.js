/**
 * @module layout/ui/reaction-list/user-list/src/reaction
 */

jn.define('layout/ui/reaction-list/user-list/src/reaction', (require, exports, module) => {
	const { Color, Indent, Component } = require('tokens');
	const { Text2, Text5 } = require('ui-system/typography/text');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { ReactionIconView, ReactionIcon } = require('ui-system/blocks/reaction/icon');
	const { UserName } = require('layout/ui/user/user-name');
	const { usersSelector } = require('statemanager/redux/slices/users');

	const { PureComponent } = require('layout/pure-component');
	const { connect } = require('statemanager/redux/connect');

	class Reaction extends PureComponent
	{
		get #testId()
		{
			return this.props.testId ?? '';
		}

		get #itemId()
		{
			return this.props.id ?? null;
		}

		get #showBorder()
		{
			return this.props.showBorder;
		}

		get #showIcon()
		{
			return this.props.showIcon;
		}

		get #reactionId()
		{
			return this.props.reactionId ?? '';
		}

		#getUser()
		{
			return this.props.user || [];
		}

		get #name()
		{
			return this.#getUser()?.fullName || '';
		}

		get #avatar()
		{
			return this.#getUser()?.avatarSize100 ?? '';
		}

		get #description()
		{
			return this.props.description || this.#getUser()?.workPosition;
		}

		render()
		{
			const icon = ReactionIcon.getIconByReactionId(this.#reactionId);

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'space-between',
						marginLeft: Component.paddingLr.toNumber(),
					},
					testId: `${this.#testId}-item`,
				},
				Avatar({
					id: Number(this.#itemId),
					testId: `${this.#testId}-user-avatar`,
					name: this.#name,
					size: 40,
					uri: this.#avatar,
					rounded: true,
					withRedux: true,
					style: {
						marginRight: Indent.XL.toNumber(),
					},
				}),
				View(
					{
						style: {
							display: 'flex',
							flexDirection: 'row',
							alignItems: 'center',
							justifyContent: 'space-between',
							borderTopWidth: this.#showBorder ? 1 : 0,
							borderTopColor: Color.bgSeparatorSecondary.toHex(),
							flexGrow: 1,
							flexShrink: 1,
						},
					},
					View(
						{
							style: {
								display: 'flex',
								height: 70,
								paddingTop: 14,
								paddingBottom: 15,
								alignItems: 'flex-start',
								justifyContent: 'center',
								flexGrow: 1,
								flexShrink: 1,
								marginRight: Indent.XL.toNumber(),
							},
						},
						this.#name && UserName({
							id: Number(this.#itemId),
							testId: `${this.#testId}-user-name`,
							text: this.#name,
							numberOfLines: 1,
							ellipsize: 'end',
							textElement: Text2,
						}),
						this.#description && Text5({
							testId: `${this.#testId}-user-description`,
							ellipsize: 'end',
							numberOfLines: 1,
							style: { color: Color.base3.toHex(), paddingBottom: 2 },
							text: this.#description,
						}),
					),
					this.#showIcon && ReactionIconView(
						{
							testId: `${this.#testId}-icon-${this.#reactionId}`,
							size: 22,
							icon,
							offset: 0,
							style: {
								marginRight: Component.paddingLr.toNumber(),
							},
						},
					),
				),
			);
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const user = usersSelector.selectById(state, ownProps.id);

		return {
			user,
		};
	};

	module.exports = {
		ReduxReactionView: connect(mapStateToProps)(Reaction),
		ReactionView: (props) => new Reaction(props),
	};
});
