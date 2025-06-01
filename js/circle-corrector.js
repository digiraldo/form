/**
 * Script de Corrección Forzada para Círculos de Imágenes
 * Refuerzo JavaScript para casos extremos donde CSS no sea suficiente
 */

(function() {
    'use strict';

    // Configuración de corrección
    const CIRCLE_CONFIG = {
        defaultSize: 32,
        supportedSizes: [24, 30, 32, 35, 40],
        selectors: [
            '.permission-tooltip img.rounded-circle',
            '.editor-tooltip img.rounded-circle',
            '.tooltip img.rounded-circle',
            '.tooltip-inner img.rounded-circle',
            'img.rounded-circle[src]'
        ]
    };

    /**
     * Fuerza que una imagen sea un círculo perfecto
     */
    function forceCircleImage(img) {
        if (!img || img.tagName !== 'IMG') return;

        // Detectar tamaño objetivo
        const computedStyle = window.getComputedStyle(img);
        const currentWidth = parseInt(computedStyle.width) || CIRCLE_CONFIG.defaultSize;
        const currentHeight = parseInt(computedStyle.height) || CIRCLE_CONFIG.defaultSize;
        
        // Usar el tamaño más cercano soportado
        const targetSize = CIRCLE_CONFIG.supportedSizes.reduce((prev, curr) => 
            Math.abs(curr - Math.max(currentWidth, currentHeight)) < Math.abs(prev - Math.max(currentWidth, currentHeight)) ? curr : prev
        );

        // Aplicar correcciones forzadas
        const corrections = {
            'width': `${targetSize}px`,
            'height': `${targetSize}px`,
            'min-width': `${targetSize}px`,
            'min-height': `${targetSize}px`,
            'max-width': `${targetSize}px`,
            'max-height': `${targetSize}px`,
            'border-radius': '50%',
            'object-fit': 'cover',
            'object-position': 'center center',
            'aspect-ratio': '1 / 1',
            'flex-shrink': '0',
            'flex-grow': '0',
            'box-sizing': 'border-box',
            'display': 'inline-block',
            'vertical-align': 'middle'
        };

        // Aplicar cada corrección con máxima prioridad
        Object.entries(corrections).forEach(([property, value]) => {
            img.style.setProperty(property, value, 'important');
        });

        // Agregar clase específica para tracking
        img.classList.add('js-circle-corrected');
        
        console.log(`✓ Imagen corregida a círculo ${targetSize}px:`, img.src);
    }

    /**
     * Procesa todas las imágenes que deben ser círculos
     */
    function processAllImages() {
        CIRCLE_CONFIG.selectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(img => {
                forceCircleImage(img);
            });
        });
    }

    /**
     * Observer para detectar nuevas imágenes añadidas dinámicamente
     */
    function setupMutationObserver() {
        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        // Verificar si el nodo añadido es una imagen
                        if (node.tagName === 'IMG' && node.classList.contains('rounded-circle')) {
                            forceCircleImage(node);
                        }
                        
                        // Verificar imágenes dentro del nodo añadido
                        node.querySelectorAll && node.querySelectorAll('img.rounded-circle').forEach(img => {
                            forceCircleImage(img);
                        });
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        console.log('✓ Observer de círculos configurado');
    }

    /**
     * Handler para cuando se muestran tooltips
     */
    function setupTooltipHandlers() {
        // Bootstrap 5 tooltip events
        document.addEventListener('shown.bs.tooltip', function(event) {
            setTimeout(() => {
                // Procesar imágenes en el tooltip recién mostrado
                const tooltipElement = document.querySelector('.tooltip.show');
                if (tooltipElement) {
                    tooltipElement.querySelectorAll('img.rounded-circle').forEach(img => {
                        forceCircleImage(img);
                    });
                }
            }, 50); // Pequeño delay para asegurar que el DOM esté listo
        });

        console.log('✓ Handlers de tooltips configurados');
    }

    /**
     * Corrección de imágenes cuando se cargan
     */
    function setupImageLoadHandlers() {
        document.addEventListener('load', function(event) {
            if (event.target.tagName === 'IMG' && event.target.classList.contains('rounded-circle')) {
                forceCircleImage(event.target);
            }
        }, true);

        console.log('✓ Handlers de carga de imágenes configurados');
    }

    /**
     * Función de diagnóstico
     */
    function diagnoseCircleIssues() {
        const images = document.querySelectorAll('img.rounded-circle');
        console.group('🔍 Diagnóstico de Círculos');
        
        images.forEach((img, index) => {
            const computedStyle = window.getComputedStyle(img);
            const width = computedStyle.width;
            const height = computedStyle.height;
            const borderRadius = computedStyle.borderRadius;
            const objectFit = computedStyle.objectFit;
            
            console.log(`Imagen ${index + 1}:`, {
                src: img.src,
                width,
                height,
                borderRadius,
                objectFit,
                isCircle: width === height && borderRadius.includes('50%'),
                classes: Array.from(img.classList)
            });
        });
        
        console.groupEnd();
    }

    /**
     * API pública para debugging
     */
    window.CircleCorrector = {
        fix: processAllImages,
        diagnose: diagnoseCircleIssues,
        forceCircle: forceCircleImage,
        config: CIRCLE_CONFIG
    };

    /**
     * Inicialización
     */
    function initialize() {
        console.log('🔧 Iniciando Corrector de Círculos...');
        
        // Procesar imágenes existentes
        processAllImages();
        
        // Configurar observers y handlers
        setupMutationObserver();
        setupTooltipHandlers();
        setupImageLoadHandlers();
        
        // Procesar nuevamente después de un breve delay (para elementos cargados dinámicamente)
        setTimeout(processAllImages, 500);
        
        // Ejecutar diagnóstico en desarrollo
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            setTimeout(diagnoseCircleIssues, 1000);
        }
        
        console.log('✅ Corrector de Círculos iniciado');
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }

})();
