<?php

if(!defined('ABSPATH')){exit;}

function router($url_segments = []){

    // building overall context
    $context = get_context();

    // body classes
    $body_classes = array('preload');

    if(!empty($url_segments)){

        if(empty($url_segments[0])){
            $body_classes[] = 'index';
            $template = 'index.twig';
            $context['page']['title'] = 'Вік живи — вік учись!';
            $context['page']['description'] = 'Безкоштовні лекції та конспекти з IT, медіа та саморозвитку. Перевірені часом матеріали, зібрані з найкращих джерел.';
            $context['page']['canonical'] = HOME_URL;

        // course routing: /{course-slug}/ or /{course-slug}/{lesson-slug}/
        } elseif(isset($context['courses'][$url_segments[0]])) {

            $course = $context['courses'][$url_segments[0]];
            $context['course'] = $course;
            $context['lessons'] = $course['lessons'];

            if(!empty($url_segments[1])) {
                // lesson page: /html-css/01-slug/
                $lesson_slug = $url_segments[1];
                $found = false;
                foreach($course['lessons'] as $idx => $lesson) {
                    if($lesson['slug'] === $lesson_slug) {
                        $template = $lesson['file'];
                        $context['page']['title'] = $lesson['title'];
                        $context['lesson'] = $lesson;
                        $context['page']['description'] = $lesson['description'] ?: $course['title'] . ' — лекція ' . $lesson['number'] . ': ' . $lesson['title'];
                        $context['page']['canonical'] = HOME_URL . $course['slug'] . '/' . $lesson['slug'] . '/';
                        $context['lesson_current'] = $idx + 1;
                        $context['lesson_total'] = count($course['lessons']);
                        $context['reading_time'] = get_reading_time($lesson['file']);
                        $context['toc'] = get_lesson_toc($lesson['file']);
                        $context['prev_lesson'] = $idx > 0 ? $course['lessons'][$idx - 1] : null;
                        $context['next_lesson'] = isset($course['lessons'][$idx + 1]) ? $course['lessons'][$idx + 1] : null;
                        $body_classes[] = 'course-lesson';
                        $context['breadcrumbs'] = [
                            ['title' => 'Головна', 'url' => HOME_URL],
                            ['title' => $course['title'], 'url' => HOME_URL . $course['slug'] . '/'],
                            ['title' => $lesson['title']],
                        ];
                        $found = true;
                        break;
                    }
                }
                if(!$found) {
                    header("HTTP/1.1 404 Not Found");
                    $body_classes[] = 'error404';
                    $template = 'others/404.twig';
                    $context['page']['title'] = 'Error 404';
                }
            } else {
                // course about page: /html-css/
                $template = 'overall/course-about.twig';
                $context['page']['title'] = $course['title'];
                $context['page']['description'] = $course['description'];
                $context['page']['canonical'] = HOME_URL . $course['slug'] . '/';
                $body_classes[] = 'course-about';
                $context['breadcrumbs'] = [
                    ['title' => 'Головна', 'url' => HOME_URL],
                    ['title' => $course['title']],
                ];
            }

        } elseif(in_array($url_segments[0], get_allowed_templates())) {
            $context['page']['title'] = ucfirst($url_segments[0]);
            $template = $url_segments[0] . '.twig';
            $context['breadcrumbs'] = [
                ['title' => 'Головна', 'url' => HOME_URL],
                ['title' => ucfirst($url_segments[0])],
            ];

            if($url_segments[0] == 'password-protected'){
                $body_classes[] = 'password-protected';
            }

            if($url_segments[0] == 'coming-soon'){
                $body_classes[] = 'header-disabled';
            }

        } else {
            header("HTTP/1.1 404 Not Found");
            $body_classes[] = 'error404';
            $template = 'others/404.twig';
            $context['page']['title'] = 'Error 404';
            $context['page']['content'] = 'How did you get here?';
        }

    }

    // set body classes
    $context['body_classes'] = implode(' ', $body_classes);
    $context['html_title'] = $context['page']['title'] . ' — ' . SITE_NAME;

    $return = array();
    $return[0] = $template;
    $return[1] = $context;
    return $return;

}
