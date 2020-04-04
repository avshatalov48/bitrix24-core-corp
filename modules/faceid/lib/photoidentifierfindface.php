<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage faceidcontroller
 * @copyright  2001-2016 Bitrix
 */

namespace Bitrix\FaceId;

/**
 * Class description
 * @package    bitrix
 * @subpackage faceidcontroller
 */
class PhotoIdentifierFindFace extends PhotoIdentifier
{
	private $token = '';

	/**
	 * @param string $token
	 */
	public function setToken($token)
	{
		$this->token = $token;
	}

	public function identify($imageContent, $galleryId = null)
	{
		$found = false;
		$items = array();

		$http = new \Bitrix\Main\Web\HttpClient();
		$resource = fopen($imageContent, 'r');

		try
		{
			$http->setHeader('Authorization', 'Token '.$this->token);
			$response = $http->post('https://api.findface.pro/v0/faces/gallery/'.$galleryId.'/identify/', array(
				'photo' => $resource,
				'threshold' => 'medium'
			), true);

			if ($http->getStatus() == 200)
			{
				$body = json_decode($response, true);
				$bodyResults = array_values($body['results']);

				if (!empty($bodyResults[0]))
				{
					$found = true;
					$person = $bodyResults[0][0]; // just 0 for the old protocol

					$items[] = array(
						'face_id' => $person['face']['id'],
						'meta' => $person['face']['meta'],
						'confidence' => round($person['confidence'],4)*100,
						'thumbnail' => $person['face']['thumbnail']
					);

					$msg = 'Found '.$person['face']['meta'].' ('.(round($person['confidence'],2)*100).'%)<br>'
						.'<img height="80" src="'.$person['face']['thumbnail'].'">';
				}
				else
				{
					$msg = 'Unknown person';
				}
			}
			else
			{
				$msg = $http->getStatus().': '.$response;
			}

			/*
			//$res = $client->request('POST', 'https://api.findface.pro/identify/', array(
			$res = $client->request('POST', 'https://api.findface.pro/v0/faces/gallery/'.$galleryId.'/identify/', array(
				'multipart' => array(
					array(
						'name' => 'photo',
						'contents' => $resource
					)
				),
				'headers' => array(
					'Authorization' => 'Token '.$this->token
				)
			));

			if ($res->getStatusCode() == 200)
			{
				$body = json_decode($res->getBody(), true);
				$bodyResults = array_values($body['results']);

				if (!empty($bodyResults[0]))
				{
					$found = true;
					$person = $bodyResults[0][0]; // just 0 for the old protocol

					$msg = 'Found '.$person['face']['meta'].' ('.(round($person['confidence'],2)*100).'%)<br>'
						.'<img height="80" src="'.$person['face']['thumbnail'].'">';
				}
				else
				{
					$msg = 'Unknown person';
				}

			}
			else
			{
				$msg = $res->getStatusCode().': '.$res->getBody();
			}
			*/
		}
		catch (\Exception $e)
		{
			$msg = $e->getMessage();
		}

		return array(
			'found' => $found,
			'items' => $items,
			'msg' => $msg
		);
	}

	public function addPerson($imageContent, $meta, $galleryId = null)
	{
		$success = false;
		$id = '';

		$http = new \Bitrix\Main\Web\HttpClient();
		$resource = fopen($imageContent, 'r');

		try
		{
			$http->setHeader('Authorization', 'Token '.$this->token);
			$response = $http->post('https://api.findface.pro/face/', array(
				'photo' => $resource,
				'meta' => $meta,
				'galleries' => $galleryId
			), true);

			if ($http->getStatus() == 200)
			{
				$body = json_decode($response, true);
				if (!empty($body['id']))
				{
					$success = true;
					$id = $body['id'];
					$msg = 'added '.$body['meta'].' ('.$body['id'].')';
				}
				else
				{
					$msg = 'fail: '.$body['code'].' '.$body['reason'];
				}
			}
			else
			{
				$msg = $http->getStatus().': '.$response;
			}
		}
		catch (\Exception $e)
		{
			$msg = $e->getMessage();
		}

		return array(
			'result' => $success,
			'face_id' => $id,
			'msg' => $msg
		);
	}

	public function createGallery($id)
	{
		$success = false;

		$http = new \Bitrix\Main\Web\HttpClient();

		try
		{
			$http->setHeader('Authorization', 'Token '.$this->token);
			$response = $http->post('https://api.findface.pro/v0/galleries/', array('name' => $id));
			
			if ($http->getStatus() == '200' || $http->getStatus() == '201')
			{
				$success = true;
				$msg = 'gallery `'.$id.'` has been created';
			}
			else
			{
				$msg = 'failed to create gallery `'.$id.'`: '.$response;
			}
		}
		catch (\Exception $e)
		{
			$msg = $e->getMessage();
		}

		return array(
			'result' => $success,
			'msg' => $msg
		);
	}
}
