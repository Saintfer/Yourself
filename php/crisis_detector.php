<?php
/**
 * ───────────────────────────────────────────────
 *  Yourself · Motor de Detección de Crisis
 * ───────────────────────────────────────────────
 *  Módulo INDEPENDIENTE del proveedor de IA.
 *  Analiza texto buscando patrones de crisis
 *  emocional y devuelve el nivel de riesgo.
 *
 *  Funciona sin conexión a Groq, Gemini ni ninguna
 *  otra API. Si se cambia de proveedor de IA, este
 *  módulo sigue funcionando exactamente igual.
 *
 *  Los patrones se leen de crisis_config.php
 *  (YOURSELF_CRISIS_KEYWORDS).
 */

require_once __DIR__ . '/crisis_config.php';

/**
 * Analiza un texto buscando patrones de crisis emocional.
 *
 * @param  string $texto  Mensaje del usuario a analizar.
 * @return array  [
 *     'is_crisis'        => bool,       // ¿Se detectó crisis?
 *     'level'            => string,     // 'none' | 'warning' | 'critical'
 *     'matched_patterns' => string[],   // Patrones que coincidieron
 * ]
 */
function detectarCrisis(string $texto): array {
    $resultado = [
        'is_crisis'        => false,
        'level'            => 'none',
        'matched_patterns' => [],
    ];

    if (trim($texto) === '') {
        return $resultado;
    }

    $texto_lower = mb_strtolower(trim($texto), 'UTF-8');

    // Normalizar caracteres acentuados comunes en variantes
    $texto_norm = strtr($texto_lower, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ü' => 'u', 'ñ' => 'ñ',
    ]);

    $keywords = YOURSELF_CRISIS_KEYWORDS;

    // ── Verificar nivel CRITICAL primero ──
    foreach ($keywords['critical'] as $pattern) {
        $pattern_lower = mb_strtolower($pattern, 'UTF-8');
        $pattern_norm  = strtr($pattern_lower, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'ñ',
        ]);

        if (mb_strpos($texto_lower, $pattern_lower) !== false ||
            mb_strpos($texto_norm, $pattern_norm)   !== false) {
            $resultado['matched_patterns'][] = $pattern;
        }
    }

    if (!empty($resultado['matched_patterns'])) {
        $resultado['is_crisis'] = true;
        $resultado['level']     = 'critical';
        return $resultado;
    }

    // ── Verificar nivel WARNING ──
    foreach ($keywords['warning'] as $pattern) {
        $pattern_lower = mb_strtolower($pattern, 'UTF-8');
        $pattern_norm  = strtr($pattern_lower, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'ñ',
        ]);

        if (mb_strpos($texto_lower, $pattern_lower) !== false ||
            mb_strpos($texto_norm, $pattern_norm)   !== false) {
            $resultado['matched_patterns'][] = $pattern;
        }
    }

    if (!empty($resultado['matched_patterns'])) {
        $resultado['is_crisis'] = true;
        $resultado['level']     = 'warning';
        return $resultado;
    }

    return $resultado;
}

/**
 * Genera un mensaje de apoyo adaptado al nivel de crisis.
 *
 * @param  string $level  'critical' o 'warning'
 * @return string Mensaje HTML-safe para mostrar en la interfaz.
 */
function getMensajeCrisis(string $level): string {
    if ($level === 'critical') {
        return 'Lo que describes me preocupa mucho y quiero que estés a salvo 💜 ' .
               'Por favor, habla con un adulto de confianza o llama a una línea de ayuda ahora. ' .
               'No estás solo/a y hay personas preparadas para ayudarte en este momento.';
    }

    // warning
    return 'Noto que estás pasando por un momento muy difícil. ' .
           'Quiero que sepas que lo que sientes es válido y que hay apoyo disponible para ti 💜 ' .
           'Si necesitas hablar con alguien, aquí tienes recursos de ayuda.';
}
