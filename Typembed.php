<?php
/**
 * Typembed 视频播放插件
 * 
 * @package Typembed
 * @author Fengzi
 * @version 1.0.0
 * @link http://www.fengziliu.com/typembed-for-php.html
 */

class Typembed{
    private $config = array(
        'width' => '100%',
        'height' => '500',
        'mobile_width' => '100%',
        'mobile_height' => '250'
    );
    
    /**
     * 参数配置
     * @param string / array $name
     * @param string $value
     * @return booleam
     */
    public function config($name = null, $value = null){
        if(is_string($name)){
            $name = strtolower($name);
            if(is_null($value)){
                isset($this->config[$name]) ? $this->config[$name] : null;
            }
            $this->config[$name] = $value;
        }else if(is_array($name)){
            $this->config = array_merge($this->config, array_change_key_case($name, CASE_LOWER));
        }
        return true;
    }
    
    /**
     * 解析内容
     * @param string $content
     * @return string
     */
    public function parse($content){
        if(empty($content)){
            return '';
        }
        $content = preg_replace_callback('/<p>(?:(?:<a[^>]+>)?(?<video_url>(?:(http|https):\/\/)+[a-z0-9_\-\/.%]+)(?:<\/a>)?)<\/p>/si', array($this, 'parseCallback'), $content);
        return $content;
    }
    
    /**
     * 解析内容
     * @param array $matches
     * @return type
     */
    private function parseCallback($matches){
        $no_html5 = array('www.letv.com', 'v.yinyuetai.com', 'v.ku6.com');
        $providers = array(
            'v.youku.com' => array(
                '#https?://v\.youku\.com/v_show/id_(?<video_id>[a-z0-9_=\-]+)#i',
                'http://player.youku.com/player.php/sid/{video_id}/v.swf',
                'http://player.youku.com/embed/{video_id}',
            ),
            'www.tudou.com' => array(
                '#https?://(?:www\.)?tudou\.com/(?:programs/view|listplay/(?<list_id>[a-z0-9_=\-]+))/(?<video_id>[a-z0-9_=\-]+)#i', 
                'http://www.tudou.com/v/{video_id}/&resourceId=0_05_05_99&bid=05/v.swf',
                'http://www.tudou.com/programs/view/html5embed.action?type=0&code={video_id}',
            ),
            'www.56.com' => array(
                '#https?://(?:www\.)?56\.com/[a-z0-9]+/(?:play_album\-aid\-[0-9]+_vid\-(?<video_id>[a-z0-9_=\-]+)|v_(?<video_id2>[a-z0-9_=\-]+))#i',
                'http://player.56.com/v_{video_id}.swf',
                'http://www.56.com/iframe/{video_id}',
            ),
            'v.qq.com' => array(
                '#https?://v\.qq\.com/(?:[a-z0-9_\./]+\?vid=(?<video_id>[a-z0-9_=\-]+)|(?:[a-z0-9/]+)/(?<video_id2>[a-z0-9_=\-]+))#i',
                'http://static.video.qq.com/TPout.swf?vid={video_id}',
                'http://v.qq.com/iframe/player.html?vid={video_id}',
            ),
            'my.tv.sohu.com' => array(
                '#https?://my\.tv\.sohu\.com/us/(?:\d+)/(?<video_id>\d+)#i',
                'http://share.vrs.sohu.com/my/v.swf&topBar=1&id={video_id}&autoplay=false&xuid=&from=page',
                'http://tv.sohu.com/upload/static/share/share_play.html#{video_id}_0_0_9001_0',
            ),
            'www.wasu.cn' => array(
                '#https?://www\.wasu\.cn/play/show/id/(?<video_id>\d+)#i',
                'http://s.wasu.cn/portal/player/20141216/WsPlayer.swf?mode=3&vid={video_id}&auto=0&ad=4228',
                'http://www.wasu.cn/Play/iframe/id/{video_id}',
            ),
            'www.letv.com' => array(
                '#https?://www\.letv\.com/ptv/vplay/(?<video_id>\d+)#i',
                'http://i7.imgs.letv.com/player/swfPlayer.swf?id={video_id}&autoplay=0',
                '',
            ),
            'www.acfun.tv' => array(
                '#https?://www\.acfun\.tv/v/ac(?<video_id>\d+)#i',
                'http://static.acfun.mm111.net/player/ACFlashPlayer.out.swf?type=page&url=http://www.acfun.tv/v/ac{video_id}',
                '',
            ),
            'www.bilibili.com' => array(
                '#https?://www\.bilibili\.com/video/av(?<video_id>\d+)#i',
                'http://static.hdslb.com/miniloader.swf?aid={video_id}&page=1',
                '',
            ),
            'v.yinyuetai.com' => array(
                '#https?://v\.yinyuetai\.com/video/(?<video_id>\d+)#i',
                'http://player.yinyuetai.com/video/player/{video_id}/v_0.swf',
                '',
            ),
            'v.ku6.com' => array(
                '#https?://v\.ku6\.com/show/(?<video_id>[a-z0-9\-_\.]+).html#i',
                'http://player.ku6.com/refer/{video_id}/v.swf',
                '',
            ),
        );
        $parse = parse_url($matches['video_url']);
        $site = $parse['host'];
        if(!in_array($site, array_keys($providers))){
            return '<p><a href="' . $matches['video_url'] . '">' . $matches['video_url'] . '</a></p>';
        }
        preg_match_all($providers[$site][0], $matches['video_url'], $match);
        $id = $match['video_id'][0] == '' ? $match['video_id2'][0] : $match['video_id'][0];
        if($this->isMobile()){
            $width = $this->config['mobile_width'];
            $height = $this->config['mobile_height'];
        }else{
            $width = $this->config['width'];
            $height = $this->config['height'];
        }
        if($this->isMobile() && !in_array($site, $no_html5)){
            $url = str_replace('{video_id}', $id, $providers[$site][2]);
            $html = sprintf(
                '<iframe src="%1$s" width="%2$s" height="%3$s" frameborder="0" allowfullscreen="true"></iframe>',
                $url, $width, $height);
        }else{
            $url = str_replace('{video_id}', $id, $providers[$site][1]);
            $html = sprintf(
                '<embed src="%1$s" allowFullScreen="true" quality="high" width="%2$s" height="%3$s" allowScriptAccess="always" type="application/x-shockwave-flash"></embed>',
                $url, $width, $height);
        }
        return '<div id="typembed">'.$html.'</div>';
    }
    
    /**
     * 移动设备识别
     * @return boolean
     */
    private function isMobile(){
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $mobile_browser = Array(
            "mqqbrowser", // 手机QQ浏览器
            "opera mobi", // 手机opera
            "juc","iuc", 'ucbrowser', // uc浏览器
            "fennec","ios","applewebKit/420","applewebkit/525","applewebkit/532","ipad","iphone","ipaq","ipod",
            "iemobile", "windows ce", // windows phone
            "240x320","480x640","acer","android","anywhereyougo.com","asus","audio","blackberry",
            "blazer","coolpad" ,"dopod", "etouch", "hitachi","htc","huawei", "jbrowser", "lenovo",
            "lg","lg-","lge-","lge", "mobi","moto","nokia","phone","samsung","sony",
            "symbian","tablet","tianyu","wap","xda","xde","zte"
        );
        $is_mobile = false;
        foreach ($mobile_browser as $device) {
            if (stristr($user_agent, $device)) {
                $is_mobile = true;
                break;
            }
        }
        return $is_mobile;
    }
}