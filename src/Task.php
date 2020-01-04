<?php

namespace CurlFuture;

/**
 * @use Task类，封装每个curl handle的输入输出方法，如果需要日志、异常处理，可以放在这个地方
 */
class Task
{
    public $url;
    public $ch;    //curl handle
    protected $curlOptions = array();

    /**
     * Task constructor.
     * @use 构造函数，供TaskManager调用
     * @param $url
     * @param $options
     * @param $other_options
     */
    public function __construct($url, $options, $other_options = [])
    {
        $this->url = $url;
        $ch = curl_init();

        $curlOptions = [
            CURLOPT_TIMEOUT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
        ];

        //这个地方需要合并cat的头信息
        $headers = isset($options['header']) ? $options['header'] : [];
        $curlOptions[CURLOPT_HTTPHEADER] = $headers;
        if (isset($options['proxy_url']) && $options['proxy_url']) {
            $curlOptions[CURLOPT_PROXY] = $options['proxy_url'];
        }

        //设置超时时间
        $timeout = isset($options['timeout']) ? $options['timeout'] : 1;
        if ($timeout < 1) {
            $curlOptions[CURLOPT_TIMEOUT_MS] = intval($timeout * 1000);
            $curlOptions[CURLOPT_NOSIGNAL] = 1;
        } else {
            $curlOptions[CURLOPT_TIMEOUT] = $timeout;
        }

        // 如果需要post数据
        if (isset($options['post_data']) && $options['post_data']) {
            $curlOptions[CURLOPT_POST] = true;
            curl_setopt($ch, CURLOPT_POST, true);
            $postData = $options['post_data'];
            if (is_array($options['post_data'])) {
                $postData = http_build_query($options['post_data']);
            }
            $curlOptions[CURLOPT_POSTFIELDS] = $postData;
        }

        // 拼接覆盖CURL_OPT
        $curlOptions = $other_options + $curlOptions;
        curl_setopt_array($ch, $curlOptions);

        $this->ch = $ch;
    }


    /**
     * @use 请求完成后调用，可以在这个函数里面加入日志与统计布点，返回http返回结果
     * @return bool|string
     */
    public function complete()
    {
        return $this->getContent();
    }

    /**
     * @use 如果curl已经完成，通过这个函数读取内容 成功string，失败false
     * @return bool|string
     */
    private function getContent()
    {
        $error = curl_errno($this->ch);
        if ($error !== 0) {
            return false;
        }

        return curl_multi_getcontent($this->ch);
    }
}
