/**
 * @module layout/ui/gratitude-list/src/gratitude
 */
jn.define('layout/ui/gratitude-list/src/gratitude', (require, exports, module) => {
	const { Indent, Color } = require('tokens');
	const { GratitudeIcon } = require('assets/icons');
	const { Text2, Text5, Text6 } = require('ui-system/typography/text');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { connect } = require('statemanager/redux/connect');
	const { usersSelector } = require('statemanager/redux/slices/users');
	const { PureComponent } = require('layout/pure-component');
	const {
		selectById,
	} = require('statemanager/redux/slices/gratitude');
	const { FormatterTypes } = require('layout/ui/friendly-date/formatter-factory');
	const { Moment } = require('utils/date');
	const { HumanDateFormatter } = require('layout/ui/friendly-date/human-date-formatter');
	const { createTestIdGenerator } = require('utils/test');
	const { Card } = require('ui-system/layout/card');

	const BADGE_SIZE = {
		width: 40,
		height: 40,
	};

	/**
	 * @class GratitudeView
	 * @typedef {Object} GratitudeViewProps
	 * @property {string} [testId]
	 * @property {number|null} [postId]
	 * @property {boolean} [showBorder=false]
	 * @property {Object} [author]
	 * @property {number} [author.id]
	 * @property {string} [author.fullName]
	 * @property {string} [author.avatarSize100]
	 * @property {string} [title]
	 * @property {string} [name]
	 * @property {number} [createdAt]
	 * @property {function(number|null): void} itemDetailOpenHandler
	 * @extends PureComponent
	 */
	class GratitudeView extends PureComponent
	{
		/**
		 * @param {GratitudeViewProps} props
		 */
		constructor(props)
		{
			super(props);
			this.getTestId = createTestIdGenerator({
				prefix: this.props.testId,
				context: this,
			});
		}

		/**
		 * @returns {number|null}
		 */
		get #postId()
		{
			return this.props.postId ?? null;
		}

		/**
		 * @returns {boolean}
		 */
		get #showBorder()
		{
			return this.props.showBorder ?? false;
		}

		/**
		 * @returns {Object|null}
		 */
		get #author()
		{
			return this.props.author ?? null;
		}

		/**
		 * @returns {number|null}
		 */
		get #authorId()
		{
			return this.#author?.id ?? null;
		}

		/**
		 * @returns {string}
		 */
		get #authorName()
		{
			return this.#author?.fullName ?? '';
		}

		/**
		 * @returns {string}
		 */
		get #authorImage()
		{
			return this.#author?.avatarSize100 ?? '';
		}

		/**
		 * @returns {string}
		 */
		get #title()
		{
			return this.props.title ?? '';
		}

		/**
		 * @returns {string}
		 */
		get #gratitudeImageUri()
		{
			const { name } = this.props;

			return name ? GratitudeIcon.getSvgUriByName(name) : '';
		}

		/**
		 * @returns {string|null}
		 */
		get #gratitudeCreatedAt()
		{
			const { createdAt } = this.props;

			if (!createdAt)
			{
				return null;
			}

			const moment = Moment.createFromTimestamp(createdAt);

			const formatter = new HumanDateFormatter({
				moment,
				showTime: false,
				futureAllowed: false,
				formatType: FormatterTypes.HUMAN_DATE,
			});

			return formatter.format(moment) ?? null;
		}

		render()
		{
			return Card(
				{
					testId: this.getTestId(),
					excludePaddingSide: {
						bottom: true,
						top: true,
					},
					style: {
						flexDirection: 'row',
						alignItems: 'flex-start',
						justifyContent: 'flex-start',
						paddingBottom: Indent.L.toNumber(),
					},
					onClick: () => {
						if (this.#postId)
						{
							this.props.itemDetailOpenHandler(this.#postId);
						}
					},
				},
				this.renderBadge(),
				this.renderContent(),
			);
		}

		renderBadge()
		{
			return Image({
				testId: this.getTestId('badge'),
				style: {
					...BADGE_SIZE,
					marginTop: Indent.XL.toNumber(),
				},
				svg: {
					uri: this.#gratitudeImageUri,
				},
			});
		}

		renderContent()
		{
			return View(
				{
					style: {
						alignItems: 'center',
						justifyContent: 'space-between',
						flexDirection: 'row',
						flexGrow: 1,
						flexShrink: 1,
						paddingTop: Indent.XL.toNumber(),
						borderTopWidth: this.#showBorder ? 1 : 0,
						borderTopColor: Color.bgSeparatorSecondary.toHex(),
						marginLeft: Indent.XL2.toNumber(),
					},
				},
				this.renderTextBlock(),
				this.renderDate(),
			);
		}

		renderTextBlock()
		{
			return View(
				{
					style: {
						flexGrow: 1,
						flexShrink: 1,
						flexDirection: 'column',
						alignItems: 'flex-start',
						justifyContent: 'flex-start',
						marginRight: Indent.XL2.toNumber(),
					},
				},
				this.renderTitle(),
				this.renderAuthor(),
			);
		}

		renderTitle()
		{
			return Text2({
				testId: this.getTestId('title'),
				text: this.#title,
				numberOfLines: 3,
				ellipsize: 'end',
				color: Color.base0.toHex(),
				style: {
					marginBottom: 5,
				},
			});
		}

		renderAuthor()
		{
			if (!this.#authorId)
			{
				return null;
			}

			return View(
				{
					testId: this.getTestId(`author-${this.#authorId}`),
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'flex-start',
					},
				},
				Avatar({
					id: this.#authorId,
					testId: this.getTestId(`author-${this.#authorId}-avatar`),
					name: this.#authorName,
					size: 18,
					uri: this.#authorImage,
					rounded: true,
					withRedux: true,
				}),
				Text6({
					testId: this.getTestId(`author-${this.#authorId}-name`),
					text: this.#authorName,
					numberOfLines: 1,
					ellipsize: 'end',
					color: Color.base4,
					style: {
						marginLeft: Indent.XS.toNumber(),
					},
				}),
			);
		}

		renderDate()
		{
			const text = this.#gratitudeCreatedAt;

			if (!text)
			{
				return null;
			}

			return Text5({
				testId: this.getTestId('date'),
				text: this.#gratitudeCreatedAt,
				color: Color.base3,
				style: {
					alignSelf: 'flex-start',
					marginTop: Indent.XS.toNumber(),
				},
			});
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const author = usersSelector.selectById(state, ownProps.authorId);
		const gratitude = selectById(state, ownProps.id) || {};

		const {
			name,
			createdAt,
			relatedPostId: postId,
			title,
		} = gratitude;

		return {
			author,
			name,
			title,
			postId,
			createdAt,
		};
	};

	module.exports = {
		ReduxGratitudeView: connect(mapStateToProps)(GratitudeView),
		GratitudeView: (props) => new GratitudeView(props),
	};
});
