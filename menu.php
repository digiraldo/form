<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navbar Moderna Bootstrap - Dropdown Usuario Colores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Estilos personalizados para un efecto "wow" */
        :root {
            --navbar-height: 65px; /* Altura de la barra de navegación */
            --primary-glow-rgb: var(--bs-primary-rgb);
        }

        html[data-bs-theme="light"] {
            --primary-glow-color: rgba(var(--primary-glow-rgb), 0.3);
        }
        html[data-bs-theme="dark"] {
            --primary-glow-color: rgba(var(--primary-glow-rgb), 0.5);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bs-body-bg);
            transition: background-color 0.35s ease, color 0.35s ease;
        }

        .navbar {
            min-height: var(--navbar-height);
            background-color: color-mix(in srgb, var(--bs-tertiary-bg), transparent 15%) !important; 
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--bs-border-color-translucent);
            box-shadow: 0 4px 18px rgba(0,0,0,0.07);
            transition: background-color 0.35s ease, border-color 0.35s ease, box-shadow 0.35s ease;
        }

        .navbar .container-fluid {
            padding-top: 0.3rem;
            padding-bottom: 0.3rem;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.3rem;
            letter-spacing: -0.5px;
            transition: color 0.2s ease;
        }
        .navbar-brand:hover {
            color: var(--bs-primary);
        }

        .navbar-nav .nav-link {
            padding: 0.65rem 1.1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: color 0.25s ease, background-color 0.25s ease, transform 0.2s cubic-bezier(0.25, 0.1, 0.25, 1);
            position: relative;
            margin: 0 0.2rem;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link:focus {
            color: var(--bs-primary);
            background-color: color-mix(in srgb, var(--bs-primary), transparent 90%);
            transform: translateY(-2px);
        }

        .navbar-nav .nav-link.active {
            color: var(--bs-primary);
            background-color: color-mix(in srgb, var(--bs-primary), transparent 85%);
            font-weight: 600;
        }
        .navbar-nav .nav-link.active::before {
            content: '';
            position: absolute;
            bottom: 5px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 3px;
            background-color: var(--bs-primary);
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        .navbar-nav .nav-link.active:hover::before {
            width: 30px;
        }


        #theme-toggler {
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: none;
            background-color: var(--bs-tertiary-bg);
            color: var(--bs-emphasis-color);
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            transition: background-color 0.25s ease, color 0.25s ease, transform 0.25s ease, box-shadow 0.25s ease;
        }

        #theme-toggler:hover,
        #theme-toggler:focus {
            background-color: var(--bs-primary);
            color: var(--bs-light);
            transform: scale(1.1) rotate(20deg);
            box-shadow: 0 0 15px var(--primary-glow-color), 0 4px 10px rgba(0,0,0,0.1);
        }
        #theme-toggler:focus {
            outline: none;
            box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.5), 0 0 15px var(--primary-glow-color);
        }

        #theme-toggler i {
            font-size: 1.3rem;
        }

        /* Estilos para el trigger del dropdown de usuario */
        #user-profile-trigger {
            cursor: pointer;
            padding: 0.35rem 0.5rem;
            border-radius: 0.75rem;
            transition: background-color 0.2s ease;
            color: var(--bs-body-color); /* Asegura que el texto del enlace se adapte */
        }
        #user-profile-trigger:hover,
        #user-profile-trigger.show { /* .show es añadido por Bootstrap cuando el dropdown está abierto */
             background-color: color-mix(in srgb, var(--bs-secondary-bg), transparent 70%);
        }

        #user-profile-trigger .profile-image { /* Estilo de la imagen de perfil dentro del trigger */
            width: 40px;
            height: 40px;
            object-fit: cover;
            border: 2px solid var(--bs-body-bg);
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        #user-profile-trigger:hover .profile-image,
        #user-profile-trigger.show .profile-image {
            transform: scale(1.1);
            box-shadow: 0 0 10px var(--primary-glow-color), 0 2px 6px rgba(0,0,0,0.1);
        }
        
        #user-profile-trigger .user-profile-name {
             font-weight: 500;
             margin-left: 0.6rem;
             transition: color 0.2s ease;
        }

        #user-profile-trigger:hover .user-profile-name,
        #user-profile-trigger.show .user-profile-name {
            color: var(--bs-primary) !important; /* Asegura que el nombre cambie de color */
        }
        
        #user-profile-trigger .bi-chevron-down {
            transition: transform 0.2s ease-in-out;
        }
        #user-profile-trigger.show .bi-chevron-down {
            transform: rotate(180deg);
        }

        /* Estilos para el menú desplegable */
        .dropdown-menu-user { /* Clase específica para el dropdown de usuario */
            --bs-dropdown-min-width: 250px;
            --bs-dropdown-padding-x: 0;
            --bs-dropdown-padding-y: 0.5rem;
            --bs-dropdown-border-radius: 0.75rem;
            --bs-dropdown-border-color: var(--bs-border-color-translucent);
            --bs-dropdown-link-hover-bg: color-mix(in srgb, var(--bs-primary), transparent 90%);
            --bs-dropdown-link-active-bg: var(--bs-primary);
            --bs-dropdown-link-active-color: var(--bs-light);
            box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.12); /* Sombra más pronunciada */
            border: none; 
            margin-top: 0.5rem !important; /* Espacio adicional desde el trigger */
        }

        .dropdown-menu-user .dropdown-item {
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            transition: background-color 0.15s ease, color 0.15s ease;
        }
        .dropdown-menu-user .dropdown-item i {
            font-size: 1.1rem; /* Tamaño de iconos en dropdown */
            opacity: 0.75;
            transition: opacity 0.15s ease;
            margin-right: 0.85rem !important; /* Espacio entre icono y texto */
            width: 20px; 
            text-align: center;
        }
        .dropdown-menu-user .dropdown-item:hover i,
        .dropdown-menu-user .dropdown-item:focus i {
            opacity: 1;
        }
        .dropdown-menu-user .dropdown-header-custom { /* Cabecera personalizada del dropdown */
            padding: 0.75rem 1.2rem;
            border-bottom: 1px solid var(--bs-border-color-translucent);
            margin-bottom: 0.25rem;
        }
        .dropdown-menu-user .dropdown-divider {
            margin-top: 0.25rem;
            margin-bottom: 0.25rem;
        }


        /* Adaptaciones para móviles */
        @media (max-width: 991.98px) {
            .navbar {
                background-color: var(--bs-tertiary-bg) !important; 
                backdrop-filter: none;
                -webkit-backdrop-filter: none;
            }

            .navbar-collapse {
                padding: 1rem 0.75rem;
                margin-top: 0.75rem;
                background-color: var(--bs-body-bg);
                border-radius: var(--bs-border-radius-lg);
                box-shadow: 0 8px 25px rgba(0,0,0,0.1);
                border: 1px solid var(--bs-border-color-translucent);
            }

            .navbar-nav .nav-link {
                padding: 0.8rem 1rem;
                margin: 0.25rem 0;
            }
            .navbar-nav .nav-link:hover,
            .navbar-nav .nav-link:focus {
                transform: none;
            }
             .navbar-nav .nav-link.active::before {
                bottom: 7px;
            }

            .navbar-collapse .d-flex.align-items-center {
                margin-top: 1rem;
                padding-top: 1rem;
                border-top: 1px solid var(--bs-border-color);
                display: flex;
                justify-content: space-between !important;
                align-items: center;
            }
            .user-profile-name { /* El nombre de usuario en el trigger */
                font-size: 0.9rem;
            }
            /* En móvil, el dropdown se alinea a la izquierda por defecto, lo cual es usualmente mejor */
            .dropdown-menu-user {
                width: 100%; /* Ocupa todo el ancho disponible en el menú colapsado */
            }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">MiApp Increíble</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavContent" aria-controls="navbarNavContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Descubrir</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Precios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Blog</a>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center ms-lg-auto">
                    <button class="btn me-2 me-lg-3" id="theme-toggler" type="button" aria-label="Cambiar tema">
                        <i class="bi bi-sun-fill"></i>
                    </button>

                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none" id="user-profile-trigger" data-bs-toggle="dropdown" aria-expanded="false" data-bs-offset="0,10">
                            <img src="https://placehold.co/40x40/7C3AED/FFFFFF?text=A&font=Inter&bold" 
                                 class="rounded-circle me-2 profile-image" 
                                 alt="Imagen de perfil del usuario"
                                 onerror="this.onerror=null; this.src='https://placehold.co/40x40/E0E0E0/B0B0B0?text=Error&font=Inter';">
                            <span class="user-profile-name d-none d-sm-inline text-body-emphasis">Alex Códer</span>
                            <i class="bi bi-chevron-down ms-1 d-none d-sm-inline text-body-secondary" style="font-size: 0.75rem;"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-user" aria-labelledby="user-profile-trigger">
                            <li>
                                <div class="dropdown-header-custom">
                                    <div class="fw-bold">Alex Códer</div>
                                    <div class="small text-muted">alex.coder@example.com</div>
                                </div>
                            </li>
                            <li><a class="dropdown-item d-flex align-items-center text-info" href="#"> <!-- MODIFICADO: Añadida clase text-info -->
                                <i class="bi bi-gear"></i> Mi Configuración
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item d-flex align-items-center text-danger" href="#">
                                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-3">
        <h1>Contenido Principal Atractivo</h1>
        <p class="lead">Este es un ejemplo de cómo se ve el contenido debajo de la nueva barra de navegación. Interactúa con ella y cambia el tema para ver los efectos "wow".</p>
        <button class="btn btn-primary btn-lg shadow-sm">Acción Principal</button>
        <button class="btn btn-outline-secondary btn-lg ms-2">Otra Acción</button>

        <div class="row mt-4">
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Tarjeta 1</h5>
                        <p class="card-text">Contenido de ejemplo para llenar la página y probar el scroll con el navbar.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Tarjeta 2</h5>
                        <p class="card-text">Más contenido para asegurar que la experiencia sea fluida.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Tarjeta 3</h5>
                        <p class="card-text">El efecto de cristal esmerilado del navbar se apreciará mejor con contenido detrás.</p>
                    </div>
                </div>
            </div>
        </div>
         <p style="height: 500px;">Espacio extra para scroll...</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeToggler = document.getElementById('theme-toggler');
            const htmlEl = document.documentElement;
            const iconEl = themeToggler.querySelector('i');

            const applyTheme = (theme, isInitialLoad = false) => {
                htmlEl.dataset.bsTheme = theme;
                if (theme === 'dark') {
                    iconEl.classList.remove('bi-sun-fill');
                    iconEl.classList.add('bi-moon-stars-fill');
                } else {
                    iconEl.classList.remove('bi-moon-stars-fill');
                    iconEl.classList.add('bi-sun-fill');
                }
                if (!isInitialLoad) {
                    localStorage.setItem('theme', theme);
                }
            };

            const animateThemeToggle = () => {
                iconEl.style.transition = 'transform 0.3s ease-in, opacity 0.2s ease-in';
                iconEl.style.transform = 'rotate(-90deg) scale(0.4)';
                iconEl.style.opacity = '0';

                setTimeout(() => {
                    const currentTheme = htmlEl.dataset.bsTheme;
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    applyTheme(newTheme); 

                    iconEl.style.transition = 'transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.4s ease-out 0.1s';
                    iconEl.style.transform = 'rotate(0deg) scale(1)';
                    iconEl.style.opacity = '1';
                }, 250);
            };
            
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (savedTheme) {
                applyTheme(savedTheme, true);
            } else {
                applyTheme(prefersDark ? 'dark' : 'light', true);
            }

            themeToggler.addEventListener('click', () => {
                animateThemeToggle();
            });
        });
    </script>
</body>
</html>
