/**
 * Use deps 'utils/iterators'
 * @module iterators
 */

(function(){

	this.ArrayIterator = class ArrayIterator
	{
		constructor(array = [])
		{
			this.array = array;
			this.index = 0;
		}

		next()
		{
			let result = { value: undefined, done: true };

			if (this.index < this.array.length)
			{
				result.value = this.array[this.index];
				result.done = false;
				this.index++;
			}

			return result;
		}
	};

	this.ObjectIterator = class ObjectIterator extends ArrayIterator
	{
		constructor(object)
		{
			super([]);

			for (let index in object)
			{
				if (object.hasOwnProperty(index))
				{
					this.array.push(object[index]);
				}
			}
		}
	};

})();