<?php

namespace Ngomory;

class Adminer
{

    /**
     * webappUrl
     *
     * @param string $endpoint
     * @param string $group
     * @param string $module
     * @param string $action
     * @param string $uuid
     * @return string
     */
    static function webappUrl(string $endpoint, string $group = 'other', string $module, string $action = 'list', string $uuid = ''): string
    {
        return  $endpoint . $group  . '/' . $module  . '/' . $action . ($uuid ? '/' . $uuid : '');
    }

    /**
     * webappModulo
     *
     * @param array $modules
     * @param string $group
     * @param array $headers Headers and token X-Webapp-Token | X-Webapp-Name
     * @return void
     */
    static function webappModulo(string $endpoint, array $modules, string $group = 'other', array $headers = [])
    {

        $url = self::webappUrl($endpoint, $group, 'modulo', 'list');
        $result = self::curl('POST', $url, ['modules' => implode('|', $modules)], $headers);

        if ($result['success'] == false) {
            die('Error : ' . implode(' => ', $result['errors']));
        }

        return $result['results'];
    }

    /**
     * styleColor
     *
     * @param array $color
     * @param string $text
     * @param string $bg
     * @return string
     */
    static function styleColor(array $color, string $text = 'color', string $bg = 'bg'): string
    {
        $colorcss = '<style>';
        foreach ($color as $name => $color) {
            $colorcss .= ' .' . $text . '-' . $name . '{ color: ' . $color . ' !important; }';
            $colorcss .= ' .' . $bg . '-' . $name . '{ background-color: ' . $color . ' !important; }';
        }
        $colorcss .= '</style>';
        return $colorcss;
    }

    /**
     * getItemByCategory
     *
     * @param array $items
     * @param string $category
     * @param integer $limit
     * @param array $options
     * @return array
     */
    static function getItemByCategory(array $items, string $category, int $limit = 0, array $options = []): array
    {

        $key_name = isset($options['key_name']) ? $options['key_name'] : 'categories';
        $filter = isset($options['filter']) ? $options['filter'] : '';
        $filter_key = isset($options['filter_key']) ? $options['filter_key'] : 'title';
        $single = isset($options['single']) && $options['single'] == true ? true : false;

        $match = [];
        foreach ($items as $item) {

            if (
                isset($item[$key_name]) &&
                (is_array($item[$key_name]) && in_array($category, $item[$key_name]) ||
                    is_string($item[$key_name]) && ($category == $item[$key_name])
                )
            ) {

                if (
                    empty($filter) ||
                    (!empty($filter) &&
                        (stripos($item[$filter_key], $filter) !== false ||
                            $item[$filter_key] == $filter
                        )
                    )
                ) {
                    $match[] = $item;
                }
            }

            if ($limit > 0 && count($match) == $limit) {
                break;
                exit;
            }
        }

        return ($single == true) ? (count($match) > 0 ? current($match)  : []) : $match;
    }

    /**
     * getFileContent
     *
     * @param string $source
     * @param boolean $parse
     * @param integer $limit
     * @return string
     */
    static function getFileContent(string $source, bool $parse = true, int $limit = 500): string
    {
        $content = @file_get_contents($source);
        $content = ($parse == true) ? strip_tags($content) : $content;
        $content = ($limit > 0) ? substr($content, 0, $limit) . ' ...' : $content;
        return $content;
    }

    /**
     * For call API
     *
     * @param string $method
     * @param string $url
     * @param array $params
     * @param array $headers
     * @return array
     */
    static function curl(string $method = 'POST', string $url, array $params = [], array $headers = []): array
    {

        $headerFields = [];
        foreach ($headers as $key => $value) {
            $headerFields[] = $key . ': ' . $value;
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_HTTPHEADER => $headerFields,
        ));

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            throw new \Exception('Adminer : curl => ' . $error);
        }

        curl_close($curl);

        return json_decode($response, true);
    }
}
