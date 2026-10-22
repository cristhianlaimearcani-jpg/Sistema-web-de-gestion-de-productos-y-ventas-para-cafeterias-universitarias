# CafeQR

Sistema web de gestión de productos y ventas para cafeterías universitarias.

CafeQR permite a los estudiantes pedir productos de la cafetería desde el celular o la computadora, pagar con QR y seguir el estado de su pedido. El personal de caja y el administrador gestionan productos, pagos, usuarios y cupones desde un panel.

## Integrantes

| Nombre | Rol |
|---|---|
| Cristhian Laime | Desarrollador |
| Andres Mamani | Desarrollador |
| Jhon Barros | Desarrollador |
| Saul Gutierrez | Desarrollador |

## Funcionalidades

- Registro e inicio de sesión con correo institucional
- Catálogo de productos y carrito de pedidos
- Pago mediante código QR (modo demo o modo banco)
- Seguimiento del pedido en tiempo de preparación
- Cupones de descuento
- Facturas y comprobantes
- Correos automáticos (bienvenida, pago confirmado)
- Panel de administración (usuarios, productos, correos)
- Perfil de usuario

## Tecnologías

- PHP 8.2+ con Laravel
- SQLite
- Blade, CSS y Vite
- Pest para pruebas

## Requisitos

- PHP 8.2 o superior
- Composer
- Node.js 18 o superior
- Git

## Instalación

```bash
git clone https://github.com/cristhianlaimearcani-jpg/Sistema-web-de-gestion-de-productos-y-ventas-para-cafeterias-universitarias.git
cd Sistema-web-de-gestion-de-productos-y-ventas-para-cafeterias-universitarias
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm run dev
```

En otra terminal:

```bash
php artisan serve
```

La aplicación queda disponible en http://localhost:8000

## Configuración

Edita el archivo `.env` (copiado de `.env.example`):

| Variable | Descripción |
|---|---|
| `MAIL_MAILER` | `log` guarda los correos en `storage/logs/laravel.log`; `smtp` los envía de verdad |
| `PAGO_MODO` | `demo` muestra una página de pago de prueba; `banco` usa el QR real de cobro |
| `DOMINIO_INSTITUCIONAL` | Dominio de correo permitido para registrarse |
| `PAGO_MINUTOS` | Minutos disponibles para pagar un pedido |
| `TIEMPO_PREPARACION` | Minutos estimados de preparación |

Nunca subas el archivo `.env` al repositorio: contiene claves y contraseñas.

## Usuario administrador

El administrador se crea automáticamente con `php artisan migrate`. Las credenciales por defecto están en `.env.example`.

## Pruebas

```bash
php artisan test
```

## Convención de commits

Usamos [Conventional Commits](https://www.conventionalcommits.org/es/):

| Prefijo | Uso |
|---|---|
| `feat:` | Nueva funcionalidad |
| `fix:` | Corrección de errores |
| `docs:` | Cambios en documentación |
| `style:` | Formato o estilos, sin cambiar la lógica |
| `refactor:` | Reorganización de código |
| `test:` | Pruebas |
| `chore:` | Tareas de mantenimiento |

Ejemplo: `feat: agregar panel de cupones`

## Flujo de trabajo con ramas

- `main`: versión estable
- `feature/nombre-de-la-tarea`: una rama por funcionalidad
- `fix/nombre-del-error`: una rama por corrección

Pasos:

1. Crear una rama desde `main`: `git checkout -b feature/mi-tarea`
2. Hacer commits siguiendo la convención
3. Subir la rama: `git push -u origin feature/mi-tarea`
4. Abrir un Pull Request hacia `main`
5. Otro integrante revisa y aprueba antes del merge

## Gestión del proyecto

Las tareas se registran como Issues y se organizan en el tablero Kanban de GitHub Projects. Cada Issue se asigna a un integrante.

## Documentación

- [Manual de usuario](docs/manual-usuario.pdf)

## Estructura del proyecto

```
app/          Modelos y controladores
config/       Configuración
database/     Migraciones y seeders
docs/         Documentación y manual de usuario
public/       Archivos públicos (CSS, imágenes)
resources/    Vistas Blade
routes/       Rutas de la aplicación
tests/        Pruebas
```

## Proyecto académico

Proyecto de Sistemas I, Universidad del Valle. Gestión 2-2026.