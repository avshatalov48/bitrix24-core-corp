/**
 * @module im/messenger/controller/chat-composer/lib/view/dialog-type
 */
jn.define('im/messenger/controller/chat-composer/lib/view/dialog-type', (require, exports, module) => {
	const { Area } = require('ui-system/layout/area');
	const { Card } = require('ui-system/layout/card');
	const { Theme } = require('im/lib/theme');
	const { Loc } = require('loc');
	const { isEqual } = require('utils/object');
	const { StageSelector, Icon } = require('ui-system/blocks/stage-selector');
	const { DialogType } = require('im/messenger/const');

	/**
	 * @class DialogTypeView
	 * @typedef {LayoutComponent<DialogTypeViewProps, DialogTypeViewState>} DialogTypeView
	 */
	class DialogTypeView extends LayoutComponent
	{
		/**
		 * @constructor
		 * @param {DialogTypeViewProps} props
		 */
		constructor(props)
		{
			super(props);

			this.state = { dialogType: this.props.dialogType };
		}

		componentWillUnmount()
		{
			this.props.callbacks.onDestroyView();
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			return !isEqual(this.props, nextProps) || !isEqual(this.state, nextState);
		}

		render()
		{
			const isCheckOpenEntityType = this.isOpenEntityType();

			return Area(
				{ isFirst: true },
				Card(
					{ border: true, excludePaddingSide: { all: true } },
					this.renderCloseTypeSelector(!isCheckOpenEntityType),
					this.renderDivider(),
					this.renderOpenTypeSelector(isCheckOpenEntityType),
				),
			);
		}

		/**
		 * @return {boolean} isCheck
		 * @return {LayoutComponent}
		 */
		renderOpenTypeSelector(isCheck)
		{
			const props = {
				title: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_DIALOG_TYPE_OPEN_TITLE'),
				subtitle: this.getLocSubtitle(true),
				rightIconColor: Theme.color.accentMainPrimary,
				rightIcon: Icon.CHECK,
			};

			if (!isCheck)
			{
				props.rightIconColor = Theme.color.bgContentPrimary;
				props.onClick = () => this.onClickType(true);
			}

			return StageSelector(props);
		}

		/**
		 * @return {boolean} isCheck
		 * @return {LayoutComponent}
		 */
		renderCloseTypeSelector(isCheck)
		{
			const props = {
				title: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_DIALOG_TYPE_CLOSE_TITLE'),
				subtitle: this.getLocSubtitle(false),
				rightIconColor: Theme.color.accentMainPrimary,
				rightIcon: Icon.CHECK,
			};

			if (!isCheck)
			{
				props.rightIconColor = Theme.color.bgContentPrimary;
				props.onClick = () => this.onClickType(false);
			}

			return StageSelector(props);
		}

		renderDivider()
		{
			return View({
				style: {
					width: '100%',
					bottom: 0,
					borderBottomWidth: 1,
					borderBottomColor: Theme.color.bgSeparatorPrimary.toHex(),
				},
			});
		}

		/**
		 * @param {boolean} isOpen
		 * @return {string}
		 */
		getLocSubtitle(isOpen)
		{
			if (isOpen)
			{
				return this.isChatType()
					? Loc.getMessage('IMMOBILE_CHAT_COMPOSER_DIALOG_TYPE_GROUP_CHAT_OPEN_SUBTITLE')
					: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_DIALOG_TYPE_CHANNEL_OPEN_SUBTITLE');
			}

			return this.isChatType()
				? Loc.getMessage('IMMOBILE_CHAT_COMPOSER_DIALOG_TYPE_GROUP_CHAT_CLOSE_SUBTITLE')
				: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_DIALOG_TYPE_CHANNEL_CLOSE_SUBTITLE');
		}

		/**
		 * @return {boolean}
		 */
		isChatType()
		{
			return [DialogType.chat, DialogType.open].includes(this.state.dialogType);
		}

		/**
		 * @return {boolean}
		 */
		isOpenEntityType()
		{
			return this.state.dialogType === DialogType.open || this.state.dialogType === DialogType.openChannel;
		}

		/**
		 * @param {boolean} isSetOpenEntityType
		 */
		async onClickType(isSetOpenEntityType)
		{
			this.props.callbacks.onChangeDialogType(isSetOpenEntityType);
		}
	}

	module.exports = { DialogTypeView };
});
