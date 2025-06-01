# 💾 Guía del Sistema de Backup y Recuperación

## 📋 Descripción General

El sistema de backup permite a los usuarios con rol **Owner (Propietario)** crear copias de seguridad completas del sistema y restaurar datos desde backups anteriores. Esta funcionalidad es crítica para la protección de datos y la continuidad del negocio.

---

## 🔐 Permisos y Acceso

### ✅ Quién puede usar el sistema de backup:
- **Solo usuarios con rol "Owner"** tienen acceso completo
- Los administradores y editores NO pueden acceder a esta funcionalidad

### 🚫 Restricciones de seguridad:
- Validación de permisos a nivel de página y API
- Autenticación requerida en cada operación
- Backup automático antes de cualquier restauración

---

## 📁 Directorios Incluidos en el Backup

El sistema incluye automáticamente los siguientes directorios críticos:

### 🗃️ **data/**
- `users.json` - Base de datos de usuarios y roles
- `areas.json` - Configuración de áreas de trabajo
- `forms/` - Todos los formularios creados
- `responses/` - Respuestas de usuarios a formularios
- `backup_history.json` - Historial de operaciones de backup

### 📥 **downloads/**
- Archivos públicos descargables
- Logs del sistema
- Configuraciones de descarga

### 👤 **profile_images/**
- Avatares de usuarios
- Imágenes de perfil del sistema

### 📎 **uploads/**
- Archivos subidos por usuarios en formularios
- Documentos adjuntos

---

## 🔄 Funcionalidades Principales

### 📤 **Exportar Backup**

#### Proceso automático:
1. **Validación de permisos** - Verificar rol de owner
2. **Creación del ZIP** - Compresión de todos los directorios
3. **Metadata del backup** - Información del sistema y timestamp
4. **Descarga automática** - El archivo se descarga directamente
5. **Registro en historial** - Log de la operación

#### Características:
- **Nombre único**: `backup_formularios_rbac_YYYY-MM-DD_HH-mm-ss.zip`
- **Compresión optimizada**: Reducción significativa del tamaño
- **Metadata incluida**: Información del sistema y versión
- **Descarga segura**: Sin almacenamiento temporal en servidor

### 📥 **Importar Backup**

#### Proceso de restauración:
1. **Validación del archivo** - Verificar que sea un ZIP válido del sistema
2. **Backup automático** - Crear copia del estado actual antes de restaurar
3. **Extracción controlada** - Descompresión en directorio temporal
4. **Validación de contenido** - Verificar estructura y archivos
5. **Restauración completa** - Reemplazo de directorios existentes
6. **Limpieza automática** - Eliminación de archivos temporales

#### Medidas de seguridad:
- **Confirmación obligatoria** - Checkbox de confirmación requerido
- **Doble confirmación** - Popup de advertencia adicional
- **Backup preventivo** - Copia automática antes de restaurar
- **Rollback disponible** - Posibilidad de volver al estado anterior

---

## 🎯 Cómo Usar el Sistema

### 📤 **Para Exportar un Backup:**

1. **Acceder al panel**:
   - Ir a la sección "Backups" en el menú superior
   - Solo visible para usuarios Owner

2. **Crear backup**:
   - Hacer clic en "Exportar Backup"
   - Esperar a que se complete la barra de progreso
   - El archivo se descargará automáticamente

3. **Verificar descarga**:
   - Revisar la carpeta de descargas del navegador
   - El archivo tendrá formato: `backup_formularios_rbac_[fecha].zip`

### 📥 **Para Importar un Backup:**

1. **Preparar el archivo**:
   - Tener el archivo ZIP de backup disponible
   - Verificar que sea un backup válido del sistema

2. **Subir e importar**:
   - Hacer clic en "Seleccionar archivo" en la sección de importación
   - Elegir el archivo ZIP de backup
   - ✅ **IMPORTANTE**: Marcar la casilla de confirmación
   - Hacer clic en "Importar Backup"

3. **Confirmar operación**:
   - Leer cuidadosamente la advertencia del popup
   - Confirmar que desea proceder con la restauración
   - Esperar a que se complete el proceso

---

## 📊 Historial y Estadísticas

### 📈 **Panel de Estadísticas**
- **Total de archivos**: Número de archivos en el sistema
- **Tamaño total**: Espacio ocupado por todos los directorios
- **Último backup**: Fecha y hora del último backup realizado

### 📋 **Historial de Operaciones**
Cada operación de backup/restauración se registra con:
- **Tipo de operación**: Exportar o Importar
- **Nombre del archivo**: Identificador único del backup
- **Tamaño**: Tamaño del archivo procesado
- **Fecha y hora**: Timestamp de la operación
- **Usuario**: Quién realizó la operación
- **Estado**: Éxito o error

---

## ⚠️ Advertencias Importantes

### 🚨 **Antes de Restaurar un Backup:**
- ⚠️ **TODOS los datos actuales serán reemplazados**
- ⚠️ **Esta acción NO se puede deshacer fácilmente**
- ⚠️ **Asegúrate de tener un backup reciente del estado actual**
- ⚠️ **Verifica que el backup a restaurar sea el correcto**

### 🛡️ **Mejores Prácticas:**
- **Backups regulares**: Crear backups antes de cambios importantes
- **Verificar archivos**: Comprobar el contenido antes de restaurar
- **Documentar cambios**: Mantener registro de qué se restauró y cuándo
- **Probar en ambiente de desarrollo**: Si es posible, probar la restauración primero

---

## 🔧 Resolución de Problemas

### ❌ **Error: "Acceso denegado"**
- **Causa**: Usuario no tiene rol de Owner
- **Solución**: Solo los propietarios pueden usar backups

### ❌ **Error: "Archivo no válido"**
- **Causa**: El archivo no es un ZIP o está corrupto
- **Solución**: Verificar que sea un backup válido del sistema

### ❌ **Error: "No se pudo crear el backup"**
- **Causa**: Problemas de permisos o espacio en disco
- **Solución**: Verificar permisos del servidor y espacio disponible

### ❌ **Error: "Falló la restauración"**
- **Causa**: Estructura de backup incorrecta o permisos
- **Solución**: El sistema automáticamente mantiene el backup previo

---

## 📱 Soporte y Contacto

Para problemas relacionados con el sistema de backup:
1. Verificar los logs del sistema
2. Revisar el historial de operaciones
3. Contactar al administrador del sistema

---

*💡 **Tip**: Mantén siempre backups múltiples y en diferentes ubicaciones para máxima seguridad de datos.*
