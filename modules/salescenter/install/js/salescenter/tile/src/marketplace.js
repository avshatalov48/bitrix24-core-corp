import {Type} from 'main.core';
import {Base} from "./base";

class Marketplace extends Base
{
	constructor(props)
	{
		super(props);

		this.id = 			Type.isInteger(this.id) ? this.id : 0;
		this.code = 		Type.isString(props.code) && props.code.length > 0 ? props.code : '';
		this.info = 		Type.isString(props.info) && props.info.length > 0 ? props.info : '';
		this.sort = 		Type.isInteger(props.sort) ? props.sort : 0;
		this.showTitle = 	Type.isBoolean(props.showTitle) ? props.showTitle : false;
		this.installedApp = Type.isBoolean(props.installedApp) ? props.installedApp : false;
	}

	static type()
	{
		return 'marketplace';
	}
	getType()
	{
		return Marketplace.type();
	}

	isInstalled()
	{
		return this.installedApp;
	}
}

export
{
	Marketplace
}
