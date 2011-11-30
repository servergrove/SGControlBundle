<?php

namespace ServerGrove\Bundle\SGControlBundle;

class APIClient
{
    protected $url;
    protected $format = 'json';
    protected $args = array();
    protected $call;
    protected $response;

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function getFullUrl($call, array $args = array())
    {
        $args = array_merge($this->args, $args);
        return $this->url . '/api/' . $call . '.' . $this->format . '?' . http_build_query($args);
    }

    /**
     * Executes API Call. Returns true|false depending on the api response. use getResponse() to retrieve response.
     * @param $call
     * @param array $args
     * @return bool
     * @throws \Exception
     */
    public function call($call, array $args = array())
    {
        if (!function_exists('curl_init')) {
            throw new \Exception("curl support not found. please install the curl extension.");
        }

        $url = $this->getFullUrl($call, $args);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $this->response = curl_exec($ch);
        curl_close($ch);
        return $this->isSuccess($this->response);
    }

    public function getResponse()
    {
        switch ($this->format) {
            case 'json':
                return json_decode($this->response);
                break;
            default:
                return $this->response;
        }
    }

    public function getRawResponse()
    {
        return $this->response;
    }


    public function isSuccess($result=null)
    {
        if (null === $result) {
            $result = $this->getResponse();
        }

        if (is_string($result)) {
            $json = json_decode($result);
            if ($json) {
                $result = $json;
            } else {
                return $result == true;
            }
        } elseif (is_array($result)) {
            return $result['result'] == true;
        }

        return $result && $result->result == true;
    }

    public function getError($result=null)
    {
        if (null === $result) {
            $result = $this->getResponse();
        }

        if (is_string($result)) {
            $json = json_decode($result);
            if ($json) {
                $result = $json;
            } else {
                return $result;
            }
        } elseif (is_array($result)) {
            return $result['msg'];
        }

        return $result && $result->msg ? $result->msg : 'Unknown error';
    }


    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setArgs($args)
    {
        $this->args = $args;
        return $this;
    }

    public function getArgs()
    {
        return $this->args;
    }

    public function setArg($name, $value)
    {
        $this->args[$name] = $value;
        return $this;
    }

    public function setApiKey($value)
    {
        return $this->setArg('apiKey', $value);
    }

    public function setApiSecret($value)
    {
        return $this->setArg('apiSecret', $value);
    }

    public function setDebug($value)
    {
        return $this->setArg('debug', $value);
    }

}
