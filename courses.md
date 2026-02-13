# Courses — структура та стилі

Документація для створення та генерації нових курсів на платформі edu.kaplia.pro.

---

## Структура файлів курсу

```
views/courses/
  {course-slug}/
    about.yaml                   ← метадані курсу
    01-назва-лекції.twig         ← файл лекції
    02-друга-лекція.twig
    ...
```

### about.yaml

Кожен курс має файл `about.yaml` в корені папки курсу. Формат — чистий YAML:

```yaml
title: "HTML & CSS — курс для початківців"
description: "18 лекцій від основ пошуку інформації до адаптивної верстки. Конспекти зібрані з найкращих джерел та перевірені практикою."
icon: "🌐"
```

| Поле          | Тип    | Опис                                        |
|---------------|--------|---------------------------------------------|
| `title`       | string | Повна назва курсу (відображається в hero)    |
| `description` | string | Короткий опис курсу (1-2 речення)           |
| `icon`        | string | Emoji-іконка курсу                          |

Значення в лапках, якщо містять спецсимволи (`:`, `—`, тощо).

### Іменування файлів лекцій

Формат: `{номер}-{назва-українською}.twig`

- Номер — порядковий, з нулем (`01`, `02`, ..., `18`)
- Назва — українською через дефіс
- Сортування відбувається по номеру автоматично
- Slug для URL генерується автоматично через транслітерацію

Приклад: `03-контентні-теги.twig` → URL: `/html-css/03-kontentni-tehy/`

---

## Шаблон лекції

Кожна лекція — це Twig-файл з чистим HTML всередині. Жодної Twig-логіки (if/for/include) в лекціях немає.

### Мінімальний шаблон

```twig
{% extends "overall/base-lesson.twig" %}

{% block lesson %}

    <h1>Назва лекції</h1>
    <p>Короткий підзаголовок або опис лекції</p>

    <!-- ===== SECTION 1 ===== -->
    <section>
        <h2>Перший розділ</h2>
        <p>Текст розділу.</p>
    </section>

    <!-- ===== SECTION 2 ===== -->
    <section>
        <h2>Другий розділ</h2>
        <p>Текст другого розділу.</p>
    </section>

{% endblock %}
```

### Обов'язкові правила

1. **Extends** — завжди `{% extends "overall/base-lesson.twig" %}`
2. **Block** — завжди `{% block lesson %}...{% endblock %}`
3. **h1** — рівно один, першим елементом, без класів
4. **p після h1** — короткий опис/підзаголовок лекції (береться як description для SEO)
5. **Секції** — кожна тема обгорнута в `<section>` з коментарем-розділювачем
6. **h2** — по одному в кожній `<section>`, головний заголовок розділу
7. **h3** — підзаголовки всередині секцій
8. **Без класів на стандартних тегах** — h1, h2, h3, p, ul, ol, li, table, section ніколи не мають CSS-класів

---

## HTML-теги для контенту лекцій

### Текст

```html
<p>Звичайний параграф тексту.</p>

<p>Текст з <strong>жирним виділенням</strong> та <em>курсивом</em>.</p>

<p>Використовуйте <code>display: flex</code> для гнучкої розкладки.</p>
```

### Заголовки

```html
<h1>Заголовок лекції</h1>        <!-- рівно один, на початку -->
<h2>Заголовок розділу</h2>        <!-- один на <section>, потрапляє в TOC -->
<h3>Підзаголовок</h3>             <!-- всередині секцій, потрапляє в TOC -->
```

TOC (зміст збоку) автоматично парситься з усіх `<h2>` та `<h3>`.

### Списки

```html
<ul>
    <li>Перший пункт</li>
    <li><strong>Важливий пункт</strong> — з поясненням</li>
    <li>Третій пункт</li>
</ul>

<ol>
    <li><strong>Крок 1.</strong> Пояснення першого кроку.</li>
    <li><strong>Крок 2.</strong> Пояснення другого кроку.</li>
</ol>
```

### Посилання

```html
<a href="https://developer.mozilla.org/" target="_blank">MDN Web Docs</a>
```

Посилання з `target="_blank"` автоматично отримують іконку зовнішнього посилання через CSS `::after`.

### Таблиці

```html
<table>
    <thead>
        <tr>
            <th>Властивість</th>
            <th>Опис</th>
            <th>Приклад</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>display</code></td>
            <td>Визначає тип відображення</td>
            <td><code>block</code>, <code>flex</code></td>
        </tr>
    </tbody>
</table>
```

На мобільних таблиці автоматично отримують горизонтальний скрол.

---

## Блоки коду

### Inline код

```html
<p>Властивість <code>margin</code> задає зовнішні відступи.</p>
```

### Блок коду з підсвіткою синтаксису

Підсвітка — ручна, через `<span>` з класами. Без сторонніх бібліотек.

**CSS-код:**

```html
<pre><code><span class="sel">.container</span> {
    <span class="prop">display</span>: <span class="val">flex</span>;
    <span class="prop">gap</span>: <span class="val">1rem</span>;
    <span class="comment">/* основний контейнер */</span>
}</code></pre>
```

**HTML-код:**

```html
<pre><code><span class="tag">&lt;div</span> <span class="attr">class=</span><span class="val">"wrapper"</span><span class="tag">&gt;</span>
    <span class="tag">&lt;p&gt;</span>Hello<span class="tag">&lt;/p&gt;</span>
<span class="tag">&lt;/div&gt;</span></code></pre>
```

### Класи підсвітки синтаксису

| Клас       | Призначення                          | Приклад            |
|------------|--------------------------------------|--------------------|
| `.tag`     | HTML тег (ім'я + кутові дужки)      | `<div>`, `</a>`    |
| `.attr`    | HTML атрибут                         | `class=`, `href=`  |
| `.val`     | Значення атрибуту або CSS-значення   | `"flex"`, `red`    |
| `.sel`     | CSS селектор                         | `.header`, `#main` |
| `.prop`    | CSS властивість                      | `display`, `color` |
| `.comment` | Коментар (CSS або HTML)              | `/* text */`       |

---

## Кастомні CSS-блоки

### Callout-блоки (підказки, приклади, попередження)

Найчастіше використовувані елементи — є практично в кожній лекції.

```html
<div class="tip">
    Рекомендація: завжди використовуйте семантичні теги замість <code>&lt;div&gt;</code>.
</div>

<div class="example">
    Приклад: <code>flex-direction: column</code> розташовує елементи вертикально.
</div>

<div class="warning">
    Увага: не використовуйте <code>float</code> для розкладки — це застарілий підхід.
</div>
```

| Клас       | Іконка | Колір фону                | Призначення                       |
|------------|--------|---------------------------|-----------------------------------|
| `.tip`     | 💡     | зелений (--color-tip-bg)  | Поради, рекомендації              |
| `.example` | 📌     | синій (--color-example-bg) | Конкретні приклади                |
| `.warning` | ⚠️     | жовтий (--color-warning-bg)| Застереження, типові помилки     |

Іконки додаються автоматично через CSS `::before`.

### Порівняння (comparison)

Два блоки поряд — для порівняння підходів, правильного/неправильного:

```html
<div class="comparison">
    <div>
        <strong>Правильно ✅</strong>
        <ul>
            <li>Семантичні теги</li>
            <li>Flexbox для розкладки</li>
        </ul>
    </div>
    <div>
        <strong>Неправильно ❌</strong>
        <ul>
            <li>Тільки div-и</li>
            <li>Float для розкладки</li>
        </ul>
    </div>
</div>
```

Перший блок отримує зелену ліву бордюру, другий — червону. На мобільних стає одна колонка.

### Картки сайтів (Lecture 01)

Для класифікації ресурсів з кольоровим індикатором:

```html
<div class="legend">
    <span><span class="dot dot-red"></span> Ненадійні</span>
    <span><span class="dot dot-yellow"></span> Різна надійність</span>
    <span><span class="dot dot-green"></span> Надійні</span>
</div>

<div class="site-card unreliable">
    <strong>Назва ресурсу</strong> — опис чому ненадійний.
</div>

<div class="site-card mixed">
    <strong>Назва ресурсу</strong> — опис ситуації.
</div>

<div class="site-card reliable">
    <strong>Назва ресурсу</strong> — опис чому надійний.
</div>
```

| Клас                  | Колір бордюру | Призначення          |
|-----------------------|---------------|----------------------|
| `.site-card.reliable`   | зелений       | Надійний ресурс      |
| `.site-card.mixed`      | жовтий        | Мішаний ресурс       |
| `.site-card.unreliable` | червоний      | Ненадійний ресурс    |

### Діаграма Box Model (Lectures 03, 04)

Вкладені блоки для візуалізації CSS Box Model:

```html
<div class="box-model">
    <div class="box-model-diagram">
        <div class="margin-zone">
            <div class="label">margin</div>
            <div class="border-zone">
                <div class="label">border</div>
                <div class="padding-zone">
                    <div class="label">padding</div>
                    <div class="content-zone">content</div>
                </div>
            </div>
        </div>
    </div>
</div>
```

---

## Повний приклад лекції

```twig
{% extends "overall/base-lesson.twig" %}

{% block lesson %}

    <h1>Назва нової лекції</h1>
    <p>Короткий опис того, що розглядається в цій лекції</p>

    <!-- ===== SECTION 1 ===== -->
    <section>
        <h2>Вступ до теми</h2>
        <p>Пояснення основних концепцій.</p>

        <ul>
            <li><strong>Перше поняття</strong> — пояснення.</li>
            <li><strong>Друге поняття</strong> — пояснення.</li>
        </ul>

        <div class="tip">
            Порада: почніть з простих прикладів перед складними.
        </div>
    </section>

    <!-- ===== SECTION 2 ===== -->
    <section>
        <h2>Основні властивості</h2>

        <table>
            <thead>
                <tr>
                    <th>Властивість</th>
                    <th>Значення</th>
                    <th>Опис</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>property-name</code></td>
                    <td><code>value</code></td>
                    <td>Що робить ця властивість</td>
                </tr>
            </tbody>
        </table>

        <h3>Приклад використання</h3>

        <pre><code><span class="sel">.element</span> {
    <span class="prop">property-name</span>: <span class="val">value</span>;
}</code></pre>

        <div class="example">
            Цей код задає елементу потрібне значення.
        </div>
    </section>

    <!-- ===== SECTION 3 ===== -->
    <section>
        <h2>Порівняння підходів</h2>

        <div class="comparison">
            <div>
                <strong>Сучасний підхід ✅</strong>
                <pre><code><span class="sel">.container</span> {
    <span class="prop">display</span>: <span class="val">flex</span>;
}</code></pre>
            </div>
            <div>
                <strong>Застарілий підхід ❌</strong>
                <pre><code><span class="sel">.container</span> {
    <span class="prop">float</span>: <span class="val">left</span>;
}</code></pre>
            </div>
        </div>

        <div class="warning">
            Уникайте застарілого підходу — він створює проблеми з адаптивністю.
        </div>
    </section>

    <!-- ===== SECTION 4 ===== -->
    <section>
        <h2>Корисні ресурси</h2>
        <ul>
            <li><a href="https://developer.mozilla.org/" target="_blank">MDN Web Docs</a> — головна документація</li>
            <li><a href="https://caniuse.com/" target="_blank">Can I Use</a> — підтримка браузерами</li>
        </ul>
    </section>

{% endblock %}
```

---

## Файли стилів

| Файл                         | Призначення                                      |
|------------------------------|--------------------------------------------------|
| `assets/scss/style.scss`     | Головний файл — імпортує всі інші                |
| `assets/scss/_variables.scss` | CSS-змінні, кольори, теми, breakpoints           |
| `assets/scss/_reset.scss`    | CSS reset                                         |
| `assets/scss/_mixins.scss`   | SCSS міксіни                                      |
| `assets/scss/_animation.scss`| Анімації                                          |
| `assets/scss/_overall.scss`  | Загальні стилі (layout, header, footer, toolbar)  |
| `assets/scss/_main.scss`     | Стилі лекцій (callouts, code, TOC, lesson-nav)   |

### Порядок імпорту

```scss
@import "mixins";
@import "variables";
@import "reset";
@import "animation";
@import "overall";    // загальні стилі — всі сторінки
@import "main";       // стилі лекцій — сторінки лекцій
```

---

## Автоматична функціональність

Ці функції працюють автоматично без додаткової розмітки в лекціях:

| Функція               | Як працює                                                     |
|-----------------------|---------------------------------------------------------------|
| **TOC (зміст)**       | Парситься з `<h2>` та `<h3>`, sidebar справа на десктопі      |
| **Reading time**      | Рахується по кількості слів (~200 сл/хв)                     |
| **Breadcrumbs**       | Головна → Курс → Лекція                                      |
| **SEO description**   | Береться з `<p>` після `<h1>`, або генерується з назви курсу  |
| **Progress bar**      | Прогрес-бар вгорі сторінки при скролі                         |
| **Lesson pagination** | Попередня/наступна лекція — фіксована панель знизу            |
| **External link icon**| Іконка 🔗 на посиланнях з `target="_blank"`                   |
| **Sitemap**           | Всі курси та лекції автоматично в sitemap.xml                 |
| **Search**            | Пошук по заголовках, описах та контенту лекцій                |

---

## Створення нового курсу

1. Створити папку `views/courses/{course-slug}/`
2. Додати `about.yaml` з title, description, icon
3. Додати лекції у форматі `{NN}-{назва}.twig`
4. Курс з'явиться автоматично на головній, в навігації, пошуку та sitemap
