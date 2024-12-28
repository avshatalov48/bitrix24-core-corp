/**
 * @module im/messenger/controller/chat-composer/lib/area/participants-list
 */
jn.define('im/messenger/controller/chat-composer/lib/area/participants-list', (require, exports, module) => {
	const { EntitySelectorElementType } = require('im/messenger/const');
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Color, Indent } = require('tokens');
	const { Text4 } = require('ui-system/typography');

	const { Participant } = require('im/messenger/controller/chat-composer/lib/element/participant');

	/**
	 * @class ParticipantsList
	 * @typedef {LayoutComponent<ParticipantsListProps, {}>} ParticipantsList
	 */
	class ParticipantsList extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				View(
					{
						style: {
							paddingTop: Indent.M.toNumber(),
							paddingLeft: Indent.XL3.toNumber(),
							paddingBottom: Indent.L.toNumber(),
						},
					},
					Text4({
						text: Loc.getMessage('IMMOBILE_CHAT_COMPOSER_PARTICIPANT_TITLE'),
						color: Color.base4,
					}),
				),
				ListView({
					style: {
						flex: 1,
					},
					data: [{ items: this.prepareItems(this.props.items) }],
					renderItem: (props) => {
						return Participant(props);
					},
				}),
			);
		}

		/**
		 * @param {Array<NestedDepartmentSelectorItem>} items
		 * @return {Array<ParticipantProps>}
		 */
		prepareItems(items)
		{
			return items.map((item) => {
				let title = item.title;
				if (item.type === 'department' && Type.isStringFilled(item.customData.sourceEntity.shortTitle))
				{
					title = item.customData.sourceEntity.shortTitle;
				}

				/** @type {ParticipantProps} */
				return {
					id: item.id,
					title,
					subtitle: this.getItemSubtitle(item),
					type: item.type,
					uri: item.imageUrl,
					key: `${item.type}-${item.id}`,
				};
			});
		}

		/**
		 * @param {NestedDepartmentSelectorItem} item
		 */
		getItemSubtitle(item)
		{
			if (item.type === EntitySelectorElementType.user)
			{
				return item.customData.position
			}

			if (item.type === EntitySelectorElementType.department)
			{
				return Loc.getMessage('IMMOBILE_CHAT_COMPOSER_DEPARTMENT_ITEM_SUBTITLE');
			}

			return '';
		}
	}

	module.exports = { ParticipantsList };
});
