<?php
namespace Bitrix\ImConnector\Connectors;

use Bitrix\Main;
use Bitrix\Main\UserTable;

use Bitrix\ImConnector\User;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\Library;

/**
 * Class InstagramBase
 *
 * @package Bitrix\ImConnector\Connectors
 */
abstract class InstagramBase extends Base
{
	/**
	 * Connector constructor.
	 * @param $idConnector
	 */
	public function __construct($idConnector)
	{
		parent::__construct($idConnector);
		$this->userPrefix = 'instagram';
	}

	//User
	/**
	 * @param array $params
	 * @param Result $result
	 * @return Result
	 */
	abstract protected function getUserData(array $params, Result $result): Result;

	/**
	 * @param array $user
	 * @return Result
	 */
	public function getUserByUserCode(array $user): Result
	{
		$resultParent = parent::getUserByUserCode($user);

		if($resultParent->isSuccess())
		{
			$result = $resultParent;
		}
		else
		{
			$result = new Result();

			$idFbInstagramDirect = 0;
			$idFbInstagram = 0;
			$md5FbInstagramDirect = '';
			$md5FbInstagram = '';

			$rawUser = UserTable::getList([
					'select' => [
						'ID',
						'XML_ID',
						'MD5' => 'UF_CONNECTOR_MD5'
					],
					'filter' => [
						'=EXTERNAL_AUTH_ID' => Library::NAME_EXTERNAL_USER,
						'=XML_ID' => [
							'fbinstagramdirect|' . $user['id'],
							'fbinstagram|' . $user['united_id']
						]
					]
				]
			);

			while ($rowUser = $rawUser->fetch())
			{
				if ($rowUser['XML_ID'] === 'fbinstagramdirect|' . $user['id'])
				{
					$idFbInstagramDirect = $rowUser['ID'];
					$md5FbInstagramDirect = $rowUser['MD5'];
				}
				elseif ($rowUser['XML_ID'] === 'fbinstagram|' . $user['united_id'])
				{
					$idFbInstagram = $rowUser['ID'];
					$md5FbInstagram = $rowUser['MD5'];
				}
			}

			if (
				!empty($idFbInstagramDirect)
				&& !empty($idFbInstagram)
			)
			{
				$result = $this->getUserData(
					[
						'ID_FB_INSTAGRAM_DIRECT' => $idFbInstagramDirect,
						'MD5_FB_INSTAGRAM_DIRECT' => $md5FbInstagramDirect,
						'ID_FB_INSTAGRAM' => $idFbInstagram,
						'MD5_FB_INSTAGRAM' => $md5FbInstagram
					],
					$result
				);

				User::addUniqueReplacementAgent($idFbInstagramDirect, [$idFbInstagram]);
			}
			elseif (
				!empty($idFbInstagramDirect)
				|| !empty($idFbInstagram)
			)
			{
				$idUser = 0;
				$md5User = '';
				if(!empty($idFbInstagramDirect))
				{
					$idUser = $idFbInstagramDirect;
					$md5User = $md5FbInstagramDirect;
				}
				if (!empty($idFbInstagram))
				{
					$idUser = $idFbInstagram;
					$md5User = $md5FbInstagram;
				}

				$cUser = new \CUser;
				$fields = ['XML_ID' => $this->userPrefix . '|' . $user['id']];

				$cUser->Update($idUser, $fields);

				$result->setResult([
					'ID' => $idUser,
					'MD5' => $md5User
				]);
			}
			else
			{
				//user record does not yet exist, it will be created on next step.
				$result->addError(new Main\Error('User does not yet exist'));
			}
		}

		return $result;
	}
}