import {Type} from 'main.core';

class Base
{
	constructor(props)
	{
		this.id  = 			Type.isString(props.id) && props.id.length > 0 ? props.id : '';
		this.name = 		Type.isString(props.name) && props.name.length > 0 ? props.name : '';
		this.color = 		Type.isString(props.color) && props.color.length > 0 ? props.color : '';
		this.selected = 	Type.isBoolean(props.selected) ? props.selected : false;
		this.colorText = 	Type.isString(props.colorText) && props.colorText.length > 0 ? props.colorText : '';
	}

	getType()
	{
		return '';
	}
}

export {
	Base
}