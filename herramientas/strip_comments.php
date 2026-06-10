<?php
/**
 * ───────────────────────────────────────────────
 *  Yourself · Utilidad de desarrollo (OPCIONAL)
 * ───────────────────────────────────────────────
 *  Elimina los comentarios de los archivos .php y .css del proyecto
 *  para entregar una versión "limpia". NO es necesaria para ejecutar
 *  la aplicación.
 *
 *  ⚠️ Hace cambios IRREVERSIBLES sobre los archivos. Haz una copia
 *     antes de usarla.
 *
 *  Uso (desde la terminal, dentro de la carpeta del proyecto):
 *      php herramientas/strip_comments.php
 *
 *  Detecta la raíz del proyecto automáticamente (un nivel arriba de
 *  esta carpeta), así funciona sin importar el nombre de la carpeta.
 */

function remove_comments(string $file): void {
    if (!file_exists($file)) return;
    $source = file_get_contents($file);
    $ext = pathinfo($file, PATHINFO_EXTENSION);

    if ($ext === 'php') {
        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } else {
                [$id, $text] = $token;
                if ($id === T_COMMENT || $id === T_DOC_COMMENT) continue;
                $output .= $text;
            }
        }
        file_put_contents($file, $output);
    } elseif ($ext === 'css') {
        $output = preg_replace('!/\*.*?\*/!s', '', $source);
        file_put_contents($file, $output);
    }
}

// Raíz del proyecto = carpeta que contiene a /herramientas
$raiz = dirname(__DIR__);

$archivos = array_merge(
    glob("$raiz/*.php"),
    glob("$raiz/pages/*.php"),
    glob("$raiz/php/*.php"),
    glob("$raiz/css/*.css")
);

foreach ($archivos as $f) {
    echo "Procesando " . $f . PHP_EOL;
    remove_comments($f);
}
echo "Listo." . PHP_EOL;
