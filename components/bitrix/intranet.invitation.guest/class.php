<?

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\JsonPayload;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\Json;
use Bitrix\Socialnetwork\Integration\UI\EntitySelector;
use Bitrix\Intranet\Integration;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class IntranetInvitationGuest extends \CBitrixComponent implements Controllerable, \Bitrix\Main\Errorable
{
	/** @var  ErrorCollection */
	protected $errorCollection;

	public function __construct($component = null)
	{
		$this->errorCollection = new ErrorCollection();

		parent::__construct($component);
	}

	public function executeComponent()
	{
		if (!$this->includeModules())
		{
			ShowError('Some modules not installed.');
			return;
		}

		if (!$this->hasCurrentUserAccess())
		{
			ShowError('You do not have permissions to invite guests.');
			return;
		}

		$this->prepareParams();
		$this->prepareResult();
		$this->includeComponentTemplate();
	}

	protected function includeModules()
	{
		return (
			Loader::includeModule('intranet') &&
			Loader::includeModule('socialnetwork') &&
			Loader::includeModule('mail')
		);
	}

	protected function hasCurrentUserAccess()
	{
		return EntitySelector\UserProvider::isIntranetUser();
	}

	protected function prepareParams()
	{
		$this->arParams['SET_TITLE'] =
			isset($this->arParams['SET_TITLE'])
				? $this->arParams['SET_TITLE'] === 'Y' || $this->arParams['SET_TITLE'] === true
				: true
		;

		$this->arParams['USER_OPTIONS'] =
			isset($this->arParams['USER_OPTIONS']) && is_array($this->arParams['USER_OPTIONS'])
				? $this->arParams['USER_OPTIONS']
				: []
		;

		$this->arParams['ROWS'] =
			isset($this->arParams['ROWS']) && is_array($this->arParams['ROWS'])
				? $this->arParams['ROWS']
				: []
		;
	}

	protected function prepareResult()
	{
		if ($this->arParams['SET_TITLE'])
		{
			/**@var \CAllMain*/
			$GLOBALS['APPLICATION']->setTitle(Loc::getMessage('INTRANET_INVITATION_GUEST_PAGE_TITLE'));
		}

		return true;
	}

	public function configureActions()
	{
		return [];
	}

	public function addGuestsAction(JsonPayload $payload)
	{
		if (!$this->includeModules())
		{
			$this->errorCollection->add([new Error('Some modules not installed.')]);
			return null;
		}

		if (!$this->hasCurrentUserAccess())
		{
			$this->errorCollection->add([new Error('You do not have permissions to invite guests.')]);
			return null;
		}

		$request = $payload->getData();
		$request = is_array($request) ? $request : [];
		$guests = is_array($request['guests']) ? $request['guests'] : [];
		$userOptions = is_array($request['userOptions']) ? $request['userOptions'] : [];
		$users = [];

		foreach ($guests as $guest)
		{
			if (is_array($guest))
			{
				$user = Integration\Mail\EmailUser::create($guest);
				if ($user)
				{
					$users[] = EntitySelector\UserProvider::makeItem($user, $userOptions);
				}
			}
		}

		return [
			'users' => $users
		];
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}
}