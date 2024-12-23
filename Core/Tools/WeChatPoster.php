<?php
/*
 * @Author: vikinglee1982 87834084@qq.com
 * @Date: 2024-12-22 21:35:08
 * @LastEditors: vikinglee1982 87834084@qq.com
 * @LastEditTime: 2024-12-23 17:16:34
 * @FilePath: \工作台\Servers\huayun_server\MeaPHP\Core\Tools\WeChatPoster.php
 * @Description: 这是默认设置,请设置`customMade`, 打开koroFileHeader查看配置 进行设置: https://github.com/OBKoro1/koro1FileHeader/wiki/%E9%85%8D%E7%BD%AE
 */

namespace MeaPHP\Core\Tools;

use MeaPHP\Core\Reply\Reply;

class WeChatPoster
{
    private $img404;
    private static $obj = null;

    //内部产生静态对象
    public static function active()
    {
        // var_dump( $dbkey );
        if (!self::$obj instanceof self) {
            //如果不存在，创建保存
            self::$obj = new self();
        }
        return self::$obj;
    }

    //阻止外部克隆书库工具类
    private function __clone() {}

    //私有化构造方法初始化，禁止外部使用
    private function __construct()
    {
        $this->img404 = realpath(dirname($_SERVER['DOCUMENT_ROOT']) . "/MeaPHP/Eikon") . "/image404.png";
    }

    /**
     * 创建海报
     * @return array
     * @throws \Exception
     * @return array
     * 
     *思路：
     * 1、加载已经存在的所有资源；
     * 2、根据已经存在的资源计算出海报的宽和高。
     * 3、根据宽高创建海报画布；
     * 2、逐行绘制和海报。
     * 
     */
    public function draw(
        string $imgUrl,
        string $weQRCodeUrl,
        string $logoPath = null,
        //客服信息及联系方式
        array $serverInfo = [],
        array $userInfo = [
            'wx_avatar' => null,
            'wx_name' => null,
        ],
        string $sloganImg = null,
        string $title = null,
        string $titleFlex = 'left',
        string $hint = '长按识别小程序码查看详情',
        int $posterWith = 1080,
        int $titleSize = 36,
        int $textSize = 24,
        int $userNameSize = 30,
        int $padding = 16,
        int $qrcodeZoneHeight = 300
    ): array {

        /**
         * 获取字体文件路径
         */
        $fontFile = $this->getFontFilePath();
        if (!$fontFile) {
            return [
                'sc' => 'err',
                'msg' => '字体文件加载失败',
            ];
        }
        /**
         *计算标题区域高度 
         */
        if ($title) {
            $titleZoneHeight = $textSize * 3;
        } else {
            $titleZoneHeight = 0;
        }
        /**
         * 计算用户区域高度
         */
        if ($userInfo['wx_avatar']) {
            $userZoneHeight = 168;
        } else {
            $userZoneHeight = 0;
        }

        /**
         * 加载海报主要图片
         * 并获取海报宽高
         */
        $imgRes = $this->createImageResourceFromAny($imgUrl);
        $img = $imgRes['data']['imageResource'];
        if ($imgRes['sc'] != 'ok') {
            $defaultBgImage = $this->createImageResourceFromAny($this->img404);
            if ($defaultBgImage['sc'] != 'ok') {
                return [
                    'sc' => 'err',
                    'msg' => '背景图片加载失败，缺省图片加载失败',
                    'data' => $this->img404,
                ];
            }
            $img = $defaultBgImage['data']['imageResource'];
        }
        $imgWidth = imagesx($img);
        $imgHeight = imagesy($img);

        /**
         * 计算海报高度
         */
        $ImgScaleHeight = round($posterWith * ($imgHeight / $imgWidth));
        $posterHeight = $titleZoneHeight + $userZoneHeight + $ImgScaleHeight  + $qrcodeZoneHeight + $textSize * 3;

        /**
         * 创建海报画布
         */
        $poster = imagecreatetruecolor($posterWith, $posterHeight);
        // 启用 Alpha 通道
        imagealphablending($poster, false);
        imagesavealpha($poster, true);

        // 分配一个完全透明的颜色
        $transparent = imagecolorallocatealpha($poster, 0, 0, 0, 127);

        // 填充背景为透明色
        imagefill($poster, 0, 0, $transparent);

        /**
         * 如果有标题绘制标题
         * 背景颜色为白色
         */
        if ($title) {
            // $white = imagecolorallocate($poster, 255, 255, 255);

            $titleBg = imagecolorallocate($poster, 242, 246, 252);

            $titleZone = imagecreatetruecolor($posterWith, $titleZoneHeight);
            // 填充背景白色
            imagefill($titleZone, 0, 0, $titleBg);
            // RGB(168,171,178)
            $titleColor = imagecolorallocate($poster, 168, 171, 178);

            //title内容的宽度
            $titleWidth = $this->getTextWidth($title, $fontFile, $titleSize);
            if ($titleFlex == 'left') {
                //居左
                $titleStartX = $padding;
            } elseif ($titleFlex == 'center') {
                $titleStartX = ($posterWith / 2)  - ($titleWidth / 2);
            } elseif ($titleFlex == 'right') {
                $titleStartX = $posterWith - $titleWidth - $padding;
            }
            imagettftext($titleZone, $titleSize, 0, $titleStartX, $titleSize * 1.5, $titleColor, $fontFile, $title);

            imagecopy($poster, $titleZone, 0, 0, 0, 0, $posterWith, $titleZoneHeight);
            imagedestroy($titleZone);
        }

        /**
         * 绘制用户区域
         */
        if ($userInfo['wx_avatar']) {
            $userAvatarRes = $this->createImageResourceFromAny($userInfo['wx_avatar']);
            if ($userAvatarRes['sc'] != 'ok') {
                return Reply::To('err', '用户头像加载失败', ['err' => $userAvatarRes]);
            }
            $userAvatar = $userAvatarRes['data']['imageResource'];
            // 创建一个新的图像资源
            $userZone = imagecreatetruecolor($posterWith, $userZoneHeight);

            $userZoneBg = imagecolorallocate($userZone, 255, 255, 255);
            // $userZoneBg = imagecolorallocate($userZone, 139, 190, 228);
            imagefill($userZone, 0, 0, $userZoneBg);

            $circleAvatarRes = $this->circleAvatar($userAvatar);

            if ($circleAvatarRes['sc'] != 'ok') {
                return Reply::To('err', '用户头像加载失败', ['err' => $circleAvatarRes]);
            }
            $circleAvatar = $circleAvatarRes['data']['imageResource'];

            // // 将圆形头像复制到 $userZone 上
            imagecopy($userZone, $circleAvatar, $padding * 2, ($userZoneHeight - 128) / 2, 0, 0, 128, 128);

            $userInfo['wx_name'] = $userInfo['wx_name'] ?? '微信好友';
            //如果昵称大于6个字，截取一部分
            $userInfo['wx_name'] = mb_strlen($userInfo['wx_name'], 'utf-8') > 6 ? mb_substr($userInfo['wx_name'], 0, 6, 'utf-8') . '...' : $userInfo['wx_name'];

            $userNameColor = imagecolorallocate($userZone, 96, 98, 102);
            //将用户名写入头像右边20px
            imagettftext($userZone, $userNameSize, 0, 168, 94, $userNameColor, $fontFile, $userInfo['wx_name']);

            if ($sloganImg) {
                $sloganRes = $this->createImageResourceFromAny($sloganImg);
                if ($sloganRes['sc'] != 'ok') {
                    return Reply::To('err', '用户头像加载失败', ['err' => $sloganRes]);
                }
                $slogan = $sloganRes['data']['imageResource'];
                $slogan_w = imagesx($slogan);
                $slogan_h = imagesy($slogan);
                imagecopyresampled($userZone, $slogan, 500, 10, 0, 0, $slogan_w, 128, $slogan_w, $slogan_h);
            }
            // // 清理不再需要的资源
            imagedestroy($userAvatar);
            imagedestroy($circleAvatar);
            imagecopy($poster, $userZone, 0, $titleZoneHeight, 0, 0, $posterWith, $userZoneHeight);
            imagedestroy($userZone);
        }

        /**
         * 将图片绘制到海报上
         */
        $imgStartHight =  $titleZoneHeight + $userZoneHeight;

        imagecopyresampled($poster, $img, 0, $imgStartHight, 0, 0, $posterWith, $ImgScaleHeight, imagesx($img), imagesy($img));
        imagedestroy($img);

        /**
         * 绘制二维码
         */

        $qrcodeZone = imagecreatetruecolor($posterWith, $qrcodeZoneHeight);

        $qrcodeZoneBg = imagecolorallocate($qrcodeZone, 255, 255, 255);
        // $userZoneBg = imagecolorallocate($userZone, 139, 190, 228);
        imagefill($qrcodeZone, 0, 0, $qrcodeZoneBg);

        $qrcodeRes = $this->createImageResourceFromAny($weQRCodeUrl);

        if ($qrcodeRes['sc'] != 'ok') {
            return Reply::To('err', '二维码加载失败', ['err' => $qrcodeRes]);
        }
        $qrcode = $qrcodeRes['data']['imageResource'];
        $qrcode_w = imagesx($qrcode);
        $qrcode_h = imagesy($qrcode);

        // $qrcodeStartX = $posterWith - $qrcode_w;
        // $qrCodeStartY = $titleZoneHeight + $userZoneHeight + $ImgScaleHeight;

        // 计算缩放比例以适应二维码区域高度，同时保持纵横比
        $ratio = min(1, $qrcodeZoneHeight / max($qrcode_w, $qrcode_h));
        $new_qrcode_w = $qrcode_w * $ratio - $padding * 2;
        $new_qrcode_h = $qrcode_h * $ratio - $padding * 2;

        // 确保二维码不会超出二维码区域的高度
        if ($new_qrcode_h > $qrcodeZoneHeight) {
            $ratio = $qrcodeZoneHeight / $qrcode_h;
            $new_qrcode_w = $qrcode_w * $ratio - $padding * 2;
            $new_qrcode_h = $qrcode_h * $ratio - $padding * 2;
        }

        // 计算二维码在二维码区域内的起始位置，使它靠右对齐并且垂直居中
        $qrcodeStartX = $posterWith - $new_qrcode_w - $padding; // 居右对齐
        $qrCodeStartY = ($qrcodeZoneHeight - $new_qrcode_h) / 2; // 垂直居中

        // 将二维码绘制到二维码区域内，并保持纵横比
        imagecopyresampled(
            $qrcodeZone,
            $qrcode,
            $qrcodeStartX,
            $qrCodeStartY,
            0,
            0,
            $new_qrcode_w,
            $new_qrcode_h,
            $qrcode_w,
            $qrcode_h
        );

        imagedestroy($qrcode);


        /**
         * 将绘制好的二维码复制到海报上
         */

        /**
         * 二维码区域开始Y
         */

        $qrcodeZoneStartY = $imgStartHight + $ImgScaleHeight;

        imagecopy($poster, $qrcodeZone, 0, $qrcodeZoneStartY, 0, 0, $posterWith, $qrcodeZoneHeight);

        imagedestroy($qrcodeZone);

        imagecopy($poster, $img, 0, 0, 0, 0, $imgWidth, $imgHeight);



        /**
         * 绘制企业logo、
         * 
         */
        $logoRes = $this->createImageResourceFromAny($logoPath);
        if ($logoRes['sc'] != 'ok') {
            return Reply::To('err', 'logo 加载失败', ['err' => $logoRes]);
        }
        $logo = $logoRes['data']['imageResource'];
        $logo_w = imagesx($logo);
        $logo_h = imagesy($logo);

        $logoZoneWidth = $posterWith - $new_qrcode_w - 4 * $padding;
        $logoZoneHeight = $qrcodeZoneHeight / 3;
        $logoZone = imagecreatetruecolor($logoZoneWidth, $logoZoneHeight);

        //填充背景白色
        $logoZoneBg = imagecolorallocate($logoZone, 255, 255, 255);
        imagefill($logoZone, 0, 0, $logoZoneBg);

        // 计算缩放比例以适应 logo 区域，同时保持纵横比
        $ratio = min($logoZoneWidth / $logo_w, $logoZoneHeight / $logo_h);
        $new_logo_w = $logo_w * $ratio;
        $new_logo_h = $logo_h * $ratio;

        // 将 logo 绘制到 logo 区域内，并保持纵横比
        imagecopyresampled($logoZone, $logo, 0, 0, 0, 0, $new_logo_w, $new_logo_h, $logo_w, $logo_h);

        imagedestroy($logo);


        // 将 logo 区域复制到海报上
        imagecopy($poster, $logoZone, $padding, $qrcodeZoneStartY + $padding, 0, 0, $logoZoneWidth, $logoZoneHeight);

        imagedestroy($logoZone);

        /**
         * 绘制客服信息
         */
        //创建客服内容区域
        $serverZoneHeight = $qrcodeZoneHeight / 3 * 2 - $padding * 3;
        $serverZone = imagecreatetruecolor($logoZoneWidth, $serverZoneHeight);
        $serverZoneBg = imagecolorallocate($serverZone, 235.9, 245.3, 255);
        imagefill($serverZone, 0, 0, $serverZoneBg);

        //创建客服标题区域


        $serverTitleZoneWidth = $userNameSize * 4;
        $serverTitleZoneHeight = $serverZoneHeight;

        $serverTitleColor = imagecolorallocate($serverZone, 255, 255, 255);
        $serverTitleZone = imagecreatetruecolor($serverTitleZoneWidth, $serverTitleZoneHeight);
        $serverTitleZoneBg = imagecolorallocate($serverTitleZone, 64, 158, 255);
        imagefill($serverTitleZone, 0, 0, $serverTitleZoneBg);


        imagecopy($serverZone, $serverTitleZone, 0, 0, 0, 0, $serverTitleZoneWidth, $serverTitleZoneHeight);
        imagedestroy($serverTitleZone);
        // 将咨询预定写入客服区域
        imagettftext($serverZone, $userNameSize, 0, $padding, $padding * 2 + $userNameSize, $serverTitleColor, $fontFile,  '咨询');
        imagettftext($serverZone, $userNameSize, 0, $padding,  $userNameSize * 4, $serverTitleColor, $fontFile,  '预定');

        //加载客服头像
        $serverAvaterRes = $this->createImageResourceFromAny($serverInfo['wx_avatar']);
        if ($serverAvaterRes['sc'] != 'ok') {
            return Reply::To('err', '客服头像加载失败', ['err' => $serverAvaterRes]);
        }
        $serverAvater = $serverAvaterRes['data']['imageResource'];
        $serverAvater_w = imagesx($serverAvater);
        $serverAvater_h = imagesy($serverAvater);
        //将头像剪裁为圆形
        $serverAvaterRes = $this->circleAvatar($serverAvater);
        if ($serverAvaterRes['sc'] != 'ok') {
            return Reply::To('err', '客服头像加载失败', ['err' => $serverAvaterRes]);
        }
        $serverAvater = $serverAvaterRes['data']['imageResource'];
        $serverAvater_w = imagesx($serverAvater);
        $serverAvater_h = imagesy($serverAvater);

        // 将头像绘制到客服内容区域
        // imagecopy($serverZone, $serverAvater, $userNameSize * 4, $padding, 0, 0, $serverAvater_w, $serverAvater_h);

        $serverAvaterStartX = $serverTitleZoneWidth + $padding * 2;

        imagecopy($serverZone, $serverAvater, $serverAvaterStartX, $padding, 0, 0, $serverAvater_w, $serverAvater_h);
        // 计算用户名绘制的起始位置
        $nameStartX = $serverAvaterStartX + $serverAvater_w + $padding * 2;

        $serverName = $serverInfo['wx_name'] ? $serverInfo['wx_name'] : '旅游顾问';

        // 使用 imagettftext 绘制用户名，添加 angle 参数
        imagettftext($serverZone, $userNameSize, 0, $nameStartX, $padding * 2 + $userNameSize, $userNameColor, $fontFile,  $serverName);


        $phoneColor = imagecolorallocate($serverZone, 48, 49, 51);
        $serverPhone = $serverInfo['phone'] ?? '暂未设置';
        $phoneStartY =  $padding * 2  + $titleSize * 3;
        imagettftext($serverZone, $titleSize, 0, $nameStartX, $phoneStartY, $phoneColor, $fontFile,   $serverPhone);

        // $nameStartX = $padding + $serverAvater_w + $padding;

        // imagettftext($serverZone, $userNameSize, $nameStartX, $padding, $userNameColor, $fontFile, $serverInfo['wx_name']);









        $serverZoneStartY = $qrcodeZoneStartY + $padding * 2 + $logoZoneHeight;
        imagecopy($poster, $serverZone, $padding, $serverZoneStartY, 0, 0, $logoZoneWidth, $serverZoneHeight);





        //创建底部提示文字区域的图片

        $hintZoneBackground = imagecreatetruecolor($posterWith, $textSize * 3);
        $backgroundColor3 = imagecolorallocate($hintZoneBackground, 255, 223, 4);
        imagefill($hintZoneBackground, 0, 0, $backgroundColor3);

        $hintZoneY = $qrcodeZoneStartY + $qrcodeZoneHeight;
        $hintZoneHeight =  $textSize * 3;
        imagecopyresampled($poster, $hintZoneBackground, 0, $hintZoneY, 0, 0, $posterWith, $hintZoneHeight, imagesx($hintZoneBackground), imagesy($hintZoneBackground));
        imagedestroy($hintZoneBackground);
        // 设置字体文件路径


        /**
         * @description: 添加底部提示文字
         * @return {*}
         */
        //计算文字宽度
        $textWidth =   $this->getTextWidth($hint, $fontFile, $textSize);

        // 计算文字居中位置（水平方向）
        $textX = ($posterWith - $textWidth) / 2;

        // 文字垂直居中在图片底部
        $textY = $posterHeight  - $textSize;
        //  textSize 是文字高度，可以适当增加一些间距以美观展示
        //如果提示文字超过长度打点显示
        $hint = $this->getFittedText($hint, $fontFile, $textSize, $posterWith - $padding * 4);
        // 使用指定的颜色、字体、字号和坐标写入文字到背景图片上
        imagettftext($poster, $textSize, 0, $textX, $textY, imagecolorallocate($poster, 61, 60, 153), $fontFile, $hint);




        // 生成最终的海报图片文件
        ob_start();
        imagejpeg($poster); // 第二个参数NULL表示直接输出到浏览器，这里我们可以将其改为文件路径
        $posterData = ob_get_clean(); // 清空并获取缓冲区内容，但在我们直接写入文件的情况下，这部分不需要了

        // 直接保存为jpg文件
        // $file_path = $_SERVER['DOCUMENT_ROOT'] . 'Resource/poseter/'; // 指定要保存的文件路径
        // file_put_contents($file_path, $posterData); // 将图片数据写入到指定路径的文件中
        // $res = $this->severImage($posterData, $file_path);

        // 释放图片资源
        imagedestroy($poster);

        return Reply::To('ok', '生成成功', [
            'poster' => 'data:image/jpeg;base64,' . base64_encode($posterData),
            'res' =>  [
                'titleZoneHeight' => $titleZoneHeight,
                'userZoneHeight' => $userZoneHeight,
                // 'img' => $img,
                'imgWidth' => $imgWidth,
                'imgHeight' => $imgHeight,
                'logo_w' => $logo_w,
                'logo_h' => $logo_h,
                'serverInfo' => $serverInfo,

            ]
        ]);
    }




    /**
     * @description: 圆形头像
     * @param {*}
     * @return {*}
     * 
     */
    private function circleAvatar($imageResource, int $size = 128): array
    {
        $userAvatar_w = imagesx($imageResource);
        $userAvatar_h = imagesy($imageResource);
        // 创建一个新的真彩色图像，用于保存最终的圆形头像
        $circleImage = imagecreatetruecolor(128, 128);

        // 确保背景透明
        imagealphablending($circleImage, false);
        imagesavealpha($circleImage, true);
        $transparent = imagecolorallocatealpha($circleImage, 0, 0, 0, 127); // 完全透明的颜色
        imagefill($circleImage, 0, 0, $transparent);

        // 将用户头像缩放到128x128并复制到目标图像
        imagecopyresampled($circleImage, $imageResource, 0, 0, 0, 0, 128, 128, $userAvatar_w, $userAvatar_h);

        // 创建一个临时的蒙版图像
        $mask = imagecreatetruecolor(128, 128);
        imagealphablending($mask, false);
        imagesavealpha($mask, true);
        $maskTransparent = imagecolorallocatealpha($mask, 0, 0, 0, 127);
        imagefilledrectangle($mask, 0, 0, 128, 128, $maskTransparent); // 填充整个矩形为透明

        // 绘制不透明的白色圆圈到蒙版上
        $maskColor = imagecolorallocatealpha($mask, 255, 255, 255, 0); // 不透明白色
        imagefilledellipse($mask, 64, 64, 128, 128, $maskColor);

        // 应用蒙版
        for ($x = 0; $x < 128; $x++) {
            for ($y = 0; $y < 128; $y++) {
                if (imagecolorat($mask, $x, $y) == $maskTransparent) {
                    imagesetpixel($circleImage, $x, $y, $transparent);
                }
            }
        }
        imagedestroy($mask);

        return Reply::To('ok', '创建圆形头像成功', [
            'imageResource' => $circleImage,
        ]);
    }

    // 创建图片资源

    private function createImageResourceFromAny($filename): array
    {
        try {
            // 尝试从远程 URL 加载图片
            if (filter_var($filename, FILTER_VALIDATE_URL)) {
                $imageData = $this->fetchRemoteImageData($filename);
            } else {
                // 对于本地文件路径
                $imageData = $this->fetchLocalImageData($filename);
            }

            // 创建图像资源
            $imageResource = imagecreatefromstring($imageData);
            if ($imageResource === false) {
                throw new \Exception("无法创建图像资源");
            }

            // 返回成功结果
            return Reply::To('ok', '创建图像资源成功', [
                'imageResource' => $imageResource, // 注意：资源不能直接序列化
            ]);
        } catch (\Exception $e) {
            // 捕获并处理所有异常
            return Reply::To('err', '操作失败', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 从远程 URL 获取图像数据
     */
    private function fetchRemoteImageData($url): string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 允许跟随重定向
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 设置超时时间
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // 尽量保持 SSL 验证开启

        $imageData = curl_exec($ch);

        // 检查 cURL 请求是否成功
        if ($imageData === false) {
            throw new \Exception("无法下载远程图片: " . curl_error($ch));
        }

        // 检查 HTTP 状态码以确保请求成功
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            throw new \Exception("HTTP 请求失败，状态码: {$httpCode}");
        }

        curl_close($ch);

        // 验证图像数据的有效性
        if (empty($imageData)) {
            throw new \Exception("下载的图像数据为空");
        }

        // 使用 getimagesizefromstring() 来进一步验证图像数据
        if (!getimagesizefromstring($imageData)) {
            throw new \Exception("下载的数据不是有效的图像格式");
        }

        return $imageData;
    }

    /**
     * 从本地路径获取图像数据
     */
    private function fetchLocalImageData($filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // 定义支持的图像类型及其对应的创建函数
        $supportedTypes = [
            'jpg' => 'imagecreatefromjpeg',
            'jpeg' => 'imagecreatefromjpeg',
            'png' => 'imagecreatefrompng',
            'gif' => 'imagecreatefromgif',
            'bmp' => 'imagecreatefrombmp', // 确认GD库支持此格式
            'webp' => 'imagecreatefromwebp', // 如果GD库支持WebP，则添加
        ];

        if (!array_key_exists($extension, $supportedTypes)) {
            throw new \Exception("不支持的图片格式: {$extension}");
        }

        $imageData = file_get_contents($filename);
        if ($imageData === false) {
            throw new \Exception("无法读取本地文件: {$filename}");
        }

        // 使用 getimagesizefromstring() 来进一步验证图像数据
        if (!getimagesizefromstring($imageData)) {
            throw new \Exception("本地文件数据不是有效的图像格式");
        }

        return $imageData;
    }
    /**
     * @description: 获取字体文件路径
     * @return {*}
     */
    private function getFontFilePath(): string
    {
        // 检查字体文件是否存在
        // 如果没有指定字体路径，尝试查找内置字体
        if (file_exists(dirname($_SERVER['DOCUMENT_ROOT'])   . "/MeaPHP/Font/msyh.ttf")) {
            $fontfile = dirname($_SERVER['DOCUMENT_ROOT'])   . "/MeaPHP/Font/msyh.ttf";
        } elseif (file_exists($_SERVER['DOCUMENT_ROOT']   . "/MeaPHP/Font/msyh.ttf")) {
            $fontfile = $_SERVER['DOCUMENT_ROOT']   . "/MeaPHP/Font/msyh.ttf";
        }
        if (!empty($fontfile) && file_exists($fontfile)) {
            return  $fontfile;
        } else {
            return false;
        }
    }

    /**
     * @description: 获取文字的宽度
     * @param {string} $text
     * @param {string} $fontFile
     * @param {int} $fontSize
     * @return {*}
     */
    private function getTextWidth(string $text, string $fontFile, int $fontSize): int
    {
        $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
        return (int) ($bbox[2] - $bbox[6]);
    }

    private  function getFittedText($originalText, $fontFile, $fontSize, $maxWidth)
    {
        $text = $originalText;
        $textBox = $this->getTextWidth($text, $fontFile, $fontSize); // 假设measureTextWidth返回文本宽度

        while ($textBox > $maxWidth && mb_strlen($text) > 0) {
            // 截取部分文本并尝试
            $text = mb_substr($text, 0, -1); // 移除最后一个字符
            $textBox = $this->getTextWidth($text, $fontFile, $fontSize);
        }

        return $text . (strlen($originalText) !== strlen($text) ? '...' : ''); // 若进行了截断则添加省略号
    }





    //析构方法
    public function __destruct() {}
    // public function draw(
    //     string $imgUrl,
    //     string $appletQrCodeUrl,
    //     string $title = null,

    //     // array $pathParams = [],
    //     //是否显示旅游网或者旅行社的logo或徽章
    //     //是否显示用户的用户名头像等相关信息
    //     //如果这些都不显示，需要设置一个默认的宣传语
    //     //需要设置一个图片加载失败的默认图片

    //     string $logoPath = null,
    //     //客服信息及联系方式
    //     array $serverInfo = [],
    //     array $userInfo = [
    //         'wx_avatar' => null,
    //         // 'wx_avatar' => 'https://xlt.huayunlvyou.com/Resource/User/1/Avatar/2c62ec7abdf4b4fcf04985f8ea2e7764-128_0.png',

    //         'wx_name' => 'vikintg'
    //     ],
    //     string $sloganImg = null,

    //     string $hint = '长按识别小程序码查看详情',
    //     int $titleSize = 36,
    //     int $textSize = 24,
    //     int $userNameSize = 30,
    //     int $padding = 16,
    //     int $qrcodeZoneHeight = 300
    // ): array {



    //     $fontFile = $this->getFontFilePath();
    //     if (!$fontFile) {
    //         return [
    //             'sc' => 'err',
    //             'msg' => '字体文件加载失败',
    //         ];
    //     }
    //     //  打开需要生成海报的背景图片文件
    //     $bgImageRes = $this->createImageResourceFromAny($imgUrl);
    //     if ($bgImageRes['sc'] != 'ok') {

    //         $defaultBgImage = $this->createImageResourceFromAny($this->img404);
    //         if ($defaultBgImage['sc'] != 'ok') {
    //             return [
    //                 'sc' => 'err',
    //                 'msg' => '背景图片加载失败，缺省图片加载失败',
    //                 'data' => $this->img404,
    //             ];
    //         }
    //     }
    //     $bgImage = $bgImageRes['data']['imageResource'];
    //     // 海报图片的尺寸
    //     $sw = imagesx($bgImage);
    //     $sh = imagesy($bgImage);
    //     // 设置图片新的宽度为1080
    //     $w = 1080;

    //     // 计算保持比例下的新高度
    //     $h = round($w * ($sh / $sw));

    //     // 计算标题区域高度
    //     if ($title) {
    //         $titleZoneHeight = $textSize * 4;
    //     } else {
    //         $titleZoneHeight = 0;
    //     }
    //     //头像高度
    //     //  打开需要生成用户头像

    //     if ($userInfo['wx_avatar']) {

    //         $userZoneHeight = 168;
    //         $userAvatarRes = $this->createImageResourceFromAny($userInfo['wx_avatar']);
    //         if ($userAvatarRes['sc'] != 'ok') {
    //             return Reply::To('err', '用户头像加载失败', ['err' => $userAvatarRes]);
    //         }
    //         $userAvatar = $userAvatarRes['data']['imageResource'];
    //         $userAvatar_w = imagesx($userAvatar);
    //         $userAvatar_h = imagesy($userAvatar);


    //         // 创建一个新的图像资源
    //         $userZone = imagecreatetruecolor($w, $userZoneHeight);

    //         $userZoneBg = imagecolorallocate($userZone, 255, 255, 255);
    //         imagefill($userZone, 0, 0, $userZoneBg);

    //         $circleAvatarRes = $this->circleAvatar($userAvatar);

    //         if ($circleAvatarRes['sc'] != 'ok') {
    //             return Reply::To('err', '用户头像加载失败', ['err' => $circleAvatarRes]);
    //         }
    //         $circleAvatar = $circleAvatarRes['data']['imageResource'];

    //         // 将圆形头像复制到 $userZone 上
    //         imagecopy($userZone, $circleAvatar, 10, ($userZoneHeight - 128) / 2, 0, 0, 128, 128);


    //         $userInfo['wx_name'] = $userInfo['wx_name'] ?? '微信好友';
    //         //如果昵称大于6个字，截取一部分
    //         $userInfo['wx_name'] = mb_strlen($userInfo['wx_name'], 'utf-8') > 6 ? mb_substr($userInfo['wx_name'], 0, 6, 'utf-8') . '...' : $userInfo['wx_name'];

    //         // $userInfo['wx_name'] = mb_substr($userInfo['wx_name'], 0, 6, 'utf-8') . '...';

    //         $userNameColor = imagecolorallocate($userZone, 96, 98, 102);
    //         // rgb(115.2, 117.6, 122.4)
    //         //将用户名写入头像右边20px
    //         imagettftext($userZone, $userNameSize, 0, 168, 94, $userNameColor, $fontFile, $userInfo['wx_name']);

    //         if ($sloganImg) {
    //             $sloganRes = $this->createImageResourceFromAny($sloganImg);
    //             if ($sloganRes['sc'] != 'ok') {
    //                 return Reply::To('err', '用户头像加载失败', ['err' => $sloganRes]);
    //             }
    //             $slogan = $sloganRes['data']['imageResource'];
    //             $slogan_w = imagesx($slogan);
    //             $slogan_h = imagesy($slogan);
    //             imagecopyresampled($userZone, $slogan, 500, 10, 0, 0, $slogan_w, 128, $slogan_w, $slogan_h);
    //         }
    //         // 清理不再需要的资源

    //         imagedestroy($userAvatar);
    //         imagedestroy($circleAvatar);
    //         // imagedestroy($mask);
    //         // 将头像写入

    //     } else {
    //         $userZoneHeight = 0;
    //         $uesrZone = imagecreatetruecolor($w, $userZoneHeight);
    //     }


    //     // 计算整体海报图片的高度
    //     $backgroundHeight = $h + $qrcodeZoneHeight + $textSize * 3 + $titleZoneHeight + $userZoneHeight;

    //     // 创建一个新的图像资源
    //     $background = imagecreatetruecolor($w, $backgroundHeight);

    //     imagealphablending($background, false); // 关闭Alpha混合
    //     imagesavealpha($background, true);      // 保存Alpha通道信息

    //     // // 设置背景颜色
    //     // 236, 245, 255//255, 223, 4
    //     // 分配一个完全透明的颜色
    //     $transparent = imagecolorallocatealpha($background, 0, 0, 0, 127);

    //     // 填充背景为透明色
    //     imagefill($background, 0, 0, $transparent);
    //     // $backgroundColor1 = imagecolorallocate($background, 255, 255, 255);
    //     // imagefill($background, 0, 0, $backgroundColor1);

    //     //如果有用户头像将头像画到背景上
    //     if ($userAvatar) {
    //         imagecopyresampled($background, $userZone, 0, $titleZoneHeight, 0, 0, $w, $userZoneHeight, imagesx($userZone), imagesy($userZone));
    //         // 将圆形头像复制到最终的背景图像中
    //         // imagecopy($background, $circleImage, 10, $titleZoneHeight + 10, 0, 0, 128, 128);
    //         imagedestroy($userZone);
    //     }


    //     // 复制海报图片到新创建的图片资源上
    //     imagecopyresampled($background, $bgImage, 0, $titleZoneHeight + $userZoneHeight, 0, 0, $w, $h, imagesx($bgImage), imagesy($bgImage));
    //     imagedestroy($bgImage);

    //     //创建二维码区域的图片

    //     $qrZoneBackground = imagecreatetruecolor($w, $qrcodeZoneHeight);
    //     $backgroundColor2 = imagecolorallocate($qrZoneBackground, 244, 244, 245);
    //     imagefill($qrZoneBackground, 0, 0, $backgroundColor2);

    //     imagecopyresampled($background, $qrZoneBackground, 0, $h + $titleZoneHeight + $userZoneHeight, 0, 0, $w, $qrcodeZoneHeight, imagesx($qrZoneBackground), imagesy($qrZoneBackground));
    //     imagedestroy($qrZoneBackground);




    //     //创建底部提示文字区域的图片

    //     $hintZoneBackground = imagecreatetruecolor($w, $textSize * 3);
    //     $backgroundColor3 = imagecolorallocate($hintZoneBackground, 255, 223, 4);
    //     imagefill($hintZoneBackground, 0, 0, $backgroundColor3);

    //     $hintZoneY = $h + $titleZoneHeight + $userZoneHeight + $qrcodeZoneHeight;
    //     $hintZoneHeight =  $textSize * 3;
    //     imagecopyresampled($background, $hintZoneBackground, 0, $hintZoneY, 0, 0, $w, $hintZoneHeight, imagesx($hintZoneBackground), imagesy($hintZoneBackground));
    //     imagedestroy($hintZoneBackground);
    //     // 设置字体文件路径


    //     /**
    //      * @description: 添加底部提示文字
    //      * @return {*}
    //      */
    //     //计算文字宽度
    //     $textWidth =   $this->getTextWidth($hint, $fontFile, $textSize);

    //     // 计算文字居中位置（水平方向）
    //     $textX = ($w - $textWidth) / 2;

    //     // 文字垂直居中在图片底部
    //     $textY = $backgroundHeight  - $textSize;
    //     //  textSize 是文字高度，可以适当增加一些间距以美观展示
    //     //如果提示文字超过长度打点显示
    //     $hint = $this->getFittedText($hint, $fontFile, $textSize, $w - $padding * 4);
    //     // 使用指定的颜色、字体、字号和坐标写入文字到背景图片上
    //     imagettftext($background, $textSize, 0, $textX, $textY, imagecolorallocate($background, 61, 60, 153), $fontFile, $hint);

    //     /**
    //      * @description: 添加标题
    //      * @return {*}
    //      */

    //     if ($title) {
    //         $titleMaxWidth = 1080 - $padding * 6; // 标题最大宽度

    //         //如果标题长度超出图片宽度，打点显示
    //         $title = $this->getFittedText($title, $fontFile, $titleSize, $titleMaxWidth);

    //         // 计算标题文字居中位置（水平方向）
    //         $titleTextX = $padding;
    //         // 文字垂直居中在图片顶部
    //         $titleTextY = $titleZoneHeight / 3 + $titleSize;
    //         // 设置标题文字颜色
    //         $titleTextColor = imagecolorallocate($background, 0, 0, 0);
    //         // 使用指定的颜色、字体、字号和坐标写入文字到背景图片上
    //         imagettftext($background, $titleSize, 0, $titleTextX, $titleTextY, $titleTextColor, $fontFile, $title);
    //     }


    //     //添加二维码

    //     $qrCodeImageRes = $this->createImageResourceFromAny($appletQrCodeUrl);
    //     if ($qrCodeImageRes['sc'] != 'ok') {
    //         return Reply::To('err', '二维码加载失败', [
    //             'data' => $qrCodeImageRes
    //         ]);
    //     }


    //     $qrCodeImage = $qrCodeImageRes['data']['imageResource'];
    //     $padding = 16;
    //     // 打开并添加二维码图片
    //     // 二维码在背景图片中的y轴开始位置
    //     $qrCodeStartY = $h + $padding + $titleZoneHeight + $userZoneHeight;

    //     //获取二维码原尺寸
    //     $qrCodeWidth = imagesx($qrCodeImage);
    //     $qrCodeHeight = imagesy($qrCodeImage);

    //     $qh = $qrcodeZoneHeight - ($padding * 2);
    //     // 计算保持比例下的新宽度
    //     $qw = round($qh * ($qrCodeWidth / $qrCodeHeight));

    //     //计算二维码在背景图片中的x轴开始位置
    //     $qrCodeStartX = $w - $qw - $padding;

    //     imagecopyresampled($background, $qrCodeImage, $qrCodeStartX, $qrCodeStartY, 0, 0, $qw, $qh, imagesx($qrCodeImage), imagesy($qrCodeImage));
    //     imagedestroy($qrCodeImage);

    //     //二维码区域内添加文字
    //     // $qrCodeTextX = $qrCodeStartX + ($qw / 2) - (strlen($qrCodeText) * $qrCodeTextSize / 2);

    //     //添加公司logo
    //     $logoRes = $this->createImageResourceFromAny($logoPath);

    //     if ($logoRes['sc'] != 'ok') {
    //         return Reply::To('err', '公司logo加载失败', ['err' => $logoRes]);
    //     }

    //     $logo = $logoRes['data']['imageResource'];

    //     $logo_w = imagesx($logo);
    //     $logo_h = imagesy($logo);

    //     // // 确保背景图像支持Alpha通道
    //     // imagealphablending($background, false); // 关闭Alpha混合
    //     // imagesavealpha($background, true);      // 保存Alpha通道信息

    //     // 确保Logo图像支持Alpha通道
    //     imagealphablending($logo, false);       // 关闭Alpha混合
    //     imagesavealpha($logo, true);            // 保存Alpha通道信息

    //     // 创建一个透明背景的图像资源
    //     // $transparentColor = imagecolorallocatealpha($background, 0, 0, 0, 127);
    //     // imagefill($background, 0, 0, $transparentColor);
    //     // 如果背景图像是使用 imagecreatetruecolor() 创建的，请确保它也支持Alpha通道
    //     if (function_exists('imagecreatetruecolor')) {
    //         imagealphablending($background, false);
    //         imagesavealpha($background, true);
    //     }

    //     // 计算缩放比例，保持原始宽高比
    //     $ratio = min(
    //         $qrCodeStartX / $logo_w,
    //         ($qrcodeZoneHeight / 3) / $logo_h
    //     );
    //     $new_width = $logo_w * $ratio;
    //     $new_height = $logo_h * $ratio;
    //     // 将Logo复制到背景图像上，并保持透明度
    //     imagecopyresampled(
    //         $background, // 目标图像
    //         $logo,       // 源图像
    //         0,           // 目标X坐标
    //         $h + $titleZoneHeight + $userZoneHeight, // 目标Y坐标
    //         0,           // 源X坐标
    //         0,           // 源Y坐标
    //         $new_width,  // 新宽度
    //         $new_height, // 新高度
    //         $logo_w,     // 原始宽度
    //         $logo_h      // 原始高度
    //     );

    //     imagedestroy($logo);







    //     // 生成最终的海报图片文件
    //     ob_start();
    //     imagejpeg($background); // 第二个参数NULL表示直接输出到浏览器，这里我们可以将其改为文件路径
    //     $posterData = ob_get_clean(); // 清空并获取缓冲区内容，但在我们直接写入文件的情况下，这部分不需要了

    //     // 直接保存为jpg文件
    //     // $file_path = $_SERVER['DOCUMENT_ROOT'] . 'Resource/poseter/'; // 指定要保存的文件路径
    //     // file_put_contents($file_path, $posterData); // 将图片数据写入到指定路径的文件中
    //     // $res = $this->severImage($posterData, $file_path);

    //     // 释放图片资源
    //     imagedestroy($background);

    //     return [
    //         'sc' => 'ok',
    //         'imgUrl' => $imgUrl,
    //         '$w' => $w,
    //         '$h' => $h,
    //         'textX' => $textX,
    //         'textY' => $textY,
    //         '$title' => $title,
    //         '$textWidth' => $textWidth,
    //         'qrCodeStartY' => $qrCodeStartY,
    //         // 'posterData' => $posterData,
    //         '$fontFile ' =>  $fontFile,
    //         'appletQrCodeUrl' => $appletQrCodeUrl,
    //         'sloganImg' => $sloganImg,
    //         'userInfo' => $userInfo,
    //         // 'userAvatar' => $userAvatar,
    //         'userAvatar_w' => $userAvatar_w,
    //         'userAvatar_h' => $userAvatar_h,
    //         'logoPath' => $logoPath,
    //         'new_width' => $new_width,
    //         'new_height' => $new_height,

    //         // 'logo' => $logo,


    //         'data' => 'data:image/jpeg;base64,' . base64_encode($posterData),
    //     ];
    // }

}
