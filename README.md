# 📦 Plugin Última Milla - WordPress

Sistema completo de gestión de servicios de última milla (delivery tracking) con seguimiento de estados en tiempo real para clientes, mensajeros y administradores.

---

## 🛠️ Tecnologías Utilizadas

### **Backend (WordPress/PHP)**
- **WordPress 5.0+** - Framework base del plugin
- **PHP 7.4+** - Lenguaje de programación
- **MySQL/MariaDB** - Base de datos (tabla personalizada para campos de formularios)
- **WordPress REST API** - AJAX handlers para operaciones asíncronas

### **Frontend**
- **Bootstrap 5.3.2** - Framework CSS (solo para formularios públicos)
- **Estilos Nativos de WordPress** - Para el área de administración
- **jQuery** - Manipulación del DOM y AJAX
- **DataTables 1.13.8** - Tablas interactivas con búsqueda, ordenamiento y paginación
- **SweetAlert2 v11** - Alertas y confirmaciones elegantes

### **Iconos y UI**
- **Dashicons** - Sistema de iconos oficial de WordPress
- **CSS3** - Animaciones y transiciones personalizadas

---

## 🏗️ Arquitectura del Plugin

### **Estructura de Archivos**

```
ultima-milla/
├── ultima-milla.php                    # Archivo principal del plugin
├── README.md                           # Documentación (este archivo)
├── index.php                           # Seguridad (previene acceso directo)
│
├── admin/                              # Área de administración
│   ├── class-admin-solicitudes.php     # Gestión de solicitudes
│   └── class-admin-formularios.php     # Constructor de formularios
│
├── includes/                           # Lógica del plugin
│   ├── class-roles.php                 # Gestión de roles y capacidades
│   ├── class-post-types.php            # Custom Post Types
│   ├── class-shortcodes.php            # Shortcodes para frontend
│   ├── class-ajax-handlers.php         # Handlers AJAX
│   └── class-form-builder.php          # Utilidades del constructor
│
└── assets/                             # Recursos estáticos
    ├── css/
    │   ├── admin.css                   # Estilos del área de admin
    │   └── frontend.css                # Estilos del frontend público
    └── js/
        ├── admin.js                    # JavaScript del admin
        └── frontend.js                 # JavaScript del frontend
```

### **Base de Datos**

#### **Custom Post Types**
- **`um_solicitud`** - Almacena las solicitudes de servicio
- **`um_formulario`** - Almacena los formularios creados

#### **Tabla Personalizada**
- **`wp_um_form_fields`** - Almacena los campos dinámicos de cada formulario

```sql
CREATE TABLE wp_um_form_fields (
    id bigint(20) AUTO_INCREMENT PRIMARY KEY,
    form_id bigint(20) NOT NULL,
    field_type varchar(50) NOT NULL,
    field_label varchar(255) NOT NULL,
    field_name varchar(100) NOT NULL,
    field_required tinyint(1) DEFAULT 0,
    field_options text,
    field_order int(11) DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP
);
```

#### **Post Meta (Metadatos de Solicitudes)**
- `_um_form_id` - ID del formulario usado
- `_um_cliente_id` - ID del usuario cliente
- `_um_mensajero_id` - ID del mensajero asignado
- `_um_codigo_seguimiento` - Código único (ej: UM-ABC12345)
- `_um_direccion_origen` - Dirección de recogida
- `_um_direccion_destino` - Dirección de entrega
- `_um_descripcion` - Detalles del servicio
- `_um_estado` - Estado actual (solicitado, en_curso, entregado, cancelado)
- `_um_fecha_solicitud` - Fecha de creación
- `_um_fecha_programada` - Fecha programada (opcional)
- `_um_fecha_entrega` - Fecha de entrega real
- `_um_campo_*` - Campos personalizados dinámicos

---

## 👥 Roles y Capacidades

### **Roles Personalizados Creados**

#### **1. Cliente Última Milla (`um_cliente`)**
**Capacidades:**
- `read` - Acceso básico al sistema
- `um_crear_solicitud` - Crear solicitudes de servicio
- `um_ver_propias_solicitudes` - Ver sus propias solicitudes

**Acceso:**
- ✅ Ver panel "Solicitudes" (solo sus solicitudes)
- ✅ Usar formularios públicos para crear solicitudes
- ❌ No puede ver solicitudes de otros clientes

#### **2. Mensajero (`um_mensajero`)**
**Capacidades:**
- `read` - Acceso básico al sistema
- `um_ver_solicitudes_asignadas` - Ver solicitudes asignadas
- `um_actualizar_estado_solicitud` - Cambiar estado de solicitudes

**Acceso:**
- ✅ Ver panel "Solicitudes" (solo las asignadas a él/ella)
- ✅ Actualizar estado (En Curso, Entregado, Cancelado)
- ❌ No puede asignar mensajeros
- ❌ No puede crear formularios

#### **3. Administrador (`administrator`)**
**Capacidades adicionales:**
- `um_gestionar_solicitudes` - Gestión completa
- `um_asignar_mensajeros` - Asignar mensajeros
- `um_gestionar_formularios` - Crear/editar formularios

**Acceso:**
- ✅ Acceso completo a todas las solicitudes
- ✅ Asignar mensajeros
- ✅ Crear y editar formularios
- ✅ Ver panel de formularios
- ✅ Cambiar cualquier estado

---

## 🎯 Funcionalidades Implementadas

### **1. Constructor de Formularios Dinámico**

El administrador puede crear formularios personalizados desde `wp-admin`.

**Tipos de campos disponibles:**
- ✅ Texto
- ✅ Email
- ✅ Teléfono
- ✅ Número
- ✅ Área de texto
- ✅ Lista desplegable (select)
- ✅ Fecha

**Campos estándar incluidos automáticamente:**
- Dirección de Origen (obligatorio)
- Dirección de Destino (obligatorio)
- Descripción del Servicio
- Fecha Programada (opcional)

**Shortcode generado automáticamente:**
```
[ultima_milla_form id="X"]
```

### **2. Módulo Cliente (Frontend)**

**Shortcode 1: Formulario de Solicitud**
```php
[ultima_milla_form id="1"]
```
- Formulario responsive con Bootstrap 5
- Validación HTML5 + JavaScript
- Envío por AJAX sin recargar página
- Genera código de seguimiento único
- Confirmación visual al enviar

**Shortcode 2: Mis Solicitudes**
```php
[ultima_milla_mis_solicitudes]
```
- Tabla Bootstrap con todas las solicitudes del usuario
- Ver detalle en modal
- Estados con colores (badges)
- Código de seguimiento visible

### **3. Módulo Mensajero (wp-admin)**

**Pantalla: Última Milla > Solicitudes**
- ✅ DataTable con búsqueda y filtros
- ✅ Ver solo solicitudes asignadas a él/ella
- ✅ Actualizar estado de solicitudes
- ✅ Ver detalle completo en modal
- ✅ Filtros rápidos por estado

### **4. Módulo Administrador (wp-admin)**

**Pantalla 1: Última Milla > Solicitudes**
- ✅ DataTable con todas las solicitudes del sistema
- ✅ Búsqueda global en tiempo real
- ✅ Filtros rápidos por estado (botones)
- ✅ Ordenamiento por columnas
- ✅ Paginación (25 registros por defecto)
- ✅ Ver información completa del cliente
- ✅ Asignar mensajero a solicitudes
- ✅ Cambiar estado de solicitudes
- ✅ Ver detalle en modal

**Pantalla 2: Última Milla > Formularios**
- ✅ Crear formularios personalizados
- ✅ Constructor de campos drag-free
- ✅ Copiar shortcode con un clic
- ✅ Publicar/Despublicar formularios
- ✅ Eliminar formularios (con confirmación)
- ✅ DataTable con búsqueda

### **5. Sistema de Estados**

| Estado | Descripción | Color | Cuándo |
|--------|-------------|-------|--------|
| **Solicitado** | Solicitud creada por el cliente | 🟡 Amarillo | Al crear la solicitud |
| **En Curso** | Mensajero asignado, en proceso | 🔵 Azul | Al asignar mensajero |
| **Entregado** | Servicio completado exitosamente | 🟢 Verde | Al marcar como entregado |
| **Cancelado** | Solicitud cancelada | 🔴 Rojo | Al cancelar manualmente |

### **6. Código de Seguimiento**

Cada solicitud genera un código único:
- Formato: `UM-XXXXXXXX` (ej: `UM-AB3C5D7F`)
- 8 caracteres alfanuméricos
- Generado automáticamente
- No se repite

---

## 📖 Guía de Uso Completa

### **PASO 1: Activar el Plugin**

1. Ve a **wp-admin → Plugins → Plugins Instalados**
2. Busca **"Última Milla"**
3. Haz clic en **"Activar"**

**Lo que sucede al activar:**
- ✅ Se crean los roles personalizados (Cliente, Mensajero)
- ✅ Se registran los Custom Post Types
- ✅ Se crea la tabla `wp_um_form_fields`
- ✅ Se agregan capacidades al rol Administrator

### **PASO 2: Crear Usuarios**

#### **Crear un Cliente:**
1. Ve a **Usuarios → Añadir Nuevo**
2. Completa los datos del usuario
3. En **Rol**, selecciona: **"Cliente Última Milla"**
4. Haz clic en **"Añadir Nuevo Usuario"**

#### **Crear un Mensajero:**
1. Ve a **Usuarios → Añadir Nuevo**
2. Completa los datos del usuario
3. En **Rol**, selecciona: **"Mensajero"**
4. Haz clic en **"Añadir Nuevo Usuario"**

### **PASO 3: Crear un Formulario**

1. Ve a **Última Milla → Formularios**
2. Haz clic en **"Añadir Nuevo"**
3. Se creará un formulario en blanco llamado "Nuevo Formulario"

#### **3.1 Configurar Información Básica**

En la sección **"Información del Formulario"**:

```
┌─────────────────────────────────────────┐
│ Nombre del Formulario: [____________]  │  ← Cambia a "Solicitud de Entrega"
│ Estado: [Borrador ▼]                   │  ← Selecciona "Publicado"
│ [💾 Guardar Cambios]                   │  ← Haz clic para guardar
└─────────────────────────────────────────┘
```

#### **3.2 Agregar Campos Personalizados (Opcional)**

1. En la sección **"Campos Personalizados"**, haz clic en **"Añadir Campo"**
2. Completa el formulario del modal:
   - **Tipo de Campo**: Email, Teléfono, Texto, etc.
   - **Etiqueta**: "Email de Contacto"
   - **Nombre**: Se auto-genera (ej: `email_de_contacto`)
   - ✅ Marca **"Campo obligatorio"** si aplica
3. Haz clic en **"Añadir Campo"**

**Ejemplo de campos adicionales:**
- Email de Contacto (tipo: Email, obligatorio)
- Teléfono (tipo: Teléfono, obligatorio)
- Comentarios Adicionales (tipo: Área de texto)

#### **3.3 Copiar el Shortcode**

En el sidebar derecho, verás:

```
┌─────────────────────────────────┐
│ Shortcode                       │
├─────────────────────────────────┤
│ [ultima_milla_form id="19"]     │
│ [📋 Copiar Shortcode]           │
└─────────────────────────────────┘
```

Haz clic en **"Copiar Shortcode"** → Verás una alerta de confirmación

### **PASO 4: Insertar el Formulario en una Página**

1. Ve a **Páginas → Añadir Nueva**
2. Título de la página: **"Solicitar Servicio"**
3. En el contenido (editor de bloques):
   - Agrega un bloque de **"Shortcode"**
   - Pega: `[ultima_milla_form id="19"]`
4. Haz clic en **"Publicar"**

**Resultado:** Los visitantes podrán crear solicitudes desde esa página.

### **PASO 5: Probar el Sistema**

#### **5.1 Como Cliente (Frontend)**

1. Cierra sesión del admin (o usa navegador incógnito)
2. Inicia sesión con el usuario Cliente creado
3. Ve a la página "Solicitar Servicio"
4. Llena el formulario:
   - Dirección de Origen: "Calle 123, Ciudad"
   - Dirección de Destino: "Avenida 456, Ciudad"
   - Descripción: "Paquete pequeño"
   - (Campos personalizados si los agregaste)
5. Haz clic en **"Enviar Solicitud"**

**Resultado:**
- ✅ Alerta de éxito con código de seguimiento
- ✅ Código: `UM-ABC12345` (ejemplo)

#### **5.2 Ver Mis Solicitudes (Cliente)**

1. Crea una nueva página
2. Inserta el shortcode: `[ultima_milla_mis_solicitudes]`
3. El cliente verá una tabla con todas sus solicitudes y estados

#### **5.3 Como Administrador (wp-admin)**

1. Inicia sesión como administrador
2. Ve a **Última Milla → Solicitudes**
3. Verás la solicitud creada con estado **"Solicitado"**

**Acciones disponibles:**
- 👁️ **Ver Detalle** - Modal con información completa
- 🔄 **Cambiar Estado** - Actualizar a En Curso, Entregado o Cancelado
- 👥 **Asignar Mensajero** - Seleccionar mensajero del dropdown

**Asignar Mensajero:**
1. Haz clic en el ícono de 👥
2. Selecciona un mensajero del dropdown
3. Haz clic en **"Asignar"**
4. El estado cambia automáticamente a **"En Curso"**

#### **5.4 Como Mensajero (wp-admin)**

1. Inicia sesión con el usuario Mensajero
2. Ve a **Última Milla → Solicitudes**
3. Verás **solo** las solicitudes asignadas a ti

**Acciones disponibles:**
- 👁️ **Ver Detalle** - Información completa del servicio
- 🔄 **Cambiar Estado** - Marcar como Entregado o Cancelado

---

## 🎨 Características de la Interfaz

### **DataTables (Área de Administración)**

Todas las tablas incluyen:

- ✅ **Búsqueda global** en tiempo real
- ✅ **Filtros rápidos** por estado (botones de colores)
- ✅ **Ordenamiento** por columnas (click en encabezado)
- ✅ **Paginación** con selector de registros por página
- ✅ **Persistencia** - Guarda búsquedas y filtros en localStorage
- ✅ **Totalmente responsive**
- ✅ **Textos en español**

**Ejemplo de uso:**
```
1. Escribe en "Buscar:" → Filtra en todas las columnas
2. Click en botón "Solicitado" → Muestra solo solicitados
3. Click en "Código" → Ordena por código
4. Selector "Mostrar 25 registros" → Cambia cantidad
```

### **SweetAlert2 (Alertas Elegantes)**

Todas las alertas usan SweetAlert2:

- ✅ **Confirmaciones** - Centradas en pantalla
- ✅ **Validaciones** - Mensajes claros con iconos
- ✅ **Éxito** - Auto-cierre después de 1.5-2 segundos
- ✅ **Errores** - Requieren confirmación del usuario

**Ejemplos:**

**Eliminar formulario:**
```
╔═══════════════════════════════════╗
║  ⚠️ ¿Eliminar formulario?        ║
║                                   ║
║  ¿Estás seguro de que deseas     ║
║  eliminar el formulario          ║
║  "Solicitud de Entrega"?         ║
║                                   ║
║  Esta acción no se puede         ║
║  deshacer.                        ║
║                                   ║
║  [Cancelar]  [Sí, eliminar]      ║
╚═══════════════════════════════════╝
```

**Éxito al copiar:**
```
╔═══════════════════════════════════╗
║  ✅ ¡Copiado!                    ║
║  Shortcode copiado al            ║
║  portapapeles                    ║
╚═══════════════════════════════════╝
   (Auto-cierre en 1.5s)
```

### **Modales Personalizados (wp-admin)**

Sistema de modales nativo sin Bootstrap:

- ✅ Backdrop oscuro con transparencia
- ✅ Cerrar con botón X
- ✅ Cerrar con click fuera del modal (backdrop)
- ✅ Cerrar con tecla ESC
- ✅ Scrollable si el contenido es muy largo
- ✅ Responsive

---

## 🔐 Seguridad Implementada

### **Validación y Sanitización**

- ✅ **Nonces** en todos los formularios (`wp_nonce_field`)
- ✅ **AJAX Nonces** verificados con `check_ajax_referer()`
- ✅ **Sanitización de inputs**:
  - `sanitize_text_field()` - Textos simples
  - `sanitize_textarea_field()` - Áreas de texto
  - `sanitize_key()` - Nombres de campos
  - `intval()` - Números
- ✅ **Escapado de outputs**:
  - `esc_html()` - Texto HTML
  - `esc_attr()` - Atributos HTML
  - `esc_url()` - URLs
- ✅ **Verificación de capacidades**: `current_user_can()`
- ✅ **Validación de tipos de post** antes de operaciones
- ✅ **Prevención de acceso directo** a archivos PHP

### **Control de Acceso**

**Clientes:**
- ❌ No pueden ver solicitudes de otros clientes
- ❌ No pueden acceder al área de formularios

**Mensajeros:**
- ❌ No pueden ver solicitudes no asignadas
- ❌ No pueden crear formularios
- ❌ No pueden asignar mensajeros

**Solo Administradores pueden:**
- ✅ Crear y editar formularios
- ✅ Ver todas las solicitudes
- ✅ Asignar mensajeros

---

## 🚀 Flujo de Trabajo Completo

### **Escenario Típico:**

```
1. ADMIN crea formulario
   ↓
2. ADMIN inserta shortcode en página "Solicitar Servicio"
   ↓
3. CLIENTE visita la página y llena el formulario
   ↓
4. Sistema genera solicitud con código único (UM-ABC12345)
   Estado: "Solicitado"
   ↓
5. ADMIN ve la solicitud en wp-admin → Solicitudes
   ↓
6. ADMIN asigna un MENSAJERO
   Estado cambia a: "En Curso"
   ↓
7. MENSAJERO ve la solicitud en su panel
   ↓
8. MENSAJERO entrega el paquete y actualiza estado
   Estado: "Entregado"
   ↓
9. CLIENTE puede consultar el estado en "Mis Solicitudes"
   Ve: "Entregado" con badge verde ✓
```

---

## 🔧 Configuración Avanzada

### **Activar Debug (Opcional)**

Para ver logs de depuración en la consola del navegador, los mensajes `console.log()` ya están incluidos.

**Abrir consola:** F12 → Console

Verás mensajes como:
```
Última Milla Admin JS cargado
AJAX URL: http://prueba.local/wp-admin/admin-ajax.php
Inicializando DataTable de solicitudes...
DataTable de solicitudes inicializado correctamente
```

### **Personalizar Estados**

Para agregar más estados, edita:

**Archivo:** `includes/class-post-types.php`

```php
public static function get_estados() {
    return array(
        'solicitado' => __('Solicitado', 'ultima-milla'),
        'en_curso' => __('En Curso', 'ultima-milla'),
        'entregado' => __('Entregado', 'ultima-milla'),
        'cancelado' => __('Cancelado', 'ultima-milla'),
        // Agregar nuevos estados aquí
        'devuelto' => __('Devuelto', 'ultima-milla'),
    );
}
```

### **Personalizar Colores de Estados**

**Archivo:** `includes/class-post-types.php`

```php
public static function get_estado_color($estado) {
    $colores = array(
        'solicitado' => 'warning',
        'en_curso' => 'info',
        'entregado' => 'success',
        'cancelado' => 'danger'
        // Agregar colores para nuevos estados
    );
    return isset($colores[$estado]) ? $colores[$estado] : 'secondary';
}
```

---

## 📊 Características Técnicas

### **Performance**

- ✅ **Carga condicional de assets** - Solo se cargan en páginas del plugin
- ✅ **Cache busting** - Timestamp en URLs de CSS/JS para forzar actualización
- ✅ **Lazy loading** - Modales se cargan pero permanecen ocultos
- ✅ **AJAX** - Todas las operaciones sin recargar página
- ✅ **LocalStorage** - DataTables guarda estado (búsquedas, filtros, página)

### **Internacionalización (i18n)**

- ✅ Todas las cadenas de texto usan `__()` y `_e()`
- ✅ Text Domain: `ultima-milla`
- ✅ Listo para traducción a otros idiomas
- ✅ Actualmente en español

### **Compatibilidad**

- ✅ WordPress 5.0+
- ✅ PHP 7.4+
- ✅ MySQL 5.6+
- ✅ Navegadores modernos (Chrome, Firefox, Safari, Edge)
- ✅ Responsive (móviles, tablets, desktop)

---

## 🎓 Casos de Uso

### **Caso 1: Empresa de Mensajería**
- Clientes solicitan envíos desde el sitio web
- Administrador asigna mensajeros disponibles
- Mensajeros actualizan estados desde sus celulares
- Seguimiento en tiempo real

### **Caso 2: Restaurante con Delivery**
- Clientes piden desde la web
- Administrador ve pedidos y asigna repartidores
- Repartidores marcan "Entregado" al llegar
- Cliente puede ver estado del pedido

### **Caso 3: Farmacia con Domicilio**
- Clientes solicitan medicamentos
- Farmacia asigna mensajero
- Seguimiento del estado del domicilio
- Confirmación de entrega

---

## 🐛 Solución de Problemas

### **Problema: No veo el menú "Última Milla"**
**Solución:**
- Verifica que el plugin esté activado
- Asegúrate de tener permisos de usuario
- Refresca la página (Ctrl+F5)

### **Problema: El shortcode no muestra el formulario**
**Solución:**
- Verifica que el ID del formulario sea correcto
- Asegúrate de que el formulario esté en estado "Publicado"
- Revisa la consola del navegador (F12) para errores

### **Problema: No puedo crear solicitudes**
**Solución:**
- Verifica que Bootstrap 5 esté cargando correctamente
- Abre la consola del navegador (F12) y busca errores
- Asegúrate de tener conexión a internet (CDN de Bootstrap)

### **Problema: DataTables no funciona**
**Solución:**
- Refresca con Ctrl+F5
- Verifica que jQuery esté cargando
- Abre consola y busca errores de JavaScript

---

## 📝 Shortcodes Disponibles

### **1. `[ultima_milla_form id="X"]`**

Muestra un formulario de solicitud específico.

**Parámetros:**
- `id` (obligatorio) - ID del formulario creado en el admin

**Ejemplo:**
```php
[ultima_milla_form id="1"]
```

**Dónde usar:**
- Páginas públicas
- Entradas (posts)
- Widgets de texto

**Requiere:**
- Formulario publicado
- Bootstrap 5 se carga automáticamente

---

### **2. `[ultima_milla_mis_solicitudes]`**

Muestra las solicitudes del usuario actual.

**Parámetros:**
- Ninguno

**Ejemplo:**
```php
[ultima_milla_mis_solicitudes]
```

**Requiere:**
- Usuario autenticado (logged in)
- Muestra mensaje si no está autenticado

**Dónde usar:**
- Página de perfil del cliente
- Área de miembros
- Dashboard personalizado

---

## 🔄 Hooks y Filtros (Para Desarrolladores)

### **Hooks de Activación**

```php
register_activation_hook(__FILE__, array($this, 'activate'));
```

**Lo que hace:**
- Crea roles personalizados
- Registra Custom Post Types
- Crea tabla de base de datos
- Flush rewrite rules

### **Actions Disponibles**

```php
// Al inicializar el plugin
add_action('init', array($this, 'init'));

// Al agregar menús admin
add_action('admin_menu', array($this, 'add_admin_menu'));

// Al encolar assets
add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
```

### **AJAX Endpoints**

Todos los endpoints requieren nonce para seguridad:

```php
// Crear solicitud
wp_ajax_um_crear_solicitud

// Obtener detalle
wp_ajax_um_obtener_detalle_solicitud

// Actualizar estado
wp_ajax_um_actualizar_estado

// Asignar mensajero
wp_ajax_um_asignar_mensajero

// Guardar campo de formulario
wp_ajax_um_guardar_campo_formulario

// Eliminar campo de formulario
wp_ajax_um_eliminar_campo_formulario

// Guardar formulario
wp_ajax_um_guardar_formulario

// Eliminar formulario
wp_ajax_um_eliminar_formulario
```

---

## 📚 Glosario

- **CPT** - Custom Post Type (Tipo de contenido personalizado)
- **Nonce** - Number Used Once (token de seguridad de WordPress)
- **AJAX** - Asynchronous JavaScript and XML
- **Shortcode** - Código corto que se expande a HTML
- **Hook** - Punto de enganche para ejecutar código
- **Capability** - Permiso/capacidad de un usuario

---

## 🎯 Próximas Mejoras Sugeridas

- [ ] Notificaciones por email al cambiar estados
- [ ] Integración con Google Maps para rutas
- [ ] Dashboard con estadísticas y gráficos
- [ ] Exportar solicitudes a CSV/Excel
- [ ] API REST para integración con apps móviles
- [ ] Sistema de calificación del servicio
- [ ] Chat en tiempo real entre cliente y mensajero
- [ ] Cálculo automático de tarifas por distancia
- [ ] Historial de cambios de estado (auditoría)
- [ ] Múltiples idiomas (POT/PO files)

---

## 📞 Soporte y Contribución

### **Reportar Issues**
Si encuentras un bug o tienes una sugerencia:
1. Ve al repositorio: https://github.com/ccdiego5/ultimamilla
2. Abre un "Issue" con descripción detallada
3. Incluye capturas de pantalla si es posible

### **Contribuir**
Pull requests son bienvenidos:
1. Fork el repositorio
2. Crea una rama: `git checkout -b feature/nueva-funcionalidad`
3. Commit cambios: `git commit -m 'Agregar nueva funcionalidad'`
4. Push a la rama: `git push origin feature/nueva-funcionalidad`
5. Abre un Pull Request

---

## 📄 Licencia

Este plugin está licenciado bajo **GPL v2 o posterior**.

WordPress Plugin License: https://www.gnu.org/licenses/gpl-2.0.html

---

## 👨‍💻 Créditos

**Desarrollado por:** Tu Nombre  
**Repositorio:** https://github.com/ccdiego5/ultimamilla  
**Versión:** 1.0.0  
**Fecha:** Enero 2026  

**Librerías Utilizadas:**
- WordPress Core
- jQuery (incluido en WordPress)
- DataTables 1.13.8
- SweetAlert2 v11
- Bootstrap 5.3.2 (solo frontend)
- Dashicons (iconos de WordPress)

---

## 📋 Changelog

### **v1.0.0 - 2026-01-20**
- ✅ Lanzamiento inicial
- ✅ Constructor de formularios dinámico
- ✅ Sistema de solicitudes con seguimiento
- ✅ Roles personalizados (Cliente, Mensajero)
- ✅ DataTables con búsqueda y filtros
- ✅ SweetAlert2 para alertas elegantes
- ✅ Modales personalizados estilo WordPress
- ✅ Shortcodes para frontend
- ✅ Sistema de estados (4 estados)
- ✅ Asignación de mensajeros
- ✅ Interfaz responsive
- ✅ Textos en español

---

**¿Necesitas ayuda?** Revisa la documentación completa o contacta al administrador del sitio.

**Desarrollado con ❤️ para gestión de última milla**
