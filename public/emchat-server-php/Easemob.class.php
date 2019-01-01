<?php

class Easemob{
	private $client_id;
	private $client_secret;
	private $org_name;
	private $app_name;
	private $url;

	public function __construct($options) {
		$this->client_id = isset ( $options ['client_id'] ) ? $options ['client_id'] : '';
		$this->client_secret = isset ( $options ['client_secret'] ) ? $options ['client_secret'] : '';
		$this->org_name = isset ( $options ['org_name'] ) ? $options ['org_name'] : '';
		$this->app_name = isset ( $options ['app_name'] ) ? $options ['app_name'] : '';
		if (! empty ( $this->org_name ) && ! empty ( $this->app_name )) {
			$this->url = 'https://a1.easemob.com/' . $this->org_name . '/' . $this->app_name . '/';
		}
	}	

	function getToken()
	{
		$options=array(
			"grant_type"=>"client_credentials",
			"client_id"=>$this->client_id,
			"client_secret"=>$this->client_secret
		);
        $url=$this->url.'token';
        $tokenResult = $this->postCurl($url,$options,$header=array());
        return "Authorization:Bearer ".$tokenResult['access_token'];
	}

	function createUser($username,$password){
		$url=$this->url.'users';
		$options=array(
			"username"=>$username,
			"password"=>$password
		);
		$header=array($this->getToken());
		$result=$this->postCurl($url,$options,$header);
		return $result;
	}

	function editNickname($username,$nickname){
		$url=$this->url.'users/'.$username;
		$options=array(
			"nickname"=>$nickname
		);
		$header=array($this->getToken());
		$result=$this->postCurl($url,$options,$header,'PUT');
		return $result;
	}

	function resetPassword($username,$newpassword){
		$url=$this->url.'users/'.$username.'/password';
		$options=array(
			"newpassword"=>$newpassword
		);
		$header=array($this->getToken());
		$result=$this->postCurl($url,$options,$header,"PUT");
		return $result;
	}

	function getUser($username){
		$url=$this->url.'users/'.$username;
		$header=array($this->getToken());
		$result=$this->postCurl($url,[],$header,"GET");
		return $result;
	}

	function sendText($from="admin",$target_type,$target,$content,$ext){
        $url=$this->url.'messages';
        $body['target_type']=$target_type;
        $body['target']=$target;
        $options['type']="txt";
        $options['msg']=$content;
        $body['msg']=$options;
        $body['from']=$from;
        $body['ext']=$ext;
        //$body=json_encode($body);
        $header=array($this->getToken());
        $result=$this->postCurl($url,$body,$header);
        return $result;
    }

    function postCurl($url,$body,$header,$type="POST"){
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_HEADER,0);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if (count($body)>0) {
            $body = json_encode($body,true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}
		//设置请求头
		if(count($header)>0){
			curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
		}
		//上传文件相关设置
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// 对认证证书来源的检查
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);// 从证书中检查SSL加密算
		
		//3)设置提交方式
		switch($type){
			case "GET":
				curl_setopt($ch,CURLOPT_HTTPGET,true);
				break;
			case "POST":
				curl_setopt($ch,CURLOPT_POST,true);
				break;
			case "PUT":
				curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"PUT");
				break;
			case "DELETE":
				curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"DELETE");
				break;
		}
		curl_setopt ($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)');
		$res=curl_exec($ch);
		$result=json_decode($res,true);
		curl_close($ch);
		if(empty($result))
			return $res;
		else
			return $result;
	
	}
}
?>
