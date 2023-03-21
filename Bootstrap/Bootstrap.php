<?php

namespace MeaPHP\Bootstrap;

use MeaPHP\Core\DataBase\DataBase;

use MeaPHP\Core\Tools\MID;
use MeaPHP\Core\Tools\Captcha;
use MeaPHP\Core\Tools\Save;
use MeaPHP\Core\Tools\Verify;
use MeaPHP\Core\Tools\MoveFile;


class Bootstrap
{

    public static function autoLoad()
    {
        spl_autoload_register([new self(), 'classPath']);
    }
    public function classPath(string $class)
    {
        $file = str_replace('\\', '/', $class) . '.php';
        // $SingleSiteFile =  $_SERVER['DOCUMENT_ROOT'] . '/' . $file;
        $CoreClassFile = dirname($_SERVER['DOCUMENT_ROOT']) . '/' . $file;

        $siteBOFile = $_SERVER['DOCUMENT_ROOT'] . '/Api/BO/' . $class . '.php';

        // $data['file'] = $file;
        // $data['class'] = $class;
        //这里注册核心类的类名称；用户使用当前类名称时提示用户类名已被占用；不能使用
        $coreClass = ['DataBase', 'MID', 'Captcha', 'Save', 'Verify', 'MoveFile'];

        if (in_array($class, $coreClass)) {
            var_dump([
                'errorfile' => 'Bootsrtap.php',
                'errorMessage' => "自动加载:[{$file}] 文件失败;当前类名称已经被MeaPHP占用；",
            ]);
            die;
        }

        if (file_exists($CoreClassFile)) {
            // echo "框架自己的核心工具类,第一个加载";
            require $CoreClassFile;
        } elseif (file_exists($siteBOFile)) {
            // echo "站点自己的对象类；面向对象";
            require $siteBOFile;
        } else {
            var_dump([
                'errorfile' => 'Bootsrtap.php',
                'errorMessage' => "自动加载:[{$file}] 文件失败",
            ]);
        }
    }
}



Bootstrap::autoLoad();
//生成数据库操作基础类
$DB = DataBase::active($UserConfig);

//id管理
$MID = MID::active();
//图片验证码
$Captcha = Captcha::active();
//上传文件；保存到服务器
$Save = Save::active();
//安全验证
$RV = Verify::active();
//文件移动工具
$MoveFile = MoveFile::active();
