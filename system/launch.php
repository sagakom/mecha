<?php if( ! defined('ROOT')) die('Rejected!');


/**
 * Route Hook: before
 * ------------------
 */

Weapon::fire('route_before');


/**
 * Manager Page(s)
 * ---------------
 */

if(Guardian::happy() && $cargo = File::exist(DECK . DS . 'launch.php')) {
    include $cargo;
}


/**
 * Secondary Action(s)
 * -------------------
 */

if($cargo = File::exist(DECK . DS . 'workers' . DS . 'task.comment.php')) include $cargo;
if($cargo = File::exist(DECK . DS . 'workers' . DS . 'task.login.php')) include $cargo;


// Exclude these fields ...
$excludes = array('content', 'content_raw', 'tags', 'css', 'js', 'comments');


/**
 * Index Page
 * ----------
 *
 * [1]. article
 * [2]. article/1
 *
 */

Route::accept(array($config->index->slug, $config->index->slug . '/(:num)'), function($offset = 1) use($config, $excludes) {
    $offset = (int) $offset;
    if($files = Mecha::eat(Get::articles())->chunk($offset, $config->index->per_page)->vomit()) {
        $articles = array();
        foreach($files as $file) {
            $articles[] = Get::article($file, $excludes);
        }
        unset($files);
    } else {
        if($offset !== 1) {
            Shield::abort('404-index');
        } else {
            $articles = false;
        }
    }
    Filter::add('pager:url', function($url) {
        return Filter::apply('index:url', $url);
    });
    Config::set(array(
        'page_title' => $config->index->title . $config->title_separator . $config->title,
        'offset' => $offset,
        'articles' => $articles,
        'pagination' => Navigator::extract(Get::articles(), $offset, $config->index->per_page, $config->index->slug)
    ));
    Shield::attach('index-article');
}, 30);


/**
 * Archive Page
 * ------------
 *
 * [1]. archive/2014
 * [2]. archive/2014/1
 *
 */

Route::accept(array($config->archive->slug . '/(:num)', $config->archive->slug . '/(:num)/(:num)'), function($slug = "", $offset = 1) use($config, $excludes) {
    $articles = array();
    $offset = (int) $offset;
    if($files = Mecha::eat(Get::articles('DESC', 'time:' . $slug))->chunk($offset, $config->archive->per_page)->vomit()) {
        foreach($files as $file) {
            $articles[] = Get::article($file, $excludes);
        }
        unset($files);
    } else {
        Shield::abort('404-archive');
    }
    Filter::add('pager:url', function($url) {
        return Filter::apply('archive:url', $url);
    });
    Config::set(array(
        'page_title' => sprintf($config->archive->title, $slug) . $config->title_separator . $config->title,
        'offset' => $offset,
        'archive_query' => $slug,
        'articles' => $articles,
        'pagination' => Navigator::extract(Get::articles('DESC', 'time:' . $slug), $offset, $config->archive->per_page, $config->archive->slug . '/' . $slug)
    ));
    Shield::attach('index-archive');
}, 40);


/**
 * Archive Page
 * ------------
 *
 * [1]. archive/2014-04
 * [2]. archive/2014-04/1
 *
 */

Route::accept(array($config->archive->slug . '/(:num)-(:num)', $config->archive->slug . '/(:num)-(:num)/(:num)'), function($year = "", $month = "", $offset = 1) use($config, $speak, $excludes) {
    $months = (array) $speak->month_names;
    $slug = $year . '-' . $month;
    $articles = array();
    $offset = (int) $offset;
    if($files = Mecha::eat(Get::articles('DESC', 'time:' . $slug))->chunk($offset, $config->archive->per_page)->vomit()) {
        foreach($files as $file) {
            $articles[] = Get::article($file, $excludes);
        }
        unset($files);
    } else {
        Shield::abort('404-archive');
    }
    Filter::add('pager:url', function($url) {
        return Filter::apply('archive:url', $url);
    });
    Config::set(array(
        'page_title' => sprintf($config->archive->title, $year . ', ' . $months[(int) $month - 1]) . $config->title_separator . $config->title,
        'offset' => $offset,
        'archive_query' => $slug,
        'articles' => $articles,
        'pagination' => Navigator::extract(Get::articles('DESC', 'time:' . $slug), $offset, $config->archive->per_page, $config->archive->slug . '/' . $slug)
    ));
    Shield::attach('index-archive');
}, 41);


/**
 * Tag Page
 * --------
 *
 * [1]. tag/tag-slug
 * [2]. tag/tag-slug/1
 *
 */

Route::accept(array($config->tag->slug . '/(:any)', $config->tag->slug . '/(:any)/(:num)'), function($slug = "", $offset = 1) use($config, $excludes) {
    if( ! $tag = Get::tag($slug)) {
        Shield::abort('404-tag');
    }
    $articles = array();
    $offset = (int) $offset;
    if($files = Mecha::eat(Get::articles('DESC', 'kind:' . $tag->id))->chunk($offset, $config->tag->per_page)->vomit()) {
        foreach($files as $file) {
            $articles[] = Get::article($file, $excludes);
        }
        unset($files);
    } else {
        Shield::abort('404-tag');
    }
    Filter::add('pager:url', function($url) {
        return Filter::apply('tag:url', $url);
    });
    Config::set(array(
        'page_title' => sprintf($config->tag->title, $tag->name) . $config->title_separator . $config->title,
        'offset' => $offset,
        'tag_query' => $slug,
        'articles' => $articles,
        'pagination' => Navigator::extract(Get::articles('DESC', 'kind:' . $tag->id), $offset, $config->tag->per_page, $config->tag->slug . '/' . $slug)
    ));
    Shield::attach('index-tag');
}, 50);


/**
 * Search Page
 * -----------
 *
 * [1]. search/search+query
 * [2]. search/search+query/1
 *
 */

Route::accept(array($config->search->slug . '/(:any)', $config->search->slug . '/(:any)/(:num)'), function($query = "", $offset = 1) use($config, $speak, $excludes) {
    $articles = array();
    $offset = (int) $offset;
    $query = Text::parse($query, '->decoded_url');
    $keywords = Text::parse($query, '->slug');
    if(Session::get('search.query') === $query) {
        $articles = Session::get('search.results');
    } else {
        // Matched with all keyword(s) combined
        if($files = Get::articles('DESC', 'keyword:' . $keywords)) {
            foreach($files as $file) {
                $articles[] = $file;
                $anchor = Get::articleAnchor($file);
                $kw = str_replace('-', ' ', $keywords);
                if(strpos(strtolower($anchor->title), $kw) !== false || strpos(File::N($anchor->path), $kw) !== false) {
                    $articles[] = $file;
                }
            }
            unset($files);
        }
        // Matched with a keyword
        $keywords = explode('-', $keywords);
        foreach($keywords as $keyword) {
            if($files = Get::articles('DESC', 'keyword:' . $keyword)) {
                foreach($files as $file) {
                    $articles[] = $file;
                    $anchor = Get::articleAnchor($file);
                    if(strpos(strtolower($anchor->title), $keyword) !== false || strpos(File::N($anchor->path), $keyword) !== false) {
                        $articles[] = $file;
                    }
                }
                unset($files);
            }
        }
        $articles = array_unique($articles); // Remove search result(s) duplicate
        Session::set('search.query', $query);
        Session::set('search.results', $articles);
    }
    $title = sprintf($config->search->title, $query);
    if( ! empty($articles) && $files = Mecha::eat($articles)->chunk($offset, $config->search->per_page)->vomit()) {
        $_articles = array();
        foreach($files as $file) {
            $_articles[] = Get::article($file, $excludes);
        }
        unset($files);
        Filter::add('pager:url', function($url) {
            return Filter::apply('search:url', $url);
        });
        Config::set(array(
            'page_title' => $title . $config->title_separator . $config->title,
            'offset' => $offset,
            'search_query' => $query,
            'articles' => $_articles,
            'pagination' => Navigator::extract($articles, $offset, $config->search->per_page, $config->search->slug . '/' . Text::parse($query, '->encoded_url'))
        ));
        Shield::attach('index-search');
    } else {
        Config::set(array(
            'page_title' => $title . $config->title_separator . $config->title,
            'page' => array(
                'title' => $title,
                'content' => ""
            ),
            'search_query' => $query
        ));
        Session::kill('search.query');
        Session::kill('search.results');
        Shield::abort('404-search');
    }
}, 60);


/**
 * Ignite Search ...
 * -----------------
 */

Route::accept($config->search->slug, function() use($config) {
    if($q = Request::post('q')) {
        Guardian::kick($config->search->slug . '/' . strip_tags(Text::parse($q, '->encoded_url')));
    }
    Guardian::kick();
}, 61);


/**
 * Article Page
 * ------------
 *
 * [1]. article/article-slug
 *
 */

Route::accept($config->index->slug . '/(:any)', function($slug = "") use($config, $speak) {
    if( ! $article = Get::article($slug)) {
        Shield::abort('404-article');
    }
    if(isset($_GET['repair']) && Guardian::happy()) {
        Guardian::kick($config->manager->slug . '/article/repair/id:' . $article->id);
    }
    if($article->state === 'draft') {
        Shield::abort('404-article');
    }
    // Collecting article slug ...
    if($articles = Get::articles('DESC', "", File::E($article->path))) {
        foreach($articles as &$v) {
            $parts = explode('_', File::N($v), 3);
            $v = $parts[2];
        }
        unset($parts, $v);
    }
    Filter::add('pager:url', function($url) {
        return Filter::apply('article:url', $url);
    });
    Config::set(array(
        'page_title' => $article->title . $config->title_separator . $config->title,
        'article' => $article,
        'pagination' => Navigator::extract($articles, $slug, 1, $config->index->slug)
    ));
    Weapon::add('shell_after', function() use($article) {
        if(isset($article->css) && trim($article->css) !== "") echo O_BEGIN . $article->css . O_END;
    });
    Weapon::add('sword_after', function() use($article) {
        if(isset($article->js) && trim($article->js) !== "") echo O_BEGIN . $article->js . O_END;
    });
    Shield::attach('article-' . $slug);
}, 70);


/**
 * XML Sitemap
 * -----------
 *
 * [1]. sitemap
 *
 */

Route::accept('sitemap', function() use($config) {
    HTTP::mime('text/xml', $config->charset);
    $q = ! empty($config->url_query) ? '.' . md5($config->url_query) : "";
    $cache = CACHE . DS . 'sitemap' . $q . '.cache';
    if(file_exists($cache)) {
        $content = file_get_contents($cache);
        $content = Filter::apply('cache:input', $content);
        $content = Filter::apply('cache:output', $content);
        echo $content;
        exit;
    }
    Weapon::add('shield_after', function($G) use($cache) {
        $G['data']['cache'] = $cache;
        File::write($G['data']['content'])->saveTo($cache);
        Weapon::fire('on_cache_construct', array($G, $G));
    });
    Shield::attach(SHIELD . DS . 'sitemap.php');
}, 80);


/**
 * RSS Feed
 * --------
 *
 * [1]. feed
 * [2]. feed/rss
 * [3]. feed/rss/1
 *
 */

Route::accept(array('(feed|feeds)', '(feed|feeds)/rss', '(feed|feeds)/rss/(:num)'), function($path = "", $offset = 1) use($config) {
    Config::set('offset', (int) $offset);
    HTTP::mime('text/xml', $config->charset);
    $q = ! empty($config->url_query) ? '.' . md5($config->url_query) : "";
    $cache = CACHE . DS . str_replace('/', '.', $config->url_path) . $q . '.cache';
    if(file_exists($cache)) {
        $content = file_get_contents($cache);
        $content = Filter::apply('cache:input', $content);
        $content = Filter::apply('cache:output', $content);
        echo $content;
        exit;
    }
    Weapon::add('shield_after', function($G) use($cache) {
        $G['data']['cache'] = $cache;
        File::write($G['data']['content'])->saveTo($cache);
        Weapon::fire('on_cache_construct', array($G, $G));
    });
    Shield::attach(SHIELD . DS . 'rss.php');
}, 81);


/**
 * JSON Feed
 * ---------
 *
 * [1]. feed/json
 * [2]. feed/json/1
 * [3]. feed/json?callback=theFunction
 * [4]. feed/json/1?callback=theFunction
 *
 */

Route::accept(array('(feed|feeds)/json', '(feed|feeds)/json/(:num)'), function($path = "", $offset = 1) use($config) {
    Config::set('offset', (int) $offset);
    HTTP::mime('application/json', $config->charset);
    $q = ! empty($config->url_query) ? '.' . md5($config->url_query) : "";
    $cache = CACHE . DS . str_replace('/', '.', $config->url_path) . $q . '.cache';
    if(file_exists($cache)) {
        $content = file_get_contents($cache);
        $content = Filter::apply('cache:input', $content);
        $content = Filter::apply('cache:output', $content);
        echo $content;
        exit;
    }
    Weapon::add('shield_after', function($G) use($cache) {
        $G['data']['cache'] = $cache;
        File::write($G['data']['content'])->saveTo($cache);
        Weapon::fire('on_cache_construct', array($G, $G));
    });
    Shield::attach(SHIELD . DS . 'json.php');
}, 82);


/**
 * Captcha Image
 * -------------
 *
 * [1]. captcha.png
 *
 */

Route::accept('captcha.png', function() {
    HTTP::mime('image/png')->header(array(
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Cache-Control' => 'post-check=0, pre-check=0',
        'Pragma' => 'no-cache'
    ));
    $str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $bg = Request::get('bg', '333333', false);
    $color = Request::get('color', 'FFFFAA', false);
    $padding = Request::get('padding', 0);
    $width = Request::get('width', 100) + ($padding * 2);
    $height = Request::get('height', 30) + ($padding * 2);
    $size = Request::get('size', 16);
    $length = Request::get('length', 7);
    $font = Request::get('font', 'special-elite-regular.ttf', false);
    $text = substr(str_shuffle($str), 0, $length);
    Session::set(Guardian::$captcha, $text);
    if($bg !== 'false' && $bg = Converter::HEX2RGB($bg)) {
        $bg = array($bg['r'], $bg['g'], $bg['b'], $bg['a']);
    } else {
        $bg = $bg !== 'false' ? array(51, 51, 51, 1) : array(0, 0, 0, 0);
    }
    if($color = Converter::HEX2RGB($color)) {
        $color = array($color['r'], $color['g'], $color['b'], $color['a']);
    } else {
        $color = array(255, 255, 170, 1);
    }
    $image = imagecreatetruecolor($width, $height);
    $font = strpos($font, '/') === false ? ASSET . DS . '__captcha' . DS . $font : ROOT . DS . File::path($font);
    imagefill($image, 0, 0, 0x7fff0000);
    imagealphablending($image, true);
    imagesavealpha($image, true);
    $bg = imagecolorallocatealpha($image, $bg[0], $bg[1], $bg[2], 127 - ($bg[3] * 127));
    $color = imagecolorallocatealpha($image, $color[0], $color[1], $color[2], 127 - ($color[3] * 127));
    imagefilledrectangle($image, 0, 0, $width, $height, $bg);
    // center the image text ...
    $xi = imagesx($image);
    $yi = imagesy($image);
    $box = imagettfbbox($size, 0, $font, $text);
    $xr = abs(max($box[2], $box[4]));
    $yr = abs(max($box[5], $box[7]));
    $x = intval(($xi - $xr) / 2);
    $y = intval(($yi + $yr) / 2);
    imagettftext($image, $size, 0, $x, $y, $color, $font, $text);
    imagepng($image);
    imagedestroy($image);
    exit;
}, 90);


/**
 * Static Page
 * -----------
 *
 * [1]. page-slug
 *
 */

Route::accept('(:any)', function($slug = "") use($config) {
    if( ! $page = Get::page($slug)) {
        Shield::abort('404-page');
    }
    if(isset($_GET['repair']) && Guardian::happy()) {
        Guardian::kick($config->manager->slug . '/page/repair/id:' . $page->id);
    }
    if($page->state === 'draft') {
        Shield::abort('404-page');
    }
    Filter::add('pager:url', function($url) {
        return Filter::apply('page:url', $url);
    });
    Config::set(array(
        'page_title' => $page->title . $config->title_separator . $config->title,
        'page' => $page
    ));
    Weapon::add('shell_after', function() use($page) {
        if(isset($page->css) && trim($page->css) !== "") echo O_BEGIN . $page->css . O_END;
    });
    Weapon::add('sword_after', function() use($page) {
        if(isset($page->js) && trim($page->js) !== "") echo O_BEGIN . $page->js . O_END;
    });
    Shield::attach('page-' . $slug);
}, 100);


/**
 * Home Page
 * ---------
 *
 * [1]. /
 *
 */

Route::accept('/', function() use($config, $excludes) {
    Session::kill('search.query');
    Session::kill('search.results');
    if($files = Mecha::eat(Get::articles())->chunk(1, $config->index->per_page)->vomit()) {
        $articles = array();
        foreach($files as $file) {
            $articles[] = Get::article($file, $excludes);
        }
        unset($files);
    } else {
        $articles = false;
    }
    Filter::add('pager:url', function($url) {
        return Filter::apply('index:url', $url);
    });
    Config::set(array(
        'articles' => $articles,
        'pagination' => Navigator::extract(Get::articles(), 1, $config->index->per_page, $config->index->slug)
    ));
    Shield::attach('page-home');
}, 110);


/**
 * Route Hook: after
 * -----------------
 */

Weapon::fire('route_after');


/**
 * Do Routing
 * ----------
 */

Route::execute();


/**
 * 404 Page
 * --------
 *
 * Fallback to 404 page if nothing matched.
 *
 */

Shield::abort();