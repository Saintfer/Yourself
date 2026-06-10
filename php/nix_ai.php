<?php
/**
 * ───────────────────────────────────────────────
 *  Nix · Asistente emocional (Gemini + fallback)
 * ───────────────────────────────────────────────
 *  - Usa Google Gemini (gemini-2.0-flash) si hay API key configurada.
 *  - Si la API falla o no hay key, responde con frases predefinidas (offline).
 *  - Garantía de seguridad: ante CUALQUIER detección de crisis,
 *    el mensaje del usuario o la respuesta de la IA se enriquecen con
 *    los teléfonos de ayuda (Línea 106 jóvenes / 123 emergencias).
 */

require_once __DIR__ . '/config.php';

define('NIX_SYSTEM_PROMPT',
    'Eres Nix 🦉, un compañero emocional cálido y cercano de la app "Yourself", ' .
    'pensada para jóvenes colombianos en etapa escolar (11-18 años). ' .
    'Háblales como un/a amigo/a mayor sabio/a: con cariño, sin sermones, sin sonar a robot. ' .
    '' .
    'TU ESTILO: ' .
    '1) VALIDA primero la emoción ("Entiendo que…", "Tiene mucho sentido que te sientas así"). ' .
    '2) Luego puedes ofrecer UN CONSEJO concreto, una técnica útil, una idea para hoy ' .
    '   (ej. respiración 4-7-8, escribir 3 cosas, salir a caminar 10 min, hablar con alguien de confianza). ' .
    '3) Cierra con UNA sola pregunta abierta o con una invitación a seguir contando. ' .
    'No te limites a "¿quieres contarme más?" todo el tiempo: arriésgate a sugerir, recordar, opinar con cariño. ' .
    '' .
    'MEMORIA: tienes el historial de la conversación. ÚSALO de verdad: ' .
    '"Antes me contaste que…", "¿Cómo sigues con lo de tu mamá?", "Recuerdo que el examen era hoy, ¿cómo te fue?". ' .
    'Si el usuario te saluda y ya hubo conversación previa, retómala explícitamente. ' .
    '' .
    'TONO: español colombiano natural, cálido, sin exceso de formalidad. ' .
    'Largo: 3-5 oraciones (más si hace falta para dar un consejo bien explicado). ' .
    'Emojis: 1-2 por mensaje, con sentido (💜🌙🦉😊🌿). ' .
    '' .
    'LÍMITES: no eres psicólogo/a, no diagnostiques, no recetes medicamentos. ' .
    'Si la situación parece grave o repetitiva, anima con tacto a hablar con un adulto de confianza ' .
    '(papás, profesor/a, orientador/a) o un profesional. ' .
    '' .
    '⚠️ PROTOCOLO DE CRISIS: si detectas señales de suicidio, autolesión, ' .
    'pensamientos de hacerse daño, abuso o riesgo inmediato, ' .
    'valida el dolor SIN juzgar y termina SIEMPRE tu mensaje con esta línea exacta: ' .
    '"📞 Si estás en peligro, llama YA a la Línea 106 (jóvenes) o 123 (emergencias). No estás solo/a."'
);

// Línea de ayuda que siempre se debe mostrar en crisis
define('NIX_LINEA_CRISIS',
    "\n\n📞 Si estás en peligro, llama YA a la Línea 106 (jóvenes) o 123 (emergencias). No estás solo/a."
);

$NIX_FALLBACK = [
    'crisis'   => [
        "Lo que describes me preocupa mucho y quiero que estés a salvo 💜 Por favor habla con un adulto de confianza ahora.",
        "Tu vida importa y mereces apoyo real. ¿Hay un adulto cerca con quien puedas hablar en este momento?",
    ],
    'tristeza' => [
        "Entiendo que estás pasando por un momento difícil 💜 Está bien sentirse triste. ¿Quieres contarme qué pasó?",
        "La tristeza es una emoción válida y normal. No tienes que afrontarla solo/a. Estoy aquí 🌙",
        "Gracias por compartir eso conmigo. La tristeza duele, pero también pasa. ¿Qué necesitas ahora mismo?",
    ],
    'ansiedad' => [
        "Entiendo que sientes mucha presión 😮‍💨 Respira: inhala 4 seg, mantén 4, exhala 4. ¿Pudiste hacerlo?",
        "El estrés puede abrumarnos. Un paso a la vez es suficiente. ¿Qué es lo más urgente para hoy?",
        "La ansiedad es real y agotadora. Enfócate en lo que sí puedes controlar ahora 💪",
    ],
    'ira'      => [
        "Entiendo que estás enojado/a, y esa emoción es completamente válida 🔥 ¿Qué fue lo que pasó?",
        "El enojo dice que algo importante fue afectado. ¿Qué te lastimó o frustró?",
    ],
    'alegria'  => [
        "¡Me alegra mucho escuchar eso! 🌟 La felicidad merece celebrarse. ¿Qué fue lo que hizo especial hoy?",
        "¡Qué buenas noticias! ✨ Cuéntame más, ¿qué lo causó?",
    ],
    'soledad'  => [
        "La soledad duele de verdad. Pero no estás completamente solo/a: aquí estoy yo 💜 ¿Qué está pasando?",
        "¿Hay alguien de confianza con quien puedas hablar hoy?",
    ],
    'familia'  => [
        "Las situaciones familiares pueden ser muy complejas. ¿Quieres contarme qué está pasando?",
        "Los conflictos en casa son muy difíciles. Buscar apoyo es una señal de valentía 💪",
    ],
    'estudio'  => [
        "El estrés académico es real y válido. ¿Qué materia o situación te está preocupando más?",
        "Todos tenemos momentos en que el estudio se siente imposible. ¿Has podido descansar bien? 📚",
    ],
    'saludo'   => [
        "¡Hola! Soy Nix 🦉 Me alegra que hayas venido. ¿Cómo te sientes hoy?",
        "¡Qué bueno verte aquí! 😊 Este es tu espacio seguro. Cuéntame, ¿cómo estuvo tu día?",
    ],
    'default'  => [
        "Gracias por contarme eso. ¿Cómo te hace sentir esa situación? 🦉",
        "Estoy aquí para escucharte sin juzgarte. ¿Hay algo más que quieras compartir?",
        "Tus sentimientos son válidos. ¿Qué necesitas en este momento?",
        "No tienes que estar solo/a con esto. Estoy escuchando 💜",
        "¿Qué crees que te ayudaría a sentirte mejor en este momento?",
    ],
];

$NIX_PALABRAS = [
    'crisis'   => [
        'morir','muerte','suicidio','suicidarme','suicidar','hacerme daño','no quiero vivir',
        'quitarme la vida','quiero desaparecer','lastimarme','cortarme','matarme','matar a',
        'me quiero matar','no quiero estar','no aguanto más','acabar con todo','sin salida'
    ],
    'tristeza' => ['triste','tristeza','llorar','lloro','lloré','deprimido','deprimida','mal','muy mal','horrible','vacío','vacía','sin ganas'],
    'ansiedad' => ['ansioso','ansiosa','ansiedad','estresado','estresada','estrés','stress','nervioso','nerviosa','agobiado','preocupado','miedo','pánico','angustia'],
    'ira'      => ['enojado','enojada','furioso','furiosa','rabia','odio','molesto','molesta','harto','harta','frustrado','frustrada'],
    'alegria'  => ['bien','feliz','felicidad','alegre','genial','excelente','increíble','emocionado','contento','maravilloso'],
    'soledad'  => ['solo','sola','soledad','nadie','sin amigos','aislado','aislada','ignorado','excluido'],
    'familia'  => ['mamá','papá','padres','familia','casa','hermano','hermana','mis papás'],
    'estudio'  => ['colegio','escuela','estudiar','examen','nota','tarea','profesor','clase','reprobé','perdí el año'],
    'saludo'   => ['hola','buenas','hey','buenos días','buenas noches','qué tal'],
];

/** ¿El texto contiene alguna palabra de crisis? */
function nixEsCrisis(string $texto): bool {
    global $NIX_PALABRAS;
    $t = mb_strtolower($texto);
    foreach ($NIX_PALABRAS['crisis'] as $p) {
        if (str_contains($t, $p)) return true;
    }
    return false;
}

/**
 * Asegura que la línea de crisis aparezca al final de la respuesta.
 * Si la IA ya la incluyó (cualquier mención a "Línea 106" o "123"),
 * no se duplica.
 */
function nixAgregarLineaCrisis(string $respuesta): string {
    $r = mb_strtolower($respuesta);
    if (str_contains($r, '106') && str_contains($r, '123')) {
        return $respuesta;
    }
    return rtrim($respuesta) . NIX_LINEA_CRISIS;
}

/** Respuesta offline: usada cuando no hay API key o la API falla. */
function nixFallback(string $texto): string {
    global $NIX_FALLBACK, $NIX_PALABRAS;
    $t = mb_strtolower($texto);

    if (nixEsCrisis($texto)) {
        $msg = $NIX_FALLBACK['crisis'][array_rand($NIX_FALLBACK['crisis'])];
        return nixAgregarLineaCrisis($msg);
    }

    foreach ($NIX_PALABRAS as $cat => $pals) {
        if ($cat === 'crisis') continue;
        foreach ($pals as $p) {
            if (str_contains($t, $p)) {
                return $NIX_FALLBACK[$cat][array_rand($NIX_FALLBACK[$cat])];
            }
        }
    }
    return $NIX_FALLBACK['default'][array_rand($NIX_FALLBACK['default'])];
}

/* ─────────────────────────────────────────────
 * Función principal: nixResponder()
 *
 *  Selecciona el proveedor de IA según AI_PROVIDER (.env):
 *    - "groq"   → Llama 3.3 70B vía Groq Cloud  (recomendado, gratis)
 *    - "gemini" → Google Gemini 2.5 Flash       (alternativa, gratis)
 *
 *  Si el proveedor activo no tiene API key, intenta el otro.
 *  Si los dos fallan, usa nixFallback() (respuestas predefinidas).
 *  Si el mensaje del usuario es crisis, garantiza la línea de ayuda.
 * ───────────────────────────────────────────── */

function nixResponder(string $mensaje, array $historial = []): string {
    $crisis = nixEsCrisis($mensaje);

    $proveedor = defined('AI_PROVIDER') ? AI_PROVIDER : 'groq';
    $respuesta = '';

    // Intento 1: el proveedor configurado
    if ($proveedor === 'groq' && defined('GROQ_API_KEY') && GROQ_API_KEY !== '') {
        $respuesta = nixLlamarGroq($mensaje, $historial);
    } elseif ($proveedor === 'gemini' && defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') {
        $respuesta = nixLlamarGemini($mensaje, $historial);
    }

    // Intento 2: el otro proveedor como respaldo
    if ($respuesta === '') {
        if ($proveedor !== 'groq' && defined('GROQ_API_KEY') && GROQ_API_KEY !== '') {
            $respuesta = nixLlamarGroq($mensaje, $historial);
        } elseif ($proveedor !== 'gemini' && defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') {
            $respuesta = nixLlamarGemini($mensaje, $historial);
        }
    }

    // Intento 3: respuestas offline predefinidas
    if ($respuesta === '') {
        $respuesta = nixFallback($mensaje);
    }

    return $crisis ? nixAgregarLineaCrisis($respuesta) : $respuesta;
}

/**
 * Llama a Groq Cloud (formato OpenAI-compatible).
 * Devuelve '' si falla — el dispatcher principal probará otra opción.
 */
function nixLlamarGroq(string $mensaje, array $historial = []): string {
    $messages = [
        ['role' => 'system', 'content' => NIX_SYSTEM_PROMPT],
    ];

    // Últimos 30 turnos para mantener memoria
    $historial_reciente = array_slice($historial, -30);
    foreach ($historial_reciente as $msg) {
        $messages[] = [
            'role'    => $msg['rol'] === 'user' ? 'user' : 'assistant',
            'content' => $msg['mensaje'],
        ];
    }
    $messages[] = ['role' => 'user', 'content' => $mensaje];

    $body = json_encode([
        'model'       => GROQ_MODEL,
        'messages'    => $messages,
        'temperature' => 0.95,
        'max_tokens'  => 600,
        'top_p'       => 0.95,
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init(GROQ_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => AI_TIMEOUT,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GROQ_API_KEY,
        ],
    ]);

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $http_code !== 200) {
        error_log("Groq API error (HTTP $http_code): $response");
        return '';
    }

    $data  = json_decode($response, true);
    $texto = $data['choices'][0]['message']['content'] ?? '';
    return trim((string) $texto);
}

/**
 * Llama a Google Gemini.
 * Devuelve '' si falla — el dispatcher principal probará otra opción.
 */
function nixLlamarGemini(string $mensaje, array $historial = []): string {
    $contents = [];

    $historial_reciente = array_slice($historial, -30);
    foreach ($historial_reciente as $msg) {
        $role = $msg['rol'] === 'user' ? 'user' : 'model';
        $contents[] = [
            'role'  => $role,
            'parts' => [['text' => $msg['mensaje']]],
        ];
    }
    $contents[] = [
        'role'  => 'user',
        'parts' => [['text' => $mensaje]],
    ];

    $body = json_encode([
        'system_instruction' => ['parts' => [['text' => NIX_SYSTEM_PROMPT]]],
        'contents'           => $contents,
        'generationConfig'   => [
            'temperature'     => 0.95,
            'maxOutputTokens' => 600,
            'topP'            => 0.95,
        ],
        'safetySettings' => [
            ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_LOW_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ],
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init(GEMINI_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => GEMINI_TIMEOUT,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $http_code !== 200) {
        error_log("Gemini API error (HTTP $http_code): $response");
        return '';
    }

    $data  = json_decode($response, true);
    $texto = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    return trim((string) $texto);
}
