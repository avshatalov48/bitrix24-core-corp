/**
 * @module ui-system/blocks/chips/chip-reaction
 */

jn.define('ui-system/blocks/chips/chip-reaction', (require, exports, module) => {
	const { Indent, Component } = require('tokens');
	const { Loc } = require('loc');
	const { Ellipsize } = require('utils/enums/style');
	const { ChipButtonSize, ChipButtonDesign } = require('ui-system/blocks/chips/chip-button');
	const { ReactionIcon } = require('ui-system/blocks/reaction/icon');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const store = require('statemanager/redux/store');
	const { selectReactionMessageById } = require('statemanager/redux/slices/settings/selector');

	const DEFAULT_REACTION = 'like';

	/**
	 * @typedef {Object} ChipReactionProps
	 * @property {string} testId
	 * @property {string} reactionId
	 * @property {boolean} [compact]
	 * @property {boolean} [selected=false]
	 * @property {Ellipsize} [ellipsize]
	 * @property {Function} [forwardRef]
	 *
	 * @class ChipReaction
	 */
	class ChipReaction extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.initStyle(props);

			this.animate = false;
		}

		componentDidMount()
		{
			this.animate = true;
		}

		componentWillReceiveProps(props)
		{
			this.initStyle(props);
		}

		get #testId()
		{
			const { testId } = this.props;

			return testId ?? '';
		}

		get #reactionId()
		{
			const { reactionId } = this.props;

			return reactionId ?? DEFAULT_REACTION;
		}

		get #userId()
		{
			const { userId } = this.props;

			return userId ?? null;
		}

		get #selected()
		{
			const { selected } = this.props;

			return selected ?? false;
		}

		get #forwardRef()
		{
			const { forwardRef } = this.props;

			return forwardRef ?? null;
		}

		get #containerTestId()
		{
			return this.#selected ? `${this.#testId}-selected` : `${this.#testId}-default`;
		}

		initStyle(props)
		{
			const { compact } = props;

			this.size = compact ? ChipButtonSize.SMALL : ChipButtonSize.NORMAL;
		}

		getTypography()
		{
			return this.size.getTypography();
		}

		getDesign()
		{
			const { design, mode } = this.props;

			if (design === null)
			{
				return {};
			}

			const finalDesign = this.#selected
				? ChipButtonDesign.resolve(design, ChipButtonDesign.PRIMARY)
				: ChipButtonDesign.resolve(design, ChipButtonDesign.GREY);

			return finalDesign.getStyle(mode);
		}

		getColor()
		{
			const design = this.getDesign();

			return design?.color;
		}

		#getMessage(reactionId)
		{
			return selectReactionMessageById(store.getState(), reactionId);
		}

		#handleOnClick = () => {
			const { onClick } = this.props;

			if (onClick)
			{
				onClick();
			}
		};

		#getEllipsize()
		{
			const { ellipsize } = this.props;

			return Ellipsize.resolve(ellipsize, Ellipsize.END).toString();
		}

		#handleOnLongClick = () => {
			const { onLongClick } = this.props;

			if (onLongClick)
			{
				onLongClick();
			}
		};

		render()
		{
			return View(
				{
					testId: this.#containerTestId,
					style: this.getBodyStyle(),
					onClick: this.#handleOnClick,
					onLongClick: this.#handleOnLongClick,
					ref: this.#forwardRef,
				},
				...this.renderContentBasedOnSelection(),
			);
		}

		renderContentBasedOnSelection()
		{
			if (this.#selected)
			{
				return [
					this.#renderLottieAnimation(),
					this.#renderAvatar(),
				];
			}

			return [
				this.#renderIcon(),
				this.#renderText(),
			];
		}

		getBodyStyle()
		{
			const { backgroundColor, ...chipStyle } = this.getDesign();

			return {
				flexShrink: 1,
				flexDirection: 'row',
				alignItems: 'center',
				height: this.size.getHeight(),
				borderRadius: Component.elementAccentCorner.toNumber(),
				paddingHorizontal: Indent.S.toNumber(),
				paddingVertical: Indent.XS2.toNumber(),
				...chipStyle,
				backgroundColor: backgroundColor ? backgroundColor?.value : null,
			};
		}

		#renderLottieAnimation()
		{
			return LottieView(
				{
					testId: `${this.#testId}-animation-${this.#reactionId}`,
					style: {
						height: 20,
						width: 20,
						marginRight: Indent.XS2.toNumber(),
					},
					data: {
						uri: ReactionIcon.getLottieAnimationById(this.#reactionId),
					},
					params: {
						loopMode: 'playOnce',
					},
					autoPlay: this.animate,
				},
			);
		}

		#renderIcon()
		{
			return IconView({
				color: this.getColor(),
				icon: Icon.LIKE,
				size: 20,
				testId: `${this.#testId}-icon`,
			});
		}

		#renderText()
		{
			const reactionId = this.#reactionId;
			const text = Loc.getMessage(`M_RATING_LIKE_EMOTION_${reactionId.toUpperCase()}`);

			const Typography = this.getTypography();

			return Typography({
				text: this.#getMessage(reactionId) ?? text,
				testId: `${this.#testId}-text`,
				color: this.getColor(),
				ellipsize: this.#getEllipsize(),
				numberOfLines: 1,
				style: {
					flexShrink: 1,
					marginRight: Indent.XS.toNumber(),
					maxWidth: 130,
				},
			});
		}

		#renderAvatar()
		{
			return Avatar({
				id: this.#userId,
				testId: `${this.#testId}-user-avatar`,
				withRedux: true,
				size: 20,
			});
		}
	}

	module.exports = {
		/**
		 * @param {ChipReactionProps} props
		 * @returns {ChipReaction}
		 */
		ChipReaction: (props) => new ChipReaction(props),
	};
});
