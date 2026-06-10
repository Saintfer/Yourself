/**
 * ───────────────────────────────────────────────
 *  Yourself · Crisis Detector (Frontend)
 * ───────────────────────────────────────────────
 *  Módulo JavaScript independiente para detección
 *  de crisis emocional en el chat.
 *
 *  - Analiza mensajes del usuario en tiempo real
 *  - Muestra modal de alerta prioritaria
 *  - Muestra botones de acción de emergencia
 *  - No depende de ninguna librería externa
 *
 *  Los patrones y recursos se inyectan desde PHP
 *  via el atributo data-crisis-config en el <body>.
 */

var YourselfCrisis = (function () {
  'use strict';

  // ── Estado interno ──
  var config = null;
  var modalVisible = false;
  var bannerVisible = false;

  /**
   * Inicializa el módulo. Debe llamarse tras el DOM ready.
   * Lee la configuración desde data-crisis-config del body.
   */
  function init() {
    var body = document.body;
    var raw = body.getAttribute('data-crisis-config');
    if (!raw) {
      console.warn('[YourselfCrisis] No se encontró data-crisis-config.');
      return;
    }
    try {
      config = JSON.parse(raw);
    } catch (e) {
      console.error('[YourselfCrisis] Error parsing config:', e);
      return;
    }
    injectModalHTML();
    injectBannerHTML();
    bindCloseEvents();
  }

  /**
   * Analiza un texto buscando patrones de crisis.
   * @param {string} text - Mensaje del usuario.
   * @returns {{ isCrisis: boolean, level: string, patterns: string[] }}
   */
  function analyze(text) {
    var result = { isCrisis: false, level: 'none', patterns: [] };
    if (!config || !config.keywords || !text.trim()) return result;

    var lower = text.toLowerCase();

    // Verificar critical primero
    var critical = config.keywords.critical || [];
    for (var i = 0; i < critical.length; i++) {
      if (lower.indexOf(critical[i].toLowerCase()) !== -1) {
        result.patterns.push(critical[i]);
      }
    }
    if (result.patterns.length > 0) {
      result.isCrisis = true;
      result.level = 'critical';
      return result;
    }

    // Verificar warning
    var warning = config.keywords.warning || [];
    for (var j = 0; j < warning.length; j++) {
      if (lower.indexOf(warning[j].toLowerCase()) !== -1) {
        result.patterns.push(warning[j]);
      }
    }
    if (result.patterns.length > 0) {
      result.isCrisis = true;
      result.level = 'warning';
      return result;
    }

    return result;
  }

  /**
   * Activa el protocolo de crisis: muestra el modal y el banner.
   * @param {string} level - 'critical' o 'warning'
   */
  function activateProtocol(level) {
    showModal(level);
    showBanner();

    // Destacar sección de ayuda profesional si existe (en index.php)
    var helpSection = document.getElementById('ayuda-profesional');
    if (helpSection) {
      helpSection.classList.add('crisis-highlighted');
    }
  }

  // ── Modal de Crisis ──

  function injectModalHTML() {
    if (document.getElementById('crisisModal')) return;

    var helpLinks = config.help_links || {};
    var profUrl = (helpLinks.buscar_profesional || {}).url || '#';

    var overlay = document.createElement('div');
    overlay.id = 'crisisModal';
    overlay.className = 'crisis-modal-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Alerta de crisis - Recursos de ayuda disponibles');
    overlay.style.display = 'none';

    overlay.innerHTML =
      '<div class="crisis-modal">' +
        '<div class="crisis-modal-header">' +
          '<div class="crisis-modal-pulse"></div>' +
          '<span class="crisis-modal-icon">💜</span>' +
          '<h2 class="crisis-modal-title">No estás solo/a</h2>' +
          '<p class="crisis-modal-subtitle">' + escapeHtml(config.support_msg || '') + '</p>' +
        '</div>' +
        '<div class="crisis-modal-actions">' +
          '<a href="' + escapeHtml(profUrl) + '" target="_blank" rel="noopener noreferrer" class="crisis-btn crisis-btn-primary" id="crisisBtnProfesional">' +
            '<span class="crisis-btn-icon">🩺</span>' +
            '<span class="crisis-btn-text">' +
              '<strong>Hablar con un profesional</strong>' +
              '<span>Conecta con ayuda especializada ahora</span>' +
            '</span>' +
          '</a>' +
          '<button type="button" class="crisis-btn crisis-btn-emergency" id="crisisBtnRecursos">' +
            '<span class="crisis-btn-icon">🆘</span>' +
            '<span class="crisis-btn-text">' +
              '<strong>Recursos de emergencia</strong>' +
              '<span>Líneas de atención y centros de apoyo</span>' +
            '</span>' +
          '</button>' +
          '<button type="button" class="crisis-btn crisis-btn-trust" id="crisisBtnConfianza">' +
            '<span class="crisis-btn-icon">🤝</span>' +
            '<span class="crisis-btn-text">' +
              '<strong>Contactar a una persona de confianza</strong>' +
              '<span>Habla con alguien cercano a ti</span>' +
            '</span>' +
          '</button>' +
        '</div>' +
        '<div class="crisis-modal-resources" id="crisisResourcesPanel" style="display:none">' +
          buildResourcesHTML() +
        '</div>' +
        '<div class="crisis-modal-trust" id="crisisTrustPanel" style="display:none">' +
          '<div class="crisis-trust-content">' +
            '<span class="crisis-trust-icon">💜</span>' +
            '<h3>Habla con alguien de confianza</h3>' +
            '<p>Piensa en alguien cercano a ti: un familiar, un amigo, un profesor, un orientador del colegio, ' +
            'o cualquier adulto en quien confíes. Contarle cómo te sientes es un acto de valentía, no de debilidad.</p>' +
            '<p><strong>¿No sabes qué decir?</strong> Puedes empezar con algo así:</p>' +
            '<div class="crisis-trust-example">"Necesito hablar contigo porque no me siento bien emocionalmente y necesito apoyo."</div>' +
          '</div>' +
        '</div>' +
        '<button type="button" class="crisis-modal-close" id="crisisModalClose" aria-label="Cerrar alerta">' +
          'Entendido, voy a buscar ayuda' +
        '</button>' +
      '</div>';

    document.body.appendChild(overlay);

    // Eventos de los botones internos
    var btnRecursos = document.getElementById('crisisBtnRecursos');
    var btnConfianza = document.getElementById('crisisBtnConfianza');
    var resourcesPanel = document.getElementById('crisisResourcesPanel');
    var trustPanel = document.getElementById('crisisTrustPanel');

    if (btnRecursos) {
      btnRecursos.addEventListener('click', function () {
        var showing = resourcesPanel.style.display !== 'none';
        resourcesPanel.style.display = showing ? 'none' : 'block';
        if (!showing) trustPanel.style.display = 'none';
      });
    }
    if (btnConfianza) {
      btnConfianza.addEventListener('click', function () {
        var showing = trustPanel.style.display !== 'none';
        trustPanel.style.display = showing ? 'none' : 'block';
        if (!showing) resourcesPanel.style.display = 'none';
      });
    }
  }

  function buildResourcesHTML() {
    if (!config || !config.resources) return '';
    var html = '';
    var resources = config.resources;
    for (var i = 0; i < resources.length; i++) {
      var cat = resources[i];
      html += '<div class="crisis-resource-cat">';
      html += '<h4>' + escapeHtml(cat.icon) + ' ' + escapeHtml(cat.category) + '</h4>';
      html += '<div class="crisis-resource-items">';
      for (var j = 0; j < cat.items.length; j++) {
        var item = cat.items[j];
        if (item.type === 'tel') {
          html += '<a href="tel:' + escapeHtml(item.value) + '" class="crisis-resource-link">';
          html += '<strong>📞 ' + escapeHtml(item.name) + '</strong>';
          html += '<span>' + escapeHtml(item.desc) + '</span></a>';
        } else if (item.type === 'url') {
          html += '<a href="' + escapeHtml(item.value) + '" target="_blank" rel="noopener noreferrer" class="crisis-resource-link">';
          html += '<strong>🔗 ' + escapeHtml(item.name) + '</strong>';
          html += '<span>' + escapeHtml(item.desc) + '</span></a>';
        } else {
          html += '<div class="crisis-resource-link">';
          html += '<strong>ℹ️ ' + escapeHtml(item.name) + '</strong>';
          html += '<span>' + escapeHtml(item.desc) + '</span></div>';
        }
      }
      html += '</div></div>';
    }
    return html;
  }

  function showModal(level) {
    var modal = document.getElementById('crisisModal');
    if (!modal) return;
    modal.style.display = 'flex';
    modalVisible = true;

    // Agregar clase de nivel
    var inner = modal.querySelector('.crisis-modal');
    if (inner) {
      inner.classList.remove('level-critical', 'level-warning');
      inner.classList.add('level-' + level);
    }

    // Focus trap — enfocar el primer botón
    setTimeout(function () {
      var firstBtn = modal.querySelector('.crisis-btn');
      if (firstBtn) firstBtn.focus();
    }, 100);

    // Bloquear scroll
    document.body.style.overflow = 'hidden';
  }

  function hideModal() {
    var modal = document.getElementById('crisisModal');
    if (!modal) return;
    modal.style.display = 'none';
    modalVisible = false;
    document.body.style.overflow = '';

    // Ocultar paneles internos
    var rp = document.getElementById('crisisResourcesPanel');
    var tp = document.getElementById('crisisTrustPanel');
    if (rp) rp.style.display = 'none';
    if (tp) tp.style.display = 'none';
  }

  // ── Banner persistente en el chat ──

  function injectBannerHTML() {
    if (document.getElementById('crisisBanner')) return;

    var helpLinks = config.help_links || {};
    var profUrl = (helpLinks.buscar_profesional || {}).url || '#';

    var banner = document.createElement('div');
    banner.id = 'crisisBanner';
    banner.className = 'crisis-banner';
    banner.setAttribute('role', 'alert');
    banner.setAttribute('aria-live', 'assertive');
    banner.style.display = 'none';

    banner.innerHTML =
      '<div class="crisis-banner-inner">' +
        '<span class="crisis-banner-icon">💜</span>' +
        '<div class="crisis-banner-text">' +
          '<strong>Recursos de ayuda disponibles</strong>' +
          '<span>Si necesitas apoyo, no dudes en contactar a un profesional.</span>' +
        '</div>' +
        '<div class="crisis-banner-actions">' +
          '<a href="tel:106" class="crisis-banner-btn crisis-banner-btn-call">📞 Línea 106</a>' +
          '<a href="' + escapeHtml(profUrl) + '" target="_blank" rel="noopener noreferrer" class="crisis-banner-btn crisis-banner-btn-prof">🩺 Ayuda profesional</a>' +
        '</div>' +
      '</div>';

    // Insertar el banner antes del input del chat
    var inputWrap = document.querySelector('.c-input-wrap');
    if (inputWrap) {
      inputWrap.parentNode.insertBefore(banner, inputWrap);
    } else {
      document.body.appendChild(banner);
    }
  }

  function showBanner() {
    var banner = document.getElementById('crisisBanner');
    if (!banner) return;
    banner.style.display = 'block';
    bannerVisible = true;
  }

  // ── Eventos de cierre ──

  function bindCloseEvents() {
    // Botón cerrar
    var closeBtn = document.getElementById('crisisModalClose');
    if (closeBtn) {
      closeBtn.addEventListener('click', hideModal);
    }

    // Clic en overlay (fuera del modal)
    var overlay = document.getElementById('crisisModal');
    if (overlay) {
      overlay.addEventListener('click', function (e) {
        if (e.target === overlay) hideModal();
      });
    }

    // Escape key
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modalVisible) hideModal();
    });
  }

  // ── Utilidades ──

  function escapeHtml(text) {
    if (!text) return '';
    var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function (k) { return map[k]; });
  }

  // ── API pública ──
  return {
    init: init,
    analyze: analyze,
    activateProtocol: activateProtocol,
    showModal: showModal,
    hideModal: hideModal,
    showBanner: showBanner,
  };
})();
