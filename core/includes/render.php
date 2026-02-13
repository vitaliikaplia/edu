<?php

if(!defined('ABSPATH')){exit;}

function get_url_segments(?string $uri = null): ?array {

    if(!isset($_SERVER['REQUEST_URI']) || !$_SERVER['REQUEST_URI']){
        return null;
    }

    if(!($parsed_url = parse_url($_SERVER['REQUEST_URI']))){
        return null;
    }

    if(empty($url_segments = explode('/', trim($parsed_url['path'], '/')))){
        return null;
    }

    // decode url-encoded segments (cyrillic etc.)
    $url_segments = array_map('urldecode', $url_segments);

    return $url_segments;

}

if ($url_segments = get_url_segments()) {

    // sitemap.xml
    if($url_segments[0] === 'sitemap.xml') {
        header('Content-Type: application/xml; charset=UTF-8');
        echo generate_sitemap();
        exit;
    }

    // robots.txt
    if($url_segments[0] === 'robots.txt') {
        header('Content-Type: text/plain; charset=UTF-8');
        echo generate_robots();
        exit;
    }

    // search API
    if($url_segments[0] === 'api' && isset($url_segments[1]) && $url_segments[1] === 'search') {
        header('Content-Type: application/json; charset=UTF-8');
        $q = isset($_GET['q']) ? $_GET['q'] : '';
        echo json_encode(search_lessons($q), JSON_UNESCAPED_UNICODE);
        exit;
    }

    // check for last slash
    if(
        !empty($url_segments[0])
        &&
        empty($_GET)
        &&
        substr($_SERVER['REQUEST_URI'], -1) !== '/'
    ){
        header("Location: " . HOME_URL . trim($_SERVER['REQUEST_URI'], '/') . '/', true, 301);
        exit;
    }

    list($template, $context) = router($url_segments);

    // render
    echo get_template($template, $context);

}
