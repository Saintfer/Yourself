<?php
/**
 * ───────────────────────────────────────────────
 *  Yourself · Configuración (lee desde .env)
 * ───────────────────────────────────────────────
 *  Las claves sensibles (API keys, contraseñas) viven en /.env
 *  que NO se sube a Git. Aquí solo se leen.
 *
 *  Si alguna variable falta, se intenta usar getenv() del sistema.
 */

if (!function_exists('cargarEnv')) {
    /**
     * Lee un archivo .env muy simple (KEY=VALUE por línea).
     * Ignora líneas vacías y comentarios (#).
     * Las variables se cargan en $_ENV y getenv().
     */
    function cargarEnv(string $rutaEnv): void {
        if (!is_readable($rutaEnv)) {
            return;
        }
        $lineas = file($rutaEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea === '' || $linea[0] === '#') {
                continue;
            }
            if (!str_contains($linea, '=')) {
                continue;
            }
            [$clave, $valor] = explode('=', $linea, 2);
            $clave = trim($clave);
            $valor = trim($valor);
            // Permitir valores con comillas
            if (
                (str_starts_with($valor, '"') && str_ends_with($valor, '"')) ||
                (str_starts_with($valor, "'") && str_ends_with($valor, "'"))
            ) {
                $valor = substr($valor, 1, -1);
            }
            if ($clave !== '' && getenv($clave) === false) {
                putenv("$clave=$valor");
                $_ENV[$clave] = $valor;
            }
        }
    }
}

if (!function_exists('env')) {
    /** Helper para leer una variable de entorno con valor por defecto. */
    function env(string $clave, $defecto = null) {
        $valor = $_ENV[$clave] ?? getenv($clave);
        return ($valor === false || $valor === null || $valor === '') ? $defecto : $valor;
    }
}

// Cargar el .env (un nivel arriba de /php)
cargarEnv(__DIR__ . '/../.env');

// ─── Proveedor de IA activo ───
// "groq" (Llama 3.3 70B, recomendado) o "gemini" (Google)
if (!defined('AI_PROVIDER')) {
    define('AI_PROVIDER', strtolower((string) env('AI_PROVIDER', 'groq')));
}
if (!defined('AI_TIMEOUT')) {
    define('AI_TIMEOUT', (int) env('AI_TIMEOUT', 25));
}

// ─── Groq (Llama 3.3 70B) ───
if (!defined('GROQ_API_KEY')) {
    define('GROQ_API_KEY', env('GROQ_API_KEY', ''));
}
if (!defined('GROQ_MODEL')) {
    define('GROQ_MODEL', env('GROQ_MODEL', 'llama-3.3-70b-versatile'));
}
if (!defined('GROQ_ENDPOINT')) {
    define('GROQ_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions');
}

// ─── Gemini (Google) ───
if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', env('GEMINI_API_KEY', ''));
}
if (!defined('GEMINI_MODEL')) {
    define('GEMINI_MODEL', env('GEMINI_MODEL', 'gemini-2.5-flash'));
}
if (!defined('GEMINI_TIMEOUT')) {
    define('GEMINI_TIMEOUT', (int) env('GEMINI_TIMEOUT', 20));
}
if (!defined('GEMINI_ENDPOINT')) {
    define(
        'GEMINI_ENDPOINT',
        'https://generativelanguage.googleapis.com/v1beta/models/'
        . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY
    );
}

// ─── Fecha en español ───
if (!function_exists('fechaEspanol')) {
    function fechaEspanol(int $timestamp = 0): string {
        if (!$timestamp) $timestamp = time();
        $dias   = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
        $meses  = ['enero','febrero','marzo','abril','mayo','junio',
                   'julio','agosto','septiembre','octubre','noviembre','diciembre'];
        return $dias[(int)date('w',$timestamp)] . ', ' .
               (int)date('j',$timestamp) . ' de ' .
               $meses[(int)date('n',$timestamp)-1] . ' de ' .
               date('Y',$timestamp);
    }
}
