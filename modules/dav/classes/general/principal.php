<?
class CDavPrincipal
{
	private $userId;
	private $login;
	private $name;
	private $email;
	private $arUserGroups = array();
	private $isAdmin = false;

	private $principalURL = "";

	public function __construct($user)
	{
		if (is_array($user))
			$this->InitializeFromArray($user);
		elseif (is_int($user))
			$this->InitializeFromId($user);
		elseif (is_bool($user) && $user || is_string($user) && strtolower($user) == "current")
			$this->InitializeFromCurrent();
	}

	public function InitializeFromCurrent()
	{
		global $USER;

		$this->userId = $USER->GetID();
		$this->login = $USER->GetLogin();
		$this->name = $USER->GetFullName();
		if (empty($this->name))
			$this->name = $this->login;
		$this->email = $USER->GetEmail();
		$this->isAdmin = $USER->IsAdmin();
		$this->arUserGroups = $USER->GetUserGroupArray();
	}

	public function InitializeFromArray($arUser)
	{
		$this->userId = $arUser['ID'];
		$this->login = $arUser['LOGIN'];
		$this->name = $arUser["NAME"].(strlen($arUser["NAME"]) <= 0 || strlen($arUser["LAST_NAME"]) <= 0 ? "" : " ").$arUser["LAST_NAME"];
		if (empty($this->name))
			$this->name = $this->login;
		$this->email = $arUser['EMAIL'];

		$this->arUserGroups = CUser::GetUserGroup($this->userId);
		$this->isAdmin = in_array(1, $this->arUserGroups);
	}

	public function InitializeFromId($userId)
	{
		$dbUser = CUser::GetByID($userId);
		if ($arUser = $dbUser->Fetch())
			$this->InitializeFromArray($arUser);
	}

	public function Id()
	{
		return $this->userId;
	}

	public function Login()
	{
		return $this->login;
	}

	public function Name()
	{
		return $this->name;
	}

	public function Email()
	{
		return $this->email;
	}

	public function IsAdmin()
	{
		return $this->isAdmin;
	}

	public function GetPrincipalUrl(CDavRequest $request)
	{
		if (strlen($this->principalURL) > 0)
			return $this->principalURL;

		if (strpos($request->GetBaseUri(), 'http') === 0)
			$this->principalURL = CDav::CheckIfRightSlashAdded($request->GetBaseUri());
		else
			$this->principalURL = ($request->GetParameter("HTTPS") === "on" ? "https" : "http").'://'.$request->GetParameter('HTTP_HOST').$request->GetParameter('SCRIPT_NAME').'/';

		$this->principalURL .= 'principals/user/'.$this->Login().'/';

		if (!$request->IsUrlRequired())
			$this->principalURL = parse_url($this->principalURL, PHP_URL_PATH);

		return $this->principalURL;
	}
}
?>