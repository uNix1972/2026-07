# Mejoras implementadas en SmartClinic

Este archivo resume los cambios aplicados para fortalecer la entrega técnica del sistema.

## 1. Enrutamiento corregido

Se actualizó `src/Utilities/Site.php` para conservar y resolver correctamente la capitalización real de los controladores. Con esto, rutas como las siguientes funcionan en Linux y Docker:

```text
index.php?page=CitasController
index.php?page=PacientesController
index.php?page=MedicosController
index.php?page=HomeController
index.php?page=Security_User
index.php?page=Sec_Login
```

## 2. Disponibilidad de citas corregida

En `src/Dao/Citas.php` se reemplazó la condición anterior que excluía el estado confirmado. Ahora las citas confirmadas sí bloquean horario y solo se excluyen estados no activos.

## 3. CSRF integrado

Se agregó token CSRF en formularios POST de:

- Login
- Perfil
- Pacientes
- Médicos
- Citas
- Usuarios
- Roles
- Funciones
- Contacto

También se cambiaron eliminaciones y cancelaciones de enlaces GET a formularios POST protegidos.

## 4. Docker y Dev Container

Se incorporaron los archivos necesarios para ejecutar el proyecto con Docker:

```text
Dockerfile
docker-compose.yml
.devcontainer/
```

## 5. Contacto con backend

El formulario de contacto registra mensajes en:

```text
data/contact_messages.json
```

## 6. Validaciones rápidas

Se agregó la carpeta `tests` con validaciones de sintaxis, validadores, rutas, CSRF y configuración Docker.

Para ejecutar:

```bash
bash tests/run_quality_checks.sh
```
