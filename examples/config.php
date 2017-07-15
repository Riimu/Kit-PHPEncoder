<?php

/* This example shows how to set up a simple page that asks user for some
   configuration options, such as a database configuration, and stores that into
   a PHP file, which can, for example, later be used in an application. */

require_once __DIR__ . '/../vendor/autoload.php';

$configFile = __DIR__ . '/dbconf.php';
$config = [
    'hostname' => '',
    'database' => '',
    'username' => '',
    'password' => '',
];

if (isset($_POST['config'])) {
    $store = array_intersect_key($_POST['config'], $config);
    $encoder = new \Riimu\Kit\PHPEncoder\PHPEncoder();

    file_put_contents($configFile, sprintf(
        '<?php return %s;' . PHP_EOL,
        $encoder->encode(array_map('strval', $store))
    ));
}

if (file_exists($configFile)) {
    $config = (require $configFile) + $config;
}

?>
<!DOCTYPE html>
<html>
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>Database configuration</title>
 </head>
 <body>
  <h1>Database configuration</h1>
<?php

if (isset($_POST['config'])) {
    echo '  <p>Configuration saved!</p>' . PHP_EOL;
}

?>
  <form method="post">
   <table>
<?php

foreach ($config as $name => $value) {
    printf(
        '    <tr><td>%s</td><td><input type="text" name="config[%s]" value="%s" /></td></tr>' . PHP_EOL,
        ucfirst($name),
        $name,
        htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')
    );
}

?>
   </table>
   <div><input type="submit" value="Save Configuration" /></div>
  </form>
 </body>
</html>
