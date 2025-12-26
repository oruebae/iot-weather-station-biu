# Documentación Técnica - IoT Weather Station

## 1. Arquitectura del Sistema
El sistema sigue una arquitectura modular en 3 capas:
1.  **Frontend (Presentación)**: HTML5 + TailwindCSS + Chart.js. Comunicación asíncrona vía Fetch API.
2.  **Backend (Lógica & API)**: PHP Vanilla 8.x. Endpoints RESTful para ingesta (`post-reading.php`) y consumo (`get-data.php`).
3.  **Persistencia (Datos)**: MySQL 8.x con índices optimizados para series temporales.

## 2. Seguridad y Seguridad Web (OWASP)
Se han implementado las siguientes medidas de mitigación:

*   **SQL Injection (SQLi)**: Uso obligatorio de Prepared Statements (PDO) en todas las consultas.
*   **Cross-Site Scripting (XSS)**:
    *   Salida de datos JSON codificada.
    *   Validación de tipos de datos estricta en el backend (is_numeric).
    *   El frontend manipula el DOM usando `textContent` en lugar de `innerHTML` para datos de usuario (exepto los iconos que son estáticos).
*   **Autenticación API**: Validación de `api_key` contra base de datos índice hash (`idx_api_key`) para cada petición de escritura.
*   **Manejo de Errores**: Los errores de BD se loguean en el error_log del servidor y no se exponen detalles al cliente (solo mensajes genéricos).

## 3. Flujo de Datos

### A. Ingesta de Datos (IoT Device -> Server)
1.  El dispositivo envía POST JSON a `/api/post-reading.php`.
2.  **Validación**: Se verifica la API Key.
3.  **Persistencia**: Se guarda la lectura en `weather_readings`.
4.  **Procesamiento de Alertas**:
    *   Se consultan las reglas activas de la tabla `alerts`.
    *   Se compara el valor actual (o el cambio vs anterior).
    *   Si se cumple la condición, se inserta en `alert_logs`.
    *   El endpoint retorna los IDs generados y estado de alertas.

### B. Visualización (Cliente -> Dashboard)
1.  El Dashboard solicita datos a `/api/get-data.php?date=YYYY-MM-DD`.
2.  **Backend**:
    *   Recupera lecturas del día (o últimas disponibles).
    *   Calcula EMA (Promedio Móvil Exponencial) en PHP (α=0.3).
    *   Determina el "Estado del Clima" (Lluvioso, Soleado, etc.) basado en reglas estrictas.
    *   Retorna JSON estructurado.
3.  **Frontend**:
    *   Renderiza métricas actuales y estado.
    *   Grafica series "Real" vs "EMA" usando Chart.js.
    *   Muestra lista de alertas recientes.

## 4. Estructura de Base de Datos (Optimización)
*   **Indices**:
    *   `idx_device_date`: Optimiza consultas de rango de fechas y ordenamiento cronológico.
    *   `idx_api_key`: Búsqueda O(1) para autenticación.
*   **Integridad Ref.**: Claves foráneas con `ON DELETE CASCADE` para mantener consistencia.

## 5. despliegue
1.  Ejecutar script `database/schema.sql` en MySQL.
2.  Configurar credenciales en `config.php`.
3.  Asegurar permisos de `error_log`.
