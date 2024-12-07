/**
 * @module layout/ui/fields/crm-element/theme/air/src/entity
 */
jn.define('layout/ui/fields/crm-element/theme/air/src/entity', (require, exports, module) => {
	const { Indent, Color } = require('tokens');
	const { Text4, Text5 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const aliases = {
		contact: 'contact',
		lead: 'lead',
		smart_invoice: 'invoice',
		invoice: 'invoice',
		deal: 'handshake',
		company: 'company',
		dynamic_multiple: 'activity',
	};

	/**
	 * @param {object} field
	 * @param {string} id
	 * @param {string} title
	 * @param {string} subtitle
	 * @param {string} imageUrl
	 * @param {string} avatar
	 * @param {string} type
	 * @param {boolean} isFirst
	 * @param {boolean} isLast
	 * @param {object} customData
	 */
	const Entity = ({
		field,
		id,
		title,
		subtitle,
		imageUrl,
		avatar,
		type,
		isFirst,
		isLast,
		customData,
	}) => {
		const testId = `${field.testId}_CRM_ELEMENT_${id}`;
		const onEntityClick = field.openEntity.bind(field, { id, type });
		const typeNameTitle = customData?.entityInfo?.typeNameTitle || subtitle;

		return View(
			{
				testId,
				style: {
					flexDirection: 'row',
					justifyContent: 'flex-start',
					marginTop: !isFirst && Indent.M.toNumber(),
					paddingTop: Indent.S.toNumber(),
				},
				onClick: field.isEmpty() ? field.getContentClickHandler() : onEntityClick,
			},
			IconView({
				icon: aliases[type] || Icon.CRM,
				size: 24,
				color: Color.accentMainPrimaryalt,
				testId: `${testId}_ICON`,
			}),
			View(
				{
					style: {
						flex: 2,
						flexDirection: 'row',
						justifyContent: 'space-between',
						marginLeft: Indent.M.toNumber(),
						borderBottomWidth: 1,
						borderBottomColor: !isLast && Color.bgSeparatorSecondary.toHex(),
						paddingBottom: Indent.XL.toNumber(),
					},
				},
				View(
					{
						style: {
							flexDirection: 'column',
							flexShrink: 2,
							marginRight: Indent.M.toNumber(),
							justifyContent: 'center',
						},
					},
					Text4({
						testId: `${testId}_TITLE`,
						text: title,
						style: {
							color: Color.base2.toHex(),
							marginBottom: Indent.XS2.toNumber(),
							flexShrink: 2,
						},
						numberOfLines: 1,
						ellipsize: 'end',
					}),
					Text5({
						testId: `${testId}_SUBTITLE`,
						text: typeNameTitle,
						style: {
							color: Color.base3.toHex(),
							flexShrink: 2,
						},
						numberOfLines: 1,
						ellipsize: 'end',
					}),
				),
				id
				&& !field.isReadOnly()
				&& !field.isRestricted()
				&& View(
					{
						onClick: () => field.removeEntity(id, type),
						style: {
							width: 20 + Indent.M.toNumber(),
							paddingLeft: Indent.M.toNumber(),
							height: '100%',
						},
					},
					IconView({
						icon: 'cross',
						size: 20,
						color: Color.base5,
					}),
				),
			),
		);
	};

	module.exports = {
		Entity,
	};
});
