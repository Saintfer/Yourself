<?php
/**
 * Utilidad de desarrollo (OPCIONAL): elimina comentarios y líneas
 * vacías del archivo .sql. No es necesaria para ejecutar la app.
 *
 * Uso:  php herramientas/strip_sql.php
 */
$raiz = dirname(__DIR__);
$f = "$raiz/database/yourself_db.sql";
if (!file_exists($f)) { exit("No se encontró $f\n"); }
$c = file_get_contents($f);
$c = preg_replace('/--.*/', '', $c);
$c = preg_replace('/^\s*[\r\n]/m', '', $c);
file_put_contents($f, $c);
echo "SQL limpiado: $f\n";
