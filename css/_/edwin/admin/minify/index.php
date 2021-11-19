<?php
  require_once 'config.php';
  require_once __LIB_DIR . 'minify.php';
?><!DOCTYPE html>
<html>
<head>
<title>Komprimieren von CSS, JS und Sprachdateien</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">

<?php if (!ConfigHelper::get('m_backend_live_mode')) { ?>
  <!-- CSS ressources -->
  <link rel="stylesheet" type="text/css" href="<?php echo __BACKEND_RELATIVE_THEME_DIR; ?>tps/css/bootstrap.min.css?v=<?php echo ConfigHelper::get('m_cache_resource_version'); ?>">

  <link rel="stylesheet" type="text/css" href="<?php echo __BACKEND_RELATIVE_THEME_DIR; ?>tps/css/jquery-ui.min.css?v=<?php echo ConfigHelper::get('m_cache_resource_version'); ?>">
  <link rel="stylesheet" type="text/css" href="<?php echo __BACKEND_RELATIVE_THEME_DIR; ?>tps/css/jquery-ui.theme.min.css?v=<?php echo ConfigHelper::get('m_cache_resource_version'); ?>">

  <link rel="stylesheet" type="text/css" href="<?php echo __BACKEND_RELATIVE_THEME_DIR; ?>tps/css/bootstrap-datetimepicker.min.css?v=<?php echo ConfigHelper::get('m_cache_resource_version'); ?>">

  <link rel="stylesheet" type="text/css" href="<?php echo __BACKEND_RELATIVE_THEME_DIR; ?>tps/css/magnific-popup.css?v=<?php echo ConfigHelper::get('m_cache_resource_version'); ?>">

  <link rel="stylesheet" href="<?php echo __BACKEND_RELATIVE_THEME_DIR; ?>tps/css/q2e-iconfont.css?v=<?php echo ConfigHelper::get('m_cache_resource_version'); ?>">

  <link rel="stylesheet/less" type="text/css" href="<?php echo __BACKEND_RELATIVE_THEME_DIR; ?>css/styles.less?v=<?php echo ConfigHelper::get('m_cache_resource_version'); ?>">

  <link rel="stylesheet/less" type="text/css" href="../../<?php echo custom_config_stylesheet_path(); ?>?v=<?php echo ConfigHelper::get('m_cache_resource_version'); ?>">

  <!-- JS ressources -->
  <script>
    less = {
      env: 'development',
      dumpLineNumbers : 'all' // enable for debugging line numbers
    };
  </script>
  <script src="<?php echo __BACKEND_RELATIVE_THEME_DIR; ?>tps/js/less.min.js?v=<?php echo ConfigHelper::get('m_cache_resource_version'); ?>"></script>

  <!-- base third party js -->
  <script src="<?php echo __BACKEND_RELATIVE_THEME_DIR; ?>tps/js/jquery.min.js?v=<?php echo ConfigHelper::get('m_cache_resource_version'); ?>"></script>
  <script src="<?php echo __BACKEND_RELATIVE_THEME_DIR; ?>tps/js/bootstrap.min.js?v=<?php echo ConfigHelper::get('m_cache_resource_version'); ?>"></script><!-- ./base third party js -->

  <!-- draggable, droppable for sorting & autocomplete
       @see http://jqueryui.com/download/#!version=1.11.4&components=1111110000100010000000000000000000000 -->
  <script src="<?php echo __BACKEND_RELATIVE_THEME_DIR; ?>tps/js/jquery-ui.min.js?v=<?php echo ConfigHelper::get('m_cache_resource_version'); ?>"></script><!-- ./draggable, droppable for sorting & autocomplete -->

  <!-- date and/or time picker -->
  <script src="<?php echo __BACKEND_RELATIVE_THEME_DIR; ?>tps/js/moment+locales.min.js?v=<?php echo ConfigHelper::get('m_cache_resource_version'); ?>"></script>
  <script src="<?php echo __BACKEND_RELATIVE_THEME_DIR; ?>tps/js/bootstrap-datetimepicker.min.js?v=<?php echo ConfigHelper::get('m_cache_resource_version'); ?>"></script><!-- ./date and/or time picker -->

  <!-- popup for overlays -->
  <script src="<?php echo __BACKEND_RELATIVE_THEME_DIR; ?>tps/js/jquery.magnific-popup.min.js?v=<?php echo ConfigHelper::get('m_cache_resource_version'); ?>"></script><!-- ./popup for overlays -->
<?php } else { ?>
  <link rel="stylesheet" type="text/css" href="<?php echo __BACKEND_RELATIVE_THEME_DIR; ?>tps/css/_tps.css?v=<?php echo ConfigHelper::get('m_cache_resource_version'); ?>">
  <link rel="stylesheet" type="text/css" href="<?php echo __BACKEND_RELATIVE_THEME_DIR; ?>css/_styles.css?v=<?php echo ConfigHelper::get('m_cache_resource_version'); ?>">
  <link rel="stylesheet" type="text/css" href="../../<?php echo custom_config_stylesheet_path(); ?>?v=<?php echo ConfigHelper::get('m_cache_resource_version'); ?>">
  <script src="<?php echo __BACKEND_RELATIVE_THEME_DIR; ?>tps/js/_tps.js?v=<?php echo ConfigHelper::get('m_cache_resource_version'); ?>"></script>
<?php } ?>

<style type="text/css">
  ul { list-style: circle outside none; margin-left: 10px; }
  .output { background-color:#FF8649; }
  input { cursor:pointer; }
</style>
</head>
<body>
<div class="container ed_top">
  <div class="row ed_top_bar">
    <div class="col-xs-6">
      <span class="item">Komprimieren von CSS, JS und Sprachdateien</span>
    </div>
    <div class="col-xs-6 text-right"></div>
  </div>
  <div class="row ed_top_nav">
    <div class="pull-left">
      <div class="logo">
        <a href="index.php">
          <img src="<?php echo __BACKEND_RELATIVE_THEME_DIR; ?>pix/logo.png" alt="" title="" />
        </a>
      </div>
    </div>
    <div class="pull-left"></div>
  </div>
</div>
<div class="container ed_main">
  <div class="row">
    <div class="col-xs-9">
      <div class="ed_content_top"></div>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h2 class="panel-title">
            Komprimieren von Sprachdateien <small>Komprimieren von Sprachdateien am Backend und Frontend</small>
          </h2>
        </div>
        <div class="panel-body">
          <a href="../../manage_stuff.php?do=compress_lang&site=1" target="_blank">Sprachdateien komprimieren</a>
        </div>
      </div>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h2 class="panel-title">
            Komprimieren von CSS und JS Files
          </h2>
        </div>
        <div class="panel-body form">
          <div class="well well-sm">
            <h4>Unterschiedliche Komprimierungsarten</h4>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" accept-charset="UTF-8" name="m_minify_form" id="m_minify_form">
              <input type="hidden" name="minify" id="minify" value="">
              <input type="button" name="css-yui-less" value="CSS/LESS" class="display_minify_cssless btn btn-primary" />
              <input type="button" name="css" value="CSS/CSSMIN" class="display_minify_cssmin btn btn-primary" />
              <input type="button" name="js" value="JS/JSMIN" class="display_minify_jsmin btn btn-primary"/>
              <input type="button" name="css-yui" value="CSS/YUICOMPRESSOR" class="display_minify_cssyuicompressor btn btn-primary"/>
              <input type="button" name="js-yui" value="JS/YUICOMPRESSOR" class="display_minify_jsyuicompressor btn btn-primary"/>
              <input type="button" name="js-jshrink" value="JS/JSHRINK" class="display_minify_jshrink btn btn-primary"/>
            </form>

            <?php
            if ($output) {
              ?>
              <h4>Bericht</h4>
              <div class="c_reg0 topbdr_ws no_margin_r clearleft">
                Ziel- und Quelldateien
              </div>
              <pre><?php echo $output; ?></pre>
            <?php
            }
            ?>
          </div>
          <div class="well well-sm">
            <h4>Allgemein</h4>
            <p>Information und Anleitung</p>
            <p>
              Dieses Skript kann dazu verwendet werden, am Frontend verwendete Javascript
              und CSS Dateien zusammenzufassen und zu komprimieren.
              CSS Dateien werden zus&auml;tzlich vor dem Zusammenfassen noch kompiliert.
              Dabei wird vorhandene LESS Syntax in CSS umgewandelt.
              <br/><br/>
              Verwendet werden folgende Bibliotheken:<br/>
            </p>
            <ul>
              <li>lessphp (http://leafo.net/lessphp)</li>
              <li>cssmin ( http://code.google.com/p/cssmin/ )</li>
              <li>jsmin ( https://github.com/rgrove/jsmin-php/ )</li>
              <li>yuicompressor / minify ( http://code.google.com/p/cssmin/ )</li>
            </ul>
            <p>
              In der <code>config.php</code> Konfigurationsdatei muss f&uuml;r
              jedes verwendete Tool ein korrekter Eintrag angelegt werden
              ( siehe unten ).
            </p>
          </div>
          <div class="well well-sm">
            <h4>Konfiguration</h4>
            <p>Allgemeine Konfigurationshinweise</p>
            <p>
              <b>Beispiel</b><br/><br/>
              Das Beispiel hier zeigt die einfache Konfiguration der 'actions',
              d.h. der Zieldateien und Quelldateien je Zieldatei.
              Dieser Teil der Konfiguration ist für alle Typen gleich.
            </p>
            <pre>
$_CONFIG['m_minify']['css'] = array(
    'actions' => array(
        __SITE_PATH . 'css/_website.css' => array(
          __SITE_PATH . 'css/main_0.css',
          __SITE_PATH . 'css/contenttypes_0.css',
          __SITE_PATH . 'css/just_another_stylesheet.css',
        )
    )
);</pre>
          </div>
          <div class="well well-sm">
            <h4>CSS</h4>
            <p>cssmin / yuicompressor / less</p>
            <p>
              Um CSS Files kombinieren und komprimieren zu können, kann sowohl cssmin als auch YUICompressor verwendet werden.
              YUICompressor kann nicht in allen Entwicklungsumgebungen verwendet werden, weshalb als Standard CSSMIN verwendet werden sollte.
              <br/><br/>
              Bei der Verwendung von <b>LESS</b> Dateien <code>*.less</code> muss die Methode <i>CSS/LESS</i> verwendet werden. Dabei werden alle <code>*.less</code> Dateien zuerst kompiliert und anschließend durch YUICompressor CSS komprimiert.<br/>
              Die Wahlt fiel dabei auf den YUICompressur, da CSSMIN aus noch unbekannten Gründen zu Problemen mit <code>font-awesome.css</code> führt.
              <br/><br/>
              Beispielkonfiguration im <code>config.php</code> File.
            </p>
          </div>
          <div class="well well-sm">
            <h4>JS</h4>
            <p>jsmin / yuicompressor</p>
            <p>
              Um JS Files kombinieren und komprimieren zu können, kann sowohl jsmin als auch YUICompressor verwendet werden.
              YUICompressor kann nicht in allen Entwicklungsumgebungen verwendet werden, weshalb als Standard JSMIN verwendet werden sollte.
              <br/><br/>
              Beispielkonfiguration im <code>config.php</code> File.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
$(document).ready(function () {
  $form = $("#m_minify_form");
  $buttons = $("#m_minify_form input[type=button]");
  $hidden = $("#m_minify_form input[name=minify]");
  $buttons.click(function () {
    $hidden.attr("value", $(this).attr("name"));
    $form.submit();
  });
});
</script>
</body>
</html>
