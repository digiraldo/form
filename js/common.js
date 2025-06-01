// common.js - Funciones compartidas entre múltiples archivos

/**
 * Calcula el color de contraste óptimo (negro o blanco) para un fondo
 * basado en la luminosidad según el estándar WCAG.
 * 
 * @param {string} hexColor - Color de fondo en formato hexadecimal (#RRGGBB o #RGB)
 * @returns {string} - Color de contraste (#000000 o #FFFFFF)
 */
function getContrastColor(hexColor) {
    // Si hexColor no es un código de color válido, devolver blanco (predeterminado seguro)
    if (!hexColor || typeof hexColor !== 'string' || !hexColor.match(/^#([0-9A-F]{3}){1,2}$/i)) {
        return '#FFFFFF';
    }

    // Convertir hex a RGB
    let r, g, b;
    if (hexColor.length === 4) {
        // Color hexadecimal de 3 dígitos (#RGB)
        r = parseInt(hexColor[1] + hexColor[1], 16);
        g = parseInt(hexColor[2] + hexColor[2], 16);
        b = parseInt(hexColor[3] + hexColor[3], 16);
    } else {
        // Color hexadecimal de 6 dígitos (#RRGGBB)
        r = parseInt(hexColor.substring(1, 3), 16);
        g = parseInt(hexColor.substring(3, 5), 16);
        b = parseInt(hexColor.substring(5, 7), 16);
    }

    // Calcular luminosidad según estándar WCAG
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
    
    // Si la luminosidad es mayor a 0.5, el fondo es claro y necesitamos texto oscuro
    return luminance > 0.5 ? '#000000' : '#FFFFFF';
}

/**
 * Aplica colores de contraste automáticamente a todos los elementos
 * con el atributo data-auto-contrast="true"
 */
function applyAutoContrast() {
    document.querySelectorAll('[data-auto-contrast="true"]').forEach(el => {
        const bgColor = window.getComputedStyle(el).backgroundColor;
        if (!bgColor || bgColor === 'transparent' || bgColor === 'rgba(0, 0, 0, 0)') return;
        
        // Convertir valor RGB/RGBA a hexadecimal
        const rgbValues = bgColor.match(/\d+/g);
        if (!rgbValues || rgbValues.length < 3) return;
        
        const r = parseInt(rgbValues[0]);
        const g = parseInt(rgbValues[1]);
        const b = parseInt(rgbValues[2]);
        const hexColor = '#' + [r, g, b].map(x => {
            const hex = x.toString(16);
            return hex.length === 1 ? '0' + hex : hex;
        }).join('');
        
        const contrastColor = getContrastColor(hexColor);
        el.style.color = contrastColor;
    });
}

// Exponer funciones globalmente
window.getContrastColor = getContrastColor;
window.applyAutoContrast = applyAutoContrast;

// Aplicar contraste automático después de cargar la página
document.addEventListener('DOMContentLoaded', function() {
    applyAutoContrast();
    
    // También aplicar cuando cambien elementos dinámicamente (MutationObserver)
    const observer = new MutationObserver(function(mutations) {
        applyAutoContrast();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
