# 🚀 Sistema de Formularios Admin con RBAC Multi-Área

<div align="center">

![PHP](https://img.shields.io/badge/PHP-8+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![MySQL](https://img.shields.io/badge/JSON-Database-00758F?style=for-the-badge&logo=json&logoColor=white)

**Una solución completa y moderna para la gestión de formularios empresariales con sistema avanzado de Control de Acceso Basado en Roles (RBAC) multi-área.**

[🎯 Características](#-características-principales) • [⚡ Instalación](#-instalación-rápida) • [🔧 Configuración](#-configuración) • [📖 Documentación](#-documentación)

</div>

---

## 📋 Descripción General

**Formularios Admin RBAC** es un sistema empresarial completo para la creación, gestión y análisis de formularios tipo Google Forms, diseñado específicamente para organizaciones con múltiples departamentos o áreas de trabajo. Cuenta con un sistema avanzado de permisos y roles que permite una gestión granular y segura de usuarios y contenido.

### 🎯 Casos de Uso Ideales
- **Empresas multi-departamentales** que necesitan formularios específicos por área
- **Organizaciones educativas** con diferentes facultades o departamentos
- **Instituciones públicas** con múltiples oficinas o dependencias
- **Empresas de servicios** con equipos especializados

---

## 🌟 Características Principales

### 🔐 **Sistema RBAC Multi-Área Avanzado**
- **3 Niveles de Roles:** Owner (Propietario), Admin (Administrador), Editor
- **Áreas de Trabajo Independientes:** Cada área funciona como un workspace separado
- **Permisos Cruzados:** Colaboración controlada entre diferentes áreas
- **Auditoría Completa:** Registro detallado de todas las acciones y cambios de permisos
- **Gestión Visual de Usuarios:** Interfaz moderna con avatares y tooltips informativos

### 🎨 **Gestión Visual de Áreas**
- **Colores Personalizables:** Cada área puede tener su color distintivo
- **Contraste Automático:** Cálculo inteligente de color de texto para óptima legibilidad
- **Badges Coloridos:** Identificación visual inmediata en tablas y listas
- **Círculos Perfectos:** Sistema robusto para imágenes de perfil siempre circulares

### 📝 **Creación y Gestión de Formularios**
- **Editor Drag & Drop:** Arrastra y ordena campos fácilmente
- **12 Tipos de Campos:** Texto, párrafo, email, teléfono, fecha, opción múltiple, checkboxes, select, archivos, descargas, términos
- **Validaciones Inteligentes:** Sistema robusto de validación frontend y backend
- **Fechas de Caducidad:** Control temporal automático de formularios
- **Preview en Tiempo Real:** Vista previa instantánea mientras editas

### 🖥️ **Panel de Administración Moderno**
- **Modo Claro/Oscuro:** Interfaz adaptable a preferencias del usuario
- **DataTables Avanzadas:** Búsqueda, filtrado, ordenamiento y paginación
- **Dashboard Intuitivo:** Métricas y estadísticas en tiempo real
- **Responsive Design:** Perfecto funcionamiento en dispositivos móviles
- **Navegación Inteligente:** Menús contextuales según el rol del usuario

### 📊 **Análisis y Reportes**
- **Gráficas Interactivas:** Visualización con Chart.js de todas las respuestas
- **Exportación de Datos:** Descarga de respuestas en múltiples formatos
- **Cálculos Automáticos:** Edad automática desde fecha de nacimiento
- **Gestión de Archivos:** Sistema seguro para archivos subidos por usuarios

### 🔒 **Seguridad y Validación**
- **Autenticación Robusta:** Sistema seguro de sesiones PHP
- **Sanitización de Datos:** Protección contra XSS e inyección de código
- **Permisos Granulares:** Control específico por área y recurso
- **URLs Únicas:** Enlaces seguros y únicos para cada formulario público

### 💾 **Sistema de Backup y Recuperación**
- **Backup Completo:** Exportación de todos los datos del sistema en un solo archivo ZIP
- **Restauración Segura:** Importación de backups con validación y backup automático del estado actual
- **Solo para Propietarios:** Acceso exclusivo para usuarios con rol "owner"
- **Historial Completo:** Registro detallado de todas las operaciones de backup
- **Estadísticas del Sistema:** Información sobre tamaños y conteos de archivos
- **Protección de Datos:** Backup automático antes de cualquier restauración
- **Directorios Incluidos:** data/, downloads/, profile_images/, uploads/

---

## 🛠️ Stack Tecnológico

<table>
<tr>
<td><strong>Backend</strong></td>
<td>
  
![PHP](https://img.shields.io/badge/PHP-8+-777BB4?style=flat-square&logo=php&logoColor=white)
- PHP 8+ con arquitectura JSON
- API RESTful estructurada
- Validación robusta de datos
- Gestión segura de sesiones

</td>
</tr>
<tr>
<td><strong>Frontend</strong></td>
<td>

![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=flat-square&logo=bootstrap&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat-square&logo=javascript&logoColor=black)
![jQuery](https://img.shields.io/badge/jQuery-3.x-0769AD?style=flat-square&logo=jquery&logoColor=white)
- Bootstrap 5 (UI/UX moderno)
- JavaScript ES6+ (funcionalidad dinámica)
- jQuery 3.x (DataTables integration)
- CSS3 (animaciones y responsive)

</td>
</tr>
<tr>
<td><strong>Librerías</strong></td>
<td>

- **[DataTables](https://datatables.net/)** - Tablas avanzadas con Bootstrap 5
- **[Chart.js 4.x](https://www.chartjs.org/)** - Gráficas interactivas y responsivas
- **[Font Awesome 6.x](https://fontawesome.com/)** - Iconografía moderna
- **[Animate.css](https://animate.style/)** - Animaciones CSS
- **[Hover.css](https://ianlunn.github.io/Hover/)** - Efectos hover avanzados

</td>
</tr>
<tr>
<td><strong>Base de Datos</strong></td>
<td>

![JSON](https://img.shields.io/badge/JSON-Database-00758F?style=flat-square&logo=json&logoColor=white)
- Almacenamiento en archivos JSON
- Sin dependencias de bases de datos
- Estructura flexible y escalable
- Backup y migración sencillos

</td>
</tr>
</table>

---

## 📂 Estructura del Proyecto

```
📁 form/
│
├── 📄 index.php                    # 🎯 Página de entrada con redirección inteligente
├── 📄 login.php                    # 🔐 Sistema de autenticación
├── 📄 admin_dashboard.php           # 🖥️ Panel principal de administración
├── 📄 admin_users.php               # 👥 Gestión avanzada de usuarios y roles
├── 📄 admin_areas.php               # 🏢 Gestión de áreas de trabajo
├── 📄 admin_settings.php            # ⚙️ Configuración de perfil y preferencias
├── 📄 admin_backup.php              # 💾 Sistema de backup y recuperación (solo owners)
├── 📄 form.php                      # 🌐 Visualización pública de formularios
├── 📄 navbar.php                    # 🧭 Navegación principal con modo claro/oscuro
├── 📄 footer.php                    # 🦶 Footer con créditos actualizados
│
├── 📁 api/                          # 🔌 API RESTful completa
│   ├── 📄 auth.php                  # Autenticación y sesiones
│   ├── 📄 forms.php                 # CRUD de formularios + permisos cruzados
│   ├── 📄 users.php                 # Gestión de usuarios y roles
│   ├── 📄 areas.php                 # Gestión de áreas y colores
│   ├── 📄 responses.php             # Procesamiento de respuestas
│   ├── 📄 backup.php                # Sistema de backup y restauración
│   └── 📄 areas_list_available.php  # Listado de áreas por permisos
│
├── 📁 data/                         # 💾 Almacenamiento JSON
│   ├── 📄 users.json                # Base de datos de usuarios
│   ├── 📄 areas.json                # Configuración de áreas
│   ├── 📁 forms/                    # Formularios individuales
│   │   ├── 📄 {form_id}.json
│   │   └── ...
│   └── 📁 responses/                # Respuestas de formularios
│       ├── 📄 {form_id}_responses.json
│       └── ...
│
├── 📁 js/                           # ⚡ JavaScript modular
│   ├── 📄 admin.js                  # Lógica principal del dashboard
│   ├── 📄 admin_users.js            # Gestión de usuarios con tooltips
│   ├── 📄 admin_areas.js            # Gestión de áreas con colores
│   ├── 📄 admin_settings.js         # Configuración de perfil
│   ├── 📄 admin_sortable.js         # Drag & drop para formularios
│   ├── 📄 circle-corrector.js       # Sistema de círculos perfectos
│   ├── 📄 common.js                 # Funciones compartidas
│   ├── 📄 navbar.js                 # Funcionalidad de navegación
│   └── 📄 public_form.js            # Formularios públicos
│
├── 📁 css/                          # 🎨 Estilos modulares
│   ├── 📄 style.css                 # Estilos principales
│   ├── 📄 navbar.css                # Estilos de navegación
│   ├── 📄 area-colors.css           # Sistema de colores de áreas
│   └── 📄 rbac-permissions.css      # Estilos para RBAC y círculos
│
├── 📁 profile_images/               # 🖼️ Avatares de usuarios
├── 📁 downloads/                    # 📥 Archivos públicos y URLs
└── 📁 uploads/                      # 📤 Archivos subidos por usuarios
```

### 🔍 Archivos de Documentación Técnica

```
📁 docs/ (raíz del proyecto)
├── 📄 docs_rbac.md                           # 📖 Documentación completa RBAC
├── 📄 guia_admin_areas.md                    # 👨‍💼 Guía para administradores
├── 📄 guia_editor_areas.md                   # ✏️ Guía para editores
└── 📄 guia_backup.md                         # 💾 Guía del sistema de backup
```

---

## ⚡ Instalación Rápida

### 📋 Requisitos Previos

- **PHP 8.0+** con extensiones básicas habilitadas
- **Servidor Web** (Apache, Nginx, o servidor de desarrollo PHP)
- **Navegador moderno** con soporte para ES6+

### 🚀 Instalación en 3 Pasos

#### 1. **Descarga e Instalación**
```bash
# Clona el repositorio
git clone https://github.com/digiraldo/formularios-admin-rbac.git
cd formularios-admin-rbac

# O descarga el ZIP y extrae en tu servidor web
```

#### 2. **Configuración del Servidor**

<details>
<summary>🖥️ <strong>Laragon (Recomendado para Windows)</strong></summary>

```bash
# Coloca el proyecto en la carpeta www de Laragon
C:\laragon\www\form\

# Inicia Laragon y accede a:
http://localhost/form/
```
</details>

<details>
<summary>🏗️ <strong>XAMPP</strong></summary>

```bash
# Coloca el proyecto en htdocs
C:\xampp\htdocs\form\

# Inicia Apache y accede a:
http://localhost/form/
```
</details>

<details>
<summary>🌐 <strong>Servidor de Desarrollo PHP</strong></summary>

```bash
# Navega a la carpeta del proyecto
cd form/

# Inicia el servidor de desarrollo
php -S localhost:8000

# Accede a:
http://localhost:8000/
```
</details>

#### 3. **Primer Acceso**
```
🔗 URL: http://localhost/form/
👤 Usuario por defecto: centromateo
🔑 Contraseña: admin123
```

### ✅ Verificación de Instalación

Después de acceder al sistema, verifica que todo funcione correctamente:

1. **✅ Dashboard Principal** - Panel de administración carga correctamente
2. **✅ Gestión de Usuarios** - Lista de usuarios y roles visible
3. **✅ Gestión de Áreas** - Áreas con colores funcionando
4. **✅ Crear Formulario** - Editor drag & drop operativo
5. **✅ Modo Claro/Oscuro** - Toggle funcional en navbar

### 🔧 Configuración Adicional

<details>
<summary><strong>📁 Permisos de Archivos (Linux/macOS)</strong></summary>

```bash
# Asignar permisos de escritura a las carpetas de datos
chmod -R 755 data/
chmod -R 755 downloads/
chmod -R 755 uploads/
chmod -R 755 profile_images/

# Asegurar que el servidor web puede escribir
chown -R www-data:www-data data/ downloads/ uploads/ profile_images/
```
</details>

<details>
<summary><strong>⚙️ Configuración de PHP (Opcional)</strong></summary>

```ini
# Recomendaciones para php.ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 60
memory_limit = 256M
```
</details>

---

## 🎨 Personalización

- Cambia el logo y nombre de empresa en la configuración del formulario.
- Usa el modo claro/oscuro desde el navbar.
- Personaliza estilos en `css/style.css`.

---

## 🛡️ Seguridad y Buenas Prácticas

- Solo el administrador autenticado puede crear/editar formularios.
- Los archivos subidos se almacenan en `/downloads` y se renombran para evitar conflictos.
- Las URLs de descarga se gestionan en `download_urls.json` para mayor control.
- Validación y sanitización de entradas en backend y frontend.

---

---

## 🔐 Sistema RBAC Multi-Área Detallado

### 👥 Jerarquía de Roles

<table>
<tr>
<th>🏆 Rol</th>
<th>📋 Permisos</th>
<th>🎯 Alcance</th>
<th>🔧 Capacidades Especiales</th>
</tr>

<tr>
<td><strong>👑 Owner (Propietario)</strong></td>
<td>
• Control total del sistema<br>
• Gestión completa de usuarios<br>
• Acceso a todas las áreas<br>
• Auditoría y reportes globales
</td>
<td>🌐 Global</td>
<td>
• Crear/eliminar administradores<br>
• Reasignar entre áreas<br>
• Configuración del sistema<br>
• Permisos cruzados sin restricciones
</td>
</tr>

<tr>
<td><strong>👨‍💼 Admin (Administrador)</strong></td>
<td>
• Gestión de su(s) área(s)<br>
• Crear/gestionar editores<br>
• CRUD completo de formularios<br>
• Asignar permisos cruzados
</td>
<td>🏢 Por Área</td>
<td>
• Gestión multi-área (si está asignado)<br>
• Colaboración entre áreas<br>
• Reportes departamentales<br>
• Configuración de área
</td>
</tr>

<tr>
<td><strong>✏️ Editor</strong></td>
<td>
• CRUD de sus formularios<br>
• Responder formularios<br>
• Ver estadísticas propias<br>
• Recibir permisos cruzados
</td>
<td>📝 Personal</td>
<td>
• Colaboración por invitación<br>
• Acceso temporal a otros formularios<br>
• Edición colaborativa<br>
• Reportes de formularios propios
</td>
</tr>
</table>

### 🔄 Sistema de Permisos Cruzados

Los **permisos cruzados** permiten la colaboración controlada entre diferentes áreas:

#### 📝 **Flujo de Asignación:**
1. Un **Admin/Owner** selecciona un formulario de su área
2. Abre el modal de "Gestión de Permisos Cruzados"
3. Selecciona editores de otras áreas
4. Asigna permisos de edición específicos
5. El sistema registra la acción con **auditoría completa**

#### 🎯 **Casos de Uso:**
- **Formulario de HR** que necesita input de IT
- **Encuesta de Marketing** que requiere datos de Ventas
- **Evaluación multi-departamental** con múltiples responsables
- **Proyectos transversales** con equipos mixtos

#### 📊 **Auditoría Automática:**
```json
{
  "permisos_cruzados": [
    {
      "user_id": "editor_ventas_001",
      "asignado_por": "admin_marketing_001", 
      "fecha": "2025-05-31 14:30:00",
      "accion": "asignado",
      "area_origen": "marketing",
      "area_destino": "ventas"
    }
  ]
}
```

### 🎨 Sistema de Colores y Visualización

#### 🌈 **Colores Inteligentes:**
- **Asignación automática** de colores a nuevas áreas
- **Cálculo de contraste** para legibilidad óptima
- **Consistencia visual** en toda la aplicación
- **Badges coloridos** para identificación rápida

#### 👤 **Avatares y Tooltips:**
- **Círculos perfectos** garantizados para todas las imágenes
- **Tooltips informativos** con datos del usuario
- **Iniciales automáticas** como fallback
- **Sistema robusto** contra deformaciones

### 📈 **Casos de Uso Empresariales**

<details>
<summary><strong>🏢 Empresa Multi-Departamental</strong></summary>

**Escenario:** Una empresa con 5 departamentos necesita gestionar formularios específicos pero también colaborar en proyectos transversales.

**Implementación:**
- 1 Owner (CEO/CTO)
- 5 Admins (Jefes de departamento)  
- 20 Editores (Empleados)
- Áreas: Marketing, Ventas, IT, HR, Finanzas

**Beneficios:**
- Autonomía departamental
- Colaboración controlada
- Auditoría completa
- Escalabilidad
</details>

<details>
<summary><strong>🎓 Institución Educativa</strong></summary>

**Escenario:** Universidad con múltiples facultades que necesitan formularios de inscripción, evaluación y encuestas.

**Implementación:**
- 1 Owner (Rector/Vicerrector)
- 6 Admins (Decanos de facultad)
- 30 Editores (Profesores/Coordinadores)
- Áreas: Ingeniería, Medicina, Derecho, Economía, Arte, Psicología

**Beneficios:**
- Gestión académica independiente
- Formularios transversales (becas, bienestar)
- Control de acceso estudiantil
- Reportes institucionales
</details>

<details>
<summary><strong>🏛️ Institución Pública</strong></summary>

**Escenario:** Alcaldía con múltiples secretarías que requieren formularios ciudadanos y gestión interna.

**Implementación:**
- 1 Owner (Alcalde/Secretario General)
- 8 Admins (Secretarios de despacho)
- 40 Editores (Funcionarios)
- Áreas: Salud, Educación, Obras, Hacienda, Gobierno, Desarrollo, Ambiente, Cultura

**Beneficios:**
- Tramites ciudadanos unificados
- Gestión inter-secretarías
- Transparencia y auditoría
- Eficiencia administrativa
</details>

---

## 🚀 Guía de Inicio Rápido

### 🎯 **Primeros Pasos (5 minutos)**

#### 1. **Acceso Inicial**
```
🌐 URL: http://localhost/form/
👤 Usuario: centromateo
🔑 Contraseña: admin123
```

#### 2. **Configuración Básica**
1. **Crear tu primera área:**
   - Ve a "Gestión de Áreas" → "Crear Nueva Área"
   - Asigna un nombre y selecciona un color
   - ✅ Área creada

2. **Crear tu primer usuario:**
   - Ve a "Gestión de Usuarios" → "Crear Nuevo Usuario"
   - Asigna rol y área
   - ✅ Usuario listo

3. **Crear tu primer formulario:**
   - Ve a "Dashboard" → "Crear Formulario"
   - Arrastra campos, configura y guarda
   - ✅ Formulario publicado

#### 3. **Prueba el Sistema**
- Comparte el enlace público del formulario
- Recibe respuestas y revisa las gráficas
- ✅ ¡Sistema funcionando!

### 📚 **Documentación Técnica Completa**

Para implementaciones avanzadas, consulta la documentación especializada:

- **[📖 `docs_rbac.md`](./docs_rbac.md)** - Documentación completa del sistema RBAC
- **[👨‍💼 `guia_admin_areas.md`](./guia_admin_areas.md)** - Guía para administradores
- **[✏️ `guia_editor_areas.md`](./guia_editor_areas.md)** - Guía para editores
- **[💾 `guia_backup.md`](./guia_backup.md)** - Guía del sistema de backup y recuperación  
- **[🔧 `mejoras_rbac_areas_resumen_tecnico.md`](./mejoras_rbac_areas_resumen_tecnico.md)** - Mejoras técnicas
- **[🎨 `implementacion_colores_areas.md`](./implementacion_colores_areas.md)** - Sistema de colores
- **[⭕ `SOLUCION_CIRCULOS_PERFECTOS_RESUMEN.md`](./SOLUCION_CIRCULOS_PERFECTOS_RESUMEN.md)** - Fix círculos perfectos

---

## 🎨 Personalización Avanzada

### 🌈 **Temas y Colores**
```css
/* Personalización en css/style.css */
:root {
  --primary-color: #4285F4;
  --secondary-color: #34A853;
  --accent-color: #EA4335;
  --background-color: #f8f9fa;
}
```

### 🏢 **Branding Corporativo**
- Reemplaza logos en `/profile_images/`
- Configura colores de área según tu identidad
- Personaliza footer con información de tu empresa

### 🔧 **Configuración Avanzada**
```php
// Configuración en header_includes.php
$config = [
    'max_file_size' => '10MB',
    'allowed_extensions' => ['pdf', 'docx', 'jpg', 'png'],
    'session_timeout' => 3600, // 1 hora
    'default_area_color' => '#4285F4'
];
```

---

## 🛡️ Seguridad y Mejores Prácticas

### 🔒 **Características de Seguridad Implementadas**

- **✅ Autenticación robusta** con sesiones PHP seguras
- **✅ Sanitización de datos** contra XSS e inyección SQL
- **✅ Validación frontend y backend** en todos los formularios
- **✅ Control de acceso granular** por roles y áreas
- **✅ Auditoría completa** de acciones críticas
- **✅ URLs únicas** para formularios públicos
- **✅ Gestión segura de archivos** con renombrado y validación

### 🔧 **Recomendaciones de Producción**

<details>
<summary><strong>🌐 Configuración del Servidor Web</strong></summary>

```apache
# .htaccess para Apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Proteger archivos sensibles
<Files "*.json">
    Require all denied
</Files>

<Directory "data/">
    Require all denied
</Directory>
```
</details>

<details>
<summary><strong>🔐 Hardening de PHP</strong></summary>

```ini
# Configuración php.ini para producción
expose_php = Off
display_errors = Off
log_errors = On
session.cookie_secure = 1
session.cookie_httponly = 1
session.use_strict_mode = 1
```
</details>

<details>
<summary><strong>📁 Backup Automático</strong></summary>

```bash
# Script de backup (backup.sh)
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
tar -czf "backup_formularios_$DATE.tar.gz" data/ uploads/ profile_images/
echo "Backup creado: backup_formularios_$DATE.tar.gz"
```
</details>

---

## 🆕 Changelog y Mejoras Recientes

### **🎉 Versión 2.0 - RBAC Multi-Área (Mayo 2025)**

#### ✨ **Nuevas Características:**
- **🏢 Sistema RBAC Multi-Área completo** con 3 niveles de roles
- **🎨 Colores personalizables** para áreas con contraste automático
- **🔄 Permisos cruzados** entre áreas con auditoría
- **👤 Gestión visual de usuarios** con avatares y tooltips
- **⭕ Círculos perfectos** garantizados para todas las imágenes
- **📱 Diseño responsive** mejorado para móviles

#### 🔧 **Mejoras Técnicas:**
- **🐛 Corrección del modal** de permisos cruzados
- **🔍 Sistema de búsqueda** avanzado en DataTables
- **⚡ Optimización de carga** para grandes volúmenes de datos
- **🛡️ Validaciones robustas** frontend y backend
- **📊 Gráficas mejoradas** con Chart.js 4.x

#### 🗂️ **Estructura Modular:**
- **📁 Organización mejorada** de archivos JavaScript
- **🎨 CSS modular** por funcionalidades
- **📖 Documentación técnica** completa y actualizada

---

## 🤝 Contribución y Desarrollo

### 🛠️ **Para Desarrolladores**

```bash
# Clonar y configurar entorno de desarrollo
git clone https://github.com/digiraldo/formularios-admin-rbac.git
cd formularios-admin-rbac

# Instalar dependencias de desarrollo (opcional)
npm install # Para herramientas de build

# Iniciar servidor de desarrollo
php -S localhost:8000
```

### 📝 **Reportar Issues**
- **🐛 Bugs:** Usa el [issue tracker](https://github.com/digiraldo/formularios-admin-rbac/issues)
- **💡 Features:** Propón nuevas funcionalidades
- **📖 Docs:** Mejoras en documentación

### 🔄 **Pull Requests**
1. Fork del repositorio
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit de cambios (`git commit -am 'Añadir nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

---

## 🎉 Créditos y Reconocimientos

<div align="center">

### 👨‍💻 **Desarrollado con ❤️ por**

**[DiGiraldo](https://github.com/digiraldo)**  
*Full Stack Developer & System Architect*

---

### 🌟 **Tecnologías Open Source Utilizadas**

![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white) 
![Bootstrap](https://img.shields.io/badge/Bootstrap-7952B3?style=flat-square&logo=bootstrap&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat-square&logo=javascript&logoColor=black)
![Chart.js](https://img.shields.io/badge/Chart.js-FF6384?style=flat-square&logo=chart.js&logoColor=white)

**Agradecimientos especiales a la comunidad open source**

</div>

---

## 📞 Soporte y Contacto

<div align="center">

### 🆘 **¿Necesitas Ayuda?**

**📧 Email:** [contacto@digiraldo.com](mailto:contacto@digiraldo.com)  
**🐛 Issues:** [GitHub Issues](https://github.com/digiraldo/formularios-admin-rbac/issues)  
**💬 Discusiones:** [GitHub Discussions](https://github.com/digiraldo/formularios-admin-rbac/discussions)

### 🌟 **¿Te Gusta el Proyecto?**

⭐ **¡Dale una estrella en GitHub!**  
🔄 **Compártelo con tu equipo**  
💝 **Contribuye con mejoras**

---

## 📄 Licencia

Este proyecto está bajo la [Licencia MIT](LICENSE) - ver el archivo LICENSE para más detalles.

---

<div align="center">

**🚀 ¡Gracias por usar Formularios Admin RBAC! 🚀**

*Tu solución completa para gestión de formularios empresariales*

[![GitHub Stars](https://img.shields.io/github/stars/digiraldo/formularios-admin-rbac?style=social)](https://github.com/digiraldo/formularios-admin-rbac)
[![GitHub Forks](https://img.shields.io/github/forks/digiraldo/formularios-admin-rbac?style=social)](https://github.com/digiraldo/formularios-admin-rbac/fork)

</div>
