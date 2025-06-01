# Configuración del Sistema de Backup

## Directorios incluidos en el backup
- data/ (Base de datos JSON)
- downloads/ (Archivos descargables)
- profile_images/ (Avatares de usuarios)
- uploads/ (Archivos subidos)

## Configuración de Apache (.htaccess)
- Acceso restringido al directorio backups/
- Solo archivos ZIP son accesibles directamente
- Prevención de listado de directorios

## Configuración de PHP
- Máximo tamaño de archivo de upload: verificar php.ini
- Tiempo límite de ejecución: verificar para backups grandes
- Memoria disponible: verificar para operaciones ZIP

## Mantenimiento recomendado
- Limpiar backups antiguos periódicamente
- Verificar integridad de backups regularmente
- Mantener backups externos del servidor

## Logs del sistema
- backup_history.json: Historial de operaciones
- Revisar regularmente para detectar problemas

## Contacto para soporte
- Administrador del sistema
- Documentación en guia_backup.md
