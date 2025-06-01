# Guía para Administradores: Sistema RBAC Multi-Área

## Introducción para Administradores

Como administrador en el sistema de formularios, tienes responsabilidades y permisos específicos asociados a una o más áreas. Esta guía te ayudará a entender cómo funciona el sistema de áreas y cómo gestionar los formularios y usuarios dentro de tus áreas asignadas.

## Tu Rol como Administrador

Como administrador, puedes:

1. **Crear formularios** en las áreas que tienes asignadas
2. **Editar formularios** dentro de tus áreas, incluyendo aquellos creados por editores
3. **Eliminar formularios** dentro de tus áreas
4. **Asignar editores** a tus áreas
5. **Gestionar permisos** de edición de formularios

## Áreas Asignadas

Tu cuenta tiene asignada una o más áreas específicas mediante la propiedad `areas_admin` en tu perfil. Estas áreas determinan:

- Qué formularios puedes ver en el panel de administración
- Dónde puedes crear nuevos formularios
- Qué editores puedes gestionar

### Importancia de las Áreas

Las áreas son fundamentales en el sistema RBAC ya que:

1. **Segmentan los permisos**: Delimitan el alcance de tus acciones
2. **Organizan los formularios**: Agrupan formularios relacionados
3. **Estructuran la organización**: Reflejan la estructura organizacional real

### Verificar tus Áreas Asignadas

Para ver qué áreas tienes asignadas:

1. Haz clic en "Crear Nuevo Formulario"
2. El selector de áreas mostrará todas tus áreas disponibles

## Solución de Problemas Comunes

### No puedo crear formularios - "Solo puedes crear formularios en tus áreas"

Este error ocurre cuando:

1. No tienes áreas asignadas a tu cuenta
2. Intentas crear un formulario en un área que no te pertenece

**Solución**:
- Contacta con el propietario del sistema (Owner) para que te asigne las áreas necesarias
- Verifica que estás seleccionando un área que te pertenece

### No veo ningún formulario en mi panel

Esto puede ocurrir si:
1. No hay formularios creados en tus áreas
2. No tienes áreas asignadas

**Solución**:
- Crea nuevos formularios en tus áreas
- Contacta con el propietario para verificar tus asignaciones de áreas

### No puedo editar un formulario específico

Esto puede ocurrir si:
1. El formulario pertenece a otra área
2. No tienes los permisos necesarios para ese formulario

**Solución**:
- Verifica el área del formulario
- Contacta con el propietario si necesitas acceso especial

## Gestión de Editores

Como administrador, puedes:

1. **Asignar áreas** a los editores bajo tu supervisión
2. **Conceder permisos** para que otros usuarios editen formularios específicos
3. **Supervisar actividad** de los editores en tus áreas

### Conceder Permisos de Edición

Para conceder permisos de edición a un formulario:
1. Localiza el formulario en la lista
2. Haz clic en el botón "Gestionar Permisos" (ícono de escudo)
3. Selecciona los usuarios que deseas que tengan acceso
4. Los cambios se guardan automáticamente

## Buenas Prácticas

1. **Organiza tus formularios** de manera lógica dentro de tus áreas
2. **Asigna permisos con cuidado**, siguiendo el principio de menor privilegio
3. **Revisa periódicamente** los permisos concedidos
4. **Documenta** tus decisiones de gestión de permisos

## Contacto para Soporte

Si encuentras problemas con el sistema de áreas, contacta al propietario del sistema (Owner) proporcionando:
1. Tu nombre de usuario
2. Las áreas que necesitas
3. Descripción del problema que estás experimentando

---

Este documento es parte de la documentación del sistema RBAC Multi-Área. Para más información técnica, consulta `rbac_areas_guia.md` y `docs_rbac.md`.
