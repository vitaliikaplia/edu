<?php

if(!defined('ABSPATH')){exit;}

function get_allowed_templates($with_404 = false) {
    $templates = glob(ABSPATH . DS . TWIG_VIEWS_DIRNAME . DS . '*.twig');
    // keep only filenames
    $templates = array_map(function($file) {
        return basename($file, '.twig');
    }, $templates);
    // exclude home.twig and 404.twig and base.twig
    $templates = array_filter($templates, function($template) {
        return !in_array($template, ['index']);
    });
    if(!empty($with_404)) {
        $templates[] = '404';
    }
    return $templates;
}

function get_all_blocks_styles(){
    $blocks_groups = glob(ABSPATH . DS . 'assets' . DS . 'css' . DS . 'blocks' . DS . '*');
    $return = "";
    if(!empty($blocks_groups)){
        foreach($blocks_groups as $group){
            $group_name = basename($group);
            $group_styles = glob(ABSPATH . DS . 'assets' . DS . 'css' . DS . 'blocks' . DS . $group_name . DS . '*.css');
            if(!empty($group_styles)){
                foreach ($group_styles as $style) {
                    $style_name = basename($style, '.min.css');
                    $return .= '<link rel="stylesheet" id="'.$group_name.'-'.$style_name.'-css" href="'.HOME_URL.'assets/css/blocks/'.$group_name.'/'.$style_name.'.min.css?ver='.ASSETS_VERSION.'" type="text/css" media="all" />';
                    $return .= "\n";
                }
            }
        }
    }
    return $return;
}

function minify_html($html) {
    if (MINIFY_HTML) {
        return preg_replace([
            '/\>[^\S ]+/s',
            '/[^\S ]+\</s',
            '/(\s)+/s'
        ], [
            '>',
            '<',
            '\\1'
        ], $html);
    } else {
        return preg_replace("/^\s*\n/m", '', $html);
    }
}

function ukr_to_lat($str) {
    $map = [
        'а' => 'a',  'б' => 'b',  'в' => 'v',  'г' => 'h',  'ґ' => 'g',
        'д' => 'd',  'е' => 'e',  'є' => 'ye', 'ж' => 'zh', 'з' => 'z',
        'и' => 'y',  'і' => 'i',  'ї' => 'yi', 'й' => 'y',  'к' => 'k',
        'л' => 'l',  'м' => 'm',  'н' => 'n',  'о' => 'o',  'п' => 'p',
        'р' => 'r',  'с' => 's',  'т' => 't',  'у' => 'u',  'ф' => 'f',
        'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
        'ь' => '',   'ю' => 'yu', 'я' => 'ya', 'ъ' => '',
        'А' => 'A',  'Б' => 'B',  'В' => 'V',  'Г' => 'H',  'Ґ' => 'G',
        'Д' => 'D',  'Е' => 'E',  'Є' => 'Ye', 'Ж' => 'Zh', 'З' => 'Z',
        'И' => 'Y',  'І' => 'I',  'Ї' => 'Yi', 'Й' => 'Y',  'К' => 'K',
        'Л' => 'L',  'М' => 'M',  'Н' => 'N',  'О' => 'O',  'П' => 'P',
        'Р' => 'R',  'С' => 'S',  'Т' => 'T',  'У' => 'U',  'Ф' => 'F',
        'Х' => 'Kh', 'Ц' => 'Ts', 'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Shch',
        'Ь' => '',   'Ю' => 'Yu', 'Я' => 'Ya', 'Ъ' => '',
    ];
    $str = strtr($str, $map);
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[^\p{L}\p{N}]+/u', '-', $str);
    $str = trim($str, '-');
    return $str;
}

function get_reading_time($template_path) {
    $file = ABSPATH . DS . TWIG_VIEWS_DIRNAME . DS . $template_path;
    if(!file_exists($file)) return 1;

    $content = file_get_contents($file);

    // remove twig tags and HTML
    $content = preg_replace('/\{[%#\{].*?[%#\}]\}/s', '', $content);
    $content = strip_tags($content);
    $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');

    // count words (unicode-aware)
    $words = preg_match_all('/[\p{L}\p{N}]+/u', $content);

    // ~200 words/min for Ukrainian, minimum 1 min
    $minutes = max(1, (int)ceil($words / 200));

    return $minutes;
}

function parse_yaml($file_path) {
    $content = file_get_contents($file_path);
    $data = [];

    $lines = explode("\n", trim($content));
    foreach($lines as $line) {
        $line = trim($line);
        if($line === '' || $line === '---') continue;
        if(preg_match('/^(\w+)\s*:\s*"(.+?)"\s*$/', $line, $kv)) {
            $data[$kv[1]] = $kv[2];
        } elseif(preg_match('/^(\w+)\s*:\s*(.+?)\s*$/', $line, $kv)) {
            $data[$kv[1]] = $kv[2];
        }
    }

    return $data;
}

function get_courses() {
    $courses_dir = ABSPATH . DS . TWIG_VIEWS_DIRNAME . DS . 'courses';
    $courses = [];

    if(!is_dir($courses_dir)) return $courses;

    $dirs = glob($courses_dir . DS . '*', GLOB_ONLYDIR);
    foreach($dirs as $dir) {
        $slug = basename($dir);
        $lessons = [];

        $files = glob($dir . DS . '*.twig');
        sort($files);
        foreach($files as $file) {
            $filename = basename($file, '.twig');
            if($filename === 'about') continue;
            if(preg_match('/^(\d+)/', $filename, $m)) {
                $num = $m[1];

                // parse lesson title and description from file content (not filename — avoids Unicode issues on hosting)
                $lesson_title = '';
                $lesson_desc = '';
                $lesson_content = file_get_contents($file);
                if(preg_match('/<h1>(.*?)<\/h1>/s', $lesson_content, $tm)) {
                    $lesson_title = trim(strip_tags($tm[1]));
                }
                if(preg_match('/<h1>.*?<\/h1>\s*<p>(.*?)<\/p>/s', $lesson_content, $lm)) {
                    $lesson_desc = trim(strip_tags($lm[1]));
                }

                $url_slug = $num . '-' . ukr_to_lat($lesson_title);
                $lesson_file = 'courses/' . $slug . '/' . $filename . '.twig';

                $lessons[] = [
                    'number'       => intval($num),
                    'slug'         => $url_slug,
                    'title'        => $lesson_title,
                    'description'  => $lesson_desc,
                    'reading_time' => get_reading_time($lesson_file),
                    'file'         => $lesson_file,
                ];
            }
        }

        usort($lessons, fn($a, $b) => $a['number'] - $b['number']);

        // parse course metadata from about.yaml
        $about_file = $dir . DS . 'about.yaml';
        $meta = ['title' => ucfirst($slug), 'description' => '', 'icon' => '📚'];
        if(file_exists($about_file)) {
            $meta = array_merge($meta, parse_yaml($about_file));
        }

        // calculate total reading time
        $total_reading_time = array_sum(array_column($lessons, 'reading_time'));

        $courses[$slug] = [
            'slug'               => $slug,
            'title'              => $meta['title'],
            'description'        => $meta['description'],
            'icon'               => $meta['icon'],
            'lessons'            => $lessons,
            'total_reading_time' => $total_reading_time,
        ];
    }

    return $courses;
}

function get_context() {

    $context = array();

    // for index page (list of templates)
    $context['allowed_templates'] = get_allowed_templates(true);

    // get all blocks all styles
    $context['all_blocks_styles'] = get_all_blocks_styles();

    // for rest of the pages
    $context['site']['charset'] = SITE_CHARSET;
    $context['site']['url'] = HOME_URL;
    $context['site']['name'] = SITE_NAME;
    $context['site']['html_loc'] = HTML_LOC;
    $context['site']['assets'] = ASSETS_URL;
    $context['assets_version'] = ASSETS_VERSION;
    $context['request']['get'] = $_GET;
    $context['request']['post'] = $_POST;
    $context['request']['cookie'] = $_COOKIE;
    $context['request']['server'] = $_SERVER;
    $context['svg_sprite'] = SVG_SPRITE_URL;
    $context['svg_folder'] = SVG_FOLDER;
    $context['img_folder'] = IMG_FOLDER;
    $context['year'] = date('Y');

    // courses
    $context['courses'] = get_courses();

    return $context;

}

function get_twig() {

    // create twig loader
    $loader = new \Twig\Loader\FilesystemLoader(TWIG_VIEWS_DIRNAME);

    // twig render options
    $twig_options = array();

    // create twig object
    $twig = new \Twig\Environment($loader, $twig_options);

    // adding twig global functions
     $twig->addFunction(new \Twig\TwigFunction('ucfirst', 'ucfirst'));
     $twig->addFunction(new \Twig\TwigFunction('rand_id', function(){
         return bin2hex(random_bytes(8));
     }));

    // adding twig filters
    $twig->addFilter( new \Twig\TwigFilter( 'pr', 'pr' ) );
    $twig->addFilter( new \Twig\TwigFilter( 'log', 'write_log' ) );

    return $twig;

}

function get_template($template, $context = []) {
    $twig = get_twig();
    $output = $twig->render($template, $context);
    return minify_html($output);
}

function generate_sitemap() {
    $courses = get_courses();
    $today = date('Y-m-d');

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    // homepage
    $xml .= "  <url>\n";
    $xml .= "    <loc>" . HOME_URL . "</loc>\n";
    $xml .= "    <changefreq>weekly</changefreq>\n";
    $xml .= "    <priority>1.0</priority>\n";
    $xml .= "  </url>\n";

    foreach($courses as $slug => $course) {
        // course page
        $xml .= "  <url>\n";
        $xml .= "    <loc>" . HOME_URL . $slug . "/</loc>\n";
        $xml .= "    <changefreq>weekly</changefreq>\n";
        $xml .= "    <priority>0.8</priority>\n";
        $xml .= "  </url>\n";

        // lessons
        foreach($course['lessons'] as $lesson) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . HOME_URL . $slug . "/" . $lesson['slug'] . "/</loc>\n";
            $xml .= "    <changefreq>monthly</changefreq>\n";
            $xml .= "    <priority>0.6</priority>\n";
            $xml .= "  </url>\n";
        }
    }

    $xml .= "</urlset>";
    return $xml;
}

function generate_robots() {
    $robots = "User-agent: *\n";
    $robots .= "Allow: /\n\n";
    $robots .= "Sitemap: " . HOME_URL . "sitemap.xml\n";
    return $robots;
}

function get_lesson_text($template_path) {
    $file = ABSPATH . DS . TWIG_VIEWS_DIRNAME . DS . $template_path;
    if(!file_exists($file)) return '';

    $content = file_get_contents($file);
    $content = preg_replace('/\{[%#\{].*?[%#\}]\}/s', '', $content);
    $content = strip_tags($content);
    $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
    $content = preg_replace('/\s+/', ' ', $content);

    return trim($content);
}

function get_lesson_toc($template_path) {
    $file = ABSPATH . DS . TWIG_VIEWS_DIRNAME . DS . $template_path;
    if(!file_exists($file)) return [];

    $content = file_get_contents($file);

    // find all <h2> and <h3> tags
    if(!preg_match_all('/<(h[23])>(.*?)<\/\1>/s', $content, $matches, PREG_SET_ORDER)) {
        return [];
    }

    $toc = [];
    $counter = 0;
    foreach($matches as $m) {
        $tag   = $m[1]; // h2 or h3
        $text  = trim(strip_tags($m[2]));
        $text  = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        if(!$text) continue;

        $counter++;
        $id = 'toc-' . $counter;

        $toc[] = [
            'id'    => $id,
            'level' => ($tag === 'h2') ? 2 : 3,
            'text'  => $text,
        ];
    }

    return $toc;
}

function search_lessons($query) {
    $query = mb_strtolower(trim($query), 'UTF-8');
    if(mb_strlen($query) < 2) return [];

    $courses = get_courses();
    $results = [];

    foreach($courses as $slug => $course) {
        foreach($course['lessons'] as $lesson) {
            // search in title + description + course name
            $meta = mb_strtolower(
                $lesson['title'] . ' ' . $lesson['description'] . ' ' . $course['title'],
                'UTF-8'
            );
            $found_in_meta = mb_strpos($meta, $query) !== false;

            // search in lesson body content
            $snippet = '';
            $found_in_content = false;
            if(!$found_in_meta) {
                $body = mb_strtolower(get_lesson_text($lesson['file']), 'UTF-8');
                $pos = mb_strpos($body, $query);
                if($pos !== false) {
                    $found_in_content = true;
                    // extract snippet around match
                    $start = max(0, $pos - 40);
                    $len = mb_strlen($query) + 80;
                    $raw = mb_substr($body, $start, $len, 'UTF-8');
                    $snippet = ($start > 0 ? '...' : '') . trim($raw) . '...';
                }
            }

            if($found_in_meta || $found_in_content) {
                $results[] = [
                    'title'        => $lesson['title'],
                    'description'  => $lesson['description'],
                    'url'          => HOME_URL . $slug . '/' . $lesson['slug'] . '/',
                    'course'       => $course['title'],
                    'course_icon'  => $course['icon'],
                    'number'       => $lesson['number'],
                    'reading_time' => $lesson['reading_time'],
                    'snippet'      => $snippet,
                ];
            }
        }
    }

    return $results;
}
