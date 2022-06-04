<?php

/**
 * Plugin Name: Show geoip data
 * Description: Show users geodata by ip
 * Author:      Vladimir Udachin
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
        $lastIP = !empty(getCookie('last_ip')) ? getCookie('last_ip') : (!empty(getSessionVal('last_ip')) ? getSessionVal('last_ip') : '' );
        $res = getCookie($atts['show']);
        if(empty($res) && $ip == $lastIP){
            $res = getSessionVal($atts['show']);
        }
        if((empty($res) && $ip == $lastIP) || $ip != $lastIP ){
            $res = getGeoIpData($ip, $atts);
        } else {
            setCookieVal($atts['show'], $res);
        }
        setCookieVal('last_ip', $ip);
        setSessionVal('last_ip', $ip);
        $id = "a" . bin2hex(random_bytes(5));
        $result="
            <span id=\"$id\"></span>            
        
        <script>
        function ready$id() {
            let valu = '';
            let name = \"" . $atts['show'] . "\" + \"=\";
            let ca = document.cookie.split(';');
            for(let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) == ' ') {
                c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    valu = c.substring(name.length, c.length);
                }
            }
            document.getElementById(\"$id\").innerHTML = valu;
          }
        
          document.addEventListener(\"DOMContentLoaded\", ready$id);
        </script>
        ";
        return $result;
    }

    function getCookie($name){
        return strip_tags($_COOKIE[$name]);
    }
    function checkCache($ip){
        return strip_tags($_COOKIE[$name]);
    }

    function setCookieVal($name, $value, $time = ''){
        if(!empty($name) && !empty($value)){
            if(empty($time)) $time = strtotime('+1 day');
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
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); 
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
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
        if (!empty($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }        
        return $ip;
    }    
    add_action('init', 'showGeoipData');