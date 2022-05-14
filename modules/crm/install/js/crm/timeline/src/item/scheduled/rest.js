import Activity from "./activity";

/** @memberof BX.Crm.Timeline.Items.Scheduled */
export default class Rest extends Activity
{
	constructor()
	{
		super();
	}

	getWrapperClassName()
	{
		return "";
	}

	getIconClassName()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-rest";
	}

	prepareContent(options)
	{
		const wrapper = super.prepareContent(options);
		const data = this.getAssociatedEntityData();

		if (data['APP_TYPE'] && data['APP_TYPE']['ICON_SRC'])
		{
			const iconNode = wrapper.querySelector('[class="' + this.getIconClassName() + '"]');
			if (iconNode)
			{
				iconNode.style.backgroundImage = "url('" +  data['APP_TYPE']['ICON_SRC'] + "')";
				iconNode.style.backgroundPosition = "center center";
			}
		}

		return wrapper;
	}

	getTypeDescription()
	{
		const entityData = this.getAssociatedEntityData();
		if (entityData['APP_TYPE'] && entityData['APP_TYPE']['NAME'])
		{
			return entityData['APP_TYPE']['NAME'];
		}

		return this.getMessage("restApplication");
	}

	static create(id, settings)
	{
		const self = new Rest();
		self.initialize(id, settings);
		return self;
	}
}
