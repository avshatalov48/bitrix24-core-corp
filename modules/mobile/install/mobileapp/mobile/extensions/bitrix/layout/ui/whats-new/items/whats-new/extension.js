/**
 * @module layout/ui/whats-new/items/whats-new
 */
jn.define('layout/ui/whats-new/items/whats-new', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { Text4 } = require('ui-system/typography/text');
	const { H4 } = require('ui-system/typography/heading');
	const { Loc } = require('loc');
	const { makeLibraryImagePath } = require('asset-manager');
	const { WhatsNewItemContent } = require('layout/ui/whats-new/items/whats-new/redux-content');

	const FINISH_NOVELTY_TYPE = 'FINISH_NOVELTY';
	const ERROR_TYPE = 'ERROR';
	const EMPTY_TYPE = 'EMPTY';

	/**
	 * @class WhatsNewItem
	 * @typedef {Object} WhatsNewItemProps
	 * @property {string} [testId]
	 */
	class WhatsNewItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.reduxComponentRef = null;
		}

		get type()
		{
			return this.props.item.type;
		}

		get testId()
		{
			return `${this.props.testId}-${this.type}-item`;
		}

		bindRef = (ref) => {
			if (ref)
			{
				this.reduxComponentRef = ref;
			}
		};

		render()
		{
			switch (this.type)
			{
				case FINISH_NOVELTY_TYPE:
					return this.renderFinishNoveltyContent();
				case ERROR_TYPE:
					return this.renderErrorContent();
				case EMPTY_TYPE:
					return this.renderEmptyContent();
				default:
					return this.renderDefaultContent();
			}
		}

		renderFinishNoveltyContent()
		{
			return View(
				{
					style: {
						backgroundColor: Color.accentSoftBlue3.toHex(),
					},
				},
				Card(
					{
						testId: `${this.testId}-finish-novelty`,
						hideCross: true,
						design: CardDesign.PRIMARY,
						style: {
							marginTop: Indent.M.toNumber(),
							marginBottom: Indent.XS.toNumber(),
							marginHorizontal: Indent.XL3.toNumber(),
						},
					},
					View(
						{
							style: {
								alignItems: 'center',
							},
						},
						Image({
							resizeMode: 'contain',
							style: {
								width: 108,
								height: 108,
								marginBottom: Indent.XL4.toNumber(),
							},
							svg: {
								uri: makeLibraryImagePath('whats-new-finish-novelty.svg', 'graphic'),
							},
						}),
						H4({
							text: Loc.getMessage('WHATS_NEW_ITEM_FINISH_NOVELTY_TITLE'),
							color: Color.base1,
							style: {
								marginBottom: Indent.M.toNumber(),
							},
						}),
						Text4({
							color: Color.base2,
							text: Loc.getMessage('WHATS_NEW_ITEM_FINISH_NOVELTY_DESCRIPTION'),
							style: {
								textAlign: 'center',
							},
						}),
					),
				),
			);
		}

		renderErrorContent()
		{
			return View(
				{
					style: {
						backgroundColor: Color.accentSoftBlue3.toHex(),
					},
				},
				Card(
					{
						testId: `${this.testId}-error`,
						hideCross: true,
						design: CardDesign.WARNING,
						style: {
							marginTop: Indent.M.toNumber(),
							marginBottom: Indent.XS.toNumber(),
							marginHorizontal: Indent.XL3.toNumber(),

							borderWidth: 1,
							borderColor: Color.accentSoftOrange2.toHex(),
						},
					},
					View(
						{
							style: {
								alignItems: 'center',
							},
						},
						Image({
							resizeMode: 'contain',
							style: {
								width: 108,
								height: 108,
								marginBottom: Indent.XL4.toNumber(),
							},
							svg: {
								uri: makeLibraryImagePath('status-problem.svg', 'graphic'),
							},
						}),
						H4({
							text: Loc.getMessage('WHATS_NEW_ITEM_ERROR_TITLE'),
							color: Color.base1,
							style: {
								marginBottom: Indent.M.toNumber(),
							},
						}),
						Text4({
							color: Color.base2,
							text: Loc.getMessage('WHATS_NEW_ITEM_ERROR_DESCRIPTION'),
							style: {
								textAlign: 'center',
							},
						}),
					),
				),
			);
		}

		renderEmptyContent()
		{
			return View(
				{
					style: {
						backgroundColor: Color.accentSoftBlue3.toHex(),
					},
				},
				Card(
					{
						testId: `${this.testId}-empty`,
						hideCross: true,
						design: CardDesign.SECONDARY,
						style: {
							marginTop: Indent.M.toNumber(),
							marginBottom: Indent.XS.toNumber(),
							marginHorizontal: Indent.XL3.toNumber(),
						},
					},
					View(
						{
							style: {
								alignItems: 'center',
							},
						},
						Image({
							resizeMode: 'contain',
							style: {
								width: 108,
								height: 108,
								marginBottom: Indent.XL4.toNumber(),
							},
							svg: {
								uri: makeLibraryImagePath('whats-new-other.svg', 'graphic'),
							},
						}),
						Text4({
							color: Color.base2,
							text: Loc.getMessage('WHATS_NEW_ITEM_EMPTY_TITLE'),
							style: {
								textAlign: 'center',
							},
						}),
					),
				),
			);
		}

		renderDefaultContent()
		{
			return WhatsNewItemContent({
				id: this.props.item.id,
				testId: this.testId,
				bindReduxComponentRef: this.bindRef,
			});
		}
	}

	module.exports = {
		WhatsNewItem,
		FINISH_NOVELTY_TYPE,
		ERROR_TYPE,
		EMPTY_TYPE,
	};
});
