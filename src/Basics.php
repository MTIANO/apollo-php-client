<?php

namespace ApolloPublicSdk\Src;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class Basics
{
	//AppId
	protected static $AppId;
	//链接
	protected static $ServerUrl;
	//集群
	protected static $Cluster;
	//密钥
	protected static $Secret;
	//空间
	protected static $NameSpaces;
	
	/**
	 * Basics constructor.
	 * @param $AppId
	 * @param $ServerUrl
	 * @param $Cluster
	 * @param $Secret
	 * @param $NameSpaces
	 */
	public function __construct($AppId,$ServerUrl,$Cluster,$Secret,$NameSpaces)
	{
		self::$AppId = $AppId;
		self::$ServerUrl = $ServerUrl;
		self::$Cluster = $Cluster;
		self::$Secret = $Secret;
		self::$NameSpaces = explode(',', $NameSpaces);
	}
	
	/**
	 * @theme 生成请求sign
	 * @param $data
	 * @return string
	 */
	public function makeSign($pathWithQuery, $time)
	{
		$data = join("\n", [$time, $pathWithQuery]);
		$sign = base64_encode(hash_hmac('sha1', $data, self::$Secret, true));
		return $sign;
	}
	
	/**
	 * @theme 发送请求
	 * @return array
	 */
	public function sendPost()
	{
		try {
			//获取毫秒级时间戳
			$time = (string)sprintf('%.0f', microtime('string') * 1000);
			$cfgs = [];
			foreach (self::$NameSpaces as $namespace) {
				$pathWithQuery = '/configs/' . self::$AppId . '/' . self::$Cluster . '/';
				$url = self::$ServerUrl . $pathWithQuery;
				$releaseKey = Redis::get($pathWithQuery . $namespace);
				$url = $url . $namespace . '?releaseKey=' . $releaseKey;
				$query = $pathWithQuery . $namespace . '?releaseKey=' . $releaseKey;
				$sign = $this->makeSign($query, self::$Secret, $time);
				$header = [
					'Authorization' => 'Apollo ' . self::$AppId . ':' . $sign,
					'Timestamp' => $time,
				];
				$client = new \GuzzleHttp\Client(['timeout' => 3.00, 'headers' => $header]);
				$response = $client->request('GET', $url);
				$body = json_decode($response->getBody()->getContents(), true);
				if ($response->getStatusCode() == 304) {
					continue;
				}
				$releaseKey = Arr::get($body, 'releaseKey', []);
				Redis::set($pathWithQuery . $namespace, $releaseKey);
				$cfg = Arr::get($body, 'configurations', []);
				if (!$cfg) {
					continue;
				}
				$cfgs = array_merge($cfgs, $cfg);
			}
			if ($cfgs) {
				foreach ($_ENV as $key => $value) {
					$cfgs[$key] = $cfgs[$key] ?? $_ENV[$key];
				}
				$items = [];
				foreach ($cfgs as $key => $value) {
					data_set($items, $key, $value);
				}
				$content = '';
				foreach ($items as $k => $item) {
					$this->line('Saving [' . $k . ']');
					$content .= $k . '=' . $item . "\n";
				}
				$fileName = '.env';
				$fileName_back = '.env_back';
				if ($content) {
					Storage::disk('env')->put($fileName, $content);
					Storage::disk('env')->put($fileName_back, $content);
				}
			}
			
		} catch (\Exception $ex) {
			$this->error($ex->getMessage());
		}
	}
}
