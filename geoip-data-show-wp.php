<?php

/**
 * Plugin Name: Show geoip data
 * Description: Выводит геоданные пользователя по ip
 * Author:      Владимир Удачин
 * Version:     1.0
 *
 * Requires PHP: 7.4
 *
 * License:     MIT
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
    
    }

    function showGeoipData(){
        add_shortcode( 'geoipdata', 'geoipData' );
    }

    function geoipData( $atts ){
        $atts = shortcode_atts( array(
            'show'   => '',
            'default' => 'No data',
            'lang'  => 'en'
        ), $atts );
        $ip = getClientIp();

        $result = getCookie($atts['show']);
        if(empty($result)){
            $result = getSessionVal($atts['show']);
        }
        if(empty($result)){
            $result = getGeoIpData($ip, $atts);
        } else {
            setCookieVal($atts['show'], $result);
        }
        return $result;
    }

    function getCookie($name){
        return strip_tags($_COOKIE[$name]);
    }

    function setCookieVal($name, $value, $time = ''){
        if(!empty($name) && !empty($value)){
            if(empty($time)) $time = strtotime('+30 days');
            setCookie($name, $value, $time);
        }
    }
    function getSessionVal($name){
        $ret = '';
        if(!empty($name)){
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $ret = strip_tags($_SESSION[$name]);
            session_commit();
        }
        return $ret;
    }
    function setSessionVal($name, $value){
        if(!empty($name) && !empty($value)){
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION[$name] = $value;
            session_commit();
        }
    }


    function getGeoIpData($ip, $params){
        $result = '';
        if(filter_var($ip, FILTER_VALIDATE_IP)){
            $url = 'http://ip-api.com/php/' . $ip . '?lang='. $params['lang'];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $resp = curl_exec($ch);
            $resp = unserialize($resp);
            curl_close($ch);
            if(!empty($resp) && is_array($resp)){
                foreach ($resp as $key => $val){
                    if($key == $params['show']) $result = strip_tags($val);
                    setCookieVal($key, $val);
                    setSessionVal($key, $val);
                }
            }
        }
        if(empty($result)) $result = $params['default'];
        return $result;
    }

    function getClientIp(){
        $ip='';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }    
    add_action('init', 'showGeoipData');