# BlueLock - Sistema de Gestión de Jugadores

## Tecnologías Principales
- **PHP 8.3** con patrón Singleton para conexiones DB
- **MySQL 8.0** (Esquema en `src/db/BlueLock.sql`)
- **Twig 3.19** para plantillas HTML
- **Bootstrap 5.3** para interfaz
- **Firebase JWT 6.10** para autenticación
- **PDO** para acceso a datos

## Requisitos Previos
1. PHP 8.3+
2. MySQL 8.0+
3. Composer
4. Servidor Apache

## Instalación
```bash
composer install
mysql -u root -p < src/db/BlueLock.sql
```
## Ejecución del scraper
Antes de iniciar el proyecto debes ejecutar el archivo para scrapear los datos
```bash
python3 ./src/scraper/scraper.py
```

## Funcionalidades Clave
### Autenticación JWT
- Registro de usuarios
- Login con generación de tokens
- Middleware de autenticación

### Gestión de Jugadores
- API REST para:
  - Listado completo de jugadores
  - Detalles por ID
  - Estadísticas técnicas (defensa, velocidad, etc.)

### Frontend
- Sistema de plantillas con Twig
- Diseño responsive con Bootstrap
- Internacionalización básica

## Estructura de Base de Datos
```sql
-- Tabla Users
CREATE TABLE Users(id INT PRIMARY KEY AUTO_INCREMENT, username VARCHAR(20) UNIQUE...);

-- Tabla Player
CREATE TABLE Player(player_id INT PRIMARY KEY, name VARCHAR(100)...);

-- Tabla Stats
CREATE TABLE Stats(stats_id INT PRIMARY KEY, def INT, spd INT...);
```

## Endpoints Principales
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | /api/players | Listado completo |
| GET | /api/players/{id} | Detalles de jugador |
| POST | /api/auth/signup | Registro usuario |
| POST | /api/auth/login | Autenticación |

## Configuración
Modificar credenciales en:
`src/config/database.php`
`src/config/twig.php`

## Licencia
MIT License
