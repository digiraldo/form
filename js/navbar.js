// navbar.js: lógica JS para el nuevo navbar (basado en menu.php)

document.addEventListener('DOMContentLoaded', () => {
    // Theme toggler
    const themeToggler = document.getElementById('theme-toggler'); // ID actualizado al del navbar.php
    const htmlEl = document.documentElement;

    // Función para aplicar el tema y actualizar el icono
    const applyTheme = (theme) => {
        htmlEl.dataset.bsTheme = theme;
        if (themeToggler) {
            const iconEl = themeToggler.querySelector('i'); // Corregido para que coincida con navbar.php
            if (theme === 'dark') {
                iconEl.classList.remove('bi-sun-fill');
                iconEl.classList.add('bi-moon-stars-fill');
            } else {
                iconEl.classList.remove('bi-moon-stars-fill');
                iconEl.classList.add('bi-sun-fill');
            }
        }
        localStorage.setItem('theme', theme);
    };

    // Animación del icono
    const animateIcon = (iconEl) => {
        if (iconEl) {
            iconEl.style.transform = 'rotate(360deg) scale(0.7)';
            setTimeout(() => { iconEl.style.transform = 'rotate(0deg) scale(1)'; }, 400);
        }
    };

    // Cargar tema guardado o preferencia del sistema
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (savedTheme) {
        applyTheme(savedTheme);
    } else {
        applyTheme(prefersDark ? 'dark' : 'light');
    }

    if (themeToggler) {
        themeToggler.addEventListener('click', () => {
            const currentTheme = htmlEl.dataset.bsTheme;
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            applyTheme(newTheme);
            animateIcon(themeToggler.querySelector('i')); // Pasar el elemento del icono a la animación
        });
    }

    // Lógica para mostrar imagen de perfil o icono por defecto
    // Esta lógica se ha simplificado ya que PHP y el atributo onerror del <img> manejan gran parte.
    // Este script puede asegurar la consistencia si algo cambia dinámicamente, aunque updateUserNavbarProfileImage en admin.js es el principal para eso.
    const profileImage = document.getElementById('userProfileImage'); 
    const profileIconPlaceholder = document.getElementById('userProfileIconPlaceholder');

    if (profileImage && profileIconPlaceholder) {
        // Si la imagen tiene un src y data-has-image es true, y no está ya visible (por PHP)
        // y no ha fallado (onerror la ocultaría y mostraría el placeholder)
        // Esta comprobación es más bien un seguro, PHP debería haberlo hecho bien.
        if (profileImage.getAttribute('data-has-image') === 'true' && profileImage.style.display !== 'none') {
            // No es necesario hacer nada aquí si PHP ya lo configuró correctamente.
            // Si la imagen está configurada para mostrarse y tiene 'data-has-image' = true, se asume que está bien.
        } else if (profileImage.style.display === 'none') {
            // Si la imagen está oculta (posiblemente por onerror o porque no hay imagen), asegúrate de que el placeholder esté visible.
            profileIconPlaceholder.style.display = 'inline-block'; // o 'block' según el layout
        } else {
            // Caso por defecto: no hay imagen o falló la carga, mostrar placeholder
            profileImage.style.display = 'none';
            profileIconPlaceholder.style.display = 'inline-block'; // o 'block'
        }
    }

    // Eliminar la lógica del dropdown de usuario, ya que el nuevo navbar no usa un dropdown para el perfil.
    // Eliminar la creación dinámica de <style> para la animación, ya que se ha movido a navbar.php (o podría ir a navbar.css)
});

// La animación CSS se ha movido al <style> en navbar.php o podría estar en css/navbar.css
// No es necesario añadirla aquí dinámicamente.
