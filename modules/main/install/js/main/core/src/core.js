import './internal/bx';

import Type from './lib/type';
import Reflection from './lib/reflection';
import Text from './lib/text';
import Dom from './lib/dom';
import Browser from './lib/browser';
import Event from './lib/event';
import Http from './lib/http';
import Runtime from './lib/runtime';
import Loc from './lib/loc';
import Tag from './lib/tag';
import Uri from './lib/uri';
import Validation from './lib/validation';
import Cache from './lib/cache';
import BaseError from './lib/base-error';

export {
	Type,
	Reflection,
	Text,
	Dom,
	Browser,
	Event,
	Http,
	Runtime,
	Loc,
	Tag,
	Uri,
	Validation,
	Cache,
	BaseError,
};

export * from './core-compatibility';

if (global && global.window && global.window.BX)
{
	Object.assign(global.window.BX, exports);
}