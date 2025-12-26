# Documentación Técnica - Estación Meteorológica IoT

## 1. Arquitectura General
El sistema sigue una arquitectura de 3 capas (MVC simplificado):
- **Base de Datos (MySQL)**: Almacenamiento persistente con optimización de índices.
- **Backend (PHP)**: Lógica de negocio, validación de API, y cálculo de predicciones (EMA).
- **Frontend (HTML/JS)**: Interfaz de usuario "Clean UI" con actualizaciones asíncronas (AJAX).

## 2. Flujo de Datos
1. **Ingesta de Datos**:
   - El dispositivo IoT envía un JSON Vía POST a `/api/post-reading.php`.
   - El sistema valida la `api_key` contra la tabla `devices` utilizando el índice `idx_api_key` para máxima velocidad.
   - Se recupera la última lectura para comparaciones (alertas de cambio).
   - Se inserta la nueva lectura en `weather_readings`.

2. **Procesamiento de Alertas**:
   - Inmediatamente después de la inserción, el sistema consulta las alertas activas para el dispositivo.
   - Se evalúan las condiciones (`above`, `below`, `change`) en memoria.
   - Si se cumple una condición, se registra en `alert_logs` manteniendo integridad referencial.

3. **Visualización y Predicción**:
   - El Dashboard consulta `/api/get-data.php` periódicamente (cada 30s).
   - Se recuperan las últimas 50 lecturas.
   - Se calcula el **Promedio Móvil Exponencial (EMA)** en el servidor (PHP) con $\alpha=0.3$.
   - El frontend renderiza los datos históricos y la línea de tendencia EMA usando Chart.js.

## 3. Seguridad
- **Inyección SQL**: Prevenida totalmente mediante el uso de **Prepared Statements** (PDO) en todas las consultas (`includes/DB.php`).
- **XSS**: El frontend asigna valores vía `textContent` en lugar de `innerHTML` para prevenir ejecución de scripts maliciosos.
- **Validación de Tipos**: El API valida que los datos numéricos lo sean realmente antes de procesarlos.
- **Integridad**: Uso de claves foráneas (`FOREIGN KEY`) y transacciones implícitas para asegurar consistencia.

## 4. Optimización
- **Índices**: 
  - `alerts(device_id, is_active)`: Para recuperar rápidamente reglas de alerta.
  - `weather_readings(device_id, created_at)`: Para consultas de series temporales y Dashboard.
- **Modularidad**:
  - `DB.php`: Singleton para conexión eficiente.
  - `WeatherService.php`: Capa de servicio para lógica reutilizable.
