# Вік живи — вік учись!

**[edu.kaplia.pro](https://edu.kaplia.pro/)** — легкий PHP-рушій для освітньої платформи з курсами та лекціями. Без бази даних, без фреймворків, без CMS. Контент зберігається у файловій системі, шаблони рендеряться через Twig, стилі компілюються з SCSS.

**Stack**: PHP 8.3 · Twig 3.x · SCSS · Vanilla JS · Apache

---

## Архітектура

```
edu.local/
├── index.php                    # Front controller
├── .htaccess                    # URL rewriting (Apache)
├── composer.json                # Залежності (Twig)
├── core/
│   ├── init.php                 # Константи, конфіг, підключення
│   └── includes/
│       ├── twig.php             # Функції + Twig setup + SEO генерація
│       ├── router.php           # Маршрутизація URL
│       └── render.php           # Парсинг URL, диспетчер запитів
├── views/
│   ├── index.twig               # Головна сторінка
│   ├── overall/                 # Загальні шаблони (layout, header, footer...)
│   ├── others/                  # 404 тощо
│   └── courses/                 # Курси та лекції
│       └── {course-slug}/
│           ├── about.yaml       # Метадані курсу
│           └── NN-назва.twig    # Файли лекцій
├── assets/
│   ├── scss/                    # SCSS вихідники
│   ├── css/                     # Скомпільований CSS
│   ├── js/                      # JavaScript
│   ├── svg/                     # SVG спрайт та favicon
│   └── img/                     # Зображення та фавікони
└── vendor/                      # Composer (Twig + залежності)
```

---

## Потік запиту

```
Браузер → .htaccess → index.php → init.php → render.php
                                                  │
                         ┌────────────────────────┘
                         ▼
              URL: /sitemap.xml  → XML sitemap
              URL: /robots.txt   → robots.txt
              URL: /api/search/  → JSON відповідь
              URL: інше          → router() → Twig render → HTML
```

1. Apache перенаправляє всі запити на `index.php`
2. `index.php` завантажує Composer autoloader та `core/init.php`
3. `init.php` визначає константи і підключає три core-файли
4. `render.php` парсить `REQUEST_URI`, обробляє спец-маршрути (sitemap, robots, API), забезпечує trailing slash (301 redirect)
5. `router()` визначає шаблон і контекст на основі URL-сегментів
6. Twig рендерить шаблон → HTML minification → відповідь

---

## Маршрутизація

| URL                              | Шаблон                       | Опис                       |
|----------------------------------|------------------------------|-----------------------------|
| `/`                              | `index.twig`                 | Головна з каталогом курсів |
| `/{course}/`                     | `overall/course-about.twig`  | Сторінка курсу (зміст)    |
| `/{course}/{lesson}/`            | `courses/{course}/{file}.twig`| Сторінка лекції           |
| `/{template-name}/`             | `{template-name}.twig`       | Довільний шаблон з views/  |
| `/sitemap.xml`                   | —                            | XML sitemap (динамічний)   |
| `/robots.txt`                    | —                            | robots.txt (динамічний)    |
| `/api/search/?q={query}`         | —                            | Пошук (JSON API)           |
| будь-що інше                     | `others/404.twig`            | Сторінка 404               |

---

## Система курсів

Курси та лекції зберігаються у файловій системі без бази даних.

### Автовиявлення

Система автоматично сканує `views/courses/*/` і для кожної папки:
- Парсить `about.yaml` (назва, опис, іконка)
- Збирає всі `*.twig` файли лекцій
- Витягує номер і назву з імені файлу (формат: `NN-назва.twig`)
- Генерує URL slug через транслітерацію `ukr_to_lat()`
- Витягує опис лекції з першого `<p>` після `<h1>`
- Розраховує час читання (~200 слів/хв для української)
- Рахує загальний час курсу

### Метадані курсу (about.yaml)

```yaml
title: "HTML & CSS — курс для початківців"
description: "18 лекцій від основ пошуку інформації до адаптивної верстки."
icon: "🌐"
```

### Іменування лекцій

```
01-як-правильно-шукати-інформацію.twig
02-введення-в-верстку.twig
```

- Номер з нулем (`01`, `02`... `18`)
- Назва українською через дефіс
- Сортування автоматичне по номеру
- `about.twig` / `about.yaml` ігноруються як лекції

### Створення нового курсу

1. Створити папку `views/courses/{slug}/`
2. Додати `about.yaml` з title, description, icon
3. Додати лекції `NN-назва.twig`
4. Курс з'явиться автоматично скрізь: головна, sitemap, пошук

---

## Шаблонізація (Twig 3.x)

### Ієрархія шаблонів

```
base.twig                         # Кореневий layout (HTML shell)
  ├── html-header.twig            #   <head>: meta, fonts, CSS, schema.org
  ├── header.twig                 #   Шапка сайту
  ├── {% block base %}            #   Основний контент
  ├── lesson-pagination.twig      #   Prev/Next (тільки на лекціях)
  ├── floating-controls.twig      #   Toolbar (на всіх, крім лекцій)
  ├── footer.twig                 #   Футер
  ├── search-overlay.twig         #   Оверлей пошуку
  └── html-footer.twig            #   JS підключення

base-lesson.twig (extends base.twig)
  ├── breadcrumbs.twig            #   Хлібні крихти
  ├── reading-time.twig           #   Бейдж часу читання
  └── .lesson-layout
      ├── article.lesson-content  #   {% block lesson %}
      └── toc-sidebar.twig        #   Зміст збоку (якщо >1 заголовок)
```

### Контекст (змінні доступні в шаблонах)

**Глобальні (всі сторінки):**

| Змінна                  | Опис                                     |
|-------------------------|------------------------------------------|
| `site.url`              | URL сайту                                |
| `site.name`             | Назва сайту                              |
| `site.charset`          | Кодування (UTF-8)                        |
| `site.html_loc`         | Мова HTML (uk)                           |
| `site.assets`           | URL папки assets                         |
| `assets_version`        | Версія для cache-busting                 |
| `courses`               | Масив усіх курсів з лекціями             |
| `body_classes`          | CSS класи для body                       |
| `html_title`            | Заголовок сторінки + назва сайту         |
| `page.title`            | Заголовок сторінки                       |
| `page.description`      | SEO опис                                 |
| `page.canonical`        | Канонічний URL                           |
| `svg_sprite`            | URL SVG спрайту                          |
| `year`                  | Поточний рік                             |
| `request.get/post/cookie/server` | Суперглобали PHP                |

**Додаткові для лекцій:**

| Змінна            | Опис                                  |
|-------------------|---------------------------------------|
| `course`          | Поточний курс (slug, title, lessons)  |
| `lesson`          | Поточна лекція                        |
| `lessons`         | Усі лекції курсу                      |
| `lesson_current`  | Номер поточної лекції (від 1)         |
| `lesson_total`    | Загальна кількість лекцій             |
| `reading_time`    | Час читання (хвилини)                 |
| `toc`             | Зміст (масив h2/h3)                   |
| `prev_lesson`     | Попередня лекція (або null)           |
| `next_lesson`     | Наступна лекція (або null)            |
| `breadcrumbs`     | Хлібні крихти                         |

### Кастомні Twig-функції та фільтри

| Тип     | Назва     | Опис                              |
|---------|-----------|-----------------------------------|
| Функція | `ucfirst` | Перша буква великою               |
| Функція | `rand_id` | Випадковий 16-символьний hex ID   |
| Фільтр  | `pr`      | Debug print_r                     |
| Фільтр  | `log`     | Запис в debug.log                 |

---

## Функції та можливості

### Пошук

- **API**: `GET /api/search/?q={query}` → JSON
- Мінімальна довжина запиту: 2 символи
- Пошук по: заголовках, описах, назвах курсів, повному тексту лекцій
- Контекстні сніпети (~80 символів навколо знайденого)
- Unicode-aware (mb_strtolower, mb_strpos)

**Frontend**:
- Fullscreen оверлей з blur backdrop
- Відкриття: кнопка або `Ctrl+K` / `Cmd+K`
- Закриття: `Escape`, кнопка або клік по фону
- Debounce 300ms на введення
- Навігація стрілками (Up/Down) з підсвіткою
- Enter для переходу до вибраного результату

### Темна тема

- Збереження в `localStorage` (ключ: `theme`)
- Раннє застосування: inline `<script>` в `<head>` перевіряє localStorage до рендеру, щоб уникнути "блимання"
- Переключення через кнопку `.theme-toggle` (є в toolbar і lesson-nav)
- CSS custom properties (`:root` для світлої, `[data-theme="dark"]` для темної)
- Плавний перехід: клас `theme-switching` на `<html>` на 400ms
- Нейтральні кольори темної теми (чисті сірі, без синього відтінку)

### TOC (зміст лекції)

- **Backend**: `get_lesson_toc()` парсить `<h2>` і `<h3>` з twig-файлу
- Генерує послідовні ID (`toc-1`, `toc-2`...)
- **Frontend**: JS призначає відповідні ID на DOM-елементи
- Smooth scroll при кліку з оновленням URL hash
- IntersectionObserver для підсвітки активного заголовка
- Автоскрол всередині TOC nav, щоб активний пункт був видимий
- `position: fixed`, видимий тільки на екранах >1100px
- На широких екранах контейнер лекції зміщується вліво, TOC — справа

### Прогрес-бар читання

- Фіксована смужка вгорі сторінки (3px)
- Ширина залежить від позиції скролу
- Тільки на сторінках лекцій

### Навігація по лекціях

- Фіксована панель знизу з glassmorphism (blur + transparency)
- Посилання на попередню/наступну лекцію
- Лічильник: "3 / 18"
- Кнопки: пошук, тема, скрол вгору
- На не-лекційних сторінках замість неї — компактний toolbar

### Хлібні крихти

- Автоматична генерація: Головна → Курс → Лекція
- Schema.org BreadcrumbList мікророзмітка

### Час читання

- Розраховується автоматично по кількості слів
- ~200 слів/хв для української
- Мінімум 1 хвилина
- Видаляє Twig-теги та HTML перед підрахунком

### Скрол вгору

- Кнопка в toolbar/lesson-nav
- `disabled` коли користувач на верху сторінки (scrollY < 50)
- Smooth scroll до верху

---

## SEO

### Meta-теги

- `<title>` з назвою сторінки та сайту
- `<meta name="description">` — опис з контексту або автогенерований
- `<link rel="canonical">` — канонічний URL

### Open Graph

- `og:title`, `og:description`, `og:url`, `og:site_name`
- `og:type` = website
- `og:locale` = uk_UA

### Schema.org (JSON-LD)

| Сторінка       | Schema              | Опис                                      |
|----------------|--------------------|--------------------------------------------|
| Головна        | `WebSite`          | Назва, URL, опис сайту                     |
| Курс           | `Course` + `ItemList` | Курс з списком усіх лекцій              |
| Лекція         | `Article`          | Автор, wordCount, timeRequired, isPartOf   |

### Sitemap та Robots

- `/sitemap.xml` — динамічна генерація з усіх курсів і лекцій
- `/robots.txt` — Allow: / + посилання на sitemap
- Trailing slash enforcement (301 redirect)

---

## CSS / SCSS

### Збірка

**Prepros** компілює SCSS → CSS з мініфікацією та source maps.

### Порядок імпорту

```scss
@import "mixins";       // Кросбраузерні міксіни
@import "variables";    // CSS custom properties + темна тема
@import "reset";        // Мінімальний reset
@import "animation";    // Анімації (placeholder)
@import "overall";      // Загальні стилі (layout, UI компоненти)
@import "main";         // Стилі лекцій (контент, TOC, nav)
```

### Файли

| Файл               | Рядків | Опис                                          |
|---------------------|--------|-----------------------------------------------|
| `_variables.scss`   | ~180   | CSS змінні, кольори, радіуси, темна тема      |
| `_mixins.scss`      | ~100   | Кросбраузерні міксіни (flexbox, blur, тощо)   |
| `_reset.scss`       | ~10    | `* { margin: 0; box-sizing: border-box }`     |
| `_overall.scss`     | ~790   | Body, typography, hero, cards, breadcrumbs, toolbar, footer, search, mobile, dark |
| `_main.scss`        | ~610   | Callouts, code, tables, TOC, lesson-nav, mobile, dark |

### Дизайн-система

- **Шрифт**: Montserrat (Google Fonts, ваги: 400, 500, 600, 700, 900)
- **Контейнер**: max-width 780px
- **Breakpoints**: 600px (mobile), 1100px (TOC sidebar)
- **Радіуси**: sm (4px), md (8px), lg (10px), xl (12px), pill (20px), circle (50%)
- **Glassmorphism**: `backdrop-filter: blur(24px) saturate(180%)` на toolbar та lesson-nav
- **Палітра**: синій primary (#2563eb), семантичні кольори (green/red/yellow)

---

## JavaScript

### Збірка

Prepros конкатенує файли через `@prepros-prepend` директиви та мініфікує.

### Файли

```
assets/js/
├── jquery.min.js                  # jQuery (підключений, не використовується в кастомному коді)
├── plugins.js                     # Placeholder для плагінів
├── custom.js                      # Маніфест конкатенації
├── custom.min.js                  # Скомпільований output
└── custom/
    ├── variables.js               # (порожній)
    ├── functions.js               # Фікс анімації Chrome preload
    ├── document-ready.js          # Основна логіка (IIFE)
    ├── window-load-resize.js      # Placeholder
    └── window-load-scroll-resize.js # Placeholder
```

### Функціональність (document-ready.js)

Весь кастомний JS — vanilla, без jQuery:

1. **Reading progress bar** — ширина залежить від scrollY / documentHeight
2. **Scroll-to-top** — disabled стан при scrollY < 50, smooth scroll
3. **Dark theme toggle** — localStorage, transition animation
4. **Search overlay** — fullscreen modal, debounced API calls, keyboard navigation
5. **TOC sidebar** — ID assignment, smooth scroll, IntersectionObserver, auto-scroll

---

## Конфігурація

Константи визначені в `core/init.php`:

| Константа          | Значення                    | Опис                            |
|--------------------|-----------------------------|---------------------------------|
| `SITE_NAME`        | `edu.kaplia.pro`            | Назва сайту                     |
| `SITE_CHARSET`     | `UTF-8`                     | Кодування                       |
| `HTML_LOC`         | `uk`                        | Мова HTML                       |
| `MINIFY_HTML`      | `false`                     | Мініфікація HTML                |
| `TWIG_VIEWS_DIRNAME` | `views`                   | Папка шаблонів                  |
| `ASSETS_VERSION`   | `1.0.0-date-{timestamp}`   | Cache-busting версія            |
| `HOME_URL`         | auto-detect                 | Автовизначення протоколу + домену |

---

## Безпека

- Усі PHP-файли починаються з `if(!defined('ABSPATH')){exit;}` — захист від прямого доступу
- `.htaccess` блокує доступ до `core/`, `vendor/`
- `display_errors = 0` у продакшені, помилки пишуться в `debug.log`
- Жодних SQL-ін'єкцій — немає бази даних
- Пошуковий запит обрізається і перевіряється на мінімальну довжину

---

## Залежності

```json
{
    "require": {
        "twig/twig": "^3.0"
    },
    "config": {
        "platform": { "php": "8.3" }
    }
}
```

**Транзитивні**: `symfony/polyfill-ctype`, `symfony/polyfill-mbstring`, `symfony/deprecation-contracts`

**Зовнішні сервіси**: Google Fonts (Montserrat)

**Build tools**: Prepros (SCSS компіляція + JS конкатенація/мініфікація)

---

## Розробка

### Встановлення

```bash
composer install
```

### Локальна розробка

1. Налаштувати Apache vhost на папку проекту з `AllowOverride All`
2. Відкрити Prepros і додати проект для автокомпіляції SCSS/JS
3. Готово — додавати курси і лекції через файлову систему

### Додавання нового курсу

1. `mkdir views/courses/{slug}`
2. Створити `views/courses/{slug}/about.yaml`
3. Додати лекції: `01-назва.twig`, `02-назва.twig`...
4. Курс автоматично з'явиться на сайті

Детальна документація по створенню курсів: [`courses.md`](courses.md)
