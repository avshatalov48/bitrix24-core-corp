/**
 * @module text-editor/svgom/svg-node
 */
jn.define('text-editor/svgom/svg-node', (require, exports, module) => {
	const { Type } = require('type');

	class SvgNode
	{
		name = '';
		attributes = {};
		children = [];
		void = false;

		static encodeSvgText(text)
		{
			if (Type.isStringFilled(text))
			{
				return text
					.replaceAll('<', '&#60;')
					.replaceAll('>', '&#62;');
			}

			return text;
		}

		static encodeAttributeValue(text)
		{
			if (Type.isStringFilled(text))
			{
				return text.replaceAll('"', '\\"');
			}

			return text;
		}

		constructor(options = {})
		{
			this.setName(options.name);
			this.setAttributes(options.attributes);
			this.setChildren(options.children);
			this.setVoid(options.void);
		}

		setName(name)
		{
			if (Type.isStringFilled(name))
			{
				this.name = name;
			}
		}

		getName()
		{
			return this.name;
		}

		setAttributes(attributes)
		{
			if (Type.isPlainObject(attributes))
			{
				this.attributes = { ...attributes };
			}
		}

		addAttributes(attributes)
		{
			if (Type.isPlainObject(attributes))
			{
				this.attributes = { ...this.attributes, ...attributes };
			}
		}

		setAttribute(name, value)
		{
			if (Type.isStringFilled(name) && !Type.isNil(value))
			{
				this.attributes[name] = String(value);
			}
		}

		removeAttribute(name)
		{
			if (Type.isStringFilled(name))
			{
				delete this.attributes[name];
			}
		}

		getAttribute(name)
		{
			if (Type.isStringFilled(name))
			{
				return this.attributes[name];
			}

			return null;
		}

		getAttributes()
		{
			return { ...this.attributes };
		}

		setChildren(children)
		{
			if (Type.isArray(children))
			{
				this.children = [...children];
			}
		}

		appendChild(...children)
		{
			this.children.push(...children);
		}

		getChildren()
		{
			return [...this.children];
		}

		setVoid(value)
		{
			if (Type.isBoolean(value))
			{
				this.void = value;
			}
		}

		isVoid()
		{
			return this.void;
		}

		toStringAttributes()
		{
			return Object.entries(this.getAttributes())
				.map(([name, value]) => {
					return `${name}="${SvgNode.encodeAttributeValue(value)}"`;
				})
				.join(' ');
		}

		makeOpeningTag()
		{
			const name = this.getName();
			const attributes = this.toStringAttributes();
			const attributesContent = Type.isStringFilled(attributes) ? ` ${attributes}` : '';

			return `<${name}${attributesContent}>`;
		}

		makeClosingTag()
		{
			return `</${this.getName()}>`;
		}

		getContent()
		{
			return this.getChildren()
				.map((child) => {
					return child.toString();
				})
				.join('\n');
		}

		toString()
		{
			const openingTag = this.makeOpeningTag();
			if (this.isVoid())
			{
				return openingTag;
			}

			const closingTag = this.makeClosingTag();
			const content = this.getContent();

			return `${openingTag}${content}${closingTag}`;
		}
	}

	module.exports = {
		SvgNode,
	};
});
