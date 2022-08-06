import {Type} from 'main.core';

class Base
{
	constructor(props)
	{
		this.id  = 			+props.id || null;
		this.img  = 		Type.isString(props.img) && props.img.length > 0 ? props.img : '';
		this.link = 		Type.isString(props.link) && props.link.length > 0 ? props.link : '';
		this.name = 		Type.isString(props.name) && props.name.length > 0 ? props.name : '';
		this.showTitle = 	Type.isBoolean(props.showTitle) ? props.showTitle : false;
		this.group  = 		Type.isString(props.group) ? props.group : '';
	}

	getType()
	{
		return '';
	}
}

export {
	Base
}