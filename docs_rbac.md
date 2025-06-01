# Sistema de Control de Acceso Basado en Roles (RBAC)

## Introducción

El sistema implementa un Control de Acceso Basado en Roles (RBAC) con un enfoque multi-área que permite gestionar permisos a nivel granular. Esto asegura que los usuarios solo puedan acceder y modificar los formularios que estén bajo su responsabilidad según el área a la que pertenezcan.

## Roles del Sistema

El sistema tiene tres niveles de roles:

1. **Owner** (Propietario):
   - Acceso completo a todas las áreas y funcionalidades.
   - Puede crear, editar y eliminar cualquier formulario.
   - Puede gestionar usuarios y áreas.

2. **Admin** (Administrador):
   - Acceso limitado a las áreas específicas que tiene asignadas.
   - Puede crear, editar y eliminar formularios en sus áreas.
   - Puede gestionar editores dentro de sus áreas.

3. **Editor**:
   - Acceso limitado a las áreas específicas que tiene asignadas.
   - Puede crear y editar formularios en sus áreas.
   - Solo puede ver y modificar formularios de los que es creador o tiene permisos especiales.

## Sistema de Áreas

Las áreas son unidades organizacionales que agrupan formularios y usuarios. Cada formulario debe pertenecer a una única área.

### Estructura de Áreas

Cada área se define en el archivo `data/areas.json` con la siguiente estructura:

```json
{
    "id": "area001",
    "name": "Área de Marketing",
    "description": "Descripción del área",
    "admins": ["user_admin1", "user_multi1"],
    "editors": ["user_editor1"],
    "color": "#4285F4"
}
```

### Asignación de Usuarios a Áreas

Los usuarios se asignan a áreas según su rol:

- **Admins**: Se asignan usando la propiedad `areas_admin` en el usuario y también en la propiedad `admins` del área.
- **Editors**: Se asignan usando la propiedad `areas_editor` en el usuario y también en la propiedad `editors` del área.

## Configuración del Sistema RBAC

### 1. Crear Áreas

Primero, defina las áreas de su organización en el archivo `data/areas.json`.

### 2. Crear Usuarios

Cree usuarios con diferentes roles en `data/users.json`. Asegúrese de asignar las áreas correspondientes:

```json
{
    "id": "user_admin1",
    "username": "admin_marketing",
    "password": "$2y$10$...", // Contraseña encriptada
    "role": "admin",
    "areas_admin": ["area001"],
    "created_at": "2025-05-20"
}
```

### 3. Asociar Usuarios a Áreas

Actualice el archivo `data/areas.json` para incluir los IDs de usuario en las propiedades `admins` y `editors`.

## Flujo de Trabajo para Formularios

### Creación de Formularios

1. Cuando un usuario crea un formulario, debe seleccionar un área.
2. Solo puede seleccionar áreas a las que tenga acceso según su rol.
3. Si es Owner, puede seleccionar cualquier área.
4. Si es Admin, solo puede seleccionar áreas donde esté asignado como administrador.
5. Si es Editor, solo puede seleccionar áreas donde esté asignado como editor.

### Edición de Formularios

Los permisos para editar un formulario se determinan según:

1. **Owner**: Puede editar cualquier formulario.
2. **Admin**: 
   - Puede editar formularios de su área.
   - Puede editar formularios que ha creado.
3. **Editor**: 
   - Solo puede editar formularios que ha creado.
   - Puede editar formularios a los que se le ha dado acceso explícito.

### Gestión de Permisos

Los administradores y propietarios pueden otorgar permisos de edición a usuarios específicos para formularios individuales:

1. En la vista de formularios, haga clic en el botón de permisos.
2. Seleccione los usuarios que desea añadir como editores.
3. Los cambios se aplican automáticamente.

## Registros y Auditoría

El sistema mantiene registros de:
- Quién creó cada formulario (`creator_id`)
- Quién realizó la última actualización (`updated_id`)
- Todos los usuarios con permisos para editar (`creator_id` como array)

## Solución de Problemas Comunes

### "Solo puedes crear formularios en tus áreas"

Este error aparece cuando:
1. Un usuario intenta crear un formulario sin seleccionar un área
2. Un usuario intenta crear un formulario en un área a la que no tiene acceso

**Solución**: Asegúrese de que el usuario tenga al menos un área asignada y seleccione una de esas áreas al crear el formulario.

### Usuario sin acceso a formularios

Si un usuario no puede ver o editar formularios:

1. Verifique que el usuario tenga el rol correcto.
2. Verifique que el usuario tenga áreas asignadas.
3. Para Admins: Verifique que aparezcan en la propiedad `admins` del área y tengan la propiedad `areas_admin`.
4. Para Editors: Verifique que aparezcan en la propiedad `editors` del área y tengan la propiedad `areas_editor`.

## Secuencia Recomendada de Configuración

Para configurar el sistema desde cero:

1. Cree el usuario Owner primero.
2. Defina las áreas de la organización.
3. Cree los usuarios Admin y asígnelos a sus áreas.
4. Cree los usuarios Editor y asígnelos a sus áreas.
5. Asegúrese de que los archivos JSON estén correctamente formateados.