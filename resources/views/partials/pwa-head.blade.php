{{-- Cabecera de PWA: lo que hace instalable la aplicación.

     El manifiesto se pide con `use-credentials` para que viaje la cookie de
     sesión y el nombre salga en el idioma del usuario; sin eso, el navegador lo
     pide en anónimo y siempre lo guardaría en el idioma por defecto. --}}
<link rel="manifest"
      href="{{ route('pwa.manifest') }}"
      crossorigin="use-credentials">
<meta name="theme-color" content="#0B1120">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ __('pwa.short_name') }}">
<link rel="apple-touch-icon" href="{{ asset('img/pwa/icon-192.png') }}">
