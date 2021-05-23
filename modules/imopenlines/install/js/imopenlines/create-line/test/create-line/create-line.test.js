import {CreateLine} from '../../src/create-line';

let sandbox = null;

beforeEach(() => {
	sandbox = sinon.createSandbox();
});

afterEach(() => {
	sandbox.restore();
});

describe('CreateLine', () => {
	it('Should be a function', () => {
		assert(typeof CreateLine === 'function');
	});

	it('Should not call init with empty options', () => {
		sandbox.spy(CreateLine.prototype, 'init');
		new CreateLine({});
		assert.equal(CreateLine.prototype.init.calledOnce, false);
	});

	it('Should call init with path option', () => {

		const initStub = sandbox.stub(CreateLine.prototype, 'init').callsFake(() => {
			return true
		});

		new CreateLine({path: 'testPath'});
		assert.equal(initStub.calledOnce, true);
	});
});