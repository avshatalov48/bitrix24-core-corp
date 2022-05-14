import History from "./history";

/** @memberof BX.Crm.Timeline.Items */
export default class Relation extends History
{
	constructor()
	{
		super();
	}

	getTitle()
	{
		return this.getMessage('title');
	}

	getWrapperClassName()
	{
		return "crm-entity-stream-section-createEntity";
	}

	prepareContentDetails()
	{
		const entityData = this.getAssociatedEntityData();

		let link = BX.prop.getString(entityData, "SHOW_URL", "");
		if (link.indexOf('/') !== 0)
		{
			link = '#';
		}

		const content =
			this.getMessage('contentTemplate')
				.replace('#ENTITY_TYPE_CAPTION#', BX.Text.encode(BX.prop.getString(entityData, 'ENTITY_TYPE_CAPTION', '')))
				.replace('#LEGEND#', '')
				.replace('#LINK#', BX.Text.encode(link))
				.replace('#LINK_TITLE#', BX.Text.encode(BX.prop.getString(entityData, "TITLE", '')))
		;

		const nodes = [];
		nodes.push(
			BX.create('SPAN', { html: content })
		);

		return nodes;
	}
}
