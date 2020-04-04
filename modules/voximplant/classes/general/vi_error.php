<?
class CVoxImplantError
{
	public $method = '';
	public $code = '';
	public $msg = '';
	public $error = false;

	public function __construct($method, $code, $msg)
	{
		if ($method != null)
		{
			$this->method = $method;
			$this->code = $code;
			$this->msg = $msg;
			$this->error = true;
		}
	}
}
?>
