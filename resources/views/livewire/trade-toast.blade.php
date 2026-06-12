{{-- El listener del toast vive en resources/js/core/trade-toast.js --}}
{{-- 15s: el poll de 3s metía una petición de fondo constante que competía con la navegación --}}
<div wire:poll.15s="checkNotifications"></div>
