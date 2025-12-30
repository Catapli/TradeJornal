## Estructura de directorios

├── app/
│   ├── Actions/
│   │   ├── Jetstream/      # Acciones y Métodos del Jetstream
│   │   └── Fortify/        # Acciones y Métodos del Fortify
│   ├── Http/
│   │   ├── Controllers/    # Controladores de los Modelos
│   │   ├── Middleware/     # Clases refentes al Middleware
│   │   └── Resources/      # Recursos para pasar Modelos a Array
│   ├── Livewire/           # Clases Relacionadas al Livewire
│   │   ├── Forms/          # Auxiliares de Formulario para el livewire
│   │   └── Profile/        # Vistas de jetstream Perfil
│   ├── Models/             # Modelos
│   ├── Providers/          # Proveedores de servicios
│   ├── View/               # Vistas, pero al usar Livewire no se usan
│   │    └── Components/
│   └── LogActions          # Método para Insertar Log desde cualquier lugar
├── bootstrap/              # Configuración de Bootstrap (no se tocó)
├── config/                 # Configuración de Paquetes
├── database/               # Gestión de Base de datos
│   ├── factories/          # Plantillas de Modelos
│   ├── migrations/         # Migraciones para crear las tablas en la BD
│   └── seeders/            # Metodos para poblar la base de datos con datos
│
├── lang/                   # Directorio encargado de los lenguajes
│   ├── ca/                 # Directorio encargado de las clases de lenguaje Catalan para PHP
│   ├── es/                 # Directorio encargado de las clases de lenguaje Español para PHP
│   ├── es.json/            # Fichero encargado de las clases de lenguaje Español para JS
│   └── ca.json/            # Fichero encargado de las clases de lenguaje Catalán para JS
│
├── node_modules/           # Modulos de Node, mejor no tocar
│
├── public/                 # Directorio donde se encuentra todo lo publico
│   ├── build/              # nunca lo he usado
│   ├── datatable/          # Ficheros configuración del datatable en tailwind
│   ├── fonts/              # Fuentes de tipografia
│   ├── img/                # Imagenes y favicon
│   └── detrafic..json/     # Fichero Configuración Notificaciones Firebase
│
├── resources/              # Directorio donde se encuentran todos los recursos
│   ├── css/                # Compilación de tailwind css (NO TOCAR, Se actualiza solo)
│   ├── js/                 # Directorio donde está todo el js
│   │    ├── alerts/        # JS de Alertas
│   │    ├── cameras/       # JS de Camaras
│   │    ├── lists/         # JS de Listas
│   │    ├── logs/          # JS de LOGS
│   │    ├── plugins/       # Métodos Mágicos para las traducciones en JS
│   │    ├── towns/         # JS de Municipios
│   │    ├── traffic/       # JS de Tráfico
│   │    ├── users/         # JS de Usuarios
│   │    ├── utils/         # Inicializar mapa de google maps
│   │    ├── zones/         # JS de Zonas
│   │    ├── app.js         # Compilación de tailwind JS (NO TOCAR, Se actualiza solo)
│   │    └── bootstrap.js   # Configuración de Bootstrap JS
│   ├── markdown/
│   └── views/              # *Directorio de las vistas*
│       ├── alerts/         # Vistas referentes a Alertas
│       ├── api/            # Vistas referentes a API
│       ├── auth/           # Vistas de Gestion de Usuario
│       ├── cameras/        # Vistas de camaras
│       ├── components/     # Componentes de vista reutilizables
│       │        ├── buttons/            # Botones
│       │        └── modals/             # Modales
│       ├── emails/        # Vistas de Teams
│       ├── layouts/       # Vistas de Layouts / Esquemas principales
│       ├── lists/         # *Vistas de Listas*
│       ├── livewire/      # *Vistas de Los Livewires*
│       ├── logs/          # *Vistas de Logs*
│       ├── profile/       # *Vistas de la Gestión de Usuario*
│       ├── towns/         # *Vistas de municipios*
│       ├── traffic/       # *Vistas de Tráfico*
│       ├── users/         # *Vistas de Usuarios*
│       └── zones/         # *Vistas de Zonas*
|
├── routes/
│   ├── api.php             # *Rutas de la API EXTERNA*
│   ├── console.php
│   └── web.php             # *Rutas de navegación y api INTERNA*
├── storage/
│   ├── app/
│   ├── clockwork/          # *Extension para comprobar rendimiento laravel*
│   ├── debugbar/           # *Extension para comprobar rendimiento laravel*
│   └── logs/               # *Logs*
├── tests/
├── vendor/
├── .env
├── .env.example
├── composer.json
├── package.json
├── vite.config.js          # *Configuracion de VITE (no tocar)*
├── tailwind.config.js      # *Configuracion de Tailwind*
└── README.md