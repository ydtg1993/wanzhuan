<?php

namespace App\Models;

use App\Models\Traits\ResourceTrait;
use Illuminate\Database\Eloquent\Model;
use Qcloud\Cos\Client;

class Resource extends BaseModel
{
    use ResourceTrait;

    protected $table = 'resources';
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'created_at',
        'updated_at',
        'path',
        'type',
        'kind',
        'ori_path',
        'sort'
    ];

    public static function getImageAndAudio($user_id)
    {
        $images = self::where([
            ['user_id', '=', $user_id],
            ['status', '=', 0],
            ['kind', '=', 2],
            ['danger_class', '<', 2]
        ])->limit(5)->orderBy('created_at', 'DESC')->get();//过滤黄色图片
        $audio = self::where(['user_id' => $user_id, 'status' => 0, 'kind' => 6])->limit(1)->orderBy('created_at', 'DESC')->get();

        if ($images) {
            $images = $images->toArray();
        }
        if ($audio) {
            $audio = $audio->toArray();
        }

        return [
            'user_info' => User::getBasic($user_id),
            'images' => $images,
            'audio' => $audio
        ];
    }

    /**
     * 新增用户文件
     *
     * @param mixed
     * @param string
     * @return void
     */
    static public function newFile($file_paths, $user_id, $kind)
    {
        foreach ($file_paths as $path) {
            //语音文件处理转换成mp3
            if ($kind == 6) {
                $path = static::uploadCos($path);
            }
            self::addFile($user_id, $path, $kind);
        }
    }

    /**
     * @param $user_id
     * @param $path
     * @param $sort
     */
    static function addAlbum($user_id, $path,$sort)
    {
        $ext = last(explode('.',$path));
        $image_type = array('jpg', 'png', 'gif');
        $video_type = array('mp4', 'avi', 'flv');
        $audio_type = array('mp3', 'wav', 'act');

        if (in_array($ext, $image_type)) {
            $type = 0;
        } elseif (in_array($ext, $video_type)) {
            $type = 1;
        } elseif (in_array($ext, $audio_type)) {
            $type = 2;
        } else {
            $type = 3;
        }

        if(preg_match("/^http:\/\//",$path)) {
            $oss_url = str_replace("http://", "", rtrim(str_replace(basename($path), "", $path), "/"));
        }else{
            $oss_url = str_replace("https://", "", rtrim(str_replace(basename($path), "", $path), "/"));
        }
        $oss_path = basename($path);
        $cdn_hash = [
            'image-1257042421.cos.ap-chengdu.myqcloud.com' => 'image.wanzhuanhuyu.cn',
            'avatar-1257042421.cos.ap-chengdu.myqcloud.com' => 'avatar.wanzhuanhuyu.cn',
            'video-1257042421.cos.ap-chengdu.myqcloud.com' => 'video.wanzhuanhuyu.cn',
            'audio-1257042421.cos.ap-chengdu.myqcloud.com' => 'audio.wanzhuanhuyu.cn',
            'static-1257042421.cos.ap-chengdu.myqcloud.com' => 'static.wanzhuanhuyu.cn',
            'image-1257042421.cos-website.ap-chengdu.myqcloud.com' => 'image-1257042421.file.myqcloud.com'
        ];

        $cdn_path = '';
        if (isset($cdn_hash[$oss_url])) {
            $cdn_path = 'http://' . $cdn_hash[$oss_url] . '/' . $oss_path;
        }

        if(preg_match("/^http:\/\/thirdqq.qlogo.cn\/qqapp/",$path)){
            //qq图片
            $type = 0;
            $cdn_path = $path;
        }
        if(preg_match("/^http:\/\/thirdwx.qlogo.cn\/mmopen/",$path)){
            //微信图片
            $type = 0;
            $cdn_path = $path;
        }

        $data = [
            'user_id' => intval($user_id),
            'path' => $cdn_path,
            'ori_path' => $path,
            'type' => $type,
            'kind' => 1,
            'sort' => $sort
        ];
        self::create($data);

        return $data;
    }

    /**
     * 新增用户文件
     *
     * @param string
     * @param string
     * @return void
     */
    private static function addFile($user_id, $path, $kind)
    {
        $ext = last(explode('.',$path));
        $image_type = array('jpg', 'png', 'gif');
        $video_type = array('mp4', 'avi', 'flv');
        $audio_type = array('mp3', 'wav', 'act');
        info($path);
        info($ext);

        if (in_array($ext, $image_type)) {
            $type = 0;
        } elseif (in_array($ext, $video_type)) {
            $type = 1;
        } elseif (in_array($ext, $audio_type)) {
            $type = 2;
        } else {
            $type = 3;
        }

        $oss_url = str_replace("http://", "", rtrim(str_replace(basename($path), "", $path), "/"));
        $oss_path = basename($path);
        $cdn_hash = [
            'image-1257042421.cos.ap-chengdu.myqcloud.com' => 'image.wanzhuanhuyu.cn',
            'avatar-1257042421.cos.ap-chengdu.myqcloud.com' => 'avatar.wanzhuanhuyu.cn',
            'video-1257042421.cos.ap-chengdu.myqcloud.com' => 'video.wanzhuanhuyu.cn',
            'audio-1257042421.cos.ap-chengdu.myqcloud.com' => 'audio.wanzhuanhuyu.cn',
            'static-1257042421.cos.ap-chengdu.myqcloud.com' => 'static.wanzhuanhuyu.cn',
            'image-1257042421.cos-website.ap-chengdu.myqcloud.com' => 'image-1257042421.file.myqcloud.com'
        ];

        $cdn_path = '';
        if (isset($cdn_hash[$oss_url])) {
            $cdn_path = 'http://' . $cdn_hash[$oss_url] . '/' . $oss_path;
        }

        self::create([
            'user_id' => intval($user_id),
            'path' => $cdn_path,
            'ori_path' => $path,
            'type' => $type,
            'kind' => (int)$kind
        ]);
    }

    /**
     * @param $filePath
     * @return mixed
     * @throws \Exception
     */
    private static function uploadCos($filePath)
    {
        //$filePath = 'http://audio-1257042421.cos.ap-chengdu.myqcloud.com/154328435928520181115008465672-220181127T100539.amr';
        $fileName = basename($filePath);
        $pathArr = explode('.', $fileName);

        $name = current($pathArr);
        $ext = end($pathArr);
        if ($ext == 'amr') {
            try {
                $saveDir = '/data/release/wz_api/storage/tempFile/';

                exec('wget -P ' . $saveDir . ' ' . $filePath);
                exec('ffmpeg -i ' . $saveDir . $fileName . ' ' . $saveDir . $name . '.mp3');

                $cosClient = new Client(['region' => 'ap-chengdu',
                    'credentials' => [
                        'secretId' => 'AKID9rSvHTyHUcbpz1zQr7B5oy2vfcUvANKH',
                        'secretKey' => 'yU5bw6iDnCPRJDvFjEXebLQRHbbVQCrM'
                    ]]);

                $result = $cosClient->putObject([
                    'Bucket' => 'audio-1257042421',
                    'Key' => $name . '.mp3',
                    'Body' => fopen($saveDir . $name . '.mp3', 'rb')
                ]);
                exec('rm -rf ' . $saveDir . $name . '.*');
                if (!$result) {
                    throw new \Exception();
                }
                $path = $result->toArray();
                return $path['ObjectURL'];
            } catch (\Exception $e) {
                throw new \Exception();
            }
        }
        return $filePath;
    }
}
