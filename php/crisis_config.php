<?php
/**
 * ───────────────────────────────────────────────
 *  Yourself · Configuración de Recursos de Crisis
 * ───────────────────────────────────────────────
 *  Archivo centralizado para TODOS los recursos de
 *  ayuda profesional y salud mental.
 *
 *  ► Para agregar o modificar recursos, editar SOLO
 *    este archivo. No se requiere cambiar código en
 *    index.php, chat.php ni en JavaScript.
 *
 *  ► Los recursos se exportan al frontend mediante
 *    la función getCrisisResourcesJSON().
 */

if (!defined('YOURSELF_CRISIS_CONFIG_LOADED')) {
    define('YOURSELF_CRISIS_CONFIG_LOADED', true);

    // ─────────────────────────────────────────────
    //  BOTONES PRINCIPALES (Sección "Ayuda profesional")
    // ─────────────────────────────────────────────
    //  URLs configurables para los dos botones de acción.
    //  Cambiarlas aquí actualiza automáticamente index.php y chat.php.

    define('YOURSELF_HELP_LINKS', [
        'buscar_profesional' => [
            'label' => 'Buscar ayuda profesional',
            'url'   => 'https://www.doctoralia.co/psicologo',
            'icon'  => '🩺',
        ],
        'recursos_salud'     => [
            'label' => 'Recursos de salud mental',
            'url'   => '#recursos-salud-mental',
            'icon'  => '📚',
        ],
    ]);

    // ─────────────────────────────────────────────
    //  RECURSOS DE APOYO POR CATEGORÍA
    // ─────────────────────────────────────────────
    //  Cada categoría contiene un array de ítems.
    //  Tipos soportados:
    //    'tel' → se abre como tel: (llamada telefónica)
    //    'url' → se abre como enlace externo
    //    'info'→ solo muestra la información, sin enlace

    define('YOURSELF_CRISIS_RESOURCES', [

        // ── 1. Líneas de atención en salud mental ──
        [
            'icon'     => '📞',
            'category' => 'Líneas de atención en salud mental',
            'items'    => [
                [
                    'name'  => 'Línea 106 — Bienestar Juvenil',
                    'desc'  => 'Atención gratuita para niños, niñas y adolescentes. Disponible 24/7.',
                    'value' => '106',
                    'type'  => 'tel',
                ],
                [
                    'name'  => 'Línea Nacional 123',
                    'desc'  => 'Línea de emergencias general de Colombia.',
                    'value' => '123',
                    'type'  => 'tel',
                ],
                [
                    'name'  => 'Línea 141 — ICBF',
                    'desc'  => 'Instituto Colombiano de Bienestar Familiar. Protección a menores.',
                    'value' => '141',
                    'type'  => 'tel',
                ],
                [
                    'name'  => 'Línea Psicoactiva 01 8000 112 439',
                    'desc'  => 'Orientación y consejería en salud mental y consumo de sustancias.',
                    'value' => '018000112439',
                    'type'  => 'tel',
                ],
            ],
        ],

        // ── 2. Centros de apoyo psicológico ──
        [
            'icon'     => '🏥',
            'category' => 'Centros de apoyo psicológico',
            'items'    => [
                [
                    'name'  => 'Secretaría de Salud — Puntos de atención',
                    'desc'  => 'Centros de salud municipales con servicios de psicología gratuita.',
                    'value' => 'https://www.minsalud.gov.co',
                    'type'  => 'url',
                ],
                [
                    'name'  => 'Universidades con consultorios psicológicos',
                    'desc'  => 'Muchas universidades ofrecen atención psicológica gratuita o de bajo costo.',
                    'value' => 'https://www.mineducacion.gov.co',
                    'type'  => 'url',
                ],
            ],
        ],

        // ── 3. Servicios de emergencia ──
        [
            'icon'     => '🚨',
            'category' => 'Servicios de emergencia',
            'items'    => [
                [
                    'name'  => 'Policía Nacional — 112',
                    'desc'  => 'Número único de emergencias (policía, bomberos, ambulancia).',
                    'value' => '112',
                    'type'  => 'tel',
                ],
                [
                    'name'  => 'Cruz Roja Colombiana',
                    'desc'  => 'Primeros auxilios y atención en emergencias.',
                    'value' => 'https://www.cruzrojacolombiana.org',
                    'type'  => 'url',
                ],
                [
                    'name'  => 'Bomberos — 119',
                    'desc'  => 'Emergencias con riesgo de vida.',
                    'value' => '119',
                    'type'  => 'tel',
                ],
            ],
        ],

        // ── 4. Prevención del suicidio ──
        [
            'icon'     => '💜',
            'category' => 'Organizaciones de prevención del suicidio',
            'items'    => [
                [
                    'name'  => 'Fundación Sergio Urrego',
                    'desc'  => 'Prevención del suicidio y discriminación en jóvenes LGBTIQ+.',
                    'value' => 'https://fundacionsergiourrego.org',
                    'type'  => 'url',
                ],
                [
                    'name'  => 'Asociación Colombiana de Psiquiatría',
                    'desc'  => 'Recursos y orientación en prevención del suicidio.',
                    'value' => 'https://psiquiatria.org.co',
                    'type'  => 'url',
                ],
                [
                    'name'  => 'Línea 106 — También para crisis suicida',
                    'desc'  => 'Línea de atención especializada en crisis emocionales.',
                    'value' => '106',
                    'type'  => 'tel',
                ],
            ],
        ],

        // ── 5. Plataformas de ayuda emocional ──
        [
            'icon'     => '🌐',
            'category' => 'Plataformas de ayuda emocional',
            'items'    => [
                [
                    'name'  => 'Te Protejo',
                    'desc'  => 'Plataforma de protección a menores en línea.',
                    'value' => 'https://www.teprotejo.org',
                    'type'  => 'url',
                ],
                [
                    'name'  => 'Safe Space (UNICEF)',
                    'desc'  => 'Herramientas de bienestar emocional para adolescentes.',
                    'value' => 'https://www.unicef.org/colombia',
                    'type'  => 'url',
                ],
                [
                    'name'  => 'Aquí Estoy — App de apoyo emocional',
                    'desc'  => 'Chat anónimo con voluntarios capacitados.',
                    'value' => 'https://aqui-estoy.org',
                    'type'  => 'url',
                ],
            ],
        ],

        // ── 6. Directorios de psicólogos ──
        [
            'icon'     => '📋',
            'category' => 'Directorios de psicólogos',
            'items'    => [
                [
                    'name'  => 'Doctoralia — Psicólogos en Colombia',
                    'desc'  => 'Directorio con perfiles, reseñas y citas online.',
                    'value' => 'https://www.doctoralia.co/psicologia',
                    'type'  => 'url',
                ],
                [
                    'name'  => 'Colegio Colombiano de Psicólogos',
                    'desc'  => 'Directorio oficial de profesionales certificados.',
                    'value' => 'https://www.colpsic.org.co',
                    'type'  => 'url',
                ],
                [
                    'name'  => 'Terapify — Terapia en línea',
                    'desc'  => 'Plataforma de psicología online con sesiones accesibles.',
                    'value' => 'https://www.terapify.com',
                    'type'  => 'url',
                ],
            ],
        ],
    ]);

    // ─────────────────────────────────────────────
    //  PATRONES DE DETECCIÓN DE CRISIS
    // ─────────────────────────────────────────────
    //  Desacoplados del módulo de IA (nix_ai.php).
    //  El motor de detección (crisis_detector.php) y el
    //  JavaScript usan EXCLUSIVAMENTE estos patrones.
    //
    //  Niveles:
    //    'critical' → riesgo alto, activar protocolo completo
    //    'warning'  → señales de alerta, mostrar recursos

    define('YOURSELF_CRISIS_KEYWORDS', [
        'critical' => [
            'suicidio', 'suicidarme', 'suicidar', 'matarme', 'me quiero matar',
            'quitarme la vida', 'quiero morir', 'quiero morirme',
            'no quiero vivir', 'no quiero existir', 'no quiero estar vivo',
            'acabar con todo', 'acabar con mi vida', 'terminar con todo',
            'hacerme daño', 'lastimarme', 'cortarme', 'autolesión',
            'autolesionarme', 'me quiero cortar', 'me voy a matar',
            'me voy a morir', 'quiero desaparecer para siempre',
            'ya no tiene sentido vivir', 'mejor me muero',
            'no aguanto más vivir', 'matar a', 'ojalá estuviera muerto',
            'ojalá estuviera muerta', 'me quiero hacer daño',
        ],
        'warning'  => [
            'no aguanto más', 'sin salida', 'no hay esperanza',
            'nadie me quiere', 'nadie me necesita', 'soy una carga',
            'todos estarían mejor sin mí', 'no le importo a nadie',
            'quiero desaparecer', 'no puedo más', 'estoy harto de todo',
            'estoy harta de todo', 'no tiene sentido',
            'nada vale la pena', 'ya nada importa',
            'me siento vacío', 'me siento vacía',
            'adiós para siempre', 'despedirme', 'esta es mi despedida',
            'perdón por todo', 'no me busquen',
            'nadie va a extrañarme', 'ya no soporto',
            'me rindo', 'ya no quiero seguir',
        ],
    ]);

    // ─────────────────────────────────────────────
    //  MENSAJE DE APOYO PARA CRISIS
    // ─────────────────────────────────────────────

    define('YOURSELF_CRISIS_SUPPORT_MSG',
        'Entiendo que estás pasando por un momento muy difícil. ' .
        'Lo que sientes es real y merece atención. ' .
        'No tienes que enfrentar esto solo/a — hay personas capacitadas ' .
        'que pueden ayudarte ahora mismo. 💜'
    );

    // ─────────────────────────────────────────────
    //  FUNCIONES HELPER
    // ─────────────────────────────────────────────

    /**
     * Exporta los recursos de crisis como JSON seguro para
     * inyectar en el frontend (atributo data-* o <script>).
     */
    function getCrisisResourcesJSON(): string {
        return json_encode([
            'help_links' => YOURSELF_HELP_LINKS,
            'resources'  => YOURSELF_CRISIS_RESOURCES,
            'keywords'   => YOURSELF_CRISIS_KEYWORDS,
            'support_msg'=> YOURSELF_CRISIS_SUPPORT_MSG,
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
    }

    /**
     * Devuelve el HTML de las categorías de recursos para renderizar
     * directamente en PHP (index.php, diario.php).
     */
    function renderCrisisResourcesHTML(): string {
        $html = '';
        foreach (YOURSELF_CRISIS_RESOURCES as $cat) {
            $html .= '<div class="resource-category">';
            $html .= '<div class="resource-category-header">';
            $html .= '<span class="resource-category-icon">' . $cat['icon'] . '</span>';
            $html .= '<h3 class="resource-category-title">' . htmlspecialchars($cat['category']) . '</h3>';
            $html .= '</div>';
            $html .= '<div class="resource-items">';
            foreach ($cat['items'] as $item) {
                $html .= '<div class="resource-item">';
                if ($item['type'] === 'tel') {
                    $html .= '<a href="tel:' . htmlspecialchars($item['value']) . '" class="resource-item-link" rel="noopener">';
                    $html .= '<span class="resource-item-icon">📞</span>';
                } elseif ($item['type'] === 'url') {
                    $html .= '<a href="' . htmlspecialchars($item['value']) . '" class="resource-item-link" target="_blank" rel="noopener noreferrer">';
                    $html .= '<span class="resource-item-icon">🔗</span>';
                } else {
                    $html .= '<div class="resource-item-link">';
                    $html .= '<span class="resource-item-icon">ℹ️</span>';
                }
                $html .= '<div class="resource-item-info">';
                $html .= '<div class="resource-item-name">' . htmlspecialchars($item['name']) . '</div>';
                $html .= '<div class="resource-item-desc">' . htmlspecialchars($item['desc']) . '</div>';
                $html .= '</div>';
                $html .= ($item['type'] === 'tel' || $item['type'] === 'url') ? '</a>' : '</div>';
                $html .= '</div>';
            }
            $html .= '</div></div>';
        }
        return $html;
    }
}
