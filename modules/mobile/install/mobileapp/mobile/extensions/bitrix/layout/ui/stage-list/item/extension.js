/**
 * @module layout/ui/stage-list/item
 */
jn.define('layout/ui/stage-list/item', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const { Color, Indent } = require('tokens');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Text5 } = require('ui-system/typography/text');

	const MIN_STAGE_HEIGHT = 70;

	const SEMANTICS = {
		PROCESS: 'P',
		SUCCESS: 'S',
		FAILED: 'F',
	};

	const { PureComponent } = require('layout/pure-component');
	const { StageStep } = require('layout/ui/stage-list/item/step');

	/**
	 * @class StageListItem
	 */
	class StageListItem extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.onSelectedStage = this.handlerOnSelectedStage.bind(this);
		}

		get counter()
		{
			return BX.prop.get(this.props, 'counter', {});
		}

		get unsuitable()
		{
			return BX.prop.getBoolean(this.counter, 'dropzone', false);
		}

		get showTotal()
		{
			return BX.prop.getBoolean(this.props, 'showTotal', false);
		}

		get showCount()
		{
			return BX.prop.getBoolean(this.props, 'showCount', false);
		}

		get showCounters()
		{
			return BX.prop.getBoolean(this.props, 'showCounters', false);
		}

		get showAllStagesItem()
		{
			return BX.prop.getBoolean(this.props, 'showAllStagesItem', false);
		}

		get hideBadge()
		{
			return BX.prop.getBoolean(this.props, 'hideBadge', false);
		}

		get showArrow()
		{
			return BX.prop.getBoolean(this.props, 'showArrow', false);
		}

		get stage()
		{
			return BX.prop.get(this.props, 'stage', {});
		}

		get showTunnels()
		{
			return BX.prop.getBoolean(this.props, 'showTunnels', false);
		}

		get isReversed()
		{
			return BX.prop.getBoolean(this.props, 'isReversed', false);
		}

		isUnsuitable()
		{
			if (this.showAllStagesItem)
			{
				return this.unsuitable;
			}

			return false;
		}

		handlerOnSelectedStage()
		{
			if (!this.enableStageSelect())
			{
				return;
			}

			Haptics.impactLight();

			const { onSelectedStage, stage } = this.props;
			if (typeof onSelectedStage === 'function')
			{
				onSelectedStage(stage);
			}
		}

		isReadOnly()
		{
			return BX.prop.getBoolean(this.props, 'readOnly', false);
		}

		enableStageSelect()
		{
			return BX.prop.getBoolean(this.props, 'enableStageSelect', false);
		}

		canMoveStages()
		{
			return BX.prop.getBoolean(this.props, 'canMoveStages', true);
		}

		renderBadgeContainer()
		{
			const { stage, index } = this.props;

			if (stage.semantics && stage.semantics !== SEMANTICS.PROCESS)
			{
				return this.renderFinalBadge();
			}

			let indexElement = null;
			if (Number(stage.id))
			{
				indexElement = Text5({
					color: Color.base4,
					numberOfLines: 1,
					ellipsize: 'end',
					text: String(index),
				});
			}
			else if (this.showAllStagesItem)
			{
				indexElement = IconView({
					size: 24,
					icon: Icon.BULLETED_LIST,
					color: Color.base4,
				});
			}

			return View(
				{
					testId: 'StageListItemIndexBadge',
					style: {
						marginLeft: Indent.XL3.toNumber(),
						marginRight: Indent.L.toNumber(),
						width: 51,
						height: MIN_STAGE_HEIGHT,
						justifyContent: 'center',
						alignItems: 'center',
						flexDirection: 'row',
						alignSelf: 'flex-start',
					},
				},
				!this.isReadOnly()
				&& this.canMoveStages()
				&& IconView({
					icon: Icon.DRAG,
					size: 24,
					color: Color.base4,
				}),
				indexElement,
			);
		}

		renderFinalBadge()
		{
			return View(
				{
					testId: 'StageListItemIndexBadge',
					style: {
						marginLeft: Indent.XL3.toNumber(),
						marginRight: Indent.L.toNumber(),
						width: 51,
						height: MIN_STAGE_HEIGHT,
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				IconView({
					size: 24,
					icon: this.props.stage.semantics === SEMANTICS.SUCCESS ? Icon.FLAG : Icon.FLAG_WITH_CROSS,
					color: this.props.stage.semantics === SEMANTICS.SUCCESS ? Color.accentMainSuccess : Color.accentMainAlert,
					style: {
						opacity: (this.isUnsuitable() ? 0.3 : 1),
					},
				}),
			);
		}

		renderContent()
		{
			const { index, active } = this.props;

			// @todo hide for users without permissions too
			const isDummyEditButton = (!this.stage.id || this.isReadOnly());

			return View(
				{
					testId: 'StageListItemInnerContent',
					style: {
						flexDirection: 'row',
						flexGrow: 2,
						alignItems: 'center',
					},
				},
				View(
					{
						style: {
							flexDirection: 'column',
							flexGrow: 2,
							paddingVertical: active ? Indent.S.toNumber() : Indent.XL.toNumber(),
						},
					},
					new StageStep({
						index,
						active,
						stage: this.stage,
						counter: this.counter,
						showTotal: !this.isUnsuitable() && this.showTotal,
						showCount: !this.isUnsuitable() && this.showCount,
						showCounters: !this.isUnsuitable() && this.showCounters,
						showAllStagesItem: this.showAllStagesItem,
						unsuitable: this.isUnsuitable(),
						showArrow: this.showArrow,
						isReversed: this.isReversed,
					}),
					this.renderAdditionalContent(this.stage),
				),
				this.renderEditButton(isDummyEditButton),
			);
		}

		hasTunnels(stage)
		{
			return this.showTunnels && stage.tunnels !== 0;
		}

		renderAdditionalContent(stage)
		{
			return null;
		}

		/**
		 * @param {boolean} isDummyView
		 * @returns {*}
		 */
		renderEditButton(isDummyView = false)
		{
			const testId = 'StageListItemEditButton';

			return View(
				{
					testId,
					style: {
						justifyContent: 'center',
						alignItems: 'flex-start',
						width: 42,
						height: MIN_STAGE_HEIGHT,
						alignSelf: 'flex-start',
						marginLeft: Indent.XL3.toNumber(),
					},
					onClick: () => {
						if (!isDummyView)
						{
							this.handlerOnOpenStageDetail(this.props.stage);
						}
					},
				},
				!isDummyView && IconView(
					{
						color: Color.base3,
						size: 24,
						icon: Icon.EDIT,
					},
				),
			);
		}

		handlerOnOpenStageDetail(stage)
		{
			const { onOpenStageDetail } = this.props;
			if (typeof onOpenStageDetail === 'function')
			{
				onOpenStageDetail(stage);
			}
		}

		getStageTestId()
		{
			const { id, color } = this.props.stage || {};
			const { index, active } = this.props;

			return `StageListItem-${id}-${index}-${color}-${active}`;
		}

		render()
		{
			if (!this.stage)
			{
				return View();
			}

			return View(
				{
					testId: this.getStageTestId(),
					style: {
						flexDirection: 'row',
						backgroundColor: Color.bgContentPrimary.toHex(),
						opacity: this.isStageEnabled() ? 1 : 0.52,
						minHeight: MIN_STAGE_HEIGHT,
					},
					onClick: this.enableStageSelect()
						&& this.isStageEnabled()
						&& this.onSelectedStage,
				},
				this.hideBadge ? null : this.renderBadgeContainer(),
				this.renderContent(),
			);
		}

		isStageEnabled()
		{
			return BX.prop.getBoolean(this.props, 'enabled', true);
		}
	}

	module.exports = { StageListItem, MIN_STAGE_HEIGHT };
});
